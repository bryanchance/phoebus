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

  public function updateAddonMetadata($aAddonManifest, ) {
    $slug = $aPostData['slug'];
    unset($aPostData['slug']);

    foreach ($aPostData as $_key => $_value) {
      if ($_key != 'content' && $aAddonManifest[$_key] == $_value) {
        unset($aPostData[$_key]);
      }

      if ($_key == 'content' && (!$_value || strcmp($aAddonManifest['content'], $aPostData['content']))) {
        unset($aPostData['content']);
      }
    }

    return $aPostData;
  }

}

// ====================================================================================================================

?>