<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Stations resource (read-only)
 *
 * Exposes the token owner's station locations. Reuses the existing Stations
 * model for all data access; ownership is enforced against the token's
 * user_id. Write support (station:write) may be added later.
 *
 * Route:  /api/v2/stations
 * Scope:  station:read
 */
class Stations_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'station';

	/** Registry labels for this resource's scopes (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read' => __('Read station locations'),
		];
	}

	/**
	 * GET /api/v2/stations
	 * All station locations of the token owner. No pagination: users only
	 * have a handful of station profiles.
	 */
	public function index() {
		$query = $this->CI->stations->all_of_user($this->user_id());

		$stations = [];
		foreach ($query->result() as $row) {
			$stations[] = $this->format_station($row);
		}

		$this->CI->api_v2_response->respond($stations);
	}

	/**
	 * GET /api/v2/stations/{id}
	 * A single station location owned by the token holder.
	 */
	public function show($id) {
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Api_v2_exception('not_found', 'Station not found', 404);
		}

		// 404 unless the station belongs to the token owner.
		if (!$this->CI->stations->check_station_against_user($id, $this->user_id())) {
			throw new Api_v2_exception('not_found', 'Station not found', 404);
		}

		$query = $this->CI->stations->profile($id);
		if ($query->num_rows() === 0) {
			throw new Api_v2_exception('not_found', 'Station not found', 404);
		}

		$this->CI->api_v2_response->respond($this->format_station($query->row()));
	}

	// --- Internal helpers --------------------------------------------------

	/**
	 * Shape a station_profile DB row into the public API representation.
	 */
	protected function format_station($row) {
		return [
			'id'         => (int) $row->station_id,
			'name'       => $row->station_profile_name ?? null,
			'callsign'   => $row->station_callsign ?? null,
			'gridsquare' => $row->station_gridsquare ?? null,
			'city'       => $row->station_city ?? null,
			'dxcc'       => isset($row->station_dxcc) ? (int) $row->station_dxcc : null,
			'country'    => $row->station_country ?? null,
			'iota'       => $row->station_iota ?? null,
			'sota'       => $row->station_sota ?? null,
			'wwff'       => $row->station_wwff ?? null,
			'pota'       => $row->station_pota ?? null,
			'active'     => isset($row->station_active) && $row->station_active == 1,
		];
	}
}
