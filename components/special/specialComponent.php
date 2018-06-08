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

/**********************************************************************************************************************
* Sends a 404 error but does it depending on debug mode
*
* @param $_path     $arraySoftwareState['requestPath']
* @param $_prefix   Prefix to strip 
* @returns          slug
***********************************************************************************************************************/
function funcSend404() {
  if (!$GLOBALS['arraySoftwareState']['debugMode']) {
    funcSendHeader('404');
  }
  funcError('404 - Not Found');
}

// ====================================================================================================================

// == | Main | ========================================================================================================

$strComponentPath = dirname(COMPONENTS[$arraySoftwareState['requestComponent']]) . '/';
$strSkinPath = $strComponentPath . '/skin/';

$arraySpecialFunctions = array('validate' => 'addonValidator.php');

// Test authentication
$arraySpecialFunctions['testAuth'] = 'testAuth.php';

// --------------------------------------------------------------------------------------------------------------------

if ($arraySoftwareState['phpRequestURI'] == '/special/phpinfo/') {
  phpinfo();
  exit();
}

$strStripPath = funcStripPath($arraySoftwareState['requestPath'], '/special/');

if (array_key_exists($strStripPath, $arraySpecialFunctions)) {
  require_once($strComponentPath . $arraySpecialFunctions[$strStripPath]);
}
else {
  if (!$arraySoftwareState['debugMode']) {
    funcSendHeader('404');
  }
  funcError('Invalid special function');
}

// ====================================================================================================================

?>