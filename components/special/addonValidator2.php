<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = ['mozillaRDF', 'validator'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// Instantiate modules
$moduleValidator = new classAddonValidator();

// ====================================================================================================================

// == | Functions | ===================================================================================================

function funcReadFileFromZip($_archive, $_file, $_checkExistance = null) {
  $file = funcUnifiedVariable('var', @file_get_contents('zip://' . $_archive . "#" . $_file));

  if (!$file) {
    return null;
  }

  if ($_checkExistance) {
    unset($file);
    return true;
  }

  return $file;
}

// ====================================================================================================================

// == | Main | ========================================================================================================

$addonValidationResult = null;

funcSendHeader('html');
print(file_get_contents($strSkinPath . 'default/template-header.xhtml'));
print('<h2>Upload</h2>');
print(
  '<form action="" method="POST" enctype="multipart/form-data">' .
  '<input type="file" name="xpiFile" />' .
  '<input type="submit" value="Upload" />' .
  '</form>'
);

if ($moduleValidator->xpiFile) {
  $addonValidationResult = $moduleValidator->validateAddon();

  if ($addonValidationResult) {
    print(
      '<br /><br /><pre>' .
      var_export(
        [$moduleValidator->xpiFile, $moduleValidator->validationChecks, $moduleValidator->installManifest],
        true
      ) .
      '</pre>'
    );
  }
  else {
    print(
      '<br /><br /><pre>' .
      var_export($moduleValidator->errors, true) .
      '</pre>'
    );
  }
}

print(file_get_contents($strSkinPath . 'default/template-footer.xhtml'));

// ====================================================================================================================

?>