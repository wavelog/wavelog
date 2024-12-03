<?php

class Core
{

	// Function to validate the post data
	function validate_post($data)
	{
		// Counter variable
		$counter = 0;
		$errors = [];

		// Validate the hostname
		if (isset($data['db_hostname']) and !empty($data['db_hostname'])) {
			$counter++;
		} else {
			$errors[] = "DB Hostname is missing.";
		}

		// Validate the username
		if (isset($data['db_username']) and !empty($data['db_username'])) {
			$counter++;
		} else {
			$errors[] = "DB Username is missing.";
		}

		// Validate the password
		if (isset($data['db_password']) and !empty($data['db_password'])) {
			$counter++;
		} else {
			$errors[] = "DB Password is missing.";
		}

		// Validate the database
		if (isset($data['db_name']) and !empty($data['db_name'])) {
			$counter++;
		} else {
			$errors[] = "DB Name is missing.";
		}

		if ($data['directory'] ?? '' != "") {
			if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $data['directory'])) {
				//pass folders real
				$counter++;
			} else {
				$errors[] = "Directory " . $data['directory'] . " does not exist.";
			}
		} else {
			// directory is not set so nothing to check here
			$counter++;
		}

		// Validate First Name
		if (isset($data['firstname']) && !empty($data['firstname'])) {
			$counter++;
		} else {
			$errors[] = "First Name is missing.";
		}

		// Validate Last Name
		if (isset($data['lastname']) && !empty($data['lastname'])) {
			$counter++;
		} else {
			$errors[] = "Last Name is missing.";
		}

		// Validate Username
		if (isset($data['username']) && !empty($data['username'])) {
			$counter++;
		} else {
			$errors[] = "Username is missing.";
		}

		// Validate Callsign
		if (isset($data['callsign']) && !empty($data['callsign'])) {
			$counter++;
		} else {
			$errors[] = "Callsign is missing.";
		}

		// Validate Password
		if (isset($data['password']) && !empty($data['password'])) {
			$counter++;
		} else {
			$errors[] = "User Password is missing.";
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
			$errors[] = "Locator is missing.";
		}

		// Validate Confirm Password
		if (isset($data['cnfm_password']) && !empty($data['cnfm_password'])) {
			$counter++;
		} else {
			$errors[] = "Confirm Password is missing.";
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
		} else {
			$errors[] = "Invalid Timezone.";
		}

		// Check if all the required fields have been entered
		if ($counter == '14') {
			log_message('info', 'Data validation passed.');
			return true;
		} else {
			log_message('error', 'Data validation failed.');
			foreach ($errors as $error) {
				log_message('error', $error);
			}
			return false;
		}
	}

	// Function to write the database config file
	function write_config($data) {

		$template_path 	= 'config/database.php';
		$output_path 	= $_SERVER['DOCUMENT_ROOT'] . '/' . $data['directory'] . '/application/config/database.php';
		if (isset($_ENV['CI_ENV'])) {
			$output_path 	= $_SERVER['DOCUMENT_ROOT'] . '/' . $data['directory'] . '/application/config/'.$_ENV['CI_ENV'].'/database.php';
		}

		// Open the file
		$database_file = file_get_contents($template_path);

		// Sanitize DB Password from single quotes
		$sanitized_db_pwd = preg_replace("/\\\\/i",'\\\\\\\\',$data['db_password']);       	// Escape the Escape char ( '\' becomes '\\' )
		$sanitized_db_pwd = preg_replace("/\'/i",'\\\\\'',$sanitized_db_pwd);  				// Escape the ' ( ' becomes \' )

		$new  = str_replace("%HOSTNAME%", $data['db_hostname'], $database_file);
		$new  = str_replace("%USERNAME%", $data['db_username'], $new);
		$new  = str_replace("%PASSWORD%", $sanitized_db_pwd, $new);
		$new  = str_replace("%DATABASE%", $data['db_name'], $new);

		// Write the new database.php file
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

		$callbooks = ['qrz', 'hamqth', 'qrzcq'];
		
		if (in_array($data['global_call_lookup'], $callbooks)) {
			$c_username = '%' . $data['global_call_lookup'] . '_username%';
			$c_password = '%' . $data['global_call_lookup'] . '_password%';

			$rest_callbooks = array_diff($callbooks, [$data['global_call_lookup']]);

			foreach ($rest_callbooks as $callbook) {
				$new = str_replace('%' . $callbook . '_username%', '', $new);
				$new = str_replace('%' . $callbook . '_password%', '', $new);
			}

			$new = str_replace($c_username, $data['callbook_username'], $new);
			$new = str_replace($c_password, $data['callbook_password'], $new);
		} else {
			foreach ($callbooks as $callbook) {
				$new = str_replace('%' . $callbook . '_username%', '', $new);
				$new = str_replace('%' . $callbook . '_password%', '', $new);
			}
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
