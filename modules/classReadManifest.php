<?php
// == | classReadManifest | ===================================================

// Coding rules for classes are as follows:
// private methods use the global func* prefix
// public methods do not use the func* prefix

class classReadManifest {
  // We want a prop that warning and error messages can be sent to
  public $addonErrors;
  private $addonSQL;
  private $addonInit;
  private $classSQL;
  
  const SQL_CATEGORY_SINGLE = "SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active` FROM `addon` WHERE `category` = ?s ORDER BY `name`";
  const SQL_CATEGORY_ALL_EXTENSIONS = "
    SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`,
           `active`
    FROM `addon`
    WHERE `type` = 'extension'
    AND NOT `category` = 'unlisted'
    OR (
      `type` = 'external'
      AND NOT
      `category` = 'theme'
    )
    ORDER BY `name`
  ";
  const SQL_SEARCH = "
    SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`,
           `active`
    FROM `addon`
    WHERE MATCH(`tags`)
    AGAINST(?s IN NATURAL LANGUAGE MODE)
  ";
  const SQL_ADDON_BASIC = "
    SELECT `id`, `slug`, `type`, `creator`, `license`, `licenseText`,
           `licenseURL`, `releaseXPI`, `reviewed`, `active`, `xpinstall`
    FROM `addon`
    WHERE `id` = ?s
    AND NOT
    `type` = 'external'
  ";
  const SQL_ADDON_FULL = "
    SELECT *
    FROM `addon`
    WHERE `slug` = ?s
    AND NOT
    `type` = 'external'
  ";

  // ------------------------------------------------------------------------

  // Initalize class
  // You should put this in every public method entry point
  private function funcInit() {
    // Be sure to clear out errors in case we reuse the class
    $this->addonErrors = null;

    // These are the SQL Query strings we will use to accomplish basic functions
    $this->addonSQL = array(
      'categorySingle' =>
        "SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active` FROM `addon` WHERE `category` = ?s ORDER BY `name`",
      'searchResults' =>
        "SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active` FROM `addon` WHERE MATCH(`tags`) AGAINST(?s IN NATURAL LANGUAGE MODE)",
      'categoryAllExtensions' =>
        "SELECT `id`, `slug`, `type`, `name`, `description`, `url`, `reviewed`, `active` FROM `addon` WHERE `type` = 'extension' AND NOT `category` = 'unlisted' OR (`type` = 'external' AND NOT `category` = 'theme') ORDER BY `name`",
      'addonBasic' =>
        "SELECT `id`, `slug`, `type`, `creator`, `license`, `licenseText`, `licenseURL`, `releaseXPI`, `reviewed`, `active`, `xpinstall` FROM `addon` WHERE `id` = ?s AND NOT `type` = 'external'",
      'addonComplete' =>
        "SELECT * FROM `addon` WHERE `slug` = ?s AND NOT `type` = 'external'"
    );
    
    // Create a new instance of the SafeMysql class
    $this->classSQL = new SafeMysql($GLOBALS['arraySQLCreds']);
  }

  // ------------------------------------------------------------------------

  // gets an indexed array of manifests for a single category
  public function getSearchResults($_searchTerms) {
    // Initalize the class
    $this->funcInit();

    $searchManifest = array();

    $searchSQL = funcCheckVar(
      $this->classSQL->getAll(
        $this->addonSQL['searchResults'], $_searchTerms)
      );

    if ($searchSQL != null) {
      foreach ($searchSQL as $_value) {
        $addonManifest = $this->funcProcessManifest($_value);
        if ($addonManifest != null) {
          $searchManifest[] = $addonManifest;
        }
        else {
          continue;
        }
      }
    }
    else {
      return null;
    }
    
    return $searchManifest;
  }

  // ------------------------------------------------------------------------

  // gets an indexed array of manifests for a single category
  public function getCategory($_categorySlug) {
    // Initalize the class
    $this->funcInit();

    $categoryManifest = array();

    $categorySQL = funcCheckVar(
      $this->classSQL->getAll(
        $this->addonSQL['categorySingle'], $_categorySlug)
      );

    if ($categorySQL != null) {
      foreach ($categorySQL as $_value) {
        $addonManifest = $this->funcProcessManifest($_value);
        if ($addonManifest != null) {
          $categoryManifest[] = $addonManifest;
        }
        else {
          continue;
        }
      }
    }
    else {
      return null;
    }
    
    return $categoryManifest;
  }

  // ------------------------------------------------------------------------

  // gets an indexed array of manifests for a all extensions
  public function getAllExtensions() {
    // Initalize the class
    $this->funcInit();

    $categoryManifest = array();

    $categorySQL = funcCheckVar(
      $this->classSQL->getAll(
        $this->addonSQL['categoryAllExtensions'])
      );

    if ($categorySQL != null) {
      foreach ($categorySQL as $_value) {
        $addonManifest = $this->funcProcessManifest($_value);
        if ($addonManifest != null) {
          $categoryManifest[] = $addonManifest;
        }
        else {
          continue;
        }
      }
    }
    else {
      return null;
    }
    
    return $categoryManifest;
  }

  // ------------------------------------------------------------------------

  // Gets a single basic add-on manifest by ID
  public function getAddonByID($_addonID) {
    // Initalize the class
    $this->funcInit();

    $addonManifest = funcCheckVar(
      $this->classSQL->getRow(
        $this->addonSQL['addonBasic'], $_addonID)
      );

    if ($addonManifest != null) {
      $addonManifest = $this->funcProcessManifest($addonManifest);
    }
    else {
      $addonManifest = $this->funcAddonError(
        $_addonID, 'fatal', 'The add-on id does not exist in the database (or it is an external)'
      );
    }

    // Return Add-on Manifest to orginal caller
    return $addonManifest;
  }

  // ------------------------------------------------------------------------

  // Gets a single complete add-on manifest by Slug
  public function getAddonBySlug($_addonSlug) {
    // Initalize the class
    $this->funcInit();
    
    $addonManifest = funcCheckVar(
      $this->classSQL->getRow(SQL_ADDON_FULL, $_addonSlug)
    );
    
    if ($addonManifest != null) {
      $addonManifest = $this->funcProcessManifest($addonManifest);
    }
    else {
      $addonManifest = $this->funcAddonError(
        $_addonSlug, 'fatal', 'The add-on slug does not exist in the database (or it is an external)'
      );
    }
    
    // Assign errors to manifest
    if ($addonManifest != null) {
      $addonManifest['debug'] = $this->addonErrors;
    }
    
    // Return Add-on Manifest to orginal caller
    return $addonManifest;
    
  }

  // ------------------------------------------------------------------------

  public function getSearchPlugins($arraySearchPluginsDB) {
    $datastorePath = ROOT_PATH . DATASTORE_RELPATH . '/searchplugins/';
    $arraySearchPlugins = array();
    
    foreach ($arraySearchPluginsDB as $_key => $_value) {
      $arraySearchPluginXML = simplexml_load_file($datastorePath . $_value);
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['type'] = 'search-plugin';
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['id'] = $_key;
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['name'] = (string)$arraySearchPluginXML->ShortName;
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['slug'] = substr($_value, 0, -4);
      $arraySearchPlugins[(string)$arraySearchPluginXML->ShortName]['icon'] = (string)$arraySearchPluginXML->Image;
    }
    
    return $arraySearchPlugins;
  }

  // ------------------------------------------------------------------------

  // This is where we do any post-processing on an Add-on Manifest
  private function funcProcessManifest($addonManifest) {
    // Cast the int-strings to bool
    $addonManifest['reviewed'] = (bool)$addonManifest['reviewed'];
    $addonManifest['active'] = (bool)$addonManifest['active'];
    
    // Check to see if an add-on is reviewed and/or active
    // If not then don't return the add-on manifest
    if ($addonManifest['reviewed'] == false || $addonManifest['active'] == false) {
      return null;
    }
    
    // Actions on xpinstall key
    if (array_key_exists('xpinstall', $addonManifest) && $addonManifest['xpinstall'] != null) {
      // JSON Decode xpinstall
      $addonManifest['xpinstall'] = json_decode($addonManifest['xpinstall'], true);

      // Ensure that the xpinstall keys are reverse sorted
      uasort($addonManifest['xpinstall'], function ($_xpi1, $_xpi2) {
        return $_xpi2['mtime'] <=> $_xpi1['mtime'];
      });
    }

    // If content exists, process it
    if (array_key_exists('content', $addonManifest)) {
      // Check to ensure that there really is content
      $addonManifest['content'] = funcCheckVar($addonManifest['content']);

      // Process content or assign description to it
      if ($addonManifest['content'] != null) {
        $addonManifest['content'] =
          $this->funcProcessContent($addonManifest['content']);
      }
      else {
        $addonManifest['content'] = nl2br($addonManifest['description']);
      }
    }

    // Process license
    if (array_key_exists('license', $addonManifest)) {
      $addonManifest = $this->funcProcessLicense($addonManifest);
    }
    
    // Truncate description if it is too long..
    if (array_key_exists('description', $addonManifest) && strlen($addonManifest['description']) >= 235) {
      $addonManifest['description'] =
        substr($addonManifest['description'], 0, 230) . '&hellip;';
      
      $this->funcAddonError($addonManifest['slug'], 'warning',
          'em:description is more than 235 characters long, truncated to 230 characters');
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

  // ------------------------------------------------------------------------

  // Reads and process phoebus.content
  private function funcProcessContent($_addonPhoebusContent) {     
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

  // ------------------------------------------------------------------------

  private function funcProcessLicense($addonManifest) {
    // Approved Licenses
    $_arrayLicenses = array(
      'custom' => 'Custom License',
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
      'PD' => 'Public Domain',
      'COPYRIGHT' => '&copy;' . ' ' . date("Y") . ' - ' . $addonManifest['creator']
    );
    
    $_arrayLicenses = array_change_key_case($_arrayLicenses, CASE_LOWER);
     
    // Set to lowercase
    if ($addonManifest['license'] != null) {
      $addonManifest['license'] = strtolower($addonManifest['license']);
    }

    // phoebus.license trumps all
    // If existant override any license* keys and load the file into the manifest
    if ($addonManifest['licenseText'] != null) {
      $addonManifest['license'] = 'custom';
      $addonManifest['licenseName'] =
        $_arrayLicenses[$addonManifest['license']];
      $addonManifest['licenseDefault'] = null;
      $addonManifest['licenseURL'] = null;

      return $addonManifest;
    }

    // If license is not set then default to copyright
    if ($addonManifest['license'] == null) {
      $addonManifest['license'] = 'copyright';
      $addonManifest['licenseName'] =
        $_arrayLicenses[$addonManifest['license']];
      $addonManifest['licenseDefault'] = true;
      
      $this->funcAddonError($addonManifest['slug'], 'warning',
            'No license detected, using default COPYRIGHT license unless otherwise indicated');

      return $addonManifest;
    }

    if ($addonManifest['license'] != null) {
      if ($addonManifest['license'] == 'custom' &&
        startsWith($addonManifest['licenseURL'], 'http')) {
        $addonManifest['license'] = 'custom';
        $addonManifest['licenseName'] =
          $_arrayLicenses[$addonManifest['license']];

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
        
        $this->funcAddonError($addonManifest['slug'], 'warning',
            'Unknown License');

        return $addonManifest;
      }
    }
  }

  // ------------------------------------------------------------------------

  // Generate debug errors relating to classAddonManifest
  private function funcAddonError($_addonSlug, $_type, $_message, $_fatal = null) {
    $this->addonErrors[] = $_addonSlug . ' - ' . $_type . ': ' . $_message;
    
    // Disable Fatal status completely but leave the mechinism in place
    $_fatal = null;

    if ($_fatal == true && $GLOBALS['arraySoftwareState']['debugMode'] == true) {
      $_fatalErrors = implode("\n", $this->addonErrors);
      funcError($_fatalErrors);
    }
    else {
      return null;
    }
  }
}

// ============================================================================

?>