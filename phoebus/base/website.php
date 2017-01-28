<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Vars | ================================================================

$strContentBasePath = './phoebus/base/content/';
$strSkinBasePath = './phoebus/skin/palemoon/';

$arrayAddonPaths = array(
    '/extensions/',
    '/themes/',
    '/search-plugins/'
);

$arrayStaticPages = array(
    '/' => array(
        'title' => 'Your browser, your way!',
        'contentFile' => $strContentBasePath . 'pages/frontpage.xhtml',
    ),
    '/search/' => array(
        'title' => 'Search',
        'contentFile' => $strContentBasePath . 'pages/search.xhtml',
    ),
    '/incompatible/' => array(
        'title' => 'Known Incompatible Add-ons',
        'contentFile' => $strContentBasePath . 'pages/incompatible.xhtml',
    ),
    '/roadmap/' => array(
        'title' => 'Add-ons Site and Project Phoebus Roadmap',
        'contentFile' => $strContentBasePath . 'pages/roadmap.xhtml',
    ),
);

// ============================================================================

// == | funcGenerateStaticPage | ==============================================

function funcGeneratePage($_arrayPage) {
    $_strContentBasePath = $GLOBALS['strContentBasePath'];
    $_strSkinBasePath = $GLOBALS['strSkinBasePath'];

    $_strHTMLTemplate = file_get_contents($_strSkinBasePath . 'template.xhtml');
    $_strHTMLStyle = file_get_contents($_strSkinBasePath . 'style.css');
    $_strPageMenu = file_get_contents($_strSkinBasePath . 'menubar.xhtml');
    
    if (array_key_exists('contentFile', $_arrayPage) && file_exists($_arrayPage['contentFile'])) {
        $_strHTMLContent = file_get_contents($_arrayPage['contentFile']);
    }
    elseif (array_key_exists('content', $_arrayPage)) {
        $_strHTMLContent = $_arrayPage['content'];
    }
    else {
        funcError('Could not properly read content');
    }

    $_strHTMLPage = $_strHTMLTemplate;

    $_arrayFilterSubstitute = array(
        '@PAGE_CONTENT@' => $_strHTMLContent,
        '@SITE_MENU@' => $_strPageMenu,
        '@SITE_STYLESHEET@' => $_strHTMLStyle,
        '@SITE_NAME@' => $GLOBALS['strPhoebusSiteName'],
        '@PAGE_TITLE@' => $_arrayPage['title'],
        '@BASE_PATH@' => substr($_strSkinBasePath, 1),
    );
    
    if (array_key_exists('subContent', $_arrayPage)) {
        $_arrayFilterSubstitute['@PAGE_SUBCONTENT@'] = $_arrayPage['subContent'];
        $_arrayFilterSubstitute['@SITE_DOMAIN@'] = '//' . $GLOBALS['strPhoebusURL'];
    }

    foreach ($_arrayFilterSubstitute as $_key => $_value) {
        $_strHTMLPage = str_replace($_key, $_value, $_strHTMLPage);
    }

    funcSendHeader('html');
    print($_strHTMLPage);
    
    // We are done here...
    exit();
}

// ============================================================================

// == | Main | ================================================================

if (startsWith($strRequestPath, '/extensions/') == true ||
    startsWith($strRequestPath, '/themes/') == true ||
    startsWith($strRequestPath, '/search-plugins/') == true) {
    require_once('./phoebus/base/addons.php');
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