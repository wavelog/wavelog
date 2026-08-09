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
 * The contest itself is addressed by its ADIF name ("contest", e.g.
 * "DARC-WAG") because the numeric catalog ids are instance-local; responses
 * always carry both. QSO links are addressed by the QSO primary key, as used
 * by the QSO resource.
 *
 * Clubstations: creating is open to every member (mirroring the web UI),
 * updating and deleting require officer level 9, exactly like the
 * corresponding Contesting controller actions.
 */
class Contest_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'contest';

	/**
	 * Session settings defaults, mirroring
	 * Contesting_model::build_session_settings(). The keys double as the
	 * whitelist of what the API accepts; unknown keys are rejected rather
	 * than silently dropped, so a client notices its typo.
	 */
	protected const SETTINGS_DEFAULTS = [
		'exchangetype'    => 'Serial',
		'copyexchangeto'  => '',
		'exchangefields'  => ['serial'],
		'callbook_lookup' => true,
		'custom_name'     => '',
		'serial_per_band' => false,
		'serial_scope'    => 'station',
	];

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

		$since_id = (int) $this->param('since_id', 0);

		$placeholders = implode(',', array_fill(0, count($station_ids), '?'));
		$bindings = array_merge([$this->user_id()], $station_ids, [$since_id]);
		$query = $this->CI->db->query(
			"SELECT cs.*, c.name AS contest_name, c.adifname AS contest_adifname,
					(SELECT COUNT(*) FROM contest_qsos cq
						WHERE cq.contest_session_id = cs.id) AS qso_count
				FROM contest_session cs
				JOIN contest c ON c.id = cs.contest_adif_id
				WHERE cs.user_id = ?
				AND cs.station_id IN ({$placeholders})
				AND cs.id > ?
				ORDER BY cs.time_start DESC, cs.id DESC",
			$bindings
		);

		$sessions = [];
		foreach ($query->result() as $row) {
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
		$query = $this->CI->db->query(
			"SELECT qso_id FROM contest_qsos WHERE contest_session_id = ? ORDER BY qso_id",
			[(int) $id]
		);
		foreach ($query->result() as $link) {
			$qso_ids[] = (int) $link->qso_id;
		}
		$session['qso_ids'] = $qso_ids;

		$this->CI->api_v2_response->respond($session);
	}

	/**
	 * POST /api/v2/contest
	 *
	 * Create a contest session. Required: contest (ADIF name) or contest_id,
	 * time_start, time_end, station_id. Optional: comment, settings (object),
	 * qso_ids (owned QSOs to link right away).
	 */
	public function create() {
		$this->require_write();

		$body = $this->body();
		$this->require_session_scalars($body);

		$contest_id = $this->resolve_contest($body, true);
		$time_start = $this->parse_datetime_field($body, 'time_start', true);
		$time_end   = $this->parse_datetime_field($body, 'time_end', true);
		$station_id = $this->resolve_station_field($body, true);
		$comment    = isset($body['comment']) ? (string) $body['comment'] : '';
		// merge over the defaults, exactly like build_session_settings() does
		// for sessions created in the web UI
		$settings   = array_merge(self::SETTINGS_DEFAULTS,
			$this->parse_settings($body['settings'] ?? []));

		$this->CI->db->query(
			"INSERT INTO contest_session
				(user_id, contest_adif_id, time_start, time_end, station_id, comment, settings)
				VALUES (?, ?, ?, ?, ?, ?, ?)",
			[$this->user_id(), $contest_id, $time_start, $time_end, $station_id,
				$comment, json_encode($settings)]
		);
		$session_id = (int) $this->CI->db->insert_id();

		$link_result = null;
		if (!empty($body['qso_ids'])) {
			$link_result = $this->link_qsos($session_id, $body['qso_ids']);
		}

		$row = $this->require_owned_session($session_id);
		$session = $this->format_session($row);
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
		// Contest sessions are shared club infrastructure; the web UI limits
		// editing to officers (clubaccess_check(9)) - mirror that here.
		$this->require_club_level(9);

		$row = $this->require_owned_session($id);
		$body = $this->body();
		$this->require_session_scalars($body);

		$fields = [];
		$bindings = [];
		if (isset($body['contest']) || isset($body['contest_id'])) {
			$fields[] = 'contest_adif_id = ?';
			$bindings[] = $this->resolve_contest($body, true);
		}
		if (array_key_exists('time_start', $body)) {
			$fields[] = 'time_start = ?';
			$bindings[] = $this->parse_datetime_field($body, 'time_start', true);
		}
		if (array_key_exists('time_end', $body)) {
			$fields[] = 'time_end = ?';
			$bindings[] = $this->parse_datetime_field($body, 'time_end', true);
		}
		if (array_key_exists('station_id', $body)) {
			$fields[] = 'station_id = ?';
			$bindings[] = $this->resolve_station_field($body, true);
		}
		if (array_key_exists('comment', $body)) {
			$fields[] = 'comment = ?';
			$bindings[] = (string) $body['comment'];
		}
		if (array_key_exists('settings', $body)) {
			// merge over the stored settings so a partial object keeps the
			// rest; only the incoming keys are validated - stored settings may
			// legitimately carry keys a future version added
			$stored = json_decode($row->settings ?? '', true) ?? [];
			$fields[] = 'settings = ?';
			$bindings[] = json_encode(array_merge(
				$stored, $this->parse_settings($body['settings'] ?? [])
			));
		}

		if (empty($fields) && empty($body['link_qso_ids']) && empty($body['unlink_qso_ids'])) {
			throw new Api_v2_exception('validation_error', 'No editable fields in request body', 400);
		}

		if (!empty($fields)) {
			$bindings[] = (int) $id;
			$bindings[] = $this->user_id();
			$this->CI->db->query(
				"UPDATE contest_session SET " . implode(', ', $fields) .
				" WHERE id = ? AND user_id = ?",
				$bindings
			);
		}

		$link_result = $unlinked = null;
		if (!empty($body['link_qso_ids'])) {
			$link_result = $this->link_qsos((int) $id, $body['link_qso_ids']);
		}
		if (!empty($body['unlink_qso_ids'])) {
			$unlinked = $this->unlink_qsos((int) $id, $body['unlink_qso_ids']);
		}

		$row = $this->require_owned_session($id);
		$session = $this->format_session($row);
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
	 */
	public function delete($id) {
		$this->require_delete();
		// Deleting is officer-only in the web UI (clubaccess_check(9)).
		$this->require_club_level(9);

		$this->require_owned_session($id);

		$delete_qsos = filter_var($this->param('delete_qsos', 'false'), FILTER_VALIDATE_BOOLEAN);
		if ($delete_qsos) {
			$this->CI->load->model('logbook_model');
			$query = $this->CI->db->query(
				"SELECT qso_id FROM contest_qsos WHERE contest_session_id = ?",
				[(int) $id]
			);
			foreach ($query->result() as $link) {
				$this->CI->logbook_model->delete($link->qso_id);
			}
			// contest_qsos rows cascade away with the logbook rows
		} else {
			$this->CI->db->query(
				"DELETE FROM contest_qsos WHERE contest_session_id = ?", [(int) $id]
			);
		}

		$this->CI->db->query(
			"DELETE FROM contest_session WHERE id = ? AND user_id = ?",
			[(int) $id, $this->user_id()]
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
		$query = $this->CI->db->query(
			"SELECT cs.*, c.name AS contest_name, c.adifname AS contest_adifname,
					(SELECT COUNT(*) FROM contest_qsos cq
						WHERE cq.contest_session_id = cs.id) AS qso_count
				FROM contest_session cs
				JOIN contest c ON c.id = cs.contest_adif_id
				WHERE cs.id = ? AND cs.user_id = ?
				LIMIT 1",
			[(int) $id, $this->user_id()]
		);
		$row = $query->row();
		if ($row === null) {
			throw new Api_v2_exception('not_found', 'Contest session not found', 404);
		}
		return $row;
	}

	/**
	 * Cast a session row to its public shape. The settings JSON is exposed as
	 * an object so a sync client can mirror a session 1:1.
	 */
	protected function format_session($row) {
		return [
			'id'          => (int) $row->id,
			'contest'     => $row->contest_adifname,
			'contest_name'=> $row->contest_name,
			'time_start'  => $row->time_start,
			'time_end'    => $row->time_end,
			'station_id'  => (int) $row->station_id,
			'comment'     => $row->comment ?? '',
			'settings'    => $this->settings_object($row->settings),
			'qso_count'   => (int) ($row->qso_count ?? 0),
			'created_at'  => $row->creation_date ?? null,
			'updated_at'  => $row->last_modified ?? null,
		];
	}

	/**
	 * Decode the stored settings JSON for a response. An empty result is
	 * emitted as {} rather than [] - PHP's empty array would otherwise
	 * change the JSON type between "no settings" and "some settings".
	 */
	protected function settings_object($raw) {
		$settings = json_decode($raw ?? '', true);
		return (is_array($settings) && !empty($settings)) ? $settings : new stdClass();
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
		$adifname = isset($body['contest']) ? trim((string) $body['contest']) : '';
		$id = isset($body['contest_id']) ? $body['contest_id'] : null;

		if ($adifname !== '') {
			$query = $this->CI->db->query(
				"SELECT id FROM contest WHERE adifname = ? LIMIT 1", [$adifname]
			);
			$row = $query->row();
			if ($row === null) {
				throw new Api_v2_exception(
					'validation_error',
					'Unknown contest: ' . $adifname,
					400,
					['field' => 'contest']
				);
			}
			return (int) $row->id;
		}

		if ($id !== null) {
			if (!is_numeric($id)) {
				throw new Api_v2_exception('validation_error', 'contest_id must be numeric', 400);
			}
			$query = $this->CI->db->query(
				"SELECT id FROM contest WHERE id = ? LIMIT 1", [(int) $id]
			);
			if ($query->row() === null) {
				throw new Api_v2_exception(
					'validation_error',
					'Unknown contest_id: ' . (int) $id,
					400,
					['field' => 'contest_id']
				);
			}
			return (int) $id;
		}

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
	 * Validate a settings object against the known session settings keys.
	 * Mirrors Contesting_model::build_session_settings(): missing keys keep
	 * their defaults when the session is used, unknown keys are a 400.
	 */
	protected function parse_settings($settings) {
		if (!is_array($settings)) {
			throw new Api_v2_exception('validation_error', 'settings must be an object', 400);
		}
		$unknown = array_diff(array_keys($settings), array_keys(self::SETTINGS_DEFAULTS));
		if (!empty($unknown)) {
			throw new Api_v2_exception(
				'validation_error',
				'Unknown settings key(s): ' . implode(', ', $unknown),
				400,
				['allowed' => array_keys(self::SETTINGS_DEFAULTS)]
			);
		}
		return $settings;
	}

	/**
	 * Link QSOs to a session. Every id must be a QSO of the token owner;
	 * foreign ids are a 403 (like foreign station ids elsewhere). Ids already
	 * linked - to this or any other session - are skipped and reported, so a
	 * sync client can re-send its full list idempotently.
	 *
	 * @return array { linked: int, skipped: int[] }
	 */
	protected function link_qsos($session_id, $qso_ids) {
		$ids = $this->validate_qso_ids($qso_ids, 'qso_ids');

		// One lookup for the existing links, one batched insert - a contest
		// session can easily hold thousands of QSOs.
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$query = $this->CI->db->query(
			"SELECT qso_id, contest_session_id FROM contest_qsos
				WHERE qso_id IN ({$placeholders})",
			$ids
		);
		$existing = [];
		foreach ($query->result() as $link) {
			$existing[(int) $link->qso_id] = (int) $link->contest_session_id;
		}

		$skipped = [];
		$rows = [];
		foreach ($ids as $qso_id) {
			if (isset($existing[$qso_id])) {
				if ($existing[$qso_id] !== (int) $session_id) {
					$skipped[] = $qso_id;
				}
				continue; // already in this session: idempotent no-op
			}
			$rows[] = ['contest_session_id' => (int) $session_id, 'qso_id' => $qso_id];
		}
		if (!empty($rows)) {
			$this->CI->db->insert_batch('contest_qsos', $rows);
		}
		return ['linked' => count($rows), 'skipped' => $skipped];
	}

	/**
	 * Unlink QSOs from a session (the QSOs stay in the logbook).
	 *
	 * @return int Number of removed links.
	 */
	protected function unlink_qsos($session_id, $qso_ids) {
		$ids = $this->validate_qso_ids($qso_ids, 'unlink_qso_ids');
		if (empty($ids)) {
			return 0;
		}
		$placeholders = implode(',', array_fill(0, count($ids), '?'));
		$this->CI->db->query(
			"DELETE FROM contest_qsos
				WHERE contest_session_id = ? AND qso_id IN ({$placeholders})",
			array_merge([(int) $session_id], $ids)
		);
		return $this->CI->db->affected_rows();
	}

	/**
	 * Validate a QSO id list from the body: numeric, owned by the token user.
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

		// Ownership exactly like Logbook_model::check_qso_is_accessible(),
		// batched into one query instead of one round-trip per QSO
		$table = $this->CI->config->item('table_name');
		$qso_placeholders = implode(',', array_fill(0, count($ids), '?'));
		$query = $this->CI->db->query(
			"SELECT q.COL_PRIMARY_KEY
				FROM {$table} q
				JOIN station_profile sp ON sp.station_id = q.station_id
				WHERE q.COL_PRIMARY_KEY IN ({$qso_placeholders})
				AND sp.user_id = ?",
			array_merge($ids, [$this->user_id()])
		);
		$owned = [];
		foreach ($query->result() as $row) {
			$owned[] = (int) $row->COL_PRIMARY_KEY;
		}
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
