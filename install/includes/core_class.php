<?php

class Core
{

	// Function to validate the post data
	function validate_post($data)
	{
		// Counter variable
		$counter = 0;

		// Validate the hostname
		if (isset($data['db_hostname']) and !empty($data['db_hostname'])) {
			$counter++;
		}
		// Validate the username
		if (isset($data['db_username']) and !empty($data['db_username'])) {
			$counter++;
		}
		// Validate the password
		if (isset($data['db_password']) and !empty($data['db_password'])) {
			// pass
		}
		// Validate the database
		if (isset($data['db_name']) and !empty($data['db_name'])) {
			$counter++;
		}

		if ($data['directory'] != "") {
			if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $data['directory'])) {
				//pass folders real
				$counter++;
			} else {
				echo "Directory " . $data['directory'] . " cannot be found";
				exit;
			}
		} else {
			$counter++;
		}

		// Validate First Name
		if (isset($_POST['firstname']) && !empty($_POST['firstname'])) {
			$counter++;
		} 

		// Validate Last Name
		if (isset($_POST['lastname']) && !empty($_POST['lastname'])) {
			$counter++;
		} 

		// Validate Username
		if (isset($_POST['username']) && !empty($_POST['username'])) {
			$counter++;
		} 

		// Validate Callsign
		if (isset($_POST['callsign']) && !empty($_POST['callsign'])) {
			$counter++;
		} 

		// Validate Password
		if (isset($_POST['password']) && !empty($_POST['password'])) {
			$counter++;
		} 

		// Validate Locator
		if (isset($_POST['userlocator']) && !empty($_POST['userlocator'])) {
			$locator = $_POST['userlocator'];
			if (preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}$/i', $locator)) {
				$counter++;
			} else {
				$errors[] = "Invalid Maidenhead Locator format.";
			}
		} else {
			$errors[] = "Locator is required.";
		}

		// Validate Confirm Password
		if (isset($_POST['cnfm_password']) && !empty($_POST['cnfm_password'])) {
			$counter++;
		} 

		// Validate Email Address
		if (isset($_POST['user_email']) && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
			$counter++;
		} else {
			$errors[] = "Invalid Email Address.";
		}

		// Validate Timezone
		if (isset($_POST['timezone']) && is_numeric($_POST['timezone'])) {
			$counter++;
		} 

		// Check if all the required fields have been entered
		if ($counter == '13') {
			return true;
		} else {
			return false;
		}
	}

	// Function to show an error
	function show_message($type, $message)
	{
		return $message;
	}

	// Function to write the config file
	function write_config($data)
	{

		// Config path
		$template_path 	= 'config/database.php';
		$output_path 	= $_SERVER['DOCUMENT_ROOT'] . '/' . $data['directory'] . '/application/config/database.php';

		// Open the file
		$database_file = file_get_contents($template_path);

		// Sanitize DB Password from single quotes
		$sanitized_db_pwd = preg_replace("/\'/i",'\\\'',$data['db_password']);

		$new  = str_replace("%HOSTNAME%", $data['db_hostname'], $database_file);
		$new  = str_replace("%USERNAME%", $data['db_username'], $new);
		$new  = str_replace("%PASSWORD%", $sanitized_db_pwd, $new);
		$new  = str_replace("%DATABASE%", $data['db_name'], $new);

		// Write the new database.php file
		$handle = fopen($output_path, 'w+');

		// Chmod the file, in case the user forgot
		@chmod($output_path, 0777);

		// Verify file permissions
		if (is_writable($output_path)) {

			// Write the file
			if (fwrite($handle, $new)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	// Function to write the config file
	function write_configfile($data)
	{

		// Config path
		$template_path 	= 'config/config.php';
		$output_path 	= $_SERVER['DOCUMENT_ROOT'] . '/' . $data['directory'] . '/application/config/config.php';

		// Open the file
		$database_file = file_get_contents($template_path);

		$new  = str_replace("%baselocator%", $data['locator'], $database_file);
		$new  = str_replace("%websiteurl%", $data['websiteurl'], $new);
		$new  = str_replace("%directory%", $data['directory'], $new);
		$new  = str_replace("%callbook%", $data['global_call_lookup'], $new);
		if ($data['global_call_lookup'] == 'qrz') {
			$new  = str_replace("%qrz_username%", $data['callbook_username'], $new);
			$new  = str_replace("%qrz_password%", $data['callbook_password'], $new);
			$new  = str_replace("%hamqth_username%", '', $new);
			$new  = str_replace("%hamqth_password%", '', $new);
		} else {
			$new  = str_replace("%qrz_username%", '', $new);
			$new  = str_replace("%qrz_password%", '', $new);
			$new  = str_replace("%hamqth_username%", $data['callbook_username'], $new);
			$new  = str_replace("%hamqth_password%", $data['callbook_password'], $new);
		}

		// Write the new config.php file
		$handle = fopen($output_path, 'w+');

		// Chmod the file, in case the user forgot
		@chmod($output_path, 0777);

		// Verify file permissions
		if (is_writable($output_path)) {

			// Write the file
			if (fwrite($handle, $new)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
