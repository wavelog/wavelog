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

		$callsign = strtoupper(trim($this->input->post('operator_callsign', TRUE)));

		if (!$this->isValidCallsign($callsign)) {
			$this->output->set_status_header(400);
			$this->output->set_content_type('application/json')->set_output(json_encode(['error' => __('Invalid callsign')]));
			return;
		}

		$operator = ['operator_callsign' => $callsign];

		$this->session->set_userdata($operator);
	}

	# Minimal sanity check that the input looks like an amateur radio callsign.
	# Mirrors the client-side check: >=3 chars, only A-Z/0-9 (no "/" suffix or
	# wildcards), at least one letter and one digit.
	private function isValidCallsign($callsign) {
		if ($callsign === '' || strlen($callsign) < 3) return false;
		if (!preg_match('/^[A-Z0-9]+$/', $callsign)) return false;	// letters and digits only
		if (!preg_match('/[A-Z]/', $callsign)) return false;		// at least one letter
		if (!preg_match('/[0-9]/', $callsign)) return false;		// at least one digit
		// A bare 3-char string ending in a digit is a prefix, not a call (e.g. "ZL3")
		if (strlen($callsign) === 3 && !preg_match('/[A-Z]$/', $callsign)) return false;
		return true;
	}
}
