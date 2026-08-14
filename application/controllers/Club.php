<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Club extends CI_Controller
{
	/** 
	 * Permission Levels:
	 * 9 - Officer
	 * 3 - Member
	 * 
	 * These permission levels are independent of the codeigniter permission levels and managed in the club_permissions table!
	 * https://docs.wavelog.org/admin-guide/administration/clubstations/
	*/

	/**
     * @var array $permissions
     */
    private $permissions;

	function __construct() {
		parent::__construct();

		$this->load->model('club_model');
		$this->permissions = $this->club_model->permission_levels();
	}

    public function index() {
        // nothing to display
        redirect('dashboard');
    }

    public function permissions($club_id) {

		$this->load->library('form_validation');

		$cid = $this->security->xss_clean($club_id);
		$club = $this->user_model->get_by_id($cid)->row();

		// Check if club managed by SSO
		$ssoManaged = $this->club_model->is_sso_managed($club_id);

		if(!$this->user_model->authorize(99) && (!$this->user_model->authorize(3) || !$this->club_model->club_authorize(9, $cid))) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		if (!is_numeric($cid)) {
			$this->session->set_flashdata('error', __("Invalid User ID!"));
			redirect('user');
		}
		if ($club->clubstation != 1) {
			$this->session->set_flashdata('error', __("This user is not a club station."));
			redirect('user');
		}

		$data['page_title'] = __("Club Permissions");
		$data['club'] = $club;
		$data['club_members'] = $this->club_model->get_club_members($cid);
		$data['permissions'] = $this->permissions;
		$data['sso_managed'] = $ssoManaged;

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/club_permissions.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('club/permissions');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function get_users() {

		if ($this->input->method() !== 'post') {
			$this->session->set_flashdata('error', __("Invalid request method."));
			redirect('dashboard');
		}

		$cid = $this->input->post('club_id', true);

		if(!$this->user_model->authorize(99) && (!$this->user_model->authorize(3) || !$this->club_model->club_authorize(9, $cid))) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$query = (string) $this->input->post('query', true) ?? '';
		if (empty($query)) {
			header('Content-Type: application/json');
			echo json_encode([]);
			return;
		}

		$users = $this->user_model->search_users($query);

		$result = [];

		if ($users != false) {
			foreach ($users->result() as $user) {
				$result[] = [
					'user_id' => $user->user_id,
					'user_name' => $user->user_name,
					'user_callsign' => $user->user_callsign,
					'user_firstname' => $user->user_firstname,
					'user_lastname' => $user->user_lastname
				];
			}
		}

		header('Content-Type: application/json');
    	echo json_encode($result);
	}

	public function alter_member() {

		$club_id = $this->input->post('club_id', true);
		$user_id = $this->input->post('user_id', true);
		$p_level = $this->input->post('permission', true);

		if(!$this->user_model->authorize(99) && (!$this->user_model->authorize(3) || !$this->club_model->club_authorize(9, $club_id))) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		if (!is_numeric($club_id)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard'); 
		}

		$this->club_model->alter_member($club_id, $user_id, $p_level);

		if ($this->input->post('notify_user', true) == 'on') {
			if (!$this->club_model->notify_member($user_id, $club_id, $this->input->post('notify_message', true))) {
				$this->session->set_flashdata('error', __("User could not be notified. Please check your email settings."));
			}
		}
		
		$this->session->set_flashdata('success', __("Club member permissions have been updated."));
		redirect('club/permissions/'.$club_id);
	}

	public function delete_member() {

		$club_id = $this->input->post('club_id', true);
		$user_id = $this->input->post('user_id', true);

		if(!$this->user_model->authorize(99) && (!$this->user_model->authorize(3) || !$this->club_model->club_authorize(9, $club_id))) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		if (!is_numeric($club_id)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard'); 
		}

		if ($this->club_model->delete_member($club_id, $user_id)) {
			$this->session->set_flashdata('success', __("User removed from club."));
		} else {
			$this->session->set_flashdata('error', __("User could not be removed from club."));
		}
		redirect('club/permissions/'.$club_id);
	}

	/**
	 * Set one permission level for several members at once.
	 * Officer(9) or instance admin(99) only.
	 * Endpoint: POST /club/batch_alter_members
	 */
	public function batch_alter_members() {

		if ($this->input->method() !== 'post') {
			$this->session->set_flashdata('error', __("Invalid request method."));
			redirect('dashboard');
		}

		$club_id = $this->input->post('club_id', true);
		$p_level = $this->input->post('permission', true);

		// Batch ops require officer or instance admin - no level 6
		if(!$this->user_model->authorize(99) && (!$this->user_model->authorize(3) || !$this->club_model->club_authorize(9, $club_id))) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		if (!is_numeric($club_id)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard');
		}

		$ids = array_filter(array_map('intval', explode(',', (string) $this->input->post('ids', true))));
		if (empty($ids)) {
			$this->session->set_flashdata('error', __("No members selected."));
			redirect('club/permissions/' . $club_id);
		}

		// Validate target ids against real membership before any write
		$valid = $this->club_model->filter_valid_member_ids($club_id, $ids);
		if (empty($valid)) {
			$this->session->set_flashdata('error', __("None of the selected users are members of this club."));
			redirect('club/permissions/' . $club_id);
		}

		// Last-officer orphan protection: demoting to <9 may leave zero officers.
		// Only an instance admin may create that state.
		if ((int) $p_level < 9
			&& !$this->user_model->authorize(99)
			&& $this->club_model->remaining_officers($club_id, $valid) < 1) {
			$this->session->set_flashdata('error', __("Cannot proceed: this would remove the last Club Officer. An instance administrator must perform this action."));
			redirect('club/permissions/' . $club_id);
		}

		$result = $this->club_model->batch_alter_members($club_id, $valid, $p_level);
		if ($result === false) {
			$this->session->set_flashdata('error', __("Club member permissions could not be updated."));
			redirect('club/permissions/' . $club_id);
		}
		if ($result === 0) {
			$this->session->set_flashdata('error', __("No members were updated."));
			redirect('club/permissions/' . $club_id);
		}

		// Notify after a successful commit - email failure must not roll back DB
		if ($this->input->post('notify_user', true) == 'on') {
			foreach ($valid as $uid) {
				if (!$this->club_model->notify_member($uid, $club_id, 'modified_member')) {
					$this->session->set_flashdata('error', __("User could not be notified. Please check your email settings."));
				}
			}
		}

		$this->session->set_flashdata('success', sprintf(_ngettext("%d member updated.", "%d members updated.", $result), $result));
		redirect('club/permissions/' . $club_id);
	}

	/**
	 * Remove several members from a club at once.
	 * Officer(9) or instance admin(99) only.
	 * Endpoint: POST /club/batch_delete_members
	 */
	public function batch_delete_members() {

		if ($this->input->method() !== 'post') {
			$this->session->set_flashdata('error', __("Invalid request method."));
			redirect('dashboard');
		}

		$club_id = $this->input->post('club_id', true);
		
		// Batch ops require officer or instance admin - no level 6
		if(!$this->user_model->authorize(99) && (!$this->user_model->authorize(3) || !$this->club_model->club_authorize(9, $club_id))) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		if (!is_numeric($club_id)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard');
		}

		$ids = array_filter(array_map('intval', explode(',', (string) $this->input->post('ids', true))));
		if (empty($ids)) {
			$this->session->set_flashdata('error', __("No members selected."));
			redirect('club/permissions/' . $club_id);
		}

		// Validate target ids against real membership before any write
		$valid = $this->club_model->filter_valid_member_ids($club_id, $ids);
		if (empty($valid)) {
			$this->session->set_flashdata('error', __("None of the selected users are members of this club."));
			redirect('club/permissions/' . $club_id);
		}

		// Last-officer orphan protection: deleting may leave zero officers.
		// Only an instance admin may create that state.
		if (!$this->user_model->authorize(99)
			&& $this->club_model->remaining_officers($club_id, $valid) < 1) {
			$this->session->set_flashdata('error', __("Cannot proceed: this would remove the last Club Officer. An instance administrator must perform this action."));
			redirect('club/permissions/' . $club_id);
		}

		$result = $this->club_model->batch_delete_members($club_id, $valid);
		if ($result === false) {
			$this->session->set_flashdata('error', __("Users could not be removed from club."));
			redirect('club/permissions/' . $club_id);
		}
		if ($result === 0) {
			$this->session->set_flashdata('error', __("No members were removed."));
			redirect('club/permissions/' . $club_id);
		}

		$this->session->set_flashdata('success', sprintf(_ngettext("%d member removed.", "%d members removed.", $result), $result));
		redirect('club/permissions/' . $club_id);
	}

	public function switch_modal() {

		$this->load->library('encryption');

		$cid = $this->input->post('club_id', true);
		$data['club_callsign'] = $this->input->post('club_callsign', true);
		$user_id = $this->session->userdata('user_id');

		if(!$this->club_model->club_authorize(3, $cid)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}
		if (!is_numeric($cid)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard'); 
		}

		$data['impersonate_hash'] = $this->encryption->encrypt($user_id . '/' . $cid . '/' . time());

		$this->load->view('club/clubswitch_modal', $data);
	}

}
