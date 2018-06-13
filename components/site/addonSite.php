<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// URI Constants
const URI_ADDON_PAGE = '/addon/';
const URI_ADDON_RELEASES = '/releases/';
const URI_ADDON_LICENSE = '/license/';
const URI_EXTENSIONS = '/extensions/';
const URI_THEMES = '/themes/';
const URI_SEARCHPLUGINS = '/search-plugins/';
const URI_LANGPACKS = '/language-packs/';
const URI_SEARCH = '/search/';

// Include modules
$arrayIncludes = array('sql', 'sql-creds', 'readManifest', 'smarty', 'generateContent');
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// ====================================================================================================================

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Strips path to obtain the slug
*
* @param $_path     $arraySoftwareState['requestPath']
* @param $_prefix   Prefix to strip 
* @returns          slug
***********************************************************************************************************************/
function funcStripPath($_path, $_prefix) {
  return str_replace('/', '', str_replace($_prefix, '', $_path));
}

/**********************************************************************************************************************
* Sends a 404 error but does it depending on debug mode
***********************************************************************************************************************/
function funcSend404() {
  if (!$GLOBALS['arraySoftwareState']['debugMode']) {
    funcSendHeader('404');
  }
  funcError('404 - Not Found');
}

/**********************************************************************************************************************
* Checks for enabled features
*
* @param $_value    feature
* @returns          true if existant else null
***********************************************************************************************************************/
function funcIsEnabledFeature($_value) {
  $_currentApplication = $GLOBALS['arraySoftwareState']['currentApplication'];
  if (!in_array($_value, TARGET_APPLICATION_SITE[$_currentApplication]['features'])) {
    return null;
  }
  return true;
}

// ====================================================================================================================

// == | Main | ========================================================================================================

// Site Name
// When in debug mode it displays the software name and version and if git
// is detected it will append the branch and short sha1 hash
// else it will use the name defined in TARGET_APPLICATION_SITE
if ($arraySoftwareState['debugMode']) {
  $arraySoftwareState['currentName'] = SOFTWARE_NAME . ' Development - Version: ' . SOFTWARE_VERSION;
  // Git stuff
  if (file_exists('./.git/HEAD')) {
    $_strGitHead = file_get_contents('./.git/HEAD');
    $_strGitSHA1 = file_get_contents('./.git/' . substr($_strGitHead, 5, -1));
    $_strGitBranch = substr($_strGitHead, 16, -1);
    $arraySoftwareState['currentName'] = 
      $arraySoftwareState['currentName'] . ' - ' .
      'Branch: ' . $_strGitBranch . ' - ' .
      'Commit: ' . substr($_strGitSHA1, 0, 7);
  }
}
else {
  $arraySoftwareState['currentName'] = TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['name'];
}

// Instantiate modules
$moduleReadManifest = new classReadManifest();
$moduleGenerateContent = new classGenerateContent(true);

// --------------------------------------------------------------------------------------------------------------------

// Decide what kind of page is being requested
// The front page
if ($arraySoftwareState['requestPath'] == '/') { 
  $moduleGenerateContent->addonSite($arraySoftwareState['currentApplication'] . '-frontpage.xhtml', 'Explore Add-ons');
}
elseif ($arraySoftwareState['requestPath'] == '/favicon.ico') { 
  funcRedirect('/components/site/skin/' . $arraySoftwareState['currentApplication'] . '/favicon.ico');
}
// Incompatible Add-ons Page (Pale Moon legacy page)
elseif ($arraySoftwareState['requestPath'] == '/incompatible/') {
  if ($arraySoftwareState['currentApplication'] != 'palemoon') {
    funcSend404();
  }

  $moduleGenerateContent->addonSite('palemoon-incompatible.xhtml', 'Incompatible Add-ons');
}
// Add-on Search
elseif ($arraySoftwareState['requestPath'] == '/search/') {
  $searchManifest =
    $moduleReadManifest->getSearchResults($arraySoftwareState['requestSearchTerms']);
  
  if (!$searchManifest) {
    $moduleGenerateContent->addonSite('search', 'No search results');
  }

  $moduleGenerateContent->addonSite('search',
    'Search results for "' . $arraySoftwareState['requestSearchTerms'] . '"',
    $searchManifest
  );
}
// Add-on Page
elseif (startsWith($arraySoftwareState['requestPath'], URI_ADDON_PAGE)) {
  if ($arraySoftwareState['requestPath'] == URI_ADDON_PAGE) {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], URI_ADDON_PAGE);
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);

  if (!$addonManifest) {
    funcSend404();
  }

  $moduleGenerateContent->addonSite('addon-page', $addonManifest['name'], $addonManifest);
}
// Add-on Releases
elseif (startsWith($arraySoftwareState['requestPath'], URI_ADDON_RELEASES)) {
  if ($arraySoftwareState['requestPath'] == URI_ADDON_RELEASES) {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], URI_ADDON_RELEASES);
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);

  if (!$addonManifest) {
    funcSend404();
  }

  $moduleGenerateContent->addonSite('addon-releases', $addonManifest['name'] . ' - Releases', $addonManifest);
}
// Add-on License
elseif (startsWith($arraySoftwareState['requestPath'], URI_ADDON_LICENSE)) {
  if ($arraySoftwareState['requestPath'] == URI_ADDON_LICENSE) {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], URI_ADDON_LICENSE);
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);

  if (!$addonManifest) {
    funcSend404();
  }

  if ($addonManifest['licenseURL']) {
    funcRedirect($addonManifest['licenseURL']);
  }
  
  if ($addonManifest['license'] == 'pd' || $addonManifest['license'] == 'copyright' ||
      $addonManifest['license'] == 'custom') {
    if ($addonManifest['licenseText']) {
      $addonManifest['licenseText'] = nl2br($addonManifest['licenseText'], true);
    }

    $moduleGenerateContent->addonSite('addon-license', $addonManifest['name'] . ' - License', $addonManifest);
  }

  funcRedirect('https://opensource.org/licenses/' . $addonManifest['license']);
}
// Extensions Category or Subcategory
elseif (startsWith($arraySoftwareState['requestPath'], URI_EXTENSIONS)) {
  // Extensions Category
  if ($arraySoftwareState['requestPath'] == URI_EXTENSIONS) {
    $categoryManifest = $moduleReadManifest->getAllExtensions();
    if ($categoryManifest) {
      $moduleGenerateContent->addonSite('cat-all-extensions', 'Extensions', $categoryManifest);
    }
  }

  // Extensions Subcategory
  if (funcIsEnabledFeature('extensions-cat')) {
    // Strip the path to get the slug
    $strSlug = funcStripPath($arraySoftwareState['requestPath'], URI_EXTENSIONS);

    // See if the slug exists in the category array
    if (array_key_exists($strSlug, classReadManifest::EXTENSION_CATEGORY_SLUGS)) {
      $categoryManifest = $moduleReadManifest->getCategory($strSlug);
      
      if (!$categoryManifest) {
        funcSend404();
      }

      $moduleGenerateContent->addonSite(
        'cat-extensions', 'Extensions: ' .
        classReadManifest::EXTENSION_CATEGORY_SLUGS[$strSlug], $categoryManifest
      );
    }
    else {
      funcSend404();
    }
  }
  else {
    funcSend404();
  }
}
// Themes Category
elseif ($arraySoftwareState['requestPath'] == URI_THEMES) { 
  if (funcIsEnabledFeature('themes')) {
    $categoryManifest = $moduleReadManifest->getCategory('themes');
    if (!$categoryManifest) {
      funcSend404();
    }

    $moduleGenerateContent->addonSite('cat-themes', 'Themes', $categoryManifest);
  }
  else {
    funcSend404();
  }
}
// Search Plugins
elseif ($arraySoftwareState['requestPath'] == URI_SEARCHPLUGINS) { 
  if (funcIsEnabledFeature('search-plugins')) {
    $categoryManifest = $moduleReadManifest->getSearchPlugins();
    if (!$categoryManifest) {
      funcSend404();
    }

    $moduleGenerateContent->addonSite('cat-search-plugins', 'Search Plugins', $categoryManifest);
  }
  else {
    funcSend404();
  }
}
// Language Packs
elseif ($arraySoftwareState['requestPath'] == URI_LANGPACKS) {  
  if (funcIsEnabledFeature('language-packs')) {
    $categoryManifest = $moduleReadManifest->getCategory('language-packs');
    if (!$categoryManifest) {
      funcSend404();
    }

    $moduleGenerateContent->addonSite('cat-language-packs', 'Language Packs', $categoryManifest);
  }
  else {
    funcSend404();
  }
}
// There are no matches so error out
else {
  funcSend404();
}

// ====================================================================================================================

?>