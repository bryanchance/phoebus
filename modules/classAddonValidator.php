<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classAddonValidator | =========================================================================================

class classAddonValidator { 
  public $xpiFile;
  public $errors;
  public $installManifest;
  public $validationChecks;

  private $arraySoftwareState;
  private $moduleMozillaRDF;
  private $jetpackManifest;

  const INSTALL_MANIFEST = 'install.rdf';
  const CHROME_MANIFEST = 'chrome.manifest';
  const JETPACK_MANIFEST = 'package.json';
  const OLD_JETPACK_MANIFEST = 'harness-options.json';
  const WEBEXTENSION_MANIFEST = 'manifest.json';

  const REGEX_GUID = '/^\{[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\}$/i';
  const REGEX_ID = '/[a-z0-9-\._]*\@[a-z0-9-\._]+/i';

  const RESTRICTED_IDS = array(
    '-bfc5-fc555c87dbc4}', // Moonchild Productions
    '-9376-3763d1ad1978}', // Pseudo-Static
    '-b98e-98e62085837f}', // Ryan
    '-9aa0-aa0e607640b9}', // BinOC
    'palemoon.org',
    'basilisk-browser.org',
    'binaryoutcast.com',
    'lootyhoof-pm',
    'mattatobin.com',
    'mozilla.org'
  );

  const VALIDATOR_STRINGS = array(
    0 => 'This is not an xpi file',
    1 => 'This xpi file does not have a valid install manifest',
    2 => 'CFX based Jetpack extensions are not supported',
    3 => 'WebExtensions are currently not supported',
    4 => 'The install manifest is missing minimum required tags - Please check to make sure it has id, type, version, creator, description, and targetApplication "em:" tags.',
    5 => 'This add-on has an "updateURL" and/or "updateKey"',
    6 => 'Embedded WebExtensions are not supported',
    7 => 'This add-on has an invalid ID or GUID',
    8 => 'This add-on has a restricted ID or GUID',
    9 => 'This add-on does not support any of the target applications that this site serves',
    10 => 'minVersion must not contain an infinite version part "*"',
    11 => 'Infinite maxVersion "*" is not supported',
  );

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    if (!funcCheckModule('mozillaRDF')) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - mozillaRDF is required to be included in the global scope');
    }

    // Assign current software state to a class property by reference
    $this->arraySoftwareState = &$GLOBALS['arraySoftwareState'];

    // Create a new instance of the mozilla rdf module
    $this->moduleMozillaRDF = new classMozillaRDF();

    $this->errors = array();
    $this->validationChecks = array(
      'installManifest'             => null,
      'webExtensionManifest'        => null,
      'jetpackManifest'             => null,
      'oldJetpackManifest'          => null,
      'hasID'                       => null,
      'hasType'                     => null,
      'hasVersion'                  => null,
      'hasCreator'                  => null,
      'hasDescription'              => null,
      'hasTargetApplication'        => null,
      'hasBootstrap'                => null,
      'hasEmbeddedWebExtension'     => null,
      'hasUpdateURL'                => null,
      'hasUpdateKey'                => null,
      'isValidID'                   => null,
      'isValidTargetApplication'    => null,
      'isValidTargetAppVersions'    => null,
      'isJetpack'                   => null
    );

    $this->xpiFile = funcUnifiedVariable('files', 'xpiFile');
  }

  /********************************************************************************************************************
  * Performs Validation on an XPI file
  *
  * @param            TBD
  * @returns          True or null
  ********************************************************************************************************************/
  public function validateAddon($_alreadyExists = null) {
    // Return null because there is no XPI file
    if (!$this->xpiFile || $this->xpiFile['error'] != UPLOAD_ERR_OK) {
      if ($this->xpiFile['error'] ?? null) {
        switch ($this->xpiFile['error']) {
          case UPLOAD_ERR_INI_SIZE:
            $uploadErrorMessage = 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            break;
          case UPLOAD_ERR_FORM_SIZE:
            $uploadErrorMessage = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            break;
          case UPLOAD_ERR_PARTIAL:
            $uploadErrorMessage = 'The uploaded file was only partially uploaded';
            break;
          case UPLOAD_ERR_NO_FILE:
            $uploadErrorMessage = 'No file was uploaded';
            break;
          case UPLOAD_ERR_NO_TMP_DIR:
            $uploadErrorMessage = 'Missing a temporary folder';
            break;
          case UPLOAD_ERR_CANT_WRITE:
            $uploadErrorMessage = 'Failed to write file to disk';
            break;
          case UPLOAD_ERR_EXTENSION:
            $uploadErrorMessage = 'File upload stopped by extension';
            break;
          default:
            $uploadErrorMessage = 'Unknown upload error: ' . $this->xpiFile['error'];
            break;
        }
        $this->errors[] = $uploadErrorMessage;
        $this->xpiFile = null;
      }

      return null;
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Other browsers send different mimetypes for xpi files so we can't trust this outside
    // of historically mozilla-based clients so actually check the extension and the file its self
    $filetype = mime_content_type($this->xpiFile['tmp_name']);

    if ($filetype != 'application/zip' || !endsWith($this->xpiFile['name'], '.xpi')) {
      $this->errors[] = self::VALIDATOR_STRINGS[0];
      return null;
    }

    $this->xpiFile['type'] = 'application/x-xpinstall';

    // ----------------------------------------------------------------------------------------------------------------

    // Check for the existance of manifest files and read install.rdf and do basic checks on it
    // Check for Valid ID and Valid targetApplications
    if (!$this->checkManifestsFiles() ||
        !$this->checkID($_alreadyExists) ||
        !$this->checkTargetApplication()) {
      return null;
    }

    return true;
  }

  /********************************************************************************************************************
  * Check for existance of manifest files and read/parse install.rdf if existant
  *
  * @returns  true or null
  ********************************************************************************************************************/
  private function checkManifestsFiles() {
    // Check for Old Jetpack manifest file
    $this->validationChecks['oldJetpackManifest'] =
      funcReadFileFromZip($this->xpiFile['tmp_name'], self::OLD_JETPACK_MANIFEST, true);

    // Check for WebExtension manifest file
    $this->validationChecks['webExtensionManifest'] =
      funcReadFileFromZip($this->xpiFile['tmp_name'], self::WEBEXTENSION_MANIFEST, true);

    // Check for install manifest file and parse it
    $this->installManifest = funcReadFileFromZip($this->xpiFile['tmp_name'], self::INSTALL_MANIFEST);
    if ($this->installManifest) {
      $this->installManifest = $this->moduleMozillaRDF->parseInstallManifest($this->installManifest);
      if (is_array($this->installManifest)) {
        $this->validationChecks['installManifest'] = true;
      }
    }

    // Check for jetpack manifests and json decode it because we need to check for embedded webextensions later
    $this->jetpackManifest =
      json_decode(funcReadFileFromZip($this->xpiFile['tmp_name'], self::JETPACK_MANIFEST), true);
    if ($this->jetpackManifest) {
      $this->validationChecks['jetpackManifest'] = true;
    }

    // We don't allow CFX based old Jetpack extensions
    if ($this->validationChecks['oldJetpackManifest']) {
      $this->errors[] = self::VALIDATOR_STRINGS[2];
      return null;
    }

    // We don't allow WebExtensions at this time
    // OR EVER IF I GET MY WAY!!!
    if ($this->validationChecks['webExtensionManifest']) {
      $this->errors[] = self::VALIDATOR_STRINGS[3];
      return null;
    }

    // Make sure we have install.rdf and it is valid rdf
    if (!$this->validationChecks['installManifest']) {
      $this->errors[] = self::VALIDATOR_STRINGS[1];
      return null;
    }

    // Populate the has* checks by checking install.rdf
    $this->validationChecks['hasID'] = (bool)($this->installManifest['id'] ?? null);
    $this->validationChecks['hasType'] = (bool)($this->installManifest['type'] ?? null);
    $this->validationChecks['hasVersion'] = (bool)($this->installManifest['version'] ?? null);
    $this->validationChecks['hasCreator'] = (bool)($this->installManifest['creator'] ?? null);
    $this->validationChecks['hasDescription'] = (bool)($this->installManifest['description'] ?? null);
    $this->validationChecks['hasTargetApplication'] = (bool)($this->installManifest['targetApplication'] ?? null);
    $this->validationChecks['hasBootstrap'] = (bool)($this->installManifest['bootstrap'] ?? null);
    $this->validationChecks['hasEmbeddedWebExtension'] = (bool)($this->installManifest['hasEmbeddedWebExtension'] ?? null);
    $this->validationChecks['hasUpdateURL'] = (bool)($this->installManifest['updateURL'] ?? null);
    $this->validationChecks['hasUpdateKey'] = (bool)($this->installManifest['updateKey'] ?? null);

    // Add-ons ABSOLUTELY MUST HAVE THESE
    if (!$this->validationChecks['hasID'] ||
        !$this->validationChecks['hasType'] ||
        !$this->validationChecks['hasVersion'] ||
        !$this->validationChecks['hasCreator'] ||
        !$this->validationChecks['hasDescription'] ||
        !$this->validationChecks['hasTargetApplication']) {
      $this->errors[] = self::VALIDATOR_STRINGS[4];
      return null;
    }
    
    // Check if Add-on is Jetpack and determin hasEmbeddedWebExtension status
    if ($this->validationChecks['hasBootstrap'] && $this->jetpackManifest) {
      $this->validationChecks['isJetpack'] = true;
      if ($this->jetpackManifest['hasEmbeddedWebExtension'] ?? null) {
        $this->validationChecks['hasEmbeddedWebExtension'] = true;
      }
    }

    // Embedded WebExtensions are not allowed
    if ($this->validationChecks['hasEmbeddedWebExtension']) {
      $this->errors[] = self::VALIDATOR_STRINGS[6];
      return null;
    }

    // We do not allow updateURL or updateKey
    if ($this->validationChecks['hasUpdateURL'] || $this->validationChecks['hasUpdateKey']) {
      $this->errors[] = self::VALIDATOR_STRINGS[5];
      return null;
    }

    return true;
  }

  /********************************************************************************************************************
  * Check for required install.rdf entries
  *
  * @returns  true or null
  ********************************************************************************************************************/
  private function checkID($_alreadyExists) {
    // Check if the add-on id is a valid GUID or user@host ID
    if (!preg_match(self::REGEX_GUID, $this->installManifest['id']) &&
        !preg_match(self::REGEX_ID, $this->installManifest['id'])) {
      $this->errors[] = self::VALIDATOR_STRINGS[7];
      return null;
    }

    // Check if the add-on id is one of the application ids
    if (in_array($this->installManifest['id'], TARGET_APPLICATION_ID)) {
      $this->errors[] = self::VALIDATOR_STRINGS[8];
      return null;
    }

    // Check if the add-on id is restricted
    // Add-ons Team and already existing add-ons are exempt from this
    foreach (self::RESTRICTED_IDS as $_value) {
      if ($_alreadyExists || (array_key_exists('authentication', $this->arraySoftwareState) &&
          $this->arraySoftwareState['authentication']['level'] >= 8)) {
        $this->validationChecks['isValidID'] = true;
        return true;
      }

      if (contains($this->installManifest['id'], $_value)) {
        $this->errors[] = self::VALIDATOR_STRINGS[8];
        return null;
      }
    }

    $this->validationChecks['isValidID'] = true;
    return true;
  }

  /********************************************************************************************************************
  * Check for required install.rdf entries
  *
  * @returns  true or null
  ********************************************************************************************************************/
  private function checkTargetApplication() {
    $validApplication = null;
    $validMinVersion = null;
    $validMaxVersion = null;

    $applications = [
      TARGET_APPLICATION_ID['palemoon'],
      TARGET_APPLICATION_ID['basilisk'],
      TARGET_APPLICATION_ID['borealis']
    ];
    
    foreach ($this->installManifest['targetApplication'] as $_key => $_value) {
      if (in_array($_key, $applications)) {
        $validApplication = true;
      }

      if (contains($_value['minVersion'], '*')) {
        break;
      }

      $validMinVersion = true;

      if ($_value['minVersion'] == '*') {
        break;
      }

      $validMaxVersion = true;
    }

    if (!$validApplication) {
      $this->errors[] = self::VALIDATOR_STRINGS[9];
      return null;
    }

    if (!$validMinVersion) {
      $this->errors[] = self::VALIDATOR_STRINGS[10];
      return null;
    }

    if (!$validMaxVersion) {
      $this->errors[] = self::VALIDATOR_STRINGS[11];
      return null;
    }

    $this->validationChecks['isValidTargetApplication'] = true;
    $this->validationChecks['isValidTargetAppVersions'] = true;
    return true;
  }
}

// ====================================================================================================================

?>