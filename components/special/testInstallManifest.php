<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | =======================================================================================================

// Include modules
$arrayIncludes = ['mozillaRDF'];
foreach ($arrayIncludes as $_value) { require_once(MODULES[$_value]); }

// ====================================================================================================================

// == | Main | ========================================================================================================

function funcReadFileFromZip($_value, $_checkExistance = null) {
  $file = funcCheckVar(@file_get_contents('zip://' . ROOT_PATH . $_value));;

  if (!$file) {
    return null;
  }

  if ($_checkExistance) {
    unset($file);
    return true;
  }

  return $file;
}

$moduleMozillaRDF = new classMozillaRDF();

$pathInstallManifest = DATASTORE_RELPATH . 'addons/abprime/abprime-1.0.6.xpi#install.rdf';
$installManifest = funcReadFileFromZip($pathInstallManifest);

if (!$installManifest) {
  funcError('Could not open requested file from zip.');
}

funcError($moduleMozillaRDF->parseInstallManifest($installManifest), 1);

// ====================================================================================================================

?>