<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | ===============================================================

// Debug inital state
$boolDebugMode = false;

// Version
$strProductName = 'Phoebus';
$strApplicationVersion = '2.0.0a1';

// Include basicFunctions
require_once('./modules/basicFunctions.php');

// URLs
$strApplicationLiveURL = 'addons.palemoon.org';
$strApplicationDevURL = 'addons-dev.palemoon.org';
$strApplicationURL = $strApplicationLiveURL;

// Define application paths
$strRootPath = $_SERVER['DOCUMENT_ROOT'];
$strObjDirPath = $strRootPath . '/.obj/';
$strApplicationDatastore = './datastore/';
$strDatabasesPath = $strRootPath . '/db/';
$strLibPath = $strRootPath . '/lib/';
$strComponentsPath = $strRootPath . '/components/';
$strModulesPath = $strRootPath . '/modules/';
$strSkinPath = $strRootPath . '/skin/';

// Define Libs
$arrayLibs = array(
    'smarty' => $strLibPath . 'smarty/Smarty.class.php',
    'rdf' => $strLibPath . 'rdf/RdfComponent.php',
    'sql' => $strLibPath . 'safemysql/safemysql.class.php'
);

// Define Database Arrays
$arrayDatabases = array(
    'dbAddons' => $strDatabasesPath . 'addons.php',
    'dbSearchPlugins' => $strDatabasesPath . 'searchPlugins.php',
    'dbCategories' => $strDatabasesPath . 'categories.php'
);

// Define Components
$arrayComponents = array(
    'aus' => $strComponentsPath . 'aus/addonUpdateService.php',
    'discover' => $strComponentsPath . 'discover/discoverPane.php',
    'download' => $strComponentsPath . 'download/addonDownload.php',
    'integration' => $strComponentsPath . 'integration/amIntegration.php',
    'license' => $strComponentsPath . 'license/addonLicense.php',
    'site' => $strComponentsPath . 'site/addonSite.php',
    'special' => $strComponentsPath . 'special/special.php'
);

// Define Modules
$arrayModules = array(
    'readManifest' => $strModulesPath . 'classReadManifest.php',
    'generatePage' => $strModulesPath . 'classGeneratePage.php',
    'vc' => $strModulesPath . 'nsIVersionComparator.php',
    'addonManifest' => $strModulesPath . 'deprecated/classAddonManifest.php',
    'langPacks' => $strModulesPath . 'deprecated/classLangPacks.php',
    'ftpAuth' => $strModulesPath . 'deprecated/classFTPAuth.php'
);

// Define Skins
$arraySkins = array(
    'default' => $strSkinPath . 'default',
    'palemoon' => $strSkinPath . 'palemoon',
    'basilisk' => $strSkinPath . 'basilisk'
);

// Known Client GUIDs
$strPaleMoonID = '{8de7fcbb-c55c-4fbe-bfc5-fc555c87dbc4}';
$strFossaMailID = '{3550f703-e582-4d05-9a08-453d09bdfdc6}';
$strBasiliskID = '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}';
$strFirefoxID = $strBasiliskID; // {ec8030f7-c20a-464f-9b0e-13a3a9e97384}
$strThunderbirdID = $strFossaMailID; // {3550f703-e582-4d05-9a08-453d09bdfdc6}
$strSeaMonkeyID = '{92650c4d-4b8e-4d2a-b7eb-24ecf4f6b63a}';
$strClientID = $strPaleMoonID;

// XXX: Pale Moon only backwards compatiblity with "Liberation Era"
$strMinimumApplicationVersion = '27.0.0';
$strFirefoxVersion = '27.9';
$strFirefoxOldVersion = '24.9';

// $_GET and Path Magic
$strRequestComponent = funcHTTPGetValue('component');
$strRequestPath = funcHTTPGetValue('path');

// ============================================================================

// == | Main | ================================================================

// Define a Debug/Developer Mode
if ($_SERVER['SERVER_NAME'] == $strApplicationDevURL) {
    // Flip the var
    $boolDebugMode = true;
    
    // Use dev URL
    $strApplicationURL = $strApplicationDevURL;

    // Use RELEASE DB Arrays
    $arrayDatabases['dbAddons'] = '../' . $strApplicationLiveURL . '/db/addons.php';
    $arrayDatabases['dbCategories'] = '../' . $strApplicationLiveURL . '/db/categories.php';
    
    // Enable all errors
    error_reporting(E_ALL);
    ini_set("display_errors", "on");
}
else {
    error_reporting(0);
}

// Merge Libs and Databases into Modules then unset
$arrayModules = array_merge($arrayModules, $arrayLibs);
$arrayModules = array_merge($arrayModules, $arrayDatabases);
unset($arrayLibs);
unset($arrayDatabases);

// Always Require SQL
require_once($arrayModules['sql']);

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