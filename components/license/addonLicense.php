<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL

// == | Vars | ================================================================

$strRequestAddonID = funcHTTPGetValue('id');

// ============================================================================

// == | funcDoLicense | =======================================================

function funcDoLicense($_addonManifest) {
  if ($_addonManifest['license'] != null) {
    if ($_addonManifest['license'] == 'custom') {
      if($_addonManifest['licenseText'] != null) {
        return $_addonManifest['licenseText'];
      }
      elseif (startsWith($_addonManifest['licenseURL'], 'http')) {
        funcRedirect($_addonManifest['licenseURL']);
      }
      else {
        funcError($_addonManifest['slug'] . ' does not have a license file');
      }
    }
    elseif ($_addonManifest['license'] == 'pd') {
      return 'Public Domain
 
The author has chosen to place their submission in the public domain.
This means there is no license attached, the submission is owned by "the public", free for anyone to use, and the author waives any rights (including Copyright) and claims of ownership to it.
The submission or any part thereof may be used by anyone, in any way they see fit, for any purpose.
Once a submission is placed in the public domain, it is no longer possible to claim exclusive rights to it, however it may be used as part of other proprietary software without further requirements of disclosure.';
    }
    elseif ($_addonManifest['license'] == 'copyright') {
      return 'This add-on is Copyrighted by its author(s); all rights reserved.

This add-on has been posted on this website by the author(s) or one of
their authorized agents with permission and consent for redistribution
to the public in unmodified form.

Modification, inclusion in a larger work, verbatim copying of (parts of)
this add-on\'s code and assets, and/or public redistribution of this
add-on without the express prior written permission of the author(s)
is prohibited.';
    }
    else {
      $strLicenseBaseURL = 'https://opensource.org/licenses/';
      funcRedirect($strLicenseBaseURL . $_addonManifest['license']);
    }
  }
  else {
    funcError($_addonManifest['slug'] . ' does not have a known license');
  }
  
  // We are done here...
  exit();
}

// ============================================================================

// == | Main | ================================================================

// Sanity
if ($strRequestAddonID == null) {
  funcError('Missing minimum required arguments.');
}

// Includes
require_once($arrayModules['readManifest']);

$readManifest = new classReadManifest();

$addonManifest = $readManifest->getAddonByID($strRequestAddonID);

if ($addonManifest != null) {   
  $strContent = funcDoLicense($addonManifest);
  
  funcSendHeader('html');
  
  print(file_get_contents($strSkinPath . 'default/template-header.xhtml'));

  print('<h1>License</h1>');

  print('<pre>' . $strContent . '</pre>');
  
  print(file_get_contents($strSkinPath . 'default/template-footer.xhtml'));
}
else {
  funcError('Unknown add-on ' . $strRequestAddonID);
}

// ============================================================================
?>