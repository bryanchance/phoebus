<?php
// == | classReadManifest | ===================================================

class classWriteManifest {
  /********************************************************************************************************************
  * Class constructor that sets inital state of things
  ********************************************************************************************************************/
  function __construct() {  
    if (!funcCheckModule('database')) {
      funcError(__CLASS__ . '::' . __FUNCTION__ . ' - database is required to be included in the global scope');
    }
  }

  public function updateAddonMetadata($aAddonManifest) {
    $arrayPostData = array(
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

    if (!$arrayPostData['slug'] || $arrayPostData['slug'] != $GLOBALS['arraySoftwareState']['requestPanelSlug']) {
      funcError('Invalid slug on GET/POST');
    }
    
    foreach ($arrayPostData as $_key => $_value) {
      if ($aAddonManifest[$_key] == $_value) {
        unset($arrayPostData[$_key]);
      }
    }

    if ($arrayPostData['slug'] ?? false) {
      funcError('Slug is still existing in post data');
    }

    if (empty($arrayPostData)) {
      $arrayPostData['content'] = null;
    }

    return $arrayPostData;
  }

}

// ====================================================================================================================

?>