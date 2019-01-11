<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

ini_set("SMTP", "vmx.palemoon.org");
ini_set("sendmail_from", "phoebus@addons.palemoon.org");

$sendmail = mail('email@mattatobin.com', 'Phoebus sendmail test case', 'Test');

funcError([$sendmail, ini_get("sendmail_from"), ini_get("SMTP")], 99);

// ====================================================================================================================

?>