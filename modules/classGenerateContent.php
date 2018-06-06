<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classGenerateContent | ================================================

class classGenerateContent {
  // Skin Templates
  const SITE_TEMPLATE = 'site-template.xhtml';
  const SITE_STYLESHEET = 'site-stylesheet.css';
  const ADDON_CATEGORY_TEMPLATE = 'addon-category.xhtml';
  const OTHER_CATEGORY_TEMPLATE = 'other-category.xhtml';
  const ADDON_PAGE_TEMPLATE = 'addon-page.xhtml';

  // XML/RDF Default Responses
  const XML_TAG = '<?xml version="1.0" encoding="UTF-8"?>';
  const RDF_AUS_BLANK = '<RDF:RDF xmlns:RDF="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:em="http://www.mozilla.org/2004/em-rdf#" />';
  const XML_API_SEARCH_BLANK = '<searchresults total_results="0" />';
  const XML_API_LIST_BLANK = '<addons />';
  const XML_API_ADDON_ERROR = '<error>Add-on not found!</error>';
  
  private $arraySoftwareState;
  private $libSmarty;

  /****************************************************************************
  * Class constructor that sets inital state of things
  ****************************************************************************/
  function __construct($_useSmarty = null) {
    // Assign current software state to a class property by reference
    $this->arraySoftwareState =
      &$GLOBALS['arraySoftwareState'];

    // Set the Application ID
    $this->arraySoftwareState['targetApplicationID'] =
      TARGET_APPLICATION_ID[$this->arraySoftwareState['currentApplication']];

    // ------------------------------------------------------------------------

    // Component Path
    $componentPath =
      dirname(COMPONENTS[$this->arraySoftwareState['requestComponent']]);

    // Component Content Path (for static content)
    $this->arraySoftwareState['componentContentPath'] =
      $componentPath . '/content/';
    
    // Current Skin
    $skin = 'default';

    // SITE component has more than one skin so set it based on
    // current application
    if ($this->arraySoftwareState['requestComponent'] == 'site') {
      $skin = $this->arraySoftwareState['currentApplication'];
    }

    $this->arraySoftwareState['componentSkinPath'] =
      $componentPath . '/skin/' . $skin . '/';
    $this->arraySoftwareState['componentSkinRelPath'] = 
      str_replace(ROOT_PATH, '', $this->arraySoftwareState['componentSkinPath']);

    // ------------------------------------------------------------------------

    if ($_useSmarty) {
      if (!funcCheckModule('smarty')) {
        funcError(
        __CLASS__ . '::' . __FUNCTION__ .
        ' - Smarty has been indicated and is required to be included
        in the global scope'
        );
      }

      // Get smartyDebug HTTP GET Argument
      $this->arraySoftwareState['requestSmartyDebug'] =
        funcHTTPGetValue('smartyDebug');

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

  /****************************************************************************
  * This will generate HTML content for the SITE component using Smarty
  * 
  * @param $_type   template or content file
  * @param $_title  Page title
  * @param $_data   Used if $_type is 'template' to send data to smarty
  ****************************************************************************/
  public function addonSite($_type, $_title, $_data = null) {
    // This function will only serve the SITE component
    if ($this->arraySoftwareState['requestComponent'] != 'site' ||
        !funcCheckModule('smarty') || !$this->libSmarty) {
      funcError(
        __CLASS__ . '::' . __FUNCTION__ .
        ' - This method only works with the SITE component and requires Smarty'
      );
    }

    // ------------------------------------------------------------------------

    // Read the Site Template
    $template = file_get_contents(
      $this->arraySoftwareState['componentSkinPath'] . self::SITE_TEMPLATE);

    // Read the Site Stylesheet
    $stylesheet = file_get_contents(
      $this->arraySoftwareState['componentSkinPath'] . self::SITE_STYLESHEET);

    // ------------------------------------------------------------------------

    switch ($_type) {
      case 'addon-page':
      case 'addon-releases':
      case 'addon-license':
        $content = file_get_contents(
          $this->arraySoftwareState['componentSkinPath'] . self::ADDON_PAGE_TEMPLATE
        );
        break;
      case 'cat-all-extensions':
      case 'cat-extensions':
      case 'cat-themes':
      case 'search':
        $content = file_get_contents(
          $this->arraySoftwareState['componentSkinPath'] . self::ADDON_CATEGORY_TEMPLATE
        );
        break;
      case 'language-pack':
      case 'cat-search-plugins':
        $content = file_get_contents(
          $this->arraySoftwareState['componentSkinPath'] . self::OTHER_CATEGORY_TEMPLATE
        );
        break;
      default:
        if (file_exists($this->arraySoftwareState['componentContentPath'] . $_type)) {
          $content = file_get_contents(
            $this->arraySoftwareState['componentContentPath'] . $_type
          );
        }
        else {
          funcError('Unkown template or content');
        }
    }

    // ------------------------------------------------------------------------

    // Build the final template
    $finalTemplate = str_replace(
      '{%SITE_STYLESHEET}',
      $stylesheet,
      str_replace(
        '{%PAGE_CONTENT}',
        $content,
        $template
      )
    );

    // ------------------------------------------------------------------------

    // Assign Data to Smarty
    $this->libSmarty->assign('APPLICATION_DEBUG', $this->arraySoftwareState['debugMode']);
    $this->libSmarty->assign('SITE_DOMAIN', '//' . $this->arraySoftwareState['currentDomain']);
    $this->libSmarty->assign('PAGE_TITLE', $_title);
    $this->libSmarty->assign('PAGE_PATH', $this->arraySoftwareState['requestPath']);
    $this->libSmarty->assign('BASE_PATH', $this->arraySoftwareState['componentSkinRelPath']);
    $this->libSmarty->assign('PHOEBUS_VERSION', SOFTWARE_VERSION);
    $this->libSmarty->assign('SITE_NAME', $this->arraySoftwareState['currentName']);
    $this->libSmarty->assign('SEARCH_TERMS', $this->arraySoftwareState['requestSearchTerms']);
    $this->libSmarty->assign('APPLICATION_ID', $this->arraySoftwareState['targetApplicationID']);
    $this->libSmarty->assign('PAGE_TYPE', $_type);
    $this->libSmarty->assign('PAGE_DATA', $_data);

    // Send html header
    funcSendHeader('html');
    
    // Send the final template to smarty and output
    $this->libSmarty->display('string:' . $finalTemplate);
    
    // We're done here
    exit();
  }

  /****************************************************************************
  * This will generate RDF content for the Add-on Update Service
  * 
  * @param $addonManifest   Add-on Manifest data structure
  ****************************************************************************/
  public function addonUpdateService($addonManifest = null) {
    if ($this->arraySoftwareState['requestComponent'] != 'aus') {
      funcError(
        __CLASS__ . '::' . __FUNCTION__ .
        ' - This method is designed to work with the AUS component only'
      );
    }

    if (!$addonManifest) {
      // Send XML header
      funcSendHeader('xml');

      // Print XML Tag and Empty RDF Response
      print(self::XML_TAG . NEW_LINE . self::RDF_AUS_BLANK);

      // We're done here
      exit();
    }

    $updateRDF = file_get_contents(
      $this->arraySoftwareState['componentContentPath'] . 'update.rdf'
    );

    $addonXPInstall =
      $addonManifest['xpinstall'][$addonManifest['releaseXPI']];
    $addonTargetApplication =
      $addonXPInstall['targetApplication'][$this->arraySoftwareState['targetApplicationID']];
    
    $arrayFilterSubstitute = array(
      '{%ADDON_TYPE}'       => $addonManifest['type'],
      '{%ADDON_ID}'         => $addonManifest['id'],
      '{%ADDON_VERSION}'    => $addonXPInstall['version'],
      '{%APPLICATION_ID}'   => $this->arraySoftwareState['targetApplicationID'],
      '{%ADDON_MINVERSION}' => $addonTargetApplication['minVersion'],
      '{%ADDON_MAXVERSION}' => $addonTargetApplication['maxVersion'],
      '{%ADDON_XPI}'        => $addonManifest['baseURL'] . $addonManifest['id'],
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

  /****************************************************************************
  * This will generate XML content for Add-ons Manager Search Results
  * 
  * @param $searchManifest    Search Result Manifest
  ****************************************************************************/
  public function amSearch($searchManifest = null) {
    if ($this->arraySoftwareState['requestComponent'] != 'api') {
      funcError(
        __CLASS__ . '::' . __FUNCTION__ .
        ' - This method is designed to work with the api component only'
      );
    }

    if (!$searchManifest) {
      // Send XML header
      funcSendHeader('xml');

      // Print XML Tag and Empty RDF Response
      print(self::XML_TAG . NEW_LINE . self::XML_API_SEARCH_BLANK);

      // We're done here
      exit();
    }

    $addonXML = file_get_contents(
      $this->arraySoftwareState['componentContentPath'] . 'addon.xml'
    );

    $intResultCount = count($searchManifest);

    $searchXML =
      self::XML_TAG . NEW_LINE .
      '<searchresults total_results="' . $intResultCount .'">' . NEW_LINE;
    
    foreach ($searchManifest as $_value) {     
      $_addonXML = $addonXML;
      $_addonType = null;

      $_addonXPInstall =
        $_value['xpinstall'][$_value['releaseXPI']];
      $_addonTargetApplication =
        $_value['targetApplication'][$this->arraySoftwareState['targetApplicationID']];

      switch ($_value['type']) {
        case 'extension':
          $_addonType = 1;
          break;
        case 'theme':
          $_addonType = 2;
          break;
      }        

      $_arrayFilterSubstitute = array(
        '{%ADDON_TYPE}'             => $_addonType,
        '{%ADDON_ID}'               => $_value['id'],
        '{%ADDON_VERSION}'          => $_addonXPInstall['version'],
        '{%ADDON_MTIME}'            => $_addonXPInstall['mtime'],
        '{%ADDON_NAME}'             => $_value['name'],
        '{%ADDON_CREATOR}'          => $_value['creator'],
        '{%ADDON_CREATORURL}'       => 'about:blank',
        '{%ADDON_SHORTDESCRIPTION}' => $_value['shortDescription'],
        '{%ADDON_ICON}'             => $_value['icon'],
        '{%ADDON_HOMEPAGEURL}'      => $_value['homepageURL'] || '',
        '{%APPLICATION_ID}'         => $this->arraySoftwareState['targetApplicationID'],
        '{%ADDON_MINVERSION}'       => $_addonTargetApplication['minVersion'],
        '{%ADDON_MAXVERSION}'       => $_addonTargetApplication['maxVersion'],
        '{%ADDON_XPI}'              => $_value['baseURL'] . $_value['id']
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

  /****************************************************************************
  * Temporary test function .. outputs arraySoftwareState
  ****************************************************************************/
  public function test() {
    ksort($this->arraySoftwareState);
    funcError(array($this->arraySoftwareState, $this->libSmarty), 1);
  }
}

// ============================================================================

?>