<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Vars | ================================================================

$arraySections = array(
    'pages' => './phoebus/components/site/pages.php',
    'extensions' => './phoebus/components/site/extensions.php',
    'themes' => './phoebus/components/site/themes.php',
    'searchplugins' => './phoebus/components/site/searchplugins.php'
);

// ============================================================================

// == | Main | ================================================================

include_once($arrayModules['readManifest']);

if ($strRequestPath == '/') {
    header('Content-Type: text/plain');
    print('homepage');
}
elseif (startsWith($strRequestPath, '/extensions/')) {
    include_once($arrayModules['dbExtensions']);
    if ($strRequestPath == '/extensions/') {
        header('Content-Type: text/plain');
        print('extensions main page');
    }
    elseif ($strRequestPath == '/extensions/all/') {
        header('Content-Type: text/plain');
        foreach ($arrayExtensionsDB as $_key => $_value) {
            var_dump(funcReadManifest('extension', $_value, true, false, false, false, false));
        }
    }
    else {
        $strStrippedPath = str_replace('/', '', str_replace('/extensions/', '', $strRequestPath));
        $ArrayDBFlip = array_flip($arrayExtensionsDB);

        if (array_key_exists($strStrippedPath,$ArrayDBFlip)) {
            header('Content-Type: text/plain');
            var_dump(funcReadManifest('extension', $strStrippedPath, true, true, false, false, false));
        }
        else {
            header("HTTP/1.0 404 Not Found");
        }
    }
}
elseif (startsWith($strRequestPath, '/themes/')) {
    include_once($arrayModules['dbThemes']);
    if ($strRequestPath == '/themes/') {
        header('Content-Type: text/plain');
        foreach ($arrayThemesDB as $_key => $_value) {
            var_dump(funcReadManifest('theme', $_value, true, false, false, false, false));
        }
    }
    else {
        $strStrippedPath = str_replace('/', '', str_replace('/themes/', '', $strRequestPath));
        $ArrayDBFlip = array_flip($arrayThemesDB);

        if (array_key_exists($strStrippedPath,$ArrayDBFlip)) {
            header('Content-Type: text/plain');
            var_dump(funcReadManifest('theme', $strStrippedPath, true, true, false, false, false));
        }
        else {
            header("HTTP/1.0 404 Not Found");
        }
    }
}
elseif (startsWith($strRequestPath, '/searchplugins/')) {
    include_once($arrayModules['dbSearchPlugins']);
    header('Content-Type: text/plain');
    $arraySearchPluginsDB = array_flip(asort($arraySearchPluginsDB));
    var_dump($arraySearchPluginsDB);
}
else {
    header("HTTP/1.0 404 Not Found");
}

// ============================================================================
?>