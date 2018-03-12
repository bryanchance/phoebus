<?php
// == | classAddonManifest | ==================================================

class classAddonManifest {
    // We want a prop that warning and error messages can be sent to
    public $addonErrors;

    // ------------------------------------------------------------------------

    // Takes two variables str $_addonSlug and bool $_processOnly
    // Returns an array representing the datastructure of an add-on manifest
    public function funcGetManifest($_addonSlug, $_processOnly = null) {
        $_boolProcess = null;
        $_boolRegenerate = null;

        // We want to log warnings and errors and include them in the manifest
        // So ensure that the property is null before we begin
        $this->funcClearErrors();

        // Get basic phoebus files
        $_arrayPhoebusFiles = $this->funcGetFiles($_addonSlug);

        // Enable the shadow file
        if ($_processOnly == null) {
            if ($_arrayPhoebusFiles['shadow']['exists'] == false && $_arrayPhoebusFiles['temp']['exists'] == false) {
                // There is no shadow file so we should generate it
                // Process the manifest files and generate the shadow file
                $_boolProcess = true;
                $_boolRegenerate = true;
            }
            elseif ($_arrayPhoebusFiles['shadow']['exists'] == true) {
                // There is a shadow file so read it and store it as $_addonManifest
                $_addonManifest = $this->funcReadJSON($_arrayPhoebusFiles['shadow']['file']);
                
                // If the JSON file is valid json we should consider it good
                if ($_addonManifest != null) {
                    $_addonManifest['phoebus']['regenerated'] = false;
                    
                    // We should only consider regenerating the file if the temporary file does not exist
                    if ($_arrayPhoebusFiles['temp']['exists'] == false) {
                        // Test to see if any phoebus files have changed
                        if ($_addonManifest['phoebus']['manifestHash'] != $_arrayPhoebusFiles['manifest']['hash'] ||
                        $_addonManifest['phoebus']['contentHash'] != $_arrayPhoebusFiles['content']['hash'] ||
                        $_addonManifest['phoebus']['licenseHash'] != $_arrayPhoebusFiles['license']['hash'] ||
                        $_addonManifest['phoebus']['xpiCount'] != $_arrayPhoebusFiles['xpinstall']['count']) {
                            // Files have changed so we should process the manifest files and regenerate the shadow file
                            $_boolProcess = true;
                            $_boolRegenerate = true;
                        }
                    }
                }
                else {
                    // Fall back to on-the-fly and just process the manifest files
                    $_boolProcess = true;
                }
            }
            else {
                // Fall back to on-the-fly and just process the manifest files
                $_boolProcess = true;
            }
        }
        else {
            $_boolProcess = true;
        }

        // Process the phoebus files
        if ($_boolProcess == true) {
            $_addonReleaseRDF = null;

            if ($_arrayPhoebusFiles['manifest']['exists'] == true) {
                $_addonManifest = $this->funcReadINI($_arrayPhoebusFiles['manifest']['file']);
            }
            else {
                return $this->funcAddonError($_addonSlug, 'error',
                    'phoebus.manifest does not physically exist', true);
            }

            // Ensure the release XPI physically exists
            if (file_exists($_arrayPhoebusFiles['basePath'] . $_addonManifest['addon']['release']) != true) {
                return $this->funcAddonError($_addonSlug, 'error',
                    'Release XPI does not physically exist', true);
            }

            // Get info on xpi files and build the xpinstall key
            foreach($_arrayPhoebusFiles['xpinstall']['files'] as $_value) {
                // Process the XPI
                $_xpiFile = $this->funcProcessXPInstall(
                    $_arrayPhoebusFiles['basePath'],
                    $_value,
                    $_addonManifest['addon']['id'],
                    $_addonManifest['addon']['release']
                    );           
                
                // Set filename.xpi key on xpinstall
                if ($_xpiFile == null && $_value == $_addonManifest['addon']['release']) {
                    return $this->funcAddonError($_addonSlug, 'error',
                        'Release XPI has a problem', true);
                }
                elseif($_xpiFile != null && $_value == $_addonManifest['addon']['release']) {
                    $_addonManifest['xpinstall'][$_value] = $_xpiFile['xpinstall'];
                    
                    // We want the raw RDF array for the release xpi
                    $_addonReleaseRDF = $_xpiFile['rdf'];
                }
                elseif ($_xpiFile != null) {
                    $_addonManifest['xpinstall'][$_value] = $_xpiFile;
                }
                else {
                    continue;
                }
            }
            
            if ($_addonManifest['xpinstall'] == null) {
                return $this->funcAddonError($_addonSlug, 'error',
                    'The XPInstall key is null for some reason', true);
            }
            
            // Reverse sort the xpinstall keys by install.rdf modified time
            uasort($_addonManifest['xpinstall'], function ($_xpi1, $_xpi2) {
                return $_xpi2['mtime'] <=> $_xpi1['mtime'];
            });

            // Process phoebus.content or set longDescription to shortDescription
            if ($_arrayPhoebusFiles['content']['exists'] == true) {
                $_addonManifest['metadata']['longDescription'] =
                    $this->funcProcessContent($_arrayPhoebusFiles['content']['file']);
            }
            else {
                $_addonManifest['metadata']['longDescription'] = null;
            }
            
            // Process phoebus.license
            $_addonManifestLicense = $this->funcProcessLicense($_addonManifest, $_arrayPhoebusFiles);
            
            if ($_addonManifestLicense != null) {
                $_addonManifest = $_addonManifestLicense;
                unset($_addonManifestLicense);
            }
            else {
                return $this->funcAddonError($_addonSlug, 'error',
                    'Could not process license', true);
            }
            
            // Fixup manifest key values for changes or poor choices.
            $_addonManifest = $this->funcFixupManifest($_addonManifest, $_addonReleaseRDF);

            // Assign basePath and baseURL and site URL
            $_addonManifest['addon']['basePath'] = $_arrayPhoebusFiles['basePath'];
            $_addonManifest['addon']['baseURL'] = 'http://' . $GLOBALS['strApplicationURL'] . '/?component=download&version=latest&id=';
            $_addonManifest['metadata']['url'] = '/addon/' . $_addonSlug . '/';

            // Assign the errors array to the manifest
            if ($_addonManifest != null) {
                $_addonManifest['phoebus']['debug'] = $this->addonErrors;
            }
            
            // Flatten Data structure
            $_addonManifestFlatten = array_merge($_addonManifest['addon'], $_addonManifest['metadata']);
            $_addonManifestFlatten['phoebus'] = $_addonManifest['phoebus'];
            $_addonManifestFlatten['xpinstall'] = $_addonManifest['xpinstall'];
            $_addonManifest = $_addonManifestFlatten;
        }

        // We are supposed to regenerate the shadow file
        if ($_boolRegenerate == true) {
            $_addonManifest = $this->funcRegenerate($_addonManifest, $_arrayPhoebusFiles);
        }

        // On the fly processing that shouldn't be cached
        $_arrayPhoebusFiles['defaultPath'] =
            substr(str_replace($_addonSlug, 'default', $_arrayPhoebusFiles['basePath']), 1);

        if ($_arrayPhoebusFiles['icon'] == true) {
            $_addonManifest['icon'] =
                substr($_arrayPhoebusFiles['basePath'] . 'icon.png', 1);
        }
        else {
            $_addonManifest['icon'] =
                $_arrayPhoebusFiles['defaultPath'] . 'icon.png';
        }
        
        if ($_arrayPhoebusFiles['preview'] == true) {
            $_addonManifest['preview'] =
                substr($_arrayPhoebusFiles['basePath'] . 'preview.png', 1);
            $_addonManifest['hasPreview'] = true;
        }
        else {
            $_addonManifest['preview'] =
                $_arrayPhoebusFiles['defaultPath'] . 'preview.png';
            $_addonManifest['hasPreview'] = false;
        }

        // Return the manifest or null
        return $_addonManifest;
    }

    // ------------------------------------------------------------------------

    // Get Phoebus files and return array
    private function funcGetFiles($_addonSlug) {
        // Define locations for files
        $_strObjDirDatastoreBasePath = $GLOBALS['strObjDirPath'] . 'shadow/addons/';
        $_strDatastoreBasePath = $GLOBALS['strApplicationDatastore'] . 'addons/';
        $_addonBasePath = $_strDatastoreBasePath . $_addonSlug . '/';

        // Define which files we are interested in and determin their existance
        $_arrayPhoebusFiles = array(
            'manifest' => array(
                'file' => $_addonBasePath . 'phoebus.manifest',
                'exists' => file_exists($_addonBasePath . 'phoebus.manifest'),
                'hash' => null
            ),
            'content' => array(
                'file' => $_addonBasePath . 'phoebus.content',
                'exists' => file_exists($_addonBasePath . 'phoebus.content'),
                'hash' => null
            ),
            'license' => array(
                'file' => $_addonBasePath . 'phoebus.license',
                'exists' => file_exists($_addonBasePath . 'phoebus.license'),
                'hash' => null
            ),
            'shadow' => array(
                'file' => $_strObjDirDatastoreBasePath . $_addonSlug. '.json',
                'exists' => file_exists($_strObjDirDatastoreBasePath . $_addonSlug. '.json'),
            ),
            'temp' => array(
                'file' => $_strObjDirDatastoreBasePath . $_addonSlug. '.temp',
                'exists' => file_exists($_strObjDirDatastoreBasePath . $_addonSlug. '.temp'),
            ),
            'icon' => file_exists($_addonBasePath . '/icon.png'),
            'preview' => file_exists($_addonBasePath . '/preview.png'),
            'xpinstall' => array(
                'files' => null,
                'count' => null
            ),
            'basePath' => $_addonBasePath
        );
        
        // Iterate through the files and generate a hash where nessisary
        foreach ($_arrayPhoebusFiles as $_key => $_value) {
            if (($_key == 'content' || $_key == 'manifest' || $_key == 'license')
                && $_value['exists'] == true) {
                $_arrayPhoebusFiles[$_key]['hash'] = hash_file('crc32b', $_value['file']);
            }
        }
        
        // Interate through XPI files in datastore
        foreach (glob($_addonBasePath . '*.xpi') as $_xpiFile) {
            $_arrayPhoebusFiles['xpinstall']['files'][] = basename($_xpiFile);
        }
        
        // Count XPI Files
        $_arrayPhoebusFiles['xpinstall']['count'] = count($_arrayPhoebusFiles['xpinstall']['files']);
    
        return $_arrayPhoebusFiles;
    }

    // ------------------------------------------------------------------------

    // Reads shadow json and returns add-on manifest
    private function funcReadJSON($_phoebusFile) {
        $_jsonFile = file_get_contents($_phoebusFile);
        $_arrayJSON = json_decode($_jsonFile, true);
        
        return $_arrayJSON;
    }

    // ------------------------------------------------------------------------

    // Reads phoebus.manifest and returns add-on manifest
    private function funcReadINI($_phoebusFile) {
        $_addonManifestINI = parse_ini_file($_phoebusFile, true) or null;
        
        // Define base manifest data structure
        $_addonManifestBase = array(
            'phoebus' => array(
                'isShadow' => null,
                'regenerated' => null,
                'manifestHash' => null,
                'contentHash' => null,
                'licenseHash' => null
            ),
            'addon' => array(
                'type' => null, // manifest (required)
                'id' => null, // manifest must match install.rdf (required)
                'release' => null // filename.xpi must exist and be valid (required)
            ),
            'metadata' => array(
                'name' => null, // install.rdf (required)
                'slug' => null, // manifest should match db but unchecked (required)
                'author' => null, // obsolete and invalid (treated as optional but recommended)
                'creator' => null, // install.rdf or default
                'shortDescription' => null, // install.rdf or manifest or default (recommended)
                'homepageURL' => null, // install.rdf (optional)
                'supportURL' => null, // manifest (optional)
                'supportEmail' => null, // manifest (optional)
                'repository' => null, // manifest (optional)
                'license' => null, // manifest defaults to generated (optional but recommended)
                'licenseName' => null, // generated
                'licenseDefault' => null, // generated
                'licenseURL' => null, // manifest (optional but license must be set to 'custom')
                'licenseText' => null, // generated
                'licence' => null // obsolete and invalid
            ),
            'xpinstall' => null // generated
        );
        
        if ($_addonManifestINI != null) {
            $_addonManifest = array_replace_recursive($_addonManifestBase, $_addonManifestINI);
        }
        else {
            return $this->funcAddonError($_phoebusFile, 'error',
                'Could not parse ini file', true);
        }
        
        // Let's do some sanity checks
        if (funcCheckVar($_addonManifest['addon']['type']) == null ||
            funcCheckVar($_addonManifest['addon']['id']) == null ||
            funcCheckVar($_addonManifest['addon']['release']) == null ||
            funcCheckVar($_addonManifest['metadata']['slug']) == null)
        {
            return $this->funcAddonError($_phoebusFile, 'error',
                'Missing minimum required keys in manifest file', true);
        }

        // If any value of a metadata subkey is 'none' replace it with null
        foreach ($_addonManifest['metadata'] as $_metadataKey => $_metadataValue) {
            if ($_metadataValue === 'none') {
                $_addonManifest['metadata'][$_metadataKey] = null;
            }
        }

        // Remove any preset filename.xpi keys from the manifest
        foreach ($_addonManifest as $_key => $_value) {
            if (endsWith($_key, '.xpi')) {
                $this->funcAddonError($_phoebusFile, 'warning',
                    'Obsolete filename.xpi section - [' . $_key . ']');
                unset($_addonManifest[$_key]);
            }
        }
        
        return $_addonManifest;
    }

    // ------------------------------------------------------------------------

    // Reads and process phoebus.content
    private function funcProcessContent($_phoebusFile) {
        // Read phoebus.content
        $_addonPhoebusContent = file_get_contents($_phoebusFile);
        
        // html encode phoebus.content
        $_addonPhoebusContent = htmlentities($_addonPhoebusContent, ENT_XHTML);

        // Replace new lines with <br />
        $_addonPhoebusContent = nl2br($_addonPhoebusContent, true);

        // create an array that contains the strs to pseudo-bbcode to real html
        $_arrayPhoebusCode = array(
            'simple' => array(
                '[b]' => '<strong>',
                '[/b]' => '</strong>',
                '[i]' => '<em>',
                '[/i]' => '</em>',
                '[u]' => '<u>',
                '[/u]' => '</u>',
                '[ul]' => '</p><ul><fixme />',
                '[/ul]' => '</ul><p><fixme />',
                '[ol]' => '</p><ol><fixme />',
                '[/ol]' => '</ol><p><fixme />',
                '[li]' => '<li>',
                '[/li]' => '</li>',
                '[section]' => '</p><h3>',
                '[/section]' => '</h3><p><fixme />'
            ),
            'complex' => array(
                '\<(ul|\/ul|li|\/li|p|\/p)\><br \/>' => '<$1>',
                '\[url=(.*)\](.*)\[\/url\]' => '<a href="$1" target="_blank">$2</a>',
                '\[url\](.*)\[\/url\]' => '<a href="$1" target="_blank">$1</a>',
                '\[img(.*)\](.*)\[\/img\]' => ''
            )
        );

        // str replace pseudo-bbcode with real html
        foreach ($_arrayPhoebusCode['simple'] as $_key => $_value) {
            $_addonPhoebusContent = str_replace($_key, $_value, $_addonPhoebusContent);
        }
        
        // Regex replace pseudo-bbcode with real html
        foreach ($_arrayPhoebusCode['complex'] as $_key => $_value) {
            $_addonPhoebusContent = preg_replace('/' . $_key . '/iU', $_value, $_addonPhoebusContent);
        }

        // Less hacky than what is in funcReadManifest
        // Remove linebreak special cases
        $_addonPhoebusContent = str_replace('<fixme /><br />', '', $_addonPhoebusContent);

        return $_addonPhoebusContent;
    }

    // ------------------------------------------------------------------------

    // Reads install.rdf, returns a filename.xpi key
    private function funcProcessXPInstall($_basePath, $_xpiFile, $_addonID, $_addonRelease) {
        require_once($GLOBALS['arrayModules']['rdf']);

        $_arrayXPInstall = null;
        $_addonXPI = new ZipArchive;
        $_addonRDF = new RdfComponent();
        $_addonInstallRDF = null;
        $_addonInstallStat = null;

        if ($_addonXPI->open($_basePath . $_xpiFile) === true) {
            // Read install.rdf and stat for mtime
            $_addonInstallRDF = $_addonXPI->getFromName('install.rdf');
            $_addonInstallStat = $_addonXPI->statName('install.rdf');
            
            // Close XPI File
            $_addonXPI->close();
            
            // Parse install.rdf
            $_addonInstallRDF = $_addonRDF->parseInstallManifest($_addonInstallRDF);
            
            if (is_string($_addonInstallRDF)) {
                return $this->funcAddonError($_basePath . $_xpiFile, 'error',
                    'XPI File Dropped - Install.rdf Parsing error reported as: ' . $_addonInstallRDF);
            }
            
            if ($_addonID != $_addonInstallRDF['id']) {
                return $this->funcAddonError($_basePath . $_xpiFile, 'warning',
                    'XPI File Dropped - add-on id mismatch');
            }
            
            if (!array_key_exists($GLOBALS['strPaleMoonID'], $_addonInstallRDF['targetApplication'])) {
                return $this->funcAddonError($_basePath . $_xpiFile, 'warning',
                    'XPI File Dropped - install.rdf does not contain a Pale Moon targetApplication');
            }

            if (array_key_exists('updateURL', $_addonInstallRDF)) {
                return $this->funcAddonError($_basePath . $_xpiFile, 'warning',
                    'XPI File Dropped - install.rdf contains an updateURL');
            }

            // Set the keys from install.rdf to the xpinstall array
            $_arrayXPInstall['version'] =
                $_addonInstallRDF['version'];

            $_arrayXPInstall['hash'] =
                hash_file('sha256', $_basePath . $_xpiFile);

            $_arrayXPInstall['mtime'] =
                $_addonInstallStat['mtime'];

            $_arrayXPInstall['date'] =
                date('Y-m-d' ,$_addonInstallStat['mtime']);

            $_arrayXPInstall['prettyDate'] =
                date('F j, Y' ,$_addonInstallStat['mtime']);

            $_arrayXPInstall['targetApplication'] =
                $_addonInstallRDF['targetApplication'];
        }
        else{
            return $this->funcAddonError($_basePath . $_xpiFile, 'error',
                    'Could not open xpi file');
        }

        if($_xpiFile == $_addonRelease) {
            return array('xpinstall' => $_arrayXPInstall, 'rdf' => $_addonInstallRDF);
        }
        else {
            return $_arrayXPInstall;
        }
    }

    // ------------------------------------------------------------------------

    private function funcProcessLicense($_addonManifest, $_arrayPhoebusFiles) {
        // Approved Licenses
        $_arrayLicenses = array(
            'custom' => 'Custom License',
            'Apache-2.0' => 'Apache License 2.0',
            'Apache-1.1' => 'Apache License 1.1',
            'BSD-3-Clause' => 'BSD 3-Clause',
            'BSD-2-Clause' => 'BSD 2-Clause',
            'GPL-3.0' => 'GNU General Public License 3.0',
            'GPL-2.0' => 'GNU General Public License 2.0',
            'LGPL-3.0' => 'GNU Lesser General Public License 3.0',
            'LGPL-2.1' => 'GNU Lesser General Public License 2.1',
            'AGPL-3.0' => 'GNU Affero General Public License v3',
            'MIT' => 'MIT License',
            'MPL-2.0' => 'Mozilla Public License 2.0',
            'MPL-1.1' => 'Mozilla Public License 1.1',
            'PD' => 'Public Domain',
            'COPYRIGHT' => '&copy;' . ' ' . date("Y") . ' - ' . $_addonManifest['metadata']['author']
        );
        
        $_arrayLicenses = array_change_key_case($_arrayLicenses, CASE_LOWER);

        // XXX: licence/license hack...
        if ($_addonManifest['metadata']['licence'] != null) {
            $_addonManifest['metadata']['license'] = $_addonManifest['metadata']['licence'];
            $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                        'Invalid "licence" key detected in manifest file.. unsetting - Please use the "license" key instead');
        }
        
        unset($_addonManifest['metadata']['licence']);
        
        // Set to lowercase
        if ($_addonManifest['metadata']['license'] != null) {
            $_addonManifest['metadata']['license'] = strtolower($_addonManifest['metadata']['license']);
        }

        // phoebus.license trumps all
        // If existant override any license* keys and load the file into the manifest
        if ($_arrayPhoebusFiles['license']['exists'] == true) {
            $_addonManifest['metadata']['license'] = 'custom';
            $_addonManifest['metadata']['licenseName'] =
                $_arrayLicenses[$_addonManifest['metadata']['license']];
            $_addonManifest['metadata']['licenseDefault'] = null;
            $_addonManifest['metadata']['licenseURL'] = null;
            $_addonManifest['metadata']['licenseText'] =
                htmlentities(file_get_contents($_arrayPhoebusFiles['license']['file']), true);

            return $_addonManifest;
        }

        // If license is not set then default to copyright
        if ($_addonManifest['metadata']['license'] == null) {
            $_addonManifest['metadata']['license'] = 'copyright';
            $_addonManifest['metadata']['licenseName'] =
                $_arrayLicenses[$_addonManifest['metadata']['license']];
            $_addonManifest['metadata']['licenseDefault'] = true;
            
            $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                        'No license detected, using default COPYRIGHT license unless otherwise indicated');

            return $_addonManifest;
        }

        if ($_addonManifest['metadata']['license'] != null) {
            if ($_addonManifest['metadata']['license'] == 'custom' &&
                startsWith($_addonManifest['metadata']['licenseURL'], 'http')) {
                $_addonManifest['metadata']['license'] = 'custom';
                $_addonManifest['metadata']['licenseName'] =
                    $_arrayLicenses[$_addonManifest['metadata']['license']];

                return $_addonManifest;
            }
            elseif (array_key_exists($_addonManifest['metadata']['license'], $_arrayLicenses)) {
                $_addonManifest['metadata']['licenseName'] =
                    $_arrayLicenses[$_addonManifest['metadata']['license']];
                $_addonManifest['metadata']['licenseDefault'] = null;
                $_addonManifest['metadata']['licenseURL'] = null;
                $_addonManifest['metadata']['licenseText'] = null;

                return $_addonManifest;
            }
            else {
                $_addonManifest['metadata']['license'] = 'unknown';
                $_addonManifest['metadata']['licenseName'] = 'Unknown License';
                $_addonManifest['metadata']['licenseDefault'] = null;
                $_addonManifest['metadata']['licenseURL'] = null;
                $_addonManifest['metadata']['licenseText'] = null;
                
                $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                        'Unknown License');

                return $_addonManifest;
            }
        }
    }

    // ------------------------------------------------------------------------

    // Fixup manifest values
    private function funcFixupManifest($_addonManifest, $_addonReleaseRDF) {
        // Add-on Name
        if (array_key_exists('name', $_addonReleaseRDF)) {
            $_addonManifest['metadata']['name'] = htmlentities($_addonReleaseRDF['name']['en-US'], ENT_XHTML);
        }
        
        // Add-on Creator
        if ($_addonManifest['metadata']['author'] != null) {
            unset($_addonManifest['metadata']['author']);
            $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                    'Obsolete author key in manifest file - Please use em:creator in install.rdf.. DO NOT EVER REPLACE EXISTING XPI FILES');
        }
        else {
            unset($_addonManifest['metadata']['author']);
        }

        if (array_key_exists('creator', $_addonReleaseRDF)) {
            $_addonManifest['metadata']['creator'] = $_addonReleaseRDF['creator'];
            $_addonManifest['metadata']['creator'] = preg_replace('/\<(.*)\>/iU', '', $_addonManifest['metadata']['creator']);
            $_addonManifest['metadata']['creator'] = htmlentities($_addonManifest['metadata']['creator'], ENT_XHTML);
        }
        else {
            $_addonManifest['metadata']['creator'] = 'The ' . $_addonManifest['metadata']['name'] . ' Team';
            $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                    'Default creator used - Add an em:creator tag to install.rdf when you next release an update.. DO NOT EVER REPLACE EXISTING XPI FILES');
        }

        // Add-on Short Description
        $_TEMPrdfDescription = null;
        $_TEMPshortDescription = null;

        if (array_key_exists('description', $_addonReleaseRDF)) {
            /* $this->funcAddonError($_addonManifest['metadata']['slug'], 'information',
                    'install.rdf description: ' . $_addonReleaseRDF['description']['en-US']); */
            $_TEMPrdfDescription = $_addonReleaseRDF['description']['en-US'];
        }

        if ($_addonManifest['metadata']['shortDescription'] != null) {
            /* $this->funcAddonError($_addonManifest['metadata']['slug'], 'information',
                    'phoebus.manifest shortDescription: ' . $_addonManifest['metadata']['shortDescription']); */
            $_TEMPshortDescription = $_addonManifest['metadata']['shortDescription'];
        }

        if ($_TEMPrdfDescription == $_TEMPshortDescription) {
            $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                    'em:description and shortDescription are identical - You should remove the redundent shortDescription from manifest file');
        }

        if (array_key_exists('description', $_addonReleaseRDF) && $_addonManifest['metadata']['shortDescription'] == null) {
            $_addonManifest['metadata']['shortDescription'] = $_addonReleaseRDF['description']['en-US'];
        }
        elseif ($_addonManifest['metadata']['shortDescription'] == null) {
            $_addonManifest['metadata']['shortDescription'] =
                'The ' . $_addonManifest['metadata']['name'] . ' ' . $_addonManifest['addon']['type'];
            $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                    'Default shortDescription used - add a description tag to install.rdf or shortDescription key to manifest file.. DO NOT EVER REPLACE EXISTING XPI FILES');
        }

        // Add-on Long Description/Content
        if ($_addonManifest['metadata']['longDescription'] == null) {
            $_addonManifest['metadata']['longDescription'] = nl2br(htmlentities($_addonManifest['metadata']['shortDescription'], true), ENT_XHTML);
            $this->funcAddonError($_addonManifest['metadata']['slug'], 'information',
                    'shortDescription used for longDescription');
        }

        // Truncate Add-on Short Description length
        if (strlen($_addonManifest['metadata']['shortDescription']) >= 235) {
            $_addonManifest['metadata']['shortDescription'] =
                substr($_addonManifest['metadata']['shortDescription'], 0, 230) . '&hellip;';
            $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                    'shortDescription is more than 365 characters long, truncated to 260 characters');
            
        }

        // Add-on Homepage URL
        if (array_key_exists('homepageURL', $_addonReleaseRDF)) {
            if (startsWith($_addonReleaseRDF['homepageURL'], 'http')) {
                $_addonManifest['metadata']['homepageURL'] = $_addonReleaseRDF['homepageURL'];
                $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                    'homepageURL value in manifest was overwritten by value in install.rdf');
            }
        }
        
        // Fix homepageURL for things like the repository as a homepage
        if ($_addonManifest['metadata']['homepageURL'] != null) {
            $_arrayHomepageRepos = array('github.com', 'bitbucket.com', 'gitlab.com');
            foreach ($_arrayHomepageRepos as $_value) {
                if (contains($_addonManifest['metadata']['homepageURL'], $_value) == true) {
                    if ($_addonManifest['metadata']['repository'] == null) {
                        $_addonManifest['metadata']['repository'] = $_addonManifest['metadata']['homepageURL'];
                    }

                    $_addonManifest['metadata']['homepageURL'] = null;

                    $this->funcAddonError($_addonManifest['metadata']['slug'], 'warning',
                        'homepageURL reassigned as repository');

                    break;
                }
            }
        }
        
        // Remove unused crap
        unset($_addonManifest['addon']['unstable']);
        
        return $_addonManifest;
    }

    // Regnerates the Shadow file
    private function funcRegenerate($_addonManifest, $_phoebusFiles) {
        // Create the temp file
        $_fileTemp = fopen($_phoebusFiles['temp']['file'], 'w+');
        
        // Assign Phoebus values for storage
        $_addonManifest['phoebus']['isShadow'] = true;
        $_addonManifest['phoebus']['manifestHash'] = $_phoebusFiles['manifest']['hash'];
        $_addonManifest['phoebus']['contentHash'] = $_phoebusFiles['content']['hash'];
        $_addonManifest['phoebus']['licenseHash'] = $_phoebusFiles['license']['hash'];
        $_addonManifest['phoebus']['xpiCount'] = $_phoebusFiles['xpinstall']['count'];
        
        // JSON Encode the array
        $_addonManifestShadow = json_encode($_addonManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Write the JSON to temp file
        fwrite($_fileTemp, $_addonManifestShadow);
        
        // Close the temp file
        fclose($_fileTemp);
        
        // Move (Overwrite) the shadow file with the temp file
        rename($_phoebusFiles['temp']['file'], $_phoebusFiles['shadow']['file']);

        // Set values for return
        $_addonManifest['phoebus']['isShadow'] = false;
        $_addonManifest['phoebus']['regenerated'] = true;
        
        return $_addonManifest;
    }

    // ------------------------------------------------------------------------

    // Clear arrayAddonErrors
    private function funcClearErrors() {
        $this->addonErrors = null;
    }

    // ------------------------------------------------------------------------

    // Generate debug errors relating to classAddonManifest
    private function funcAddonError($_addonSlug, $_type, $_message, $_fatal = null) {
        $this->addonErrors[] = $_addonSlug . ' - ' . $_type . ': ' . $_message;
        
        // Disable Fatal status completely but leave the mechinism in place
        $_fatal = null;

        if ($_fatal == true && $GLOBALS['boolDebugMode'] == true) {
            $_fatalErrors = implode("\n", $this->addonErrors);
            funcError($_fatalErrors);
        }
        else {
            return null;
        }
    }
}

// ============================================================================

?>