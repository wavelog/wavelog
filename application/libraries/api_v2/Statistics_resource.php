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
 *   - qsos:     QSO analytics — total, rolling activity windows, band/mode
 *               breakdown, confirmation totals (default).
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
 * Route:  /api/v2/statistics[?profile=qsos|system|full]
 * Scope:  statistics:read
 */
class Statistics_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'statistics';

	/** Topics exposing instance/admin data — only for administrator tokens. */
	protected const ADMIN_TOPICS = ['system'];

	/**
	 * GET /api/v2/statistics
	 * Optional query: ?profile=<topic>|full (default qsos).
	 */
	public function index() {
		// Note: CI registers models under their lowercased name.
		$this->CI->load->model('debug_model');

		// Topic key => builder. "full" is every permitted topic; each key is
		// also a single-topic profile. Adding a topic here exposes it both ways;
		// list it in ADMIN_TOPICS to restrict it to administrators.
		$topics = [
			'qsos'     => function () {
				return $this->qsos_topic();
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

		$profile = strtolower(trim((string) $this->param('profile', 'qsos')));
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
		$user = $this->CI->user_model->get_by_id($this->user_id())->row();
		return $user !== null && (int) $user->user_type === 99;
	}

	// --- Topics ------------------------------------------------------------

	/**
	 * QSO analytics for the token owner: total, activity (today/month/year),
	 * band/mode breakdown, confirmation and DXCC totals. Scoped to the owner's
	 * station locations, so the numbers are the user's own, not the instance.
	 */
	protected function qsos_topic() {
		$this->CI->load->model('logbook_model');
		$station_ids = $this->owner_station_ids();

		// The model's station-scoped stats functions treat [-1] as "no
		// locations" and return zeros, matching a token owner with no stations.
		$scope = $station_ids ?: [-1];

		$counts = $this->CI->logbook_model->get_qso_counts($scope);

		// dashboard_stats_batch() already computes confirmation (QSL/eQSL/LoTW
		// received) and DXCC worked/confirmed totals for a given set of station
		// locations, so we reuse it instead of duplicating those queries.
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
			'confirmations' => [
				'lotw' => (int) $batch['LoTW_Received'],
				'eqsl' => (int) $batch['eQSL_Received'],
				'qsl'  => (int) $batch['QSL_Received'],
			],
			'dxcc' => [
				'worked'    => (int) $batch['Countries_Worked'],
				'confirmed' => (int) $batch['Countries_Worked_Confirmed'],
				'available' => $this->CI->logbook_model->count_dxcc_entities(),
			],
		];
	}

	/**
	 * Station location ids owned by the token user; the QSO stats are scoped to
	 * these so they reflect the token owner's logbook, not the whole instance.
	 */
	protected function owner_station_ids() {
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
		$result['worker'] = $worker;

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
