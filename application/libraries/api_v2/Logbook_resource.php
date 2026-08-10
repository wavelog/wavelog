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
 *   GET    /api/v2/logbook           list all logbooks
 *   GET    /api/v2/logbook/{id}      single logbook
 *   POST   /api/v2/logbook           create logbook (body: name)
 *   PATCH  /api/v2/logbook/{id}      rename and/or set active
 *   DELETE /api/v2/logbook/{id}      delete (not allowed when active)
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
		return [
			'id'     => (int) $row->logbook_id,
			'name'   => $row->logbook_name ?? null,
			'active' => ((int) $row->logbook_id === $active_id),
		];
	}
}
