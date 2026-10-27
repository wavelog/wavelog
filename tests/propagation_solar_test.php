<?php
// Standalone solar parser/cache tests: no network or database.
define('BASEPATH', __DIR__);
$temporary = sys_get_temp_dir() . '/wavelog-solar-' . bin2hex(random_bytes(6));
function log_message($level, $message) {}
function &get_instance() {
	static $instance;
	if (!$instance) {
		$instance = new stdClass();
		$instance->paths = new class {
			public function make_update_path($path) { return $GLOBALS['temporary'] . '/custom/updates/' . $path; }
		};
	}
	return $instance;
}
require __DIR__ . '/../application/libraries/Propagation_solar.php';
class Fixture_solar extends Propagation_solar {
	public $failure = false;
	public $observed = '[{"time-tag":"2026-03","smoothed_ssn":101.4},{"time-tag":"2026-04","smoothed_ssn":-1}]';
	public $predicted = '[{"time-tag":"2026-03","predicted_ssn":99},{"time-tag":"2026-04","predicted_ssn":91.8}]';
	protected function download($url) {
		if ($this->failure) throw new RuntimeException('HTTP 503 test failure');
		return strpos($url, '/observed-') !== false ? $this->observed : $this->predicted;
	}
}
$checks = 0;
function check($condition, $message) {
	global $checks;
	$checks++;
	if (!$condition) throw new RuntimeException($message);
}
function fails($operation, $message) {
	try {
		$operation();
	} catch (RuntimeException $error) {
		check(stripos($error->getMessage(), $message) !== false, 'Unexpected failure: ' . $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected error: ' . $message);
}
$solar = new Fixture_solar();
$path = $temporary . '/custom/updates/propagation/solar-cycle.json';
try {
	fails(fn() => $solar->snapshot(), 'not been downloaded');
	check($solar->refresh() === 2, 'Must merge observed and predicted months');
	$snapshot = $solar->snapshot();
	check($snapshot['months']['2026-03']['kind'] === 'observed', 'Observed must win over predicted');
	check($snapshot['months']['2026-03']['value'] === 101.4, 'Must preserve modern SSN scale');
	check($snapshot['months']['2026-03']['ssn'] === 101, 'Must round to engine integer');
	check($snapshot['months']['2026-04']['kind'] === 'predicted', 'Missing observed -1 must not replace prediction');
	check($snapshot['months']['2026-04']['ssn'] === 92, 'Prediction must round to nearest integer');
	check($snapshot['stale'] === false, 'Fresh data must not be flagged stale');
	$original = file_get_contents($path);
	$solar->failure = true;
	fails(fn() => $solar->refresh(), 'HTTP 503');
	check(file_get_contents($path) === $original, 'Download failure must preserve last good snapshot');
	$solar->failure = false;
	foreach (['<html>', 'null', '{}', '[]',
		'[{"time-tag":"2026-13","predicted_ssn":100}]',
		'[{"time-tag":"2026-10","predicted_ssn":"bad"}]',
		'[{"time-tag":"2026-10","predicted_ssn":-2}]',
		'[{"time-tag":"2026-10","predicted_ssn":-1}]',
		'[{"time-tag":"2026-10","predicted_ssn":1001}]',
		'[{"time-tag":"2026-10","ssn":100}]'] as $invalid) {
		$solar->predicted = $invalid;
		fails(fn() => $solar->refresh(), 'solar-cycle');
		check(file_get_contents($path) === $original, 'Malformed response must preserve cache');
	}
	$values = Propagation_solar::monthly_values(
		[['time-tag' => '2026-01', 'smoothed_ssn' => 0]],
		[['time-tag' => '2026-02', 'predicted_ssn' => 400]]);
	check($values['2026-01']['ssn'] === 0 && $values['2026-02']['ssn'] === 400, 'Unsupported model values must not be silently clamped');
	$stale = $snapshot;
	$stale['updated'] = time() - 8 * 86400;
	file_put_contents($path, json_encode($stale));
	check($solar->snapshot()['stale'] === true, 'Old cache must be explicitly flagged');
	$lock = fopen($path . '.lock', 'c');
	flock($lock, LOCK_EX);
	try {
		fails(fn() => $solar->refresh(), 'already running');
	} finally {
		fclose($lock);
	}
	$solar->predicted = '[{"time-tag":"2026-04","predicted_ssn":91.8}]';
	check($solar->refresh() === 2, 'Retry after failure must work and release lock');
	check(count(glob(dirname($path) . '/.solar-*')) === 0, 'No temporary files should remain');
	file_put_contents($path, '{}');
	fails(fn() => $solar->snapshot(), 'Invalid cached');
	file_put_contents($path, 'broken');
	fails(fn() => $solar->snapshot(), 'Invalid solar-cycle JSON');
	echo "Passed $checks propagation solar checks.\n";
} finally {
	foreach ([$path, $path . '.lock'] as $file) {
		if (is_file($file)) unlink($file);
	}
	foreach ([dirname($path), dirname($path, 2), dirname($path, 3), $temporary] as $directory) {
		if (is_dir($directory)) rmdir($directory);
	}
}
