<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules
$arrayIncludes = ['database', 'readManifest'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$moduleReadManifest = new classReadManifest();

$queryInsert = 'REPLACE INTO addon SET ?u';
$arrayInsert = ['slug' => 'abprime', 'name' => 'ABShit', 'xpinstall' => "' DROP * FROM addon"];

$moduleDatabase->query('normal', $queryInsert, $arrayInsert);

$queryAddon = "SELECT addon.*
              FROM `addon`
              WHERE `slug` = ?s;

$queryResult = $moduleDatabase->query('row', $queryAddon, 'abprime');

funcError($queryResult, 98);

// ====================================================================================================================

?>