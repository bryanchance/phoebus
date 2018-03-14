<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | INFO | ================================================================

// Automatic Update Service for Add-ons responds with a valid RDF file
// containing update information for known add-ons or sends the request
// off to AMO (for now) if it is unknown to us.

// FULL GET Arguments for AUS are as follows:

// [query]      [Description]     [Example]             [Used]
// ----------------------------------------------------------------------------
// reqVersion     Request Version   '2'               false
// id         Add-on ID       '{GUID}' or 'user@host.tld'   true
// version      Add-on Version    '1.2.3a1'             amo
// maxAppVersion            '26.5.0'            false
// status       Add-on Status     'userEnabled'           false
// appID      Client ID       'toolkit@mozilla.org'       true
// appVersion     Client Version    '27.2.1'            true
// appOS      Client OS       'WINNT'             false
// appABI       Client ABI      'x86-gcc3'            false
// locale       Client Locale     'en-US'             false  
// currentAppVersion          '27.4.2'            false
// updateType     Update Type     '32' or '64'          false
// compatMode     Compatibility Mode  'normal', 'ignore', or 'strict' amo

// See: https://developer.mozilla.org/Add-ons/Install_Manifests#updateURL

// ============================================================================

// == | Vars | ================================================================

$boolMozXPIUpdate = false;
$boolAMOKillSwitch = false;
$intVcResult = null;

$arrayIncludes = array(
  $arrayModules['langPacks'],
  $arrayModules['readManifest'],
);

$strRequestAddonID = funcHTTPGetValue('id');
$strRequestAddonVersion = funcHTTPGetValue('version');
$strRequestAppID = funcHTTPGetValue('appID');
$strRequestAppVersion = funcHTTPGetValue('appVersion');
$strRequestCompatMode = funcHTTPGetValue('compatMode');
$strRequestMozXPIUpdate = funcHTTPGetValue('Moz-XPI-Update');

// ============================================================================

// == | funcGenerateUpdateXML | ===============================================

function funcGenerateUpdateXML($_addonManifest) {
  $_strUpdateXMLHead = '<?xml version="1.0"?>' . "\n" . 
    '<RDF:RDF xmlns:RDF="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:em="http://www.mozilla.org/2004/em-rdf#">';
  $_strUpdateXMLTail = '</RDF:RDF>';

  funcSendHeader('xml');

  print($_strUpdateXMLHead);

  if ($_addonManifest != null) {
      print("\n");

      $_strUpdateXMLBody = '<RDF:Description about="urn:mozilla:{%ADDON_TYPE}:{%ADDON_ID}">
  <em:updates>
    <RDF:Seq>
    <RDF:li>
      <RDF:Description>
      <em:version>{%ADDON_VERSION}</em:version>
      <em:targetApplication>
        <RDF:Description>
        <em:id>{%APPLICATION_ID}</em:id>
        <em:minVersion>{%ADDON_MINVERSION}</em:minVersion>
        <em:maxVersion>{%ADDON_MAXVERSION}</em:maxVersion>
        <em:updateLink><![CDATA[{%ADDON_XPI}]]></em:updateLink>
        <em:updateHash>sha256:{%ADDON_HASH}</em:updateHash>
        </RDF:Description>
      </em:targetApplication>
      </RDF:Description>
    </RDF:li>
    </RDF:Seq>
  </em:updates>
  </RDF:Description>';
      
      $_arrayFilterSubstitute = array(
        '{%ADDON_TYPE}' => $_addonManifest['type'],
        '{%ADDON_ID}' => $_addonManifest['id'],
        '{%ADDON_VERSION}' => $_addonManifest['xpinstall'][$_addonManifest['release']]['version'],
        '{%APPLICATION_ID}' => $GLOBALS['strClientID'],
        '{%ADDON_MINVERSION}' => $_addonManifest['xpinstall'][$_addonManifest['release']]['targetApplication'][$GLOBALS['strPaleMoonID']]['minVersion'],
        '{%ADDON_MAXVERSION}' => $_addonManifest['xpinstall'][$_addonManifest['release']]['targetApplication'][$GLOBALS['strPaleMoonID']]['maxVersion'],
        '{%ADDON_XPI}' => $_addonManifest['baseURL'] . $_addonManifest['id'],
        '{%ADDON_HASH}' => $_addonManifest['xpinstall'][$_addonManifest['release']]['hash']
      );

      foreach ($_arrayFilterSubstitute as $_key => $_value) {
        $_strUpdateXMLBody = str_replace($_key, $_value, $_strUpdateXMLBody);
      }
      
      print("\n");
      print($_strUpdateXMLBody);
  }
  
  print($_strUpdateXMLTail);
  
  // We are done here...
  exit();
}

// ============================================================================

// == | Main | ================================================================

funcCheckUserAgent();

// Sanity
if ($strRequestAddonID == null || $strRequestAddonVersion == null ||
  $strRequestAppID == null || $strRequestAppVersion == null ||
  $strRequestCompatMode == null) {
  if ($GLOBALS['boolDebugMode'] == true) {
    funcError('Missing minimum required arguments.');
  }
  else {
    funcGenerateUpdateXML(null, false);
  }
}

if (array_key_exists('HTTP_MOZ_XPI_UPDATE', $_SERVER) || ($boolDebugMode == true && $strRequestMozXPIUpdate == true)) {
  $boolMozXPIUpdate = true;
}

if ($boolMozXPIUpdate == false) {
  if ($GLOBALS['boolDebugMode'] == true) {
    funcError('Compatibility check failed.');
  }
  else {
    funcGenerateUpdateXML(null, false);
  }
}

// Check for Updates
if ($strRequestAppID == $strPaleMoonID) {
  // Include modules
  foreach($arrayIncludes as $_value) {
    require_once($_value);
  }
  unset($arrayIncludes);

  if ($GLOBALS['boolDebugMode'] == true) {
    // classLangPacks
    $langPacks = new classLangPacks;
    $arrayLangPackDB = $langPacks->funcGetLanguagePacks();
  }
  else {
    $arrayLangPackDB = array();
  }

  // Search for add-ons in our database
  // Language Packs
  if (array_key_exists($strRequestAddonID, $arrayLangPackDB)) {    
    funcGenerateUpdateXML($arrayLangPackDB[$strRequestAddonID]);
  }
  // Search SQL else send blank xml
  else {
    $readManifest = new classReadManifest();
    $addonManifest = $readManifest->getAddonByID($strRequestAddonID);
    
    if ($addonManifest != null) {
      $addonManifest['release'] = $addonManifest['releaseXPI'];
      funcGenerateUpdateXML($addonManifest);
    }
    else {
      funcGenerateUpdateXML(null);
    }
  }
}
elseif ($strRequestAppID == $strFossaMailID) {
  $strClientID = $strFossaMailID;

  $arrayBadFossaMailDB = array(
    '{a62ef8ec-5fdc-40c2-873c-223b8a6925cc}' => 'gdata',
    '{e2fda1a4-762b-4020-b5ad-a41df1933103}' => 'lightning'
  );

  if (array_key_exists($strRequestAddonID, $arrayBadFossaMailDB)) {
    funcGenerateUpdateXML(null);
  }
  else {
    if ($boolAMOKillSwitch == false) {       
      $strAMOLink = 'https://versioncheck.addons.mozilla.org/update/VersionCheck.php?reqVersion=2' .
      '&id=' . $strRequestAddonID .
      '&version=' . $strRequestAddonVersion .
      '&appID=' . $strThunderbirdID .
      '&appVersion=' . '38.9' .
      '&compatMode=' . $strRequestCompatMode;
      
      funcRedirect($strAMOLink);
    }
    else {
      funcGenerateUpdateXML(null);
    }
  }
}
else {
  if ($GLOBALS['boolDebugMode'] == true) {
    funcError('Invalid Application ID');
  }
  else {
    funcGenerateUpdateXML(null, false);
  }
}

// ============================================================================
?>
