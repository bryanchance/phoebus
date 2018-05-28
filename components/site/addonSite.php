<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | ===============================================================

// URI Constants
const URI_ADDONS_PAGE = '/addons/';
const URI_ADDONS_RELEASES = '/releases/';
const URI_ADDONS_LICENSE = '/license/';
const URI_EXTENSIONS = '/extensions/';
const URI_THEMES = '/themes/';
const URI_SEARCHPLUGINS = '/search-plugins/';
const URI_LANGPACKS = '/language-packs/';
const URI_SEARCH = '/search/';

// Include modules
$arrayIncludes = array('sql', 'sql-creds', 'readManifest',
                       'smarty', 'generatePage');
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// ============================================================================

// == | Functions | ===========================================================

/******************************************************************************
* Strips path to obtain the slug
*
* @param $_path     $arraySoftwareState['requestPath']
* @param $_prefix   Prefix to strip 
* @returns          slug
******************************************************************************/
function funcStripPath($_path, $_prefix) {
  return str_replace('/', '', str_replace($_prefix, '', $_path));
}

/******************************************************************************
* Sends a 404 error but does it depending on debug mode
*
* @param $_path     $arraySoftwareState['requestPath']
* @param $_prefix   Prefix to strip 
* @returns          slug
******************************************************************************/
function funcSend404() {
  if (!$GLOBALS['arraySoftwareState']['debugMode']) {
    funcSendHeader('404');
  }
  funcError('404 - Not Found');
}

// ============================================================================

// == | Main | ================================================================

// These are Category Slugs with their titles
$arrayCategorySlug = array(
  'alerts-and-updates' => 'Alerts & Updates',
  'appearance' => 'Appearance',
  'bookmarks-and-tabs' => 'Bookmarks & Tabs',
  'download-management' => 'Download Management',
  'feeds-news-and-blogging' => 'Feeds, News, & Blogging',
  'privacy-and-security' => 'Privacy & Security',
  'search-tools' => 'Search Tools',
  'social-and-communication' => 'Social & Communication',
  'tools-and-utilities' => 'Tools & Utilities',
  'web-development' => 'Web Development',
  'other' => 'Other'
);

$moduleReadManifest = new classReadManifest();

// ----------------------------------------------------------------------------

// Site Name
// When in debug mode it displays the software name and version and if git
// is detected it will append the branch and short sha1 hash
// else it will use the name defined in TARGET_APPLICATION_SITE
if ($arraySoftwareState['debugMode']) {
  $arraySoftwareState['currentName'] =
    SOFTWARE_NAME .
    ' Development - Version: '
    . SOFTWARE_VERSION;
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
  $arraySoftwareState['currentName'] =
    TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['name'];
}

// ----------------------------------------------------------------------------

// Decide what kind of page is being requested
// The front page
if ($arraySoftwareState['requestPath'] == '/') {
  //funcError(array('Front Page', $arraySoftwareState), 1);
  $moduleGeneratePage = new classGeneratePage();
  $moduleGeneratePage->test();
}
// Add-on Page
elseif (startsWith($arraySoftwareState['requestPath'], URI_ADDON_PAGE)) {
  if ($arraySoftwareState['requestPath'] == URI_ADDON_PAGE) {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], URI_ADDON_PAGE);
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);
  funcError(array('Add-on Page: ' . $strSlug, $addonManifest, $arraySoftwareState), 1);
}
// Add-on Releases
elseif (startsWith($arraySoftwareState['requestPath'], URI_ADDON_RELEASES)) {
  if ($arraySoftwareState['requestPath'] == URI_ADDON_RELEASES) {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], URI_ADDON_RELEASES);
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);
  funcError(array('Add-on Releases: ' . $strSlug, $addonManifest, $arraySoftwareState), 1);
}
// Add-on License
elseif (startsWith($arraySoftwareState['requestPath'], URI_ADDON_LICENSE)) {
  if ($arraySoftwareState['requestPath'] == URI_ADDON_LICENSE) {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], URI_ADDON_LICENSE);
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);
  funcError(array('Add-on License: ' . $strSlug, $addonManifest, $arraySoftwareState), 1);
}
// Extensions Category or Subcategory
elseif (startsWith($arraySoftwareState['requestPath'], URI_EXTENSIONS)) {
  $boolExtensionsEnabled =
    in_array('extensions', TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['features']);
  $boolExtensionsCatEnabled =
    in_array('extensions-cat', TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['features']);
  
  // Extensions Category
  if ($arraySoftwareState['requestPath'] == URI_EXTENSIONS) {
    $categoryManifest = $moduleReadManifest->getAllExtensions();
    funcError(array('Extensions Category', $categoryManifest, $arraySoftwareState), 1);
  }

  if ($boolExtensionsCatEnabled) {
    // Extensions Subcategory
    // Strip the path to get the slug
    $strSlug = funcStripPath($arraySoftwareState['requestPath'], URI_EXTENSIONS);

    // See if the slug exists in the category array
    if (array_key_exists($strSlug, $arrayCategorySlug)) {
      $categoryManifest = $moduleReadManifest->getCategory($strSlug);
      funcError(array('Extensions Category: ' . $strSlug, $categoryManifest, $arraySoftwareState), 1);
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
  $boolThemesEnabled =
    in_array('themes', TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['features']);
  
  if ($boolThemesEnabled) {
    $categoryManifest = $moduleReadManifest->getCategory('themes');
    funcError(array('Themes Category', $categoryManifest, $arraySoftwareState), 1);
  }
  else {
    funcSend404();
  }
}
// Search Plugins
elseif ($arraySoftwareState['requestPath'] == URI_SEARCHPLUGINS) {
  $boolSearchPluginsEnabled =
    in_array('search-plugins', TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['features']);
  
  if ($boolSearchPluginsEnabled) {
    funcError(array('Search Plugins Category', $arraySoftwareState), 1);
  }
  else {
    funcSend404();
  }
}
// Language Packs
elseif ($arraySoftwareState['requestPath'] == URI_LANGPACKS) {
  $boolLangPacksEnabled =
    in_array('language-packs', TARGET_APPLICATION_SITE[$arraySoftwareState['currentApplication']]['features']);
  
  if ($boolLangPacksEnabled) {
    funcError(array('Language Packs Category', $boolLangPacksEnabled, $arraySoftwareState), 1);
  }
}
// There are no matches so error out
else {
  funcSend404();
}

// ============================================================================

?>