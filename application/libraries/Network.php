<?php defined('BASEPATH') or exit('No direct script access allowed');

/***
 * Library for IP validation
 */
class Network
{
	// returns true or false if client ip matches the ip range defined in config.php
	function validate_client_ip($allowed_ips) {

		$host_array = explode(', ', $allowed_ips);
		$client_ip = $this->get_client_ip();

		$result = false;
		foreach ($host_array as $allowed_host) {

			// remove the http scheme
			if (substr($allowed_host, 0, 4) === "http") {
				$p = parse_url($allowed_host);
				$host = $p['host'];
			} else {
				$host = $allowed_host;
			}

			// if the user typed in just 0.0.0.0 we need the CIDR
			if ($host == '0.0.0.0'){
				$host .= '/0';
			}

			// we need an IP adress. So get the IP by hostname if it is one.
			if ((bool)ip2long($host)) {
				$allowed_ip = $host;
			} else {
				$allowed_ip = gethostbyname($host);
			}

			// now we can check if the ip is in range
			if ($this->ip_in_range($client_ip, $allowed_ip)) {
				$result = true;
				break;
			}
		}
		
		return $result;
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
