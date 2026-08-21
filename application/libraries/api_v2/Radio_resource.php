<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Radios resource (CAT interface)
 *
 * Exposes the token owner's CAT radios (the live rig state stored in the `cat`
 * table: frequency, mode, power, ...). Reuses the existing Cat model.
 *
 * Unlike the other resources, radios are not created by id: a radio is
 * identified by its name and CAT clients (WSJT-X, rigctl, GridTracker, ...)
 * continuously push a fresh state snapshot. POST therefore upserts by name
 * (create or update), mirroring the legacy v1 Api::radio() write path. There is
 * no PATCH/PUT: a new snapshot is a POST.
 *
 * Route:  /api/v2/radio
 * Scope:  radio:read / radio:write / radio:delete
 */
class Radio_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'radio';

	/** Registry labels for this resource's scopes (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read'   => __('Read radios'),
			'write'  => __('Create and update radios'),
			'delete' => __('Delete radios'),
		];
	}

	/**
	 * GET /api/v2/radio
	 * All CAT radios of the token owner. No pagination: users have a handful.
	 */
	public function index() {
		$this->CI->load->model('cat');

		$radios = [];
		$query = $this->CI->cat->status_for_user_id($this->user_id(), $this->visible_operator());
		foreach ($query->result() as $row) {
			$radios[] = $this->format_radio($row);
		}

		$this->CI->api_v2_response->respond($radios);
	}

	/**
	 * GET /api/v2/radio/{id}
	 * A single radio owned by the token holder.
	 */
	public function show($id) {
		$this->CI->api_v2_response->respond($this->format_radio($this->require_owned_radio($id)));
	}

	/**
	 * POST /api/v2/radio
	 * Upsert a radio state by name: creates the radio when its name is new for
	 * this owner/operator, otherwise updates the existing one. Required body
	 * field: radio. 201 when created, 200 when updated.
	 */
	public function create() {
		$this->require_write();
		$this->CI->load->model('cat');

		$body = $this->body();
		$this->require_scalar_fields($body);
		if (empty($body['radio'])) {
			throw new Api_v2_exception('validation_error', 'Missing required field(s): radio', 400, ['missing' => ['radio']]);
		}

		// Shared shape validation with the legacy v1 endpoint (Cat::update()
		// column limits). Casts numeric fields to int in place.
		$invalid = $this->CI->cat->validate_radio_payload($body);
		if (!empty($invalid)) {
			throw new Api_v2_exception('validation_error', 'Invalid field(s): ' . implode(', ', $invalid), 400, ['invalid' => $invalid]);
		}

		// Clubmode: a member token (owner != creator) logs under the creator as
		// operator, mirroring Api::radio(). Otherwise operator == owner.
		$operator = ($this->auth['user_id'] != $this->auth['created_by'])
			? $this->auth['created_by']
			: $this->auth['user_id'];

		$existing = $this->CI->cat->radio_by_name($body['radio'], $operator, $this->user_id());

		// Only forward the fields Cat::update() understands; cat_url is
		// intentionally not writable via the API (needs URL sanitisation).
		$payload = ['radio' => $body['radio']];
		foreach (['frequency', 'frequency_rx', 'mode', 'mode_rx', 'power', 'prop_mode', 'sat_name'] as $field) {
			if (array_key_exists($field, $body)) {
				$payload[$field] = $body[$field];
			}
		}

		$this->CI->cat->update($payload, $this->user_id(), $operator);

		$row = $this->CI->cat->radio_by_name($body['radio'], $operator, $this->user_id());
		$status  = $existing ? 200 : 201;
		$headers = ($status === 201 && $row) ? ['Location' => base_url('index.php/api/v2/radio/' . (int) $row->id)] : [];
		$this->CI->api_v2_response->respond($row ? $this->format_radio($row) : null, $status, null, $headers);
	}

	/**
	 * DELETE /api/v2/radio/{id}
	 * Remove a radio from the CAT list.
	 */
	public function delete($id) {
		$this->require_delete();
		$this->require_owned_radio($id);

		$this->CI->cat->delete_for_user((int) $id, $this->user_id(), $this->visible_operator());

		$this->CI->api_v2_response->no_content();
	}

	// --- Internal helpers --------------------------------------------------

	/**
	 * The operator whose radios this token may see, or null for "all of the
	 * owner's".
	 *
	 * Radios carry an operator of their own, so on a clubstation each member
	 * registers its own rigs into the shared account. A member below officer
	 * level must therefore only ever see and delete its own - the session-free
	 * equivalent of the clubaccess_check(9) branch in Cat::status().
	 *
	 * @return int|null
	 */
	protected function visible_operator() {
		return $this->is_restricted_club_member() ? $this->auth['created_by'] : null;
	}

	/**
	 * Verify a radio exists and belongs to the token owner, returning its row.
	 * Scoped to the acting operator for restricted club members, so another
	 * member's rig is reported as not found rather than exposed.
	 *
	 * @throws Api_v2_exception 404 when missing or not owned.
	 * @return object cat row.
	 */
	protected function require_owned_radio($id) {
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Api_v2_exception('not_found', 'Radio not found', 404);
		}
		$this->CI->load->model('cat');
		$row = $this->CI->cat->radio_for_user($id, $this->user_id(), $this->visible_operator());
		if ($row === null) {
			throw new Api_v2_exception('not_found', 'Radio not found', 404);
		}
		return $row;
	}

	/**
	 * Shape a cat table row into the public API representation. Frequencies are
	 * in Hz. Unlike Cat::format_status() (tailored to the live-status consumer),
	 * this keeps a stable, id-addressable shape with explicit nulls.
	 */
	protected function format_radio($row) {
		return [
			'id'           => (int) $row->id,
			'radio'        => $row->radio ?? null,
			'frequency'    => isset($row->frequency) && is_numeric($row->frequency) ? (int) $row->frequency : null,
			'frequency_rx' => isset($row->frequency_rx) && is_numeric($row->frequency_rx) ? (int) $row->frequency_rx : null,
			'mode'         => (isset($row->mode) && $row->mode !== '' && $row->mode !== 'non') ? $row->mode : null,
			'mode_rx'      => (isset($row->mode_rx) && $row->mode_rx !== '' && $row->mode_rx !== 'non') ? $row->mode_rx : null,
			'power'        => isset($row->power) && is_numeric($row->power) ? (int) $row->power : null,
			'prop_mode'    => $row->prop_mode ?? null,
			'sat_name'     => (isset($row->sat_name) && $row->sat_name !== '') ? $row->sat_name : null,
			'updated_at'   => $row->timestamp ?? null,
		];
	}
}
