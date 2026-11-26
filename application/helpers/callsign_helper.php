<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Callsign helpers shared by the calltester, zonechecker and countychecker views.
 * Pure functions, no CI dependencies.
 */

if (!function_exists('calltester_base_call')) {
	/**
	 * Resolve the home callsign from a (possibly portable) callsign: the longest
	 * "/"-separated segment is the base call, e.g. TF/DL2NWK/P -> DL2NWK.
	 *
	 * Used for lookups so the link targets the station's real callsign and the
	 * resulting URL has no "/" to break path-based routing.
	 *
	 * @param string $call Callsign, possibly with prefix and/or suffix segments
	 * @return string Base callsign in upper case
	 */
	function calltester_base_call($call) {
		$base = '';
		foreach (explode('/', $call) as $seg) {
			if (strlen($seg) > strlen($base)) {
				$base = $seg;
			}
		}
		return strtoupper(trim($base));
	}
}
