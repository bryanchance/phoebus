<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | classReadManifest | ===========================================================================================

class classReadManifest {
  private $currentApplication;
  private $currentAppID;

  // ------------------------------------------------------------------------------------------------------------------

  // The current category slugs
  const EXTENSION_CATEGORY_SLUGS = array(
    'alerts-and-updates' => 'Alerts &amp; Updates',
    'appearance' => 'Appearance',
    'bookmarks-and-tabs' => 'Bookmarks &amp; Tabs',
    'download-management' => 'Download Management',
    'feeds-news-and-blogging' => 'Feeds, News, &amp; Blogging',
    'privacy-and-security' => 'Privacy &amp; Security',
    'search-tools' => 'Search Tools',
    'social-and-communication' => 'Social &amp; Communication',
    'tools-and-utilities' => 'Tools &amp; Utilities',
    'web-development' => 'Web Development',
    'other' => 'Other'
  );

  // ------------------------------------------------------------------------------------------------------------------

  const SEARCH_PLUGINS_DB = array(
      'search-100' => 'google.xml',
      'search-101' => 'youtube.xml',
      'search-102' => 'google-play.xml',
      'search-103' => 'ask.xml',
      'search-104' => 'merriam-webster.xml',
      'search-105' => 'facebook.xml',
      'search-106' => 'abbreviations-com.xml',
      'search-107' => 'accuweather.xml',
      'search-108' => 'amazon-com.xml',
      'search-109' => 'amazon-co-uk.xml',
      'search-110' => 'baidu.xml',
      'search-111' => 'dictionary-com.xml',
      'search-112' => 'dogpile.xml',
      'search-113' => 'ebay.xml',
      'search-114' => 'european-search-engine.xml',
      'search-115' => 'imdb.xml',
      'search-116' => 'imgur.xml',
      'search-117' => 'ixquick.xml',
      'search-118' => 'openstreetmap.xml',
      'search-119' => 'pale-moon-add-ons-site.xml',
      'search-120' => 'pale-moon-forum.xml',
      'search-121' => 'pcnet.xml',
      'search-122' => 'qwant.xml',
      'search-123' => 'reference-com.xml',
      'search-124' => 'searx.xml',
      'search-125' => 'startpage.xml',
      'search-126' => 'the-online-slang-dictionary.xml',
      'search-127' => 'the-weather-channel.xml',
      'search-128' => 'tumblr.xml',
      'search-129' => 'urban-dictionary.xml',
      'search-130' => 'webopedia.xml',
      'search-131' => 'wiktionary.xml',
      'search-132' => 'yandex.xml',
    //'search-133' => 'pale-moon-add-ons-google.xml'
    );

  // ------------------------------------------------------------------------------------------------------------------

  const LICENSES = array(
      'Apache-2.0' => 'Apache License 2.0',
      'Apache-1.1' => 'Apache License 1.1',
      'BSD-3-Clause' => 'BSD 3-Clause',
      'BSD-2-Clause' => 'BSD 2-Clause',
      'GPL-3.0' => 'GNU General Public License 3.0',
      'GPL-2.0' => 'GNU General Public License 2.0',
      'LGPL-3.0' => 'GNU Lesser General Public License 3.0',
      'LGPL-2.1' => 'GNU Lesser General Public License 2.1',
      'AGPL-3.0' => 'GNU Affero General Public License v3',
      'MIT' => 'MIT License',
      'MPL-2.0' => 'Mozilla Public License 2.0',
      'MPL-1.1' => 'Mozilla Public License 1.1',
      'Custom' => 'Custom License',
      'PD' => 'Public Domain',
      'COPYRIGHT' => ''
    );

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {  
    if (!funcCheckModule('database')) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - database is required to be included in the global scope');
    }
    
    // Assign currentApplication
    $this->currentApplication = $GLOBALS['arraySoftwareState']['currentApplication'];
    $this->currentAppID = TARGET_APPLICATION_ID[$GLOBALS['arraySoftwareState']['currentApplication']];
  }

 /********************************************************************************************************************
  * Gets a single reduced manifest for an add-on by ID
  * 
  * @param $_addonID        Add-on ID either GUID or user@host
  * @returns                reduced add-on manifest or null
  ********************************************************************************************************************/
  public function getAddonByID($_addonID) {
    $query = "
      SELECT `id`, `slug`, `type`, `releaseXPI`, `reviewed`, `active`, `xpinstall`
      FROM `addon`
      JOIN `client` ON addon.id = client.addonID
      WHERE ?n = 1
      AND `id` = ?s
      AND `type` IN ('extension', 'theme', 'langpack')
    ";
    $queryResult = $GLOBALS['moduleDatabase']->query('row', $query, $this->currentApplication, $_addonID);

    if (!$queryResult) {
      return null;
    }
    
    $addonManifest = $this->funcProcessManifest($queryResult, null, true);
    
    if (!$addonManifest) {
      return null;
    }

    return $addonManifest;
  }

 /********************************************************************************************************************
  * Gets a single manifest for an add-on by slug
  * 
  * @param $_addonSlug      Add-on slug
  * @returns                add-on manifest or null
  ********************************************************************************************************************/
  public function getAddonBySlug($aAddonSlug,
                                 $aProcessContent = true,
                                 $aReturnInactive = null,
                                 $aReturnUnreviewed = true) { 
    $query = "
      SELECT addon.*, `license` AS `licenseCode`
      FROM `addon`
      JOIN `client` ON addon.id = client.addonID
      WHERE ?n = 1
      AND `slug` = ?s
      AND `type` IN ('extension', 'theme', 'langpack')
    ";
    $queryResult = $GLOBALS['moduleDatabase']->query('row', $query, $this->currentApplication, $aAddonSlug);
    
    if (!$queryResult) {
      return null;
    }
  
    $addonManifest = $this->funcProcessManifest($queryResult,
                                                $aReturnInactive,
                                                $aReturnUnreviewed,
                                                $aProcessContent);
    
    if (!$addonManifest) {
      return null;
    }

    return $addonManifest;
  }

 /********************************************************************************************************************
  * Gets a single reduced manifest for an add-on by ID
  * 
  * @param $_addonID        Add-on ID either GUID or user@host
  * @returns                reduced add-on manifest or null
  ********************************************************************************************************************/
  public function getPanelAddonByID($_addonID) {
    $query = "
      SELECT `id`, `slug`, `type`, `releaseXPI`, `reviewed`, `active`, `xpinstall`
      FROM `addon`
      AND `id` = ?s
      AND `type` IN ('extension', 'theme', 'langpack')
    ";
    $queryResult = $GLOBALS['moduleDatabase']->query('row', $query, $_addonID);

    if (!$queryResult) {
      return null;
    }

    $addonManifest = $this->funcProcessManifest($queryResult, true, true, null);

    if (!$addonManifest) {
      return null;
    }

    return $addonManifest;
  }

 /********************************************************************************************************************
  * Gets a single manifest for an external by slug
  * 
  * @param $_addonSlug      Add-on ID either GUID or user@host
  * @returns                add-on manifest or null
  ********************************************************************************************************************/
  public function getPanelAddonBySlug($aAddonSlug) { 
    $query = "SELECT addon.*
              FROM `addon`
              WHERE `slug` = ?s
              AND `type` IN ('extension', 'theme', 'langpack', 'external')";

    $queryResult = $GLOBALS['moduleDatabase']->query('row', $query, $aAddonSlug);
    
    if (!$queryResult) {
      return null;
    }

    $addonManifest = $this->funcProcessManifest($queryResult, true, true, null);
    
    if (!$addonManifest) {
      return null;
    }

    return $addonManifest;
  }

 /********************************************************************************************************************
  * Method to replace a bunch of methods that are virtually identical
  * Mostly those that get an indexed array of manifests
  * 
  * @param $_queryType      Type of query to be performed
  * @param $_queryData      Data for the query such as slugs or search terms
  * @returns                indexed array of manifests or null
  ********************************************************************************************************************/
  public function getAddons($_queryType, $_queryData = null) {
    $query = null;
    $returnInactive = null;
    $returnUnreviewed = null;
    $processContent = true;

    switch ($_queryType) {
      case 'site-addons-by-category':
        $query = "
          SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active`, `license` AS `licenseCode`
          FROM `addon`
          JOIN `client` ON addon.id = client.addonID
          WHERE ?n = 1
          AND `category` = ?s
          AND NOT `category` = 'unlisted'
          ORDER BY `name`
        ";
        $queryResults = $GLOBALS['moduleDatabase']->query('rows', $query, $this->currentApplication, $_queryData);
        break;
      case 'site-all-extensions':
        $query = "
          SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active`, `license` AS `licenseCode`
          FROM `addon`
          JOIN `client` ON addon.id = client.addonID
          WHERE ?n = 1
          AND `type` IN ('extension', 'external')
          AND NOT `category` IN ('unlisted', 'themes', 'langpack')
          ORDER BY `name`
        ";
        $queryResults = $GLOBALS['moduleDatabase']->query('rows', $query, $this->currentApplication);
        break;
      case 'site-search':
        $query = "
          SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active`, `license` AS `licenseCode`
          FROM `addon`
          JOIN `client` ON addon.id = client.addonID
          WHERE ?n = 1
          AND `type` IN ('extension', 'theme', 'langpack')
          AND MATCH(`tags`) AGAINST(?s IN NATURAL LANGUAGE MODE)
        ";
        $queryResults = $GLOBALS['moduleDatabase']->query('rows', $query, $this->currentApplication, $_queryData);
        break;
      case 'api-search':
        $query = "
          SELECT `id`, `slug`, `type`, `creator`, `releaseXPI`, `name`, `homepageURL`, `description`,
                 `url`, `reviewed`, `active`, `license` AS `licenseCode`, `xpinstall`
          FROM `addon`
          JOIN `client` ON addon.id = client.addonID
          WHERE ?n = 1
          AND `type` IN ('extension', 'theme', 'langpack')
          AND MATCH(`tags`) AGAINST(?s IN NATURAL LANGUAGE MODE)
        ";
        $queryResults = $GLOBALS['moduleDatabase']->query('rows', $query, $this->currentApplication, $_queryData);
        break;
      case 'api-get':
        $query = "
          SELECT `id`, `slug`, `type`, `creator`, `releaseXPI`, `name`, `homepageURL`, `description`,
                 `url`, `reviewed`, `active`, `xpinstall`
          FROM `addon`
          JOIN `client` ON addon.id = client.addonID
          WHERE ?n = 1
          AND `id` IN (?a)
          AND `type` IN ('extension', 'theme', 'langpack')
        ";
        $queryResults = $GLOBALS['moduleDatabase']->query('rows', $query, $this->currentApplication, $_queryData);
        break;
      case 'panel-user-addons':
        $returnInactive = true;
        $returnUnreviewed = true;
        $processContent = null;
        $query = "
          SELECT `id`, `slug`, `type`, `name`, `url`, `reviewed`, `active`
          FROM `addon`
          WHERE `slug` IN (?a)
          AND `type` IN ('extension', 'theme')
          ORDER BY `name`
        ";
        $queryResults = $GLOBALS['moduleDatabase']->query('rows', $query, $_queryData);
        break;
      case 'panel-all-addons':
        $returnInactive = true;
        $returnUnreviewed = true;
        $processContent = null;
        $query = "
          SELECT `id`, `slug`, `type`, `name`, `category`, `url`, `reviewed`, `active`
          FROM `addon`
          ORDER BY `name`
        ";
        $queryResults = $GLOBALS['moduleDatabase']->query('rows', $query);
        break;
      case 'panel-addons-by-type':
        $returnInactive = true;
        $returnUnreviewed = true;
        $processContent = null;
        $query = "
          SELECT `id`, `slug`, `type`, `name`, `category`, `url`, `reviewed`, `active`
          FROM `addon`
          WHERE `type` = ?s
          ORDER BY `name`
        ";
        $queryResults = $GLOBALS['moduleDatabase']->query('rows', $query, $_queryData);
        break;
      default:
        funcError(__CLASS__ . '::' . __FUNCTION__ . ' - Unknown query type');
    }

    if (!$queryResults) {
      return null;
    }

    $manifestData = array();
    
    foreach($queryResults as $_value) {
      $addonManifest = $this->funcProcessManifest(
        $_value, $returnInactive, $returnUnreviewed, $processContent
      );

      if (!$addonManifest) {
        continue;
      }

      $manifestData[] = $addonManifest;
    }

    return $manifestData;
  }

 /********************************************************************************************************************
  * Gets an indexed array of simplified/legacy search engine manifests
  * XXX: This function has insufficient error checking
  * XXX: This should be converted to SQL
  * 
  * @returns                indexed array of manifests or null
  ********************************************************************************************************************/
  public function getSearchPlugins($_listOnly = null) {
    $datastorePath = ROOT_PATH . DATASTORE_RELPATH . '/searchplugins/';
    $arraySearchPlugins = array();

    $searchPluginsDB = self::SEARCH_PLUGINS_DB;

    asort($searchPluginsDB, SORT_NATURAL);

    foreach ($searchPluginsDB as $_key => $_value) {
      $arraySearchPluginXML = simplexml_load_file($datastorePath . $_value);
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['type'] = 'search-plugin';
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['id'] = $_key;
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['name'] = (string)$arraySearchPluginXML->ShortName;
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['slug'] = substr($_value, 0, -4);
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['icon'] = (string)$arraySearchPluginXML->Image;
    }

    return $arraySearchPlugins;
  }

 /********************************************************************************************************************
  * Internal method to post-process an add-on manifest
  * 
  * @param $addonManifest       add-on manifest
  * @param $returnInactive      Optional, return inactive add-on instead of null
  * @param $returnUnreviewed    Optional, return unreviewed add-on instead of null
  * @returns                    add-on manifest or null
  ********************************************************************************************************************/
  // This is where we do any post-processing on an Add-on Manifest
  private function funcProcessManifest($addonManifest,
                                       $returnInactive = null,
                                       $returnUnreviewed = null,
                                       $processContent = true) {
    // Cast the int-strings to bool
    $addonManifest['reviewed'] = (bool)$addonManifest['reviewed'];
    $addonManifest['active'] = (bool)$addonManifest['active'];

    if (!$addonManifest['active'] && !$returnInactive) {
      return null;
    }
    
    if (!$addonManifest['reviewed'] && !$returnUnreviewed) {
      return null;
    }

    // Actions on xpinstall key
    if (array_key_exists('xpinstall', $addonManifest)) {
      // JSON Decode xpinstall
      $addonManifest['xpinstall'] = json_decode($addonManifest['xpinstall'], true);

      // We need to perform some minor post processing on XPInstall
      foreach ($addonManifest['xpinstall'] as $_key => $_value) {
        // Remove entries that are not compatible with the current application
        if (!array_key_exists($this->currentAppID, $addonManifest['xpinstall'][$_key]['targetApplication'])) {
          unset($addonManifest['xpinstall'][$_key]);
          continue;
        }

        // Set a human readable date based on epoch
        $addonManifest['xpinstall'][$_key]['date'] = date('F j, Y' ,$addonManifest['xpinstall'][$_key]['epoch']);
      }

      // Ensure that the xpinstall keys are reverse sorted using an anonymous function and a spaceship
      uasort($addonManifest['xpinstall'], function ($_xpi1, $_xpi2) {
        return $_xpi2['epoch'] <=> $_xpi1['epoch'];
      });
    }

    // Remove whitespace from description and html encode
    if ($addonManifest['description'] ?? false) {
      $addonManifest['description'] = htmlentities(trim($addonManifest['description']), ENT_XHTML);
    }

    // If content exists, process it
    if ($processContent && array_key_exists('content', $addonManifest)) {
      // Check to ensure that there really is content
      $addonManifest['content'] = funcUnifiedVariable('var', $addonManifest['content']);

      // Process content or assign description to it
      if ($addonManifest['content'] != null) {
        $addonManifest['content'] = $this->funcProcessContent($addonManifest['content']);
      }
      else {
        $addonManifest['content'] = $addonManifest['description'];
      }
    }

    // Process license
    if (array_key_exists('license', $addonManifest)) {
      $addonManifest = $this->funcProcessLicense($addonManifest);
    }
    
    // Truncate description if it is too long..
    if (array_key_exists('description', $addonManifest) && strlen($addonManifest['description']) >= 235) {
      $addonManifest['description'] = substr($addonManifest['description'], 0, 230) . '&hellip;';
    }

    // Set baseURL if applicable
    if ($addonManifest['type'] != 'external') {
      $addonManifest['baseURL'] =
        'http://' .
        $GLOBALS['arraySoftwareState']['currentDomain'] .
        '/?component=download&version=latest&id=';
    }

    // Set Datastore Paths     
    if ($addonManifest['type'] == 'external') {
      // Extract the legacy external id
      $_oldID = preg_replace('/(.*)\@(.*)/iU', '$2', $addonManifest['id']);

      // Set basePath
      $addonManifest['basePath'] =
        '.' . DATASTORE_RELPATH . 'addons/' . $_oldID . '/';

      // Set reletive url paths
      $_addonPath = substr($addonManifest['basePath'], 1);
      $_defaultPath = str_replace($_oldID, 'default', $_addonPath);
    }
    else {
      // Set basePath
      $addonManifest['basePath'] =
        '.' . DATASTORE_RELPATH . 'addons/' . $addonManifest['slug'] . '/';

      // Set reletive url paths
      $_addonPath = substr($addonManifest['basePath'], 1);
      $_defaultPath = str_replace($addonManifest['slug'], 'default', $_addonPath);
    }

    // We want to not have to hit this unless we are coming from the SITE
    if (array_key_exists('name', $addonManifest)) {
      // Detect Icon
      if (file_exists($addonManifest['basePath'] . 'icon.png')) {
        $addonManifest['icon'] = $_addonPath . 'icon.png';
      }
      else {
        $addonManifest['icon'] = $_defaultPath . 'icon.png';
      }

      // Detect Preview
      if (file_exists($addonManifest['basePath'] . 'preview.png')) {
        $addonManifest['preview'] = $_addonPath . 'preview.png';
        $addonManifest['hasPreview'] = true;
      }
      else {
        $addonManifest['preview'] = $_defaultPath . 'preview.png';
        $addonManifest['hasPreview'] = false;
      }
    }

    // Return Add-on Manifest to internal caller
    return $addonManifest;
  }

 /********************************************************************************************************************
  * Internal (most of the time) method to process "phoebus.content"
  * 
  * @param $_addonPhoebusContent    raw "phoebus.content"
  * @returns                        processed "phoebus.content"
  ********************************************************************************************************************/
  public function funcProcessContent($_addonPhoebusContent) {     
    // html encode phoebus.content
    $_addonPhoebusContent = htmlentities($_addonPhoebusContent, ENT_XHTML);

    // Replace new lines with <br />
    $_addonPhoebusContent = nl2br($_addonPhoebusContent, true);

    // create an array that contains the strs to pseudo-bbcode to real html
    $_arrayPhoebusCode = array(
      'simple' => array(
        '[b]' => '<strong>',
        '[/b]' => '</strong>',
        '[i]' => '<em>',
        '[/i]' => '</em>',
        '[u]' => '<u>',
        '[/u]' => '</u>',
        '[ul]' => '</p><ul><fixme />',
        '[/ul]' => '</ul><p><fixme />',
        '[ol]' => '</p><ol><fixme />',
        '[/ol]' => '</ol><p><fixme />',
        '[li]' => '<li>',
        '[/li]' => '</li>',
        '[section]' => '</p><h3>',
        '[/section]' => '</h3><p><fixme />'
      ),
      'complex' => array(
        '\<(ul|\/ul|li|\/li|p|\/p)\><br \/>' => '<$1>',
        '\[url=(.*)\](.*)\[\/url\]' => '<a href="$1" target="_blank">$2</a>',
        '\[url\](.*)\[\/url\]' => '<a href="$1" target="_blank">$1</a>',
        '\[img(.*)\](.*)\[\/img\]' => ''
      )
    );

    // str replace pseudo-bbcode with real html
    foreach ($_arrayPhoebusCode['simple'] as $_key => $_value) {
      $_addonPhoebusContent = str_replace($_key, $_value, $_addonPhoebusContent);
    }
    
    // Regex replace pseudo-bbcode with real html
    foreach ($_arrayPhoebusCode['complex'] as $_key => $_value) {
      $_addonPhoebusContent = preg_replace('/' . $_key . '/iU', $_value, $_addonPhoebusContent);
    }

    // Less hacky than what is in funcReadManifest
    // Remove linebreak special cases
    $_addonPhoebusContent = str_replace('<fixme /><br />', '', $_addonPhoebusContent);

    return $_addonPhoebusContent;
  }

 /********************************************************************************************************************
  * Internal method to process "phoebus.content"
  * 
  * @param $addonManifest    add-on manifest
  * @returns                 add-on manifest with additional license metadata
  ********************************************************************************************************************/
  private function funcProcessLicense($addonManifest) {
    // Approved Licenses  
    $_arrayLicenses = array_change_key_case(self::LICENSES, CASE_LOWER);
    $_arrayLicenses['copyright'] = '&copy; ' . date("Y") . ' - ' . $addonManifest['creator'];
     
    // Set to lowercase
    if ($addonManifest['license'] != null) {
      $addonManifest['license'] = strtolower($addonManifest['license']);
    }

    // phoebus.license trumps all
    // If existant override any license* keys and load the file into the manifest
    if ($addonManifest['licenseText'] != null) {
      $addonManifest['license'] = 'custom';
      $addonManifest['licenseName'] = $_arrayLicenses[$addonManifest['license']];
      $addonManifest['licenseDefault'] = null;
      $addonManifest['licenseURL'] = null;

      return $addonManifest;
    }

    // If license is not set then default to copyright
    if ($addonManifest['license'] == null) {
      $addonManifest['license'] = 'copyright';
      $addonManifest['licenseName'] = $_arrayLicenses[$addonManifest['license']];
      $addonManifest['licenseDefault'] = true;

      return $addonManifest;
    }

    if ($addonManifest['license'] != null) {
      if ($addonManifest['license'] == 'custom' &&
        startsWith($addonManifest['licenseURL'], 'http')) {
        $addonManifest['license'] = 'custom';
        $addonManifest['licenseName'] = $_arrayLicenses[$addonManifest['license']];

        return $addonManifest;
      }
      elseif (array_key_exists($addonManifest['license'], $_arrayLicenses)) {
        $addonManifest['licenseName'] =
          $_arrayLicenses[$addonManifest['license']];
        $addonManifest['licenseDefault'] = null;
        $addonManifest['licenseURL'] = null;
        $addonManifest['licenseText'] = null;

        return $addonManifest;
      }
      else {
        $addonManifest['license'] = 'unknown';
        $addonManifest['licenseName'] = 'Unknown License';
        $addonManifest['licenseDefault'] = null;
        $addonManifest['licenseURL'] = null;
        $addonManifest['licenseText'] = null;
        
        return $addonManifest;
      }
    }
  }
}

// ====================================================================================================================

?>