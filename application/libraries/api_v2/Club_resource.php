<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Clubstation members resource
 *
 * Lists and manages the members of a clubstation, the v2 equivalent of the v1
 * list_clubmembers endpoint plus the permission handling of the web UI
 * (controllers/Club.php). Only a club officer (permission level 9) using a
 * member-issued token may use it: the token owner is the clubstation and the
 * token creator is the member acting on its behalf, so a token where owner ==
 * creator (a personal token) is never a club officer.
 *
 * The clubstation is implicit - it is the token owner - so the path id
 * addresses the member: /api/v2/club/{user_id}.
 *
 * Route:  /api/v2/club
 * Scope:  club:read / club:write / club:delete
 */
class Club_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'club';

	/**
	 * The whole resource only exists while the clubstation feature is on.
	 * This overrides the default is_available() of Api_v2_resource, which always returns
	 * true.
	 */
	public static function is_available() {
		$CI =& get_instance();
		return (bool) $CI->config->item('special_callsign');
	}

	/** Registry labels for this resource's scopes (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read'   => __('Read club members'),
			'write'  => __('Manage club member permissions'),
			'delete' => __('Remove club members'),
		];
	}

	/**
	 * GET /api/v2/club
	 * The clubstation's members (callsign, user_name, p_level).
	 */
	public function index() {
		$this->require_permission_level(9);

		$members = [];
		foreach ($this->CI->club_model->get_club_members($this->user_id()) as $member) {
			$members[] = $this->format_member($member);
		}

		$this->CI->api_v2_response->respond($members, 200, ['count' => count($members)]);
	}

	/**
	 * GET /api/v2/club/{user_id}
	 * A single member of the clubstation.
	 */
	public function show($user_id) {
		$this->require_permission_level(9);

		$member = $this->find_member($user_id);
		if ($member === null) {
			throw new Api_v2_exception('not_found', 'User is not a member of this clubstation', 404);
		}

		$this->CI->api_v2_response->respond($this->format_member($member));
	}

	/**
	 * POST /api/v2/club
	 * Add a member to the clubstation.
	 * Body: { user_id, permission_level, notify? }
	 */
	public function create() {
		$this->require_write();
		$this->require_permission_level(9);
		$this->require_not_sso_managed();

		$body = $this->body();
		$this->require_scalar_fields($body);

		// The id comes from the body here, so a missing or malformed one is a
		// validation error rather than an unknown URL.
		if (!isset($body['user_id']) || !is_numeric($body['user_id'])) {
			throw new Api_v2_exception('validation_error', 'user_id must be a numeric user id', 400, ['field' => 'user_id']);
		}
		$user_id = (int) $body['user_id'];

		$this->require_manageable_member($user_id);
		$level = $this->parse_permission_level($body);

		// Changing an existing membership is what PATCH is for; creating one
		// twice must not silently overwrite the level already granted.
		if ((int) $this->CI->club_model->get_permission_noui($this->user_id(), $user_id) > 0) {
			throw new Api_v2_exception('conflict', 'User is already a member of this clubstation', 409);
		}

		$this->CI->club_model->alter_member($this->user_id(), $user_id, $level);

		$this->respond_member($user_id, $body, 'new_member', 201);
	}

	/**
	 * PATCH /api/v2/club/{user_id}
	 * Change a member's permission level.
	 * Body: { permission_level, notify? }
	 */
	public function update($user_id) {
		$this->require_write();
		$this->require_permission_level(9);
		$this->require_not_sso_managed();

		$body = $this->body();
		$this->require_scalar_fields($body);

		$user_id = $this->require_manageable_member($user_id);
		$level = $this->parse_permission_level($body);

		// No upsert: adding a member is POST.
		if ((int) $this->CI->club_model->get_permission_noui($this->user_id(), $user_id) === 0) {
			throw new Api_v2_exception('not_found', 'User is not a member of this clubstation', 404);
		}

		$this->CI->club_model->alter_member($this->user_id(), $user_id, $level);

		$this->respond_member($user_id, $body, 'modified_member', 200);
	}

	/**
	 * DELETE /api/v2/club/{user_id}
	 * Remove a member from the clubstation, together with the API keys and rig
	 * control sessions it created for the club (Club_model::delete_member()).
	 */
	public function delete($user_id) {
		$this->require_delete();
		$this->require_permission_level(9);
		$this->require_not_sso_managed();

		$user_id = $this->require_manageable_member($user_id);

		// Without this a DELETE on a non-member would report success.
		if ((int) $this->CI->club_model->get_permission_noui($this->user_id(), $user_id) === 0) {
			throw new Api_v2_exception('not_found', 'User is not a member of this clubstation', 404);
		}

		if (!$this->CI->club_model->delete_member($this->user_id(), $user_id)) {
			throw new Api_v2_exception('internal_error', 'User could not be removed from the clubstation', 500);
		}

		$this->CI->api_v2_response->no_content();
	}

	// --- Internal helpers --------------------------------------------------

	/**
	 * Every operation here is officer-only. Api_v2::resolve_club_context()
	 * fills club_permission for club tokens only, and only while the
	 * clubstation feature is on, so this one check covers a personal token, a
	 * member below officer level and a disabled feature alike.
	 *
	 * @throws Api_v2_exception 403
	 */
	protected function require_permission_level($level) {
		if (!is_numeric($level) || (int) $level < 1) {
			throw new Api_v2_exception('internal_error', 'Invalid permission level', 500);
		}

		$this->CI->load->model('club_model');

		if ((int) $this->club_permission() !== (int) $level) {
			throw new Api_v2_exception('forbidden', 'Token is not a club officer', 403);
		}
	}

	/**
	 * Refuse writes to a clubstation whose memberships an identity provider
	 * owns. Those are granted and revoked on login, so a write here would be
	 * undone without warning - the web UI disables the form for the same reason.
	 *
	 * @throws Api_v2_exception 409
	 */
	protected function require_not_sso_managed() {
		if ($this->CI->club_model->is_sso_managed($this->user_id())) {
			throw new Api_v2_exception('conflict', 'Club membership is managed by the identity provider', 409);
		}
	}

	/**
	 * Verify a user id may be managed as a member of this clubstation.
	 *
	 * @param mixed $user_id Id from the path or the request body.
	 * @return int The validated user id.
	 * @throws Api_v2_exception 400/403/404
	 */
	protected function require_manageable_member($user_id) {
		if (!is_numeric($user_id) || (int) $user_id < 1) {
			throw new Api_v2_exception('not_found', 'Unknown user', 404);
		}
		$user_id = (int) $user_id;

		if ($user_id === (int) $this->user_id()) {
			throw new Api_v2_exception('validation_error', 'A clubstation cannot be a member of itself', 400);
		}

		// An officer must not be able to lock themselves out of their own club
		// through the API; changing your own level stays a web UI operation.
		if ($user_id === (int) $this->auth['created_by']) {
			throw new Api_v2_exception('forbidden', 'Cannot modify your own club membership', 403);
		}

		$user = $this->CI->user_model->get_by_id($user_id);
		if ($user === null || $user->num_rows() === 0) {
			throw new Api_v2_exception('not_found', 'Unknown user', 404);
		}

		// Mirrors the member search of the web UI, which only offers regular
		// accounts (User_model::search_users() filters on clubstation = 0).
		if ((int) $user->row()->clubstation === 1) {
			throw new Api_v2_exception('validation_error', 'A clubstation cannot be a member of a clubstation', 400);
		}

		return $user_id;
	}

	/**
	 * Read and validate the permission level from the request body against the
	 * levels the web UI offers (Club_model::permission_levels()).
	 *
	 * @throws Api_v2_exception 400
	 */
	protected function parse_permission_level($body) {
		$allowed = array_keys($this->CI->club_model->permission_levels());

		if (!isset($body['permission_level']) || !is_numeric($body['permission_level'])
			|| !in_array((int) $body['permission_level'], $allowed, true)) {
			throw new Api_v2_exception(
				'validation_error',
				'permission_level must be one of: ' . implode(', ', $allowed),
				400,
				['field' => 'permission_level', 'allowed' => $allowed]
			);
		}

		return (int) $body['permission_level'];
	}

	/**
	 * Send the response for a create/update, optionally notifying the member
	 * first. A failed mail is reported in the meta rather than failing the
	 * request: the permission itself was granted either way.
	 *
	 * @param int    $user_id  The member the operation acted on.
	 * @param array  $body     Decoded request body (read for the notify flag).
	 * @param string $template 'new_member' or 'modified_member'.
	 * @param int    $status   201 on create, 200 on update.
	 */
	protected function respond_member($user_id, $body, $template, $status) {
		$meta = null;
		// no notification by default
		if (filter_var($body['notify'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
			$meta = ['notified' => (bool) $this->CI->club_model->notify_member($user_id, $this->user_id(), $template)];
		}

		$headers = ($status === 201)
			? ['Location' => base_url('index.php/api/v2/club/' . $user_id)]
			: [];

		$this->CI->api_v2_response->respond($this->format_member($this->find_member($user_id)), $status, $meta, $headers);
	}

	/**
	 * A single member row of this clubstation, or null when the user is not a
	 * member. Clubs have a handful of members, so filtering the existing list
	 * query is cheaper than a new model method.
	 *
	 * @return object|null
	 */
	protected function find_member($user_id) {
		if (!is_numeric($user_id)) {
			return null;
		}
		foreach ($this->CI->club_model->get_club_members($this->user_id()) as $member) {
			if ((int) $member->user_id === (int) $user_id) {
				return $member;
			}
		}
		return null;
	}

	/**
	 * Shape a club member row into the public API representation.
	 */
	protected function format_member($member) {
		return [
			'user_id'          => (int) $member->user_id,
			'user_firstname'   => $member->user_firstname,
			'user_lastname'    => $member->user_lastname,
			'user_locator'     => $member->user_locator,
			'callsign'         => $member->user_callsign,
			'user_name'        => $member->user_name,
			'user_email'       => $member->user_email,
			'permission_level' => (int) $member->p_level,
			'user_language'    => $member->user_language,
		];
	}
}
