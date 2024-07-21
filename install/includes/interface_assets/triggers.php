<?php

/**
 * Triggers for Ajax Calls
 */

// Database Check
if ($_POST['database_check'] ?? false == true) {

	$result = $database->database_check($_POST);
	echo $result;
	exit;
}

/**
 * 
 * Install Triggers
 * 
 */

// config_file()
// if ($core->validate_post($_POST) == true) {

if ($_POST['run_config_file'] ?? false == true) {
	sleep(1);
	$data = json_decode($_POST['data'], true);
	$result = $core->write_configfile($data);
	echo $result ? 'success' : 'error';
	exit;
}

if ($_POST['run_database_file'] ?? false == true) {
	sleep(1);
	$data = json_decode($_POST['data'], true);
	$result = $core->write_config($data);
	echo $result ? 'success' : 'error';
	exit;
}

if ($_POST['run_database_tables'] ?? false == true) {
	$data = json_decode($_POST['data'], true);
	$result = $database->create_tables($data);
	echo $result ? 'success' : 'error';
	exit;
}

if ($_POST['run_installer_lock'] ?? false) {
	exec('touch .lock', $output, $return_var);
	if ($return_var === 0 && file_exists('.lock')) {
		echo 'success';
	} else {
		echo 'error';
	}
	exit;
}


// }