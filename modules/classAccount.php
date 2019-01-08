<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | INFO | ========================================================================================================

/* Phoebus User Levels
  Level 1 - Registered Developer
  Level 2 - Advanced/Legacy Developer
  Level 3 - Add-ons Team Member
  Level 4 - Add-ons Team Leader
  Level 5 - Administrator
*/

// ====================================================================================================================

// == | classAuthentication | =========================================================================================

class classAccount { 
  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    if (!funcCheckModule('database')) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - database module is required to be included in the global scope');
    }
  }

  /********************************************************************************************************************
  * Gets all users at or below the requesting user level
  ********************************************************************************************************************/
  public function getUsers() {
    $query = "SELECT * FROM `user` WHERE `level` <= ?i";
    $allUsers = $GLOBALS['moduleDatabase']->query('rows',
                                                      $query,
                                                      $GLOBALS['arraySoftwareState']['authentication']['level']);

    foreach ($allUsers as $_key => $_value) {
      $allUsers[$_key]['addons'] = json_decode($_value['addons']);
    }

    return $allUsers;
  }

  /********************************************************************************************************************
  * Gets a single user manifest
  ********************************************************************************************************************/
  private function getSingleUser($aUserName, $aIncludePassword = null) {
    $query = "SELECT * FROM `user` WHERE `username` = ?s";
    $userManifest = $GLOBALS['moduleDatabase']->query('row', $query, $aUserName);

    if (!$aIncludePassword) {
      unset($userManifest['password']);
    }

    return $userManifest;
  }

  /********************************************************************************************************************
  * Prompts for credentals or shows 401
  ********************************************************************************************************************/
  private function promptCredentials() {
    header('WWW-Authenticate: Basic realm="' . SOFTWARE_NAME . '"');
    header('HTTP/1.0 401 Unauthorized');   
    funcError('You need to enter a valid username and password.');
    exit();
  }

  /********************************************************************************************************************
  * Performs authentication
  ********************************************************************************************************************/
  public function authenticate($aLogout = null) {
    // Get Username and Password from HTTP Basic Authentication 
    $strUsername = funcUnifiedVariable('server', 'PHP_AUTH_USER');
    $strPassword = funcUnifiedVariable('server', 'PHP_AUTH_PW');

    // Check for the existance of username and password as well as the special 'logout' user
    if (!$strUsername || $strUsername == 'logout' || !$strPassword ) {
      $this->promptCredentials();
    }
    // This will handle a logout situation using a dirty javascript trick
    // It will not work without javascript or on IE but then again neither will the PANEL
    if ($aLogout) {
      $url = 'https://logout:logout@' . $GLOBALS['arraySoftwareState']['currentDomain'] . '/panel/logout/';
      funcSendHeader('html');
      die(
        '<html><head><script>' .
        'var xmlHttp = new XMLHttpRequest();' .
        'xmlHttp.open( "GET", "' . $url . '", false );' .
        'xmlHttp.send( null );' .
        'window.location = "/panel/";' .
        '</script></head><body>' .
        '<p>Logging out...</p>' .
        '<p>If you are not redirected you also are not logged out. Enable Javascript or stop using IE/Edge!<br>' .
        'Additionally, you can just close the browser or clear private data.</p>' .
        '</body></html>'
      );
    }

    // ----------------------------------------------------------------------------------------------------------------

    // Query SQL for a user row
    $userManifest = $this->getSingleUser($strUsername, true);

    // If nothing from SQL or the user isn't active or the password doesn't match
    // then reprompt until the user cancels
    if (!$userManifest || !$userManifest['active'] || !password_verify($strPassword, $userManifest['password'])) {
      $userManifest = null;
      $this->promptCredentials();
    }

    // We don't need the password anymore at this point so kill it
    $strPassword = null;
    unset($userManifest['password']);

    // ----------------------------------------------------------------------------------------------------------------

    // We need to JSON decode the Add-ons list
    $userManifest['addons'] = json_decode($userManifest['addons']);

    // Boolean cast int booleans
    $userManifest['active'] = (bool)$userManifest['active'];

    // Assign the userManifest to the softwareState
    $GLOBALS['arraySoftwareState']['authentication'] = $userManifest;

    // Clear out the userManifest for safety
    $userManifest = null;

    return true;
  }
}
// ====================================================================================================================

?>