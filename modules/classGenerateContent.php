<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// Include required libraries
require_once(LIBRARIES['smarty']);

// ====================================================================================================================

// == | classGenerateContent | ========================================================================================

class classGenerateContent {
  // Skin Templates
  const SITE_TEMPLATE = 'site-template.xhtml';
  const SITE_STYLESHEET = 'site-stylesheet.css';
  const ADDON_CATEGORY_TEMPLATE = 'addon-category.xhtml';
  const EXTENSION_CATEGORY_TEMPLATE = 'extension-category.xhtml';
  const LANGPACK_CATEGORY_TEMPLATE = 'langpack-category.xhtml';
  const PERSONA_CATEGORY_TEMPLATE = 'persona-category.xhtml';
  const SEARCHPLUGIN_CATEGORY_TEMPLATE = 'searchplugin-category.xhtml';
  const ADDON_PAGE_TEMPLATE = 'addon-page.xhtml';
  const ADDON_LICENSE_TEMPLATE = 'addon-license.xhtml';

  // XML/RDF Default Responses
  const XML_TAG = '<?xml version="1.0" encoding="UTF-8"?>';
  const RDF_AUS_BLANK = '<RDF:RDF xmlns:RDF="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:em="http://www.mozilla.org/2004/em-rdf#" />';
  const XML_API_SEARCH_BLANK = '<searchresults total_results="0" />';
  const XML_API_LIST_BLANK = '<addons />';
  const XML_API_ADDON_ERROR = '<error>Add-on not found!</error>';
  
  private $arraySoftwareState;
  private $libSmarty;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct($aUseSmarty = null) {
    // Assign current software state to a class property by reference
    $this->arraySoftwareState = &$GLOBALS['arraySoftwareState'];

    // Set the Application ID
    $this->arraySoftwareState['targetApplicationID'] =
      TARGET_APPLICATION_ID[$this->arraySoftwareState['currentApplication']];

    // ----------------------------------------------------------------------------------------------------------------

    // Component Path
    $componentPath = dirname(COMPONENTS[$this->arraySoftwareState['requestComponent']]);

    // Component Content Path (for static content)
    $this->arraySoftwareState['componentContentPath'] = $componentPath . '/content/';
    
    // Current Skin
    $skin = 'default';

    // SITE component has more than one skin so set it based on
    // current application
    if ($this->arraySoftwareState['requestComponent'] == 'site') {
      $skin = $this->arraySoftwareState['currentApplication'];
    }

    $this->arraySoftwareState['componentSkinPath'] = $componentPath . '/skin/' . $skin . '/';
    $this->arraySoftwareState['componentSkinRelPath'] = 
      str_replace(ROOT_PATH, '', $this->arraySoftwareState['componentSkinPath']);

    // ----------------------------------------------------------------------------------------------------------------

    if ($aUseSmarty) {
      // Get smartyDebug HTTP GET Argument
      $this->arraySoftwareState['requestSmartyDebug'] = funcUnifiedVariable('get', 'smartyDebug');

      // Initalize Smarty
      $this->libSmarty = new Smarty();

      // Set Smarty Caching
      $this->libSmarty->caching = 0;

      // Set Smarty Debug
      $this->libSmarty->debugging = false;

      if ($this->arraySoftwareState['requestSmartyDebug']) {
        $this->libSmarty->debugging = $this->arraySoftwareState['debugMode'];
      }

      // Set Smarty Paths
      $smartyObjPath = ROOT_PATH . OBJ_RELPATH . '/smarty/' .
                       $this->arraySoftwareState['requestComponent'] .
                       '-' . $skin . '/';

      $this->libSmarty->setCacheDir($smartyObjPath . 'cache');
      $this->libSmarty->setCompileDir($smartyObjPath . 'compile');
      $this->libSmarty->setConfigDir($smartyObjPath . 'config');
      $this->libSmarty->addPluginsDir($smartyObjPath . 'plugins');
      $this->libSmarty->setTemplateDir($smartyObjPath . 'template');
    }
  }

  /********************************************************************************************************************
  * This will generate HTML content for the SITE component using Smarty
  * 
  * @param $aType         template or content file
  * @param $aTitle        Page title
  * @param $aData         Used if not null
  * @param $aExtraData    Used if not null
  ********************************************************************************************************************/
  public function addonSite($aType, $aTitle, $aData = null, $aExtraData = null) {
    // This function will only serve the SITE component
    if ($this->arraySoftwareState['requestComponent'] != 'site' || !$this->libSmarty) {
      funcError(
        __CLASS__ . '::' . __FUNCTION__ . ' - This method only works with the SITE component and requires Smarty'
      );
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Read the Site Template
    $template = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::SITE_TEMPLATE);

    // Read the Site Stylesheet
    $stylesheet = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::SITE_STYLESHEET);

    if (!$template || !$stylesheet) {
      funcError('Site Template and/or Stylesheet could not be read or is missing');
    }

    // ----------------------------------------------------------------------------------------------------------------

    switch ($aType) {
      case 'addon-page':
      case 'addon-releases':
        $content = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::ADDON_PAGE_TEMPLATE);
        break;
      case 'addon-license':
        $content = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::ADDON_LICENSE_TEMPLATE);
        break;
      case 'cat-extension-category':
        $content = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::EXTENSION_CATEGORY_TEMPLATE);
        break;
      case 'cat-all-extensions':
      case 'cat-extensions':
      case 'cat-themes':
      case 'search':
        $content = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::ADDON_CATEGORY_TEMPLATE);
        break;
      case 'cat-personas':
        $content = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::PERSONA_CATEGORY_TEMPLATE);
        break;
      case 'cat-language-packs':
        $content = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::LANGPACK_CATEGORY_TEMPLATE);
        break;
      case 'cat-search-plugins':
        $content = @file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::SEARCHPLUGIN_CATEGORY_TEMPLATE);
        break;
      default:
        if (file_exists($this->arraySoftwareState['componentContentPath'] . $aType)) {
          $content = file_get_contents($this->arraySoftwareState['componentContentPath'] . $aType);
        }
        else {
          funcError('Unkown template or content');
        }
    }

    if (!$content) {
      funcError('Content template could not be read or is missing');
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Build the final template
    $finalTemplate = str_replace('{%SITE_STYLESHEET}', $stylesheet,
      str_replace('{%PAGE_CONTENT}', $content, $template)
    );

    // ----------------------------------------------------------------------------------------------------------------

    // Assign Data to Smarty
    $this->libSmarty->assign('APPLICATION_DEBUG', $this->arraySoftwareState['debugMode']);
    $this->libSmarty->assign(
      'SITE_DOMAIN',
      $this->arraySoftwareState['currentScheme'] .
      '://' . $this->arraySoftwareState['currentDomain']
    );
    $this->libSmarty->assign('PAGE_TITLE', $aTitle);
    $this->libSmarty->assign('PAGE_PATH', $this->arraySoftwareState['requestPath']);
    $this->libSmarty->assign('BASE_PATH', $this->arraySoftwareState['componentSkinRelPath']);
    $this->libSmarty->assign('PHOEBUS_VERSION', SOFTWARE_VERSION);
    $this->libSmarty->assign('SITE_NAME', $this->arraySoftwareState['currentName']);
    $this->libSmarty->assign('SEARCH_TERMS', $this->arraySoftwareState['requestSearchTerms']);
    $this->libSmarty->assign('APPLICATION_ID', $this->arraySoftwareState['targetApplicationID']);
    $this->libSmarty->assign('PAGE_TYPE', $aType);
    $this->libSmarty->assign('PAGE_DATA', $aData);
    $this->libSmarty->assign('EXTRA_DATA', $aExtraData);

    // Send html header
    funcSendHeader('html');
    
    // Send the final template to smarty and output
    $this->libSmarty->display('string:' . $finalTemplate);
    
    // We're done here
    exit();
  }

 /********************************************************************************************************************
  * This will generate HTML content for the PANEL component using Smarty
  * 
  * @param $aType         template or content file
  * @param $aTitle        Page title
  * @param $aData         Used if not null
  ********************************************************************************************************************/
  public function addonPanel($aType, $aTitle, $aData = null) {
    // This function will only serve the PANEL component
    if ($this->arraySoftwareState['requestComponent'] != 'panel' || !$this->libSmarty) {
      funcError(
        __CLASS__ . '::' . __FUNCTION__ . ' - This method only works with the PANEL component and requires Smarty'
      );
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Read the Panel Template
    $template = file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::SITE_TEMPLATE);
    // Read the Site Stylesheet
    $stylesheet = file_get_contents($this->arraySoftwareState['componentSkinPath'] . self::SITE_STYLESHEET);

    // ----------------------------------------------------------------------------------------------------------------

    switch ($aType) {
      case 'developer-addons-list':
        $content = file_get_contents($this->arraySoftwareState['componentSkinPath'] . 'developer-addons-list.xhtml');
        break;
      case 'administration-list':
      case 'admin-list-extensions':
      case 'admin-list-externals':
      case 'admin-list-themes':
      case 'admin-list-langpacks':
        $content = file_get_contents($this->arraySoftwareState['componentSkinPath'] . 'administration-list.xhtml');
        break;
      default:
        if (file_exists($this->arraySoftwareState['componentContentPath'] . $aType)) {
          $content = file_get_contents($this->arraySoftwareState['componentContentPath'] . $aType);
        }
        else {
          funcError('Unkown template or content');
        }
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Build the final template
    $finalTemplate = str_replace('{%SITE_STYLESHEET}', $stylesheet,
      str_replace('{%PAGE_CONTENT}', $content, $template)
    );

    // ----------------------------------------------------------------------------------------------------------------

    // Assign Data to Smarty
    $this->libSmarty->assign('APPLICATION_DEBUG', $this->arraySoftwareState['debugMode']);
    $this->libSmarty->assign('SITE_DOMAIN', '//' . $this->arraySoftwareState['currentDomain']);
    $this->libSmarty->assign('PAGE_TITLE', $aTitle);
    $this->libSmarty->assign('PAGE_PATH', $this->arraySoftwareState['requestPath']);
    $this->libSmarty->assign('BASE_PATH', $this->arraySoftwareState['componentSkinRelPath']);
    $this->libSmarty->assign('PHOEBUS_VERSION', SOFTWARE_VERSION);
    $this->libSmarty->assign('APPLICATION_ID', $this->arraySoftwareState['targetApplicationID']);
    $this->libSmarty->assign('PAGE_TYPE', $aType);
    $this->libSmarty->assign('PAGE_DATA', $aData);

    if ($this->arraySoftwareState['authentication']) {
      $this->libSmarty->assign('USER_LEVEL', $this->arraySoftwareState['authentication']['level']);
    }
    else {
      $this->libSmarty->assign('USER_LEVEL', 0);
    }

    // Send html header
    funcSendHeader('html');
    
    // Send the final template to smarty and output
    $this->libSmarty->display('string:' . $finalTemplate);
    
    // We're done here
    exit();
  }

  /********************************************************************************************************************
  * This will generate RDF content for the Add-on Update Service
  * 
  * @param $aAddonManifest   Add-on Manifest data structure
  ********************************************************************************************************************/
  public function addonUpdateService($aAddonManifest = null) {
    if ($this->arraySoftwareState['requestComponent'] != 'aus') {
      funcError(
        __CLASS__ . '::' . __FUNCTION__ . ' - This method is designed to work with the AUS component only'
      );
    }

    if (!$aAddonManifest) {
      // Send XML header
      funcSendHeader('xml');

      // Print XML Tag and Empty RDF Response
      print(self::XML_TAG . NEW_LINE . self::RDF_AUS_BLANK);

      // We're done here
      exit();
    }

    $updateRDF = file_get_contents($this->arraySoftwareState['componentContentPath'] . 'update.rdf');

    $addonXPInstall = $aAddonManifest['xpinstall'][$aAddonManifest['releaseXPI']];
    $addonTargetApplication = $addonXPInstall['targetApplication'][$this->arraySoftwareState['targetApplicationID']];
    
    // Language Packs are an 'item' as far as update.rdf is conserned
    if ($aAddonManifest['type'] == 'langpack') {
      $aAddonManifest['type'] = 'item';
    }
    
    $arrayFilterSubstitute = array(
      '{%ADDON_TYPE}'       => $aAddonManifest['type'],
      '{%ADDON_ID}'         => $aAddonManifest['id'],
      '{%ADDON_VERSION}'    => $addonXPInstall['version'],
      '{%APPLICATION_ID}'   => $this->arraySoftwareState['targetApplicationID'],
      '{%ADDON_MINVERSION}' => $addonTargetApplication['minVersion'],
      '{%ADDON_MAXVERSION}' => $addonTargetApplication['maxVersion'],
      '{%ADDON_XPI}'        => $aAddonManifest['baseURL'] . $aAddonManifest['id'],
      '{%ADDON_HASH}'       => $addonXPInstall['hash']
    );

    foreach ($arrayFilterSubstitute as $_key => $_value) {
      $updateRDF = str_replace($_key, $_value, $updateRDF);
    }

    // Send XML header
    funcSendHeader('xml');

    // Print Update RDF
    print($updateRDF);

    // We're done here
    exit();
  }

  /********************************************************************************************************************
  * This will generate XML content for Add-ons Manager Search Results
  * 
  * @param $aSearchManifest    Search Result Manifest
  ********************************************************************************************************************/
  public function amSearch($aSearchManifest = null) {
    if (!$aSearchManifest) {
      // Send XML header
      funcSendHeader('xml');

      // Print XML Tag and Empty RDF Response
      print(self::XML_TAG . NEW_LINE . self::XML_API_SEARCH_BLANK);

      // We're done here
      exit();
    }

    $addonXML = file_get_contents($this->arraySoftwareState['componentContentPath'] . 'addon.xml');

    $intResultCount = count($aSearchManifest);

    $searchXML = self::XML_TAG . NEW_LINE . '<searchresults total_results="' . $intResultCount .'">' . NEW_LINE;
    
    foreach ($aSearchManifest as $_value) {     
      $_addonXML = $addonXML;
      $_addonType = null;
      
      if (!$_value['homepageURL']) {
        $_addonHomepageURL = '';
      }
      else {
        $_addonHomepageURL = $_value['homepageURL'];
      }

      $_addonXPInstall = $_value['xpinstall'][$_value['releaseXPI']];
      $_addonTargetApplication = $_addonXPInstall['targetApplication'][$this->arraySoftwareState['targetApplicationID']];

      switch ($_value['type']) {
        case 'extension':
          $_addonType = 1;
          break;
        case 'theme':
          $_addonType = 2;
          break;
        case 'langpack':
          $_addonType = 6;
          break;
        default:
          $_addonType = 0;
      }        

      $_arrayFilterSubstitute = array(
        '{%ADDON_TYPE}'         => $_addonType,
        '{%ADDON_ID}'           => $_value['id'],
        '{%ADDON_VERSION}'      => $_addonXPInstall['version'],
        '{%ADDON_EPOCH}'        => $_addonXPInstall['epoch'],
        '{%ADDON_NAME}'         => $_value['name'],
        '{%ADDON_CREATOR}'      => $_value['creator'],
        '{%ADDON_CREATORURL}'   => 'about:blank',
        '{%ADDON_DESCRIPTION}'  => $_value['description'],
        '{%ADDON_URL}'          => 'http://' . $this->arraySoftwareState['currentDomain'] . $_value['url'],
        '{%ADDON_ICON}'         => 'http://' . $this->arraySoftwareState['currentDomain'] . $_value['icon'],
        '{%ADDON_HOMEPAGEURL}'  => $_addonHomepageURL,
        '{%APPLICATION_ID}'     => $this->arraySoftwareState['targetApplicationID'],
        '{%ADDON_MINVERSION}'   => $_addonTargetApplication['minVersion'],
        '{%ADDON_MAXVERSION}'   => $_addonTargetApplication['maxVersion'],
        '{%ADDON_XPI}'          => $_value['baseURL'] . $_value['id']
      );

      foreach ($_arrayFilterSubstitute as $_key => $_value) {
        $_addonXML = str_replace($_key, $_value, $_addonXML);
      }
      
      $searchXML .= $_addonXML . NEW_LINE;
    }

    $searchXML .= '</searchresults>';
    
    // Send XML header
    funcSendHeader('xml');

    // Print Update RDF
    print($searchXML);

    // We're done here
    exit();
  }

  /********************************************************************************************************************
  * Temporary test function .. outputs arraySoftwareState
  ********************************************************************************************************************/
  public function test() {
    ksort($this->arraySoftwareState);
    funcError(array($this->arraySoftwareState, $this->libSmarty), 1);
  }
}

// ====================================================================================================================

?>