<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Basic Setup | =========================================================

// This has to be defined using the function at runtime because it is based
// on a variable. However, constants defined with the language construct
// can use this constant with by some strange voodoo. Keep an eye on this.
// Additionally, Using the define function can have a performance impact.
// NOTE: DOCUMENT_ROOT does NOT have a trailing slash.
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

// Define basic constants for the application
const APPLICATION_NAME = 'Phoebus';
const APPLICATION_VERSION = '2.0.0a1';
const DATASTORE_RELPATH = '/datastore/';
const OBJ_RELPATH = '/.obj/';
const COMPONENTS_RELPATH = '/components/';
const MODULES_RELPATH = '/modules/';
const LIB_RELPATH = '/lib/';

// Define components
const COMPONENTS = array(
  'api' => ROOT_PATH . COMPONENTS_RELPATH . 'api/src/placeholder.txt',
  'aus' => ROOT_PATH . COMPONENTS_RELPATH . 'aus/src/placeholder.txt',
  'discover' => ROOT_PATH . COMPONENTS_RELPATH . 'discover/src/placeholder.txt',
  'panel' => ROOT_PATH . COMPONENTS_RELPATH . 'panel/src/placeholder.txt',
  'site' => ROOT_PATH . COMPONENTS_RELPATH . 'site/src/placeholder.txt',
  'special' => ROOT_PATH . COMPONENTS_RELPATH . 'special/src/placeholder.txt'
);

// Define modules
const MODULES = array(
  'readManifest' => ROOT_PATH . MODULES_RELPATH . 'classReadManifest.php',
  'vc' => ROOT_PATH . MODULES_RELPATH . 'nsIVersionComparator.php',
  'smarty' => ROOT_PATH . LIB_RELPATH . 'smarty/Smarty.class.php',
  'rdf' => ROOT_PATH . LIB_RELPATH . 'rdf/RdfComponent.php',
  'sql' => ROOT_PATH . LIB_RELPATH . 'safemysql/safemysql.class.php'
);

// ============================================================================

// == | Functions | ===========================================================

/******************************************************************************
* Error function that will display data (Error Message) as an html page
*
* @param $_value    Data to be printed
* @param #_mode     Optional integer to change how data is printed
                    0: Default, just print $_value as-is
                    1: Print #_value as a JSON encoded string
                    2: Print $_value as valid php code
******************************************************************************/
function funcError($_value, $_mode = 0) {
  ob_get_clean();
  header('Content-Type: text/html', false);   
  print(file_get_contents('./components/special/skin/default/template-header.xhtml'));
  print('<h2>' . $GLOBALS['strProductName'] . ' ' . $GLOBALS['strApplicationVersion'] . '</h2>');

  switch($_mode) {
    case 0:
      print('<p class="pulseText" style="text-decoration: blink;"><strong>Fatal Error</strong></p>');
      print('<ul><li>' . $_value . '</li></ul>');
    case 1:
      print('<p2>Output:</p>');
      print('<pre>' . json_encode($_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</pre>');
    case 2:
      print('<p2>Output:</p>');
      print('<pre>' . var_export($_value, true) . '</pre>');
  }

  print(file_get_contents('./components/special/skin/default/template-footer.xhtml'));
  
  // We are done here
  exit();
}

/******************************************************************************
* Gets an HTTP GET request value and performs basic checks and filtering
*
* @param $_value    HTTP GET argument
* @returns          Value of HTTP GET argument or null
******************************************************************************/
function funcHTTPGetValue($_value) {
  if (!isset($_GET[$_value]) || $_GET[$_value] === '' ||
    $_GET[$_value] === null || empty($_GET[$_value])) {
    return null;
  }
  else {
    $_finalValue =
      preg_replace('/[^-a-zA-Z0-9_\-\/\{\}\@\.\%\s]/', '', $_GET[$_value]);
    return $_finalValue;
  }
}

/******************************************************************************
* Check if an /existing/ variable has a value
*
* @param $_value    Any existing variable
* @returns          Passed data or null
******************************************************************************/
function funcCheckVar($_value) {
  if ($_value === '' || $_value === 'none' || $_value === null || empty($_value)) {
    return null;
  }
  else {
    return $_value;
  }
}

/******************************************************************************
* Sends HTTP Headers to client using a short name
*
* @param $_value    Short name of header
******************************************************************************/
function funcSendHeader($_value) {
  $_arrayHeaders = array(
    '404' => 'HTTP/1.0 404 Not Found',
    '501' => 'HTTP/1.0 501 Not Implemented',
    'html' => 'Content-Type: text/html',
    'text' => 'Content-Type: text/plain',
    'xml' => 'Content-Type: text/xml',
    'css' => 'Content-Type: text/css',
    'phoebus' => 'X-Phoebus: https://github.com/Pale-Moon-Addons-Team/phoebus/',
  );
  
  if (array_key_exists($_value, $_arrayHeaders)) {
    header($_arrayHeaders['phoebus']);
    header($_arrayHeaders[$_value]);
    
    if ($_value == '404') {
      // We are done here
      exit();
    }
  }
  else {
    // Fallback to text
    header($_arrayHeaders['text']);
  }
}

/******************************************************************************
* Sends HTTP Header to redirect the client to another URL
*
* @param $_strURL   URL to redirect to
******************************************************************************/
// This function sends a redirect header
function funcRedirect($_strURL) {
	header('Location: ' . $_strURL , true, 302);
  
  // We are done here
  exit();
}

/******************************************************************************
* Polyfills for missing functions
* startsWith, endsWith, contains
*
* @param $haystack  string
* @param $needle    substring
******************************************************************************/

function startsWith($haystack, $needle) {
   $length = strlen($needle);
   return (substr($haystack, 0, $length) === $needle);
}

function endsWith($haystack, $needle) {
  $length = strlen($needle);
  if ($length == 0) {
    return true;
  }

  return (substr($haystack, -$length) === $needle);
}

function contains($haystack, $needle) {
  if (strpos($haystack, $needle) > -1) {
    return true;
  }
  else {
    return false;
  }
}

// ============================================================================

// == | Vars | ================================================================

$constants = get_defined_constants(true);

// ============================================================================

// == | Main | ================================================================

funcError($constants['user'], 1);

// ============================================================================
?>