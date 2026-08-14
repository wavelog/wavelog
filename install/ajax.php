<?php

session_start();

if (file_exists('.lock')) {
	http_response_code(403);
	echo 'forbidden';
	exit;
}

$token = $_SERVER['HTTP_X_INSTALLER_TOKEN'] ?? '';
if (empty($token) || !isset($_SESSION['installer_token']) || !hash_equals($_SESSION['installer_token'], $token)) {
	http_response_code(403);
	echo 'forbidden';
	exit;
}

// PHP locks the session file for the whole request, so a long running step would
// block every other call - including the debug log polling of run.php. Only these
// handlers write to $_SESSION, everything else releases the lock right away.
$session_writers = ['run_config_file', 'run_cron_token', 'run_installer_lock'];
$needs_session = false;
foreach ($session_writers as $key) {
	if (isset($_POST[$key])) {
		$needs_session = true;
		break;
	}
}
if (!$needs_session) {
	session_write_close();
}

// Target for Ajax Calls

require_once('includes/install_config/install_lib.php');
require_once('includes/install_config/install_config.php');

require_once('includes/gettext/gettext.php');
require_once('includes/gettext/gettext_conf.php');

require_once('includes/core/core_class.php');
require_once('includes/core/database_class.php');

$core = new Core();
$database = new Database();

require_once('includes/interface_assets/triggers.php');