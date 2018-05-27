<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classGeneratePage | ===================================================

class classGeneratePage {
  const TEMPLATE_FILE = 'template.xhtml';
  const STYLESHEET_FILE = 'stylesheet.css';
  private $arraySoftwareState;
  private $strSkinPath;
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

    // Componet Path
    $componentPath = dirname(COMPONENTS[$this->arraySoftwareState['requestComponent']]);

    // Current Skin
    $skin = 'default';

    // SITE component has more than one skin so set it based on
    // current application
    if ($this->arraySoftwareState['requestComponent'] == 'site') {
      $skin = $this->arraySoftwareState['currentApplication'];
    }

    // Set the skinPath class property
    $this->strSkinPath = $componentPath . '/skin/' . $skin . '/';

    // ------------------------------------------------------------------------

    // Initalize Smarty
    $this->libSmarty = new Smarty();

    // Set Smarty Caching
    $this->libSmarty->caching = 0;

    // Set Smarty Debug
    $this->libSmarty->debug = false;

    if ($this->arraySoftwareState['requestSmartyDebug']) {
      $this->libSmarty->debug = $this->arraySoftwareState['debugMode'];
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

  public function test() {
    funcError(array(
        $this->strSkinPath,
        $this->arraySoftwareState
      ), 1
    );
  }
}

// ============================================================================

?>