<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Library for IP validation
 */
class Network
{
	// returns true or false if client ip matches the ip range defined in config.php
	function validate_client_ip($allowed_ip) {

		$allowed_ips = explode(', ', $allowed_ip);

		$client_ip = $this->get_client_ip();

		$ip_allowed = false;
		foreach ($allowed_ips as $allowed_ip) {
			if ($this->ip_in_range($client_ip, $allowed_ip)) {
				$ip_allowed = true;
				break;
			}
		}

		return $ip_allowed;
	}

	// gets the client ip
	function get_client_ip(){

		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			//ip from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			//ip pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		
		return $ip;
	}

	// calculates the ip range
	function ip_in_range($ip, $range) {
		if ($range == 'localhost') {
			$range = '127.0.0.1';
		}
		if (strpos($range, '/') !== false) {
			list($subnet, $mask) = explode('/', $range, 2);
			if ((ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet)) {
				return true;
			}
		} else {
			if (ip2long($ip) === ip2long($range)) {
				return true;
			}
		}
		return false;
	}

}
