<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classGeneratePage | ===================================================

class classGeneratePage {
  // Skin Templates
  const SITE_TEMPLATE = 'site-template.xhtml';
  const SITE_STYLESHEET = 'site-stylesheet.css';
  const ADDON_CATEGORY_TEMPLATE = 'addon-category.xhtml';
  const OTHER_CATEGORY_TEMPLATE = 'other-category.xhtml';
  const ADDON_PAGE_TEMPLATE = 'addon-page.xhtml';
  
  private $arraySoftwareState;
  private $libSmarty;

  /****************************************************************************
  * Class constructor that sets inital state of things
  ****************************************************************************/
  function __construct() {
    // Assign current software state to a class property by reference
    $this->arraySoftwareState = &$GLOBALS['arraySoftwareState'];
    
    // Get smartyDebug HTTP GET Argument
    $this->arraySoftwareState['requestSmartyDebug'] = funcHTTPGetValue('smartyDebug');

    // ------------------------------------------------------------------------

    // Component Path
    $componentPath = dirname(COMPONENTS[$this->arraySoftwareState['requestComponent']]);

    // Component Content Path (for static content)
    $this->arraySoftwareState['smartyContentPath'] = $componentPath . '/content/';
    
    // Current Skin
    $skin = 'default';

    // SITE component has more than one skin so set it based on
    // current application
    if ($this->arraySoftwareState['requestComponent'] == 'site') {
      $skin = $this->arraySoftwareState['currentApplication'];
    }

    $this->arraySoftwareState['smartySkinPath'] = $componentPath . '/skin/' . $skin . '/';
    $this->arraySoftwareState['smartySkinRelPath'] = 
      str_replace(ROOT_PATH, '', $this->arraySoftwareState['smartySkinPath']);

    // ------------------------------------------------------------------------

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
    $smartyObjPath = ROOT_PATH . OBJ_RELPATH . '/amarty/' .
                     $this->arraySoftwareState['requestComponent'] .
                     '-' . $skin . '/';

    $this->libSmarty->setCacheDir($smartyObjPath . 'cache');
    $this->libSmarty->setCompileDir($smartyObjPath . 'compile');
    $this->libSmarty->setConfigDir($smartyObjPath . 'config');
    $this->libSmarty->addPluginsDir($smartyObjPath . 'plugins');
    $this->libSmarty->setTemplateDir($smartyObjPath . 'template');
  }

  /****************************************************************************
  * Public method that will control the page generation and send it to smarty
  *
  * @param $_type   Content type 'content' or 'template'    
  * @param $_title  Page title
  * @param $_flag   Depends on $_type
                    $_type = 'template' then this controls WHICH template
                    $_type = 'content' then this is the content file to open
  * @param $_data   Used if $_type is 'template' to send data to smarty
  ****************************************************************************/
  public function output($_type, $_title, $_flag, $_data = null) {
    // Get template
    if ($this->arraySoftwareState['requestComponent'] == 'site') {
      $template = $this->funcGetSiteTemplate($_type, $_flag);
    }
    else {
      funcError(__FUNCTION__ . ': Non-SITE component page generation is not yet implimented');
    }

    // Assign Data to Smarty (In the old way)
    $this->libSmarty->assign('APPLICATION_DEBUG', $this->arraySoftwareState['debugMode']);
    $this->libSmarty->assign('SITE_NAME', $this->arraySoftwareState['currentName']);
    $this->libSmarty->assign('SITE_DOMAIN', '//' . $this->arraySoftwareState['currentDomain']);
    $this->libSmarty->assign('PAGE_TITLE', $_title);
    $this->libSmarty->assign('PAGE_PATH', $this->arraySoftwareState['requestPath']);
    $this->libSmarty->assign('BASE_PATH', $this->arraySoftwareState['smartySkinRelPath']);
    $this->libSmarty->assign('PHOEBUS_VERSION', SOFTWARE_VERSION);

    // Templates need to be aware of the arbitrary flag
    if ($_type == 'template') {
      $this->libSmarty->assign('PAGE_TYPE', $_flag);
    }
    
    // If we have data we should assign it
    if ($_data) {
      $this->libSmarty->assign('PAGE_TYPE', $_data);
    }

    // Send html header
    funcSendHeader('html');
    
    // Send the final template to smarty and output
    $this->libSmarty->display('string:' . $template);
    
    // We're done here
    exit();
  }

  /****************************************************************************
  * Private method that will read the various template/content files
  *
  * @param $_type  Content type 'content' or 'template'
  * @param $_flag   Depends on $_type
                    $_type = 'template' then this controls WHICH template
                    $_type = 'content' then this is the content file to open
  * @returns       Final template as string
  ****************************************************************************/
  private function funcGetSiteTemplate($_type, $_flag) {
    if ($this->arraySoftwareState['requestComponent'] != 'site') {
      funcError(__FUNCTION__ . ': This function only works with the SITE component');
    }

    $template = file_get_contents(
      $this->arraySoftwareState['smartySkinPath'] . self::SITE_TEMPLATE);
    $stylesheet = file_get_contents(
      $this->arraySoftwareState['smartySkinPath'] . self::SITE_STYLESHEET);

    if ($_type = 'content') {
      $content = file_get_contents(
        $this->arraySoftwareState['smartyContentPath'] . $_flag);
    }
    else {
      switch ($_flag) {
        case 'addon-page':
        case 'addon-releases':
        case 'addon-licenses':
          $content = file_get_contents(
            $this->arraySoftwareState['smartySkinPath'] . self::ADDON_PAGE_TEMPLATE);
          break;
        case 'cat-extensions':
        case 'cat-themes':
        case 'cat-search':
          $content = file_get_contents(
            $this->arraySoftwareState['smartySkinPath'] . self::ADDON_CATEGORY_TEMPLATE);
          break;
        case 'language-pack':
        case 'search-plugin':
          $content = file_get_contents(
            $this->arraySoftwareState['smartySkinPath'] . self::OTHER_CATEGORY_TEMPLATE);
          break;
        default:
          funcError('Unkown template type');
      }
    }

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
    
    return $finalTemplate;
  }

  /****************************************************************************
  * Temporary test function .. outputs arraySoftwareState
  ****************************************************************************/
  public function test() {
    //funcError($this->arraySoftwareState, 1);
    
    $this->output('content', 'Your browser, Your way!', 'palemoon-frontpage.xhtml');
  }
}

// ============================================================================

?>