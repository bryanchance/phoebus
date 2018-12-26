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

function funcValueOrEmptyString ($aValue) {
  return $aValue ?? '';
}

if (!$post) {
  $addonManifest = $moduleReadManifest->getAddonBySlug('abprime', null);
  //funcError($addonManifest, 98);

  funcSendHeader('html');

  $html ='<form id="form1"accept-charset="UTF-8" action="/special/test/?case=metadata&post=1" autocomplete="off" method="POST" target="_blank">
	Administration:<br />
  <input name="active" type="checkbox" value="1" /> Active<br />
	<input name="reviewed" type="checkbox" value="1" /> Reviewed<br />
  <br />
  Repository:<br />
  <input name="repository" type="text" value="@ADDON_REPO@" /><br /> 
	Content:<br />
	<textarea name="content" cols="30" rows="2">@ADDON_CONTENT@</textarea><br /> 
	<br />
  <button type="submit" value="Submit">Submit</button>
</form>';

  $arrayFilterSubstitute = array(
    '@ADDON_ACTIVE@' => funcValueOrEmptyString($addonManifest['active']),
    '@ADDON_REVIEWED@' => funcValueOrEmptyString($addonManifest['reviewed']),
    '@ADDON_REPOURL@' => funcValueOrEmptyString($addonManifest['repository']),
    '@ADDON_CONTENT@' => funcValueOrEmptyString($addonManifest['content'])
  );

  foreach ($arrayFilterSubstitute as $_key => $_value) {
    $html = str_replace($_key, $_value, $html);
  }

  print($html);
}
else {
  funcError($_POST, 99);
}

// ====================================================================================================================

?>