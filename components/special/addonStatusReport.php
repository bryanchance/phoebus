<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Vars | ================================================================

$strRequest = funcHTTPGetValue('slug');
$_addonStatusOK = ':<span style="color: green"> <strong>[  OK  ]</strong></span><br />';
$_addonStatusFail = ':<span style="color: red"> <strong>[ FAIL ]</strong></span><br />';
$GLOBALS['boolDebugMode'] = false;
$arrayAddonsDB = array();

// ============================================================================

// == | Main | ================================================================

require_once($arrayModules['ftpAuth']);

$FTPAuth = new classFTPAuth;

$isAuthorized = $FTPAuth->doAuth();

$arrayAddonsDB = $FTPAuth->arrayFinalAddons;

if ($strRequest != null) {
  if ($FTPAuth->boolIsAdmin == true) {
    $arrayAddonsDB = array($strRequest);
  }
  elseif (in_array($strRequest, $arrayAddonsDB)) {
    $arrayAddonsDB = array($strRequest);
  }
  else {
    funcSendHeader('404');
  }
}

require_once($arrayModules['addonManifest']);
$addonManifest = new classAddonManifest();

funcSendHeader('html');

print(file_get_contents($strSkinPath . 'default/template-header.xhtml'));

print('<h1>Check Add-on Status</h1>');
print('<div class="description">Did you know that Phoebus is another name for Apollo?</div>');

print('<h2>Add-on status</h2>');

foreach ($arrayAddonsDB as $_value) {
  $_addonManifest = $addonManifest->funcGetManifest($_value, true);
  $_addonErrors = $addonManifest->addonErrors;
  $_checkURL = $_value;
  
  if ($strRequest == null) {
    $_checkURL = '<a href="' . $_SERVER['REQUEST_URI'] . '?slug=' . $_value . '">' . $_value . '</a>';
  }
  
  if ($_addonManifest != null) {
    print($_checkURL . $_addonStatusOK);
  }
  else {
    print($_checkURL . $_addonStatusFail);
  }
  
  if ($_addonErrors != null) {
    print('-!- ' . implode('<br />-!- ', $_addonErrors) . '<br />');
  }
}

if ($strRequest != null && $_addonManifest != null) {
  unset($_addonManifest['phoebus']);
  //$_addonManifest['xpinstall'] = json_encode($_addonManifest['xpinstall'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  print(
    '<h2>Add-on data structure</h2><pre>' .
    htmlentities(var_export($_addonManifest, true), ENT_XHTML) .
    '</pre>'
  );
}

print(file_get_contents($strSkinPath . 'default/template-footer.xhtml'));

// ============================================================================
?>