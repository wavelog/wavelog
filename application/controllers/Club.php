<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Club extends CI_Controller
{
    public function index()
    {
        // nothing to display
        redirect('dashboard');
    }

    public function permissions($club_id) {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['page_title'] = __("Club Permissions");
		$uid = $this->security->xss_clean($club_id);
		if (!is_numeric($uid)) {
			$this->session->set_flashdata('error', __("Invalid User ID"));
			redirect('user');
		}
		$data['club'] = $this->user_model->get_by_id($uid)->row();

		if ($data['club']->clubstation != 1) {
			$this->session->set_flashdata('error', __("This user is not a club station"));
			redirect('user');
		}

		$this->load->view('interface_assets/header', $data);
		$this->load->view('club/permissions');
		$this->load->view('interface_assets/footer');
	}
}