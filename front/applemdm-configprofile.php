<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2017 by the FusionInventory Development Team.
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
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

use CFPropertyList\CFArray;
use CFPropertyList\CFDictionary;
use CFPropertyList\CFNumber;
use CFPropertyList\CFPropertyList;
use CFPropertyList\CFString;

include ('../../../inc/includes.php');

$config = new PluginFlyvemdmApplemdmConfigurationProfile();

// https://developer.apple.com/library/content/featuredarticles/iPhoneConfigurationProfileRef/Introduction/Introduction.html
$plist = new CFPropertyList();
$plist->add($dict = new CFDictionary());
$dict->add('PayloadIdentifier', new CFString('org.flyve-mdm.test'));
$dict->add('PayloadUUID', new CFString(PluginFlyvemdmCommon::generateUUID()));
$dict->add('PayloadType', new CFString('Configuration'));
$dict->add('PayloadVersion', new CFNumber(1));
$dict->add('PayloadContent', $content = new CFArray());
$content->add($contentDict = new CFDictionary());
$contentDict->add('PayloadType', new CFString('Configuration'));
$contentDict->add('PayloadVersion', new CFNumber(1));
$contentDict->add('PayloadIdentifier', new CFString('org.flyve-mdm.test'));
$contentDict->add('PayloadUUID', new CFString(PluginFlyvemdmCommon::generateUUID()));
$contentDict->add('PayloadDisplayName', new CFString('Display name of profile'));
$contentDict->add('PayloadDescription', new CFString('some description'));

header('Content-Disposition: attachment; filename="configprofile.mobileconfig"');
header('Content-type: application/octet-stream');
header('Content-Length: '.strlen($xml = $config->getXml()));
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

echo $plist->toXML(true);
exit(0);

echo '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
  <dict>
    <key>PayloadIdentifier</key>
    <string>org.flyve-mdm.test</string>
    <key>PayloadUUID</key>
    <string>org.flyve-mdm.test</string>
    <key>PayloadType</key>
    <string>Configuration</string>
    <key>PayloadVersion</key>
    <integer>1</integer>
    <key>PayloadContent</key>
    <array>
      <dict>
			<key>PayloadDescription</key>
			<string>Configures restrictions</string>
			<key>PayloadDisplayName</key>
			<string>Restrictions</string>
			<key>PayloadIdentifier</key>
			<string>com.apple.applicationaccess.C4A82C9D-5FA7-4535-A7E9-2CD37839B8BE</string>
			<key>PayloadType</key>
			<string>com.apple.applicationaccess</string>
			<key>PayloadUUID</key>
			<string>C4A82C9D-5FA7-4535-A7E9-2CD37839B8BE</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
			<key>allowActivityContinuation</key>
			<true/>
			<key>allowAddingGameCenterFriends</key>
			<true/>
			<key>allowAirPlayIncomingRequests</key>
			<true/>
			<key>allowAirPrint</key>
			<true/>
			<key>allowAirPrintCredentialsStorage</key>
			<true/>
			<key>allowAirPrintiBeaconDiscovery</key>
			<true/>
			<key>allowAppCellularDataModification</key>
			<true/>
			<key>allowAppInstallation</key>
			<true/>
			<key>allowAppRemoval</key>
			<true/>
			<key>allowAssistant</key>
			<true/>
			<key>allowAssistantWhileLocked</key>
			<true/>
			<key>allowAutoCorrection</key>
			<true/>
			<key>allowAutomaticAppDownloads</key>
			<true/>
			<key>allowBluetoothModification</key>
			<true/>
			<key>allowBookstore</key>
			<true/>
			<key>allowBookstoreErotica</key>
			<true/>
			<key>allowCamera</key>
			<false/>
      </dict>
    </array>
  </dict>
</plist>
';

