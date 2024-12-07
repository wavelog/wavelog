<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Club extends CI_Controller
{
	/** 
	 * Permission Levels:
	 * 9 - Officer
	 * 3 - Member
	 * 
	 * These permission levels are independent of the codeigniter permission levels and managed in the club_permissions table!
	 * // TODO: Add a Wiki Link to the Permission Levels and Explainations about the Club Features
	*/

	/**
     * @var array $permissions
     */
    private $permissions = [
        9 => "Officer",
        3 => "Member",
    ];

    public function index()
    {
        // nothing to display
        redirect('dashboard');
    }

    public function permissions($club_id) {

		$this->load->model('user_model');
		$this->load->model('club_model');
		$this->load->library('form_validation');

		$cid = $this->security->xss_clean($club_id);
		$club = $this->user_model->get_by_id($cid)->row();

		if (!is_numeric($cid)) {
			$this->session->set_flashdata('error', __("Invalid User ID!"));
			redirect('user');
		}
		if(!$this->user_model->authorize(99) && !$this->club_model->club_authorize(9, $cid)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}
		if ($club->clubstation != 1) {
			$this->session->set_flashdata('error', __("This user is not a club station."));
			redirect('user');
		}

		$data['page_title'] = __("Club Permissions");
		$data['club'] = $club;
		$data['club_members'] = $this->club_model->get_club_members($cid);
		$data['users'] = $this->user_model->users();
		$data['permissions'] = $this->permissions;

		$this->load->view('interface_assets/header', $data);
		$this->load->view('club/permissions');
		$this->load->view('interface_assets/footer');
	}

	public function alter_member() {
		
		$this->load->model('user_model');
		$this->load->model('club_model');

		$club_id = $this->input->post('club_id', true);
		$user_id = $this->input->post('user_id', true);
		$p_level = $this->input->post('permission', true);

		if (!is_numeric($club_id)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard'); 
		}
		if(!$this->user_model->authorize(99) && !$this->club_model->club_authorize(9, $club_id)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}

		$this->club_model->alter_member($club_id, $user_id, $p_level);
		$this->session->set_flashdata('message', __("User added to club."));
		redirect('club/permissions/'.$club_id);
	}

	public function delete_member() {
		
		$this->load->model('user_model');
		$this->load->model('club_model');

		$club_id = $this->input->post('club_id', true);
		$user_id = $this->input->post('user_id', true);

		if (!is_numeric($club_id)) {
			$this->session->set_flashdata('error', __("Invalid Club ID!"));
			redirect('dashboard'); 
		}
		if(!$this->user_model->authorize(99) && !$this->club_model->club_authorize(9, $club_id)) { 
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); 
			redirect('dashboard'); 
		}

		$this->club_model->delete_member($club_id, $user_id);
		$this->session->set_flashdata('message', __("User removed from club."));
		redirect('club/permissions/'.$club_id);
	}
	
}