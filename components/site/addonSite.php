<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Vars | ================================================================

$strApplicationSiteName = 'Pale Moon - Add-ons';
$strApplicationSkin = 'palemoon';
$strContentBasePath = './components/site/content/';
$strSkinBasePath = './skin/' . $strApplicationSkin . '/';
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
    '/search/' => array(
        'title' => 'Search',
        'contentTemplate' => $strContentBasePath . 'search.xhtml.tpl',
    ),
    '/incompatible/' => array(
        'title' => 'Known Incompatible Add-ons',
        'contentTemplate' => $strContentBasePath . 'incompatible.xhtml.tpl',
    )
);

// ============================================================================

// == | funcGenAddonContent | =================================================

function funcGenAddonContent($_strAddonSlug) {
    $_addonManifest = new classAddonManifest();
    $_arrayAddonMetadata = $_addonManifest->funcGetManifest($_strAddonSlug);

    if ($_arrayAddonMetadata != null) {
        $_arrayAddonMetadata['basePath'] = substr($_arrayAddonMetadata['basePath'], 1);
        
        $arrayPage = array(
            'title' => $_arrayAddonMetadata['name'],
            'contentTemplate' => $GLOBALS['strSkinBasePath'] . 'single-addon.tpl',
            'contentData' => $_arrayAddonMetadata
        );
        
        return $arrayPage;
    }
    else {
        if ($GLOBALS['boolDebugMode'] == true) {
            funcError('The requested add-on has a problem with it\'s manifest file');
        }
        else {
            funcSendHeader('404');
        }       
    }
}

// ============================================================================

// == | funcGenAllExtensions | ================================================

function funcGenAllExtensions($_array) {
    $arrayCategory = array();
    
    foreach ($_array as $_key => $_value) {
        if ($_key != 'themes') {
            $_arraySingleCategory = funcGenCategoryContent('extension', $_value);
            foreach ($_arraySingleCategory["contentData"] as $_key2 => $_value2) {
                $arrayCategory[$_key2] = $_value2;
            }
        }
    }
    
    ksort($arrayCategory, SORT_NATURAL | SORT_FLAG_CASE);
    
    $arrayPage = array(
        'title' => 'Extensions',
        'contentType' => 'cat-all-extensions',
        'contentTemplate' => $GLOBALS['strSkinBasePath'] . 'category-addons.tpl',
        'contentData' => $arrayCategory
    );

    return $arrayPage;
}

// ============================================================================

// == | funcGenCategoryContent | ==============================================

function funcGenCategoryContent($_type, $_array) {
    $_addonManifest = new classAddonManifest();
    $arrayCategory = array();
    $_strDatastoreBasePath = $GLOBALS['strApplicationDatastore'] . 'addons/';
    
    foreach ($_array as $_key => $_value) {
        if (($_type === 'extension' || $_type == 'theme') && is_int($_key)) {
            $_arrayAddonMetadata = $_addonManifest->funcGetManifest($_value);
            if ($_arrayAddonMetadata != null) {
                $arrayCategory[$_arrayAddonMetadata['name']] = $_arrayAddonMetadata;
                unset($_arrayAddonMetadata);
            }
        }
        elseif ($_key === 'externals') {
            foreach($_array['externals'] as $_key2 => $_value2) {
                $arrayCategory[$_value2['name']]['type'] = 'external';
                $arrayCategory[$_value2['name']]['name'] = $_value2['name'];
                $arrayCategory[$_value2['name']]['slug'] = $_value2['id'];
                $arrayCategory[$_value2['name']]['url'] = $_value2['url'];
                $arrayCategory[$_value2['name']]['shortDescription'] = $_value2['shortDescription'];

                if ($_value2['id'] != 'default' && file_exists($_strDatastoreBasePath . $_value2['id'] . '/icon.png')) {
                    $arrayCategory[$_value2['name']]['icon'] = substr($_strDatastoreBasePath . $_value2['id'] . '/icon.png', 1);
                }
                else {
                    $arrayCategory[$_value2['name']]['icon'] = substr($_strDatastoreBasePath . 'default/' . $_type . '.png', 1);
                    
                }
                
                if ($_value2['id'] != 'default' && file_exists($_strDatastoreBasePath . $_value2['id'] . '/preview.png')) {
                    $arrayCategory[$_value2['name']]['preview'] = substr($_strDatastoreBasePath . $_value2['id'] . '/preview.png', 1);
                }
                else {
                    $arrayCategory[$_value2['name']]['preview'] = substr($_strDatastoreBasePath . 'default/preview.png', 1);
                }                
            }
        }
        elseif ($_type === 'language-pack') {
            foreach($_array as $_key3 => $_value3) {
                $arrayCategory[$_value3['name']] = $_value3;
            }
        }
        elseif ($_type === 'search-plugin') {
            $_arrayAddonMetadata = simplexml_load_file('./datastore/searchplugins/' . $_value);
            $arrayCategory[(string)$_arrayAddonMetadata->ShortName]['type'] = 'search-plugin';
            $arrayCategory[(string)$_arrayAddonMetadata->ShortName]['id'] = $_key;
            $arrayCategory[(string)$_arrayAddonMetadata->ShortName]['name'] = (string)$_arrayAddonMetadata->ShortName;
            $arrayCategory[(string)$_arrayAddonMetadata->ShortName]['slug'] = substr($_value, 0, -4);
            $arrayCategory[(string)$_arrayAddonMetadata->ShortName]['icon'] = (string)$_arrayAddonMetadata->Image;
            unset($_arrayAddonMetadata);
        }
    }
    ksort($arrayCategory, SORT_NATURAL | SORT_FLAG_CASE);
    
    if ($_type == 'extension') {
        $arrayPage = array(
            'title' => $_array['title'],
            'contentTemplate' => $GLOBALS['strSkinBasePath'] . 'category-addons.tpl',
            'contentType' => 'cat-extensions',
            'contentData' => $arrayCategory
        );
    }
    elseif ($_type == 'theme') {
        $arrayPage = array(
            'title' => $_array['title'],
            'contentTemplate' => $GLOBALS['strSkinBasePath'] . 'category-addons.tpl',
            'contentType' => 'cat-themes',
            'contentData' => $arrayCategory
        );
    }
    elseif ($_type == 'language-pack') {
        $arrayPage = array(
            'title' => 'Language Packs',
            'contentTemplate' => $GLOBALS['strSkinBasePath'] . 'category-other.tpl',
            'contentType' => 'cat-language-packs',
            'contentData' => $arrayCategory
        );
    }
    elseif ($_type == 'search-plugin') {
        $arrayPage = array(
            'title' => 'Search Plugins',
            'contentTemplate' => $GLOBALS['strSkinBasePath'] . 'category-other.tpl',
            'contentType' => 'cat-search-plugins',
            'contentData' => $arrayCategory
        );
    }
    
    return $arrayPage;
}

// ============================================================================

// == | funcGeneratePage | ====================================================

function funcGeneratePage($_array) {
    // Get the required template files
    $_strSiteTemplate = file_get_contents($GLOBALS['strSkinBasePath'] . 'template.tpl');
    $_strStyleSheet = file_get_contents($GLOBALS['strSkinBasePath'] . 'stylesheet.tpl');
    $_strContentTemplate = file_get_contents($_array['contentTemplate']);

    // Merge the stylesheet and the content template into the site template
    $_strSiteTemplate = str_replace('{%PAGE_CONTENT}', $_strContentTemplate, $_strSiteTemplate);
    $_strSiteTemplate = str_replace('{%SITE_STYLESHEET}', $_strStyleSheet, $_strSiteTemplate);
    $_randomDownloadButtonName = 'PM-' . bin2hex(openssl_random_pseudo_bytes(4));
    $_strSiteTemplate = str_replace('dllink_green', $_randomDownloadButtonName, $_strSiteTemplate);
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
    $libSmarty->assign('BASE_PATH', substr($GLOBALS['strSkinBasePath'], 1));
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
    // Do not allow public access to the site component when on addons-dev
    require_once($arrayModules['ftpAuth']);
    $FTPAuth = new classFTPAuth;
    $isAuthorized = $FTPAuth->doAuth(true);

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

require_once($arrayModules['addonManifest']);
$addonManifest = new classAddonManifest();

if (startsWith($strRequestPath, '/addon/')) {
    require_once($arrayModules['dbAddons']);
    $strStrippedPath = str_replace('/', '', str_replace('/addon/', '', $strRequestPath));
    $ArrayDBFlip = array_flip($arrayAddonsDB);
    
    if (array_key_exists($strStrippedPath,$ArrayDBFlip)) {
        funcGeneratePage(funcGenAddonContent($strStrippedPath));
    }
    else {
        funcSendHeader('404');
    }
}
elseif (startsWith($strRequestPath, '/extensions/') || startsWith($strRequestPath, '/themes/')) {
    require_once($arrayModules['dbCategories']);
    
    if ($strRequestPath == '/extensions/') {
        funcGeneratePage(funcGenAllExtensions($arrayCategoriesDB));
    }
    elseif (startsWith($strRequestPath, '/extensions/')) {
        $strStrippedPath = str_replace('/', '', str_replace('/extensions/', '', $strRequestPath));
        
        if (array_key_exists($strStrippedPath,$arrayCategoriesDB) && $strStrippedPath != 'themes') {
            funcGeneratePage(funcGenCategoryContent('extension', $arrayCategoriesDB[$strStrippedPath]));
        }
        else {
            // Nginx cannot easily handle the condition of non-matching-category slug so send non-matching slugs to /addon/[slug] and let that 404 if it isn't an extension
            funcRedirect('/addon/' . $strStrippedPath);
        }
    }
    elseif ($strRequestPath == '/themes/') {
        funcGeneratePage(funcGenCategoryContent('theme', $arrayCategoriesDB['themes']));
    }
    else {
        funcSendHeader('404');
    }
}
elseif ($strRequestPath == '/language-packs/') {
    require_once($arrayModules['langPacks']);
    $langPacks = new classLangPacks;
    $arrayLangPackDB = $langPacks->funcGetLanguagePacks();
    
    funcGeneratePage(funcGenCategoryContent('language-pack', $arrayLangPackDB));
}
elseif ($strRequestPath == '/search-plugins/') {
    require_once($arrayModules['dbSearchPlugins']);
    
    funcGeneratePage(funcGenCategoryContent('search-plugin', $arraySearchPluginsDB));
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