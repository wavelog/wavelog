<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';
// DXCC lookups live in the standalone Wavelog\Dxcc namespace (same source the v1
// API uses); pull it in and reference it fully-qualified below.
require_once APPPATH . '../src/Dxcc/Dxcc.php';

/**
 * API v2 - Lookup resource (read-only)
 *
 * A "look something up" endpoint for a single callsign or a single gridsquare,
 * scoped to the token owner's own logbook.
 *
 * Callsign lookup — resolves a callsign to DXCC data plus the owner's
 * worked/confirmed status, LoTW membership and optional callbook data (v2
 * equivalent of the v1 lookup / private_lookup endpoints; used by FlexRadio
 * overlay, DXClusterAPI, ...). The ?detail= parameter selects the depth:
 *   - basic: DXCC + Lat/Long/continent plus the owner's grid/name if the call
 *            was worked before. No per-band/mode history. (v1 `lookup`.)
 *   - full:  additionally per-band/mode worked & confirmed flags, DXCC
 *            confirmation state and (opt-in) callbook data. (v1 `private_lookup`,
 *            default.)
 *
 * Grid lookup (?grid=) — whether a gridsquare is worked/confirmed in the owner's
 * logbook (v2 equivalent of v1 logbook_check_grid).
 *
 * Routes:
 *   GET /api/v2/lookup/{callsign}   path form (callsigns without a "/")
 *   GET /api/v2/lookup?callsign=..  query form (required for calls with a "/")
 *   GET /api/v2/lookup?grid=..      grid worked/confirmed check
 *
 * Scope:  lookup:read
 */
class Lookup_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'lookup';

	/** Registry label for this resource's scope (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read' => __('Look up callsigns and grids'),
		];
	}

	/**
	 * GET /api/v2/lookup?callsign=...  or  ?grid=...
	 * Query form of the lookup. `?callsign=` is required for callsigns containing
	 * a "/" (portable or DXCC-prefix calls like DL1ABC/P or W/DL1ABC), which
	 * cannot be a path segment because the webserver rejects encoded slashes.
	 * `?grid=` looks up a gridsquare's worked/confirmed status instead.
	 */
	public function index() {
		$grid = $this->param('grid');
		if ($grid !== null && $grid !== '') {
			$this->do_grid_lookup($grid);
			return;
		}

		$callsign = $this->param('callsign');
		if ($callsign === null || $callsign === '') {
			throw new Api_v2_exception(
				'validation_error',
				'Provide a callsign (?callsign=...) or a grid (?grid=...)',
				400
			);
		}
		$this->do_lookup($callsign);
	}

	/**
	 * GET /api/v2/lookup/{callsign}
	 * Path form. For callsigns with a "/" use the ?callsign= query form instead.
	 */
	public function show($callsign) {
		$this->do_lookup($callsign);
	}

	/**
	 * Shared lookup for both the path and query forms.
	 * Optional query: ?detail=full|basic (default full), ?band=, ?mode=,
	 * ?callbook=true, ?station_ids=1,2 (default: all of the owner's stations).
	 */
	protected function do_lookup($callsign) {
		$callsign = strtoupper(trim((string) $callsign));
		if ($callsign === '') {
			throw new Api_v2_exception('validation_error', 'Missing callsign', 400);
		}

		$detail = strtolower(trim((string) $this->param('detail', 'basic')));
		if (!in_array($detail, ['full', 'basic'], true)) {
			throw new Api_v2_exception(
				'validation_error',
				'Unknown detail "' . $detail . '". Allowed: full, basic',
				400,
				['allowed' => ['full', 'basic']]
			);
		}

		$this->CI->load->model('logbook_model');

		$station_ids = $this->resolve_station_ids();

		$data = ($detail === 'basic')
			? $this->lookup_basic($callsign, $station_ids)
			: $this->lookup_full($callsign, $station_ids);

		$this->CI->api_v2_response->respond($data, 200, ['detail' => $detail]);
	}

	/**
	 * Grid lookup (?grid=): whether the given gridsquare is worked/confirmed in
	 * the owner's logbook. Optional ?band=, ?cnfm= (qsl|lotw|eqsl) and
	 * ?logbook_id= filters. Result is one of "Not Found", "Found" (no cnfm
	 * requested), "Worked", "Confirmed". Replaces the v1 logbook_check_grid.
	 */
	protected function do_grid_lookup($grid) {
		$grid = strtoupper(trim((string) $grid));
		if ($grid === '') {
			throw new Api_v2_exception('validation_error', 'Missing gridsquare', 400);
		}

		$band = $this->param('band');
		$cnfm = $this->param('cnfm');

		$result = 'Not Found';
		$locations = $this->grid_station_location_ids();
		if (!empty($locations)) {
			$this->CI->load->model('logbook_model');
			$query = $this->CI->logbook_model->check_if_grid_worked_in_logbook($grid, $locations, $band, $cnfm);
			if ($query->num_rows() === 0) {
				$result = 'Not Found';
			} elseif ($cnfm === null) {
				$result = 'Found';
			} else {
				$values = [];
				foreach ($query->result() as $row) {
					$values[] = $row->gridorcnfm;
				}
				$result = in_array('Y', $values, true) ? 'Confirmed' : 'Worked';
			}
		}

		$this->CI->api_v2_response->respond(['gridsquare' => $grid, 'result' => $result], 200, ['type' => 'grid']);
	}

	/**
	 * Station-location ids (as an array, the form check_if_grid_worked_in_logbook
	 * expects) the grid lookup runs against. Defaults to all of the owner's
	 * station locations; ?logbook_id= narrows it to a single owned logbook (403
	 * for a foreign id). Returns [] when the logbook has no linked locations.
	 *
	 * @return int[]
	 */
	protected function grid_station_location_ids() {
		$logbook_id = $this->param('logbook_id');
		if ($logbook_id !== null && $logbook_id !== '') {
			if (!is_numeric($logbook_id)) {
				throw new Api_v2_exception('validation_error', 'logbook_id must be numeric', 400);
			}
			$this->CI->load->model('logbooks_model');
			if (!$this->CI->logbooks_model->logbook_id_belongs_to_user((int) $logbook_id, $this->user_id())) {
				throw new Api_v2_exception('forbidden', 'logbook_id does not belong to this token', 403);
			}
			$locations = $this->CI->logbooks_model->list_logbook_relationships((int) $logbook_id);
			return ($locations === [-1]) ? [] : $locations;
		}

		$this->CI->load->model('stations');
		$ids = [];
		$query = $this->CI->stations->all_of_user($this->user_id());
		if ($query !== null) {
			foreach ($query->result() as $row) {
				$ids[] = (int) $row->station_id;
			}
		}
		return $ids;
	}

	// --- Lookup variants ---------------------------------------------------

	/**
	 * Reduced lookup: DXCC derivation plus the owner's grid/name when the call
	 * was worked before. No QSO history. Mirrors the v1 `lookup` endpoint used
	 * by the DXClusterAPI.
	 */
	protected function lookup_basic($callsign, $station_ids) {
		$date = date('Y-m-d');

		$return = [
			'callsign'     => $callsign,
			'dxcc'         => false,
			'dxcc_id'      => -1,
			'dxcc_lat'     => '',
			'dxcc_long'    => '',
			'dxcc_cqz'     => '',
			'dxcc_flag'    => '',
			'cont'         => '',
			'name'         => '',
			'gridsquare'   => '',
			'location'     => '',
			'iota_ref'     => '',
			'state'        => '',
			'us_county'    => '',
			'qsl_manager'  => '',
			'bearing'      => '',
			'workedBefore' => false,
			'lotw_member'  => false,
			'suffix_slash' => '',
		];

		$dxccobj = new \Wavelog\Dxcc\Dxcc();
		$dxcc = $dxccobj->dxcc_lookup($callsign, $date);
		$return['dxcc_id']   = $dxcc['adif']   ?? '';
		$return['dxcc']      = $dxcc['entity'] ?? '';
		$return['dxcc_lat']  = $dxcc['lat']    ?? '';
		$return['dxcc_long'] = $dxcc['long']   ?? '';
		$return['dxcc_cqz']  = $dxcc['cqz']    ?? '';
		$return['cont']      = $dxcc['cont']   ?? '';

		// Owner's own record for this call (no band/mode scoping).
		$hit = $this->CI->logbook_model->call_lookup_result($callsign, $station_ids, '', 'NO BAND', 'NO MODE');
		if ($hit != null) {
			$return['name']         = $hit->COL_NAME;
			$return['gridsquare']   = $hit->COL_GRIDSQUARE;
			$return['location']     = $hit->COL_QTH;
			$return['iota_ref']     = $hit->COL_IOTA;
			$return['qsl_manager']  = $hit->COL_QSL_VIA;
			$return['state']        = $hit->COL_STATE;
			$return['us_county']    = $hit->COL_CNTY;
			$return['workedBefore'] = true;
			if ($return['gridsquare'] != '') {
				$return['latlng'] = $this->grid_to_latlng($return['gridsquare']);
			}
		}

		if (($return['dxcc'] ?? '') != '') {
			$this->CI->load->library('DxccFlag');
			$return['dxcc_flag'] = $this->CI->dxccflag->get($return['dxcc_id']);
		}

		$lotw_days = $this->CI->logbook_model->check_last_lotw($callsign);
		if ($lotw_days !== null) {
			$return['lotw_member'] = $lotw_days;
		}

		return $return;
	}

	/**
	 * Full lookup: everything from basic plus per-band/mode worked & confirmed
	 * flags, DXCC confirmation state and optional callbook data. Mirrors the v1
	 * `private_lookup` endpoint.
	 */
	protected function lookup_full($callsign, $station_ids) {
		$date = date('Y-m-d');
		$band = (string) $this->param('band', 'NO_BAND');
		$mode = (string) $this->param('mode', 'NO_MODE');

		$user = $this->CI->user_model->get_by_id($this->user_id())->row();
		$default_confirmation = $user->user_default_confirmation ?? '';

		$return = [
			'callsign'                    => $callsign,
			'dxcc'                        => false,
			'dxcc_id'                     => -1,
			'dxcc_lat'                    => '',
			'dxcc_long'                   => '',
			'dxcc_cqz'                    => '',
			'dxcc_flag'                   => '',
			'cont'                        => '',
			'name'                        => '',
			'gridsquare'                  => '',
			'location'                    => '',
			'iota_ref'                    => '',
			'state'                       => '',
			'us_county'                   => '',
			'qsl_manager'                 => '',
			'bearing'                     => '',
			'call_worked'                 => false,
			'call_worked_band'            => false,
			'call_worked_band_mode'       => false,
			'lotw_member'                 => false,
			'dxcc_confirmed_on_band'      => false,
			'dxcc_confirmed_on_band_mode' => false,
			'dxcc_confirmed'              => false,
			'call_confirmed'              => false,
			'call_confirmed_band'         => false,
			'call_confirmed_band_mode'    => false,
			'suffix_slash'                => '',
		];

		$dxccobj = new \Wavelog\Dxcc\Dxcc();
		$dxcc = $dxccobj->dxcc_lookup($callsign, $date);

		// A trailing "/XXX" may itself be a DXCC prefix (e.g. W/DL1ABC): resolve
		// it, otherwise map the common portable/mobile suffixes to a label.
		$last_slash_pos = strrpos($callsign, '/');
		$suffix_dxcc = null;
		if ($last_slash_pos !== false && $last_slash_pos > 4) {
			$suffix = substr($callsign, $last_slash_pos + 1);
			switch ($suffix) {
				case 'P':
					$return['suffix_slash'] = 'Portable';
					break;
				case 'M':
					$return['suffix_slash'] = 'Mobile';
					break;
				case 'MM':
					$return['suffix_slash'] = 'Maritime Mobile';
					break;
				default:
					// Not a known suffix: likely a DXCC prefix override.
					$suffix_dxcc = $dxccobj->dxcc_lookup($suffix, $date);
					$return['suffix_slash'] = null;
			}
		}

		if (isset($suffix_dxcc['call'])) {
			$return['dxcc_id']   = $suffix_dxcc['adif'];
			$return['dxcc']      = $suffix_dxcc['entity'];
			$return['dxcc_lat']  = $suffix_dxcc['lat'];
			$return['dxcc_long'] = $suffix_dxcc['long'];
			$return['dxcc_cqz']  = $suffix_dxcc['cqz'];
			$return['cont']      = $suffix_dxcc['cont'];
		} else {
			$return['dxcc_id']   = $dxcc['adif']   ?? '';
			$return['dxcc']      = $dxcc['entity'] ?? '';
			$return['dxcc_lat']  = $dxcc['lat']    ?? '';
			$return['dxcc_long'] = $dxcc['long']   ?? '';
			$return['dxcc_cqz']  = $dxcc['cqz']    ?? '';
			$return['cont']      = $dxcc['cont']   ?? '';
		}

		// ITU zone from the DXCC entity; only added when the entity has one.
		$entity = $this->CI->logbook_model->get_entity($return['dxcc_id']);
		if (is_array($entity) && (($entity['ituz'] ?? 0) > 0)) {
			$return['dxcc_ituz'] = (int) $entity['ituz'];
		}

		// Owner's own worked/confirmed status for this call.
		$hit = $this->CI->logbook_model->call_lookup_result($callsign, $station_ids, $default_confirmation, $band, $mode);
		if ($hit != null) {
			$return['name']                  = $hit->COL_NAME;
			$return['gridsquare']            = $hit->COL_GRIDSQUARE;
			$return['location']              = $hit->COL_QTH;
			$return['iota_ref']              = $hit->COL_IOTA;
			$return['qsl_manager']           = $hit->COL_QSL_VIA;
			$return['state']                 = $hit->COL_STATE;
			$return['us_county']             = $hit->COL_CNTY;
			$return['dxcc_id']               = $hit->COL_DXCC;
			$return['cont']                  = $hit->COL_CONT;
			$return['call_worked']           = true;
			$return['call_worked_band']      = ($hit->CALL_WORKED_BAND == 1);
			$return['call_worked_band_mode'] = ($hit->CALL_WORKED_BAND_MODE == 1);
			$return['call_confirmed']        = ($hit->CALL_CNF == 1);
			$return['call_confirmed_band']   = ($hit->CALL_CNF_BAND == 1);
			$return['call_confirmed_band_mode'] = ($hit->CALL_CNF_BAND_MODE == 1);
			if ($return['gridsquare'] != '') {
				$return['latlng'] = $this->grid_to_latlng($return['gridsquare']);
			}
		}

		if (($return['dxcc'] ?? '') != '') {
			$this->CI->load->library('DxccFlag');
			$return['dxcc_flag'] = $this->CI->dxccflag->get($return['dxcc_id']);
		}

		$lotw_days = $this->CI->logbook_model->check_last_lotw($callsign);
		if ($lotw_days !== null) {
			$return['lotw_member'] = $lotw_days;
		}

		if (($return['dxcc_id'] ?? '') != '') {
			$return['dxcc_confirmed']              = ($this->CI->logbook_model->check_if_dxcc_cnfmd_in_logbook_api($default_confirmation, $return['dxcc_id'], $station_ids, null, null) > 0);
			$return['dxcc_confirmed_on_band']      = ($this->CI->logbook_model->check_if_dxcc_cnfmd_in_logbook_api($default_confirmation, $return['dxcc_id'], $station_ids, $band, null) > 0);
			$return['dxcc_confirmed_on_band_mode'] = ($this->CI->logbook_model->check_if_dxcc_cnfmd_in_logbook_api($default_confirmation, $return['dxcc_id'], $station_ids, $band, $mode) > 0);
		}

		// Callbook data is opt-in (external HTTP lookup).
		if ($this->param('callbook') === 'true') {
			$this->CI->load->library('callbook');
			$callbook = $this->CI->logbook_model->loadCallBook($callsign, $this->CI->config->item('use_fullname'));
			if ($callbook) {
				$return['callbook'] = $callbook;
			}
		}

		return $return;
	}

	// --- Helpers -----------------------------------------------------------

	/**
	 * The station-location id set (comma-joined string, as call_lookup_result()
	 * expects) the lookup runs against. Defaults to all of the token owner's
	 * stations; ?station_ids=1,2 narrows it to the given, ownership-checked ids.
	 */
	protected function resolve_station_ids() {
		$this->CI->load->model('stations');
		$user_id = $this->user_id();

		$requested = $this->param('station_ids');
		if ($requested === null || $requested === '') {
			return $this->CI->stations->all_station_ids_of_user($user_id);
		}

		$granted = [];
		foreach (explode(',', $requested) as $sid) {
			$sid = (int) trim($sid);
			if ($sid > 0 && $this->CI->stations->check_station_against_user($sid, $user_id)) {
				$granted[] = $sid;
			}
		}

		// Fall back to all of the owner's stations if none of the requested ids
		// were granted, so the lookup still returns DXCC data.
		return empty($granted)
			? $this->CI->stations->all_station_ids_of_user($user_id)
			: implode(', ', $granted);
	}

	/**
	 * Convert a Maidenhead locator to [lat, long] via the Qra library.
	 */
	protected function grid_to_latlng($grid) {
		if (!$this->CI->load->is_loaded('Qra')) {
			$this->CI->load->library('Qra');
		}
		return $this->CI->qra->qra2latlong($grid);
	}
}
