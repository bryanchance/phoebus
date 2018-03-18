<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Vars | ================================================================

$strApplicationSiteName = 'Pale Moon - Add-ons';
$strApplicationSkin = $arraySkins['palemoon'];
$strContentBasePath = './components/site/content/';
$strObjDirSmartyCachePath = $strObjDirPath . 'smarty/frontend/';

$strRequestSmartyDebug = funcHTTPGetValue('smartyDebug');

$arraySmartyPaths = array(
  'cache' => $strObjDirSmartyCachePath . 'cache',
  'compile' => $strObjDirSmartyCachePath . 'compile',
  'config' => $strObjDirSmartyCachePath . 'config',
  'plugins' => $strObjDirSmartyCachePath . 'plugins',
  'templates' => $strObjDirSmartyCachePath . 'templates',
);

$arrayStaticPages = array(
  '/' => array(
    'title' => 'Your browser, your way!',
    'contentTemplate' => $strContentBasePath . 'frontpage.xhtml.tpl',
  ),
  '/incompatible/' => array(
    'title' => 'Known Incompatible Add-ons',
    'contentTemplate' => $strContentBasePath . 'incompatible.xhtml.tpl',
  )
);

// ============================================================================

// == | funcGeneratePage | ====================================================

function funcGeneratePage($_array) {
  $_strSkinBasePath = str_replace($GLOBALS['strRootPath'], '', $GLOBALS['strApplicationSkin']);
  // Get the required template files
  $_strSiteTemplate = file_get_contents($GLOBALS['strApplicationSkin'] . 'template.tpl');
  $_strStyleSheet = file_get_contents($GLOBALS['strApplicationSkin'] . 'stylesheet.tpl');
  $_strContentTemplate = file_get_contents($_array['contentTemplate']);

  // Merge the stylesheet and the content template into the site template
  $_strSiteTemplate = str_replace('{%PAGE_CONTENT}', $_strContentTemplate, $_strSiteTemplate);
  $_strSiteTemplate = str_replace('{%SITE_STYLESHEET}', $_strStyleSheet, $_strSiteTemplate);
  unset($_strStyleSheet);
  unset($_strContentTemplate);

  // Load Smarty
  require_once($GLOBALS['arrayModules']['smarty']);
  $libSmarty = new Smarty();
  
  // Configure Smarty
  $libSmarty->caching = 0;
  $libSmarty->setCacheDir($GLOBALS['arraySmartyPaths']['cache'])
    ->setCompileDir($GLOBALS['arraySmartyPaths']['compile'])
    ->setConfigDir($GLOBALS['arraySmartyPaths']['config'])
    ->addPluginsDir($GLOBALS['arraySmartyPaths']['plugins'])
    ->setTemplateDir($GLOBALS['arraySmartyPaths']['templates']);

  // Smarty Debug
  if ($GLOBALS['strRequestSmartyDebug']) {
    $libSmarty->debugging = $GLOBALS['boolDebugMode'];
  }
  else {
    $libSmarty->debugging = false;
  }
  
  // Assign data to Smarty
  $libSmarty->assign('APPLICATION_DEBUG', $GLOBALS['boolDebugMode']);
  $libSmarty->assign('SITE_NAME', $GLOBALS['strApplicationSiteName']);
  $libSmarty->assign('SITE_DOMAIN', '//' . $GLOBALS['strApplicationURL']);
  $libSmarty->assign('PAGE_TITLE', $_array['title']);
  $libSmarty->assign('PAGE_PATH', $GLOBALS['strRequestPath']);
  $libSmarty->assign('BASE_PATH', $_strSkinBasePath);
  $libSmarty->assign('PHOEBUS_VERSION', $GLOBALS['strApplicationVersion']);
  
  if (array_key_exists('contentData', $_array)) {
    $libSmarty->assign('PAGE_DATA', $_array['contentData']);
  }
  
  if (array_key_exists('contentType', $_array)) {
    $libSmarty->assign('PAGE_TYPE', $_array['contentType']);
  }
  
  // Send html header and pass the final template to Smarty
  funcSendHeader('html');
  $libSmarty->display('string:' . $_strSiteTemplate, null, str_replace('/', '_', $GLOBALS['strRequestPath']));

  // We are done here...
  exit();
}

// ============================================================================

// == | Main | ================================================================

// Debug Conditions
if ($boolDebugMode == true) {
  // Git stuff
  if (file_exists('./.git/HEAD')) {
    $_strGitHead = file_get_contents('./.git/HEAD');
    $_strGitSHA1 = file_get_contents('./.git/' . substr($_strGitHead, 5, -1));
    $_strGitBranch = substr($_strGitHead, 16, -1);
    $strApplicationSiteName = 'Phoebus Development - Version: ' . $strApplicationVersion . ' - ' .
      'Branch: ' . $_strGitBranch . ' - ' .
      'Commit: ' . substr($_strGitSHA1, 0, 7);
  }
  else {
    $strApplicationSiteName = 'Phoebus Development - Version: ' . $strApplicationVersion;
  }  
}

require_once($arrayModules['readManifest']);
$addonManifest = new classReadManifest();

// Start deciding what to do with the URL
// Single Add-on Pages
if (startsWith($strRequestPath, '/addon/')) {
  $strStrippedPath = str_replace('/', '', str_replace('/addon/', '', $strRequestPath));  
  $arrayAddonMetadata = $addonManifest->getAddonBySlug($strStrippedPath);

  if ($arrayAddonMetadata != null) {     
    $arrayPage = array(
      'title' => $arrayAddonMetadata['name'],
      'contentTemplate' => $strApplicationSkin . 'single-addon.tpl',
      'contentData' => $arrayAddonMetadata
    );
  }
  else {
    if ($GLOBALS['boolDebugMode'] == true) {
      funcError('The requested add-on has a problem');
    }
    else {
      funcSendHeader('404');
    }     
  }
  funcGeneratePage($arrayPage);
}
elseif ($strRequestPath == '/extensions/') {
  $arrayCategory = $addonManifest->getAllExtensions();

  if ($arrayCategory != null) {
    $arrayPage = array(
      'title' => 'Extensions',
      'contentType' => 'cat-all-extensions',
      'contentTemplate' => $strApplicationSkin . 'category-addons.tpl',
      'contentData' => $arrayCategory
    );
  }
  else {
    if ($GLOBALS['boolDebugMode'] == true) {
      funcError('The requested category has a problem');
    }
    else {
      funcSendHeader('404');
    }     
  }

  funcGeneratePage($arrayPage);
}
elseif (startsWith($strRequestPath, '/extensions/')) {
  $strStrippedPath = str_replace('/', '', str_replace('/extensions/', '', $strRequestPath));

  $arrayCategoriesDB = array(
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
  
  if (array_key_exists($strStrippedPath, $arrayCategoriesDB))  {
      $arrayCategory = $addonManifest->getCategory($strStrippedPath);

    if ($arrayCategory != null) {
      $arrayPage = array(
        'title' => $arrayCategoriesDB[$strStrippedPath],
        'contentType' => 'cat-extensions',
        'contentTemplate' => $strApplicationSkin . 'category-addons.tpl',
        'contentData' => $arrayCategory
      );
    }
    else {
      if ($GLOBALS['boolDebugMode'] == true) {
        funcError('The requested category has a problem');
      }
      else {
        funcSendHeader('404');
      }     
    }

    funcGeneratePage($arrayPage);
  }
  else {
    funcSendHeader('404');
  }
}
// Themes Category
elseif ($strRequestPath == '/themes/') {
  $arrayCategory = $addonManifest->getCategory('themes');
  
  if ($arrayCategory != null) {
    $arrayPage = array(
      'title' => 'Themes',
      'contentTemplate' => $strApplicationSkin . 'category-addons.tpl',
      'contentType' => 'cat-themes',
      'contentData' => $arrayCategory
    );
  }
  else {
    if ($GLOBALS['boolDebugMode'] == true) {
      funcError('The requested category has a problem');
    }
    else {
      funcSendHeader('404');
    }     
  }
  
  funcGeneratePage($arrayPage);
}
elseif ($strRequestPath == '/language-packs/') {
  funcError('Languge Packs have been disabled');
}
elseif ($strRequestPath == '/search-plugins/') {
  require_once($arrayModules['dbSearchPlugins']);
  $arrayCategory = $addonManifest->getSearchPlugins($arraySearchPluginsDB);
  
  if ($arrayCategory != null) {
    $arrayPage = array(
      'title' => 'Search Plugins',
      'contentTemplate' => $strApplicationSkin . 'category-searchPlugins.tpl',
      'contentType' => 'cat-themes',
      'contentData' => $arrayCategory
    );
  }
  else {
    if ($GLOBALS['boolDebugMode'] == true) {
      funcError('The requested category has a problem');
    }
    else {
      funcSendHeader('404');
    }     
  }
  
  funcGeneratePage($arrayPage);
}
elseif ($strRequestPath == '/search/') {
  $strSearchTearms = funcHTTPGetValue('terms');
  $arrayResults = $addonManifest->getSearchResults($strSearchTearms);

  if ($strSearchTearms == null || $arrayResults == null) {
    $arrayPage = array(
      'title' => 'No Results',
      'contentType' => 'search',
      'contentTemplate' => $strApplicationSkin . 'category-addons.tpl',
      'contentData' => null
    );
  }
  else {
    $arrayPage = array(
      'title' => 'Search Results for "' . $strSearchTearms . '"',
      'contentType' => 'search',
      'contentTemplate' => $strApplicationSkin . 'category-addons.tpl',
      'contentData' => $arrayResults
    );
  }
  
  funcGeneratePage($arrayPage);
}
else {
  if (array_key_exists($strRequestPath, $arrayStaticPages)) {
    funcGeneratePage($arrayStaticPages[$strRequestPath]);
  }
  else {
    funcSendHeader('404');
  }
}

// ============================================================================
?>