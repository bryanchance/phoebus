<?php
// == | classAddonManifest | ==================================================

class classLangPacks {
    // We want a prop that warning and error messages can be sent to
    public $addonErrors;

    // ------------------------------------------------------------------------

    public function funcGetLanguagePacks($_processOnly = null) {
        $_boolProcess = null;
        $_boolRegenerate = null;

        // Define locations for files
        $_strDatastoreBasePath = $GLOBALS['strApplicationDatastore'] . 'langpacks/';
        $_strPaleMoonLangPacksPath = $_strDatastoreBasePath . 'palemoon/';
        $_strPaleMoonStagePath = $_strDatastoreBasePath . 'palemoon-stage/';
        $_strPaleMoonOldPath = $_strDatastoreBasePath . 'palemoon-' . time() . '/';
        
        
        $_strPaleMoonManifest = 'palemoon.json';
        $_strPaleMoonTemp = 'palemoon.temp';

        $_arrayLangPacks = null;

        // Decide if LangPacks need to be regenerated
        if ($_processOnly == null) {
            if (!file_exists($_strDatastoreBasePath . $_strPaleMoonManifest) &&
                !file_exists($_strDatastoreBasePath . $_strPaleMoonTemp)) {
                $_boolProcess = true;
                $_boolRegenerate = true;
                // Lock processing
                $_fileTemp = fopen($_strDatastoreBasePath . $_strPaleMoonTemp, 'w');
                fclose($_fileTemp);
            }
            elseif (file_exists($_strDatastoreBasePath . $_strPaleMoonManifest)) {
                $_arrayLangPacks = $this->funcReadJSON($_strDatastoreBasePath . $_strPaleMoonManifest);
                
                if ($_arrayLangPacks != null) {
                    return $this->funcFlatten($_arrayLangPacks);
                }
                else {
                    return array();
                }
            }
            else {
                return array();
            }
        }
        else {
            $_boolProcess = true;
        }
        
        // Process langpacks
        if ($_boolProcess == true) {
            $_arrayLangPackNames = array(
                'bg' => 'Bulgarian',
                'cs' => 'Czech',
                'de' => 'German',
                'en-GB' => 'English (United Kingdom)',
                'es-AR' => 'Spanish (Argentina)',
                'es-ES' => 'Spanish (Spain)',
                'es-MX' => 'Spanish (Mexico)',
                'el' => 'Greek',
                'fr' => 'French',
                'hu' => 'Hungarian',
                'it' => 'Italian',
                'ja' => 'Japanese',
                'ko' => 'Korean',
                'nl' => 'Dutch (Netherlands)',
                'pl' => 'Polish',
                'pt-BR' => 'Portuguese (Brazil)',
                'pt-PT' => 'Portuguese (Portugal)',
                'ru' => 'Russian',
                'sv-SE' => 'Swedish (Sweden)',
                'tr' => 'Turkish',
                'uk' => 'Ukrainian',
                'zh-CN' => 'Chinese (Simplified)'
            );
        
            $_arrayLangPackFiles = glob($_strPaleMoonStagePath . '*.xpi');
            
            foreach ($_arrayLangPackFiles as $_value) {
                $_langPack = $this->funcProcessXPInstall($_value, $_strPaleMoonLangPacksPath);

                if ($_langPack != null) {
                    $_langPackID = $_langPack['addon']['id'];
                    $_arrayLangPacks[$_langPackID] = $_langPack;

                    // Assign Add-ons Site name
                    if (array_key_exists($_langPack['metadata']['slug'], $_arrayLangPackNames)) {
                        $_arrayLangPacks[$_langPackID]['metadata']['rdfName'] = 
                            $_langPack['metadata']['name'];

                        $_arrayLangPacks[$_langPackID]['metadata']['name'] =
                            $_arrayLangPackNames[$_langPack['metadata']['slug']];
                    }
                }
                else {
                    continue;
                }
            }
            
            if ($_boolRegenerate == true) {
                $_strLangPackJSON = json_encode($_arrayLangPacks, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                // Write the array to the temp file
                $_fileTemp = fopen($_strDatastoreBasePath . $_strPaleMoonTemp, 'w+');
                fwrite($_fileTemp, $_strLangPackJSON);
                fclose($_fileTemp);

                // Rename the current langpack directory to {application}-epoch
                rename($_strPaleMoonLangPacksPath, $_strPaleMoonOldPath);

                // Rename {application}-stage to {application}
                rename($_strPaleMoonStagePath, $_strPaleMoonLangPacksPath);
                
                // Create a new empty {application}-stage directory
                mkdir($_strPaleMoonStagePath);

                // Rename the temp file to the manifest file
                rename($_strDatastoreBasePath . $_strPaleMoonTemp, $_strDatastoreBasePath . $_strPaleMoonManifest);
            };

            return $this->funcFlatten($_arrayLangPacks);
        }
    }

    // ------------------------------------------------------------------------

    // Reads shadow json and returns add-on manifest
    private function funcReadJSON($_phoebusFile) {
        $_jsonFile = file_get_contents($_phoebusFile);
        $_arrayJSON = json_decode($_jsonFile, true);
        
        return $_arrayJSON;
    }

    // ------------------------------------------------------------------------

// Reads install.rdf, returns a filename.xpi key
    private function funcProcessXPInstall($_xpiFile, $_basePath) {
        require_once($GLOBALS['arrayModules']['rdf']);

        $_arrayXPInstall = null;
        $_addonXPI = new ZipArchive;
        $_addonRDF = new RdfComponent();
        $_addonInstallRDF = null;
        $_addonInstallStat = null;

        if ($_addonXPI->open($_xpiFile) === true) {
            // Read install.rdf and stat for mtime
            $_addonInstallRDF = $_addonXPI->getFromName('install.rdf');
            $_addonInstallStat = $_addonXPI->statName('install.rdf');
            
            // Close XPI File
            $_addonXPI->close();
            
            // Parse install.rdf
            $_addonInstallRDF = $_addonRDF->parseInstallManifest($_addonInstallRDF);

            if (!array_key_exists($GLOBALS['strPaleMoonID'], $_addonInstallRDF['targetApplication'])) {
                return $this->funcAddonError($_xpiFile, 'warning',
                    'XPI File Dropped - install.rdf does not contain a Pale Moon targetApplication');
            }

            // Create a fairly complete manifest for each langpack
            
            $_arrayXPInstall = array(
                'addon' => array(
                    'type' => 'item',
                    'id' => $_addonInstallRDF['id'],
                    'release' => basename($_xpiFile),
                    'basePath' => $_basePath,
                    'baseURL' => 'http://' . $GLOBALS['strApplicationURL'] . '/?component=download&version=latest&id=',
                ),
                'metadata' => array(
                    'slug' => substr(basename($_xpiFile), 0, -4),
                    'name' => $_addonInstallRDF['name']['en-US']
                ),
                'xpinstall' => array(
                    basename($_xpiFile) => array(
                        'version' => $_addonInstallRDF['version'],
                        'minAppVersion' => $_addonInstallRDF['targetApplication'][$GLOBALS['strPaleMoonID']]['minVersion'],
                        'maxAppVersion' => $_addonInstallRDF['targetApplication'][$GLOBALS['strPaleMoonID']]['maxVersion'],
                        'hash' => hash_file('sha256', $_xpiFile)
                    )
                )
            );
        }
        else {
            return $this->funcAddonError($_xpiFile, 'error',
                    'Could not open xpi file');
        }
        
        return $_arrayXPInstall;
    }

    // ------------------------------------------------------------------------

    private function funcFlatten($_langPacks) {
        $_langPacksFlatten = array();
        foreach ($_langPacks as $_key3 => $_value3) {
            $_temp = array_merge($_value3['addon'], $_value3['metadata']);
            $_temp['xpinstall'][$_value3['addon']['release']]['version'] = $_value3['xpinstall'][$_value3['addon']['release']]['version'];
            $_temp['xpinstall'][$_value3['addon']['release']]['hash'] = $_value3['xpinstall'][$_value3['addon']['release']]['hash'];
            $_temp['xpinstall'][$_value3['addon']['release']]['targetApplication'][$GLOBALS['strPaleMoonID']]['minVersion'] = $_value3['xpinstall'][$_value3['addon']['release']]['minAppVersion'];
            $_temp['xpinstall'][$_value3['addon']['release']]['targetApplication'][$GLOBALS['strPaleMoonID']]['maxVersion'] = $_value3['xpinstall'][$_value3['addon']['release']]['maxAppVersion'];
            
            $_langPacksFlatten[$_key3] = $_temp;
        }
        
        return $_langPacksFlatten;
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
