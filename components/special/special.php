<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | application.php | =====================================================

// This file defines the general entry point of the application. It should not
// contain more than the basic operational logic for defining universal
// variables, components, and modules as well the default component

// ============================================================================

// == | Vars | ================================================================

// Define application paths
$strSpecialComponentsPath = $strRootPath . '/components/special/';

// Define Components
$arraySpecialFunctions = array(
  'phpinfo' => $strSpecialComponentsPath . 'phpInfo.php',
  'validate' => $strSpecialComponentsPath . 'addonValidator.php',
  'manifest-test' => $strSpecialComponentsPath . 'manifestTest.php'
);

// ============================================================================

// == | Main | ================================================================

// URL to Function assignment
if (startsWith($strRequestPath, '/special/')) {
  $strStrippedPath = str_replace('/', '', str_replace('/special/', '', $strRequestPath));
  
  if (array_key_exists($strStrippedPath, $arraySpecialFunctions)) {
    require_once($arraySpecialFunctions[$strStrippedPath]);
  }
  else {
    funcSendHeader('404');
  }
}
else {
  funcSendHeader('404');
}

// ============================================================================
?>