<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of information for mode tools.
*/

class Usermode extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index()
	{
		$this->load->model('usermodes');
		$data['modes'] = $this->usermodes->all();
		// Render Page
		$data['page_title'] = __("Modes");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('usermode/index');
		$this->load->view('interface_assets/footer');
	}

	public function activate() {
		$id = $this->input->post('id',true);
		$this->load->model('usermodes');
		$this->usermodes->activate($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function deactivate() {
		$id = $this->input->post('id',true);
		$this->load->model('usermodes');
		$this->usermodes->deactivate($id);
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function activateAll() {
		$this->load->model('usermodes');
		$this->usermodes->activateAll();
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function deactivateAll() {
		$this->load->model('usermodes');
		$this->usermodes->deactivateAll();
		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

}
