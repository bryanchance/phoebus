<?php
// == | classReadManifest | ===================================================

class classWriteManifest {
  private $libSQL;
  private $currentApplication;
  private $currentAppID;

  // Some SQL Constants

  // ------------------------------------------------------------------------------------------------------------------

  // Initalize class
  function __construct() {  
    if (!funcCheckModule('sql') || !funcCheckModule('sql-creds') ||
        !array_key_exists('arraySQLCreds', $GLOBALS)) {
      funcError(
      __CLASS__ . '::' . __FUNCTION__ .
      ' - sql and sql-creds are required to be included in the global scope'
      );
    }
    
    // Assign currentApplication by reference
    $this->currentApplication = $GLOBALS['arraySoftwareState']['currentApplication'];
    $this->currentAppID = TARGET_APPLICATION_ID[$GLOBALS['arraySoftwareState']['currentApplication']];

    // Create a new instance of the SafeMysql class
    $this->libSQL = new SafeMysql($GLOBALS['arraySQLCreds']);
  }

  // ------------------------------------------------------------------------------------------------------------------

// ====================================================================================================================

?>