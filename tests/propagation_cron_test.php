<?php
// Standalone cron/controller contract tests; no database or downloads.
define('BASEPATH', __DIR__);
function log_message($level, $message) {
	$GLOBALS['messages'][] = [$level, $message];
}
#[AllowDynamicProperties]
class CI_Controller {}
#[AllowDynamicProperties]
class CI_Migration {}
require __DIR__ . '/../application/controllers/Update.php';
require __DIR__ . '/../application/migrations/308_add_propagation_data_cron.php';
require __DIR__ . '/../application/migrations/309_add_propagation_solar_cron.php';
$checks = 0;
function check($condition, $message) {
	global $checks;
	$checks++;
	if (!$condition) throw new RuntimeException($message);
}
function update($failure = false, $solar = false) {
	$controller = (new ReflectionClass(Update::class))->newInstanceWithoutConstructor();
	$controller->load = new class {
		public function library($name) {}
		public function model($name) {}
	};
	$controller->propagation_data = new class($failure) {
		private $failure;
		public function __construct($failure) { $this->failure = $failure; }
		public function install() {
			if ($this->failure) throw new RuntimeException('Test download integrity failure');
			return 0;
		}
	};
	$controller->propagation_solar = new class($failure) {
		private $failure;
		public function __construct($failure) { $this->failure = $failure; }
		public function refresh() {
			if ($this->failure) throw new RuntimeException('Test NOAA download failure');
			return 12;
		}
	};
	$controller->cron_model = new class {
		public $id;
		public function set_last_run($id) { $this->id = $id; }
	};
	$controller->output = new class {
		public $status = 200;
		public $body;
		public $type;
		public function set_status_header($value) { $this->status = $value; return $this; }
		public function set_content_type($value) { $this->type = $value; return $this; }
		public function set_output($value) { $this->body = $value; return $this; }
	};
	if ($solar) $controller->update_propagation_solar();
	else $controller->update_propagation();
	return $controller;
}
$controller = update();
check($controller->output->status === 200, 'Valid data must return success');
check(strpos($controller->output->body, 'DONE:') === 0, 'Success must report completed verification');
check($controller->output->type === 'text/plain', 'Cron output must be plain text');
check($controller->cron_model->id === 'update_update_propagation', 'Last run must use registered cron ID');
$controller = update(true);
check($controller->output->status === 503, 'Download failures must fail the HTTP request for Cron Manager');
check(strpos($controller->output->body, 'FAILED: Test download integrity failure') === 0, 'Failure details must be explicit');
check($GLOBALS['messages'][0][0] === 'error', 'Failures must be logged');
$controller = update(false, true);
check($controller->output->status === 200 && strpos($controller->output->body, '12 months') !== false, 'Solar cron must report success');
check($controller->cron_model->id === 'update_update_propagation_solar', 'Solar cron ID must match migration');
$controller = update(true, true);
check($controller->output->status === 503 && strpos($controller->output->body, 'Test NOAA') !== false, 'Solar cron failure must fail HTTP request');

$migration = new Migration_add_propagation_data_cron();
$migration->db = new class {
	public $rows = [];
	public $exists = true;
	private $id;
	public function table_exists($name) { return $this->exists; }
	public function where($key, $value) { $this->id = $value; return $this; }
	public function get($table) { return $this; }
	public function num_rows() { return isset($this->rows[$this->id]) ? 1 : 0; }
	public function insert($table, $row) { $this->rows[$row['id']] = $row; }
	public function delete($table) { unset($this->rows[$this->id]); }
};
$migration->up();
$job = $migration->db->rows['update_update_propagation'];
check($job['function'] === 'index.php/update/update_propagation', 'Cron route must match update controller');
check($job['enabled'] === '1', 'Installation must be enabled by default');
check($job['expression'] === '17 * * * *', 'Cron must check hourly');
$migration->db->rows['update_update_propagation']['enabled'] = '0';
$migration->up();
check(count($migration->db->rows) === 1, 'Repeated migration must not duplicate cron');
check($migration->db->rows['update_update_propagation']['enabled'] === '0', 'Migration must preserve admin settings');
$migration->down();
check(count($migration->db->rows) === 0, 'Rollback must remove only this cron');
$migration->db->exists = false;
$migration->up();
check(count($migration->db->rows) === 0, 'Missing cron table must be handled');
$solarMigration = new Migration_add_propagation_solar_cron();
$solarMigration->db = $migration->db;
$solarMigration->db->exists = true;
$solarMigration->up();
$job = $solarMigration->db->rows['update_update_propagation_solar'];
check($job['function'] === 'index.php/update/update_propagation_solar' && $job['enabled'] === '1', 'Solar job must be registered enabled');
check($job['expression'] === '23 2 * * *', 'Solar job must run daily');
$solarMigration->db->rows['update_update_propagation_solar']['enabled'] = '0';
$solarMigration->up();
check($solarMigration->db->rows['update_update_propagation_solar']['enabled'] === '0', 'Solar migration must preserve admin settings');
$solarMigration->down();
check(count($solarMigration->db->rows) === 0, 'Solar rollback must remove job');
echo "Passed $checks propagation cron checks.\n";
