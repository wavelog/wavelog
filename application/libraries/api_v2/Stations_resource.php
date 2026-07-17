<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Stations resource
 *
 * Exposes the token owner's station locations (CRUD). Reuses the existing
 * Stations / Stationsetup_model data access; ownership is always enforced
 * against the token's user_id, never the session.
 *
 * Route:  /api/v2/stations
 * Scope:  station:read / station:write / station:delete
 *
 * Only the core location fields are writable via the API; external-service
 * credentials (QRZ/HRDLog/ClubLog/OQRS/webADIF/eQSL) are intentionally not
 * exposed here and keep their defaults on create.
 */
class Stations_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'station';

	/**
	 * DXCC entities that have ADIF subdivisions (secondary administrative
	 * areas); the county field is only kept for these, mirroring
	 * Stations::add()/edit(). Anything else clears the county.
	 */
	protected const COUNTY_DXCC = [6, 110, 291, 15, 54, 61, 126, 151, 288, 339, 170, 21, 29, 32, 281];

	/** Registry labels for this resource's scopes (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read'   => __('Read station locations'),
			'write'  => __('Create and update station locations'),
			'delete' => __('Delete station locations'),
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
		$this->require_owned_station($id);

		$query = $this->CI->stations->profile($id);
		if ($query->num_rows() === 0) {
			throw new Api_v2_exception('not_found', 'Station not found', 404);
		}

		$this->CI->api_v2_response->respond($this->format_station($query->row()));
	}

	/**
	 * POST /api/v2/stations
	 * Create a station location. Required body fields: name, callsign.
	 */
	public function create() {
		$this->require_write();
		$this->CI->load->model('stations');
		$this->CI->load->model('stationsetup_model');

		$body = $this->body();
		$this->require_scalar_fields($body);
		$this->require_present($body, ['name', 'callsign']);
		$this->validate_grid($body);

		// The first station of a user becomes the active/default one, mirroring
		// Stations::add(); find_active() is session-bound and unusable here.
		$active = $this->user_has_active_station() ? 0 : 1;

		$dbdata = $this->build_columns($body, true, null);
		$dbdata['user_id']         = $this->user_id();
		$dbdata['station_active']  = $active;
		$dbdata['webadifapiurl']   = 'https://qo100dx.club/api';
		$dbdata['station_uuid']    = $this->CI->db->query("SELECT UUID() as uuid")->row()->uuid;

		$optiondata = ['eqsl_default_qslmsg' => '', 'link_active_logbook' => 'false'];

		$result = $this->CI->stationsetup_model->save_location($dbdata, $optiondata, $this->user_id());
		if ($result === false) {
			throw new Api_v2_exception('validation_error', 'Missing required field(s): name, callsign', 400, ['missing' => ['name', 'callsign']]);
		}
		if ($result === 0) {
			throw new Api_v2_exception('conflict', 'An identical station location already exists', 409);
		}

		$row = $this->CI->stations->profile_by_uuid($dbdata['station_uuid'], $this->user_id());
		$headers = $row ? ['Location' => base_url('index.php/api/v2/stations/' . (int) $row->station_id)] : [];
		$this->CI->api_v2_response->respond($row ? $this->format_station($row) : null, 201, null, $headers);
	}

	/**
	 * PATCH /api/v2/stations/{id}
	 * Partial update: only the fields present in the body are changed.
	 */
	public function update($id) {
		$this->apply_update($id, false);
	}

	/**
	 * PUT /api/v2/stations/{id}
	 * Full replace of the editable fields: required fields must be present,
	 * every omitted optional field is reset to its default.
	 */
	public function replace($id) {
		$this->apply_update($id, true);
	}

	/**
	 * DELETE /api/v2/stations/{id}
	 *
	 * Mirrors the web UI: the active station cannot be deleted (409); any other
	 * station is removed together with all of its QSOs, QSL/eQSL images and
	 * related data (Stations::delete() -> deletelog()). This is destructive by
	 * design and matches Stationsetup::DeleteStation_json().
	 */
	public function delete($id) {
		$this->require_delete();
		$row = $this->require_owned_station($id);

		// The internal Stations::delete() active guard uses the session and is
		// a no-op for token requests, so enforce it here from the DB row.
		if ((int) $row->station_active === 1) {
			throw new Api_v2_exception('conflict', 'The active station location cannot be deleted', 409);
		}

		// Ownership is already verified; force = true bypasses the (session-based)
		// internal guard, which we replaced with the explicit check above.
		$this->CI->stations->delete((int) $id, true, $this->user_id());

		$this->CI->api_v2_response->no_content();
	}

	// --- Internal helpers --------------------------------------------------

	/**
	 * Shared PATCH/PUT implementation.
	 *
	 * @param int  $id      station_id from the path.
	 * @param bool $replace true for PUT (reset omitted fields), false for PATCH.
	 */
	protected function apply_update($id, $replace) {
		$this->require_write();
		$this->CI->load->model('stations');
		$this->CI->load->model('stationsetup_model');

		$current = $this->require_owned_station($id);

		$body = $this->body();
		$this->require_scalar_fields($body);
		if ($replace) {
			$this->require_present($body, ['name', 'callsign']);
		}
		$this->validate_grid($body);

		$data = $this->build_columns($body, $replace, (int) $current->station_dxcc);
		if (empty($data)) {
			throw new Api_v2_exception('validation_error', 'No editable fields in request body', 400);
		}

		$this->CI->stationsetup_model->update_location((int) $id, $data, $this->user_id());

		$this->CI->api_v2_response->respond($this->format_station($this->CI->stations->profile_clean($id)));
	}

	/**
	 * Map the JSON body to station_profile columns with the same normalisation
	 * the web UI applies. On $replace (PUT/create) omitted optional fields get
	 * their reset default; on PATCH only supplied fields are touched.
	 *
	 * @param array    $body         Decoded request body (API field names).
	 * @param bool     $replace      Reset omitted fields to their default.
	 * @param int|null $current_dxcc Existing DXCC (for county gating on PATCH).
	 * @return array column => value
	 */
	protected function build_columns($body, $replace, $current_dxcc) {
		$data = [];
		foreach ($this->writable_fields() as $key => $def) {
			list($col, $normalizer, $default) = $def;
			if (array_key_exists($key, $body)) {
				$data[$col] = $this->normalize($normalizer, $body[$key]);
			} elseif ($replace) {
				$data[$col] = $default;
			}
		}

		// County is only meaningful for DXCC entities with subdivisions; clear
		// it otherwise. Effective DXCC = the value in this request, else the
		// station's existing one (PATCH without a dxcc field).
		$effective_dxcc = array_key_exists('dxcc', $body) ? (int) $body['dxcc'] : $current_dxcc;
		if (array_key_exists('cnty', $body)) {
			$data['station_cnty'] = in_array($effective_dxcc, self::COUNTY_DXCC, true)
				? xss_clean((string) $body['cnty'])
				: '';
		} elseif ($replace) {
			$data['station_cnty'] = null;
		}

		return $data;
	}

	/**
	 * Writable core location fields: API key => [column, normalizer, reset default].
	 * External-service credentials are intentionally excluded.
	 */
	protected function writable_fields() {
		return [
			'name'       => ['station_profile_name', 'string',      ''],
			'callsign'   => ['station_callsign',     'callsign',    ''],
			'gridsquare' => ['station_gridsquare',   'upper',       ''],
			'city'       => ['station_city',         'string',      ''],
			'dxcc'       => ['station_dxcc',         'int_or_null', null],
			'cq'         => ['station_cq',           'int_or_null', null],
			'itu'        => ['station_itu',          'int_or_null', null],
			'state'      => ['state',                'string',      null],
			'iota'       => ['station_iota',         'upper',       ''],
			'sota'       => ['station_sota',         'upper',       ''],
			'wwff'       => ['station_wwff',         'upper',       null],
			'pota'       => ['station_pota',         'pota',        null],
			'sig'        => ['station_sig',          'upper',       null],
			'sig_info'   => ['station_sig_info',     'upper',       null],
			'power'      => ['station_power',        'power',       null],
		];
	}

	/**
	 * Normalise a single field value the same way the web UI does before it
	 * reaches the database.
	 */
	protected function normalize($normalizer, $value) {
		switch ($normalizer) {
			case 'upper':
				return xss_clean(strtoupper(trim((string) $value)));
			case 'callsign':
				return str_replace('Ø', '0', trim(xss_clean(strtoupper((string) $value))));
			case 'pota':
				return preg_replace('/\s+/', '', xss_clean(strtoupper((string) $value)));
			case 'int_or_null':
				return is_numeric($value) ? (int) $value : null;
			case 'power':
				return is_numeric($value) ? xss_clean((string) $value) : null;
			case 'string':
			default:
				return xss_clean((string) $value);
		}
	}

	/**
	 * Verify a station exists and belongs to the token owner, returning its row.
	 *
	 * @throws Api_v2_exception 404 when missing or not owned.
	 * @return object station_profile row.
	 */
	protected function require_owned_station($id) {
		if (!is_numeric($id) || (int) $id < 1
			|| !$this->CI->stations->check_station_against_user($id, $this->user_id())) {
			throw new Api_v2_exception('not_found', 'Station not found', 404);
		}
		$row = $this->CI->stations->profile_clean($id);
		if ($row === null) {
			throw new Api_v2_exception('not_found', 'Station not found', 404);
		}
		return $row;
	}

	/**
	 * Throw 400 unless every listed field is present and non-empty in the body.
	 */
	protected function require_present($body, $fields) {
		$missing = [];
		foreach ($fields as $field) {
			if (empty($body[$field])) {
				$missing[] = $field;
			}
		}
		if (!empty($missing)) {
			throw new Api_v2_exception(
				'validation_error',
				'Missing required field(s): ' . implode(', ', $missing),
				400,
				['missing' => $missing]
			);
		}
	}

	/**
	 * Validate the gridsquare, if supplied, with the same rule as the web UI
	 * (Qra::validate_grid). An empty value is allowed.
	 *
	 * @throws Api_v2_exception 400 on a malformed locator.
	 */
	protected function validate_grid($body) {
		if (!array_key_exists('gridsquare', $body)) {
			return;
		}
		$grid = trim((string) $body['gridsquare']);
		if ($grid === '') {
			return;
		}
		$this->CI->load->library('Qra');
		if (!$this->CI->qra->validate_grid($grid)) {
			throw new Api_v2_exception('validation_error', 'Invalid grid locator', 400, ['field' => 'gridsquare']);
		}
	}

	/**
	 * Whether the token owner already has an active station location.
	 */
	protected function user_has_active_station() {
		foreach ($this->CI->stations->all_of_user($this->user_id())->result() as $row) {
			if ((int) $row->station_active === 1) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Shape a station_profile DB row into the public API representation. The
	 * shape is symmetric with the writable fields so a GET result round-trips
	 * through PUT.
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
			'cq'         => isset($row->station_cq) ? (int) $row->station_cq : null,
			'itu'        => isset($row->station_itu) ? (int) $row->station_itu : null,
			'state'      => $row->state ?? null,
			'cnty'       => $row->station_cnty ?? null,
			'iota'       => $row->station_iota ?? null,
			'sota'       => $row->station_sota ?? null,
			'wwff'       => $row->station_wwff ?? null,
			'pota'       => $row->station_pota ?? null,
			'sig'        => $row->station_sig ?? null,
			'sig_info'   => $row->station_sig_info ?? null,
			'power'      => isset($row->station_power) && is_numeric($row->station_power) ? (int) $row->station_power : null,
			'active'     => isset($row->station_active) && $row->station_active == 1,
		];
	}
}
