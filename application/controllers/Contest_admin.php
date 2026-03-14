<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	This controller contains features for contesting
*/

class Contest_admin extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function create() {
		$this->load->model('Contest_admin_model');
		$this->load->library('form_validation');

		$this->form_validation->set_rules('name', 'Contest Name', 'required');
		$this->form_validation->set_rules('adifname', 'Adif Contest Name', 'required');

		if ($this->form_validation->run() == FALSE) {
			$data['page_title'] = "Create Mode";
			$this->load->view('contest_admin/create', $data);
		} else {
			$this->Contest_admin_model->add();
		}
	}

	public function add() {
		$this->load->model('Contest_admin_model');

		$data['contests'] = $this->Contest_admin_model->getAllContests();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/contest_admin.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/contest_admin.js")),
		];

		// Render Page
		$data['page_title'] = __("Contests Administration");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('contest_admin/add');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function edit($id) {
		$this->load->model('Contest_admin_model');
		$this->load->library('form_validation');

		$item_id_clean = $this->security->xss_clean($id);

		$data['contest'] = $this->Contest_admin_model->contest($item_id_clean);

		$data['page_title'] = __("Update Contest");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/contest_admin.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/contest_admin.js")),
		];

		$this->form_validation->set_rules('name', 'Contest Name', 'required');
		$this->form_validation->set_rules('adifname', 'Adif Contest Name', 'required');

		if ($this->form_validation->run() == FALSE)
		{
			$this->load->view('interface_assets/header', $data);
			$this->load->view('contest_admin/edit');
			$this->load->view('interface_assets/footer', $footerData);
		}
		else
		{
			$this->Contest_admin_model->edit($item_id_clean);

			$data['notice'] = "Contest ".$this->security->xss_clean($this->input->post('name', true))." Updated";

			redirect('contest_admin/add');
		}
	}

	public function delete() {
		$id = $this->input->post('id', true);
		$this->load->model('Contest_admin_model');
		$this->Contest_admin_model->delete($id);
	}

	public function activate() {
		$id = $this->input->post('id', true);
		$this->load->model('Contest_admin_model');
		$this->Contest_admin_model->activate($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function deactivate() {
		$id = $this->input->post('id', true);
		$this->load->model('Contest_admin_model');
		$this->Contest_admin_model->deactivate($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function deactivateall() {
		$this->load->model('Contest_admin_model');
		$this->Contest_admin_model->deactivateall();
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function activateall() {
		$this->load->model('Contest_admin_model');
		$this->Contest_admin_model->activateall();
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

}
