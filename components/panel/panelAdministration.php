<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Main | ========================================================================================================

$arraySoftwareState['requestPanelTask'] = funcUnifiedVariable('get', 'task');
$arraySoftwareState['requestPanelWhat'] = funcUnifiedVariable('get', 'what');
$arraySoftwareState['requestPanelSlug'] = funcUnifiedVariable('get', 'slug');

// --------------------------------------------------------------------------------------------------------------------

$moduleAuth->authenticate();
funcCheckAccessLevel(3);

// --------------------------------------------------------------------------------------------------------------------

if ($arraySoftwareState['requestPath'] == URI_ADMIN && !$arraySoftwareState['requestPanelTask']) {
  $moduleGenerateContent->addonPanel('admin-frontpage.xhtml', 'Administration Panel');
}

if ($arraySoftwareState['requestPanelTask'] == 'list') {
  if (!$arraySoftwareState['requestPanelWhat']) {
    funcError('You did not specify what you want to list');
  }

  switch ($arraySoftwareState['requestPanelWhat']) {
    case 'all':
      $allAddons = $moduleReadManifest->getAddons('panel-all-addons');
      $moduleGenerateContent->addonPanel('administration-list', 'Administration Panel - All Add-ons', $allAddons);
      break;
    case 'extensions':
    case 'externals':
    case 'themes':
    case 'langpacks':
      $allAddons = $moduleReadManifest->getAddons(
        'panel-addons-by-type',
        substr($arraySoftwareState['requestPanelWhat'], 0, -1)
      );
      $moduleGenerateContent->addonPanel(
        'admin-list-' . $arraySoftwareState['requestPanelWhat'],
        'Administration Panel - All ' . $arraySoftwareState['requestPanelWhat'],
        $allAddons
      );
      break;
    case 'users':
      funcSendHeader('501');
    default:
      funcError('Invalid list request');
  }
}
elseif ($arraySoftwareState['requestPanelTask'] == 'update') {
  if (!$arraySoftwareState['requestPanelWhat'] || !$arraySoftwareState['requestPanelSlug']) {
    funcError('You did not specify what you want to update');
  }

  switch ($arraySoftwareState['requestPanelWhat']) {
    case 'metadata':
      $addonManifest = $moduleReadManifest->getAddonBySlug($arraySoftwareState['requestPanelSlug'], null);
      funcSendHeader('html');
      funcError($addonManifest['content']);
      break;
    case 'release':
      funcSendHeader('501');
    default:
      funcError('Invalid update request');
  }
}

funcSend404();

// ====================================================================================================================

?>
