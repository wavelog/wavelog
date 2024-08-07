<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Wwff library is a World Wide Flora Fauna client
 */
class Wwff
{
	// return summit references matching the provided query
	public function get($query): array
	{
		if (empty($query)) {
			return [];
		}

		$json = [];
		$ref = strtoupper($query);

		$file = 'updates/wwff.txt';

		if (is_readable($file)) {
			$lines = file($file, FILE_IGNORE_NEW_LINES);
			$input = preg_quote($ref, '~');
			$reg = '~^' . $input . '(.*)$~';
			$result = preg_grep($reg, $lines);

			foreach ($result as &$value) {
				// Limit to 100 as to not slowdown browser too much
				if (count($json) <= 100) {
					$json[] = ["name" => $value];
				}
			}
		} else {
			$src = 'assets/resources/wwff.txt';
			if (copy($src, $file)) {
				$this->get($query);
			} else {
				log_message('error', 'Failed to copy source file ('.$src.') to new location. Check if this path has the right permission: '.$file);
			}
		}

		return $json;
	}

	// fetches the summit information from WWFF
	public function info($ref) {
		$url = 'https://www.cqgma.org/wwff_ref.php?ref='.$ref;

		// Let's use cURL instead of file_get_contents
		// begin script
		$ch = curl_init();

		// basic curl options for all requests
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// use the URL we built
		curl_setopt($ch, CURLOPT_URL, $url);

		$summit_info = curl_exec($ch);

		// Close cURL handle
		curl_close($ch);

		return $summit_info;
	}
}
