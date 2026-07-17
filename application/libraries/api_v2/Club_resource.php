<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Club members resource (read-only)
 *
 * Lists the members of a clubstation, the v2 equivalent of the v1
 * list_clubmembers endpoint. Only a club officer (permission level 9) using a
 * member-issued token may read the list: the token owner is the clubstation and
 * the token creator is the member acting on its behalf, so a token where owner
 * == creator (a personal token) is never a club officer.
 *
 * Route:  /api/v2/club
 * Scope:  club:read
 */
class Club_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'club';

	/** Registry label for this resource's scope (see scope_definitions()). */
	protected static function scope_labels() {
		return [
			'read' => __('Read club members'),
		];
	}

	/**
	 * GET /api/v2/club
	 * The clubstation's members (callsign, user_name, p_level).
	 */
	public function index() {
		$club_id    = $this->user_id();
		$created_by = $this->auth['created_by'];

		$this->CI->load->model('club_model');
		$club_perm = $this->CI->club_model->get_permission_noui($club_id, $created_by);

		// Officer check: the acting member (created_by) must hold p_level 9 on the
		// clubstation (club_id), and must not be the clubstation itself.
		if ($club_id == $created_by || (int) ($club_perm ?? 0) !== 9) {
			throw new Api_v2_exception('forbidden', 'Token is not a club officer', 403);
		}

		$members = [];
		foreach ($this->CI->club_model->get_club_members($club_id) as $member) {
			$members[] = [
				'user_firstname' => $member->user_firstname,
				'user_lastname'  => $member->user_lastname,
				'user_locator'    => $member->user_locator,
				'callsign'  => $member->user_callsign,
				'user_name' => $member->user_name,
				'user_email' => $member->user_email,
				'permission_level'   => (int) $member->p_level,
				'user_language' => $member->user_language,
			];
		}

		$this->CI->api_v2_response->respond($members, 200, ['count' => count($members)]);
	}
}
