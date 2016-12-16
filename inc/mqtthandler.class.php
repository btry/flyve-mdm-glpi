<?php
/**
 LICENSE

Copyright (C) 2016 Teclib'
Copyright (C) 2010-2016 by the FusionInventory Development Team.

This file is part of Flyve MDM Plugin for GLPI.

Flyve MDM Plugin for GLPi is a subproject of Flyve MDM. Flyve MDM is a mobile
device management software.

Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
modify it under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
You should have received a copy of the GNU Affero General Public License
along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 ------------------------------------------------------------------------------
 @author    Thierry Bugier Pineau
 @copyright Copyright (c) 2016 Flyve MDM plugin team
 @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 @link      https://github.com/flyvemdm/backend
 @link      http://www.glpi-project.org/
 ------------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginStorkmdmMqtthandler extends sskaje\mqtt\MessageHandler {

   /**
    * @var PluginStorkmdmMqttlog $log mqtt messages logger
    */
   protected $log;

   /**
    * @var integer $starttime Time when the subscribtion started
    */
   protected $startTime;

   protected $flyveManifestMissing = true;
   protected $publishedVersion = null;
   protected static $instance = null;

   protected function __construct() {
      $this->log = new PluginStorkmdmMqttlog();
      $this->startTime = time();
   }

   public static function getInstance() {
      if (self::$instance === null) {
         self::$instance = new static();
      }
      return self::$instance;
   }

   protected function publishManifest($mqtt) {
      // Don't use version from the constant in setup.php because the backend may upgrade while this script is running
      // thus keep in RAM in an older version
      $config = Config::getConfigurationValues('storkmdm', array('version'));
      $version = $config['version'];

      if ($this->flyveManifestMissing) {
         preg_match('/^([\d\.]+)/', $version, $matches);
         if (isset($matches[1])) {
            $mqtt->publish_async("/FlyvemdmManifest/Status/Version", $matches[1], 0, 1);
            $this->flyveManifestMissing = false;
         }
      }
   }

   /**
    * Handle MQTT Ping response
    * @param sskaje\mqtt\MQTT $mqtt
    * @param sskaje\mqtt\Message\PINGRESP $pingresp_object
    */
   public function pingresp(sskaje\mqtt\MQTT $mqtt, sskaje\mqtt\Message\PINGRESP $pingresp_object) {
      global $DB;

      if (time() - $this->startTime > PluginStorkmdmMqttclient::MQTT_MAXIMUM_DURATION) {
         $mqtt->unsubscribe(array('#'));
      } else {
         // Reconnect to DB to avoid timeouts
         $DB->connect();

         $this->publishManifest($mqtt);
      }
   }

   /**
    * Handle MQTT publish messages
    * {@inheritDoc}
    * @see \sskaje\mqtt\MessageHandler::publish()
    */
   public function publish(sskaje\mqtt\MQTT $mqtt, sskaje\mqtt\Message\PUBLISH $publish_object) {
      $topic = $publish_object->getTopic();
      $message = $publish_object->getMessage();
      $this->log->saveIngoingMqttMessage($topic, $publish_object->getMessage());

      $mqttPath = explode('/', $topic, 5);
      if (isset($mqttPath[4])) {
         if ( $mqttPath[4] == "Status/Ping" && $message == "!") {
            $this->updateLastContact($topic, $message);
         } else if ($mqttPath[4] == "Status/Geolocation"  && $message != "?") {
            $this->saveGeolocationPosition($topic, $message);
         } else if ($mqttPath[4] == "Status/Install") {
            $this->saveInstallationFeedback($topic, $message);
         } else if ($mqttPath[4] == "Status/Unenroll") {
            $this->deleteAgent($topic, $message);
         } else if ($mqttPath[4] == "Status/Inventory") {
            $this->updateInventory($topic, $message);
         } else if ($mqttPath[4] == "FlyvemdmManifest/Status/Version") {
            $this->updateAgentVersion($topic, $message);
         } else if (strpos($topic, "/FlyvemdmManifest") === 0) {
            if ($topic == '/FlyvemdmManifest/Status/Version') {
               $this->publishFlyveManifest();
            }
         }
      }
   }

   /**
    * Update the version of an agent
    * @param string $topic
    * @param string $message
    */
   protected function updateAgentVersion($topic, $message) {
      $agent = new PluginStorkmdmAgent();
      if ($agent->getByTopic($topic) !== false) {
         preg_match("#^[\d.]+$#", $message, $sanitized);
         if (!empty($sanitized[0])) {
            $agent->update([
                  'id'        => $agent->getID(),
                  'version'   => $sanitized[0],
            ]);
         }
      }
   }

   protected function publishFlyveManifest() {
      // Don't use version from the cosntant in setup.php because the backend may upgrade while this script is running
      // thus keep in RAM in an older version
      $config = Config::getConfigurationValues('storkmdm', 'version');
      $version = $config['version'];

      preg_match('/^([\d\.]+)/', $version, $matches);
      if (!isset($matches[1]) || (isset($matches[1]) && $matches[1] != $message)) {
         $this->flyveManifestMissing = true;
         $this->publishManifest($mqtt);
      }
   }

   protected function updateInventory($topic, $message) {
      global $DB;

      $mqttPath = explode('/', $topic);
      $entityId = $mqttPath[1];
      $serial = $DB->escape($mqttPath[3]);
      $computer = new Computer();
      if ($computer->getFromDBByQuery("WHERE `entities_id` = '$entityId' AND `serial` = '$serial'")) {
         $inventoryXML = $message;
         $communication = new PluginFusioninventoryCommunication();
         ob_start();
         $communication->handleOCSCommunication('', $inventoryXML);
         ob_end_clean();

         $this->updateLastContact($topic, $message);
      }
   }

   protected function updateLastContact($topic, $message) {
      $agent = new PluginStorkmdmAgent();
      if ($agent->getByTopic($topic)) {

         $date = new DateTime("now", new DateTimeZone("UTC"));
         $agent->update([
               'id'              => $agent->getID(),
               'last_contact'    => $date->format('Y-m-d H:i:s')
         ]);
      }
   }

   protected function deleteAgent($topic, $message) {
      $agent = new PluginStorkmdmAgent();
      $agent->getByTopic($topic);
      $agent->delete([
            'id'  => $agent->getID(),
      ]);
   }

   protected function saveInstallationFeedback($topic, $message) {
      if ($message = json_decode($message, true)) {
         $agent = new PluginStorkmdmAgent();
         if ($agent->getByTopic($topic)
               && isset($message['ack'])) {
            $agentId = $agent->getID();
            $package = new PluginStorkmdmPackage();
            $name = $message['ack'];
            $package->getFromDBByQuery("WHERE `name`='$name'");
            $packageId = $package->getID();
            $agent_Package = new PluginStorkmdmAgent_Package();
            $query = "WHERE `plugin_storkmdm_packages_id`='$packageId'
            AND `plugin_storkmdm_agents_id`='$agentId'";
            if ($agent_Package->getFromDBByQuery($query)) {
               $agent_Package->update([
                     'id'     => $agent_Package->getID(),
                     'status' => 'DOWNLOADED'
               ]);
            }

            $this->updateLastContact($topic, $message);
         }
      }
   }

   protected function saveGeolocationPosition($topic, $message) {
      $agent = new PluginStorkmdmAgent();
      if ($agent->getByTopic($topic)) {
         $position = json_decode($message, true);
         if (isset($position['datetime'])) {
            $dateGeolocation = DateTime::createFromFormat('U', $position['datetime'], new DateTimeZone("UTC"));
         } else {
            $dateGeolocation = false;
         }
         if (isset($position['latitude']) && isset($position['longitude'])) {
            if ($dateGeolocation !== false) {
               $geolocation = new PluginStorkmdmGeolocation();
               $geolocation->add([
                     'computers_id'       => $agent->getField('computers_id'),
                     'date'               => $dateGeolocation->format('Y-m-d H:i:s'),
                     'latitude'           => $position['latitude'],
                     'longitude'          => $position['longitude']
               ]);
            }
         } else if (isset($position['gps']) && strtolower($position['gps']) == 'off') {
            // No GPS geolocation available at this time, log it anyway
            if ($dateGeolocation !== false) {
               $geolocation = new PluginStorkmdmGeolocation();
               $geolocation->add([
                     'computers_id'       => $agent->getField('computers_id'),
                     'date'               => $dateGeolocation->format('Y-m-d H:i:s'),
                     'latitude'           => 'na',
                     'longitude'          => 'na'
               ]);
            }
         }

         $this->updateLastContact($topic, $message);
      }
   }
}
