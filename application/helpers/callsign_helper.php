<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// Parse a callsign into core, prefix, and suffix.
if (!function_exists('parse_callsign')) {
	function parse_callsign($callsign) {
		$callsign = strtoupper(trim($callsign));
		$prefix = null;
		$suffix = null;
		$core = $callsign;
		// Split by '/'
		$parts = explode('/', $callsign);
		// Find the first part that matches a valid callsign pattern (letters+digits, at least one digit)
		$core_index = null;
		foreach ($parts as $i => $part) {
			if (preg_match('/[A-Z]+[0-9]+[A-Z]*/', $part)) {
				$core = $part;
				$core_index = $i;
				break;
			}
		}
		if ($core_index !== null) {
			if ($core_index > 0) {
				$prefix = implode('/', array_slice($parts, 0, $core_index));
			}
			if ($core_index < count($parts) - 1) {
				$suffix = implode('/', array_slice($parts, $core_index + 1));
			}
		}
		return [
			'core' => $core,
			'prefix' => $prefix,
			'suffix' => $suffix
		];
	}
}

?>
