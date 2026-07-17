<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - QSOs resource (reference implementation)
 *
 * Demonstrates the full CRUD pattern every other v2 resource should follow.
 * It reuses the existing Logbook_model / Stations data layer rather than
 * reimplementing QSO logic:
 *   - reads via Logbook_model::get_qso() (trusted, after our own ownership check)
 *   - lists via Logbook_model::get_qsos()
 *   - creates via Logbook_model::import_bulk() (the same path the v1 API uses)
 *   - updates via Logbook_model::update_qso_columns() (PATCH/PUT)
 *   - deletes via Logbook_model::delete() (handles OQRS/QSL/eQSL + cache)
 *
 * PATCH updates only the fields present in the body; PUT additionally resets
 * every other editable field to its default. Both verbs touch only the field
 * whitelist in editable_fields(): QSL/LoTW/eQSL bookkeeping, DXCC/country
 * recalculation and the MY_* station refs are deliberately out of scope here.
 *
 * Unit note: POST follows ADIF field semantics (freq in MHz), while PATCH/PUT
 * take freq/freq_rx in Hz - matching the representation returned by GET.
 * Unit suffixes work too (e.g. "7.0475M" = 7047500 Hz, see parse_frequency()).
 *
 * Ownership in v2 is enforced against the token's user_id (not the web
 * session): we resolve the token owner's station ids and reject anything else.
 *
 * Route:  /api/v2/qso
 * Scope:  qso:read / qso:write / qso:delete
 */
class Qso_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'qso';

	/** Registry labels for this resource's scopes (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read'   => __('Read QSOs'),
			'write'  => __('Create and update QSOs'),
			'delete' => __('Delete QSOs'),
		];
	}

	/**
	 * GET /api/v2/qso
	 * Paginated list of the key owner's QSOs. Optional ?band= filter.
	 */
	public function index() {
		$this->CI->load->model('logbook_model');

		$page = $this->pagination();
		$band = $this->param('band', '');

		$location_ids = $this->owner_station_ids();
		if (empty($location_ids)) {
			$this->CI->api_v2_response->respond([], 200, $this->list_meta($page, 0));
			return;
		}

		$query = $this->CI->logbook_model->get_qsos(
			$page['per_page'],
			$page['offset'],
			$location_ids,
			$band
		);

		// get_qsos() returns a CI result object, or a plain array() when there
		// are no matching logbook locations.
		$qsos = [];
		if (is_object($query)) {
			foreach ($query->result() as $row) {
				$qsos[] = $this->format_qso($row);
			}
		}

		$this->CI->api_v2_response->respond($qsos, 200, $this->list_meta($page, count($qsos)));
	}

	/**
	 * GET /api/v2/qso/{id}
	 * A single QSO owned by the key holder.
	 */
	public function show($id) {
		$this->require_numeric_id($id);
		$this->CI->load->model('logbook_model');

		// 404 unless the QSO belongs to the key owner.
		if (!$this->CI->logbook_model->check_qso_is_accessible($id, $this->user_id())) {
			throw new Api_v2_exception('not_found', 'QSO not found', 404);
		}

		// Trusted read: ownership already verified against the key's user_id.
		$query = $this->CI->logbook_model->get_qso($id, true);
		if ($query === null || $query->num_rows() === 0) {
			throw new Api_v2_exception('not_found', 'QSO not found', 404);
		}

		$this->CI->api_v2_response->respond($this->format_qso($query->row()));
	}

	/**
	 * POST /api/v2/qso
	 * Create a single QSO from JSON fields (not ADIF). Required body fields:
	 *   station_profile_id, call, band, mode, qso_date (YYYY-MM-DD), time_on (HHMM[SS])
	 */
	public function create() {
		$this->require_write();
		$this->CI->load->model('logbook_model');
		$this->CI->load->model('stations');

		$body = $this->body();
		$this->require_scalar_fields($body);

		$station_profile_id = $body['station_profile_id'] ?? null;
		if ($station_profile_id === null
			|| !$this->CI->stations->check_station_against_user($station_profile_id, $this->user_id())) {
			throw new Api_v2_exception('forbidden', 'station_profile_id does not belong to the API key owner', 403);
		}

		// Minimal required-field validation; the model handles the rest.
		$required = ['call', 'band', 'mode', 'qso_date', 'time_on'];
		$missing = [];
		foreach ($required as $field) {
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

		// Build an ADIF-style record (lowercase keys) for the import pipeline.
		$record = $this->body_to_record($body);

		// apicall = true and skipStationCheck = true: we already verified the
		// station belongs to the key owner above, so skip the session-based check.
		$result = $this->CI->logbook_model->import_bulk(
			[$record],
			$station_profile_id,
			true,   // skipDuplicate
			false, false, false, false, false, false, false,
			false,  // skipexport
			false,  // operatorName
			true,   // apicall
			true    // skipStationCheck
		);

		if (($result['qsocount'] ?? 0) < 1) {
			throw new Api_v2_exception(
				'validation_error',
				trim(strip_tags($result['errormessage'] ?? 'QSO could not be created')),
				400,
				$result['structured_errors'] ?? null
			);
		}

		// Resolve the freshly inserted QSO by primary key for the response.
		$new_id = $result['inserted_id'] ?? null;
		$created = null;
		if ($new_id) {
			$query = $this->CI->logbook_model->get_qso($new_id, true);
			if ($query !== null && $query->num_rows() > 0) {
				$created = $this->format_qso($query->row());
			}
		}

		$headers = $new_id ? ['Location' => base_url('index.php/api/v2/qso/' . $new_id)] : [];
		$this->CI->api_v2_response->respond($created ?? ['id' => $new_id], 201, null, $headers);
	}

	/**
	 * PATCH /api/v2/qso/{id}
	 * Partial update: only the fields present in the body are changed.
	 */
	public function update($id) {
		$this->apply_update($id, false);
	}

	/**
	 * PUT /api/v2/qso/{id}
	 * Full replace of the editable fields: required fields must be present,
	 * every omitted optional field is reset to its default.
	 */
	public function replace($id) {
		$this->apply_update($id, true);
	}

	/**
	 * DELETE /api/v2/qso/{id}
	 */
	public function delete($id) {
		$this->require_delete();
		$this->require_numeric_id($id);
		$this->CI->load->model('logbook_model');

		// 404 unless the QSO belongs to the key owner.
		if (!$this->CI->logbook_model->check_qso_is_accessible($id, $this->user_id())) {
			throw new Api_v2_exception('not_found', 'QSO not found', 404);
		}

		// The model handles the full teardown (OQRS, QSL/eQSL images, cache)
		// and re-checks ownership against the same user_id.
		$this->CI->logbook_model->delete($id, $this->user_id());

		$this->CI->api_v2_response->no_content();
	}

	// --- Internal helpers --------------------------------------------------

	/**
	 * Shared PATCH/PUT implementation.
	 *
	 * @param int  $id      QSO primary key from the path.
	 * @param bool $replace true for PUT (reset omitted fields), false for PATCH.
	 */
	protected function apply_update($id, $replace) {
		$this->require_write();
		$this->require_numeric_id($id);
		$this->CI->load->model('logbook_model');

		// 404 unless the QSO belongs to the token owner.
		if (!$this->CI->logbook_model->check_qso_is_accessible($id, $this->user_id())) {
			throw new Api_v2_exception('not_found', 'QSO not found', 404);
		}

		$body = $this->body();
		$this->require_scalar_fields($body);

		// PUT must carry the same required fields as create.
		if ($replace) {
			$required = ['call', 'band', 'mode', 'qso_date', 'time_on'];
			$missing = [];
			foreach ($required as $field) {
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

		$data = $this->build_update_data($body, $replace);

		// Optional move to another station location, ownership-checked.
		if (array_key_exists('station_profile_id', $body)) {
			$this->CI->load->model('stations');
			$station_profile_id = $body['station_profile_id'];
			if (!$this->CI->stations->check_station_against_user($station_profile_id, $this->user_id())) {
				throw new Api_v2_exception('forbidden', 'station_profile_id does not belong to the API token owner', 403);
			}
			$profile = $this->CI->stations->profile_clean($station_profile_id);
			$data['station_id'] = $station_profile_id;
			$data['COL_STATION_CALLSIGN'] = trim($profile->station_callsign);
		}

		if (empty($data)) {
			throw new Api_v2_exception('validation_error', 'No editable fields in request body', 400);
		}

		$this->CI->logbook_model->update_qso_columns($id, $data);

		// Respond with the fresh state of the QSO.
		$query = $this->CI->logbook_model->get_qso($id, true);
		$this->CI->api_v2_response->respond($this->format_qso($query->row()));
	}

	/**
	 * Build the COL_* update array from the JSON body.
	 *
	 * PATCH ($replace = false): only fields present in the body.
	 * PUT   ($replace = true):  every editable field; omitted ones get their
	 * reset default so the QSO matches the supplied representation.
	 */
	protected function build_update_data($body, $replace) {
		$data = [];

		// Date/time: qso_date and time_on always travel together.
		$has_date = array_key_exists('qso_date', $body);
		$has_time = array_key_exists('time_on', $body);
		if ($has_date !== $has_time) {
			throw new Api_v2_exception('validation_error', 'qso_date and time_on must be supplied together', 400);
		}
		if ($has_date) {
			$time_on = $this->parse_datetime($body['qso_date'], $body['time_on']);
			$data['COL_TIME_ON'] = $time_on;

			// time_off defaults to time_on and is clamped to >= time_on,
			// same as the web UI edit path.
			$time_off = isset($body['time_off']) && $body['time_off'] !== ''
				? $this->parse_datetime($body['qso_date'], $body['time_off'])
				: $time_on;
			if (strtotime($time_off) < strtotime($time_on)) {
				$time_off = $time_on;
			}
			$data['COL_TIME_OFF'] = $time_off;
		}

		// Mode: split into main mode / submode like the web UI does.
		if (array_key_exists('mode', $body)) {
			$main_mode = $this->CI->logbook_model->get_main_mode_if_submode($body['mode']);
			if ($main_mode == null) {
				$data['COL_MODE'] = $body['mode'];
				$data['COL_SUBMODE'] = null;
			} else {
				$data['COL_MODE'] = $main_mode;
				$data['COL_SUBMODE'] = $body['mode'];
			}
		}

		// Frequencies are normalised to Hz; PUT resets omitted ones.
		foreach (['freq' => 'COL_FREQ', 'freq_rx' => 'COL_FREQ_RX'] as $key => $col) {
			if (array_key_exists($key, $body)) {
				$data[$col] = $this->CI->logbook_model->parse_frequency($body[$key]);
			} elseif ($replace) {
				$data[$col] = null;
			}
		}

		// Simple whitelisted fields: present in body, or reset default on PUT.
		foreach ($this->editable_fields() as $key => $def) {
			list($col, $default, $upper) = $def;
			if (array_key_exists($key, $body)) {
				$value = $body[$key];
				if ($upper && is_string($value)) {
					$value = strtoupper(trim($value));
				}
				$data[$col] = $value;
			} elseif ($replace && $key !== 'call' && $key !== 'band') {
				// call/band are required on PUT and therefore always present.
				$data[$col] = $default;
			}
		}

		return $data;
	}

	/**
	 * Editable simple fields: json key => [column, reset default, uppercase].
	 * Date/time, mode and frequencies are handled separately above.
	 */
	protected function editable_fields() {
		return [
			'call'       => ['COL_CALL', null, true],
			'band'       => ['COL_BAND', null, false],
			'band_rx'    => ['COL_BAND_RX', null, false],
			'rst_sent'   => ['COL_RST_SENT', null, false],
			'rst_rcvd'   => ['COL_RST_RCVD', null, false],
			'gridsquare' => ['COL_GRIDSQUARE', '', true],
			'name'       => ['COL_NAME', '', false],
			'comment'    => ['COL_COMMENT', '', false],
			'notes'      => ['COL_NOTES', '', false],
			'qth'        => ['COL_QTH', '', false],
			'tx_pwr'     => ['COL_TX_PWR', null, false],
			'prop_mode'  => ['COL_PROP_MODE', '', false],
			'sat_name'   => ['COL_SAT_NAME', '', true],
			'sat_mode'   => ['COL_SAT_MODE', '', true],
			'sota_ref'   => ['COL_SOTA_REF', '', true],
			'pota_ref'   => ['COL_POTA_REF', '', true],
			'wwff_ref'   => ['COL_WWFF_REF', '', true],
			'iota'       => ['COL_IOTA', '', true],
			'sig'        => ['COL_SIG', '', true],
			'sig_info'   => ['COL_SIG_INFO', '', true],
			'darc_dok'   => ['COL_DARC_DOK', '', true],
			'state'      => ['COL_STATE', '', false],
			'cnty'       => ['COL_CNTY', '', false],
			'cqz'        => ['COL_CQZ', null, false],
			'ituz'       => ['COL_ITUZ', null, false],
			'qsl_via'    => ['COL_QSL_VIA', '', false],
			'srx'        => ['COL_SRX', null, false],
			'stx'        => ['COL_STX', null, false],
			'srx_string' => ['COL_SRX_STRING', '', true],
			'stx_string' => ['COL_STX_STRING', '', true],
		];
	}

	/**
	 * Combine an ADIF-style date (YYYY-MM-DD) and time (HHMM[SS] or HH:MM[:SS])
	 * into a datetime string, or throw a 400 on garbage input.
	 */
	protected function parse_datetime($date, $time) {
		// Normalise compact ADIF times (1200 / 120030) to colon notation.
		if (preg_match('/^\d{4}$/', $time)) {
			$time = substr($time, 0, 2) . ':' . substr($time, 2, 2);
		} elseif (preg_match('/^\d{6}$/', $time)) {
			$time = substr($time, 0, 2) . ':' . substr($time, 2, 2) . ':' . substr($time, 4, 2);
		}

		$timestamp = strtotime($date . ' ' . $time);
		if ($timestamp === false) {
			throw new Api_v2_exception('validation_error', 'Invalid qso_date/time value', 400);
		}
		return date('Y-m-d H:i:s', $timestamp);
	}

	/**
	 * All station ids that belong to the API key owner.
	 *
	 * @return int[]
	 */
	protected function owner_station_ids() {
		$this->CI->load->model('stations');
		$query = $this->CI->stations->all_of_user($this->user_id());
		$ids = [];
		if ($query !== null) {
			foreach ($query->result() as $row) {
				$ids[] = (int) $row->station_id;
			}
		}
		return $ids;
	}

	/**
	 * Validate a path id is a positive integer, else 404.
	 */
	protected function require_numeric_id($id) {
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Api_v2_exception('not_found', 'QSO not found', 404);
		}
	}

	/**
	 * Map incoming JSON fields to ADIF-style record keys understood by
	 * Logbook_model::import(). Unknown keys are passed through unchanged, so
	 * any valid ADIF field name (lowercase) works out of the box. Dates and
	 * times are normalised to the compact ADIF notation the import pipeline
	 * expects (20260610 / 1200), so ISO input (2026-06-10 / 12:00) works too.
	 */
	protected function body_to_record($body) {
		$record = [];
		foreach ($body as $key => $value) {
			if ($key === 'station_profile_id') {
				continue;
			}
			$key = strtolower($key);
			if ($key === 'freq' || $key === 'freq_rx') {
				// The API freq contract is Hz (matching GET and PATCH/PUT, which
				// use parse_frequency). The ADIF import pipeline expects MHz, so
				// normalise to Hz here and hand it the MHz equivalent, keeping
				// create round-trip-consistent with read and update.
				$hz = $this->CI->logbook_model->parse_frequency(is_string($value) ? $value : (string) $value);
				$record[$key] = $hz > 0 ? $this->hz_to_mhz($hz) : $value;
				continue;
			}
			if (is_string($value)) {
				if ($key === 'qso_date' || $key === 'qso_date_off') {
					$value = str_replace('-', '', $value);
				} elseif ($key === 'time_on' || $key === 'time_off') {
					$value = str_replace(':', '', $value);
				}
			}
			$record[$key] = $value;
		}
		return $record;
	}

	/**
	 * Convert a frequency in Hz to the MHz string the ADIF import expects, with
	 * up to 1 Hz resolution and no trailing zeros (e.g. 14200000 -> "14.2").
	 */
	protected function hz_to_mhz($hz) {
		return rtrim(rtrim(number_format($hz / 1000000, 6, '.', ''), '0'), '.');
	}

	/**
	 * Shape a QSO DB row into the public API representation. Kept deliberately
	 * small for the reference implementation; extend as needed.
	 */
	protected function format_qso($row) {
		return [
			'id'         => (int) $row->COL_PRIMARY_KEY,
			'station_id' => isset($row->station_id) ? (int) $row->station_id : null,
			'call'       => $row->COL_CALL ?? null,
			'band'       => $row->COL_BAND ?? null,
			'mode'       => $row->COL_MODE ?? null,
			'submode'    => $row->COL_SUBMODE ?? null,
			'freq'       => $row->COL_FREQ ?? null,
			'freq_rx'    => $row->COL_FREQ_RX ?? null,
			'qso_date'   => $row->COL_TIME_ON ?? null,
			'rst_sent'   => $row->COL_RST_SENT ?? null,
			'rst_rcvd'   => $row->COL_RST_RCVD ?? null,
			'gridsquare' => $row->COL_GRIDSQUARE ?? null,
			'name'       => $row->COL_NAME ?? null,
			'comment'    => $row->COL_COMMENT ?? null,
			'notes'      => $row->COL_NOTES ?? null,
			'qth'        => $row->COL_QTH ?? null,
			'prop_mode'  => $row->COL_PROP_MODE ?? null,
			'sat_name'   => $row->COL_SAT_NAME ?? null,
		];
	}

	/**
	 * Build the pagination meta block for list responses.
	 */
	protected function list_meta($page, $count) {
		return [
			'page'     => $page['page'],
			'per_page' => $page['per_page'],
			'count'    => $count,
		];
	}
}
