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
					$callbook['error'] = 'Lookup not configured. Please review configuration.';
					return $callbook;
				}
				return $this->qrz($this->ci->config->item('qrz_username'), $this->ci->config->item('qrz_password'), $callsign, $this->ci->config->item('use_fullname'));
				break;
			case 'qrzcq':
				if ($this->ci->config->item('qrzcq_username') == null || $this->ci->config->item('qrzcq_password') == null) {
					$callbook['error'] = 'Lookup not configured. Please review configuration.';
					return $callbook;
				}
				return $this->qrzcq($this->ci->config->item('qrzcq_username'), $this->ci->config->item('qrzcq_password'), $callsign);
				break;
			case 'hamqth':
				if ($this->ci->config->item('hamqth_username') == null || $this->ci->config->item('hamqth_password') == null) {
					$callbook['error'] = 'Lookup not configured. Please review configuration.';
					return $callbook;
				}
				return $this->hamqth($this->ci->config->item('hamqth_username'), $this->ci->config->item('hamqth_password'), $callsign);
				break;
			default:
				$callbook['error'] = 'No callbook defined. Please review configuration.';
				return $callbook;
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

		if (strpos($callbook['error'] ?? '', 'Not found') !== false && strpos($callsign, "/") !== false) {
			$plaincall = $this->get_plaincall($callsign);
			// Now try again but give back reduced data, as we can't validate location and stuff (true at the end)
			$callbook = $this->ci->qrz->search($plaincall, $this->ci->session->userdata('qrz_session_key'), $fullname, true);
		}

		return $callbook;
	}

	function qrzcq($username, $password, $callsign) {
		if (!$this->ci->load->is_loaded('qrzcq')) {
			$this->ci->load->library('qrzcq');
		}

		if (!$this->ci->session->userdata('qrzcq_session_key')) {
			$result = $this->ci->qrzcq->session($username, $password);
			if ($result[0] == 0) {
				$this->ci->session->set_userdata('qrzcq_session_key', $result[1]);
			} else {
				$data['error'] = __("QRZCQ Error").": ".$result[1];
				return $data;
			}
		}

		$callbook = $this->ci->qrzcq->search($callsign, $this->ci->session->userdata('qrzcq_session_key'));

		if ($callbook['error'] ?? '' == 'Invalid session key') {
			$qrzcq_session_key = $this->ci->qrzcq->session($username, $password);
			$this->ci->session->set_userdata('qrzcq_session_key', $qrzcq_session_key);
			$callbook = $this->ci->qrzcq->search($callsign, $this->ci->session->userdata('qrzcq_session_key'));
		}

		if (strpos($callbook['error'] ?? '', 'Not found') !== false && strpos($callsign, "/") !== false) {
			$plaincall = $this->get_plaincall($callsign);
			// Now try again but give back reduced data, as we can't validate location and stuff (true at the end)
			$callbook = $this->ci->qrzcq->search($plaincall, $this->ci->session->userdata('qrzcq_session_key'), true);
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

		if (strpos($callbook['error'] ?? '', 'Not found') !== false && strpos($callsign, "/") !== false) {
			$plaincall = $this->get_plaincall($callsign);
			// Now try again but give back reduced data, as we can't validate location and stuff (true at the end)
			$callbook = $this->ci->hamqth->search($plaincall, $this->ci->session->userdata('hamqth_session_key'), true);
		}

		return $callbook;
	}

	function get_plaincall($callsign) {
		$split_callsign = explode('/', $callsign);
		if (count($split_callsign) == 1) {				// case F0ABC --> return cel 0 //
			$lookupcall = $split_callsign[0];
		} else if (count($split_callsign) == 3) {			// case EA/F0ABC/P --> return cel 1 //
			$lookupcall = $split_callsign[1];
		} else {										// case F0ABC/P --> return cel 0 OR  case EA/FOABC --> retunr 1  (normaly not exist) //
			if (in_array(strtoupper($split_callsign[1]), array('P', 'M', 'MM', 'QRP', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'))) {
				$lookupcall = $split_callsign[0];
			} else if (strlen($split_callsign[1]) > 3) {	// Last Element longer than 3 chars? Take that as call
				$lookupcall = $split_callsign[1];
			} else {									// Last Element up to 3 Chars? Take first element as Call
				$lookupcall = $split_callsign[0];
			}
		}
		return $lookupcall;
	}
}
