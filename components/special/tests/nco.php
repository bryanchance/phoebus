<?php
// This Source Code Form is subject to the terms of the Mozilla Public
// License, v. 2.0. If a copy of the MPL was not distributed with this
// file, You can obtain one at http://mozilla.org/MPL/2.0/.

// == | Main | ========================================================================================================

$arrayTest = array(
  'existingVar' => true
);

$arrayTest['falseBool'] = false ?? null;
$arrayTest['falseString'] = 'false' ?? null;
$arrayTest['zeroInt'] = 0 ?? null;
$arrayTest['zeroString'] = '0' ?? null;
$arrayTest['emptyString'] = '' ?? null;

if ($arrayTest['existingVar'] ?? null) {
  $arrayTest['cond1'] = true;
}

if ($arrayTest['nonexistingVar'] ?? null) {
  $arrayTest['cond2'] = true;
}

funcError([$arrayTest, array() ?? null], 98);

// ====================================================================================================================

?>