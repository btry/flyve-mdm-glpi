<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2018 Teclib'
 * Copyright © 2010-2018 by the FusionInventory Development Team.
 *
 * This file is part of Flyve MDM Plugin for GLPI.
 *
 * Flyve MDM Plugin for GLPI is a subproject of Flyve MDM. Flyve MDM is a mobile
 * device management software.
 *
 * Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 * ------------------------------------------------------------------------------
 * @author    Thierry Bugier
 * @copyright Copyright © 2018 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmConfig extends CommonDBTM {
   // From CommonGLPI
   protected $displaylist         = false;

   // From CommonDBTM
   public $auto_message_on_action = false;
   public $showdebug              = true;

   static $rightname              = 'config';

   // Type reservation : https://forge.indepnet.net/projects/plugins/wiki/PluginTypesReservation
   const RESERVED_TYPE_RANGE_MIN = 11000;
   const RESERVED_TYPE_RANGE_MAX = 11049;

   const PLUGIN_FLYVEMDM_MQTT_CLIENT = 'flyvemdm';

   // first and last steps of the welcome pages of wizard
   const WIZARD_WELCOME_BEGIN = 1;
   const WIZARD_WELCOME_END = 1;

   // first and last steps of the MQTT pages of wizard
   const WIZARD_MQTT_BEGIN = 100;
   const WIZARD_MQTT_END = 105;

   // first and last steps of the MQTT pages of wizard
   const WIZARD_APPLEMDM_BEGIN = 200;
   const WIZARD_APPLEMDM_END = 299;

   // To create a Apple Developer Enterprise account
   const WIZARD_APPLEMDM_CREATE_ADEA_BEGIN = 210;
   const WIZARD_APPLEMDM_CREATE_ADEA_END = 210;

   // To create a vendor CSR and get a vendor certificate from Apple
   const WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN = 220;
   const WIZARD_APPLEMDM_CREATE_VENDOR_CSR_END = 223;

   // To create a customer CSR and get a certificate from a vendor
   const WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN = 230;
   const WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_END = 233;

   // To sign a customer CST witha vendor certificate
   const WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN = 240;
   const WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_END = 243;

   // To install a customer certificate
   const WIZARD_APPLEMDM_USE_CUSTOMER_CRT_BEGIN = 250;
   const WIZARD_APPLEMDM_USE_CUSTOMER_CRT_END = 250;

   const WIZARD_FINISH = -1;
   static $config = [];

   /**
    * @param string|null $classname
    * @return string
    */
   public static function getTable($classname = null) {
      return Config::getTable();
   }

   /**
    * Gets permission to create an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canCreate() {
      return (!isAPI() && parent::canCreate());
   }

   /**
    * Gets permission to view an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canView() {
      return (!isAPI() && parent::canView());
   }

   /**
    * Gets permission to update an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canUpdate() {
      return (!isAPI() && parent::canUpdate());
   }

   /**
    * Gets permission to delete an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canDelete() {
      return (!isAPI() && parent::canDelete());
   }

   /**
    * Gets permission to purge an instance of the itemtype
    * @return boolean true if permission granted, false otherwise
    */
   public static function canPurge() {
      return (!isAPI() && parent::canPurge());
   }

   /**
    * Define tabs available for this itemtype
    * @return array
    */
   public function defineTabs($options = []) {
      $tab = [];
      $this->addStandardTab(__CLASS__, $tab, $options);

      return $tab;
   }

   /**
    * @param CommonGLPI $item
    * @param integer $withtemplate
    * @return string
    */
   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      switch ($item->getType()) {
         case __CLASS__:
            $tabs = [];
            $config = Config::getConfigurationValues('flyvemdm', ['show_wizard']);
            if ($config['show_wizard'] !== '0') {
               $tabs[1] = __('Installation wizard', 'flyvemdm');
            }
            $tabs[2] = __('General configuration', 'flyvemdm');
            $tabs[3] = __('Message queue', 'flyvemdm');
            $tabs[4] = __('Apple devices', 'flyvemdm');
            $tabs[5] = __('Debug', 'flyvemdm');
            return $tabs;
            break;
      }

      return '';
   }

   /**
    * @param CommonGLPI $item object
    * @param integer $tabnum (default 1)
    * @param integer $withtemplate (default 0)
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1:
               $item->showFormWizard();
               break;

            case 2:
               $item->showFormGeneral();
               break;

            case 3:
               $item->showFormMessageQueue();
               break;

            case 4:
               $item->showFormAppleMdm();
               break;

            case 5:
               $item->showFormDebug();
               break;
         }
      }

   }

   /**
    * adds document types needed by the plugin in GLPI configuration
    */
   public function addDocumentTypes() {
      $extensions = [
            'apk'    => 'Android application package',
            'upk'    => 'Uhuru application package'
      ];

      foreach ($extensions as $extension => $name) {
         $documentType = new DocumentType();
         if (!$documentType->getFromDBByQuery("WHERE LOWER(`ext`)='$extension'")) {
            $documentType->add([
                  'name'            => $name,
                  'ext'             => $extension,
                  'is_uploadable'   => '1',
            ]);
         }
      }
   }

   /**
    * Displays the general configuration form for the plugin.
    */
   public function showFormGeneral() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
      }

      $fields = Config::getConfigurationValues('flyvemdm');
      $fields['android_bugcollector_passwd_placeholder'] = __('Bugcollector password', 'flyvemdm');
      if (strlen($fields['android_bugcollector_passwd']) > 0) {
         $fields['android_bugcollector_passwd_placeholder'] = '******';
      }
      unset($fields['android_bugcollector_passwd']);

      $fields['computertypes_id'] = ComputerType::dropdown([
                                                            'display' => false,
                                                            'name'   => 'computertypes_id',
                                                            'value'  => $fields['computertypes_id'],
                                                          ]);
      $fields['agentusercategories_id'] = UserCategory::dropdown([
                                                            'display' => false,
                                                            'name'   => 'agentusercategories_id',
                                                            'value'  => $fields['agentusercategories_id'],
                                                           ]);
      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config.html', $data);

      Html::closeForm();
   }

   /**
    * Displays the message queue configuration form for the plugin.
    */
   public function showFormMessageQueue() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
      }

      $fields = Config::getConfigurationValues('flyvemdm');
      unset($fields['android_bugcollector_passwd']);

      $fields['mqtt_tls_for_clients'] = Dropdown::showYesNo(
         'mqtt_tls_for_clients', $fields['mqtt_tls_for_clients'],
         -1,
         ['display' => false]
      );

      $fields['mqtt_tls_for_backend'] = Dropdown::showYesNo(
         'mqtt_tls_for_backend', $fields['mqtt_tls_for_backend'],
         -1,
         ['display' => false]
      );

      $fields['mqtt_use_client_cert'] = Dropdown::showYesNo(
         'mqtt_use_client_cert',
         $fields['mqtt_use_client_cert'],
         -1,
         ['display' => false]
      );

      $fields['CACertificateFile'] = Html::file([
         'name'      => 'CACertificateFile',
         'display'   => false,
      ]);

      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config-messagequeue.html', $data);

      Html::closeForm();
   }

   public function showFormAppleMdm() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
      }

      $fields = Config::getConfigurationValues('flyvemdm');

      $data = [
         'config' => $fields
      ];
      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config-applemdm.html', $data);

      Html::closeForm();

   }

   /**
    * Displays the message queue configuration form for the plugin.
    */
   public function showFormDebug() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';
      }

      $fields = Config::getConfigurationValues('flyvemdm');
      unset($fields['android_bugcollector_passwd']);

      $fields['debug_enrolment'] = Dropdown::showYesNo(
         'debug_enrolment',
         $fields['debug_enrolment'],
         -1,
         ['display' => false]
      );
      $fields['debug_noexpire'] = Dropdown::showYesNo(
         'debug_noexpire',
         $fields['debug_noexpire'],
         -1,
         ['display' => false]
      );
      $fields['show_wizard'] = Dropdown::showYesNo(
         'show_wizard',
         $fields['show_wizard'],
         -1,
         ['display' => false]
      );

      $data = [
         'config' => $fields
      ];

      $twig = plugin_flyvemdm_getTemplateEngine();
      echo $twig->render('config-debug.html', $data);

      Html::closeForm();
   }

   /**
    * Displays the message queue configuration form for the plugin.
    */
   public function showFormWizard() {
      $canedit = PluginFlyvemdmConfig::canUpdate();
      if ($canedit) {
         if (!isset($_SESSION['plugin_flyvemdm_wizard_step'])) {
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_WELCOME_BEGIN;
         }
         echo '<form name="form" id="pluginFlyvemdm-config" method="post" action="' . Toolbox::getItemTypeFormURL(__CLASS__) . '">';

         $texts = [];
         $data = [];
         $paragraph = 1;
         switch ($_SESSION['plugin_flyvemdm_wizard_step']) {
            case static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN + 2:
               $data['priv_key'] = file_get_contents(FLYVEMDM_CONFIG_PATH . '/applemdm/vendor/vendor_privkey.pem');
               if ($data['priv_key'] === false) {
                  $data['priv_key'] = '';
               }
               $data['pub_key'] = file_get_contents(FLYVEMDM_CONFIG_PATH . '/applemdm/vendor/vendor_pubkey.pem');
               if ($data['pub_key'] === false) {
                  $data['pub_key'] = '';
               }
               $data['csr'] = file_get_contents(FLYVEMDM_CONFIG_PATH . '/applemdm/vendor/vendor_request.csr');
               if ($data['csr'] === false) {
                  $data['csr'] = '';
               }
               break;

            case static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN + 2:
               $data['priv_key'] = file_get_contents(FLYVEMDM_CONFIG_PATH . '/applemdm/customer/customer_privkey.pem');
               if ($data['priv_key'] === false) {
                  $data['priv_key'] = '';
               }
               $data['pub_key'] = file_get_contents(FLYVEMDM_CONFIG_PATH . '/applemdm/customer/customer_pubkey.pem');
               if ($data['pub_key'] === false) {
                  $data['pub_key'] = '';
               }
               $data['csr'] = file_get_contents(FLYVEMDM_CONFIG_PATH . '/applemdm/customer/customer_request.csr');
               if ($data['csr'] === false) {
                  $data['csr'] = '';
               }
               break;

            case static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN + 2:
               $data['customer_crt'] = file_get_contents(FLYVEMDM_CONFIG_PATH . '/applemdm/customer/cert.crt');
               if ($data['customer_crt'] === false) {
                  $data['customer_crt'] = '';
               }
               break;
         }

         $data = $data + [
            'texts'  => $texts,
            'update' => $_SESSION['plugin_flyvemdm_wizard_step'] === static::WIZARD_FINISH ? __('Finish', 'flyvemdm') : __('Next', 'flyvemdm'),
            'step' => $_SESSION['plugin_flyvemdm_wizard_step'],
            'appleEnterpriseDeveloperUrl' => PLUGIN_FLYVEMDM_APPLE_DEVELOPER_ENTERPRISE_URL,
         ];
         $twig = plugin_flyvemdm_getTemplateEngine();
         echo $twig->render('config-wizard.html', $data);

         Html::closeForm();
      }
   }

   /**
    * Initializes the instance of the item with default values
    */
   public function post_getEmpty() {
      $this->fields['id'] = 1;
      $this->fields['mqtt_broker_address'] = '127.0.0.1';
      $this->fields['mqtt_broker_port'] = '1883';
      $this->fields['mqtt_broker_tls_port'] = '8883';
   }

   /**
    * Hook for config validation before update
    * @param array $input configuration settings
    * @return array
    */
   public static function configUpdate($input) {
      if (isset($input['back'])) {
         // Going one step backwards in wizard
         return static::backwardStep();
      }

      // process certificates update
      if (isset($input['_CACertificateFile'])) {
         if (isset($input['_CACertificateFile'][0])) {
            $file = GLPI_TMP_DIR . "/" . $input['_CACertificateFile'][0];
            if (is_writable($file)) {
               rename($file, FLYVEMDM_CONFIG_CACERTMQTT);
            }
         }
      }

      if (isset($input['invitation_deeplink'])) {
         // Ensure there is a trailing slash
         if (strrpos($input['invitation_deeplink'], '/') != strlen($input['invitation_deeplink']) - 1) {
            $input['invitation_deeplink'] .= '/';
         }
      }

      // Build a CSR for Apple MDM
      if (isset($input['applemdm_privkey_password'])) {
         $input = static::appleMdmCreateCsr($input);
      }

      if (isset($_SESSION['plugin_flyvemdm_wizard_step'])) {
         $input = static::processStep($input);
         if (count($input) > 0 && $input !== false) {
            static::forwardStep($input);
         } else {
            $input = [];
         }
      }

      unset($input['_CACertificateFile']);
      unset($input['_tag_CACertificateFile']);
      unset($input['CACertificateFile']);
      return $input;
   }

   /**
    * Does an action for the step saved in session, and defines the next step to run
    * @param array $input the data send in the submitted step
    * @return array modified input
    */
   protected static function processStep($input) {
      switch ($_SESSION['plugin_flyvemdm_wizard_step']) {
         case static::WIZARD_FINISH:
            Config::setConfigurationValues('flyvemdm', ['show_wizard' => '0']);
            break;

         case static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN + 1:
            $input['is_vendor_cert'] = 1;
            $input = static::appleMdmCreateCsr($input);
            break;
         case static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN + 1:
            $input['is_customer_cert'] = 1;
            $input = static::appleMdmCreateCsr($input);
            break;
         case static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN + 1:
            $input = static::appleMdmSignCustomerCsr($input);
      }
      return $input;
   }

   /**
    * Generates a private key and a CSR using the date from the Apple MDM form
    * @param array $input
    * @return array|false false if creation failed
    */
   protected static function appleMdmCreateCsr($input) {
      $subDir = null;
      if (isset($input['is_vendor_cert'])) {
         $subDir = 'vendor';
      }
      if (isset($input['is_customer_cert'])) {
         $subDir = 'customer';
      }
      if ($subDir === null) {
         Session::addMessageAfterRedirect(__('Internal wizard error', 'flyvemdm'), false, ERROR);
         return false;
      }

      $destinationDir = FLYVEMDM_CONFIG_PATH . "/applemdm/$subDir";
       // Create folder for keys
       $applemdmCrtDir = FLYVEMDM_CONFIG_PATH . "/applemdm/$subDir";
       if (!is_dir($applemdmCrtDir) && !is_readable($applemdmCrtDir)) {
          if (! @mkdir($applemdmCrtDir, 0770, true)) {
             Toolbox::logInFile('php-errors', "Could not create directory $applemdmCrtDir");
             return false;
          }
       }
      $prefix = $subDir;
      $options = [
         'country_code' => $input['applemdm_country_code'],
         'common_name'  => $input['applemdm_common_name'],
         'email'        => $input['applemdm_email'],
         'passphrase'   => $input['applemdm_privkey_passphrase'],
      ];
      $certTool = new PluginFlyvemdmApplemdmCertTool();
      if (!$certTool->generateCsr($destinationDir, $prefix, $options)) {
         return false;
      }
      return $input;
   }

   /**
    * Signs a customer certificate with the vendor private key
    * @param array $input
    * @param array $input
    */
   protected static function appleMdmSignCustomerCsr($input) {
      if (strlen($input['vendor_privkey_passphrase']) < 1) {
         Session::addMessageAfterRedirect(__('You must provide the passphrase of the vendor private key', 'flyvemdm'), false, ERROR);
         return false;
      }

      $vendorDir = FLYVEMDM_CONFIG_PATH . "/applemdm/vendor";
      $customerDir = FLYVEMDM_CONFIG_PATH . "/applemdm/customer";
      $certTool = new PluginFlyvemdmApplemdmCertTool();
      if (!$certTool->generatePlist($vendorDir, $customerDir, $input['vendor_privkey_passphrase'])) {
          return false;
      }

      return $input;
   }

   /**
    * Do the vendor certificate exists in the filesystem ?
    * @return boolean true if the certificate exists, false otherwise
    */
   protected static function vendorCertExists() {
      $directory = FLYVEMDM_CONFIG_PATH . "/applemdm/vendor";
      if (!file_exists("$directory/vendor_privkey.pem")) {
         return false;
      }

      if (!file_exists("$directory/cert.crt")) {
         return false;
      }

      return true;
   }

   /**
    * Do the customer certificate reqiest exists in the filesystem ?
    * @return boolean true if the CSR exists, false otherwise
    */
   protected static function customerCsrExists() {
      $directory = FLYVEMDM_CONFIG_PATH . "/applemdm/customer";
      if (!file_exists("file://$directory/customer_request.csr")) {
         return false;
      }

      return true;
   }

   /**
    * Goes one step forward in the wizard
    * @param array $input the data send in the submitted step
    */
   protected static function forwardStep($input) {
      // Choose next step depending on current step and form data
      switch ($_SESSION['plugin_flyvemdm_wizard_step']) {
         case static::WIZARD_WELCOME_END:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_MQTT_BEGIN;
            break;

         case static::WIZARD_MQTT_END:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_BEGIN;
            break;

         case static::WIZARD_APPLEMDM_BEGIN:
            // Choosing how to obtain the MDM certificate
            switch ($input['apple_certificate']) {
               case 'create enterprise account':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_ADEA_BEGIN;
                  break;

               case 'use enterprise account':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN;
                  break;

               case 'create customer certificate':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN;
                  break;

               case 'use customer certificate':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_USE_CUSTOMER_CRT_BEGIN;
                  break;

               case 'sign customer certificate':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN;
                  break;

               case 'skip apple mdm':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_FINISH;
                  break;
            }
            break;

         case static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN:
            switch ($input['customer_siging']) {
               case 'sign csr':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN + 1;
                  break;

               case 'manually sign csr':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN + 3;
                  break;
            }
            break;

         case static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN:
            switch ($input['generate_csr']) {
               case 'generate csr':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN + 1;
                  break;

               case 'manually generate csr':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN + 3;
                  break;
            }
            break;

         case static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN + 2:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_BEGIN;
            break;

         case static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN:
            switch ($input['generate_csr']) {
               case 'generate csr':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN + 1;
                  break;

               case 'manually generate csr':
                  $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN + 3;
                  break;
            }
            break;

         case static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN + 2:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_BEGIN;
            break;

         case static::WIZARD_APPLEMDM_CREATE_ADEA_END:
         case static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_END:
         case static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_END:
         case static::WIZARD_APPLEMDM_USE_CUSTOMER_CRT_END:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_BEGIN;
            break;

         case static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN + 2:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_FINISH;
            break;

         default:
            $_SESSION['plugin_flyvemdm_wizard_step']++;
      }
   }

   /**
    * Goes one step backward in the wizard
    */
   protected static function backwardStep() {
      switch ($_SESSION['plugin_flyvemdm_wizard_step']) {
         case static::WIZARD_MQTT_BEGIN:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_WELCOME_END;
            break;

         case static::WIZARD_APPLEMDM_BEGIN:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_MQTT_END;
            break;

         case static::WIZARD_APPLEMDM_CREATE_ADEA_BEGIN:
         case static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN:
         case static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN:
         case static::WIZARD_APPLEMDM_USE_CUSTOMER_CRT_BEGIN:
         case static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_BEGIN;
            break;

         case static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN + 3:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_VENDOR_CSR_BEGIN;
            break;

         case static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN + 3:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_CREATE_CUSTOMER_CRT_BEGIN;
            break;

         case static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN + 3:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_SIGN_CUSTOMER_CSR_BEGIN;
            break;

         case static::WIZARD_FINISH:
            $_SESSION['plugin_flyvemdm_wizard_step'] = static::WIZARD_APPLEMDM_BEGIN;
            break;

         default:
            $_SESSION['plugin_flyvemdm_wizard_step']--;
      }

      return [];
   }

   /**
    * Remove the value from sensitive configuration entry
    * @param array $fields
    * @return array the filtered configuration entry
    */
   public static function undiscloseConfigValue($fields) {
      $undisclosed = [
            'mqtt_passwd',
            'android_bugcollector_passwd',
      ];

      if ($fields['context'] == 'flyvemdm'
            && in_array($fields['name'], $undisclosed)) {
         unset($fields['value']);
      }
      return $fields;
   }
}
