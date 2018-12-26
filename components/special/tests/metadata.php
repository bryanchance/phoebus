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

$post = empty($_POST);

if (!$post) {
  $addonManifest = $moduleReadManifest->getAddonByID('abprime', null);
  funcSendHeader('html');
  $html = '<html><head><title>Metadata</title></head><body>' .
          '<form action="/special/test/?case=metadata" method="post">' .
          '<label for="addonActive">Active</label>' .
          '<input type="checkbox" id="addonActive">' .
          '<input type="submit" value="Submit">' .
          '</form>' .
          '</body></html>';
  print($html);
}
else {
  funcError($_POST, 99);
}

// ====================================================================================================================

?>