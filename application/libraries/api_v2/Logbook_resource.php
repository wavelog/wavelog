<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Logbook resource
 *
 * Exposes the token owner's logbooks (station_logbooks table) through the
 * Logbooks_model, which owns all access to that table. Ownership is enforced
 * against the token's user_id, never the session.
 *
 * Every verb here has a counterpart in Stationsetup (index, newLogbook_json,
 * saveContainerName, setActiveLogbook_json, linkLocations, unLinkLocations,
 * deleteLogbook_json). That controller is gated at clubstation officer level as
 * a whole - its single exception is list_locations, which Station_resource
 * mirrors with ungated reads - so every verb below carries the same
 * require_club_level(9), reads included: a member below officer level manages
 * no logbooks in the web UI and manages none through the API either.
 *
 * Routes:
 *   GET    /api/v2/logbook           list all logbooks
 *   GET    /api/v2/logbook/{id}      single logbook
 *   POST   /api/v2/logbook           create logbook
 *   PATCH  /api/v2/logbook/{id}      rename, set active, change linked stations
 *   DELETE /api/v2/logbook/{id}      delete (not allowed when active)
 *
 * Body fields (all optional on PATCH, `name` required on POST):
 *   name         string  — logbook name
 *   station_ids  int[]   — the station locations this logbook contains. The list
 *                          replaces the stored one, so it round-trips with what
 *                          a GET returns.
 *   set_active   bool    — make this the owner's active logbook. Deliberately a
 *                          different key from the read-only `active` in the
 *                          response, so a GET result can be sent straight back
 *                          through PATCH without reassigning the active logbook.
 *
 * Scope: logbook:read / logbook:write / logbook:delete
 */
class Logbook_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'logbook';

	/** Registry labels for this resource's scopes (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read'   => __('Read logbooks'),
			'write'  => __('Create and update logbooks'),
			'delete' => __('Delete logbooks'),
		];
	}

	/**
	 * Only sessions that could actually use these scopes are offered them:
	 * outside a clubstation every user manages their own logbooks, inside one
	 * it takes officer level. A member below that would end up with a token
	 * that can only ever answer 403.
	 */
	public static function is_grantable() {
		// Returns true outside a clubstation, so this covers both cases.
		return clubaccess_check(9);
	}

	/**
	 * GET /api/v2/logbook
	 * All logbooks of the token owner. No pagination, same as Station_resource:
	 * users only have a handful of them.
	 */
	public function index() {
		$this->require_club_level(9);

		$rows = $this->CI->logbooks_model->show_all($this->user_id())->result();

		$active_id = $this->active_id();
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
		$this->require_club_level(9);

		$row = $this->require_owned_logbook($id);
		$this->CI->api_v2_response->respond($this->format($row, $this->active_id()));
	}

	/**
	 * POST /api/v2/logbook
	 * Required body field: name.
	 */
	public function create() {
		$this->require_write();
		$this->require_club_level(9);

		$uid  = $this->user_id();
		$body = $this->body();
		$this->require_body_fields($body);

		$name = $this->clean_name($body['name'] ?? '');
		if ($this->CI->logbooks_model->logbook_name_exists($name, $uid)) {
			throw new Api_v2_exception('conflict', 'A logbook with that name already exists', 409);
		}
		$station_ids = array_key_exists('station_ids', $body)
			? $this->validate_station_ids($body['station_ids'])
			: [];

		// add() also makes the logbook active when the user has none yet,
		// mirroring Stationsetup::newLogbook_json().
		$logbook_id = (int) $this->CI->logbooks_model->add($name, $uid);
		$this->sync_station_links($logbook_id, $station_ids, $uid);

		$row = $this->CI->logbooks_model->logbook($logbook_id, $uid)->row();
		$headers = $row ? ['Location' => base_url('index.php/api/v2/logbook/' . $logbook_id)] : [];
		$this->CI->api_v2_response->respond($row ? $this->format($row, $this->active_id()) : null, 201, null, $headers);
	}

	/**
	 * PATCH /api/v2/logbook/{id}
	 *
	 * Everything is validated before the first write, so a rejected request
	 * cannot leave the logbook half-updated.
	 */
	public function update($id) {
		$this->require_write();
		$this->require_club_level(9);

		$uid        = $this->user_id();
		$row        = $this->require_owned_logbook($id);
		$logbook_id = (int) $row->logbook_id;

		$body = $this->body();
		$this->require_body_fields($body);

		$name = array_key_exists('name', $body) ? $this->clean_name($body['name']) : null;
		if ($name !== null && $name !== $row->logbook_name
			&& $this->CI->logbooks_model->logbook_name_exists($name, $uid)) {
			throw new Api_v2_exception('conflict', 'A logbook with that name already exists', 409);
		}

		$station_ids = array_key_exists('station_ids', $body)
			? $this->validate_station_ids($body['station_ids'])
			: null;

		$set_active = !empty($body['set_active']);

		if ($name === null && $station_ids === null && !$set_active) {
			throw new Api_v2_exception('validation_error', 'No editable fields in request body', 400);
		}

		if ($name !== null) {
			$this->CI->logbooks_model->rename($logbook_id, $name, $uid);
		}
		if ($station_ids !== null) {
			$this->sync_station_links($logbook_id, $station_ids, $uid);
		}
		if ($set_active) {
			$this->CI->logbooks_model->set_logbook_active($logbook_id, $uid);
		}

		$updated = $this->CI->logbooks_model->logbook($logbook_id, $uid)->row();
		$this->CI->api_v2_response->respond($this->format($updated, $this->active_id()));
	}

	/**
	 * DELETE /api/v2/logbook/{id}
	 * The active logbook cannot be deleted, the same rule Logbooks_model::delete()
	 * enforces internally; checked here so the caller gets a 409 instead of a
	 * silent no-op. QSOs are not touched - they belong to a station location.
	 */
	public function delete($id) {
		$this->require_delete();
		$this->require_club_level(9);

		$row        = $this->require_owned_logbook($id);
		$logbook_id = (int) $row->logbook_id;

		if ($logbook_id === $this->active_id()) {
			throw new Api_v2_exception('conflict', 'The active logbook cannot be deleted', 409);
		}

		$this->CI->logbooks_model->delete($logbook_id, $this->user_id());
		$this->CI->api_v2_response->no_content();
	}

	// --- Internal helpers --------------------------------------------------

	/**
	 * The owner's active logbook id, or 0 when none is set.
	 */
	protected function active_id() {
		return (int) $this->CI->logbooks_model->find_active_station_logbook_from_userid($this->user_id());
	}

	/**
	 * Verify a logbook exists and belongs to the token owner, returning its row.
	 *
	 * @throws Api_v2_exception 404 when missing or not owned.
	 * @return object station_logbooks row.
	 */
	protected function require_owned_logbook($id) {
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Api_v2_exception('not_found', 'Logbook not found', 404);
		}
		$row = $this->CI->logbooks_model->logbook((int) $id, $this->user_id())->row();
		if ($row === null) {
			throw new Api_v2_exception('not_found', 'Logbook not found', 404);
		}
		return $row;
	}

	/**
	 * Guard against nested JSON, with station_ids exempted: it is the one
	 * list-valued field and validate_station_ids() checks it element by element.
	 */
	protected function require_body_fields($body) {
		$this->require_scalar_fields(array_diff_key($body, ['station_ids' => true]));
	}

	/**
	 * Clean a logbook name exactly as it will be stored, so the duplicate check
	 * and the insert compare the same value.
	 */
	protected function clean_name($raw) {
		$name = xss_clean(trim((string) $raw));
		if ($name === '') {
			throw new Api_v2_exception('validation_error', 'Missing required field: name', 400, ['missing' => ['name']]);
		}
		return $name;
	}

	/**
	 * Validate a station_ids list: numeric and owned by the token user. Foreign
	 * ids are rejected rather than silently dropped, the same rule
	 * resolve_station_ids() applies to the query string.
	 *
	 * @return int[]
	 */
	protected function validate_station_ids($raw) {
		if (!is_array($raw)) {
			throw new Api_v2_exception('validation_error', 'station_ids must be an array of station location ids', 400);
		}
		$owned = $this->owner_station_ids();
		$ids = [];
		foreach ($raw as $sid) {
			if (!is_numeric($sid)) {
				throw new Api_v2_exception('validation_error', 'station_ids values must be numeric', 400);
			}
			$sid = (int) $sid;
			if (!in_array($sid, $owned, true)) {
				throw new Api_v2_exception(
					'forbidden',
					'station_ids contains stations not accessible for this token',
					403,
					['station_ids' => [$sid]]
				);
			}
			$ids[] = $sid;
		}
		return array_values(array_unique($ids));
	}

	/**
	 * Make the logbook's station links match $station_ids exactly.
	 */
	protected function sync_station_links($logbook_id, $station_ids, $user_id) {
		$current = $this->CI->logbooks_model->get_linked_station_ids($logbook_id);

		foreach (array_diff($current, $station_ids) as $station_id) {
			$this->CI->logbooks_model->remove_logbook_location_link($logbook_id, $station_id, $user_id);
		}
		foreach (array_diff($station_ids, $current) as $station_id) {
			$this->CI->logbooks_model->create_logbook_location_link($logbook_id, $station_id, $user_id);
		}
	}

	/**
	 * Shape a station_logbooks row into the public API representation. The shape
	 * is symmetric with the writable fields, so a GET result can be sent straight
	 * back through POST or PATCH.
	 */
	protected function format($row, $active_id) {
		$logbook_id = (int) $row->logbook_id;
		return [
			'id'          => $logbook_id,
			'name'        => $row->logbook_name ?? null,
			'active'      => ($logbook_id === $active_id),
			'station_ids' => $this->CI->logbooks_model->get_linked_station_ids($logbook_id),
		];
	}
}
