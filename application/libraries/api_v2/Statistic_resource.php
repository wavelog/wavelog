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
 *   - instance: operational view of the whole instance - users and their
 *               activity, QSO counts per user, cron jobs and the master
 *               cron, worker. Also available in OpenMetrics text format
 *               (?format=openmetrics, or an Accept header asking for
 *               application/openmetrics-text) for Prometheus. (admin only)
 *   - full:     all permitted topics.
 *
 * The system and instance topics expose instance internals and are restricted
 * to tokens owned by a Wavelog administrator (user_type 99). For a non-admin
 * token these topics are hidden entirely: absent from full, absent from the
 * "allowed" list, and requesting one by name returns 400 (unknown profile) — so
 * their very existence is not disclosed. Single-topic profiles let a monitoring
 * poller fetch just the cheap slice it needs; full is the complete picture.
 *
 * Route:  /api/v2/statistic[?profile=qso|confirmations|system|instance|full]
 *         confirmations additionally accepts
 *         [&type=][&since=][&qso_since=][&band=][&mode=]
 *         instance additionally accepts [&format=json|openmetrics]
 * Scope:  statistic:read
 */
class Statistic_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'statistic';

	/** Topics exposing instance/admin data — only for administrator tokens. */
	protected const ADMIN_TOPICS = ['system', 'instance'];

	/** An enabled cron job counts as overdue this long after its next_run. */
	protected const CRON_OVERDUE_SECONDS = 600;

	/** Content type of the OpenMetrics text format. */
	protected const OPENMETRICS_CONTENT_TYPE = 'application/openmetrics-text; version=1.0.0; charset=utf-8';

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
			},
			'instance' => function () {
				return $this->instance_topic();
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

		$format = $this->parse_format();
		if ($format === 'openmetrics' && $profile !== 'instance') {
			// Only an explicit ?format= is an error; an Accept header falls back to JSON
			if ($this->param('format') !== null) {
				throw new Api_v2_exception('validation_error', 'format=openmetrics is only available for profile=instance', 400);
			}
			$format = 'json';
		}

		$selected = $profile === 'full' ? $permitted : [$profile];

		$data = [];
		foreach ($selected as $key) {
			$data[$key] = $topics[$key]();
		}

		if ($format === 'openmetrics') {
			$this->emit_openmetrics($data['instance']);
			return;
		}

		$this->CI->api_v2_response->respond($data, 200, ['profile' => $profile, 'admin' => $is_admin]);
	}

	/**
	 * Output format: ?format=json|openmetrics. Without the parameter an Accept
	 * header asking for OpenMetrics (as Prometheus sends it) selects it.
	 */
	protected function parse_format() {
		$format = $this->param('format');
		if ($format === null) {
			$accept = (string) $this->CI->input->get_request_header('Accept', true);
			return (stripos($accept, 'application/openmetrics-text') !== false) ? 'openmetrics' : 'json';
		}
		$format = strtolower(trim((string) $format));
		if (!in_array($format, ['json', 'openmetrics'], true)) {
			throw new Api_v2_exception(
				'validation_error',
				'Unknown format "' . $format . '". Allowed: json, openmetrics',
				400,
				['allowed' => ['json', 'openmetrics']]
			);
		}
		return $format;
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
					'confirmed'       => (int) $batch['Countries_Deleted_Confirmed'],
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
		$result['worker'] = $this->worker_status();

		return $result;
	}

	/**
	 * Status of the worker (websocket nodes), or enabled=false without one.
	 */
	protected function worker_status() {
		if (!$this->CI->load->is_loaded('worker')) {
			$this->CI->load->library('worker');
		}

		$status = $this->CI->worker->status();
		if (!$status['enabled']) {
			return ['enabled' => false];
		}

		return [
			'enabled'           => true,
			'client_url'        => $this->CI->worker->client_url() ?: null,
			'nodes'             => $status['nodes'],
			'nodes_alive'       => $status['nodes_alive'],
			'nodes_total'       => $status['nodes_total'],
			'active_topics'     => $status['active_topics'],
			'connected_clients' => $status['connected_clients'],
		];
	}

	/**
	 * Operational view of the whole instance for its administrator: build and
	 * migration state, users and their activity, QSO counts per user, cron
	 * jobs and the master cron, worker. QSO counts come from one grouped query
	 * over the QSO table; "today" and "month" go by the QSO time (COL_TIME_ON),
	 * as there is no insert time - use the change of "total" over time for the
	 * number of QSOs added.
	 */
	protected function instance_topic() {
		$this->CI->config->load('migration', false, true);
		$this->CI->load->model('cron_model');
		$now = time();

		$qso_counts = [];
		foreach ($this->CI->debug_model->instance_qso_counts(date('Y-m-d 00:00:00'), date('Y-m-01 00:00:00')) as $row) {
			$qso_counts[(int) $row->user_id] = $row;
		}

		$users = [];
		$by_type = ['admin' => 0, 'user' => 0, 'clubstation' => 0];
		$active = ['24h' => 0, '7d' => 0, '30d' => 0];
		$windows = ['24h' => 86400, '7d' => 7 * 86400, '30d' => 30 * 86400];
		$created_30d = 0;
		foreach ($this->CI->debug_model->instance_users() as $row) {
			$type = ((int) $row->clubstation === 1) ? 'clubstation' : (((int) $row->user_type === 99) ? 'admin' : 'user');
			$by_type[$type]++;

			$last_seen = $this->to_timestamp($row->last_seen);
			foreach ($windows as $window => $seconds) {
				if ($last_seen !== null && $now - $last_seen <= $seconds) {
					$active[$window]++;
				}
			}
			$created = $this->to_timestamp($row->created_at);
			if ($created !== null && $now - $created <= $windows['30d']) {
				$created_30d++;
			}

			$qsos = $qso_counts[(int) $row->user_id] ?? null;
			$users[] = [
				'user_id'  => (int) $row->user_id,
				'callsign' => $row->user_callsign,
				'type'     => $type,
				'last_seen' => $row->last_seen ?: null,
				'stations' => (int) $row->stations,
				'qsos'     => [
					'total' => (int) ($qsos->total ?? 0),
					'today' => (int) ($qsos->today ?? 0),
					'month' => (int) ($qsos->month ?? 0),
				],
				'last_qso' => $qsos->last_qso ?? null,
			];
		}

		$jobs = [];
		$cron_summary = ['total' => 0, 'enabled' => 0, 'failed' => 0, 'overdue' => 0];
		foreach ($this->CI->cron_model->get_crons() as $cron) {
			$enabled = (int) $cron->enabled === 1;
			$next_run = $this->to_timestamp($cron->next_run);
			$overdue = $enabled && $next_run !== null && $now - $next_run > self::CRON_OVERDUE_SECONDS;
			$failed = $enabled && $cron->status === 'failed';

			$cron_summary['total']++;
			$cron_summary['enabled'] += $enabled ? 1 : 0;
			$cron_summary['failed'] += $failed ? 1 : 0;
			$cron_summary['overdue'] += $overdue ? 1 : 0;

			$jobs[] = [
				'id'         => $cron->id,
				'enabled'    => $enabled,
				'status'     => $cron->status,
				'expression' => $cron->expression,
				'last_run'   => $cron->last_run ?: null,
				'next_run'   => $cron->next_run ?: null,
				'overdue'    => $overdue,
			];
		}

		// The master cron stores its last run in UTC (see Cron::get_mastercron_status())
		$master_last_run = $this->CI->optionslib->get_option('mastercron_last_run') ?: null;
		$master = DateTime::createFromFormat('Y-m-d H:i:s', (string) $master_last_run, new DateTimeZone('UTC'));

		return [
			'build' => [
				'wavelog'     => $this->CI->optionslib->get_option('version') ?: null,
				'adif'        => $this->CI->optionslib->get_option('adif_version') ?: null,
				'database'    => $this->CI->db->version(),
				'php'         => PHP_VERSION,
				'environment' => ENVIRONMENT,
			],
			'migration' => [
				'db'     => (int) $this->CI->debug_model->getMigrationVersion(),
				'config' => (int) $this->CI->config->item('migration_version'),
			],
			'users' => [
				'total'       => count($users),
				'by_type'     => $by_type,
				'active'      => $active,
				'created_30d' => $created_30d,
			],
			'stations' => $this->CI->debug_model->count_stations(),
			'logbooks' => $this->CI->debug_model->count_logbooks(),
			'radios'   => $this->CI->debug_model->count_radios(),
			'qsos'     => (int) $this->CI->debug_model->count_all_qso(),
			'per_user' => $users,
			'cron'     => [
				'master' => [
					'last_run'    => $master_last_run,
					'age_seconds' => $master ? $now - $master->getTimestamp() : null,
				],
				'summary' => $cron_summary,
				'jobs'    => $jobs,
			],
			'worker' => $this->worker_status(),
		];
	}

	/**
	 * Unix timestamp of a DB date/time in the instance time zone, or null.
	 */
	protected function to_timestamp($value) {
		if ($value === null || $value === '' || strpos((string) $value, '0000-00-00') === 0) {
			return null;
		}
		$ts = strtotime((string) $value);
		return $ts === false ? null : $ts;
	}

	// --- OpenMetrics -------------------------------------------------------

	/**
	 * Render the instance topic in the OpenMetrics text format and send it
	 * (no JSON envelope). Values that are unknown (e.g. a user that never
	 * logged a QSO has no last-QSO time) are left out.
	 */
	protected function emit_openmetrics($i) {
		$out = '';

		$this->om_family($out, 'wavelog_build', 'info', 'Wavelog build information', [[[
			'version'     => $i['build']['wavelog'],
			'adif'        => $i['build']['adif'],
			'database'    => $i['build']['database'],
			'php'         => $i['build']['php'],
			'environment' => $i['build']['environment'],
		], 1]]);
		$this->om_family($out, 'wavelog_migration_version', 'gauge', 'Database migration version, applied (db) and expected (config)', [
			[['source' => 'db'], $i['migration']['db']],
			[['source' => 'config'], $i['migration']['config']],
		]);

		$samples = [];
		foreach ($i['users']['by_type'] as $type => $count) {
			$samples[] = [['type' => $type], $count];
		}
		$this->om_family($out, 'wavelog_users', 'gauge', 'User accounts by type', $samples);
		$samples = [];
		foreach ($i['users']['active'] as $window => $count) {
			$samples[] = [['window' => $window], $count];
		}
		$this->om_family($out, 'wavelog_users_active', 'gauge', 'Users seen within the window', $samples);
		$this->om_family($out, 'wavelog_users_created', 'gauge', 'Users created within the window', [[['window' => '30d'], $i['users']['created_30d']]]);
		$this->om_family($out, 'wavelog_stations', 'gauge', 'Station locations', [[[], $i['stations']]]);
		$this->om_family($out, 'wavelog_logbooks', 'gauge', 'Logbooks', [[[], $i['logbooks']]]);
		$this->om_family($out, 'wavelog_radios', 'gauge', 'CAT radios', [[[], $i['radios']]]);
		$this->om_family($out, 'wavelog_qsos', 'gauge', 'QSOs in the log (decreases when QSOs are deleted)', [[[], $i['qsos']]]);

		$per_user = [
			'info' => [], 'qsos' => [], 'today' => [], 'month' => [], 'stations' => [], 'last_seen' => [], 'last_qso' => [],
		];
		foreach ($i['per_user'] as $u) {
			$labels = ['user_id' => $u['user_id'], 'callsign' => $u['callsign']];
			$per_user['info'][]     = [$labels + ['type' => $u['type']], 1];
			$per_user['qsos'][]     = [$labels, $u['qsos']['total']];
			$per_user['today'][]    = [$labels, $u['qsos']['today']];
			$per_user['month'][]    = [$labels, $u['qsos']['month']];
			$per_user['stations'][] = [$labels, $u['stations']];
			$per_user['last_seen'][] = [$labels, $this->to_timestamp($u['last_seen'])];
			$per_user['last_qso'][]  = [$labels, $this->to_timestamp($u['last_qso'])];
		}
		$this->om_family($out, 'wavelog_user', 'info', 'User account', $per_user['info']);
		$this->om_family($out, 'wavelog_user_qsos', 'gauge', 'QSOs of the user', $per_user['qsos']);
		$this->om_family($out, 'wavelog_user_qsos_today', 'gauge', 'QSOs of the user with a QSO time today', $per_user['today']);
		$this->om_family($out, 'wavelog_user_qsos_month', 'gauge', 'QSOs of the user with a QSO time this month', $per_user['month']);
		$this->om_family($out, 'wavelog_user_stations', 'gauge', 'Station locations of the user', $per_user['stations']);
		$this->om_family($out, 'wavelog_user_last_seen_timestamp_seconds', 'gauge', 'Last time the user was seen', $per_user['last_seen'], 'seconds');
		$this->om_family($out, 'wavelog_user_last_qso_timestamp_seconds', 'gauge', 'QSO time of the last QSO of the user', $per_user['last_qso'], 'seconds');

		$cron = ['enabled' => [], 'status' => [], 'overdue' => [], 'last_run' => [], 'next_run' => []];
		$states = ['healthy', 'pending', 'failed', 'disabled'];
		foreach ($i['cron']['jobs'] as $job) {
			$labels = ['cron' => $job['id']];
			$cron['enabled'][]  = [$labels, $job['enabled'] ? 1 : 0];
			$cron['overdue'][]  = [$labels, $job['overdue'] ? 1 : 0];
			$cron['last_run'][] = [$labels, $this->to_timestamp($job['last_run'])];
			$cron['next_run'][] = [$labels, $this->to_timestamp($job['next_run'])];
			foreach (array_unique(array_merge($states, [(string) $job['status']])) as $state) {
				if ($state !== '') {
					$cron['status'][] = [$labels + ['wavelog_cron_status' => $state], $job['status'] === $state ? 1 : 0];
				}
			}
		}
		$this->om_family($out, 'wavelog_cron_enabled', 'gauge', 'Cron job is enabled', $cron['enabled']);
		$this->om_family($out, 'wavelog_cron_status', 'stateset', 'Status of the cron job', $cron['status']);
		$this->om_family($out, 'wavelog_cron_overdue', 'gauge', 'Enabled cron job is more than ' . self::CRON_OVERDUE_SECONDS . ' s past its next run', $cron['overdue']);
		$this->om_family($out, 'wavelog_cron_last_run_timestamp_seconds', 'gauge', 'Last run of the cron job', $cron['last_run'], 'seconds');
		$this->om_family($out, 'wavelog_cron_next_run_timestamp_seconds', 'gauge', 'Next scheduled run of the cron job', $cron['next_run'], 'seconds');

		$master = $i['cron']['master']['age_seconds'] === null ? null : time() - $i['cron']['master']['age_seconds'];
		$this->om_family($out, 'wavelog_mastercron_last_run_timestamp_seconds', 'gauge', 'Last run of the master cron (should run every minute)', [[[], $master]], 'seconds');

		$worker = $i['worker'];
		$this->om_family($out, 'wavelog_worker_enabled', 'gauge', 'Worker is configured', [[[], $worker['enabled'] ? 1 : 0]]);
		if ($worker['enabled']) {
			$this->om_family($out, 'wavelog_worker_nodes', 'gauge', 'Worker nodes', [
				[['state' => 'alive'], $worker['nodes_alive']],
				[['state' => 'total'], $worker['nodes_total']],
			]);
			$this->om_family($out, 'wavelog_worker_connected_clients', 'gauge', 'Clients connected to the worker', [[[], $worker['connected_clients']]]);
		}

		$out .= "# EOF\n";

		$this->CI->output
			->set_status_header(200)
			->set_header('Content-Type: ' . self::OPENMETRICS_CONTENT_TYPE)
			->set_header('Access-Control-Allow-Origin: *')
			->set_output($out);
	}

	/**
	 * Append one metric family: its metadata and samples, each [labels, value].
	 * Samples with a null value are skipped. Info samples get the "_info"
	 * suffix the OpenMetrics spec requires.
	 */
	protected function om_family(&$out, $name, $type, $help, $samples, $unit = null) {
		$out .= '# TYPE ' . $name . ' ' . $type . "\n";
		if ($unit !== null) {
			$out .= '# UNIT ' . $name . ' ' . $unit . "\n";
		}
		$out .= '# HELP ' . $name . ' ' . $help . "\n";

		$sample_name = ($type === 'info') ? $name . '_info' : $name;
		foreach ($samples as [$labels, $value]) {
			if ($value === null) {
				continue;
			}
			$pairs = [];
			foreach ($labels as $key => $label) {
				$label = str_replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], (string) $label);
				$pairs[] = $key . '="' . $label . '"';
			}
			$out .= $sample_name . ($pairs ? '{' . implode(',', $pairs) . '}' : '') . ' ' . (is_float($value) ? $value : (int) $value) . "\n";
		}
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
