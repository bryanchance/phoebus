<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Main | ========================================================================================================

$arraySoftwareState['requestPanelTask'] = funcUnifiedVariable('get', 'task');
$arraySoftwareState['requestPanelWhat'] = funcUnifiedVariable('get', 'what');
$arraySoftwareState['requestPanelSlug'] = funcUnifiedVariable('get', 'slug');

// --------------------------------------------------------------------------------------------------------------------

$moduleAccount->authenticate();
funcCheckAccessLevel(3);

// --------------------------------------------------------------------------------------------------------------------

if ($arraySoftwareState['requestPath'] == URI_ADMIN && !$arraySoftwareState['requestPanelTask']) {
  $moduleGenerateContent->addonSite('admin-frontpage.xhtml', 'Administration');
}

if ($arraySoftwareState['requestPanelTask'] == 'list') {
  if (!$arraySoftwareState['requestPanelWhat']) {
    funcError('You did not specify what you want to list');
  }

  switch ($arraySoftwareState['requestPanelWhat']) {
    case 'all':
      $allAddons = $moduleReadManifest->getAddons('panel-all-addons');
      $moduleGenerateContent->addonSite('administration-list', 'All Add-ons - Administration', $allAddons);
      break;
    case 'extensions':
    case 'externals':
    case 'themes':
    case 'langpacks':
      $allAddons = $moduleReadManifest->getAddons('panel-addons-by-type',
                                                  substr($arraySoftwareState['requestPanelWhat'], 0, -1));

      $moduleGenerateContent->addonSite('admin-list-' . $arraySoftwareState['requestPanelWhat'],
                                        'All ' . $arraySoftwareState['requestPanelWhat'] . ' - Administration',
                                        $allAddons);
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
      if (!$arraySoftwareState['requestPanelSlug']) {
        funcError('Not a valid slug');
      }

      $addonManifest = $moduleReadManifest->getPanelAddonBySlug($arraySoftwareState['requestPanelSlug']);

      if (!$addonManifest) {
        funcError('Add-on Manifest is null');
      }

      if ($addonManifest['type'] == 'external' || $addonManifest['type'] == 'langpack') {
        funcError($addonManifest, 98);
      }

      if ($boolHasPostData) {
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

        if (!$arrayPostData['slug'] || $arrayPostData['slug'] != $arraySoftwareState['requestPanelSlug']) {
          funcError('Invalid slug on GET/POST');
        }

        funcError($moduleWriteManifest->updateAddonMetadata($addonManifest, $arrayPostData), 99);
      }

      $arrayExtraData = array('licenses' => array_keys($moduleReadManifest::LICENSES));
      if ($addonManifest['type'] == 'extension') {
        $arrayExtraData['categories'] = $moduleReadManifest::EXTENSION_CATEGORY_SLUGS;
      }

      $moduleGenerateContent->addonSite('admin-edit-addon-metadata',
                                         'Editing Metadata for ' . $addonManifest['name'],
                                         $addonManifest,
                                         $arrayExtraData);
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
