<?php

class Database {

	// Function to create the tables and fill them with the default data
	function create_tables($data) {
		// Connect to the database
		$mysqli = new mysqli($data['db_hostname'], $data['db_username'], $data['db_password'], $data['db_name']);

		// Check for errors
		if (mysqli_connect_errno())
			return false;

		// Open the default SQL file
		$query = file_get_contents('assets/install.sql');

		$newpw = password_hash($data['password'], PASSWORD_DEFAULT);
		$newquery  = str_replace("%%FIRSTUSER_NAME%%", str_replace("'", "\\'", $data['username']), $query);
		$newquery  = str_replace("%%FIRSTUSER_PASS%%", $newpw, $newquery);
		$newquery  = str_replace("%%FIRSTUSER_MAIL%%", $data['user_email'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_CALL%%", strtoupper($data['callsign']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_LOCATOR%%", strtoupper($data['userlocator']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_FIRSTNAME%%", str_replace("'", "\\'", $data['firstname']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_LASTNAME%%", str_replace("'", "\\'", $data['lastname']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_TIMEZONE%%", $data['timezone'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_DXCC%%", $data['dxcc'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_CITY%%", str_replace("'", "\\'", $data['city']), $newquery);
		$newquery  = str_replace("%%FIRSTUSER_USERLANGUAGE%%", $data['userlanguage'], $newquery);


		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		// Execute a multi query
		$mysqli->multi_query($newquery);

		// MultiQuery is NON-Blocking,so wait until everything is done
		do {
			null;
		} while ($mysqli->next_result());

		$result = $mysqli->store_result();

		// Close the connection
		$mysqli->close();

		return true;
	}

	function database_check($data) {
		try {
			$timeout = 5;  /* five seconds for timeout */
			$link = mysqli_init();
			
			$link->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
			
			$link->real_connect($data['db_hostname'], $data['db_username'], $data['db_password']);
			
			if ($link->connect_error) {
				throw new Exception(__("Connection Error: ") . $link->connect_error);
			}
	
			if (!$link->query("CREATE DATABASE IF NOT EXISTS " . $data['db_name'])) {
				throw new Exception(__("Unable to create database: ") . $link->error);
			}
	
			// Wählen Sie die Datenbank aus
			if (!$link->select_db($data['db_name'])) {
				throw new Exception(__("Unable to select database: ") . $link->error);
			}
			
			$result = $link->query("SHOW TABLES");
		
			if ($result->num_rows > 0) {
				throw new Exception(__("Database is not empty."));
			}
			
			$mysql_version = $link->server_info;
			
			$link->close();
			
			return $mysql_version;
		} catch (Exception $e) {
			return 'Error: ' . $e->getMessage();
		}
	}
}
