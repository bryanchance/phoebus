<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | INFO | ================================================================

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
$arraySoftwareState['requestMozXPIUpdate'] = funcHTTPGetValue('updateOverride');

// Instantiate modules
$moduleReadManifest = new classReadManifest();
$moduleGenerateContent = new classGenerateContent();

// ----------------------------------------------------------------------------

// Sanity
if (!$arraySoftwareState['requestAddonID'] || !$arraySoftwareState['requestAddonVersion'] ||
    !$arraySoftwareState['requestAppID'] || !$arraySoftwareState['requestAppVersion'] ||
    !$arraySoftwareState['requestAddonCompatMode']) {
  if (!arraySoftwareState['debugMode']) {
    $moduleGenerateContent->addonUpdateService(null);
  }

  funcError('Missing minimum required arguments.');
}

// Check for Moz-XPI-Update header
if (!array_key_exists('HTTP_MOZ_XPI_UPDATE', $_SERVER) ||
    (!arraySoftwareState['debugMode'] && !$arraySoftwareState['requestMozXPIUpdate'])) {
  if (!arraySoftwareState['debugMode']) {
    $moduleGenerateContent->addonUpdateService(null);
  }

  funcError('Compatibility check failed.');
}



$moduleGenerateContent->test();

// ============================================================================

?>
