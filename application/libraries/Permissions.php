<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Permissions {

	/*
	 *	Class Description: Provide functions for checking file and folder permissions
	 */

	function is_really_writable($folder) {
		// Get the absolute path to the folder
		$path = FCPATH . $folder;

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
		} catch(Exception $e) { 
			return false;
		}

		return false;
	}
}

/* End of file Permissions.php */
