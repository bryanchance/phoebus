<?php

// Don't include things in class scope when it MAY be called elsewhere.. It won't work
require_once($arrayModules['dbAddons']);

// Define a class to handle FTPAuth
class classFTPAuth {

private $strUser;
private $strPassword;

private $arrayAdminDB;
public $boolIsAdmin;
private $arrayUserDB;

private $arrayAllAddons;
private $arrayUserAddons;

public $arrayFinalAddons;
    
    public function doAuth($_adminOnly = null) {
        
        funcCheckUserAgent();
        
        $this->strUser = $this->checkServerValue('PHP_AUTH_USER');
        $this->strPassword = $this->checkServerValue('PHP_AUTH_PW');
        
        $this->getData();

        if (array_key_exists($this->strUser, $this->arrayAdminDB)) {
            if ($this->strUser == 'mattatobin') {
                $this->boolIsAdmin = password_verify($this->strPassword, $this->arrayAdminDB[$this->strUser]);
            }
            elseif ($this->arrayAdminDB[$this->strUser] == $this->strPassword) {
                $this->boolIsAdmin = true;
            }

            if ($this->boolIsAdmin == true) {
                $this->arrayFinalAddons = $this->arrayAllAddons;
                sort($this->arrayFinalAddons);
                unset($this->arrayAllAddons);
                unset($this->arrayUserAddons);
                return $this->boolIsAdmin;
            }
            else {
                $this->failAuth();
            }
        }
        elseif (array_key_exists($this->strUser, $this->arrayUserDB) && $this->arrayUserDB[$this->strUser] == $this->strPassword && $_adminOnly == null) {
            $this->boolIsAdmin = false;
            $this->arrayFinalAddons = $this->arrayUserAddons[$this->strUser];
            sort($this->arrayFinalAddons);
            return true;
        }
        else {
            $this->failAuth();
        }
    }

    private function getData() {       
        // Cycle through and assign them as a index based array
        foreach ($GLOBALS['arrayAddonsDB'] as $_key => $_value) {
            $this->arrayAllAddons[] = $_value;
        }
        
        unset($arrayAddonsDB);
        
        $_strPathJSON = '/regolith/account/.vsftpd/users/';
        
        // JSON decode admin.json to an array
        $_arrayAdminJSON = json_decode(file_get_contents($_strPathJSON . 'admin.json'), true);
        
        // Cycle through the entries and assign them to class property $arrayAdmins
        foreach($_arrayAdminJSON as $_value) {
            if ($_value['username'] == 'mattatobin') {
                $this->arrayAdminDB[$_value['username']] = $_value['hash'];
            }
            else {
                $this->arrayAdminDB[$_value['username']] = $_value['password'];
            }
        }
        $this->arrayAdminDB['testout'] = 'time03';
        unset($_arrayAdminJSON);

        // Glob all json files
        $_arrayUserJSONFiles = glob($_strPathJSON . '*.json');

        // Cycle through the entries and load the files assigning usernames, passwords, and add-ons
        foreach ($_arrayUserJSONFiles as $_value) {
            if (!endsWith($_value, 'admin.json')) {
                $_jsonFile = json_decode(file_get_contents($_value), true);
                $this->arrayUserDB[$_jsonFile['account']['username']] = $_jsonFile['account']['password'];
                $this->arrayUserAddons[$_jsonFile['account']['username']] = $_jsonFile['addons'];
            }
        }

        unset($_arrayUserJSONFiles);
        unset($_jsonFile);
    }

    private function failAuth() {
        header('WWW-Authenticate: Basic realm="' . $GLOBALS['strProductName'] . '"');
        header('HTTP/1.0 401 Unauthorized');
        echo "You need to enter a valid username and password.";
        exit();
    }
    
    private function checkServerValue($_value) {
        if (!isset($_SERVER[$_value]) || $_SERVER[$_value] === '' || $_SERVER[$_value] === null || empty($_SERVER[$_value])) {
            return null;
        }
        else {
            return $_SERVER[$_value];
        }
    }
    
}
?>
