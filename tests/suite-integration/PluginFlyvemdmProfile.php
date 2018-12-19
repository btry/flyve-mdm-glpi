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
 * @copyright Copyright © 2018 Teclib
 * @license   https://www.gnu.org/licenses/agpl.txt AGPLv3+
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

namespace tests\units;

use Flyvemdm\Tests\CommonTestCase;

class PluginFlyvemdmProfile extends CommonTestCase {
   /**
    * @param $method
    */
   public function beforeTestMethod($method) {
      switch ($method) {
         case 'testChangeProfile':
            $this->login('glpi', 'glpi');
            break;
      }
   }

   public function testChangeProfile() {
      $config = \Config::getConfigurationValues('flyvemdm', ['guest_profiles_id']);

      $this->array($_SESSION)->hasKey('plugin_flyvemdm_guest_profiles_id');
      $this->string($_SESSION['plugin_flyvemdm_guest_profiles_id'])
         ->isEqualTo($config['guest_profiles_id']);
      \PluginFlyvemdmProfile::changeProfile();
      $this->array($_SESSION)->hasKey('plugin_flyvemdm_guest_profiles_id');
      $this->string($_SESSION['plugin_flyvemdm_guest_profiles_id'])
         ->isEqualTo($config['guest_profiles_id']);
   }
}
