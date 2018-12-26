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
  $addonManifest = $moduleReadManifest->getAddonByID('abprime', null);
  funcSendHeader('html');
  $html = '<html><head><title>Metadata</title></head><body>' .
          '<form action="/special/test/?case=metadata&post=1" method="post">';

  // Active
  $html .= '<label for="addonActive">Active</label> ';
  if ($addonManifest['active']) {
    $html .= '<input type="checkbox" id="addonActive" name="addonActive" checked><br />';
  }
  else {
    $html .= '<input type="checkbox" id="addonActive" name="addonActive"><br />';
  }

  // Reviewed
  $html .= '<label for="addonReviewed">Reviewed</label> ';
  if ($addonManifest['reviewed']) {
    $html .= '<input type="checkbox" id="addonReviewed" name="addonReviewed" checked><br />';
  }
  else {
    $html .= '<input type="checkbox" id="addonReviewed" name="addonReviewed"><br />';
  }

  $html .= '<label for="addonRepository">Repository</label> ' .
           '<input type="url" id="addonRepository" value="' . $addonManifest['repository'] . '">';

  $html .= '<input type="submit" value="Submit"></form></body></html>';
  print($html);
}
else {
  funcError($_POST, 99);
}

// ====================================================================================================================

?>