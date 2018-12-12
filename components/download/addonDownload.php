<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = ['database', 'readManifest'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$moduleReadManifest = new classReadManifest();

// ====================================================================================================================

// == | funcDownloadXPI | ===============================================

function funcDownloadXPI($_addonManifest, $_addonVersion) {
  $_versionXPI = null;
  
  if ($_addonVersion == 'latest') {
    $_versionXPI = $_addonManifest['release'];
    $_addonFile = $_addonManifest['basePath'] . $_versionXPI;
  }
  else {
    $_versionMatch = false;
    foreach ($_addonManifest['xpinstall'] as $_key => $_value) {
      if (in_array($_addonVersion, $_value)) {
        $_versionMatch = true;
        $_versionXPI = $_key;
        break;
      }
    }
    
    if ($_versionMatch == true) { 
      $_addonFile = $_addonManifest['basePath'] . $_versionXPI;
    }
    else {
      funcError('Unknown XPI version');
    }
  }
  
  if (file_exists($_addonFile)) {
    // Non-web browsers should send as an arbitrary binary stream
    if (in_array('disable-xpinstall', TARGET_APPLICATION_SITE[$_currentApplication]['features'])) {
      header('Content-Type: application/octet-stream');
    }
    else {
      header('Content-Type: application/x-xpinstall');
    }

    header('Content-Disposition: inline; filename="' . $_versionXPI . '"');
    header('Content-Length: ' . filesize($_addonFile));
    header('Cache-Control: no-cache');
    header('X-Accel-Redirect: ' . ltrim($_addonFile, '.'));
  }
  else {
    funcError('XPI file not found');
  }

  // We are done here
  exit();
}

// ============================================================================

// == | funcDownloadSearchPlugin | ============================================

function funcDownloadSearchPlugin($_searchPluginName) {
  $_SearchPluginFile = './datastore/searchplugins/' . $_searchPluginName;
  
  if (file_exists($_SearchPluginFile)) {
    header('Content-Type: text/xml');
    header('Content-Disposition: inline; filename="' . $_searchPluginName .'"');
    header('Cache-Control: no-cache');
    
    readfile($_SearchPluginFile);
  }
  else {
    funcError('Search Plugin XML file not found');
  }
  
  // We are done here
  exit();
}

// ============================================================================

// == | Main | ================================================================

$strRequestAddonID = funcUnifiedVariable('get', 'id');
$strRequestAddonVersion = funcUnifiedVariable('get', 'version');

// Sanity
if ($strRequestAddonID == null) {
  funcError('Missing minimum required arguments.');
}

if ($strRequestAddonVersion == null) {
  $strRequestAddonVersion = 'latest';
} 

// Search for add-ons in our databases
// Search Plugins
if (array_key_exists($strRequestAddonID, classReadManifest::SEARCH_PLUGINS_DB)) {
  funcDownloadSearchPlugin(classReadManifest::SEARCH_PLUGINS_DB[$strRequestAddonID]);
}
else {
  $addonManifest = $moduleReadManifest->getAddonByID($strRequestAddonID);

  if ($addonManifest != null) {
    $addonManifest['release'] = $addonManifest['releaseXPI'];
    funcDownloadXPI($addonManifest, $strRequestAddonVersion);
  }
  else {  
    funcError('Add-on could not be found in our database');
  }
}

// ============================================================================
?>