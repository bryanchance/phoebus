<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = ['database'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

$moduleDatabase = new classDatabase();

// ====================================================================================================================

// == | Main | ========================================================================================================

$result = array();

$result['single'] =
  $moduleDatabase->query('row', "SELECT * from `addon` WHERE `slug` = ?s", 'abprime');
$result['multiple'] = $moduleDatabase->query('rows',
  "SELECT `username`, `addons` from `user` WHERE `level` >= ?i AND NOT `username` = ?s",
  3, 'mattatobin');

funcError($result, -1);

// ====================================================================================================================

?>