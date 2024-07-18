<?php

class Database
{

	// Function to the database and tables and fill them with the default data
	function create_database($data)
	{
		// Connect to the database
		$mysqli = new mysqli($data['db_hostname'], $data['db_username'], $data['db_password'], '');

		// Check for errors
		if (mysqli_connect_errno())
			return false;

		// Create the prepared statement
		$mysqli->query("CREATE DATABASE IF NOT EXISTS " . $data['db_name']);

		// Close the connection
		$mysqli->close();

		return true;
	}

	// Function to create the tables and fill them with the default data
	function create_tables($data)
	{
		// Connect to the database
		$mysqli = new mysqli($data['db_hostname'], $data['db_username'], $data['db_password'], $data['db_name']);

		// Check for errors
		if (mysqli_connect_errno())
			return false;

		// Open the default SQL file
		$query = file_get_contents('assets/install.sql');

		$newpw = password_hash($data['password'], PASSWORD_DEFAULT);
		$newquery  = str_replace("%%FIRSTUSER_NAME%%", $data['username'], $query);
		$newquery  = str_replace("%%FIRSTUSER_PASS%%", $newpw, $newquery);
		$newquery  = str_replace("%%FIRSTUSER_MAIL%%", $data['user_email'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_CALL%%", $data['callsign'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_LOCATOR%%", $data['userlocator'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_FIRSTNAME%%", $data['firstname'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_LASTNAME%%", $data['lastname'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_TIMEZONE%%", $data['timezone'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_DXCC%%", $data['dxcc'], $newquery);
		$newquery  = str_replace("%%FIRSTUSER_CITY%%", $data['city'], $newquery);
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

	function database_check($data)
	{
		try {
			$timeout = 5;  /* five seconds for timeout */
			$link = mysqli_init();

			$link->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);

			$link->real_connect($data['db_hostname'], $data['db_username'], $data['db_password'], $data['db_name']);

			if ($link->connect_error) {
				throw new Exception('Connection Error: ' . $link->connect_error);
			}

			$mysql_version = $link->server_info;

			$link->close();

			return $mysql_version;
		} catch (Exception $e) {
			return 'Error: ' . $e->getMessage();
		}
	}
}
