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
  if ($aValue === true) {
    return 'checked';
  }

  return $aValue ?? '';
}

if (!$post) {
  $addonManifest = $moduleReadManifest->getAddonBySlug('abprime', null);
  //funcError($addonManifest, 98);

  $html ='<form id="form1"accept-charset="UTF-8" action="/special/test/?case=metadata&post=1" autocomplete="off" method="POST" target="_blank">
	Administration:<br />
  <input name="active" type="checkbox" value="1" @ADDON_ACTIVE@/> Active<br />
	<input name="reviewed" type="checkbox" value="1" @ADDON_REVIEWED@ /> Reviewed<br />
  <br />
  Repository URL:<br />
  <input name="repository" type="text" size="120" value="@ADDON_REPOURL@" /><br /> 
  Support URL:<br />
  <input name="supportURL" type="text" size="120" value="@ADDON_SUPPORTURL@" /><br />
  Support E-mail:<br />
  <input name="supportEmail" type="text" size="120" value="@ADDON_SUPPORTEMAIL@" /><br /> 
  License: <select name="license">
    @ADDON_LICENSE@
	</select><br />
	Content:<br />
	<textarea name="content" cols="120" rows="25">@ADDON_CONTENT@</textarea><br /> 
	<br />
  <button type="submit" value="Submit">Submit</button>
</form>';

  //<option selected="selected" value="1">Yes</option>
  $strLicenseHTML = '';
  $boolLicenseFound = null;


  $arrayFilterSubstitute = array(
    '@ADDON_ACTIVE@' => funcValueOrEmptyString($addonManifest['active']),
    '@ADDON_REVIEWED@' => funcValueOrEmptyString($addonManifest['reviewed']),
    '@ADDON_REPOURL@' => funcValueOrEmptyString($addonManifest['repository']),
    '@ADDON_SUPPORTURL@' => funcValueOrEmptyString($addonManifest['supportURL']),
    '@ADDON_SUPPORTEMAIL@' => funcValueOrEmptyString($addonManifest['supportEmail']),
    '@ADDON_LICENSE@' => $strLicenseHTML,
    '@ADDON_CONTENT@' => funcValueOrEmptyString($addonManifest['content'])
  );

  foreach ($arrayFilterSubstitute as $_key => $_value) {
    $html = str_replace($_key, $_value, $html);
  }

  funcSendHeader('html');
  print($html);
}
else {
  funcError($_POST, 99);
}

// ====================================================================================================================

?>