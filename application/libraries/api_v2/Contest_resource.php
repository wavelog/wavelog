<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Contest sessions resource
 *
 * Exposes the token owner's contest sessions. The existing Contesting_model
 * is session-bound; this resource uses direct DB queries with the token user_id.
 *
 * Routes:
 *   GET    /api/v2/contest           list all sessions (newest first)
 *   GET    /api/v2/contest/{id}      single session with QSO count
 *   POST   /api/v2/contest           create session
 *   PATCH  /api/v2/contest/{id}      update session
 *   DELETE /api/v2/contest/{id}      delete session (and optionally its QSOs)
 *
 * Scope: contest:read / contest:write / contest:delete
 *
 * POST body fields:
 *   contest_id     int  required  — id from the contest table (ADIF contest id)
 *   station_id     int  required  — must belong to the token owner
 *   time_start     str  required  — "YYYY-MM-DD HH:MM:SS" UTC
 *   time_end       str  optional
 *   notes          str  optional
 *
 * DELETE query param:
 *   ?delete_qsos=true  — also removes QSOs linked to this session (default false)
 */
class Contest_resource extends Api_v2_resource {

	protected $scope = 'contest';

	protected static function scope_labels() {
		return [
			'read'   => __('Read contest sessions'),
			'write'  => __('Create and update contest sessions'),
			'delete' => __('Delete contest sessions'),
		];
	}

	/**
	 * GET /api/v2/contest
	 * Optional: ?page=, ?per_page= (default 50, max 200)
	 */
	public function index() {
		$uid  = $this->user_id();
		$page = max(1, (int) $this->param('page', 1));
		$per  = min(200, max(1, (int) $this->param('per_page', 50)));

		$sql_count = "SELECT COUNT(*) AS total FROM contest_session WHERE user_id = ?";
		$total = (int) $this->CI->db->query($sql_count, [$uid])->row()->total;

		$offset = ($page - 1) * $per;
		$sql = "SELECT
					cs.id,
					cs.time_start,
					cs.time_end,
					cs.comment,
					cs.contest_adif_id,
					c.name AS contest_name,
					cs.station_id,
					sp.station_callsign,
					COUNT(cq.id) AS qso_count
				FROM contest_session cs
				JOIN contest c ON c.id = cs.contest_adif_id
				JOIN station_profile sp ON sp.station_id = cs.station_id
				LEFT JOIN contest_qsos cq ON cq.contest_session_id = cs.id
				WHERE cs.user_id = ?
				GROUP BY cs.id
				ORDER BY cs.time_start DESC
				LIMIT ? OFFSET ?";

		$rows = $this->CI->db->query($sql, [$uid, $per, $offset])->result();
		$out  = array_map([$this, 'format_list'], $rows);

		$this->CI->api_v2_response->respond($out, 200, [
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per,
			'total_pages' => (int) ceil($total / $per),
			'has_more'    => ($offset + $per) < $total,
		]);
	}

	/**
	 * GET /api/v2/contest/{id}
	 */
	public function show($id) {
		$row = $this->require_owned($id);
		$this->CI->api_v2_response->respond($this->format_detail($row));
	}

	/**
	 * POST /api/v2/contest
	 */
	public function create() {
		$this->require_write();

		$body = $this->body();
		$this->require_scalar_fields($body);

		foreach (['contest_id', 'station_id', 'time_start'] as $field) {
			if (empty($body[$field])) {
				throw new Api_v2_exception('validation_error', 'Missing required field: ' . $field, 400, ['missing' => [$field]]);
			}
		}

		$uid        = $this->user_id();
		$contest_id = (int) $body['contest_id'];
		$station_id = (int) $body['station_id'];
		$time_start = xss_clean((string) $body['time_start']);
		$time_end   = isset($body['time_end'])   ? xss_clean((string) $body['time_end']) : null;
		$notes      = isset($body['notes'])      ? xss_clean((string) $body['notes'])    : '';

		// Validate station ownership.
		$this->CI->load->model('stations');
		if (!$this->CI->stations->check_station_against_user($station_id, $uid)) {
			throw new Api_v2_exception('forbidden', 'station_id does not belong to this token', 403);
		}

		// Validate contest exists.
		$c = $this->CI->db->where('id', $contest_id)->get('contest');
		if ($c->num_rows() === 0) {
			throw new Api_v2_exception('not_found', 'Contest not found', 404);
		}

		$this->CI->db->insert('contest_session', [
			'user_id'         => $uid,
			'contest_adif_id' => $contest_id,
			'station_id'      => $station_id,
			'time_start'      => $time_start,
			'time_end'        => $time_end,
			'comment'         => $notes,
			'settings'        => '{}',
		]);

		$session_id = $this->CI->db->insert_id();
		$row = $this->require_owned($session_id);
		$headers = ['Location' => base_url('index.php/api/v2/contest/' . $session_id)];
		$this->CI->api_v2_response->respond($this->format_detail($row), 201, null, $headers);
	}

	/**
	 * PATCH /api/v2/contest/{id}
	 * Accepts: time_start, time_end, notes, station_id
	 */
	public function update($id) {
		$this->require_write();
		$this->require_owned($id);

		$body = $this->body();
		$this->require_scalar_fields($body);

		$data = [];
		if (array_key_exists('time_start', $body)) {
			$data['time_start'] = xss_clean((string) $body['time_start']);
		}
		if (array_key_exists('time_end', $body)) {
			$data['time_end'] = $body['time_end'] !== null ? xss_clean((string) $body['time_end']) : null;
		}
		if (array_key_exists('notes', $body)) {
			$data['comment'] = xss_clean((string) $body['notes']);
		}
		if (array_key_exists('station_id', $body)) {
			$uid = $this->user_id();
			$this->CI->load->model('stations');
			if (!$this->CI->stations->check_station_against_user((int) $body['station_id'], $uid)) {
				throw new Api_v2_exception('forbidden', 'station_id does not belong to this token', 403);
			}
			$data['station_id'] = (int) $body['station_id'];
		}

		if (empty($data)) {
			throw new Api_v2_exception('validation_error', 'No editable fields in request body', 400);
		}

		$this->CI->db->where('id', (int) $id)->where('user_id', $this->user_id())->update('contest_session', $data);
		$row = $this->require_owned($id);
		$this->CI->api_v2_response->respond($this->format_detail($row));
	}

	/**
	 * DELETE /api/v2/contest/{id}
	 * Query: ?delete_qsos=true to also remove linked QSOs.
	 */
	public function delete($id) {
		$this->require_delete();
		$this->require_owned($id);
		$session_id = (int) $id;

		$delete_qsos = $this->param('delete_qsos') === 'true';

		if ($delete_qsos) {
			// Fetch linked QSO ids, then delete via logbooks route to clean up
			// related tables (awards, images, etc.). Use the model if loaded.
			$qso_ids = $this->CI->db
				->select('qso_id')
				->where('contest_session_id', $session_id)
				->get('contest_qsos')
				->result();

			$this->CI->db->where('contest_session_id', $session_id)->delete('contest_qsos');

			if (!empty($qso_ids)) {
				$this->CI->load->model('logbook_model');
				foreach ($qso_ids as $q) {
					$this->CI->logbook_model->deleteQso((int) $q->qso_id);
				}
			}
		} else {
			// Unlink QSOs from the session without deleting them.
			$this->CI->db->where('contest_session_id', $session_id)->delete('contest_qsos');
		}

		$this->CI->db->where('id', $session_id)->where('user_id', $this->user_id())->delete('contest_session');
		$this->CI->api_v2_response->no_content();
	}

	// --- Helpers -----------------------------------------------------------

	protected function require_owned($id) {
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Api_v2_exception('not_found', 'Contest session not found', 404);
		}
		$row = $this->fetch_detail((int) $id);
		if ($row === null) {
			throw new Api_v2_exception('not_found', 'Contest session not found', 404);
		}
		return $row;
	}

	protected function fetch_detail($id) {
		$sql = "SELECT
					cs.id,
					cs.time_start,
					cs.time_end,
					cs.comment,
					cs.contest_adif_id,
					c.name AS contest_name,
					c.adifname AS contest_adifname,
					cs.station_id,
					sp.station_callsign,
					sp.station_gridsquare,
					COUNT(cq.id) AS qso_count
				FROM contest_session cs
				JOIN contest c ON c.id = cs.contest_adif_id
				JOIN station_profile sp ON sp.station_id = cs.station_id
				LEFT JOIN contest_qsos cq ON cq.contest_session_id = cs.id
				WHERE cs.id = ? AND cs.user_id = ?
				GROUP BY cs.id";
		$q = $this->CI->db->query($sql, [$id, $this->user_id()]);
		return $q->num_rows() > 0 ? $q->row() : null;
	}

	protected function format_list($row) {
		return [
			'id'           => (int) $row->id,
			'contest_id'   => (int) $row->contest_adif_id,
			'contest_name' => $row->contest_name ?? null,
			'station_id'   => (int) $row->station_id,
			'callsign'     => $row->station_callsign ?? null,
			'time_start'   => $row->time_start ?? null,
			'time_end'     => $row->time_end    ?? null,
			'qso_count'    => (int) $row->qso_count,
		];
	}

	protected function format_detail($row) {
		return [
			'id'               => (int) $row->id,
			'contest_id'       => (int) $row->contest_adif_id,
			'contest_name'     => $row->contest_name     ?? null,
			'contest_adifname' => $row->contest_adifname ?? null,
			'station_id'       => (int) $row->station_id,
			'callsign'         => $row->station_callsign  ?? null,
			'gridsquare'       => $row->station_gridsquare ?? null,
			'time_start'       => $row->time_start ?? null,
			'time_end'         => $row->time_end   ?? null,
			'notes'            => $row->comment    ?? null,
			'qso_count'        => (int) $row->qso_count,
		];
	}
}
