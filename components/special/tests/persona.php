<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

// Include modules
$arrayIncludes = ['database', 'persona'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleDatabase = new classDatabase();
$modulePersona = new classPersona();

$categoryManifest = $modulePersona->getPersonas('site-all-personas');

//funcError($categoryManifest, 2);

funcSendHeader('html');

print(file_get_contents($strSkinPath . 'default/template-header.xhtml'));

print('<script>
  function setTheme(node) {
    var event = document.createEvent("Events");
    event.initEvent("InstallBrowserTheme", true, false);
    node.dispatchEvent(event);
  }
</script>');

foreach($categoryManifest as $_value) {
  print('<a href="#" onclick="setTheme(this);" data-browsertheme=\'' . json_encode($_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '\'>' . $_value['name'] . '</a><br />');
}

print(file_get_contents($strSkinPath . 'default/template-footer.xhtml'));



// ====================================================================================================================

?>