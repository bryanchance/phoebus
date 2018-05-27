<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classGeneratePage | ===================================================

class classGeneratePage {
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
    $this->strSkinPath = $componentPath . '/' . $skin . '/';

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
  }

  public function test() {
    funcError(array($this->arraySoftwareState, $this->strSkinPath, $this->libSmarty), 1);
  }
}

// ============================================================================

?>