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
$arrayIncludes = ['database', 'account', 'readManifest', 'writeManifest', 'generateContent'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase         = new classDatabase();
$moduleAccount          = new classAccount();
$moduleReadManifest     = new classReadManifest();
$moduleWriteManifest    = new classWriteManifest();
$moduleGenerateContent  = new classGenerateContent('smarty');

// Request arguments
$arraySoftwareState['requestPanelTask'] = funcUnifiedVariable('get', 'task');
$arraySoftwareState['requestPanelWhat'] = funcUnifiedVariable('get', 'what');
$arraySoftwareState['requestPanelSlug'] = funcUnifiedVariable('get', 'slug');

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Checks user level
*
* @param $_level    Required level
* @returns          true 404
***********************************************************************************************************************/
function funcCheckAccessLevel($aLevel, $aReturnNull = null) {
  if ($GLOBALS['arraySoftwareState']['authentication']['level'] >= $aLevel) {
    return true;
  }

  if (!$aReturnNull) {
    funcRedirect('/panel/login/');
  }

  return null;
}

// ====================================================================================================================

// == | Main | ========================================================================================================

$strComponentPath = dirname(COMPONENTS[$arraySoftwareState['requestComponent']]) . '/';
$boolHasPostData = !empty($_POST);

// --------------------------------------------------------------------------------------------------------------------

// Special case: Interlink should use Pale Moon's panel access at least until I get a cert
if ($arraySoftwareState['currentApplication'] == 'interlink') {
  funcRedirect('https://addons.palemoon.org/panel/');
}

// The Panel can ONLY be used on HTTPS
if (!in_array('https', TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['features'])) {
  funcError('The Phoebus Panel requires HTTPS, however this application\'s Add-ons Site does not have this feature enabled.</li>' .
            '<li>To access the Panel please try one of the other Phoebus-based Add-ons Sites.</li>' .
            '<li>If all else fails you can always use the Panel at the <a href="https://addons.palemoon.org/panel/">Pale Moon Add-ons Site</a>.');
}

if ($arraySoftwareState['currentScheme'] != 'https') {
  funcRedirect('https://' . $arraySoftwareState['currentDomain'] . '/panel/');
}

// --------------------------------------------------------------------------------------------------------------------

// Handle URIs
switch ($arraySoftwareState['requestPath']) {
  case URI_PANEL:
    $moduleGenerateContent->addonSite('panel-frontpage.xhtml', 'Landing Page');
    break;
  case URI_REG:
    //funcSendHeader('501');
    if ($boolHasPostData) {
      $moduleAccount->registerUser();
      funcError('Done');
    }

    $moduleGenerateContent->addonSite('panel-account-registration', 'Registration');
    break;
  case URI_LOGIN:
    $moduleAccount->authenticate();
    if (funcCheckAccessLevel(3, true)) {
      funcRedirect(URI_ADMIN);
    }
    funcRedirect(URI_ADDONS);
    break;
  case URI_LOGOUT:
    $moduleAccount->authenticate('logout');
    break;
  case URI_ACCOUNT:
  case URI_ADDONS:
    $moduleAccount->authenticate();
    funcCheckAccessLevel(1);
    require_once($strComponentPath . 'developer.php');
    break;
  default:
    if (startsWith($arraySoftwareState['requestPath'], URI_ADMIN)){
      $moduleAccount->authenticate();
      funcCheckAccessLevel(3);
      require_once($strComponentPath . 'administration.php');
    }

    // No clue send 404
    funcSend404();
}

// ====================================================================================================================

?>
