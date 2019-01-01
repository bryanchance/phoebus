<?php
// == | classReadManifest | ===================================================

class classWriteManifest {
  private $postData;
  private $xpiFile;

  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {  
    if (!funcCheckModule('database')) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - database is required to be included in the global scope');
    }

    $this->$postData = array(
      'slug'          => funcUnifiedVariable('post', 'slug'),
      'active'        => funcUnifiedVariable('post', 'active'),
      'reviewed'      => funcUnifiedVariable('post', 'reviewed'),
      'category'      => funcUnifiedVariable('post', 'category'),
      'license'       => funcUnifiedVariable('post', 'license'),
      'repository'    => funcUnifiedVariable('post', 'repository'),
      'supportURL'    => funcUnifiedVariable('post', 'supportURL'),
      'supportEmail'  => funcUnifiedVariable('post', 'supportEmail'),
      'tags'          => funcUnifiedVariable('post', 'tags'),
      'content'       => funcUnifiedVariable('post', 'content')
    );

    $this->xpiFile = funcUnifiedVariable('files', 'xpiFile');
  }

  public function updateAddonMetadata($aAddonManifest) {
    // Sanity
    if (!$this->$postData['slug']) {
      funcError('Slug was not found in POST');
    }

    if ($this->$postData['slug'] != $addonManifest['slug']) {
      funcError('POST Slug does not match GET/Manifest Slug');
    }

    // Hackers are a superstitious cowardly lot
    if ($GLOBALS['arraySoftwareState']['authentication']['level'] < 3) {
      unset($this->$postData['active']);
      unset($this->$postData['reviewed']);

      if (!in_array($this->$postData['slug'], $GLOBALS['arraySoftwareState']['authentication']['addons'])) {
        funcError('You do not own this add-on. Stop trying to fuck with other people\'s shit!');
      }

      unset($this->$postData['slug']);
    }

    // Remove stuff that is the same
    // Content will always be sent to SQL even if null least for now
    foreach ($this->$postData as $_key => $_value) {
      if ($aAddonManifest[$_key] == $_value) {
        unset($this->$postData[$_key]);
      }
    }

    if (empty($this->$postData)) {
      $this->$postData['content'] = null;
    }

    if ($this->$postData['slug'] ?? false) {
      funcError('Slug is still existing in post data');
    }

    // Insert the new manifest data into the database
    $query = "UPDATE `addon` SET ?u WHERE `slug` = ?s";
    $GLOBALS['moduleDatabase']->query('normal', $query, $this->$postData, $aAddonManifest['slug']);

    return true;
  }
}

// ====================================================================================================================

?>