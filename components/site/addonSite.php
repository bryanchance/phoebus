<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | ===============================================================

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

$generatePage = new classGeneratePage();
$generatePage->test();

// Decide what kind of page is being requested
// The front page
if ($arraySoftwareState['requestPath'] == '/') {
  funcError(array('Front Page', $arraySoftwareState), 1);
}
// Add-on Page
elseif (startsWith($arraySoftwareState['requestPath'], '/addon/')) {
  if ($arraySoftwareState['requestPath'] == '/addon/') {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], '/addon/');
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);
  funcError(array('Add-on Page: ' . $strSlug, $addonManifest, $arraySoftwareState), 1);
}
// Add-on Releases
elseif (startsWith($arraySoftwareState['requestPath'], '/releases/')) {
  if ($arraySoftwareState['requestPath'] == '/releases/') {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], '/releases/');
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);
  funcError(array('Add-on Releases: ' . $strSlug, $addonManifest, $arraySoftwareState), 1);
}
// Add-on License
elseif (startsWith($arraySoftwareState['requestPath'], '/license/')) {
  if ($arraySoftwareState['requestPath'] == '/license/') {
    funcRedirect('/');
  }

  $strSlug = funcStripPath($arraySoftwareState['requestPath'], '/license/');
  $addonManifest = $moduleReadManifest->getAddonBySlug($strSlug);
  funcError(array('Add-on License: ' . $strSlug, $addonManifest, $arraySoftwareState), 1);
}
// Extensions Category or Subcategory
elseif (startsWith($arraySoftwareState['requestPath'], '/extensions/')) {
  // Extensions Category
  if ($arraySoftwareState['requestPath'] == '/extensions/') {
    $categoryManifest = $moduleReadManifest->getAllExtensions();
    funcError(array('Extensions Category', $categoryManifest, $arraySoftwareState), 1);
  }

  // Extensions Subcategory
  // Strip the path to get the slug
  $strSlug = funcStripPath($arraySoftwareState['requestPath'], '/extensions/');

  // See if the slug exists in the category array
  if (array_key_exists($strSlug, $arrayCategorySlug)) {
    $categoryManifest = $moduleReadManifest->getCategory($strSlug);
    funcError(array('Extensions Category: ' . $strSlug, $categoryManifest, $arraySoftwareState), 1);
  }
  else {
    if (!$arraySoftwareState['debugMode']) {
      funcSendHeader('404');
    }
    funcError('404 - Not Found');
  }
}
// Themes Category
elseif ($arraySoftwareState['requestPath'] == '/themes/') {
  $categoryManifest = $moduleReadManifest->getCategory('themes');
  funcError(array('Themes Category', $categoryManifest, $arraySoftwareState), 1);
}
// Search Plugins
elseif ($arraySoftwareState['requestPath'] == '/search-plugins/') {
  funcError(array('Search Plugins Category', $arraySoftwareState), 1);
}
// Language Packs
elseif ($arraySoftwareState['requestPath'] == '/search-plugins/') {
  funcError(array('Search Plugins Category', $arraySoftwareState), 1);
}
// There are no matches so error out
else {
  if (!$arraySoftwareState['debugMode']) {
    funcSendHeader('404');
  }
  funcError('404 - Not Found');
}

// ============================================================================

?>