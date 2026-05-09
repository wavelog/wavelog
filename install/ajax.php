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