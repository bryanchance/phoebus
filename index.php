<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Setup | ===============================================================

// This has to be defined using the function at runtime because it is based
// on a variable. However, constants defined with the language construct
// can use this constant by some strange voodoo. Keep an eye on this.
// NOTE: DOCUMENT_ROOT does NOT have a trailing slash.
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);

// Define basic constants for the application
const SOFTWARE_NAME = 'Phoebus';
const SOFTWARE_VERSION = '2.0.0a1';
const DATASTORE_RELPATH = '/datastore/';
const OBJ_RELPATH = '/.obj/';
const COMPONENTS_RELPATH = '/components/';
const MODULES_RELPATH = '/modules/';
const LIB_RELPATH = '/lib/';

// Define components
// Components are considered to be the main code that drives the site and
// do the direct work of calling modules and outputting content
const COMPONENTS = array(
  'api' => ROOT_PATH . COMPONENTS_RELPATH . 'api/placeholder.txt',
  'aus' => ROOT_PATH . COMPONENTS_RELPATH . 'aus/placeholder.txt',
  'discover' => ROOT_PATH . COMPONENTS_RELPATH . 'discover/discoverPane.php',
  'panel' => ROOT_PATH . COMPONENTS_RELPATH . 'panel/placeholder.txt',
  'site' => ROOT_PATH . COMPONENTS_RELPATH . 'site/addonSite.php',
  'special' => ROOT_PATH . COMPONENTS_RELPATH . 'special/placeholder.txt'
);

// Define modules
// Modules are largely independent and reusable chunks of code that do not
// directly output content. Libs are also considered modules for simplicity.
// The exception to this would be smarty.
const MODULES = array(
  'readManifest' => ROOT_PATH . MODULES_RELPATH . 'classReadManifest.php',
  'vc' => ROOT_PATH . MODULES_RELPATH . 'nsIVersionComparator.php',
  'smarty' => ROOT_PATH . LIB_RELPATH . 'smarty/Smarty.class.php',
  'rdf' => ROOT_PATH . LIB_RELPATH . 'rdf/RdfComponent.php',
  'sql' => ROOT_PATH . LIB_RELPATH . 'safemysql/safemysql.class.php'
);

// Define the target applications that the site will accomidate with
// the enabled site features
const TARGET_APPLICATION_SITE = array(
  'palemoon' => array(
    'enabled' => true,
    'name' => 'Pale Moon - Add-ons',
    'domain' => array(
      'live' => 'addons.palemoon.org',
      'dev' => 'addons-dev.palemoon.org'
    ),
    'features' => array(
      'https', 'extensions', 'extensions-cat', 'themes',
      'language-packs', 'search-plugins'
    )
  ),
  'basilisk' => array(
    'enabled' => true,
    'name' => 'Basilisk: add-ons',
    'domain' => array(
      'live' => 'addons.basilisk-browser.org',
      'dev' => 'addons-dev.basilisk-browser.org'
    ),
    'features' => array(
      'https', 'extensions', 'themes', 'search-plugins'
    )
  ),
  'borealis' => array(
    'enabled' => false,
    'name' => 'Add-ons - Borealis - Binary Outcast',
    'domain' => array(
      'live' => 'borealis-addons.binaryoutcast.com',
      'dev' => null
    ),
    'features' => array(
      'extensions', 'themes', 'search-plugins'
    )
  ),
);

// Define Application IDs
// Application IDs are normally in the form of a GUID, however, they
// can be in the form of a user@host ID as well.
// Basilisk/Firefox have the same ID
// FossaMail/Thunderbird have the same ID
const TARGET_APPLICATION_ID = array(
  // MCP
  'palemoon' => '{8de7fcbb-c55c-4fbe-bfc5-fc555c87dbc4}',
  'basilisk' => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
  'fossamail' => '{3550f703-e582-4d05-9a08-453d09bdfdc6}',
  // BinOC
  'borealis' => '{a3210b97-8e8a-4737-9aa0-aa0e607640b9}',
  // Mozilla
  'firefox' => '{ec8030f7-c20a-464f-9b0e-13a3a9e97384}',
  'thunderbird' => '{3550f703-e582-4d05-9a08-453d09bdfdc6}',
  'seamonkey' => '{92650c4d-4b8e-4d2a-b7eb-24ecf4f6b63a}',
  'fennic-xul' => '{a23983c0-fd0e-11dc-95ff-0800200c9a66}',
  'fennic-native' => '{aa3c5121-dab2-40e2-81ca-7ea25febc110}',
  'sunbird' => '{718e30fb-e89b-41dd-9da7-e25a45638b28}',
  // Adblock Plus
  'adblock-browser' => '{55aba3ac-94d3-41a8-9e25-5c21fe874539}',
  // Common
  'toolkit' => 'toolkit@mozilla.org'
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
  print('<h2>' . SOFTWARE_NAME . ' ' . SOFTWARE_VERSION . '</h2>');

  switch($_mode) {
    case 0:
      print('<p class="pulseText" style="text-decoration: blink;"><strong>Fatal Error</strong></p>');
      print('<ul><li>' . $_value . '</li></ul>');
      break;
    case 1:
      print('<p>Output:</p>');
      print('<pre>' . json_encode($_value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</pre>');
      break;
    case 2:
      print('<p>Output:</p>');
      print('<pre>' . var_export($_value, true) . '</pre>');
      break;
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
    
    if ($_value == '404' || $_value == '501') {
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
* @returns          true if substring exists in string else false
******************************************************************************/

function startsWith($haystack, $needle) {
   $length = strlen($needle);
   return (substr($haystack, 0, $length) === $needle);
}

// ----------------------------------------------------------------------------

function endsWith($haystack, $needle) {
  $length = strlen($needle);
  if ($length == 0) {
    return true;
  }

  return (substr($haystack, -$length) === $needle);
}

// ----------------------------------------------------------------------------

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

// Define an array that will hold the current application state
$arraySoftwareState = array(
  'currentApplication' => null,
  'orginalApplication' => null,
  'currentName' => null,
  'currentDomain' => null,
  'debugMode' => null,
  'phpServerName' => $_SERVER['SERVER_NAME'],
  'phpRequestURI' => $_SERVER['REQUEST_URI'],
  'requestComponent' => funcHTTPGetValue('component'),
  'requestPath' => funcHTTPGetValue('path'),
  'requestApplication' => funcHTTPGetValue('application')
);

// ============================================================================

// == | Main | ================================================================

// Decide which application by domain that the software will be serving
// and if debug is enabled
foreach (TARGET_APPLICATION_SITE as $_key => $_value) {
  if ($arraySoftwareState['phpServerName'] == $_value['domain']['live']) {
    $arraySoftwareState['currentApplication'] = $_key;
    $arraySoftwareState['currentDomain'] = $_value['domain']['live'];
  }
  elseif ($arraySoftwareState['phpServerName'] == $_value['domain']['dev']) {
    $arraySoftwareState['currentApplication'] = $_key;
    $arraySoftwareState['currentDomain'] = $_value['domain']['dev'];
    $arraySoftwareState['debugMode'] = true;
  }

  if ($arraySoftwareState['currentApplication']) {
    break;
  }
}

// Override currentApplication by query
// If requestApplication is set and it exists in the array constant check if it is
// enabled and if so set the currentApplication to that
if ($arraySoftwareState['requestApplication'] &&
    array_key_exists($arraySoftwareState['requestApplication'], TARGET_APPLICATION_SITE) &&
    TARGET_APPLICATION_SITE[$arraySoftwareState['requestApplication']]['enabled']) {
    $arraySoftwareState['orginalApplication'] = $arraySoftwareState['currentApplication'];
    $arraySoftwareState['currentApplication'] = $arraySoftwareState['requestApplication'];
    $arraySoftwareState['requestApplication'] = null;
}

// If there is no valid currentApplication or currentDomain
// or if requestApplication is still set then error out
if (!$arraySoftwareState['currentApplication'] ||
    !$arraySoftwareState['currentDomain'] ||
    $arraySoftwareState['requestApplication'] ||
    ($arraySoftwareState['currentApplication'] == $arraySoftwareState['orginalApplication'])) {
  funcError('Invalid domain or application');
}

// ----------------------------------------------------------------------------

// Set entry points for URI based components
// Root (/) won't set a component or path
if ($arraySoftwareState['phpRequestURI'] == '/') {
  $arraySoftwareState['requestComponent'] = 'site';
  $arraySoftwareState['requestPath'] = '/';
}
// The SPECIAL component overrides the SITE component
elseif (startsWith($arraySoftwareState['phpRequestURI'], '/special/')) {
  $arraySoftwareState['requestComponent'] = 'special';
}
// requestPath should NEVER be set if the component isn't SITE
elseif ($arraySoftwareState['requestComponent'] != 'site' &&
        $arraySoftwareState['requestPath']) {
  funcSendHeader('404');
}

// ----------------------------------------------------------------------------

// Load component based on requestComponent
if ($arraySoftwareState['requestComponent'] &&
    array_key_exists($arraySoftwareState['requestComponent'], COMPONENTS)) {
  //funcError($arraySoftwareState['requestComponent'], 1);
  require_once(COMPONENTS[$arraySoftwareState['requestComponent']]);
}
else {
  if (!$arraySoftwareState['debugMode']) {
    funcSendHeader('404');
  }
  funcError('Unknown or non-existant component');
}

// ============================================================================

?>