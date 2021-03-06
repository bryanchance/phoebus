<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | =======================================================================================================

// Constants
const URI_PANEL     = '/panel/';
const URI_REG       = URI_PANEL . 'registration/';
const URI_LOGIN     = URI_PANEL . 'login/';
const URI_LOGOUT    = URI_PANEL . 'logout/';
const URI_ACCOUNT   = URI_PANEL . 'account/';
const URI_ADDONS    = URI_PANEL . 'addons/';
const URI_ADMIN     = URI_PANEL . 'administration/';

// Include modules
$arrayIncludes = ['database', 'auth', 'readManifest', 'generateContent'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$moduleAuth = new classAuthentication();
$moduleReadManifest = new classReadManifest();
$moduleGenerateContent = new classGenerateContent(true);

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Checks user level
*
* @param $_level    Required level
* @returns          true 404
***********************************************************************************************************************/
function funcCheckAccessLevel($aLevel) {
  if ($GLOBALS['arraySoftwareState']['authentication']['level'] >= $aLevel) {
    return true;
  }

  funcSend404();
}

// ====================================================================================================================

// == | Main | ========================================================================================================

$strComponentPath = dirname(COMPONENTS[$arraySoftwareState['requestComponent']]) . '/';

// --------------------------------------------------------------------------------------------------------------------

// Special case: Interlink should use Pale Moon's panel access
if ($arraySoftwareState['currentApplication'] == 'interlink') {
  funcRedirect('https://addons.palemoon.org/panel/');
}

// The Panel can ONLY be used on HTTPS
if (!in_array('https', TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['features'])) {
  funcError('The Phoebus Panel requires HTTPS, however this application\'s Add-ons Site does not have this feature enabled.</li>' .
            '<li>To access the Panel please try one of the other Phoebus-based Add-ons Sites.</li>' .
            '<li>If all else fails you can always use the Panel at the <a href="https://addons.palemoon.org/panel/">Pale Moon Add-ons Site</a>.');
}

if ($_SERVER['SCHEME'] != 'https') {
  funcRedirect('https://' . $arraySoftwareState['currentDomain'] . '/panel/');
}

// Use a simple switch case to deal with simple URIs
switch ($arraySoftwareState['requestPath']) {
  case URI_PANEL:
    $moduleGenerateContent->addonPanel('panel-frontpage.xhtml', 'Landing Page');
    break;
  case URI_REG:
    funcSendHeader('501');
    break;
  case URI_LOGOUT:
    $moduleAuth->authenticate(true);
    break;
  case URI_LOGIN:
    $moduleAuth->authenticate();
    funcRedirect('/panel/account/');
    break;
  case URI_ACCOUNT:
    $moduleAuth->authenticate();
    funcCheckAccessLevel(1);
    $moduleGenerateContent->addonPanel('panel-frontpage.xhtml', 'Account Page');
    break;
}

// --------------------------------------------------------------------------------------------------------------------

// Complex URIs need more complex conditional checking
if (startsWith($arraySoftwareState['requestPath'], URI_ADDONS)) {
  $moduleAuth->authenticate();
  funcCheckAccessLevel(1);
  $userAddons = $moduleReadManifest->getAddons('panel-user-addons', $arraySoftwareState['authentication']['addons']);
  $moduleGenerateContent->addonPanel('developer-addons-list', 'Your Add-ons', $userAddons);
}
elseif (startsWith($arraySoftwareState['requestPath'], URI_ADMIN)){
  require_once($strComponentPath . 'panelAdministration.php');
}

funcSend404();

// ====================================================================================================================

?>
