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

$post = funcUnifiedVariable('get', 'post');

if (!$post) {
  $addonManifest = $moduleReadManifest->getAddonBySlug('abprime', null);
  //funcError($addonManifest, 98);
  funcSendHeader('html');
  $html ='<form id="form1"accept-charset="UTF-8" action="/special/test/?case=metadata&post=1" autocomplete="off" method="POST" target="_blank">
	<input name="name" type="text" value="Frank" /><br /> 
	<textarea cols="30" rows="2" form="form1">Long text.</textarea><br /> 
	<input name="democheckbox" type="checkbox" value="1" /> Checkbox<br /> 
	<button type="submit" value="Submit">Submit</button>
</form>';
  print($html);
}
else {
  funcError($_POST, 99);
}

// ====================================================================================================================

?>