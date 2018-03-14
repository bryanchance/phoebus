<?php
// == | classGeneratePage | ===================================================

class classGeneratePage {
  // Use smarty to generate the page
  public function useSmarty((array)$_arrayPageInput) {
    // Create a base page array
    $arrayPage = array(
      'component' => null,
      'skin' => null,
      'contentTemplate' => null,
      'contentType' => null,
      'contentData' => null,
      'contentInline' => null,
      'contentTitle' => null
    );
    
    // Merge input into base
    $arrayPage = array_merge($_arrayPageInput, $arrayPage);
    
    // Set the local component content directory
    if ($_component == 'frontend') {
      $arrayPage['contentPath'] = './components/site/content/';
    }
    else {
       $arrayPage['contentPath'] = './components/' . $_component . '/content/';
    }

    // Build the objdir basepath for smarty path
    $smartyBasePath = $GLOBALS['strObjDirPath'] . 'smarty/' . $_component . '/';

    // Include smarty
    require_once($arrayModules['smarty']);

    // Create a new instance of the Smarty class
    $classSmarty = new Smarty();

    // Configure smarty
    $classSmarty->caching = 0;
    $classSmarty->setCacheDir($smartyBasePath . 'cache');
    $classSmarty->setCompileDir($smartyBasePath . 'compile');
    $classSmarty->setConfigDir($smartyBasePath . 'config');
    $classSmarty->addPluginsDir($smartyBasePath . 'plugins');
    $classSmarty->setTemplateDir($smartyBasePath . 'templates');

    // Smarty Debug
    if (funcHTTPGetValue('smartyDebug')) {
      $classSmarty->debugging = $GLOBALS['boolDebugMode'];
    }
    else {
      $classSmarty->debugging = false;
    }

    // Create an array that will be iterated over and assigned to Smarty
    $arraySmarty = array(
      'APPLICATION_DEBUG' => $GLOBALS['boolDebugMode'],
      'SITE_NAME' => $GLOBALS['strApplicationSiteName'],
      'SITE_DOMAIN' => '//' . $GLOBALS['strApplicationURL'],
      'PAGE_PATH' => $GLOBALS['strRequestPath'],
      'PHOEBUS_VERSION' => $GLOBALS['strApplicationVersion'])
    );

    // Assign data to Smarty
    $classSmarty->assign('APPLICATION_DEBUG', $GLOBALS['boolDebugMode']);
    $classSmarty->assign('SITE_NAME', $GLOBALS['strApplicationSiteName']);
    $classSmarty->assign('SITE_DOMAIN', '//' . $GLOBALS['strApplicationURL']);
    $classSmarty->assign('PAGE_TITLE', $_array['title']);
    $classSmarty->assign('PAGE_PATH', $GLOBALS['strRequestPath']);
    $classSmarty->assign('BASE_PATH', substr($GLOBALS['strSkinBasePath'], 1));
    $classSmarty->assign('PHOEBUS_VERSION', $GLOBALS['strApplicationVersion']);
  
    if (array_key_exists('contentData', $_array)) {
      $libSmarty->assign('PAGE_DATA', $_array['contentData']);
    }
    
    if (array_key_exists('contentType', $_array)) {
      $libSmarty->assign('PAGE_TYPE', $_array['contentType']);
    }
    
    // Send html header and pass the final template to Smarty
    funcSendHeader('html');
    $libSmarty->display('string:' . $_strSiteTemplate, null, str_replace('/', '_', $GLOBALS['strRequestPath']));
  }
}

// ============================================================================

?>