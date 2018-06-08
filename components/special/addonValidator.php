<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// Include modules
$arrayIncludes = array('rdf');
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// == | Vars | ================================================================
ini_set("upload_tmp_dir", "/regolith/account/" . $_SERVER['SERVER_NAME'] . "/.obj/temp/");

$arrayKnownIDs = array(
    'applications' => TARGET_APPLICATION_ID,
    'reserved' => array(
        '-bfc5-fc555c87dbc4}', // Moonchild Productions
        '-9376-3763d1ad1978}', // Pseudo-Static
        '-b98e-98e62085837f}', // Ryan
        '-9aa0-aa0e607640b9}', // BinOC
        'palemoon.org',
        'basilisk-browser.org',
        'binaryoutcast.com',
        'mattatobin.com',
        'mozilla.org'
    )
);

$arrayAddonStatus = array(
    'fatal' => null,
    'errors' => null,
    'warnings' => null,
    'messages' => null,
    'isBootstrap' => null,
    'isJetpack' => null,
    'isBadJetpack' => null,
    'hasEmbeddedWebExtension' => null,
    'hasCreator' => null,
    'isValidIDFormat' => null,
    'isRestrictedID' => null,
    'hasValidTargetApplication' => null,
    'isValidMinVersion' => null,
    'isValidMaxVersion' => null,
    'hasDescription' => null,
    'isTooLongDesc' => null,
    'hasIcon' => null,
    'hasUpdateKeys' => null,
);

$strPageTitle = '<h1>Add-on Validator Tool (Beta)</h1>' . "\n" .
    '<div class="description">This tool will allow one to check the validity of a Mozilla-style add-on by doing some common tests on the install manifest file.<br /><br />It is primarily intended for Add-on Developers wishing to check their add-ons before submitting them to or updating them on the Add-ons Site. However, it does have applications for the regular everyday Pale Moon user. In that one can get important infomation about any XPI file.<br /><br />This tool does NOT intend to indicate any kind of technological compatiblity of a specific add-on. Though, it may provide some general indications.</div>';

// ============================================================================

// == | funcUpdateStatus | ====================================================

function funcUpdateStatus($_statusItem, $_statusValue, $_messageType = null, $_stringIndex = null) {
    $_arrayStrings = array(
        0 => 'This add-on has a valid ID format (GUID)',
        1 => 'This add-on has a valid ID format (user@host)',
        2 => 'This add-on has a invalid ID format. Please use a valid GUID with braces or a user@host ID',
        3 => 'This add-on has an ID that matches a reserved mozilla-style application ID',
        4 => 'This add-on has an ID that contains reserved elements',
        5 => 'This add-on has a Pale Moon targetApplication block',
        6 => 'This add-on has an invalid minVersion for Pale Moon - Infinite (*) minVersion is not allowed.. Period.',
        7 => 'This add-on has an invalid maxVersion for Pale Moon - Infinite (*) maxVersion is not allowed on the Add-ons Site',
        8 => 'This add-on does NOT have a Pale Moon targetApplication block',
        9 => 'This add-on has no creator - If you do not add one then a default creator will be used on the Add-ons Site',
        10 => 'This add-on has no description - If you do not add one then a default description will be used on the Add-ons Site',
        11 => 'This add-on\'s description is rather long.. Consider reducing it to under 235 characters',
        12 => 'This add-on uses Traditional XUL Toolkit (Overlay) Technology (or it might be a theme)',
        13 => 'This add-on uses Bootstrap (Restartless) Technology',
        14 => 'Please ensure that this add-on has been properly modified to conform to experimental PMKit standards',
        15 => 'Additionally, this add-on uses Jetpack (Add-on SDK) Technology',
        16 => 'This add-on uses OBSOLETE Jetpack (Add-on SDK) Technology - If you wish this add-on to work you will have to bring it up to a more modern Add-on SDK standard in addition to necessary alterations to conform to experimental PMKit standards',
        17 => 'This add-on has an embedded WebExtension. Pale Moon does not support WebExtensions at this time - Ensure your add-on is not compromised with WebExtension Technology and can function on its own without it',
        18 => 'This add-on has an updateURL or updateKey. While this could be perfectly normal for say, externals/indexed add-ons. You may not use those tags when directly hosting your add-on on the Add-ons Site because it will interfere with the Add-ons Update Service.',
    );

    $GLOBALS['arrayAddonStatus'][$_statusItem] = $_statusValue;

    if ($_messageType != null || $_stringIndex != null) {
        // $_messageType is either 'errors' or 'messages'
        $GLOBALS['arrayAddonStatus'][$_messageType][] = $_arrayStrings[$_stringIndex];
    }
}

// ============================================================================

// == | funcValidateRDF | =====================================================

function funcValidateRDF() {
    // Prep to read the xpi file and install.rdf
    $_arrayXPInstall = null;
    $_addonXPI = new ZipArchive;
    $_addonRDF = new RdfComponent();
    $_addonInstallRDFRaw = null;
    $_addonInstallRDF = null;

    // Open the XPI file
    if ($_addonXPI->open($_FILES['xpiFile']['tmp_name']) === true) {
        funcError($_FILES['xpiFile'], 1);
        // See if we have install.rdf
        // OR manifest.json and error out if this is a pure webextension
        if ($_addonXPI->locateName('install.rdf') >-1) {
            // Read install.rdf
            $_addonInstallRDFRaw = $_addonXPI->getFromName('install.rdf');
        }
        elseif ($_addonXPI->locateName('manifest.json') >-1) {
            // Screw webextensions..
            $GLOBALS['arrayAddonStatus']['fatal'][] = 'This script does not support WebExtensions';
            return null;
        }
        else {
            // No install.rdf found
            $GLOBALS['arrayAddonStatus']['fatal'][] = 'Could not find install.rdf';
            return null;
        }

        // Parse install.rdf through the RDF module
        $_addonInstallRDF = $_addonRDF->parseInstallManifest($_addonInstallRDFRaw);

        // If the result is a string then assume error
        if (is_string($_addonInstallRDF)) {
            $GLOBALS['arrayAddonStatus']['fatal'][] = 'Install.rdf Parsing error reported as: ' . $_addonInstallRDF;
            return null;
        }

        // Sort the RDF by key
        ksort($_addonInstallRDF);
        
        // Check ID Validity
        if (preg_match('/^\{[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\}$/i', $_addonInstallRDF['id'])) {
            funcUpdateStatus('isValidIDFormat', true, 'messages', 0);
        }
        elseif (preg_match('/[a-z0-9-\._]*\@[a-z0-9-\._]+/i', $_addonInstallRDF['id'])) {
            funcUpdateStatus('isValidIDFormat', true, 'messages', 1);
        }
        else {
            funcUpdateStatus('isValidIDFormat', false, 'errors', 2);
        }

        // Check for restricted IDs
        if (in_array($_addonInstallRDF['id'], $GLOBALS['arrayKnownIDs']['applications'])) {
            funcUpdateStatus('isRestrictedID', true, 'errors', 3);
        }
        else {
            foreach ($GLOBALS['arrayKnownIDs']['reserved'] as $_value) {
                if (contains($_addonInstallRDF['id'], $_value)) {
                    funcUpdateStatus('isRestrictedID', true, 'errors', 4);
                    break;
                }

                funcUpdateStatus('isRestrictedID', false);
            }
        }

        // Check if there is a Pale Moon targetApplication and that min and max version is not *
        if (array_key_exists(TARGET_APPLICATION_ID['palemoon'], $_addonInstallRDF['targetApplication'])) {
            funcUpdateStatus('hasValidTargetApplication', true, 'messages', 5);

            if ($_addonInstallRDF['targetApplication'][TARGET_APPLICATION_ID['palemoon']]['minVersion'] == '*') {
                funcUpdateStatus('isValidMinVersion', false, 'errors', 6);
            }
            else {
                funcUpdateStatus('isValidMinVersion', true);
            }

            if ($_addonInstallRDF['targetApplication'][TARGET_APPLICATION_ID['palemoon']]['maxVersion'] == '*') {
                funcUpdateStatus('isValidMaxVersion', false, 'errors', 7);
            }
            else {
                funcUpdateStatus('isValidMaxVersion', true);
            }
        }
        else {
            funcUpdateStatus('hasValidTargetApplication', false, 'errors', 8);
            funcUpdateStatus('isValidMinVersion', false);
            funcUpdateStatus('isValidMaxVersion', false);
        }

        // Check creator
        if (!array_key_exists('creator', $_addonInstallRDF)) {
            funcUpdateStatus('hasCreator', false, 'warnings', 9);
        }
        else {
            funcUpdateStatus('hasCreator', true);
        }

        // Check description
        if (!array_key_exists('description', $_addonInstallRDF)) {
            funcUpdateStatus('hasDescription', false, 'warnings', 10);
        }
        else {
            funcUpdateStatus('hasDescription', true);
            
            if (strlen($_addonInstallRDF['description']['en-US']) >= 235) {
                funcUpdateStatus('isTooLongDesc', true, 'warnings', 11);
            }
            else {
                funcUpdateStatus('isTooLongDesc', false);
            }
        }

        // Check bootstrap/jetpack
        if (!array_key_exists('bootstrap', $_addonInstallRDF)) {
            funcUpdateStatus('isBootstrap', false, 'messages', 12);
            funcUpdateStatus('isJetpack', false);
            funcUpdateStatus('isBadJetpack', false);
        }
        else {
            funcUpdateStatus('isBootstrap', true, 'messages', 13);

            // Jetpack
            if ($_addonXPI->locateName('package.json') >-1) {
                funcUpdateStatus('isJetpack', true, 'messages', 15);
                funcUpdateStatus('isJetpack', true, 'warnings', 14);
            }
            else {
                funcUpdateStatus('isJetpack', false);
            }

            // Bad Jetpack
            if ($_addonXPI->locateName('harness-options.json') >-1) {
                funcUpdateStatus('isBadJetpack', true, 'messages', 15);
                funcUpdateStatus('isBadJetpack', true, 'errors', 16);
            }
            else {
                funcUpdateStatus('isBadJetpack', false);
            }
        }

        // Check for embedded webex
        if (!array_key_exists('hasEmbeddedWebExtension', $_addonInstallRDF)) {
            funcUpdateStatus('hasEmbeddedWebExtension', false);
        }
        else {
            funcUpdateStatus('hasEmbeddedWebExtension', true, 'warnings', 17);
        }

        // Check for update keys
        if (array_key_exists('updateURL', $_addonInstallRDF) || array_key_exists('updateKey', $_addonInstallRDF)) {
            funcUpdateStatus('hasUpdateKeys', true, 'errors', 18);
        }
        else {
            funcUpdateStatus('hasUpdateKeys', false);
        }

        // See if there is at least icon.png
        if ($_addonXPI->locateName('icon.png') > -1) {
            funcUpdateStatus('hasIcon', true);
        }
        else {
            funcUpdateStatus('hasIcon', false);
        }
        
        // Close the XPI file
        $_addonXPI->close();
    }
    else {
        $GLOBALS['arrayAddonStatus']['fatal'][] = 'The XPI file could not be opened. It could be corrupt or not an actual XPI file';
        return null;
    }

    return array($_addonInstallRDF, $_addonInstallRDFRaw);
}

// ============================================================================

// == | Main | ================================================================

ob_start();

// Inital state
print(file_get_contents($strSkinPath . 'default/template-header.xhtml'));
print($strPageTitle);
print('<h2>Upload</h2>');
print('      <form action="" method="POST" enctype="multipart/form-data">
     <input type="file" name="xpiFile" />
     <input type="submit" value="Upload"/>
  </form>');

if (!empty($_FILES['xpiFile']['name'])) {
    // Validate the RDF
    $addonRDF = funcValidateRDF();

    if ($addonRDF != null) {
        // Print out the things
        if ($arrayAddonStatus['errors'] != null) {
            print('<h2>Errors</h2>');
            print('<p>These items will cause an add-on to be rejected on the Add-ons Site very soon.</p>');
            print('<ul>');
            foreach ($arrayAddonStatus['errors'] as $_value) {
                print('<li><span style="color: red;"><strong>' . $_value . '</strong></span></li>');
            }
            print('</ul>');
        }

        if ($arrayAddonStatus['warnings'] != null) {
            print('<h2>Warnings</h2>');
            print('<p>These items should be evaluated.</p>');
            print('<ul>');
            foreach ($arrayAddonStatus['warnings'] as $_value) {
                print('<li><span style="color: DarkOrange;"><strong>' . $_value . '</strong></span></li>');
            }
            print('</ul>');
        }

        if ($arrayAddonStatus['messages'] != null) {
            print('<h2>Messages</h2>');
            print('<p>They say "No news is good news" but we think it is better to know!</p>');
            print('<ul>');
            foreach ($arrayAddonStatus['messages'] as $_value) {
                print('<li><span style="color: green;"><strong>' . $_value . '</strong></span></li>');
            }
            print('</ul>');
        }

        unset($arrayAddonStatus['fatal']);
        unset($arrayAddonStatus['errors']);
        unset($arrayAddonStatus['warnings']);
        unset($arrayAddonStatus['messages']);

        print('<h2>All Checks (JSON Encoded)</h2>');
        print('<p>This is the raw status of the checks we did.</p>');
        print('<pre>');
        print(json_encode($arrayAddonStatus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        print('</pre>');

        print('<h2>Parsed Install Manifest (JSON Encoded)</h2>');
        print('<p>This is what the Install Manifest parser was able to extract from install.rdf</p>');
        print('<pre>');
        print(json_encode($addonRDF[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        print('</pre>');

        print('<h2>Original Install Manifest</h2>');
        print('<p>The install.rdf with possible html entities encoded. (It\'s a website thing)</p>');
        print('<pre>');
        print(htmlentities($addonRDF[1], ENT_XHTML));
        print('</pre>');
    }
    else {
        print('<h2>Fatal Errors</h2>');
        print('<ul>');
        foreach ($arrayAddonStatus['fatal'] as $_value) {
            print('<li><span class="pulseText" style="text-decoration: blink;"><strong>' . $_value . '</strong></span></li>');
        }
        print('</ul>');
    }
}

print(file_get_contents($strSkinPath . 'default/template-footer.xhtml'));

ob_end_flush();

// ============================================================================
?>