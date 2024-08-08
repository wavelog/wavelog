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

if ($_POST['read_logfile'] ?? false == true) {

	$result = read_logfile();
	echo $result;
	exit;
	
}

if ($_POST['write_to_logfile'] ?? false == true) {

	$level = $_POST['log_level'];
	$message = $_POST['log_message'];

	if(log_message($level, $message)) {
		$result = 'success';
	} else {
		$result = 'error';
	}
	echo $result;
	exit;
	
}

/**
 * 
 * Install Triggers
 * 
 */

if ($_POST['check_lockfile'] ?? false == true) {

	$lockfile = '../install/.lock';

	if (file_exists($lockfile)) {
		$result = 'installer_locked';
	} else {
		$result = 'no_lockfile';
	}
	echo $result;
	exit;
}

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
	if (touch('.lock')) {
		echo 'success';
	} else {
		echo 'error';
	}
	exit;
}
