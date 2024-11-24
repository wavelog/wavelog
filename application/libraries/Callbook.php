<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Callbook library
	Instead of implementing functions for each callbook, we should call one library, which then calls the correct callbook according to config.
	This makes it easy to implement other callbooks as well.
*/

class Callbook {

	private $ci;

	public function __construct() {
		$this->ci = & get_instance();
	}

	// TODO:
	//
	// Implement the following:
	// - Check that username/password is set
	// - Check for use_fullname
	// - Implement that reduced logic
	public function getCallbookData($callsign) {
		switch ($this->ci->config->item('callbook')) {
			case 'qrz': return $this->qrz($this->ci->config->item('qrz_username'), $this->ci->config->item('qrz_password'), $callsign);
				break;
			case 'qrzcq': return $this->qrzcq($this->ci->config->item('qrzcq_username'), $this->ci->config->item('qrzcq_password'), $callsign);
				break;
			case 'hamqth': return $this->hamqth($this->ci->config->item('hamqth_username'), $this->ci->config->item('hamqth_password'), $callsign);
				break;
		}
	}

	function qrz($username, $password, $callsign) {
		if (!$this->ci->load->is_loaded('qrz')) {
			$this->ci->load->library('qrz');
		}

		if (!$this->ci->session->userdata('qrz_session_key')) {
			$qrz_session_key = $this->ci->qrz->session($username, $password);
			$this->ci->session->set_userdata('qrz_session_key', $qrz_session_key);
		}

		$callbook = $this->ci->qrz->search($callsign, $this->ci->session->userdata('qrz_session_key'));

		return $callbook;
	}

	function qrzcq($username, $password, $callsign) {
		if (!$this->ci->load->is_loaded('qrzcq')) {
			$this->ci->load->library('qrzcq');
		}

		if (!$this->ci->session->userdata('qrzcq_session_key')) {
			$qrzcq_session_key = $this->ci->qrzcq->session($username, $password);
			$this->ci->session->set_userdata('qrzcq_session_key', $qrzcq_session_key);
		}

		$callbook = $this->ci->qrzcq->search($callsign, $this->ci->session->userdata('qrzcq_session_key'));

		return $callbook;
	}

	function hamqth($username, $password, $callsign) {
		// Load the HamQTH library
		if (!$this->ci->load->is_loaded('hamqth')) {
			$this->ci->load->library('hamqth');
		}

		if (!$this->ci->session->userdata('hamqth_session_key')) {
			$hamqth_session_key = $this->ci->hamqth->session($username, $password);
			$this->ci->session->set_userdata('hamqth_session_key', $hamqth_session_key);
		}

		$callbook = $this->ci->hamqth->search($callsign, $this->ci->session->userdata('hamqth_session_key'));

		// If HamQTH session has expired, start a new session and retry the search.
		if ($callbook['error'] == "Session does not exist or expired") {
			$hamqth_session_key = $this->ci->hamqth->session($username, $password);
			$this->ci->session->set_userdata('hamqth_session_key', $hamqth_session_key);
			$callbook = $this->ci->hamqth->search($callsign, $this->ci->session->userdata('hamqth_session_key'));
		}

		return $callbook;
	}
}

// This is the one that needs to be implemented above:
// if ($this->config->item('callbook') == "qrz" && $this->config->item('qrz_username') != null && $this->config->item('qrz_password') != null) {
			// 	// Lookup using QRZ
			// 	$this->load->library('qrz');

			// 	if (!$this->session->userdata('qrz_session_key')) {
			// 		$qrz_session_key = $this->qrz->session($this->config->item('qrz_username'), $this->config->item('qrz_password'));
			// 		$this->session->set_userdata('qrz_session_key', $qrz_session_key);
			// 	}

			// 	$callbook = $this->qrz->search($callsign, $this->session->userdata('qrz_session_key'), $use_fullname);

			// 	// We need to handle, if the sessionkey is invalid
			// 	if ($callbook['error'] ?? '' == 'Invalid session key') {
			// 		$this->qrz->set_session($this->config->item('qrz_username'), $this->config->item('qrz_password'));
			// 		$callbook = $this->qrz->search($callsign, $this->session->userdata('qrz_session_key'), $use_fullname);
			// 	}

			// 	// If the callsign contains a slash we have a pre- or suffix. If then the result is "Not found" we can try again with the plain call
			// 	if (strpos($callbook['error'] ?? '', 'Not found') !== false && strpos($callsign, "/") !== false) {
			// 		$plaincall = $this->get_plaincall($callsign);
			// 		// Now try again but give back reduced data, as we can't validate location and stuff (true at the end)
			// 		$callbook = $this->qrz->search($plaincall, $this->session->userdata('qrz_session_key'), $use_fullname, true);
			// 	}
			// }

			// if ($this->config->item('callbook') == "hamqth" && $this->config->item('hamqth_username') != null && $this->config->item('hamqth_password') != null) {
			// 	// Load the HamQTH library
			// 	$this->load->library('hamqth');

			// 	if (!$this->session->userdata('hamqth_session_key')) {
			// 		$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
			// 		$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
			// 	}

			// 	// if the callsign contains a pre- or suffix we only give back reduced data to avoid wrong data (location and other things are not valid then)
			// 	if (strpos($callsign, "/") !== false) {
			// 		$reduced = true;
			// 	} else {
			// 		$reduced = false;
			// 	}
			// 	$callbook = $this->hamqth->search($callsign, $this->session->userdata('hamqth_session_key'), $reduced);

			// 	// If HamQTH session has expired, start a new session and retry the search.
			// 	if ($callbook['error'] == "Session does not exist or expired") {
			// 		$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
			// 		$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
			// 		$callbook = $this->hamqth->search($callsign, $this->session->userdata('hamqth_session_key'));
			// 	}
			// }

// this is from grid, but ignore I think
	// if ($this->config->item('callbook') == "qrz" && $this->config->item('qrz_username') != null && $this->config->item('qrz_password') != null) {
				// 	// Lookup using QRZ
				// 	if (!$this->load->is_loaded('qrz')) {
				// 		$this->load->library('qrz');
				// 	}

				// 	if (!$this->session->userdata('qrz_session_key')) {
				// 		$qrz_session_key = $this->qrz->session($this->config->item('qrz_username'), $this->config->item('qrz_password'));
				// 		$this->session->set_userdata('qrz_session_key', $qrz_session_key);
				// 	}

				// 	$callbook = $this->qrz->search($callsign, $this->session->userdata('qrz_session_key'));
				// }

				// if ($this->config->item('callbook') == "hamqth" && $this->config->item('hamqth_username') != null && $this->config->item('hamqth_password') != null) {
				// 	// Load the HamQTH library
				// 	if (!$this->load->is_loaded('hamqth')) {
				// 		$this->load->library('hamqth');
				// 	}

				// 	if (!$this->session->userdata('hamqth_session_key')) {
				// 		$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
				// 		$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
				// 	}

				// 	$callbook = $this->hamqth->search($callsign, $this->session->userdata('hamqth_session_key'));

				// 	// If HamQTH session has expired, start a new session and retry the search.
				// 	if ($callbook['error'] == "Session does not exist or expired") {
				// 		$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
				// 		$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
				// 		$callbook = $this->hamqth->search($callsign, $this->session->userdata('hamqth_session_key'));
				// 	}
				// }


				// This is from logbook.php, probably ignore:
							// if ($this->config->item('callbook') == "qrz" && $this->config->item('qrz_username') != null && $this->config->item('qrz_password') != null) {
				// 	// Lookup using QRZ
				// 	$this->load->library('qrz');

				// 	if(!$this->session->userdata('qrz_session_key')) {
				// 		$qrz_session_key = $this->qrz->session($this->config->item('qrz_username'), $this->config->item('qrz_password'));
				// 		$this->session->set_userdata('qrz_session_key', $qrz_session_key);
				// 	}
				// 	$callsign['callsign'] = $this->qrz->search($id, $this->session->userdata('qrz_session_key'), $this->config->item('use_fullname'));

				// 	if (empty($callsign['callsign']['callsign'])) {
				// 		$qrz_session_key = $this->qrz->session($this->config->item('qrz_username'), $this->config->item('qrz_password'));
				// 		$this->session->set_userdata('qrz_session_key', $qrz_session_key);
				// 		$callsign['callsign'] = $this->qrz->search($id, $this->session->userdata('qrz_session_key'), $this->config->item('use_fullname'));
				// 	}
				// 	if (isset($callsign['callsign']['dxcc'])) {
				// 		$this->load->model('logbook_model');
				// 		$entity = $this->logbook_model->get_entity($callsign['callsign']['dxcc']);
				// 		$callsign['callsign']['dxcc_name'] = $entity['name'];
				// 		$callsign['dxcc_worked'] = $this->logbook_model->check_if_dxcc_worked_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
				// 		$callsign['dxcc_confirmed'] = $this->logbook_model->check_if_dxcc_cnfmd_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
				// 	}
				// } else if ($this->config->item('callbook') == "qrzcq" && $this->config->item('qrzcq_username') != null && $this->config->item('qrzcq_password') != null) {
				// 	// Lookup using QRZCQ
				// 	$this->load->library('qrzcq');

				// 	if(!$this->session->userdata('qrzcq_session_key')) {
				// 		$qrzcq_session_key = $this->qrzcq->session($this->config->item('qrzcq_username'), $this->config->item('qrzcq_password'));
				// 		$this->session->set_userdata('qrzcq_session_key', $qrzcq_session_key);
				// 	}
				// 	$callsign['callsign'] = $this->qrzcq->search($id, $this->session->userdata('qrzcq_session_key'), $this->config->item('use_fullname'));

				// 	if (empty($callsign['callsign']['callsign'])) {
				// 		$qrzcq_session_key = $this->qrzcq->session($this->config->item('qrz_username'), $this->config->item('qrzcq_password'));
				// 		$this->session->set_userdata('qrz_session_key', $qrzcq_session_key);
				// 		$callsign['callsign'] = $this->qrzcq->search($id, $this->session->userdata('qrzcq_session_key'), $this->config->item('use_fullname'));
				// 	}
				// 	if (isset($callsign['callsign']['dxcc'])) {
				// 		$this->load->model('logbook_model');
				// 		$entity = $this->logbook_model->get_entity($callsign['callsign']['dxcc']);
				// 		$callsign['callsign']['dxcc_name'] = $entity['name'];
				// 		$callsign['dxcc_worked'] = $this->logbook_model->check_if_dxcc_worked_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
				// 		$callsign['dxcc_confirmed'] = $this->logbook_model->check_if_dxcc_cnfmd_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
				// 	}
				// } else if ($this->config->item('callbook') == "hamqth" && $this->config->item('hamqth_username') != null && $this->config->item('hamqth_password') != null) {
				// 	// Load the HamQTH library
				// 	$this->load->library('hamqth');

				// 	if(!$this->session->userdata('hamqth_session_key')) {
				// 		$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
				// 		$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
				// 	}

				// 	$callsign['callsign'] = $this->hamqth->search($id, $this->session->userdata('hamqth_session_key'));

				// 	// If HamQTH session has expired, start a new session and retry the search.
				// 	if($callsign['callsign']['error'] == "Session does not exist or expired") {
				// 		$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
				// 		$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
				// 		$callsign['callsign'] = $this->hamqth->search($id, $this->session->userdata('hamqth_session_key'));
				// 	}
				// 	if (isset($data['callsign']['gridsquare'])) {
				// 		$this->load->model('logbook_model');
				// 		$callsign['grid_worked'] = $this->logbook_model->check_if_grid_worked_in_logbook(strtoupper(substr($data['callsign']['gridsquare'],0,4)), null, $this->session->userdata('user_default_band'))->num_rows();
				// 	}
				// 	if (isset($callsign['callsign']['dxcc'])) {
				// 		$this->load->model('logbook_model');
				// 		$entity = $this->logbook_model->get_entity($callsign['callsign']['dxcc']);
				// 		$callsign['callsign']['dxcc_name'] = $entity['name'];
				// 		$callsign['dxcc_worked'] = $this->logbook_model->check_if_dxcc_worked_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
				// 		$callsign['dxcc_confirmed'] = $this->logbook_model->check_if_dxcc_cnfmd_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
				// 	}
				// 	if (isset($callsign['callsign']['error'])) {
				// 		$callsign['error'] = $callsign['callsign']['error'];
				// 	}
				// } else {
				// 	$callsign['error'] = 'Lookup not configured. Please review configuration.';
				// }

				// There's no hamli integration? Disabled for now.
				/*else {
					// Lookup using hamli
					$this->load->library('hamli');

					$callsign['callsign'] = $this->hamli->callsign($id);
				}*/
