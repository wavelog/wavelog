<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Achievements extends CI_Controller {

	function __construct() {
		parent::__construct();

		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$this->load->model('achievements_model');

		$confirmed_mode = $this->input->get('confirmed', true) === '1';
		$data['trophies'] = $this->achievements_model->get_trophies($confirmed_mode);
		$data['confirmed_mode'] = $confirmed_mode;
		$data['page_title'] = __("Achievements");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('achievements/index');
		$this->load->view('interface_assets/footer');
	}
}
