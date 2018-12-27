<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Functions | ===================================================================================================

/**********************************************************************************************************************
* Strips path to obtain the slug
*
* @param $_path     $arraySoftwareState['requestPath']
* @param $_prefix   Prefix to strip 
* @returns          slug
***********************************************************************************************************************/
function funcStripPath($_path, $_prefix) {
  return str_replace('/', '', str_replace($_prefix, '', $_path));
}

// ====================================================================================================================

// == | Main | ========================================================================================================

$strComponentPath = dirname(COMPONENTS[$arraySoftwareState['requestComponent']]) . '/';
$strStripPath = funcStripPath($arraySoftwareState['requestPath'], '/special/');
$strSkinPath = $strComponentPath . '/skin/';

// --------------------------------------------------------------------------------------------------------------------

switch ($strStripPath) {
  case 'phpinfo':
    phpinfo();
    break;
  case 'phpvars':
    phpinfo(32);
    break;
  case 'softwareState':
    require_once(MODULES['auth']);
    $moduleAuth->authenticate();
    funcError($arraySoftwareState, 98);
    break;
  case 'validate':
  case 'validator':
    require_once($strComponentPath . 'addonValidator.php');
    break;
  case 'validator2':
    require_once($strComponentPath . 'addonValidator2.php');
    break;
  case 'migrator':
    if (file_exists(ROOT_PATH . '/.migration')) {
      require_once($strComponentPath . 'addonMigrator.php');
    }
    else {
      funcRedirect('/');
    }
    break;
  case 'test':
    if (!$arraySoftwareState['debugMode']) {
      funcRedirect('/');
    }

    $arraySoftwareState['requestTestCase'] = funcUnifiedVariable('get', 'case');

    if ($arraySoftwareState['requestTestCase'] &&
        file_exists($strComponentPath . 'tests/' . $arraySoftwareState['requestTestCase'] . '.php')) {
      require_once($strComponentPath . 'tests/' . $arraySoftwareState['requestTestCase'] . '.php');
    }
    else {
      funcError('Invalid test case');
    }

    exit();
  default:
    funcRedirect('/');
}

// ====================================================================================================================

?>