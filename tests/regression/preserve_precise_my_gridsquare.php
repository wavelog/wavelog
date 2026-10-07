<?php

// Dependency-free regression harness for Logbook_model's ADIF grid policy.
define('APPPATH', dirname(__DIR__, 2) . '/application/');

class CI_Model {}

require_once APPPATH . 'models/Logbook_model.php';

$model = (new ReflectionClass(Logbook_model::class))->newInstanceWithoutConstructor();
$failures = [];

function expect_same($description, $expected, $actual) {
	global $failures;
	if ($expected !== $actual) {
		$failures[] = sprintf('%s: expected %s, got %s', $description, var_export($expected, true), var_export($actual, true));
	}
}

expect_same(
	'preserves a six-character grid within EM89',
	'EM89KT',
	$model->get_import_my_gridsquare(['my_gridsquare' => 'EM89KT'], 'EM89')
);
expect_same(
	'trims and uppercases a precise grid within EN80',
	'EN80MD',
	$model->get_import_my_gridsquare(['my_gridsquare' => ' en80md '], 'EN80')
);
expect_same(
	'equal precision retains the station profile',
	'EM89',
	$model->get_import_my_gridsquare(['my_gridsquare' => 'EM89'], 'EM89')
);
expect_same(
	'less precise input retains the station profile',
	'EM89KT',
	$model->get_import_my_gridsquare(['my_gridsquare' => 'EM89'], 'EM89KT')
);
expect_same(
	'incompatible precision retains the station profile even when import validation is skipped',
	'EM89',
	$model->get_import_my_gridsquare(['my_gridsquare' => 'EN80md'], 'EM89')
);
expect_same(
	'existing validation can reject the incompatible grid',
	false,
	$model->adif_grid_check_location($model->get_adif_grid_value(['my_gridsquare' => 'EN80md']), 'EM89')
);
expect_same(
	'VUCC station profiles remain unchanged',
	'EM89,EM99',
	$model->get_import_my_gridsquare(['my_gridsquare' => 'EM89KT'], ' em89,em99 ')
);
expect_same(
	'absent input retains the station profile',
	'EM89',
	$model->get_import_my_gridsquare([], 'EM89')
);
expect_same(
	'MY_GRIDSQUARE_EXT participates only in compatibility checking',
	'EM89KT12',
	$model->get_import_my_gridsquare(['my_gridsquare' => 'em89kt12', 'my_gridsquare_ext' => 'AB'], 'EM89')
);
expect_same(
	'MY_GRIDSQUARE_EXT is not concatenated into the stored value',
	8,
	strlen($model->get_import_my_gridsquare(['my_gridsquare' => 'em89kt12', 'my_gridsquare_ext' => 'AB'], 'EM89'))
);

if ($failures) {
	fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
	exit(1);
}

echo "All precise MY_GRIDSQUARE regression checks passed." . PHP_EOL;
