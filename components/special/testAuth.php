<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = array('sql', 'sql-creds', 'auth');
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// ====================================================================================================================

// == | Main | ========================================================================================================

$moduleAuth = new classAuthentication();
$moduleAuth->authenticate();

if ($arraySoftwareState['authentication']) {
  ksort($arraySoftwareState);
  funcError($arraySoftwareState, 1);
}

funcError('Something went terribly wrong');

// ====================================================================================================================

?>