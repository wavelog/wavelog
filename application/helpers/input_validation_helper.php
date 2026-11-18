<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Central input validation for user-supplied QSO data.
 *
 * Single source of truth for Maidenhead locator/gridsquare checks and the
 * satellite-mode / propagation-mode format checks. Pure functions, no CI
 * dependencies, safe to use in models, controllers, libraries and tests.
 *
 * Dialects (kept distinct on purpose):
 *  - valid_grid()      single locator, 2/4/6/8/10 chars
 *  - valid_vucc()      VUCC grid line/corner: exactly 2 or 4 grids, uniform length
 *  - valid_grid_list() loose ADIF-import style: 1..n comma separated grids, each 4-10 chars
 */

if (!function_exists('valid_grid')) {
	/**
	 * Validate a single Maidenhead locator (field, square, subsquare, extended square, subextended square).
	 *
	 * @param string $grid      Locator to check, case-insensitive, surrounding/embedded whitespace ignored.
	 * @param int    $min_chars Minimum accepted precision (2, 4, 6, 8 or 10). Default 2.
	 * @return bool True if the locator is well-formed and at least $min_chars long.
	 */
	function valid_grid($grid, $min_chars = 2) {
		$g = strtoupper(preg_replace('/\s+/', '', (string) $grid));
		switch (strlen($g)) {
			case 10: return $min_chars <= 10 && preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}[0-9]{2}[A-X]{2}$/', $g) === 1;
			case 8:  return $min_chars <= 8  && preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}[0-9]{2}$/', $g) === 1;
			case 6:  return $min_chars <= 6  && preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}$/', $g) === 1;
			case 4:  return $min_chars <= 4  && preg_match('/^[A-R]{2}[0-9]{2}$/', $g) === 1;
			case 2:  return $min_chars <= 2  && preg_match('/^[A-R]{2}$/', $g) === 1;
			default: return false;
		}
	}
}

if (!function_exists('valid_vucc')) {
	/**
	 * Validate a VUCC grids string: a grid line (2 grids) or grid corner (4 grids),
	 * comma separated, all grids of uniform length (4 or 6 characters each).
	 *
	 * @param string $grids Comma separated grid list, case-insensitive, whitespace ignored.
	 * @return bool True if the string is a valid VUCC line or corner.
	 */
	function valid_vucc($grids) {
		$g = strtoupper(preg_replace('/\s+/', '', (string) $grids));
		$parts = explode(',', $g);
		if (count($parts) != 2 && count($parts) != 4) {
			return false;
		}
		foreach (['/^[A-R]{2}[0-9]{2}$/', '/^[A-R]{2}[0-9]{2}[A-X]{2}$/'] as $rx) {
			$all = true;
			foreach ($parts as $p) {
				if (preg_match($rx, $p) !== 1) {
					$all = false;
					break;
				}
			}
			if ($all) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('valid_grid_list')) {
	/**
	 * Validate a loose ADIF-import style grid string: one or more comma separated
	 * grids, each 4 to 10 characters long ("junction" style used by GRIDSQUARE and
	 * VUCC_GRIDS imports; count is unrestricted, mixed lengths allowed).
	 *
	 * @param string $grids Comma separated grid list, segments may carry whitespace.
	 * @return bool True if every segment is a well-formed 4-10 character locator.
	 */
	function valid_grid_list($grids) {
		if ($grids === null || $grids === '') {
			return false;
		}
		foreach (explode(',', (string) $grids) as $p) {
			if (!valid_grid(trim($p), 4)) {
				return false;
			}
		}
		return true;
	}
}

if (!function_exists('valid_sat_mode')) {
	/**
	 * Validate an ADIF SAT_MODE value: a single upper-case letter (uplink designator),
	 * optionally followed by a slash and a second letter (downlink designator).
	 * The legacy slash-less two-letter form (e.g. "VU") is accepted as well.
	 *
	 * @param string $mode Satellite mode, case-insensitive, surrounding whitespace ignored.
	 * @return bool True if the value is a well-formed satellite mode.
	 */
	function valid_sat_mode($mode) {
		return preg_match('/^[A-Z](\/?[A-Z])?$/', strtoupper(trim((string) $mode))) === 1;
	}
}

if (!function_exists('valid_prop_mode')) {
	/**
	 * Validate a propagation mode against a whitelist of allowed ADIF codes.
	 *
	 * @param string $mode    Propagation mode, case-insensitive, surrounding whitespace ignored.
	 * @param array  $allowed List of allowed codes (e.g. array_keys(config item 'adif_propmodes')).
	 * @return bool True if the (normalised) value is contained in the whitelist.
	 */
	function valid_prop_mode($mode, array $allowed) {
		return in_array(strtoupper(trim((string) $mode)), $allowed, true);
	}
}
