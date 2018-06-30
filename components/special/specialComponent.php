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
$strSkinPath = $strComponentPath . '/skin/';

$arraySpecialFunctions = array(
  'validate' => 'addonValidator.php'
);

// Temporary Test Cases
$arraySpecialFunctions['testAuth'] = 'testAuth.php';
$arraySpecialFunctions['testInstallManifest'] = 'testInstallManifest.php';
$arraySpecialFunctions['testSQL'] = 'testSQL.php';

// --------------------------------------------------------------------------------------------------------------------

if ($arraySoftwareState['requestPath'] == '/special/phpinfo/') {
  phpinfo();
  exit();
}
elseif ($arraySoftwareState['requestPath'] == '/special/phpvars/') {
  phpinfo(32);
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
  //funcError('Invalid special function');
  funcError($arraySoftwareState, 1);
}

// ====================================================================================================================

?>