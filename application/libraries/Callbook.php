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
	// Implement the following:
	// - Implement callsign reduced logic
	public function getCallbookData($callsign) {
		switch ($this->ci->config->item('callbook')) {
			case 'qrz':
				if ($this->ci->config->item('qrz_username') == null || $this->ci->config->item('qrz_password') == null) {
					return 'Lookup not configured. Please review configuration.';
				}
				return $this->qrz($this->ci->config->item('qrz_username'), $this->ci->config->item('qrz_password'), $callsign, $this->ci->config->item('use_fullname'));
				break;
			case 'qrzcq':
				if ($this->ci->config->item('qrzcq_username') == null || $this->ci->config->item('qrzcq_password') == null) {
					return 'Lookup not configured. Please review configuration.';
				}
				return $this->qrzcq($this->ci->config->item('qrzcq_username'), $this->ci->config->item('qrzcq_password'), $callsign);
				break;
			case 'hamqth':
				if ($this->ci->config->item('hamqth_username') == null || $this->ci->config->item('hamqth_password') == null) {
					return 'Lookup not configured. Please review configuration.';
				}
				return $this->hamqth($this->ci->config->item('hamqth_username'), $this->ci->config->item('hamqth_password'), $callsign);
				break;
		}
	}

	function qrz($username, $password, $callsign, $fullname) {
		if (!$this->ci->load->is_loaded('qrz')) {
			$this->ci->load->library('qrz');
		}

		if (!$this->ci->session->userdata('qrz_session_key')) {
			$qrz_session_key = $this->ci->qrz->session($username, $password);
			$this->ci->session->set_userdata('qrz_session_key', $qrz_session_key);
		}

		$callbook = $this->ci->qrz->search($callsign, $this->ci->session->userdata('qrz_session_key'), $fullname);

		if ($callbook['error'] ?? '' == 'Invalid session key') {
			$qrz_session_key = $this->ci->qrz->session($username, $password);
			$this->ci->session->set_userdata('qrz_session_key', $qrz_session_key);
			$callbook = $this->ci->qrz->search($callsign, $this->ci->session->userdata('qrz_session_key'), $fullname);
		}

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

		if ($callbook['error'] ?? '' == 'Invalid session key') {
			$qrzcq_session_key = $this->ci->qrzcq->session($username, $password);
			$this->ci->session->set_userdata('qrzcq_session_key', $qrzcq_session_key);
			$callbook = $this->ci->qrzcq->search($callsign, $this->ci->session->userdata('qrzcq_session_key'));
		}

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
