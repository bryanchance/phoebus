<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Functions | ===========================================================

// ============================================================================

// == | Vars | ================================================================

// These are valid URIs
$arraySiteURI = array(
  'frontpage' => '/',
  'addonPage' => '/addon/',
  'addonReleases' => '/releases/',
  'addonLicense' => '/license/',
  'extensions' => '/extensions/',
  'themes' => '/themes/',
  'search' => '/search/',
);

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

// ============================================================================

// == | Main | ================================================================

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

funcError($arraySoftwareState, 1);

// ============================================================================

?>