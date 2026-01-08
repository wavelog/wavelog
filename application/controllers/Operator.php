<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

# This is the controller for the operator dialog which can be enabled for special callsign activities
# Check out the config "special_callsign" in your config.php for more information

class Operator extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}


	public function displayOperatorDialog() {

		$this->load->view('operator/index');

	}

	public function saveOperator() {

		$operator = ['operator_callsign' => $this->security->xss_clean(strtoupper($this->input->post('operator_callsign')))];

		$this->session->set_userdata($operator);
	}
}
