<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller for QSL Cards
*/

class Generic_qsl extends CI_Controller {

	function __construct() {
		parent::__construct();
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	// View for filtering and showing confirmations on LoTW/QSL/eQSL/QRZ/HRDLog/Clublog
	public function confirmations() {
		// Render Page
		$data['page_title'] = __("Confirmations");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/bootstrap-multiselect.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/bootstrap-multiselect.js")),
			'assets/js/sections/qsl.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/qsl.js")),
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('qslcard/confirmations');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function searchConfirmations() {
		$this->load->model('qsl_model');
		$confirmationtype = xss_clean($this->input->post('type'));
		$data['result'] = $this->qsl_model->getConfirmations($confirmationtype);
		$this->load->view('qslcard/confirmationresult', $data);
	}

}
