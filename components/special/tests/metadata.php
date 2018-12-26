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

$post = funcUnifiedVariable('post', 'slug');

function funcValueOrEmptyString ($aValue) {
  if ($aValue === true) {
    return 'checked';
  }

  return $aValue ?? '';
}

if (!$post) {
  $addonManifest = $moduleReadManifest->getAddonBySlug('abprime', null);
  
  if ($addonManifest['type'] == 'external' || $addonManifest['type'] == 'langpack') {
    funcError('This only works for Extensions and Themes that are not external');
  }

  $html ='<form id="form1"accept-charset="UTF-8" action="/special/test/?case=metadata" autocomplete="off" method="POST">
	Administration:<br />
  <input type="hidden" name="slug" value="' . $addonManifest['slug'] . '" />
  <input name="active" type="checkbox" value="1" @ADDON_ACTIVE@/> Active<br />
	<input name="reviewed" type="checkbox" value="1" @ADDON_REVIEWED@ /> Reviewed<br />
  <br />
  Repository URL:<br />
  <input name="repository" type="text" size="120" value="@ADDON_REPOURL@" /><br /> 
  <br />
  Support URL:<br />
  <input name="supportURL" type="text" size="120" value="@ADDON_SUPPORTURL@" /><br />
  <br />
  Support E-mail:<br />
  <input name="supportEmail" type="text" size="120" value="@ADDON_SUPPORTEMAIL@" /><br /> 
  <br />
  License: <select name="license">
    @ADDON_LICENSE@
	</select><br />
	<br />
  Content:<br />
	<textarea name="content" cols="120" rows="25">@ADDON_CONTENT@</textarea><br /> 
	<br />
  <button type="submit" value="Submit">Submit</button>
</form>';

  //<option selected="selected" value="1">Yes</option>
  $strLicenseHTML = '';
  $boolLicenseFound = null;

  foreach (array_keys($moduleReadManifest::LICENSES) as $_value) {
    $_lcLicense = strtolower($_value);
    if (strtolower($addonManifest['license']) == $_lcLicense) {
      $strLicenseHTML .= '<option selected="selected" value="' . $_lcLicense . '">' . $_value . '</option>';
      $boolLicenseFound = true;
      continue;
    }

    if (!$boolLicenseFound && $_value == 'COPYRIGHT') {
      $strLicenseHTML .= '<option selected="selected" value="' . $_lcLicense . '">' . $_value . '</option>';
      continue;
    }

    $strLicenseHTML .= '<option value="' . $_lcLicense . '">' . $_value . '</option>';
  }

  $arrayFilterSubstitute = array(
    '@ADDON_ACTIVE@'        => funcValueOrEmptyString($addonManifest['active']),
    '@ADDON_REVIEWED@'      => funcValueOrEmptyString($addonManifest['reviewed']),
    '@ADDON_REPOURL@'       => funcValueOrEmptyString($addonManifest['repository']),
    '@ADDON_SUPPORTURL@'    => funcValueOrEmptyString($addonManifest['supportURL']),
    '@ADDON_SUPPORTEMAIL@'  => funcValueOrEmptyString($addonManifest['supportEmail']),
    '@ADDON_LICENSE@'       => $strLicenseHTML,
    '@ADDON_CONTENT@'       => funcValueOrEmptyString($addonManifest['content'])
  );

  foreach ($arrayFilterSubstitute as $_key => $_value) {
    $html = str_replace($_key, $_value, $html);
  }

  funcSendHeader('html');
  print($html);
}
else {
  $arrayPostResults = array(
    'slug'          => funcUnifiedVariable('post', 'slug'),
    'active'        => funcUnifiedVariable('post', 'active'),
    'reviewed'      => funcUnifiedVariable('post', 'reviewed'),
    'repository'    => funcUnifiedVariable('post', 'repository'),
    'supportURL'    => funcUnifiedVariable('post', 'supportURL'),
    'supportEmail'  => funcUnifiedVariable('post', 'supportEmail'),
    'license'       => funcUnifiedVariable('post', 'license'),
    'content'       => funcUnifiedVariable('post', 'content')
  );

  funcError($arrayPostResults, 99);
}

// ====================================================================================================================

?>