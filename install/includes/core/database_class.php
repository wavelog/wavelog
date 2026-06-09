<?php

class Database {

	// Function to create the tables and fill them with the default data
	function create_tables($data) {
		// Connect to the database
		$mysqli = new mysqli($data['db_hostname'], $data['db_username'], $data['db_password'], $data['db_name']);

		// Check for errors
		if (mysqli_connect_errno()) {
			log_message('error', 'Database connection error: ' . mysqli_connect_error());
			return false;
		}

		// Open the default SQL file
		if (!$query = file_get_contents('assets/install.sql')) {
			log_message('error', 'Failed to read install.sql file.');
			return false;
		}

		$newpw = password_hash($data['password'], PASSWORD_DEFAULT);
		$newquery  = str_replace("%%FIRSTUSER_NAME%%", $mysqli->real_escape_string($data['username']), $query);
		$newquery  = str_replace("%%FIRSTUSER_PASS%%", $newpw, $newquery);
		$newquery  = str_replace("%%FIRSTUSER_MAIL%%", $mysqli->real_escape_string($data['user_email']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_CALL%%", $mysqli->real_escape_string(strtoupper($data['callsign'])), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_LOCATOR%%", $mysqli->real_escape_string(strtoupper($data['userlocator'])), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_FIRSTNAME%%", $mysqli->real_escape_string($data['firstname']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_LASTNAME%%", $mysqli->real_escape_string($data['lastname']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_TIMEZONE%%", (int)$data['timezone'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_DXCC%%", (int)$data['dxcc'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_CITY%%", $mysqli->real_escape_string($data['city']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_USERLANGUAGE%%", $mysqli->real_escape_string($data['userlanguage']), $newquery);
		log_message('info', 'SQL queries prepared successfully. Writing to database...');


		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		try {
			// Execute a multi query
			$mysqli->multi_query($newquery);

			// Drain all results to ensure the server finishes processing.
			do {
				if ($result = $mysqli->store_result()) {
					$result->free();
				}
			} while ($mysqli->more_results() && $mysqli->next_result());

			// Close the connection
			$mysqli->close();

			log_message('info', 'Database tables created successfully.');
			return true;

		} catch (mysqli_sql_exception $e) {
			log_message('error', 'Database Error: ' . $e->getMessage());

			if ($mysqli->ping()) {
				$mysqli->close();
			}

			return false;
		}
	}

	function database_check($data) {
		try {
			$timeout = 5;  /* five seconds for timeout */
			$link = mysqli_init();
			
			$link->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
			
			$link->real_connect($data['db_hostname'], $data['db_username'], $data['db_password']);
			
			if ($link->connect_error) {
				throw new Exception("Connection Error: " . $link->connect_error);
			}
	
			if (!preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $data['db_name'])) {
				throw new Exception("Invalid database name. Only letters, numbers, underscores and hyphens are allowed.");
			}
			if (!$link->query("CREATE DATABASE IF NOT EXISTS `" . $data['db_name'] . "`")) {
				throw new Exception("Unable to create database: " . $link->error);
			}
	
			if (!$link->select_db($data['db_name'])) {
				throw new Exception("Unable to select database: " . $link->error);
			}
			
			$result = $link->query("SHOW TABLES");
		
			if ($result->num_rows > 0) {
				throw new Exception("Database is not empty.");
			}
			
			$version_query = $link->query("SELECT VERSION() as version")->fetch_assoc();  // $link->server_info sometimes returns wrong version or additional (in this case unnecessary) information, e.g. 5.5.5-10.3.29-MariaDB-0+deb10u1
			if (!$version_query) {
				throw new Exception("Unable to get Database version: " . $link->error);
			}
			if (!isset($version_query['version'])) {
				throw new Exception("Database version could not be retrieved.");
			}
			$mysql_version = $version_query['version'];

			// in case of a previous failed installation it can happen that still the migration lockfile is existent
			// this would prevent the migration from running or at least would cause a unnecessary delay
			// so we delete it here
			$lockfile = sys_get_temp_dir() . '/.migration_running';
			if (file_exists($lockfile)) {
				log_message('info', 'Removing migration lockfile. Not expected to be present at this point.');
				unlink($lockfile);
			}

			$link->close();
			
			return $mysql_version;

		} catch (Exception $e) {
			log_message('error', 'Database Check Error: ' . $e->getMessage());
			return 'Error: ' . $e->getMessage();
		}
	}
}
