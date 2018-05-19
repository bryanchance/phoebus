<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | ===============================================================

// Include basicFunctions
require_once('./modules/basicFunctions.php');

// Function-Define a constant for the absolute path to document root for
// use in the CONFIG constant array
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

// Define a constant array for configuration that does not change during runtime
const CONFIG = array(
  'application' => array(
    'name' => 'Phoebus',
    'version' => '2.0.0a1',
    'root' => ROOT_PATH,
    'datastore' => ROOT_PATH . '/datastore/',
    'obj' => ROOT_PATH . '/.obj/'
  ),
  'components' => array(
    'aus' => ROOT_PATH . '/components/aus/addonUpdateService.php',
    'discover' => ROOT_PATH . '/components/discover/discoverPane.php',
    'download' => ROOT_PATH . '/components/download/addonDownload.php',
    'integration' => ROOT_PATH . '/components/integration/amIntegration.php',
    'license' => ROOT_PATH . '/components/license/addonLicense.php',
    'site' => ROOT_PATH . '/components/site/addonSite.php',
    'special' => ROOT_PATH . '/components/special/special.php'
  ),
  'modules' => array(
    'readManifest' => ROOT_PATH . '/modules/classReadManifest.php',
    'generatePage' => ROOT_PATH . '/modules/classGeneratePage.php',
    'vc' => ROOT_PATH . '/modules/nsIVersionComparator.php',
    'dbSearchPlugins' => ROOT_PATH . '/modules/searchPlugins.php',
    'smarty' => ROOT_PATH . '/lib/smarty/Smarty.class.php',
    'rdf' => ROOT_PATH . '/lib/rdf/RdfComponent.php',
    'sql' => ROOT_PATH . '/lib/safemysql/safemysql.class.php'
  ),
  'skins' => array(
    'default' => ROOT_PATH . '/skin/default/',
    'palemoon' => ROOT_PATH . '/skin/palemoon/',
    'basilisk'  => ROOT_PATH . '/skin/basilisk/',
  //'borealis'  => ROOT_PATH . '/skin/default/'
  ),
  'sites' => array(
    'palemoon' => array(
      'name' => 'Pale Moon - Add-ons - ',
      'liveURL' => 'addons.palemoon.org',
      'devURL' => 'addons-dev.palemoon.org',
      'httpsEnabled' => true,
      'extensionsEnabled' => true,
      'themesEnabled' => true,
      'langpacksEnabled' => true,
      'searchpluginsEnabled' => true
    ),
    'basilisk' => array(
      'name' => 'Basilisk Add-ons: ',
      'liveURL' => 'addons.basilisk-browser.org',
      'devURL' => 'addons-dev.basilisk-browser.org',
      'httpsEnabled' => true,
      'extensionsEnabled' => true,
      'themesEnabled' => false,
      'langpacksEnabled' => false,
      'searchpluginsEnabled' => true
    ),
    /*
    'borealis' => array(
      'name' => 'Add-ons - Borealis - Projects - Binary Outcast',
      'liveURL' => 'borealis-addons.binaryoutcast.com',
      'devURL' => 'borealis-addons-dev.binaryoutcast.com',
      'httpsEnabled' => false,
      'extensionsEnabled' => true,
      'themesEnabled' => false,
      'langpacksEnabled' => false,
      'searchpluginsEnabled' => true
    )
    */
  ),
  'addons' => array(
    'appID' => array(
      'palemoon' => '{8de7fcbb-c55c-4fbe-bfc5-fc555c87dbc4}',
      'basilisk' => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
      'fossamail' => '{3550f703-e582-4d05-9a08-453d09bdfdc6}',
    //'borealis' => '{}'
    ),
    'legacyAppID' => array(
      'firefox' => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
      'thunderbird' => '{3550f703-e582-4d05-9a08-453d09bdfdc6}',
      'seamonkey' => '{92650c4d-4b8e-4d2a-b7eb-24ecf4f6b63a}'
    )
  )
);

// Define and array for configuration that can change during runtime
$arrayConfig = array(
  'application' => array(
    'url' => CONFIG['sites']['palemoon']['liveURL'],
    'debug' => false
  ),
  'request' => array(
    'component' => funcHTTPGetValue('component'),
    'path' => funcHTTPGetValue('path'),
  )
);

// ----------------------------------------------------------------------------

// Temporary backwards compatible vars
$boolDebugMode = $arrayConfig['application']['debug'];
$strProductName = CONFIG['application']['name'];
$strApplicationVersion = CONFIG['application']['version'];
$strApplicationLiveURL = CONFIG['sites']['palemoon']['liveURL'];
$strApplicationDevURL = CONFIG['sites']['palemoon']['devURL'];
$strApplicationURL = $arrayConfig['application']['url'];
$strRootPath = $_SERVER['DOCUMENT_ROOT'];
$strObjDirPath = $strRootPath . '/.obj/';
$strApplicationDatastore = './datastore/';
$strLibPath = $strRootPath . '/lib/';
$strComponentsPath = $strRootPath . '/components/';
$strModulesPath = $strRootPath . '/modules/';
$strSkinPath = $strRootPath . '/skin/';
$arrayComponents = CONFIG['components'];
$arrayModules = CONFIG['modules'];
$arraySkins = CONFIG['skins'];
$strPaleMoonID = CONFIG['addons']['appID']['palemoon'];
$strFossaMailID = CONFIG['addons']['appID']['fossamail'];
$strThunderbirdID = CONFIG['addons']['legacyAppID']['thunderbird']; // {3550f703-e582-4d05-9a08-453d09bdfdc6}
$strClientID = CONFIG['addons']['appID']['palemoon'];
$strRequestComponent = $arrayConfig['request']['component'];
$strRequestPath = $arrayConfig['request']['path'];

// ============================================================================

// == | Main | ================================================================

// Define a Debug/Developer Mode
if ($_SERVER['SERVER_NAME'] == $strApplicationDevURL) {
  // Flip the var
  $boolDebugMode = true;
  
  // Use dev URL
  $strApplicationURL = $strApplicationDevURL;

  // Enable all errors
  error_reporting(E_ALL);
  ini_set("display_errors", "on");
}
else {
  error_reporting(0);
}

// Always Require SQL
require_once($arrayModules['sql']);
require_once('./datastore/pm-admin/rdb.php');

// Set inital URL-based entry points
if ($_SERVER['REQUEST_URI'] == '/') {
  // Root (/) won't send a component or path
  $strRequestComponent = 'site';
  $strRequestPath = '/';
}
elseif (startsWith($_SERVER['REQUEST_URI'], '/special/')) {
  // The special component is well.. Special load it up
  $strRequestComponent = 'special';
}
elseif ($strRequestComponent != 'site' && $strRequestPath != null) {
  // If for some reason the SITE component was sent but no path.. 404
  funcSendHeader('404');
}

// Load component based on strRequestComponent
if ($strRequestComponent != null) {
  if (array_key_exists($strRequestComponent, $arrayComponents)) {
    require_once($arrayComponents[$strRequestComponent]);
  }
  else {
    if ($boolDebugMode == true) {
      funcError($strRequestComponent . ' is an unknown component');
    }
    else {
      funcSendHeader('404');
    }
  }
}
else {
  if ($boolDebugMode == true) {
    funcError('You did not specify a component');
  }
  else {
    funcSendHeader('404');
  }
}

// ============================================================================
?>