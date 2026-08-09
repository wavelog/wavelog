<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - QSL confirmations resource (read-only)
 *
 * Lists the QSL confirmations the token owner has received, one row per
 * (QSO, confirmation-type) pair. The API counterpart to the web UI's
 * Confirmations page (controller Generic_qsl::searchConfirmations): a QSO
 * confirmed via both LoTW and eQSL appears as two rows here, exactly as it
 * does in the table on that page.
 *
 * This is distinct from the existing statistic profile
 * /api/v2/statistic?profile=confirmations, which returns aggregate counts for
 * monitoring (number of QSOs confirmed by each type). This endpoint returns
 * the per-QSO records themselves - callsign, dates, mode/band, type - so a
 * client can render the same view a human sees in the browser.
 *
 * The QSO resource's ?qsl_filter= parameter is also different: it filters the
 * QSO list by "has at least one of these confirmations" but does not surface
 * which type matched, nor when the confirmation arrived. This endpoint does.
 *
 * Route:  /api/v2/confirmation
 * Scope:  confirmation:read
 *
 * Filters (all optional):
 *   ?type=       comma list of lotw|eqsl|qsl|qrz|clublog (default: all)
 *   ?station_id= comma-separated station ids; ownership-checked (default: all
 *                owned by the token user)
 *   ?since=      YYYY-MM-DD floor on the date the *confirmation* was received
 *   ?qso_since=  YYYY-MM-DD floor on the QSO date
 *   ?qso_until=  YYYY-MM-DD ceiling on the QSO date
 *   ?band=       COL_BAND value, or SAT for satellite QSOs
 *   ?mode=       matched against the mode or the submode
 *   ?callsign=   exact match on the worked callsign
 *   Pagination:  ?page= / ?per_page= (default 100, max 1000).
 */
class Confirmation_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'confirmation';

	/** Default and hard-max page sizes for the list. */
	protected const DEFAULT_PER_PAGE = 100;
	protected const MAX_PER_PAGE     = 1000;

	/**
	 * Translated label for the registry entry of this resource's read scope.
	 */
	protected static function scope_labels() {
		return ['read' => __('Read QSL confirmations')];
	}

	/**
	 * GET /api/v2/confirmation
	 * Filtered list of confirmations, newest first. Clubstation members below
	 * officer level only ever see their own QSOs' confirmations - the operator
	 * restriction mirrors the QSO resource, since a confirmation is reached
	 * through the QSO it belongs to.
	 */
	public function index() {
		$this->CI->load->model('logbook_model');

		$types      = $this->parse_type_list('type', self::CONFIRMATION_TYPES) ?: self::CONFIRMATION_TYPES;
		$since      = $this->parse_date('since');
		$qso_since  = $this->parse_date('qso_since');
		$qso_until  = $this->parse_date('qso_until');
		$band       = $this->normalize_band($this->param('band'));
		$mode       = $this->normalize_mode($this->param('mode'));
		$callsign   = $this->normalize_callsign($this->param('callsign'));
		$page       = $this->pagination(self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);
		$station_ids = $this->resolve_station_ids();

		// Same club-member rule as Qso_resource::index(): restricted members
		// only ever see their own operator's QSOs.
		$operator = $this->is_restricted_club_member() ? (string) $this->operator_callsign() : '';

		$filters = [
			'station_ids' => $station_ids,
			'types'       => $types,
			'since'       => $since,
			'qso_since'   => $qso_since,
			'qso_until'   => $qso_until,
			'band'        => $band,
			'mode'        => $mode,
			'callsign'    => $callsign,
			'operator'    => $operator,
		];

		$total = $this->CI->logbook_model->count_confirmations_list($filters);
		$query = $this->CI->logbook_model->get_confirmations_list(
			$filters, $page['per_page'], $page['offset']
		);

		$rows = is_object($query) ? $query->result() : [];
		$confirmations = array_map([$this, 'format_confirmation'], $rows);

		$meta = $this->list_meta($page, count($confirmations), $total);
		$meta['filters'] = [
			'type'      => $types,
			'since'     => $since ?: null,
			'qso_since' => $qso_since ?: null,
			'qso_until' => $qso_until ?: null,
			'band'      => $band ?: null,
			'mode'      => $mode ?: null,
			'callsign'  => $callsign ?: null,
		];

		$this->CI->api_v2_response->respond($confirmations, 200, $meta);
	}

	/**
	 * Cast a DB row to its public shape. The model already aliases columns to
	 * these names. Core fields are always present; the optional ones are
	 * omitted entirely when empty so the payload carries only what the QSO
	 * actually has (a HF contact has no sat_*, a gridless one no gridsquare).
	 */
	protected function format_confirmation($row) {
		$out = [
			'qso_id'            => (int) $row->qso_id,
			'callsign'          => $row->callsign,
			'qso_date'          => $row->qso_date,
			'mode'              => $row->mode,
			'band'              => $row->band,
			'confirmation_date' => $row->confirmation_date,
			'type'              => $row->type,
		];

		foreach (['submode', 'gridsquare', 'vucc_grids', 'sat_name', 'sat_mode'] as $opt) {
			$value = $row->$opt ?? '';
			if ($value !== '' && $value !== null) {
				$out[$opt] = $value;
			}
		}

		return $out;
	}
}
