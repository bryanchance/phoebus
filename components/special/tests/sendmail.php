<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

ini_set("sendmail_from", "phoebus@addons.palemoon.org");

$sendmail = mail('email@mattatobin.com', 'Phoebus sendmail test case', 'Test');

funcError($sendmail, 99);

// ====================================================================================================================

?>