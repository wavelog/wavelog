<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Contest resource
 *
 * Manages contest sessions (the Contesting module's grouping of QSOs into a
 * contest run) and their QSO links. This is the API counterpart to the
 * Contesting pages in the web UI and enables external tools - loggers,
 * offline mirrors, sync clients - to read and replicate contest sessions
 * instead of rebuilding them by hand via "Import Historical Contests".
 *
 * Route:  /api/v2/contest[/{id}]
 * Scope:  contest:read / contest:write / contest:delete
 *
 * Verbs:
 *   GET    /contest        List the token owner's contest sessions
 *   GET    /contest/{id}   Single session, including its linked QSO ids
 *   POST   /contest        Create a session (optionally linking QSOs)
 *   PATCH  /contest/{id}   Partial update; link/unlink QSOs
 *   DELETE /contest/{id}   Delete a session (QSOs stay in the log by default)
 *
 * All database access lives in the models (Contesting_model,
 * Contest_admin_model, Logbook_model); this class only validates,
 * orchestrates and formats. Settings defaults and validation come from
 * Contesting_model (session_settings_defaults() /
 * validate_session_settings()), so the rules live in one place.
 *
 * The contest itself is addressed by its ADIF name ("contest", e.g.
 * "DARC-WAG") because the numeric catalog ids are instance-local; responses
 * always carry both. QSO links are addressed by the QSO primary key, as used
 * by the QSO resource; linking and unlinking also maintain COL_CONTEST_ID on
 * the QSO rows, mirroring the advanced logbook's attach workflow.
 *
 * Clubstations: creating and deleting a session and editing its fields,
 * require officer level 9; club members below that level may only link and
 * unlink QSOs they logged themselves, as in the advanced logbook.
 */
class Contest_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'contest';

	public function __construct($auth, $body = null) {
		parent::__construct($auth, $body);
		$this->CI->load->model('contesting_model');
	}

	/** Registry label for this resource's scope (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read'   => __('Read contest sessions'),
			'write'  => __('Create and update contest sessions'),
			'delete' => __('Delete contest sessions'),
		];
	}

	/**
	 * GET /api/v2/contest
	 *
	 * All contest sessions of the token owner, newest first. Filters:
	 *   ?station_id=1,2   ownership-checked station subset
	 *   ?since_id=42      only sessions with id > 42 (incremental sync)
	 */
	public function index() {
		$station_ids = $this->resolve_station_ids();
		if (empty($station_ids)) {
			$this->CI->api_v2_response->respond([]);
			return;
		}

		$rows = $this->CI->contesting_model->get_sessions_for_user(
			$this->user_id(), $station_ids, (int) $this->param('since_id', 0)
		);

		$sessions = [];
		foreach ($rows as $row) {
			$sessions[] = $this->format_session($row);
		}
		$this->CI->api_v2_response->respond($sessions);
	}

	/**
	 * GET /api/v2/contest/{id}
	 *
	 * A single session, including the ids of its linked QSOs so a client can
	 * mirror the linkage (the ids match the QSO resource).
	 */
	public function show($id) {
		$row = $this->require_owned_session($id);
		$session = $this->format_session($row);

		$qso_ids = [];
		foreach ($this->CI->contesting_model->get_session_qsos((int) $id) as $qso) {
			$qso_ids[] = (int) $qso['qso_id'];
		}
		sort($qso_ids);
		$session['qso_ids'] = $qso_ids;

		$this->CI->api_v2_response->respond($session);
	}

	/**
	 * POST /api/v2/contest
	 *
	 * Create a contest session. Required: contest (ADIF name) or contest_id,
	 * time_start, time_end, station_id. Optional: comment, settings (object),
	 * qso_ids (owned QSOs to link right away).
	 *
	 * The settings are merged over the module defaults by
	 * Contesting_model::build_session_settings(), exactly like a session
	 * created in the web UI; exchangetype is derived from the exchange
	 * fields, never taken from the request.
	 */
	public function create() {
		$this->require_write();
		// Contest sessions are shared club infrastructure - officer-only,
		// like update and delete.
		$this->require_club_level(9);

		$body = $this->body();
		$this->require_session_scalars($body);

		$contest_id = $this->resolve_contest($body, true);
		$time_start = $this->parse_datetime_field($body, 'time_start', true);
		$time_end   = $this->parse_datetime_field($body, 'time_end', true);
		$station_id = $this->resolve_station_field($body, true);
		$comment    = isset($body['comment']) ? (string) $body['comment'] : '';
		$settings   = $this->parse_settings($body['settings'] ?? []);
		$settings   = $this->derive_exchangetype($settings);

		// Validate the QSO links BEFORE creating the session, so a rejected
		// link list cannot leave a stale session behind.
		$link_ids = !empty($body['qso_ids'])
			? $this->validate_qso_ids($body['qso_ids'], 'qso_ids')
			: [];

		$session_id = (int) $this->CI->contesting_model->create_contest_session(
			$contest_id, $time_start, $time_end, $station_id, $comment,
			true, $settings, $this->user_id()
		);

		$link_result = null;
		if (!empty($link_ids)) {
			$link_result = $this->perform_link($session_id, $link_ids, $contest_id);
		}

		$session = $this->format_session($this->require_owned_session($session_id));
		if ($link_result !== null) {
			$session['linked']  = $link_result['linked'];
			$session['skipped'] = $link_result['skipped'];
		}

		$headers = ['Location' => base_url('index.php/api/v2/contest/' . $session_id)];
		$this->CI->api_v2_response->respond($session, 201, null, $headers);
	}

	/**
	 * PATCH /api/v2/contest/{id}
	 *
	 * Partial update: only the fields present in the body change. QSO links
	 * are managed with link_qso_ids / unlink_qso_ids (arrays of QSO ids) -
	 * the URL space has no sub-paths, so the linkage travels in the body.
	 */
	public function update($id) {
		$this->require_write();

		$row = $this->require_owned_session($id);
		$body = $this->body();
		$this->require_session_scalars($body);

		$has_fields = isset($body['contest']) || isset($body['contest_id'])
			|| array_key_exists('time_start', $body)
			|| array_key_exists('time_end', $body)
			|| array_key_exists('station_id', $body)
			|| array_key_exists('comment', $body)
			|| array_key_exists('settings', $body);
		if (!$has_fields && empty($body['link_qso_ids']) && empty($body['unlink_qso_ids'])) {
			throw new Api_v2_exception('validation_error', 'No editable fields in request body', 400);
		}

		// Editing the session itself is officer-only in the web UI
		// (clubaccess_check(9)); attaching and detaching QSOs is not
		// (Logbookadvanced uses clubaccess_check(3, $qsoID)) - members below
		// officer level are limited to their own QSOs by validate_qso_ids().
		if ($has_fields) {
			$this->require_club_level(9);
		}

		// Catalog id of the (possibly changed) contest - the field update and
		// the COL_CONTEST_ID maintenance below both need it.
		$catalog_id = (isset($body['contest']) || isset($body['contest_id']))
			? $this->resolve_contest($body, true)
			: (int) $row->contest_adif_id;

		// Validate the link lists BEFORE touching the session, so a rejected
		// list cannot leave a half-applied update behind.
		$link_ids = !empty($body['link_qso_ids'])
			? $this->validate_qso_ids($body['link_qso_ids'], 'link_qso_ids')
			: [];
		$unlink_ids = !empty($body['unlink_qso_ids'])
			? $this->validate_qso_ids($body['unlink_qso_ids'], 'unlink_qso_ids')
			: [];

		if ($has_fields) {
			// Merge the request over the stored state and hand the full set to
			// update_contest_session(), which the web UI uses as well. Only the
			// incoming settings keys are validated - stored settings may
			// legitimately carry keys a future version added.
			$time_start = array_key_exists('time_start', $body)
				? $this->parse_datetime_field($body, 'time_start', true)
				: $row->time_start;
			$time_end = array_key_exists('time_end', $body)
				? $this->parse_datetime_field($body, 'time_end', true)
				: $row->time_end;
			$station_id = array_key_exists('station_id', $body)
				? $this->resolve_station_field($body, true)
				: (int) $row->station_id;
			$comment = array_key_exists('comment', $body)
				? (string) $body['comment']
				: ($row->comment ?? '');
			$stored_settings = json_decode($row->settings ?? '', true) ?? [];
			$settings = array_key_exists('settings', $body)
				? array_merge($stored_settings, $this->parse_settings($body['settings'] ?? []))
				: $stored_settings;
			$settings = $this->derive_exchangetype($settings);

			$this->CI->contesting_model->update_contest_session(
				(int) $id, $catalog_id, $time_start, $time_end, $station_id,
				$comment, $settings, $this->user_id()
			);
		}

		$link_result = $unlinked = null;
		if (!empty($link_ids)) {
			$link_result = $this->perform_link((int) $id, $link_ids, $catalog_id);
		}
		if (!empty($unlink_ids)) {
			$unlinked = $this->perform_unlink((int) $id, $unlink_ids);
		}

		$session = $this->format_session($this->require_owned_session($id));
		if ($link_result !== null) {
			$session['linked']  = $link_result['linked'];
			$session['skipped'] = $link_result['skipped'];
		}
		if ($unlinked !== null) {
			$session['unlinked'] = $unlinked;
		}
		$this->CI->api_v2_response->respond($session);
	}

	/**
	 * DELETE /api/v2/contest/{id}
	 *
	 * Delete a session. The linked QSOs stay in the logbook and only lose
	 * their link - pass ?delete_qsos=true to remove them as well (full
	 * teardown via Logbook_model::delete(), like the web UI's checkbox).
	 * Removing QSOs additionally requires the qso:delete scope.
	 */
	public function delete($id) {
		$this->require_delete();
		// Deleting is officer-only in the web UI (clubaccess_check(9)).
		$this->require_club_level(9);

		$this->require_owned_session($id);

		$delete_qsos = filter_var($this->param('delete_qsos', 'false'), FILTER_VALIDATE_BOOLEAN);
		if ($delete_qsos && !in_array('qso:delete', $this->auth['scopes'] ?? [], true)) {
			// contest:delete covers the session; removing logbook QSOs is a
			// QSO-resource power and needs that resource's delete scope.
			throw new Api_v2_exception(
				'insufficient_scope',
				'Token is missing the required scope: qso:delete',
				403,
				['required_scope' => 'qso:delete']
			);
		}

		$this->CI->contesting_model->delete_contest_session(
			(int) $id, $delete_qsos, $this->user_id()
		);

		$this->CI->api_v2_response->no_content();
	}

	// --- Internals ---------------------------------------------------------

	/**
	 * Fetch a session row of the token owner or throw 404. A session of
	 * another user is treated as not found, like on every other resource.
	 */
	protected function require_owned_session($id) {
		if (!is_numeric($id)) {
			throw new Api_v2_exception('validation_error', 'Session id must be numeric', 400);
		}
		$rows = $this->CI->contesting_model->get_sessions_for_user(
			$this->user_id(), null, 0, (int) $id
		);
		if (empty($rows)) {
			throw new Api_v2_exception('not_found', 'Contest session not found', 404);
		}
		return $rows[0];
	}

	/**
	 * Cast a session row (get_sessions_for_user()) to its public shape. The
	 * settings are exposed merged over the module defaults
	 * (Contesting_model::session_settings_defaults()), matching what
	 * get_session_info() reports to the web UI.
	 */
	protected function format_session($row) {
		$settings = array_merge(
			$this->CI->contesting_model->session_settings_defaults(),
			json_decode($row->settings ?? '', true) ?? []
		);

		return [
			'id'           => (int) $row->id,
			'contest'      => $row->contest_adifname,
			'contest_name' => $row->contest_name,
			'time_start'   => $row->time_start,
			'time_end'     => $row->time_end,
			'station_id'   => (int) $row->station_id,
			'comment'      => $row->comment ?? '',
			'settings'     => $settings,
			'qso_count'    => (int) ($row->qso_count ?? 0),
			'created_at'   => $row->creation_date ?? null,
			'updated_at'   => $row->last_modified ?? null,
		];
	}

	/**
	 * Body-level scalar guard that tolerates the documented array fields
	 * (settings object, qso id lists) but rejects nesting anywhere else.
	 */
	protected function require_session_scalars($body) {
		$array_fields = ['settings', 'qso_ids', 'link_qso_ids', 'unlink_qso_ids'];
		$scalars = array_diff_key($body, array_flip($array_fields));
		$this->require_scalar_fields($scalars);
	}

	/**
	 * Resolve the contest catalog entry from "contest" (ADIF name, preferred -
	 * catalog ids are instance-local) or "contest_id". Returns the catalog id.
	 */
	protected function resolve_contest($body, $required) {
		$this->CI->load->model('contest_admin_model');

		$adifname = isset($body['contest']) ? trim((string) $body['contest']) : '';
		$id = isset($body['contest_id']) ? $body['contest_id'] : null;

		if ($adifname !== '') {
			$field = 'contest';
			$contest = $this->CI->contest_admin_model->contest_by_adifname($adifname);
			if ($contest === null) {
				throw new Api_v2_exception(
					'validation_error',
					'Unknown contest: ' . $adifname,
					400,
					['field' => 'contest']
				);
			}
		} elseif ($id !== null) {
			$field = 'contest_id';
			if (!is_numeric($id)) {
				throw new Api_v2_exception('validation_error', 'contest_id must be numeric', 400);
			}
			$contest = $this->CI->contest_admin_model->contest((int) $id);
			if (empty($contest)) {
				throw new Api_v2_exception(
					'validation_error',
					'Unknown contest_id: ' . (int) $id,
					400,
					['field' => 'contest_id']
				);
			}
		} else {
			if ($required) {
				throw new Api_v2_exception(
					'validation_error',
					'Missing required field: contest (ADIF contest name) or contest_id',
					400,
					['missing' => ['contest']]
				);
			}
			return null;
		}

		if ((int) $contest->active !== 1) {
			throw new Api_v2_exception(
				'validation_error',
				'Contest is not active: ' . $contest->adifname,
				400,
				['field' => $field]
			);
		}
		return (int) $contest->id;
	}

	/**
	 * Validate a datetime body field ("YYYY-MM-DD HH:MM" or with seconds).
	 */
	protected function parse_datetime_field($body, $key, $required) {
		$raw = isset($body[$key]) ? trim((string) $body[$key]) : '';
		if ($raw === '') {
			if ($required) {
				throw new Api_v2_exception(
					'validation_error',
					'Missing required field: ' . $key,
					400,
					['missing' => [$key]]
				);
			}
			return null;
		}
		foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
			$date = DateTime::createFromFormat($format, $raw);
			if ($date !== false && $date->format($format) === $raw) {
				return $date->format('Y-m-d H:i:s');
			}
		}
		throw new Api_v2_exception(
			'validation_error',
			$key . ' must be a datetime',
			400,
			['format' => 'YYYY-MM-DD HH:MM[:SS]']
		);
	}

	/**
	 * Validate the station_id body field against the owner's stations.
	 */
	protected function resolve_station_field($body, $required) {
		$id = $body['station_id'] ?? null;
		if ($id === null || $id === '') {
			if ($required) {
				throw new Api_v2_exception(
					'validation_error',
					'Missing required field: station_id',
					400,
					['missing' => ['station_id']]
				);
			}
			return null;
		}
		if (!is_numeric($id)) {
			throw new Api_v2_exception('validation_error', 'station_id must be numeric', 400);
		}
		if (!in_array((int) $id, $this->owner_station_ids(), true)) {
			throw new Api_v2_exception('forbidden', 'station_id not accessible for this token', 403);
		}
		return (int) $id;
	}

	/**
	 * Validate a settings object - keys AND values - via
	 * Contesting_model::validate_session_settings(), the single place that
	 * knows the allowed values. Merging over defaults respectively the stored
	 * settings happens in the caller.
	 */
	protected function parse_settings($settings) {
		$errors = $this->CI->contesting_model->validate_session_settings($settings);
		if (!empty($errors)) {
			throw new Api_v2_exception(
				'validation_error',
				'Invalid settings: ' . implode('; ', $errors),
				400,
				['errors' => $errors]
			);
		}
		return $settings;
	}

	/**
	 * Recompute exchangetype from the final exchange fields
	 * (Contesting_model::exchangetype_for_fields()) - the web UI derives it
	 * the same way, so the two values can never drift apart.
	 */
	protected function derive_exchangetype($settings) {
		$fields = $settings['exchangefields']
			?? $this->CI->contesting_model->session_settings_defaults()['exchangefields'];
		$settings['exchangetype'] = $this->CI->contesting_model->exchangetype_for_fields($fields);
		return $settings;
	}

	/**
	 * Link pre-validated QSO ids to a session (see validate_qso_ids()). Ids
	 * already linked - to this or any other session - are skipped and
	 * reported, so a sync client can re-send its full list idempotently.
	 * COL_CONTEST_ID on the fresh links follows the advanced logbook's
	 * attach workflow (Logbook_model::set_contest()).
	 *
	 * @return array { linked: int, skipped: int[] }
	 */
	protected function perform_link($session_id, $ids, $contest_catalog_id) {
		$existing = $this->CI->contesting_model->get_linked_sessions($ids);

		$skipped = [];
		$to_link = [];
		foreach ($ids as $qso_id) {
			if (isset($existing[$qso_id])) {
				if ($existing[$qso_id] !== (int) $session_id) {
					$skipped[] = $qso_id;
				}
				continue; // already in this session: idempotent no-op
			}
			$to_link[] = $qso_id;
		}
		$linked = $this->CI->contesting_model->link_qsos((int) $session_id, $to_link);

		$this->CI->load->model('logbook_model');
		foreach ($to_link as $qso_id) {
			$this->CI->logbook_model->set_contest($qso_id, (int) $contest_catalog_id);
		}
		return ['linked' => $linked, 'skipped' => $skipped];
	}

	/**
	 * Unlink pre-validated QSO ids from a session. COL_CONTEST_ID is cleared
	 * only on QSOs that were actually linked to THIS session, mirroring the
	 * advanced logbook's detach workflow.
	 *
	 * @return int Number of removed links.
	 */
	protected function perform_unlink($session_id, $ids) {
		$existing = $this->CI->contesting_model->get_linked_sessions($ids);
		$here = [];
		foreach ($ids as $qso_id) {
			if (($existing[$qso_id] ?? 0) === (int) $session_id) {
				$here[] = $qso_id;
			}
		}

		$unlinked = $this->CI->contesting_model->unlink_qsos((int) $session_id, $here);

		$this->CI->load->model('logbook_model');
		foreach ($here as $qso_id) {
			$this->CI->logbook_model->set_contest($qso_id, 0);
		}
		return $unlinked;
	}

	/**
	 * Validate a QSO id list from the body: numeric, owned by the token user
	 * (batched ownership check, see Contesting_model::filter_owned_qso_ids()).
	 * Club members below officer level are additionally restricted to QSOs
	 * they logged themselves.
	 *
	 * @return int[]
	 */
	protected function validate_qso_ids($qso_ids, $field) {
		if (!is_array($qso_ids)) {
			throw new Api_v2_exception('validation_error', $field . ' must be an array of QSO ids', 400);
		}
		$ids = [];
		foreach ($qso_ids as $id) {
			if (!is_numeric($id)) {
				throw new Api_v2_exception('validation_error', $field . ' values must be numeric', 400);
			}
			$ids[] = (int) $id;
		}
		$ids = array_values(array_unique($ids));
		if (empty($ids)) {
			return [];
		}

		$owned = $this->CI->contesting_model->filter_owned_qso_ids(
			$ids, $this->user_id(),
			$this->is_restricted_club_member() ? $this->operator_callsign() : null
		);
		$foreign = array_diff($ids, $owned);
		if (!empty($foreign)) {
			throw new Api_v2_exception(
				'forbidden',
				$field . ' contains QSOs not accessible for this token',
				403,
				['qso_ids' => array_values($foreign)]
			);
		}
		return $ids;
	}
}
