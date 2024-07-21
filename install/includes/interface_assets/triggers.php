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

if ($_POST['run_config_file'] ?? false == true) {
	sleep(1);
	$data = $_POST['data'];
	if ($core->validate_post($data)) {
		if($core->write_configfile($data)) {
			$result = 'success';
		} else {
			$result = 'error';
		}
	} else {
		$result = 'error';
	}
	echo $result;
	exit;
}

if ($_POST['run_database_file'] ?? false == true) {
	sleep(1);
	$data = $_POST['data'];
	if ($core->validate_post($data)) {
		if($core->write_config($data)) {
			$result = 'success';
		} else {
			$result = 'error';
		}
	} else {
		$result = 'error';
	}
	echo $result;
	exit;
}

if ($_POST['run_database_tables'] ?? false == true) {
	$data = $_POST['data'];
	if ($core->validate_post($data)) {
		$result = $database->create_tables($data);
	} else {
		$result = 'error';
	}
	echo $result ? 'success' : 'error';
	exit;
}

if ($_POST['run_installer_lock'] ?? false == true) {
	exec('touch .lock', $output, $return_var);
	if ($return_var === 0 && file_exists('.lock')) {
		echo 'success';
	} else {
		echo 'error';
	}
	exit;
}
