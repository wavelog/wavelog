<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Logbook resource
 *
 * Exposes the token owner's logbooks (station_logbooks table). The existing
 * Logbooks_model is session-bound, so this resource issues direct queries using
 * the token's user_id.
 *
 * Routes:
 *   GET    /api/v2/logbook           list all logbooks (includes linked station ids)
 *   GET    /api/v2/logbook/{id}      single logbook
 *   POST   /api/v2/logbook           create logbook (body: name)
 *   PATCH  /api/v2/logbook/{id}      rename, set active, link/unlink a station
 *   DELETE /api/v2/logbook/{id}      delete (not allowed when active)
 *
 * PATCH body fields (all optional, any combination):
 *   name              string  — new logbook name
 *   active            bool    — make this the active logbook
 *   link_station_id   int     — add a station location to this logbook
 *   unlink_station_id int     — remove a station location from this logbook
 *
 * Scope: logbook:read / logbook:write / logbook:delete
 */
class Logbook_resource extends Api_v2_resource {

	protected $scope = 'logbook';

	protected static function scope_labels() {
		return [
			'read'   => __('Read logbooks'),
			'write'  => __('Create and update logbooks'),
			'delete' => __('Delete logbooks'),
		];
	}

	/**
	 * GET /api/v2/logbook
	 */
	public function index() {
		$rows = $this->fetch_all();
		$active_id = $this->active_logbook_id();
		$out = [];
		foreach ($rows as $row) {
			$out[] = $this->format($row, $active_id);
		}
		$this->CI->api_v2_response->respond($out);
	}

	/**
	 * GET /api/v2/logbook/{id}
	 */
	public function show($id) {
		$row = $this->require_owned($id);
		$this->CI->api_v2_response->respond($this->format($row, $this->active_logbook_id()));
	}

	/**
	 * POST /api/v2/logbook
	 * Required body field: name
	 */
	public function create() {
		$this->require_write();

		$body = $this->body();
		$this->require_scalar_fields($body);

		$name = trim((string) ($body['name'] ?? ''));
		if ($name === '') {
			throw new Api_v2_exception('validation_error', 'Missing required field: name', 400, ['missing' => ['name']]);
		}

		$uid = $this->user_id();

		// Check for duplicate name.
		$existing = $this->CI->db->where('user_id', $uid)->where('logbook_name', $name)->get('station_logbooks');
		if ($existing->num_rows() > 0) {
			throw new Api_v2_exception('conflict', 'A logbook with that name already exists', 409);
		}

		$this->CI->db->insert('station_logbooks', [
			'user_id'      => $uid,
			'logbook_name' => xss_clean($name),
		]);
		$logbook_id = $this->CI->db->insert_id();

		// Make active if it is the user's first logbook.
		$active_id = $this->active_logbook_id();
		if ($active_id === null) {
			$this->set_active($logbook_id);
			$active_id = $logbook_id;
		}

		$row = $this->fetch_one($logbook_id);
		$headers = ['Location' => base_url('index.php/api/v2/logbook/' . $logbook_id)];
		$this->CI->api_v2_response->respond($this->format($row, $active_id), 201, null, $headers);
	}

	/**
	 * PATCH /api/v2/logbook/{id}
	 * Accepts: name (string), active (bool true)
	 */
	public function update($id) {
		$this->require_write();
		$row = $this->require_owned($id);
		$body = $this->body();
		$this->require_scalar_fields($body);

		$uid = $this->user_id();
		$logbook_id = (int) $row->logbook_id;
		$changed = false;

		// Rename.
		if (array_key_exists('name', $body)) {
			$name = trim((string) $body['name']);
			if ($name === '') {
				throw new Api_v2_exception('validation_error', 'name cannot be blank', 400);
			}
			$this->CI->db->where('user_id', $uid)->where('logbook_id', $logbook_id)
				->update('station_logbooks', ['logbook_name' => xss_clean($name)]);
			$changed = true;
		}

		// Set active.
		if (!empty($body['active'])) {
			$this->set_active($logbook_id);
			$changed = true;
		}

		// Link a station location to this logbook.
		if (!empty($body['link_station_id'])) {
			$station_id = (int) $body['link_station_id'];
			$this->CI->load->model('stations');
			if (!$this->CI->stations->check_station_against_user($station_id, $uid)) {
				throw new Api_v2_exception('forbidden', 'link_station_id does not belong to this token', 403);
			}
			$exists = $this->CI->db
				->where('station_logbook_id', $logbook_id)
				->where('station_location_id', $station_id)
				->get('station_logbooks_relationship')->num_rows();
			if (!$exists) {
				$this->CI->db->insert('station_logbooks_relationship', [
					'station_logbook_id'  => $logbook_id,
					'station_location_id' => $station_id,
				]);
			}
			$changed = true;
		}

		// Unlink a station location from this logbook.
		if (!empty($body['unlink_station_id'])) {
			$station_id = (int) $body['unlink_station_id'];
			$this->CI->db
				->where('station_logbook_id', $logbook_id)
				->where('station_location_id', $station_id)
				->delete('station_logbooks_relationship');
			$changed = true;
		}

		if (!$changed) {
			throw new Api_v2_exception('validation_error', 'No editable fields in request body', 400);
		}

		$updated = $this->fetch_one($logbook_id);
		$this->CI->api_v2_response->respond($this->format($updated, $this->active_logbook_id()));
	}

	/**
	 * DELETE /api/v2/logbook/{id}
	 * The active logbook cannot be deleted.
	 */
	public function delete($id) {
		$this->require_delete();
		$row = $this->require_owned($id);
		$logbook_id = (int) $row->logbook_id;

		if ($logbook_id === $this->active_logbook_id()) {
			throw new Api_v2_exception('conflict', 'The active logbook cannot be deleted', 409);
		}

		// Remove static map images first (mirrors Logbooks_model::delete()).
		if (!$this->CI->load->is_loaded('staticmap_model')) {
			$this->CI->load->model('staticmap_model');
		}
		$this->CI->staticmap_model->remove_static_map_image(null, $logbook_id);

		$this->CI->db->where('user_id', $this->user_id())->where('logbook_id', $logbook_id)
			->delete('station_logbooks');

		$this->CI->api_v2_response->no_content();
	}

	// --- Helpers -----------------------------------------------------------

	protected function require_owned($id) {
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Api_v2_exception('not_found', 'Logbook not found', 404);
		}
		$row = $this->fetch_one((int) $id);
		if ($row === null) {
			throw new Api_v2_exception('not_found', 'Logbook not found', 404);
		}
		return $row;
	}

	protected function fetch_all() {
		return $this->CI->db
			->where('user_id', $this->user_id())
			->order_by('logbook_id', 'ASC')
			->get('station_logbooks')
			->result();
	}

	protected function fetch_one($id) {
		$q = $this->CI->db
			->where('user_id', $this->user_id())
			->where('logbook_id', (int) $id)
			->get('station_logbooks');
		return $q->num_rows() > 0 ? $q->row() : null;
	}

	protected function active_logbook_id() {
		$q = $this->CI->db
			->select('active_station_logbook')
			->where('user_id', $this->user_id())
			->get('users');
		if ($q->num_rows() === 0) return null;
		$v = $q->row()->active_station_logbook;
		return ($v !== null && $v > 0) ? (int) $v : null;
	}

	protected function set_active($logbook_id) {
		$this->CI->db
			->where('user_id', $this->user_id())
			->update('users', ['active_station_logbook' => (int) $logbook_id]);
	}

	protected function format($row, $active_id) {
		$logbook_id = (int) $row->logbook_id;
		return [
			'id'                 => $logbook_id,
			'name'               => $row->logbook_name ?? null,
			'active'             => ($logbook_id === $active_id),
			'linked_station_ids' => $this->linked_station_ids($logbook_id),
		];
	}

	protected function linked_station_ids($logbook_id) {
		$rows = $this->CI->db
			->select('station_location_id')
			->where('station_logbook_id', $logbook_id)
			->get('station_logbooks_relationship')
			->result();
		return array_map(fn($r) => (int) $r->station_location_id, $rows);
	}
}
