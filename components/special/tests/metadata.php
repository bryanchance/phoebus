<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules
$arrayIncludes = ['readManifest'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleReadManifest = new classReadManifest();

$post = null;

if (!$post) {
  $addonManifest = $moduleReadManifest->getAddonByID('abprime', null);
  funcSendHeader('html');
  $html = '<html><head><title>Metadata</title></head><body>' .
          '<form action="/special/test/?case=metadata" method="post">' .
          '<input type="submit" value="Submit">' .
          '</form>' .
          '</body></html>';

}

// ====================================================================================================================

?>