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

		if ($data['directory'] ?? '' != "") {
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
		if (isset($data['firstname']) && !empty($data['firstname'])) {
			$counter++;
		}

		// Validate Last Name
		if (isset($data['lastname']) && !empty($data['lastname'])) {
			$counter++;
		}

		// Validate Username
		if (isset($data['username']) && !empty($data['username'])) {
			$counter++;
		}

		// Validate Callsign
		if (isset($data['callsign']) && !empty($data['callsign'])) {
			$counter++;
		}

		// Validate Password
		if (isset($data['password']) && !empty($data['password'])) {
			$counter++;
		}

		// Validate Locator
		if (isset($data['userlocator']) && !empty($data['userlocator'])) {
			$locator = $data['userlocator'];
			if (preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}$/i', $locator)) {
				$counter++;
			} else {
				$errors[] = "Invalid Maidenhead Locator format.";
			}
		} else {
			$errors[] = "Locator is required.";
		}

		// Validate Confirm Password
		if (isset($data['cnfm_password']) && !empty($data['cnfm_password'])) {
			$counter++;
		}

		// Validate Email Address
		if (isset($data['user_email']) && filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
			$counter++;
		} else {
			$errors[] = "Invalid Email Address.";
		}

		// Validate Timezone
		if (isset($data['timezone']) && is_numeric($data['timezone'])) {
			$counter++;
		}

		// Check if all the required fields have been entered
		if ($counter == '13') {
			return true;
		} else {
			log_message('error', 'Failed to validate POST data');
			return false;
		}
	}

	// Function to write the config file
	function write_config($data) {

		$template_path 	= 'config/database.php';
		$output_path 	= $_SERVER['DOCUMENT_ROOT'] . '/' . $data['directory'] . '/application/config/database.php';
		if (isset($_ENV['CI_ENV'])) {
			$output_path 	= $_SERVER['DOCUMENT_ROOT'] . '/' . $data['directory'] . '/application/config/'.$_ENV['CI_ENV'].'/database.php';
		}

		// Open the file
		$database_file = file_get_contents($template_path);

		// Sanitize DB Password from single quotes
		$sanitized_db_pwd = preg_replace("/\\\\/i",'\\\\\\\\',$data['db_password']);       // Escape the Escape char ( '\' becomes '\\' )
		$sanitized_db_pwd = preg_replace("/\'/i",'\\\\\'',$sanitized_db_pwd);  // Escape the ' ( ' becomes \' )

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
				if(file_exists($output_path)) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	// Function to write the config file
	function write_configfile($data) {

		$template_path 	= 'config/config.php';
		$output_path 	= '../application/config/config.php';
		if (isset($_ENV['CI_ENV'])) {
			$output_path 	= '../application/config/'.$_ENV['CI_ENV'].'/config.php';
		}

		// Open the file
		$database_file = file_get_contents($template_path);

		// creating a unique encryption key
		$encryptionkey = uniqid(bin2hex(random_bytes(8)), false);

		$new  = str_replace("%baselocator%", strtoupper($data['userlocator']), $database_file);
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
		$new = str_replace("%encryptionkey%", $encryptionkey, $new);
		$new = str_replace("'%log_threshold%'", $data['log_threshold'], $new);

		// Write the new config.php file
		$handle = fopen($output_path, 'w+');

		// Verify file permissions
		if (is_writable($output_path)) {

			// Write the file
			if (fwrite($handle, $new)) {
				if(file_exists($output_path)) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
