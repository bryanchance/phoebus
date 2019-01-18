<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | INFO | ========================================================================================================

/* Phoebus User Levels
  Level 1 - Add-on Developer
  Level 2 - Advanced/Legacy Add-on Developer
  Level 3 - Add-ons Team Member
  Level 4 - Add-ons Team Leader
  Level 5 - Phoebus Administrator
*/

// ====================================================================================================================

// == | classAuthentication | =========================================================================================

class classAccount {
  private $postData;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {
    if (!funcCheckModule('database')) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - database module is required to be included in the global scope');
    }

    $this->postData = array(
      'username'      => funcUnifiedVariable('post', 'username'),
      'password'      => funcUnifiedVariable('post', 'password'),
      'active'        => (bool)funcUnifiedVariable('post', 'active'),
      'level'         => (int)funcUnifiedVariable('post', 'level'),
      'displayName'   => funcUnifiedVariable('post', 'displayName'),
      'email'         => funcUnifiedVariable('post', 'email'),
    );
  }

  /********************************************************************************************************************
  * Update a user manifest
  ********************************************************************************************************************/
  public function registerUser() {
    $regex = '/[^a-z0-9_\-]/';

    $this->postData['active'] = false;
    $this->postData['level'] = 1;

    $username = preg_replace($regex, '', $this->postData['username']);
    if (!$this->postData['username'] ||
        strlen($this->postData['username']) < 3 ||
        strlen($this->postData['username']) > 32 ||
        $this->postData['username'] !== $username) {
      funcError('You did not specify a valid username.</li>' .
                '<li>Usernames must be 3+ chars not exceeding 32 chars.</li>' .
                '<li>Please use only lower case letters, numbers, and/or underscore (_) or dash (-)');
    }

    if (!$this->postData['password'] ||
        strlen($this->postData['password']) < 8 ||
        strlen($this->postData['password']) > 64 ) {
      funcError('You did not specify a valid password. Passwords must be 8+ chars not exceeding 64 chars.');
    }

    $this->postData['password'] = password_hash($this->postData['password'], PASSWORD_BCRYPT);

    if (!$this->postData['email']) {
      funcError('You did not specify a valid email address. You will not be able to activate your account without one');
    }

    if (!$this->postData['displayName']) {
      funcError('You did not specify a display name.');
    }

    $query = "SELECT `username`, `email` FROM `user` WHERE `username` = ?s OR `email` = ?s";
    (bool)$isUsernameOrEmailExisting = $GLOBALS['moduleDatabase']->query('rows',
                                                                         $query,
                                                                         $this->postData['username'],
                                                                         $this->postData['email']);

    if ($isUsernameOrEmailExisting) {
      funcError('Your username or email address is not available. Please select another.</li>' .
                '<li>You may only have one account.');
    }

    $code = funcUnifiedVariable('var', @file_get_contents(ROOT_PATH . DATASTORE_RELPATH . 'pm-admin/secret.code')) ?? time();
    $hash = hash('sha256', time() . $this->postData['username'] . $this->postData['email'] . $code);

    $extraData = array(
      'regEpoch' => time(),
      'verification' => $hash
    );

    $this->postData['extraData'] = json_encode($extraData, 320);
    $this->postData['addons'] = '[]';

    $query = "INSERT INTO `user` SET ?u";
    $GLOBALS['moduleDatabase']->query('normal', $query, $this->postData);
    
    return true;
  }

  /********************************************************************************************************************
  * Update a user manifest
  ********************************************************************************************************************/
  public function updateUserManifest($aUserManifest) {
    if (!$this->postData['username']) {
      funcError('Username was not found in POST');
    }

    if ($this->postData['username'] != $aUserManifest['username']) {
      funcError('POST Slug does not match GET/Manifest Slug');
    }

    if (!in_array($this->postData['level'], [1, 2, 3, 4, 5])) {
      $this->postData['level'] = $aUserManifest['level'];
    }

    // Hackers are a superstitious cowardly lot
    if ($GLOBALS['arraySoftwareState']['authentication']['level'] < 3) {
      unset($this->postData['active']);
      unset($this->postData['level']);
      unset($this->postData['username']);
    }
    else {
      if ($GLOBALS['arraySoftwareState']['authentication']['level'] != 5 &&
          $this->postData['level'] >= $GLOBALS['arraySoftwareState']['authentication']['level']) {
        switch ($GLOBALS['arraySoftwareState']['authentication']['username']) {
          case $this->postData['username']:
            if ($this->postData['level'] <= $GLOBALS['arraySoftwareState']['authentication']['level']) {
              break;
            }
          default:
            funcError('Seriously, did you think manipulating user levels was going to work? I\'m disappointed!');
        }
      }

      if ($aUserManifest['extraData']['verification'] && $this->postData['active']) {
        $this->postData['extraData'] = $aUserManifest['extraData'];
        $this->postData['extraData']['verification'] = null;
        unset($this->postData['extraData']['regDate']);
        $this->postData['extraData'] = json_encode($this->postData['extraData'], 320);
      }
    }

    if ($this->postData['password']) {
      if (strlen($this->postData['password']) < 8 || strlen($this->postData['password']) > 64 ) {
        funcError('You did not specify a valid password. Passwords must be 8+ chars not exceeding 64 chars.');
      }
      $this->postData['password'] = password_hash($this->postData['password'], PASSWORD_BCRYPT);
    }

    // Remove stuff that is the same
    foreach ($this->postData as $_key => $_value) {
      if ($aUserManifest[$_key] == $_value) {
        unset($this->postData[$_key]);
      }
    }

    if (empty($this->postData)) {
      return true;
    }

    if ($this->postData['username'] ?? false) {
      funcError('Username is still existing in post data');
    }

    // Insert the new manifest data into the database
    $query = "UPDATE `user` SET ?u WHERE `username` = ?s";
    $GLOBALS['moduleDatabase']->query('normal', $query, $this->postData, $aUserManifest['username']);

    return true;
  }

  /********************************************************************************************************************
  * Gets all users at or below the requesting user level
  ********************************************************************************************************************/
  public function getUsers() {
    if ($GLOBALS['arraySoftwareState']['authentication']['level'] < 3) {
      funcError('I have no idea how you managed to get here but seriously you need to piss off...');
    }

    $query = "SELECT * FROM `user` WHERE ?i = 5 OR `level` < ?i OR `username` = ?s";
    $allUsers = $GLOBALS['moduleDatabase']->query('rows',
                                                  $query,
                                                  $GLOBALS['arraySoftwareState']['authentication']['level'],
                                                  $GLOBALS['arraySoftwareState']['authentication']['level'],
                                                  $GLOBALS['arraySoftwareState']['authentication']['username']);

    foreach ($allUsers as $_key => $_value) {
      unset($allUsers[$_key]['password']);
      $allUsers[$_key]['active'] = (bool)$_value['active'];
      $allUsers[$_key]['level'] = (int)$_value['level'];
      $allUsers[$_key]['extraData'] = json_decode($_value['extraData'], true);
      $allUsers[$_key]['extraData']['regDate'] = date('F j, Y', $allUsers[$_key]['extraData']['regEpoch']);
      $allUsers[$_key]['addons'] = json_decode($_value['addons']);
    }

    return $allUsers;
  }

  /********************************************************************************************************************
  * Gets a single user manifest
  ********************************************************************************************************************/
  public function getSingleUser($aUserName, $aRemovePassword = null) {
    $query = "SELECT * FROM `user` WHERE `username` = ?s";
    $userManifest = $GLOBALS['moduleDatabase']->query('row', $query, $aUserName);

    $userManifest['active'] = (bool)$userManifest['active'];
    $userManifest['level'] = (int)$userManifest['level'];
    $userManifest['extraData'] = json_decode($userManifest['extraData'], true);
    $userManifest['extraData']['regDate'] = date('F j, Y', $userManifest['extraData']['regEpoch']);
    $userManifest['addons'] = json_decode($userManifest['addons']);

    if ($aRemovePassword) {
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
    $userManifest = $this->getSingleUser($strUsername);

    // If nothing from SQL or the user isn't active or the password doesn't match
    // then reprompt until the user cancels
    if (!$userManifest || !password_verify($strPassword, $userManifest['password'])) {
      $userManifest = null;
      $this->promptCredentials();
    }

    // Deal with inactive users.. If inactive but not validated then send to validation page
    // else prompt forever
    if (!$userManifest['active']) {
      if ($userManifest['extraData']['verification']) {
        funcRedirect(URI_VERIFY);
      }

      $userManifest = null;
      $this->promptCredentials();
    }

    // Levels 1 and 2 need to add their email and displayName so force them
    if ($userManifest['level'] < 3 && $GLOBALS['arraySoftwareState']['requestPath'] != URI_ACCOUNT) {
      if (!$userManifest['email'] || !$userManifest['displayName']) {
        funcRedirect(URI_ACCOUNT);
      }
    }

    // We don't need the password anymore at this point so kill it
    unset($userManifest['password']);

    // ----------------------------------------------------------------------------------------------------------------

    // Assign the userManifest to the softwareState
    $GLOBALS['arraySoftwareState']['authentication'] = $userManifest;

    return true;
  }
}
// ====================================================================================================================

?>