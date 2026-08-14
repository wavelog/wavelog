<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Wwff library is a World Wide Flora Fauna client
 */
class Wwff
{
	// fetches the summit information from WWFF
	public function info($ref) {
		$url = 'https://www.gma.rocks/wwff_ref.php?ref='.$ref;

		// Let's use cURL instead of file_get_contents
		// begin script
		$ch = curl_init();

		// basic curl options for all requests
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// use the URL we built
		curl_setopt($ch, CURLOPT_URL, $url);

		$summit_info = curl_exec($ch);

		return $summit_info;
	}
}
