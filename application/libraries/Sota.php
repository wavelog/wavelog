<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Sota library is a Summit On The Air client
 */
class Sota
{
	// fetches the summit information from SOTA
	public function info($summit) {
		$url = 'https://api-db2.sota.org.uk/api/summits/' . $summit;

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
