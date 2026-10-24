<?php
// Standalone controller contract tests; no database or remote callbook is contacted.
define('BASEPATH', __DIR__);
function __($message) { return $message; }
function redirect($path) { throw new RuntimeException('redirect:' . $path); }
function log_message($level, $message) {}

#[AllowDynamicProperties]
class CI_Controller {
	public static $authorized = true;
	public function __construct() {
		$this->user_model = new class {
			public function authorize($level) { return CI_Controller::$authorized; }
		};
		$this->session = new class {
			public function set_flashdata($key, $value) {}
		};
		$this->load = new class {
			public function library($name) {}
			public function model($name) {}
		};
		$this->qra = new class {
			public function validate_grid($grid, $type) { return strtoupper($grid) !== 'AA00AA'; }
			public function qra2latlong($grid) { return [41.5, -73]; }
		};
		$this->input = new class {
			public $target = '';
			public $values = [];
			public function get($key, $clean) { return $key === 'target' ? $this->target : ($this->values[$key] ?? null); }
		};
		$this->output = new class {
			public $status = 200;
			public $body = '';
			public $type = '';
			public function set_status_header($status) { $this->status = $status; return $this; }
			public function set_content_type($type) { $this->type = $type; return $this; }
			public function set_output($body) { $this->body = $body; return $this; }
		};
		$this->logbook_model = new class {
			public $result = ['qra' => ''];
			public $calls = 0;
			public function get_callsign_all_info($call) { $this->calls++; return $this->result; }
		};
		$this->callbook = new class {
			public $result = ['gridsquare' => '', 'error' => 'Lookup not configured'];
			public $calls = 0;
			public function getCallbookData($call) { $this->calls++; return $this->result; }
		};
	}
}

require __DIR__ . '/../application/controllers/Propagation.php';
$checks = 0;
function check($condition, $message) {
	global $checks;
	$checks++;
	if (!$condition) throw new RuntimeException($message);
}
function lookup($target, $local = '', $remote = null) {
	$controller = new Propagation();
	$controller->input->target = $target;
	$controller->logbook_model->result = ['qra' => $local];
	if ($remote !== null) $controller->callbook->result = $remote;
	$controller->lookup();
	return [$controller, json_decode($controller->output->body, true, 512, JSON_THROW_ON_ERROR)];
}

foreach (['FN31', 'fn31aa', 'FN31AA00', 'FN31AA00AA'] as $grid) {
	[$controller, $result] = lookup($grid);
	check($controller->output->status === 200 && $result['grid'] === strtoupper($grid), 'Direct locator failed');
	check($controller->output->type === 'application/json', 'JSON content type missing');
	check($controller->logbook_model->calls === 0 && $controller->callbook->calls === 0, 'Direct grid must not query callbooks');
	check(is_float($result['lat']) && is_int($result['lng']), 'Coordinates must be JSON numbers');
}

[$controller, $result] = lookup(' k1abc ', 'FN31AA');
check($result['target'] === 'K1ABC' && $result['source'] === 'Logbook', 'Local lookup normalization failed');
check($controller->callbook->calls === 0, 'A valid local grid must prevent remote lookup');

[$controller, $result] = lookup('K1ABC/P', '', ['gridsquare' => 'FN31', 'source' => 'Test callbook']);
check($result['grid'] === 'FN31' && $result['source'] === 'Test callbook', 'Remote fallback failed');
check($controller->callbook->calls === 1, 'Remote call count incorrect');

[$controller, $result] = lookup('K1ABC', 'AA00AA', ['gridsquare' => 'FN31']);
check($result['grid'] === 'FN31', 'Placeholder local grid must fall back to remote lookup');

[$controller, $result] = lookup('K1ABC', '', ['gridsquare' => 'FN31YY', 'error_qrz' => 'Test remote error', 'error_qrz_name' => 'QRZ']);
check($controller->output->status === 404 && isset($result['error']), 'No grid must return an explicit error');
check(strpos($result['error'], 'Test remote error') !== false, 'Remote errors must be surfaced');

foreach ([null, [], '', ' ', 'ABC', '<script>', 'A//1', 'A/1/', str_repeat('A', 33)] as $invalid) {
	[$controller, $result] = lookup($invalid);
	check($controller->output->status === 400 && isset($result['error']), 'Invalid input must return HTTP 400');
	check($controller->callbook->calls === 0, 'Invalid input must not query remote callbook');
}

foreach ([new InvalidArgumentException('Unknown file'), new RuntimeException('Missing file')] as $error) {
	$controller = new Propagation();
	$controller->propagation_data = new class($error) {
		private $error;
		public function __construct($error) { $this->error = $error; }
		public function revision() { return 'current'; }
		public function verified_path($name) { throw $this->error; }
	};
	$controller->input->values = ['file' => '../private', 'revision' => 'current'];
	$controller->data();
	check($controller->output->status === ($error instanceof InvalidArgumentException ? 400 : 503), 'Data errors must have explicit HTTP status');
	check(isset(json_decode($controller->output->body, true)['error']), 'Data errors must return a JSON message');
	$controller->input->values['revision'] = 'old';
	$controller->data();
	check($controller->output->status === 400, 'Stale reference revision must require a page reload');
}

CI_Controller::$authorized = false;
try {
	new Propagation();
	throw new RuntimeException('Authorization was bypassed');
} catch (RuntimeException $exception) {
	check($exception->getMessage() === 'redirect:dashboard', 'Unauthorized users must be redirected');
}
echo "Passed $checks propagation lookup checks.\n";
