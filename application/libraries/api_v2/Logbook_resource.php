<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Logbook resource
 *
 * Exposes the token owner's logbooks (station_logbooks table) via the
 * Logbooks_model, which owns all access to that table.
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
		$this->CI->load->model('logbooks_model');
		$uid      = $this->user_id();
		$rows     = $this->CI->logbooks_model->list_for_user($uid);
		$active_id = $this->CI->logbooks_model->get_active_id_for_user($uid);
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
		$this->CI->load->model('logbooks_model');
		$row = $this->require_owned($id);
		$active_id = $this->CI->logbooks_model->get_active_id_for_user($this->user_id());
		$this->CI->api_v2_response->respond($this->format($row, $active_id));
	}

	/**
	 * POST /api/v2/logbook
	 * Required body field: name
	 */
	public function create() {
		$this->require_write();
		$this->CI->load->model('logbooks_model');

		$body = $this->body();
		$this->require_scalar_fields($body);

		$name = trim((string) ($body['name'] ?? ''));
		if ($name === '') {
			throw new Api_v2_exception('validation_error', 'Missing required field: name', 400, ['missing' => ['name']]);
		}

		$uid = $this->user_id();
		$logbook_id = $this->CI->logbooks_model->create_for_user($name, $uid);
		if ($logbook_id === -1) {
			throw new Api_v2_exception('conflict', 'A logbook with that name already exists', 409);
		}

		// Make active if it is the user's first logbook.
		$active_id = $this->CI->logbooks_model->get_active_id_for_user($uid);
		if ($active_id === null) {
			$this->CI->logbooks_model->set_active_for_user($logbook_id, $uid);
			$active_id = $logbook_id;
		}

		$row = $this->CI->logbooks_model->get_by_id_for_user($logbook_id, $uid);
		$headers = ['Location' => base_url('index.php/api/v2/logbook/' . $logbook_id)];
		$this->CI->api_v2_response->respond($this->format($row, $active_id), 201, null, $headers);
	}

	/**
	 * PATCH /api/v2/logbook/{id}
	 * Accepts: name (string), active (bool true), link_station_id (int), unlink_station_id (int)
	 */
	public function update($id) {
		$this->require_write();
		$this->CI->load->model('logbooks_model');
		$this->CI->load->model('stations');

		$row = $this->require_owned($id);
		$body = $this->body();
		$this->require_scalar_fields($body);

		$uid        = $this->user_id();
		$logbook_id = (int) $row->logbook_id;
		$changed    = false;

		// Rename.
		if (array_key_exists('name', $body)) {
			$name = trim((string) $body['name']);
			if ($name === '') {
				throw new Api_v2_exception('validation_error', 'name cannot be blank', 400);
			}
			$this->CI->logbooks_model->rename_for_user($logbook_id, $name, $uid);
			$changed = true;
		}

		// Set active.
		if (!empty($body['active'])) {
			$this->CI->logbooks_model->set_active_for_user($logbook_id, $uid);
			$changed = true;
		}

		// Link a station location to this logbook.
		if (!empty($body['link_station_id'])) {
			$station_id = (int) $body['link_station_id'];
			if (!$this->CI->stations->check_station_against_user($station_id, $uid)) {
				throw new Api_v2_exception('forbidden', 'link_station_id does not belong to this token', 403);
			}
			$this->CI->logbooks_model->link_station($logbook_id, $station_id);
			$changed = true;
		}

		// Unlink a station location from this logbook.
		if (!empty($body['unlink_station_id'])) {
			$station_id = (int) $body['unlink_station_id'];
			$this->CI->logbooks_model->unlink_station($logbook_id, $station_id);
			$changed = true;
		}

		if (!$changed) {
			throw new Api_v2_exception('validation_error', 'No editable fields in request body', 400);
		}

		$updated   = $this->CI->logbooks_model->get_by_id_for_user($logbook_id, $uid);
		$active_id = $this->CI->logbooks_model->get_active_id_for_user($uid);
		$this->CI->api_v2_response->respond($this->format($updated, $active_id));
	}

	/**
	 * DELETE /api/v2/logbook/{id}
	 * The active logbook cannot be deleted.
	 */
	public function delete($id) {
		$this->require_delete();
		$this->CI->load->model('logbooks_model');

		$row        = $this->require_owned($id);
		$logbook_id = (int) $row->logbook_id;
		$uid        = $this->user_id();

		if ($logbook_id === $this->CI->logbooks_model->get_active_id_for_user($uid)) {
			throw new Api_v2_exception('conflict', 'The active logbook cannot be deleted', 409);
		}

		$this->CI->logbooks_model->delete_for_user($logbook_id, $uid);
		$this->CI->api_v2_response->no_content();
	}

	// --- Helpers -----------------------------------------------------------

	protected function require_owned($id) {
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Api_v2_exception('not_found', 'Logbook not found', 404);
		}
		$row = $this->CI->logbooks_model->get_by_id_for_user((int) $id, $this->user_id());
		if ($row === null) {
			throw new Api_v2_exception('not_found', 'Logbook not found', 404);
		}
		return $row;
	}

	protected function format($row, $active_id) {
		$logbook_id = (int) $row->logbook_id;
		return [
			'id'                 => $logbook_id,
			'name'               => $row->logbook_name ?? null,
			'active'             => ($logbook_id === $active_id),
			'linked_station_ids' => $this->CI->logbooks_model->get_linked_station_ids($logbook_id),
		];
	}
}
