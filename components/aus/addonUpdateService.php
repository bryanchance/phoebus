<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | INFO | ================================================================

// This needs to be moved the hell out of this file

// Automatic Update Service for Add-ons responds with a valid RDF file
// containing update information for known add-ons or sends the request
// off to AMO (for now) if it is unknown to us.

// FULL GET Arguments for AUS are as follows:

// [query]          [Description]       [Example]                       [Used]
// ----------------------------------------------------------------------------
// reqVersion       Request Version     '2'                             false
// id               Add-on ID           '{GUID}' or 'user@host.tld'     true
// version          Add-on Version      '1.2.3a1'                       amo
// maxAppVersion                        '26.5.0'                        false
// status           Add-on Status       'userEnabled'                   false
// appID            Client ID           'toolkit@mozilla.org'           true
// appVersion       Client Version      '27.2.1'                        true
// appOS            Client OS           'WINNT'                         false
// appABI           Client ABI          'x86-gcc3'                      false
// locale           Client Locale       'en-US'                         false    
// currentAppVersion                    '27.4.2'                        false
// updateType       Update Type         '32' or '64'                    false
// compatMode       Compatibility Mode  'normal', 'ignore', or 'strict' amo

// See: https://developer.mozilla.org/Add-ons/Install_Manifests#updateURL

// ============================================================================

// == | Setup | ===============================================================

// Constants
const AMO_AUS_URL = 'https://versioncheck.addons.mozilla.org/update/VersionCheck.php?reqVersion=2';

// This constant is a list of Add-on IDs that should never be checked for
const BAD_ADDON_IDS = array(
  '{972ce4c6-7e08-4474-a285-3208198ce6fd}', // Default Theme
  'modern@themes.mozilla.org',              // Mozilla Modern Theme
  '{a62ef8ec-5fdc-40c2-873c-223b8a6925cc}', // GData
  '{e2fda1a4-762b-4020-b5ad-a41df1933103}', // Lightning
);

// Include modules
$arrayIncludes = array('sql', 'sql-creds', 'readManifest', 'generateContent');
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// ============================================================================

// == | Main | ================================================================

// Assign HTTP GET arguments to the software state
$arraySoftwareState['requestAddonID'] = funcHTTPGetValue('id');
$arraySoftwareState['requestAddonVersion'] = funcHTTPGetValue('version');
$arraySoftwareState['requestAppID'] = funcHTTPGetValue('appID');
$arraySoftwareState['requestAppVersion'] = funcHTTPGetValue('appVersion');
$arraySoftwareState['requestAddonCompatMode'] = funcHTTPGetValue('compatMode');
(bool)$arraySoftwareState['requestMozXPIUpdate'] =
  array_key_exists('HTTP_MOZ_XPI_UPDATE', $_SERVER) || funcHTTPGetValue('updateOverride');

// Instantiate modules
$moduleReadManifest = new classReadManifest();
$moduleGenerateContent = new classGenerateContent();

// ----------------------------------------------------------------------------

// Sanity
if (!$arraySoftwareState['requestAddonID'] || !$arraySoftwareState['requestAddonVersion'] ||
    !$arraySoftwareState['requestAppID'] || !$arraySoftwareState['requestAppVersion'] ||
    !$arraySoftwareState['requestAddonCompatMode']) {
  if (!arraySoftwareState['debugMode']) {
    // Send blank rdf response
    $moduleGenerateContent->addonUpdateService(null);
  }
  funcError('Missing minimum required arguments.');
}

// Check for Moz-XPI-Update header
if (!$arraySoftwareState['requestMozXPIUpdate']) {
  if (!$arraySoftwareState['debugMode']) {
    // Send blank rdf response
    $moduleGenerateContent->addonUpdateService(null);
  }
  funcError('Compatibility check failed.');
}

// ----------------------------------------------------------------------------

// Check for "Bad" Add-on IDs
if (in_array($arraySoftwareState['requestAddonID'], BAD_ADDON_IDS)) {
  if (!$arraySoftwareState['debugMode']) {
    // Send blank rdf response
    $moduleGenerateContent->addonUpdateService(null);
  }
  funcError('"Bad" Add-on ID Detected');
}

// ----------------------------------------------------------------------------

// Handle FossaMail Special Case (Send to AMO unconditionally)
if ($arraySoftwareState['requestAppID'] == TARGET_APPLICATION_ID['fossamail']) {
  $strAMO = 
    AMO_AUS_URL .
    '&id=' . $arraySoftwareState['requestAddonID'] .
    '&version=' . $arraySoftwareState['requestAddonVersion'] .
    '&appID=' . TARGET_APPLICATION_ID['thunderbird'] .
    '&appVersion=' . '38.9' .
    '&compatMode=' . $arraySoftwareState['requestAddonCompatMode'];
  
  funcRedirect($strAMOLink);
}

// ----------------------------------------------------------------------------

// Check for Add-on Updates
if ($arraySoftwareState['requestAppID'] == $arraySoftwareState['targetApplicationID'] ||
    ($arraySoftwareState['debugMode'] && $arraySoftwareState['orginalApplication'])) {
  $addonManifest =
    $moduleReadManifest->getAddonByID($arraySoftwareState['requestAddonID']);

  if (!$addonManifest) {
    // Send non-existant add-ons to AMO for Basilisk
    if ($arraySoftwareState['currentApplication'] = 'basilisk') {
      $strAMO = 
        AMO_AUS_URL .
        '&id=' . $arraySoftwareState['requestAddonID'] .
        '&version=' . $arraySoftwareState['requestAddonVersion'] .
        '&appID=' . TARGET_APPLICATION_ID['firefox'] .
        '&appVersion=' . '52.9' .
        '&compatMode=' . $arraySoftwareState['requestAddonCompatMode'];
      
      funcRedirect($strAMOLink);
    }

    // Add-on is non-existant send blank rdf response
    $moduleGenerateContent->addonUpdateService(null);
  }
  
  // Add-on exists so send update.rdf
  $moduleGenerateContent->addonUpdateService($addonManifest);
}
else {
  if (!$arraySoftwareState['debugMode']) {
    // Send blank rdf response
    $moduleGenerateContent->addonUpdateService(null);
  }
  funcError('Mismatched or Invalid Application ID');
}

// ============================================================================

?>
