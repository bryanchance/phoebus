<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Vars | ================================================================

$strRequest = funcHTTPGetValue('ident');

// ============================================================================

// == | Main | ================================================================

require_once($arrayModules['readManifest']);
$addonManifest = new classReadManifest();
$manifest = $addonManifest->getAddonBySlug($strRequest);
if ($manifest != null && array_key_exists('content', $manifest) && $manifest['content'] != null) {
  $manifest['content'] = htmlentities($manifest['content'], ENT_XHTML);
}
funcSendHeader('html');

print(file_get_contents($strSkinPath . 'default/template-header.xhtml'));

if ($manifest != null) {
  print('<pre>' . var_export($manifest, true) . '</pre>');
}
else {
  print('<pre>' . var_export($addonManifest->addonErrors, true) . '</pre>');
}

print(file_get_contents($strSkinPath . 'default/template-footer.xhtml'));

// ============================================================================
?>