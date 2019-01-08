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
    funcSend404();
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

if ($_SERVER['SCHEME'] != 'https') {
  funcRedirect('https://' . $arraySoftwareState['currentDomain'] . '/panel/');
}

// Use a simple switch case to deal with simple URIs
switch ($arraySoftwareState['requestPath']) {
  case URI_PANEL:
    $moduleGenerateContent->addonSite('panel-frontpage.xhtml', 'Landing Page');
    break;
  case URI_REG:
    funcSendHeader('501');
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
    $moduleAccount->authenticate();
    // Users level 3 or above should use the administration codepath
    if (funcCheckAccessLevel(3, true)) {
      //funcRedirect(URI_ADMIN . '?task=update&what=user&slug=' . $arraySoftwareState['authentication']['username']);
    }

    // Deal with writing the updated user manifest
    if ($boolHasPostData) {
      funcError($_POST, 98);
    }
    $moduleGenerateContent->addonSite('developer-account', 'Account Page', $arraySoftwareState['authentication']);
    break;
}

// --------------------------------------------------------------------------------------------------------------------

// Complex URIs need more complex conditional checking
// Add-on Developer Code Path
if (startsWith($arraySoftwareState['requestPath'], URI_ADDONS)) {
  $moduleAccount->authenticate();
  funcCheckAccessLevel(1);

  // Serve the Developer Add-ons page
  if ($arraySoftwareState['requestPath'] == URI_ADDONS && !$arraySoftwareState['requestPanelTask']) {
    $addons = $moduleReadManifest->getAddons('panel-user-addons', $arraySoftwareState['authentication']['addons']);
    $moduleGenerateContent->addonSite('developer-addons-list', 'Your Add-ons', $addons);
  }

  // Users level 3 and above should redirect to the administration codepath
  if (funcCheckAccessLevel(3, true)) {
    funcRedirect(str_replace(URI_ADDONS, URI_ADMIN, $arraySoftwareState['phpRequestURI']));
  }

  switch ($arraySoftwareState['requestPanelTask']) {
    case 'update':
      switch ($arraySoftwareState['requestPanelWhat']) {
        case 'metadata':
          // Check for valid slug
          if (!$arraySoftwareState['requestPanelSlug']) {
            funcError('You did not specify a slug');
          }

          // Get the manifest
          $addonManifest = $moduleReadManifest->getPanelAddonBySlug($arraySoftwareState['requestPanelSlug']);

          // Check if manifest is valid
          if (!$addonManifest || !in_array($addonManifest['type'], ['extension', 'theme'])) {
            funcError('Add-on Manifest is null');
          }

          if (!in_array($arraySoftwareState['requestPanelSlug'], $arraySoftwareState['authentication']['addons'])) {
            funcError('You do not own this add-on. Stop trying to fuck with other people\'s shit!');
          }

          // We have post data so we should update the manifest data via classWriteManifest
          if ($boolHasPostData) {
            $boolUpdate = $moduleWriteManifest->updateAddonMetadata($addonManifest);

            // If an error happened stop.
            if (!$boolUpdate) {
              funcError('Something has gone horribly wrong');
            }

            // Manifest updated go somewhere
            funcRedirect(URI_ADDONS);
          }

          // Create an array to hold extra data to send to smarty
          // Such as the list of licenses
          $arrayExtraData = array('licenses' => array_keys($moduleReadManifest::LICENSES));

          // Extensions need the associative array of extension categories as well
          if ($addonManifest['type'] == 'extension') {
            $arrayExtraData['categories'] = $moduleReadManifest::EXTENSION_CATEGORY_SLUGS;
          }

          // Generate the edit add-on metadata page
          $moduleGenerateContent->addonSite('developer-edit-addon-metadata',
                                             'Editing Metadata for ' . $addonManifest['name'],
                                             $addonManifest,
                                             $arrayExtraData);
          break;
        case 'release':
          funcSendHeader('501');
        default:
          funcError('Invalid update request');
      }
      break;
    default:
      funcSendHeader('501');
  }
}
// Administration Code Path
elseif (startsWith($arraySoftwareState['requestPath'], URI_ADMIN)){
  // Challenge
  $moduleAccount->authenticate();
  funcCheckAccessLevel(3);

  // Serve the Adminsitration landing page
  if ($arraySoftwareState['requestPath'] == URI_ADMIN && !$arraySoftwareState['requestPanelTask']) {
    $moduleGenerateContent->addonSite('admin-frontpage.xhtml', 'Administration');
  }

  switch ($arraySoftwareState['requestPanelTask']) {
    case 'list':
      if (!$arraySoftwareState['requestPanelWhat']) {
        funcError('You did not specify what you want to list');
      }

      switch ($arraySoftwareState['requestPanelWhat']) {
        case 'extensions':
        case 'externals':
        case 'themes':
        case 'langpacks':
          $addons = $moduleReadManifest->getAddons('panel-addons-by-type',
                                                   substr($arraySoftwareState['requestPanelWhat'], 0, -1));

          $moduleGenerateContent->addonSite('admin-list-' . $arraySoftwareState['requestPanelWhat'],
                                            ucfirst($arraySoftwareState['requestPanelWhat']) . ' - Administration',
                                            $addons);
          break;
        case 'users':
          $users = $moduleAccount->getUsers();
          $moduleGenerateContent->addonSite('admin-list-users', 'Users - Administration', $users);
        default:
          funcError('Invalid list request');
      }
      break;
    case 'update':
      if (!$arraySoftwareState['requestPanelWhat'] || !$arraySoftwareState['requestPanelSlug']) {
        funcError('You did not specify what you want to update');
      }

      switch ($arraySoftwareState['requestPanelWhat']) {
        case 'metadata':
          // Check for valid slug
          if (!$arraySoftwareState['requestPanelSlug']) {
            funcError('You did not specify a slug');
          }

          // Get the manifest
          $addonManifest = $moduleReadManifest->getPanelAddonBySlug($arraySoftwareState['requestPanelSlug']);

          // Check if manifest is valid
          if (!$addonManifest) {
            funcError('Add-on Manifest is null');
          }

          // Extenrals need special handling so just send back the manifest for now
          if ($addonManifest['type'] == 'external') {
            funcError($addonManifest, 98);
          }

          if ($addonManifest['type'] == 'langpack') {
            funcError('Language Packs are not handled using this function. Stop being a moron.');
          }

          // We have post data so we should update the manifest data via classWriteManifest
          if ($boolHasPostData) {
            $boolUpdate = $moduleWriteManifest->updateAddonMetadata($addonManifest);

            // If an error happened stop.
            if (!$boolUpdate) {
              funcError('Something has gone horribly wrong');
            }

            // Manifest updated go somewhere
            funcRedirect(URI_ADMIN . '?task=list&what=' . $addonManifest['type'] . 's');
          }

          // Create an array to hold extra data to send to smarty
          // Such as the list of licenses
          $arrayExtraData = array('licenses' => array_keys($moduleReadManifest::LICENSES));

          // Extensions need the associative array of extension categories as well
          if ($addonManifest['type'] == 'extension') {
            $arrayExtraData['categories'] = $moduleReadManifest::EXTENSION_CATEGORY_SLUGS;
          }

          // Generate the edit add-on metadata page
          $moduleGenerateContent->addonSite('admin-edit-addon-metadata',
                                             'Editing Metadata for ' . $addonManifest['name'],
                                             $addonManifest,
                                             $arrayExtraData);
          break;
        case 'release':
          funcSendHeader('501');
        default:
          funcError('Invalid update request');
      }
      break;
    case 'download':
      if ($arraySoftwareState['requestPanelWhat'] == 'xpi' && $arraySoftwareState['requestPanelSlug']) {
        $addonManifest = $moduleReadManifest->getPanelAddonBySlug($arraySoftwareState['requestPanelSlug']);

        funcError($addonManifest, 99);
        if (!$addonManifest) {
          funcError('The Add-on manifest is blank');
        }

        $strPathXPI = $addonManifest['basepath'] . $addonManifest['releaseXPI'];

        if (!file_exists($strPathXPI)) {
          funcError('Release XPI does not physically exist');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: inline; filename="' . ltrim($strPathXPI, '.') . '"');
        header('Content-Length: ' . filesize($strPathXPI));
        header('Cache-Control: no-cache');
        header('X-Accel-Redirect: ' . ltrim($strPathXPI, '.'));
        break;
      }
    default:
      funcSendHeader('501');
  }
}

funcSend404();

// ====================================================================================================================

?>
