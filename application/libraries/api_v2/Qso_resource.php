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
 *   - updates via Logbook_model::update_qso_columns() (PATCH)
 *   - deletes via Logbook_model::delete() (handles OQRS/QSL/eQSL + cache)
 *
 * PATCH updates only the fields present in the body; anything omitted keeps its
 * stored value. There is deliberately no PUT: Wavelog is the source of truth, so
 * an API client should never be able to blank fields it simply did not know
 * about - a client wanting a full overwrite can send every field explicitly.
 * PATCH touches only the field whitelist in editable_fields(): QSL/LoTW/eQSL
 * bookkeeping, DXCC/country recalculation and the MY_* station refs are
 * deliberately out of scope here.
 *
 * Unit note: POST follows ADIF field semantics (freq in MHz), while PATCH
 * takes freq/freq_rx in Hz - matching the representation returned by GET.
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
	 * Filtered list of the token owner's QSOs. The same filtered result set is
	 * rendered as JSON (default) or as ADIF via ?format=adif — the data is fetched
	 * once and only the rendering differs.
	 *
	 * Filters (all optional):
	 *   ?station_id= comma-separated, ownership-checked; default: all owned
	 *   ?band=       single band filter (e.g. 20m or SAT)
	 *   ?mode=       single mode/submode filter (e.g. SSB or FT8)
	 *   ?qsl_filter= comma list of lotw|qsl|eqsl|clublog (OR-combined)
	 *   ?since_id=   only QSOs with a primary key greater than this (default 0)
	 * Pagination: ?page= / ?per_page= (max 5000; default 50 for JSON, 1000 for
	 *             ADIF), or ?limit= as a shortcut for the newest N QSOs (overrides
	 *             page/per_page).
	 * Rendering:  ?format=json (default) | adif.
	 */
	public function index() {
		$format = strtolower(trim((string) $this->param('format', 'json')));
		if ($format !== 'json' && $format !== 'adif') {
			throw new Api_v2_exception(
				'validation_error',
				'Unknown format "' . $format . '". Allowed: json, adif',
				400,
				['allowed' => ['json', 'adif']]
			);
		}

		$this->CI->load->model('logbook_model');

		// One upper bound for both formats: a JSON row (format_qso) is a trimmed
		// subset of fields and thus no heavier than an ADIF line, so there is no
		// reason to cap JSON lower. Only the default page size differs — small for
		// JSON browsing, larger for ADIF bulk sync.
		$is_adif = ($format === 'adif');
		$max_per_page = 5000;
		$page        = $this->pagination($is_adif ? 1000 : 50, $max_per_page);
		$station_ids = $this->resolve_station_ids();
		$band        = $this->normalize_band($this->param('band'));
		$mode        = $this->normalize_mode($this->param('mode'));
		$qsl_filter  = $this->parse_qsl_filter();
		$since_id    = $this->parse_since_id();

		// ADIF sync needs ascending-by-id order (so lastfetchedid advances); the
		// JSON browse list is newest-first.
		$order = $is_adif ? 'id_asc' : 'time_desc';

		// `limit` is a shortcut for "the newest N QSOs" (e.g. limit=1 => the last
		// QSO). It overrides page/per_page: page 1, newest-first, capped like
		// per_page. Handy for a quick "what did I work last?" query.
		$limit = $this->param('limit');
		if ($limit !== null && $limit !== '') {
			if (!is_numeric($limit) || (int) $limit < 1) {
				throw new Api_v2_exception('validation_error', 'limit must be a positive integer', 400);
			}
			$page['page']     = 1;
			$page['offset']   = 0;
			$page['per_page'] = min((int) $limit, $max_per_page);
			$order = 'time_desc';
		}

		if (empty($station_ids)) {
			$this->respond_qsos($format, [], $page, 0, $since_id);
			return;
		}

		// Total across all pages for the same filter, so a client can find the
		// last page without probing for an empty response.
		$total = $this->CI->logbook_model->count_qsos_filtered($station_ids, $band, $mode, $qsl_filter, $since_id);

		$query = $this->CI->logbook_model->get_qsos_filtered(
			$station_ids, $band, $mode, $qsl_filter, $since_id, $order, $page['per_page'], $page['offset']
		);

		$rows = is_object($query) ? $query->result() : [];
		$this->respond_qsos($format, $rows, $page, $total, $since_id);
	}

	/**
	 * Render the fetched QSO rows in the requested format, with pagination meta.
	 * JSON returns the QSO objects; ADIF returns { exported, lastfetchedid, adif }
	 * built from the same rows via AdifHelper.
	 */
	protected function respond_qsos($format, $rows, $page, $total, $since_id) {
		$meta = $this->list_meta($page, count($rows), $total);

		if ($format === 'adif') {
			$this->CI->load->library('AdifHelper');
			$adif = $this->CI->adifhelper->getAdifHeader(
				$this->CI->config->item('app_name'),
				$this->CI->optionslib->get_option('version'),
				$this->CI->optionslib->get_option('adif_version')
			);
			$lastfetchedid = (int) $since_id;
			foreach ($rows as $row) {
				$adif .= $this->CI->adifhelper->getAdifLine($row);
				$lastfetchedid = max($lastfetchedid, (int) $row->COL_PRIMARY_KEY);
			}
			$this->CI->api_v2_response->respond([
				'exported'      => count($rows),
				'lastfetchedid' => $lastfetchedid,
				'adif'          => count($rows) > 0 ? $adif : null,
			], 200, $meta);
			return;
		}

		$qsos = [];
		foreach ($rows as $row) {
			$qsos[] = $this->format_qso($row);
		}
		$this->CI->api_v2_response->respond($qsos, 200, $meta);
	}

	/**
	 * Parse and validate the ?since_id= floor, or 0 when absent.
	 */
	protected function parse_since_id() {
		$raw = $this->param('since_id', 0);
		if (!is_numeric($raw)) {
			throw new Api_v2_exception('validation_error', 'since_id must be numeric', 400);
		}
		return (int) $raw;
	}

	/**
	 * Parse and validate the ?qsl_filter= query (comma list), or null when absent.
	 * Mirrors the confirmation types the QSO filter can match on (see
	 * Logbook_model::_qso_v2_filter_where()); HRDLog is upload-only and has no
	 * received column, so it is not among them.
	 */
	protected function parse_qsl_filter() {
		return $this->parse_type_list('qsl_filter', ['lotw', 'qsl', 'eqsl', 'qrz', 'clublog']);
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
	 * Create QSO(s). The body field `import_type` selects the payload format:
	 *   - "json" (default): a single QSO from the top-level fields, OR a bulk
	 *     import when a `qsos` array is present (each element a QSO object).
	 *     Required per QSO: call, band, mode, qso_date (YYYY-MM-DD), time_on.
	 *     `station_profile_id` (shared) is required at the top level; bulk accepts
	 *     an optional `dryrun`.
	 *   - "adif": a bulk ADIF import; the ADIF payload travels in the `adif` field,
	 *     with `station_profile_id` and an optional `dryrun` flag.
	 */
	public function create() {
		$this->require_write();
		$this->CI->load->model('logbook_model');
		$this->CI->load->model('stations');

		$body = $this->body();

		// Ownership-checked station profile is required for both import types.
		// The body-wide scalar check runs later (and not at all for the bulk
		// variant, whose "qsos" key is an array), so guard the type here before
		// the value reaches the data layer.
		$station_profile_id = $body['station_profile_id'] ?? null;
		if (!is_numeric($station_profile_id)
			|| !$this->CI->stations->check_station_against_user((int) $station_profile_id, $this->user_id())) {
			throw new Api_v2_exception('forbidden', 'station_profile_id does not belong to the API key owner', 403);
		}

		$import_type = strtolower(trim((string) ($body['import_type'] ?? 'json')));
		switch ($import_type) {
			case 'json':
				$this->create_from_json($body, $station_profile_id);
				return;
			case 'adif':
				$this->create_from_adif($body, $station_profile_id, !empty($body['dryrun']));
				return;
			default:
				throw new Api_v2_exception(
					'validation_error',
					'Unknown import_type "' . $import_type . '". Allowed: json, adif',
					400,
					['allowed' => ['json', 'adif']]
				);
		}
	}

	/**
	 * Create QSO(s) from JSON (import_type=json, the default). A "qsos" array in
	 * the body triggers a bulk import; otherwise a single QSO is created from the
	 * top-level fields.
	 */
	protected function create_from_json($body, $station_profile_id) {
		if (array_key_exists('qsos', $body)) {
			$this->create_bulk_json($body, $station_profile_id);
			return;
		}

		$this->require_scalar_fields($body);

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
	 * Bulk-create QSOs from a JSON array (import_type=json with a "qsos" array).
	 * Each element is a QSO object with the same fields as a single create; they
	 * are all imported into the given, ownership-checked station_profile_id. An
	 * optional top-level "dryrun" validates without importing.
	 *
	 * @param array $body               Decoded request body (must contain "qsos").
	 * @param int   $station_profile_id Ownership-checked target station.
	 */
	protected function create_bulk_json($body, $station_profile_id) {
		$qsos = $body['qsos'];
		// Must be a non-empty JSON array (a list, not an object).
		if (!is_array($qsos) || empty($qsos) || array_keys($qsos) !== range(0, count($qsos) - 1)) {
			throw new Api_v2_exception('validation_error', '"qsos" must be a non-empty array of QSO objects', 400);
		}

		$required = ['call', 'band', 'mode', 'qso_date', 'time_on'];
		$records = [];
		foreach ($qsos as $i => $qso) {
			if (!is_array($qso)) {
				throw new Api_v2_exception('validation_error', 'qsos[' . $i . '] must be an object', 400);
			}
			$this->require_scalar_fields($qso);

			$missing = [];
			foreach ($required as $field) {
				if (empty($qso[$field])) {
					$missing[] = $field;
				}
			}
			if (!empty($missing)) {
				throw new Api_v2_exception(
					'validation_error',
					'qsos[' . $i . '] missing required field(s): ' . implode(', ', $missing),
					400,
					['index' => $i, 'missing' => $missing]
				);
			}

			$records[] = $this->body_to_record($qso);
		}

		if (!empty($body['dryrun'])) {
			$this->CI->api_v2_response->respond(['dryrun' => true, 'parsed' => count($records)], 200);
			return;
		}

		$result = $this->CI->logbook_model->import_bulk(
			$records,
			$station_profile_id,
			true,   // skipDuplicate
			false, false, false, false, false, false, false,
			false,  // skipexport
			false,  // operatorName
			true,   // apicall
			true    // skipStationCheck (ownership already verified)
		);

		$this->respond_bulk_import($result, count($records));
	}

	/**
	 * Emit the response for a bulk import (ADIF or JSON multi-QSO): a summary of
	 * parsed / imported / skipped counts plus any messages. Throws 400 when
	 * nothing was imported and only hard errors occurred.
	 *
	 * @param array $result Return value of Logbook_model::import_bulk().
	 * @param int   $parsed Number of records handed to the import.
	 */
	protected function respond_bulk_import($result, $parsed) {
		$imported = (int) ($result['qsocount'] ?? 0);
		$structured = $result['structured_errors'] ?? ['critical' => [], 'validation' => [], 'duplicate' => []];
		$skipped = count($structured['duplicate'] ?? []);
		$hard_errors = array_merge($structured['critical'] ?? [], $structured['validation'] ?? []);

		// Nothing imported and only hard errors -> the whole batch failed.
		if ($imported === 0 && !empty($hard_errors)) {
			throw new Api_v2_exception(
				'validation_error',
				trim(strip_tags($result['errormessage'] ?? 'Import failed')),
				400,
				$structured
			);
		}

		$this->CI->api_v2_response->respond([
			'parsed'   => $parsed,
			'imported' => $imported,
			'skipped'  => $skipped,
			'messages' => array_values(array_map(function ($m) { return trim(strip_tags($m)); }, $hard_errors)),
		], 201);
	}

	/**
	 * Bulk-import QSOs from an ADIF string (import_type=adif).
	 *
	 * Reuses the same parse/import pipeline as the v1 API: adif_parser feeds
	 * records into Logbook_model::import_bulk(). Clubstation operator resolution
	 * mirrors v1 — when a club member's token is used, the operator is forced to
	 * the token creator rather than the club callsign.
	 *
	 * @param array $body               Decoded request body.
	 * @param int   $station_profile_id Ownership-checked target station.
	 * @param bool  $dryrun             Parse only, import nothing.
	 */
	protected function create_from_adif($body, $station_profile_id, $dryrun) {
		$adif = $body['adif'] ?? '';
		if (!is_string($adif) || trim($adif) === '') {
			throw new Api_v2_exception('validation_error', 'import_type=adif requires a non-empty "adif" string', 400);
		}

		$this->CI->load->model('club_model');
		if (!$this->CI->load->is_loaded('adif_parser')) {
			$this->CI->load->library('adif_parser');
		}
		if (!$this->CI->load->is_loaded('Qra')) {
			$this->CI->load->library('Qra');
		}

		// Clubstation operator resolution: a club member's token must log under
		// the member's own callsign, not the shared club call.
		$user_id = $this->user_id();
		$created_by = $this->auth['created_by'];
		$club_perm = $this->CI->club_model->get_permission_noui($user_id, $created_by);
		$real_operator = null;
		if ($this->CI->config->item('special_callsign') && $user_id != $created_by) {
			$real_operator = $this->CI->user_model->get_by_id($created_by)->row()->user_callsign;
		}

		$profile = $this->CI->stations->profile_clean($station_profile_id);
		$mygrid = $profile->station_gridsquare ?? '';

		// Collapse whitespace right after <eor> so the parser is not tripped up
		// by pretty-printed ADIF, matching the v1 endpoint.
		$adif = preg_replace('#<([eE][oO][rR])>[\r\n\t]+#', '<$1>', $adif);
		$this->CI->adif_parser->feed($adif);

		$records = [];
		$parsed = 0;
		while ($record = $this->CI->adif_parser->get_record()) {
			if (!isset($record['call']) || trim($record['call']) === '') {
				continue;
			}
			if (count($record) === 0) {
				break;
			}

			// Normalise slashed zeros in the callsign fields.
			$record['call'] = str_replace('Ø', '0', $record['call']);
			foreach (['operator', 'station_callsign', 'owner_callsign'] as $f) {
				if (($record[$f] ?? '') !== '') {
					$record[$f] = str_replace('Ø', '0', $record[$f]);
				}
			}

			// Force the operator to the token creator for clubstation tokens.
			if ($real_operator !== null) {
				$recorded_operator = $record['operator'] ?? '';
				if ((array_key_exists('operator', $record) && $record['operator'] == ($record['station_callsign'] ?? '')) || $recorded_operator === '') {
					$record['operator'] = $real_operator;
				}
				if (($club_perm ?? 0) == 3 || ($club_perm ?? 0) == 6) {
					$record['operator'] = $real_operator;
				}
			}

			// Fill the distance from the station's own grid when possible.
			if (array_key_exists('gridsquare', $record) && $mygrid !== ''
				&& ($record['gridsquare'] ?? '') !== '' && !array_key_exists('distance', $record)) {
				$record['distance'] = $this->CI->qra->distance($mygrid, $record['gridsquare'], 'K');
			}

			$records[] = $record;
			$parsed++;
		}

		if ($dryrun) {
			$this->CI->api_v2_response->respond(['dryrun' => true, 'parsed' => $parsed], 200);
			return;
		}

		if (empty($records)) {
			throw new Api_v2_exception('validation_error', 'No valid QSO records found in ADIF', 400);
		}

		$result = $this->CI->logbook_model->import_bulk(
			$records,
			$station_profile_id,
			true,   // skipDuplicate
			false, false, false, false, false, false, false,
			true,   // skipexport
			false,  // operatorName
			true,   // apicall
			true    // skipStationCheck (ownership already verified above)
		);

		$this->respond_bulk_import($result, $parsed);
	}

	/**
	 * PATCH /api/v2/qso/{id}
	 * Partial update: only the fields present in the body are changed.
	 */
	public function update($id) {
		$this->apply_update($id);
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
	 * PATCH implementation.
	 *
	 * @param int $id QSO primary key from the path.
	 */
	protected function apply_update($id) {
		$this->require_write();
		$this->require_numeric_id($id);
		$this->CI->load->model('logbook_model');

		// 404 unless the QSO belongs to the token owner.
		if (!$this->CI->logbook_model->check_qso_is_accessible($id, $this->user_id())) {
			throw new Api_v2_exception('not_found', 'QSO not found', 404);
		}

		$body = $this->body();
		$this->require_scalar_fields($body);

		$data = $this->build_update_data($body);

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
	 * Build the COL_* update array from the JSON body. Only fields present in
	 * the body are touched; anything omitted keeps its stored value.
	 */
	protected function build_update_data($body) {
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

		// Frequencies are normalised to Hz.
		foreach (['freq' => 'COL_FREQ', 'freq_rx' => 'COL_FREQ_RX'] as $key => $col) {
			if (array_key_exists($key, $body)) {
				$data[$col] = $this->CI->logbook_model->parse_frequency($body[$key]);
			}
		}

		if (array_key_exists('call', $body) && !$this->CI->logbook_model->is_valid_callsign($body['call'])) {
			throw new Api_v2_exception('validation_error', 'Invalid callsign', 400, ['field' => 'call']);
		}

		// Simple whitelisted fields, only those present in the body.
		foreach ($this->editable_fields() as $key => $def) {
			list($col, $upper) = $def;
			if (array_key_exists($key, $body)) {
				$value = $body[$key];
				if ($upper && is_string($value)) {
					$value = strtoupper(trim($value));
				}
				$data[$col] = $value;
			}
		}

		return $data;
	}

	/**
	 * Editable simple fields: json key => [column, uppercase].
	 * Date/time, mode and frequencies are handled separately above.
	 */
	protected function editable_fields() {
		return [
			'call'       => ['COL_CALL', true],
			'band'       => ['COL_BAND', false],
			'band_rx'    => ['COL_BAND_RX', false],
			'rst_sent'   => ['COL_RST_SENT', false],
			'rst_rcvd'   => ['COL_RST_RCVD', false],
			'gridsquare' => ['COL_GRIDSQUARE', true],
			'name'       => ['COL_NAME', false],
			'comment'    => ['COL_COMMENT', false],
			'notes'      => ['COL_NOTES', false],
			'qth'        => ['COL_QTH', false],
			'tx_pwr'     => ['COL_TX_PWR', false],
			'prop_mode'  => ['COL_PROP_MODE', false],
			'sat_name'   => ['COL_SAT_NAME', true],
			'sat_mode'   => ['COL_SAT_MODE', true],
			'sota_ref'   => ['COL_SOTA_REF', true],
			'pota_ref'   => ['COL_POTA_REF', true],
			'wwff_ref'   => ['COL_WWFF_REF', true],
			'iota'       => ['COL_IOTA', true],
			'sig'        => ['COL_SIG', true],
			'sig_info'   => ['COL_SIG_INFO', true],
			'darc_dok'   => ['COL_DARC_DOK', true],
			'state'      => ['COL_STATE', false],
			'cnty'       => ['COL_CNTY', false],
			'cqz'        => ['COL_CQZ', false],
			'ituz'       => ['COL_ITUZ', false],
			'qsl_via'    => ['COL_QSL_VIA', false],
			'srx'        => ['COL_SRX', false],
			'stx'        => ['COL_STX', false],
			'srx_string' => ['COL_SRX_STRING', true],
			'stx_string' => ['COL_STX_STRING', true],
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
				// The API freq contract is Hz (matching GET and PATCH, which
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
		// Read-only fields: the identifiers plus the date/mode/frequency group,
		// which POST derives from the ADIF payload and apply_update() handles
		// separately. Everything else comes from editable_fields() below.
		$qso = [
			'id'         => (int) $row->COL_PRIMARY_KEY,
			'station_id' => isset($row->station_id) ? (int) $row->station_id : null,
			'qso_date'   => $row->COL_TIME_ON ?? null,
			'mode'       => $row->COL_MODE ?? null,
			'submode'    => $row->COL_SUBMODE ?? null,
			'freq'       => $row->COL_FREQ ?? null,
			'freq_rx'    => $row->COL_FREQ_RX ?? null,
		];

		// Columns that hold numbers; the driver hands them back as strings.
		$numeric = ['cqz', 'ituz', 'srx', 'stx'];

		// Driven by editable_fields() on purpose: a client must be able to read
		// back every field it may write, or a read-modify-write cycle would
		// silently drop whatever GET never showed it.
		foreach ($this->editable_fields() as $key => $spec) {
			$value = $row->{$spec[0]} ?? null;
			if (in_array($key, $numeric, true)) {
				$value = ($value === null || $value === '') ? null : (int) $value;
			}
			$qso[$key] = $value;
		}

		return $qso;
	}

	/**
	 * Build the pagination meta block for list responses.
	 *
	 * `count` is the number of items on this page; `total` is the number across
	 * all pages. `total_pages` and `has_more` are derived so a client knows
	 * definitively when it has reached the last page (no need to probe for an
	 * empty response).
	 *
	 * @param array $page  { page, per_page, offset }
	 * @param int   $count Items returned on this page.
	 * @param int   $total Items across all pages for the current filter.
	 */
	protected function list_meta($page, $count, $total = 0) {
		$total = (int) $total;
		$per_page = $page['per_page'];
		$total_pages = $per_page > 0 ? (int) ceil($total / $per_page) : 0;

		return [
			'page'        => $page['page'],
			'per_page'    => $per_page,
			'count'       => $count,
			'total'       => $total,
			'total_pages' => $total_pages,
			'has_more'    => $page['page'] < $total_pages,
		];
	}
}
