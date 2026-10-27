<?php
// Standalone installer tests with small fixtures; no network or database.
define('BASEPATH', __DIR__);
$temporary = sys_get_temp_dir() . '/wavelog-propagation-' . bin2hex(random_bytes(6));
mkdir($temporary);
mkdir($temporary . '/config');
define('APPPATH', $temporary . '/');
function log_message($level, $message) {}
function &get_instance() {
	static $instance;
	if (!$instance) {
		$instance = new stdClass();
		$instance->paths = new class {
			public function make_update_path($path) { return APPPATH . 'custom-datadir/updates/' . $path; }
		};
	}
	return $instance;
}
require __DIR__ . '/../application/libraries/Propagation_data.php';

class Fixture_data extends Propagation_data {
	public $requests = [];
	public $fail = false;
	public $contents = ['ionos01.bin' => 'map one', 'COEFF01W.txt' => 'noise one'];
	protected function download($url, $destination) {
		$this->requests[] = $url;
		file_put_contents($destination, $this->contents[rawurldecode(basename($url))]);
		if ($this->fail) throw new RuntimeException('HTTP 503 test failure');
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
	} catch (RuntimeException | InvalidArgumentException $error) {
		check(strpos($error->getMessage(), $message) !== false, 'Unexpected error: ' . $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected failure: ' . $message);
}
$official = json_decode(file_get_contents(__DIR__ . '/../application/config/propagation-data.json'), true, 512, JSON_THROW_ON_ERROR);
check(count($official['files']) === 25, 'Expected twelve maps, twelve noise files and decile factors');
check(strpos($official['upstreamDirectory'], '/' . $official['revision'] . '/ITURHFProp/Data/') !== false, 'Download URL must pin the engine revision');
$manifest = ['revision' => 'test-revision', 'upstreamDirectory' => 'https://example.invalid/pinned/', 'files' => []];
foreach (['ionos01.bin' => 'map one', 'COEFF01W.txt' => 'noise one'] as $name => $contents) {
	$manifest['files'][$name] = ['bytes' => strlen($contents), 'sha256' => hash('sha256', $contents)];
}
file_put_contents(APPPATH . 'config/propagation-data.json', json_encode($manifest));
$directory = APPPATH . 'custom-datadir/updates/propagation/test-revision';
try {
	$data = new Fixture_data();
	check($data->revision() === 'test-revision', 'Revision must match installed directory and cache key');
	check(!$data->is_ready(), 'Fresh install must be unready');
	fails(fn() => $data->verified_path('ionos01.bin'), 'Missing or damaged');
	foreach (['../config/propagation-data.json', '/ionos01.bin', [], null] as $name) {
		fails(fn() => $data->verified_path($name), 'Unknown');
	}
	check($data->install() === 2, 'Must download both missing files');
	check($data->is_ready(), 'Completed install must be ready');
	check($data->verified_path('ionos01.bin') === $directory . '/ionos01.bin', 'Custom datadir not honored');
	check($data->install() === 0 && count($data->requests) === 2, 'Valid files must not be downloaded again');
	file_put_contents($directory . '/ionos01.bin', 'bad one');
	check(!$data->is_ready(), 'Same-size corruption must be detected');
	$data->contents['ionos01.bin'] = 'bad two';
	fails(fn() => $data->install(), 'SHA-256 mismatch');
	check(file_get_contents($directory . '/ionos01.bin') === 'bad one', 'Failed verification must not replace existing data');
	check(count(glob($directory . '/.download-*')) === 0, 'Failed verification must clean temporary files');
	$data->contents['ionos01.bin'] = 'tiny';
	fails(fn() => $data->install(), 'size or SHA-256 mismatch');
	$data->contents['ionos01.bin'] = 'map one';
	$data->fail = true;
	fails(fn() => $data->install(), 'HTTP 503');
	check(count(glob($directory . '/.download-*')) === 0, 'HTTP failure must clean temporary files');
	$data->fail = false;
	check($data->install() === 1, 'Retry must repair only the damaged file');
	check($data->is_ready(), 'Repair must restore readiness');
	$lock = fopen($directory . '/.install.lock', 'c');
	flock($lock, LOCK_EX);
	try {
		fails(fn() => $data->install(), 'already running');
	} finally {
		fclose($lock);
	}
	check($data->install() === 0, 'Lock must be released after concurrent attempt');
	echo "Passed $checks propagation data checks.\n";
} finally {
	foreach (['ionos01.bin', 'COEFF01W.txt', '.install.lock'] as $name) {
		if (is_file($directory . '/' . $name)) unlink($directory . '/' . $name);
	}
	unlink(APPPATH . 'config/propagation-data.json');
	foreach ([$directory, dirname($directory), dirname($directory, 2), dirname($directory, 3), APPPATH . 'config', $temporary] as $path) {
		if (is_dir($path)) rmdir($path);
	}
}
