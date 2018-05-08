<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Vars | ================================================================

$strAMOServicesURL = 'https://services.addons.mozilla.org/';
$strAMOServicesAPIPath = '/firefox/api/1.5/';

// Main Entry Points
$strRequestType = funcHTTPGetValue('type');
$strRequestReq = funcHTTPGetValue('request');

// Possible arguments directly passed from the Browser
$strRequestAddonID = funcHTTPGetValue('addonguid');
$strRequestSearchQuery = funcHTTPGetValue('q');
$strRequestLocale = funcHTTPGetValue('locale');
$strRequestOS = funcHTTPGetValue('os');
$strRequestVersion = funcHTTPGetValue('version');

$_strFirefoxVersion = $strFirefoxVersion;

// ============================================================================

// == | Main | ================================================================

// Sanity
if ($strRequestType == null || $strRequestReq == null) {
    funcError('Missing minimum arguments (type or request)');
}

// Start the logic to fulfill the request
if ($strRequestType == 'internal') {
    if ($strRequestReq == 'get' || $strRequestReq == 'search') {
        funcSendHeader('xml');
        print(
            '<?xml version="1.0" encoding="utf-8" ?>' . "\n" .
            '<searchresults total_results="0">' . "\n" .
            '</searchresults>'
        );
        exit();
    }
    elseif ($strRequestReq == 'recommended') {
        funcSendHeader('xml');
        print(
            '<?xml version="1.0" encoding="utf-8" ?>' . "\n" .
            '<addons>' . "\n" .
            '</addons>'
        );
        exit();
    }
    else {
        funcError('Unknown Internal Request');
    }
}
elseif ($strRequestType == 'external') {
    if ($strRequestReq == 'search') {
        funcRedirect('https://addons.palemoon.org/extensions/');
    }
    elseif ($strRequestReq == 'recommended') {
        funcRedirect('/');
    }
    elseif ($strRequestReq == 'themes') {
        funcRedirect('/themes/');
    }
    elseif ($strRequestReq == 'searchplugins') {
        funcRedirect('/search-plugins/');
    }
    elseif ($strRequestReq == 'devtools') {
        funcRedirect('/extensions/web-development/');
    }
    else {
        funcError('Unknown External Request');
    }
}
else {
    funcError('Unknown scope'); 
}

// ============================================================================
?>