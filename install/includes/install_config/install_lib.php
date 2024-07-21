<?php

// Function to check if a PHP extension is installed
function isExtensionInstalled($extensionName) {
	return in_array($extensionName, get_loaded_extensions());
}

// function to switch the language based on the user selection
function switch_lang($new_language) {
	global $gt_conf;
	setcookie($gt_conf['lang_cookie'], $new_language);
}

// check if page is called with https or not
function is_https() {
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
		return true;
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
		return true;
	}
	if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
		return true;
	}
	return false;
}

// Folder permission checks
function is_really_writable($path) {

	// Check if the folder exists
	if (!file_exists($path)) {
		return false;
	}

	// Check if the folder is writable
	try {
		if (is_writable($path)) {
			// Check if the subdirectories are writable (recursive check)
			$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
			foreach ($iterator as $item) {
				if ($item->isDir() && basename($item->getPathName()) != '..') {
					if (!is_writable($item->getRealPath())) {
						return false;
					}
				}
			}

			return true;
		}
	} catch (Exception $e) {
		return false;
	}

	return false;
}
