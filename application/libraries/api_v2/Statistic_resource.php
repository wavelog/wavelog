<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Statistics resource (read-only)
 *
 * Exposes instance-wide statistics (all users). Built for monitoring/alerting
 * (e.g. Zabbix): values are flat and numeric where it matters so they map onto
 * monitoring items and triggers.
 *
 * The ?profile= query parameter selects which topic(s) to return. Every topic
 * is nested under its own key, so a value's JSONPath is identical whether it is
 * requested on its own or as part of full (e.g. $.data.worker.connected_clients):
 *   - qso:      QSO analytics — total, rolling activity windows, band/mode
 *               breakdown, confirmation totals (default).
 *   - confirmations: QSL confirmation counts per type (LoTW/eQSL/paper/QRZ/
 *               Clublog), broken down by band and by mode, filterable by
 *               ?type=, ?since=, ?qso_since=, ?band= and ?mode=.
 *   - system:   version/build info.                     (admin only)
 *   - full:     all permitted topics.
 *
 * The system topic expose instance internals and is restricted
 * to tokens owned by a Wavelog administrator (user_type 99). For a non-admin
 * token these topics are hidden entirely: absent from full, absent from the
 * "allowed" list, and requesting one by name returns 400 (unknown profile) — so
 * their very existence is not disclosed. Single-topic profiles let a monitoring
 * poller fetch just the cheap slice it needs; full is the complete picture.
 *
 * Route:  /api/v2/statistic[?profile=qso|confirmations|system|full]
 *         confirmations additionally accepts
 *         [&type=][&since=][&qso_since=][&band=][&mode=]
 * Scope:  statistic:read
 */
class Statistic_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'statistic';

	/** Topics exposing instance/admin data — only for administrator tokens. */
	protected const ADMIN_TOPICS = ['system'];

	/**
	 * Translated label for the registry entry of this resource's read scope.
	 */
	protected static function scope_labels() {
		return ['read' => __("Read statistics")];
	}

	/**
	 * GET /api/v2/statistic
	 * Optional query: ?profile=<topic>|full (default qso).
	 */
	public function index() {
		// Note: CI registers models under their lowercased name.
		$this->CI->load->model('debug_model');

		// Topic key => builder. "full" is every permitted topic; each key is
		// also a single-topic profile. Adding a topic here exposes it both ways;
		// list it in ADMIN_TOPICS to restrict it to administrators.
		$topics = [
			'qso'      => function () {
				return $this->qso_topic();
			},
			'confirmations' => function () {
				return $this->confirmations_topic();
			},
			'system'   => function () {
				return $this->system_topic();
			}
		];

		$is_admin = $this->is_admin();

		// Topics this token may see; admin-only topics are hidden from non-admins
		// entirely (not just refused), so their existence is not disclosed.
		$permitted = array_values(array_filter(array_keys($topics), function ($key) use ($is_admin) {
			return $is_admin || !in_array($key, self::ADMIN_TOPICS, true);
		}));
		$allowed = array_merge($permitted, ['full']);

		$profile = strtolower(trim((string) $this->param('profile', 'qso')));
		if (!in_array($profile, $allowed, true)) {
			throw new Api_v2_exception(
				'validation_error',
				'Unknown profile "' . $profile . '". Allowed: ' . implode(', ', $allowed),
				400,
				['allowed' => $allowed]
			);
		}

		$selected = $profile === 'full' ? $permitted : [$profile];

		$data = [];
		foreach ($selected as $key) {
			$data[$key] = $topics[$key]();
		}

		$this->CI->api_v2_response->respond($data, 200, ['profile' => $profile, 'admin' => $is_admin]);
	}

	/**
	 * Whether the token owner is a Wavelog administrator. Evaluated from the
	 * token's user_id (there is no session in the API), gating the admin topics.
	 */
	protected function is_admin() {
		return $this->CI->user_model->is_admin($this->user_id());
	}

	// --- Topics ------------------------------------------------------------

	/**
	 * QSO analytics for the token owner: total, activity (today/month/year),
	 * band/mode breakdown, confirmation and DXCC totals. Scoped to the owner's
	 * station locations, so the numbers are the user's own, not the instance.
	 *
	 * DXCC semantics (mirrors the dashboard DXCC card):
	 * - worked: unique valid DXCC entities worked in the log
	 * - confirmed: DXCC entities confirmed by paper QSL OR LoTW (combined,
	 *   deduped) — the Needed numerator; the card itself shows the split below
	 * - confirmed_paper / confirmed_lotw: the paper-QSL / LoTW split the card
	 *   displays in its Confirmed row
	 * - available: current DXCC entities in the active DXCC list
	 * - deleted: DXCC entities worked but since deleted from the active list
	 *   (worked plus the same paper/LoTW confirmation split), shown in brackets
	 *
	 * eQSL remains visible in the dashboard/UI and in the confirmations topic,
	 * but it is intentionally excluded from dxcc.confirmed.
	 */
	protected function qso_topic() {
		$this->CI->load->model('logbook_model');
		$station_ids = $this->owner_station_ids();

		// The model's station-scoped stats functions treat [-1] as "no
		// locations" and return zeros, matching a token owner with no stations.
		$scope = $station_ids ?: [-1];

		$counts = $this->CI->logbook_model->get_qso_counts($scope);

		// dashboard_stats_batch() already computes confirmation counts and DXCC
		// worked/confirmed totals for a given set of station locations, so we
		// reuse it instead of duplicating those queries.
		$batch = $this->CI->logbook_model->dashboard_stats_batch($scope);

		return [
			'total'    => $counts['total'],
			'activity' => [
				'today' => $counts['today'],
				'month' => $counts['month'],
				'year'  => $counts['year'],
			],
			'breakdown' => [
				'by_band' => $this->shape_counts($this->CI->logbook_model->total_bands(null, null, $scope, 12), 'band'),
				'by_mode' => $this->shape_counts($this->CI->logbook_model->total_modes(null, null, $scope, 12), 'mode'),
			],
			'dxcc' => [
				'worked'          => (int) $batch['Countries_Worked'],
				'confirmed'       => (int) $batch['Countries_Worked_Confirmed'],
				'confirmed_paper' => (int) $batch['Countries_Worked_QSL'],
				'confirmed_lotw'  => (int) $batch['Countries_Worked_LOTW'],
				'available'       => $this->CI->logbook_model->count_dxcc_entities(),
				'deleted' => [
					'worked'          => (int) $batch['Countries_Deleted_Worked'],
					'confirmed_paper' => (int) $batch['Countries_Deleted_Worked_QSL'],
					'confirmed_lotw'  => (int) $batch['Countries_Deleted_Worked_LOTW'],
				],
			],
		];
	}

	/**
	 * QSL confirmation counts for the token owner, per confirmation type, with
	 * a band and a mode breakdown alongside the grand totals.
	 *
	 * Filters (all optional, all combinable):
	 *   type      comma list of lotw|eqsl|qsl|qrz|clublog (default: all)
	 *   since     YYYY-MM-DD floor on the date a confirmation was *received*
	 *   qso_since YYYY-MM-DD floor on the date the *QSO* was made
	 *   qso_until YYYY-MM-DD ceiling on the date the *QSO* was made
	 *   band      COL_BAND value, or SAT for satellite QSOs
	 *   mode      matched against the mode or the submode
	 *
	 * The resolved filters are echoed back so a poller can interpret the numbers
	 * without having to remember the query it sent.
	 */
	protected function confirmations_topic() {
		$this->CI->load->model('logbook_model');

		$types = $this->parse_type_list('type', self::CONFIRMATION_TYPES) ?: self::CONFIRMATION_TYPES;
		$since = $this->parse_date('since');
		$qso_since = $this->parse_date('qso_since');
		$qso_until = $this->parse_date('qso_until');
		$band = $this->normalize_band($this->param('band'));
		$mode = $this->normalize_mode($this->param('mode'));

		$station_ids = $this->owner_station_ids();

		$filters = [
			'station_ids' => $station_ids,
			'types'       => $types,
			'since'       => $since,
			'qso_since'   => $qso_since,
			'qso_until'   => $qso_until,
			'band'        => $band,
			'mode'        => $mode,
		];

		return [
			'counts'  => $this->shape_confirmations(
				$this->CI->logbook_model->count_confirmations_filtered($filters)
			),
			'by_band' => array_map([$this, 'shape_confirmations'],
				$this->CI->logbook_model->count_confirmations_filtered($filters, 'band')
			),
			'by_mode' => array_map([$this, 'shape_confirmations'],
				$this->CI->logbook_model->count_confirmations_filtered($filters, 'mode')
			),
			'filters' => [
				'type'      => $types,
				'since'     => $since ?: null,
				'qso_since' => $qso_since ?: null,
				'qso_until' => $qso_until ?: null,
				'band'      => $band ?: null,
				'mode'      => $mode ?: null,
			],
		];
	}

	/**
	 * Cast a confirmation count row to ints, so monitoring gets numbers rather
	 * than the strings the database driver hands back. The group key ("band" /
	 * "mode") is the only non-numeric column and stays a string.
	 */
	protected function shape_confirmations($row) {
		$out = [];
		foreach ($row as $key => $value) {
			$out[$key] = in_array($key, ['band', 'mode'], true) ? $value : (int) $value;
		}
		return $out;
	}

	/**
	 * Version / build information about the running instance.
	 */
	protected function system_topic() {
		// migration_version lives in config/migration.php, which is not part of
		// the request bootstrap here; load it (fail gracefully) before reading.
		$this->CI->config->load('migration', false, true);

		if (!$this->CI->load->is_loaded('worker')) {
			$this->CI->load->library('worker');
		}

		$status = $this->CI->worker->status();

		if ($status['enabled']) {
			$worker['enabled']         		= true;
			$worker['client_url'] 			= $this->CI->worker->client_url() ?: null;
			$worker['nodes']       			= $status['nodes'];
			$worker['nodes_alive']       	= $status['nodes_alive'];
			$worker['nodes_total'] 			= $status['nodes_total'];
			$worker['active_topics']     	= $status['active_topics'];
			$worker['connected_clients'] 	= $status['connected_clients'];
		}

		$result = [
			'wavelog'          => $this->CI->optionslib->get_option('version') ?: null,
			'adif'             => $this->CI->optionslib->get_option('adif_version') ?: null,
			'migration_db'     => (int) $this->CI->debug_model->getMigrationVersion(),
			'migration_config' => $this->CI->config->item('migration_version'),
			'database'         => $this->CI->db->version(),
			'php'              => PHP_VERSION,
			'environment'      => ENVIRONMENT,
			'time'             => gmdate('Y-m-d H:i:s'),
			'wavelog_stats'     => [
				'users'    => (int) $this->CI->debug_model->count_users(),
				'stations' => $this->CI->debug_model->count_stations(),
				'logbooks' => $this->CI->debug_model->count_logbooks(),
				'radios'   => $this->CI->debug_model->count_radios(),
			],
			'system_stats'     => [
				'memory_usage' => memory_get_usage(true),
				'memory_peak'  => memory_get_peak_usage(true),
				'cpu_time'     => getrusage()['ru_utime.tv_sec'] ?? null,
			],
			'cache' => $this->CI->debug_model->get_cache_info(),
		];
		$result['worker'] = $worker ?? ['enabled' => false];

		return $result;
	}

	// --- Helpers -----------------------------------------------------------

	/**
	 * Shape a grouped-count query ("<key>, count" rows, as returned by
	 * total_bands()/total_modes()) into [{<key>, count}] with an int count.
	 * Accepts the CI query object (or null when there are no locations).
	 */
	protected function shape_counts($query, $key) {
		$out = [];
		if ($query !== null) {
			foreach ($query->result() as $row) {
				$out[] = [$key => $row->$key, 'count' => (int) $row->count];
			}
		}
		return $out;
	}
}
