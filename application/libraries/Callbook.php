<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Callbook library
	Instead of implementing functions for each callbook, we should call one library, which then calls the correct callbook according to config.
	This makes it easy to implement other callbooks as well.
*/

class Callbook {

	private $ci;

	// Duration of session keys

	// QRZ.com
	// They write that session keys have no guaranteed lifetime. We should cache it to reuse it, but also be prepared
	// to get a new one if the session key is invalid.
	// Older documents showed that the duration was between 12-24 hours. So we set it to 4 hours to be on the safe side.
	// Ref.: https://www.qrz.com/docs/xml/current_spec.html
	const QRZ_SESSION_DURATION = 14400; // 4 hours
	private $qrz_session_cachekey = null;

	// QRZCQ.com
	// I could not find any information about session key duration on their website. Let's cache it for at least 55 minutes.
	// In code we are prepared for an invalid session key, so if the session key is invalid we will get a new one and retry the search.
	// Ref.: https://www.qrzcq.com/docs/api/xml/
	const QRZCQ_SESSION_DURATION = 3300; // 55 minutes
	private $qrzcq_session_cachekey = null;

	// HamQTH.com
	// Session Key is valid for 1 hour according to their documentation. We set it just a few moments below that to 55 Minutes.
	// Ref.: https://www.hamqth.com/developers.php
	const HAMQTH_SESSION_DURATION = 3300; // 55 minutes
	private $hamqth_session_cachekey = null;

	// QRZRU.com
	// Session Key is valid for 1 hour according to their documentation. We set it just a few moments below that to 55 Minutes.
	// Ref.: https://www.qrz.ru/help/api/xml
	const QRZRU_SESSION_DURATION = 3300; // 55 minutes
	private $qrzru_session_cachekey = null;

	// Some generic stuff
	private $logbook_not_configured;
	private $error_obtaining_sessionkey;

	public function __construct() {
		$this->ci = & get_instance();

		$this->ci->load->is_loaded('cache') ?: $this->ci->load->driver('cache', [
			'adapter' => $this->ci->config->item('cache_adapter') ?? 'file',
			'backup' => $this->ci->config->item('cache_backup') ?? 'file',
			'key_prefix' => $this->ci->config->item('cache_key_prefix') ?? ''
		]);

		$this->qrz_session_cachekey = 'qrz_session_key_'.$this->ci->config->item('qrz_username');
		$this->qrzcq_session_cachekey = 'qrzcq_session_key_'.$this->ci->config->item('qrzcq_username');
		$this->hamqth_session_cachekey = 'hamqth_session_key_'.$this->ci->config->item('hamqth_username');
		$this->qrzru_session_cachekey = 'qrzru_session_key_'.$this->ci->config->item('qrzru_username');

		$this->logbook_not_configured = __("Lookup not configured. Please review configuration.");
		$this->error_obtaining_sessionkey = __("Error obtaining a session key for callbook. Error: %s");
	}

	// TODO:
	// Implement the following:
	// - Implement callsign reduced logic
	public function getCallbookData($callsign) {
		// Load callbook configuration from config.php
		$source_callbooks = $this->ci->config->item('callbook');
		$callbook_errors = array();

		// Check if the source callbook is a single element or an array
		if (is_array($source_callbooks)) {
			// Parse each callbook in the array until we get a valid result
			foreach ($source_callbooks as $source) {
				$callbook = $this->queryCallbook($callsign, $source);
				if (!isset($callbook['error']) || $callbook['error'] == '') {
					break;
				} else {
					$callbook_errors['error_'.$source] = $callbook['error'];
					$callbook_errors['error_'.$source.'_name'] = $callbook['source'] ?? '';
				}
			}
		} else {
			// Single callbook lookup (default behavior)
			$callbook = $this->queryCallbook($callsign, $source_callbooks);
		}

		// Handle callbook specific fields
		if (! array_key_exists('geoloc', $callbook)) {
			$callbook['geoloc'] = '';
		}

		// qrz.com gives AA00aa if the user deleted his grid from the profile
		$this->ci->load->library('qra');
		if (!array_key_exists('gridsquare', $callbook) || !$this->ci->qra->validate_grid($callbook['gridsquare'])) {
			$callbook['gridsquare'] = '';
		}

		if (isset($callbook['error']) && $callbook['error'] != '') {
			if (is_array($source_callbooks)) {
				foreach ($source_callbooks as $source) {
					if (isset($callbook_errors['error_'.$source])) {
						$callbook['error_'.$source] = $callbook_errors['error_'.$source];
						$callbook['error_'.$source.'_name'] = $callbook_errors['error_'.$source.'_name'];
					}
				}
			}
		}
		return $callbook;
	}

	function queryCallbook($callsign, $source) {
		switch ($source) {
			case 'qrz':
				$callbook = $this->_qrz($callsign, $this->ci->config->item('use_fullname'));
				break;
			case 'qrzcq':
				$callbook = $this->_qrzcq($callsign);
				break;
			case 'hamqth':
				$callbook = $this->_hamqth($callsign);
				break;
			case 'qrzru':
				$callbook = $this->_qrzru($callsign);
				break;
			default:
				$callbook['error'] = $this->logbook_not_configured;
		}

		log_message('debug', 'Callbook lookup for '.$callsign.' using '.$source.': '.((($callbook['error'] ?? '' ) != '') ? $callbook['error'] : 'Success'));
		return $callbook;
	}

	private function _qrz($callsign, $fullname) {
		$this->ci->load->is_loaded('qrz') ?: $this->ci->load->library('qrz');

		$callbook['source'] = $this->ci->qrz->sourcename();
		$username = trim($this->ci->config->item('qrz_username') ?? '');
		$password = trim($this->ci->config->item('qrz_password') ?? '');

		if ($username == '' || $password == '') {
			$callbook['error'] = $this->logbook_not_configured;
		} else {

			if (!$this->ci->cache->get($this->qrz_session_cachekey)) {
				$qrz_session_key = $this->ci->qrz->session($username, $password);
				if (!$this->_validate_sessionkey($qrz_session_key)) {
					$callbook['error'] = sprintf($this->error_obtaining_sessionkey, $qrz_session_key);
					$this->ci->cache->delete($this->qrz_session_cachekey);
					return $callbook;
				}
				$this->ci->cache->save($this->qrz_session_cachekey, $qrz_session_key, self::QRZ_SESSION_DURATION);
			}

			$callbook = $this->ci->qrz->search($callsign, $this->ci->cache->get($this->qrz_session_cachekey), $fullname);

			if ($callbook['error'] ?? '' == 'Invalid session key') {
				$qrz_session_key = $this->ci->qrz->session($username, $password);
				if (!$this->_validate_sessionkey($qrz_session_key)) {
					$callbook['error'] = sprintf($this->error_obtaining_sessionkey, $qrz_session_key);
					$this->ci->cache->delete($this->qrz_session_cachekey);
					return $callbook;
				}
				$this->ci->cache->save($this->qrz_session_cachekey, $qrz_session_key, self::QRZ_SESSION_DURATION);
				$callbook = $this->ci->qrz->search($callsign, $this->ci->cache->get($this->qrz_session_cachekey), $fullname);
			}

			if (strpos($callbook['error'] ?? '', 'Not found') !== false && strpos($callsign, "/") !== false) {
				$plaincall = $this->get_plaincall($callsign);
				// Now try again but give back reduced data, as we can't validate location and stuff (true at the end)
				$callbook = $this->ci->qrz->search($plaincall, $this->ci->cache->get($this->qrz_session_cachekey), $fullname, true);
			}
		}

		return $callbook;
	}

	private function _qrzcq($callsign) {
		$this->ci->load->is_loaded('qrzcq') ?: $this->ci->load->library('qrzcq');

		$callbook['source'] = $this->ci->qrzcq->sourcename();
		$username = trim($this->ci->config->item('qrzcq_username') ?? '');
		$password = trim($this->ci->config->item('qrzcq_password') ?? '');

		if ($username == '' || $password == '') {
			$callbook['error'] = $this->logbook_not_configured;
		} else {

			if (!$this->ci->cache->get($this->qrzcq_session_cachekey)) {
				$result = $this->ci->qrzcq->session($username, $password);
				if (!$this->_validate_sessionkey($result[1])) {
					$callbook['error'] = sprintf($this->error_obtaining_sessionkey, $result[1]);
					$this->ci->cache->delete($this->qrzcq_session_cachekey);
					return $callbook;
				}
				if ($result[0] == 0) {
					$this->ci->cache->save($this->qrzcq_session_cachekey, $result[1], self::QRZCQ_SESSION_DURATION);
				} else {
					$callbook['error'] = __("QRZCQ Error").": ".$result[1];
					return $callbook;
				}
			}

			$callbook = $this->ci->qrzcq->search($callsign, $this->ci->cache->get($this->qrzcq_session_cachekey));

			if ($callbook['error'] ?? '' == 'Invalid session key') {
				$qrzcq_session_key = $this->ci->qrzcq->session($username, $password);
				if (!$this->_validate_sessionkey($qrzcq_session_key[1])) {
					$callbook['error'] = sprintf($this->error_obtaining_sessionkey, $qrzcq_session_key[1]);
					$this->ci->cache->delete($this->qrzcq_session_cachekey);
					return $callbook;
				}
				$this->ci->cache->save($this->qrzcq_session_cachekey, $qrzcq_session_key[1], self::QRZCQ_SESSION_DURATION);
				$callbook = $this->ci->qrzcq->search($callsign, $this->ci->cache->get($this->qrzcq_session_cachekey));
			}

			if (strpos($callbook['error'] ?? '', 'Not found') !== false && strpos($callsign, "/") !== false) {
				$plaincall = $this->get_plaincall($callsign);
				// Now try again but give back reduced data, as we can't validate location and stuff (true at the end)
				$callbook = $this->ci->qrzcq->search($plaincall, $this->ci->cache->get($this->qrzcq_session_cachekey), true);
			}
		}

		return $callbook;
	}

	private function _hamqth($callsign) {
		$this->ci->load->is_loaded('hamqth') ?: $this->ci->load->library('hamqth');

		$callbook['source'] = $this->ci->hamqth->sourcename();
		$username = trim($this->ci->config->item('hamqth_username') ?? '');
		$password = trim($this->ci->config->item('hamqth_password') ?? '');

		if ($username == '' || $password == '') {
			$callbook['error'] = $this->logbook_not_configured;
		} else {

			if (!$this->ci->cache->get($this->hamqth_session_cachekey)) {
				$hamqth_session_key = $this->ci->hamqth->session($username, $password);
				if (!$this->_validate_sessionkey($hamqth_session_key)) {
					$callbook['error'] = sprintf($this->error_obtaining_sessionkey, $hamqth_session_key);
					$this->ci->cache->delete($this->hamqth_session_cachekey);
					return $callbook;
				} else {
					$this->ci->cache->save($this->hamqth_session_cachekey, $hamqth_session_key, self::HAMQTH_SESSION_DURATION);
				}
			}

			$callbook = $this->ci->hamqth->search($callsign, $this->ci->cache->get($this->hamqth_session_cachekey));

			// If HamQTH session has expired, start a new session and retry the search.
			if ($callbook['error'] == "Session does not exist or expired") {
				$hamqth_session_key = $this->ci->hamqth->session($username, $password);
				if (!$this->_validate_sessionkey($hamqth_session_key)) {
					$callbook['error'] = sprintf($this->error_obtaining_sessionkey, $hamqth_session_key);
					$this->ci->cache->delete($this->hamqth_session_cachekey);
					return $callbook;
				}
				$this->ci->cache->save($this->hamqth_session_cachekey, $hamqth_session_key, self::HAMQTH_SESSION_DURATION);
				$callbook = $this->ci->hamqth->search($callsign, $this->ci->cache->get($this->hamqth_session_cachekey));
			}

			if (strpos($callbook['error'] ?? '', 'Not found') !== false && strpos($callsign, "/") !== false) {
				$plaincall = $this->get_plaincall($callsign);
				// Now try again but give back reduced data, as we can't validate location and stuff (true at the end)
				$callbook = $this->ci->hamqth->search($plaincall, $this->ci->cache->get($this->hamqth_session_cachekey), true);
			}
		}

		return $callbook;
	}

	private function _qrzru($callsign) {
		$this->ci->load->is_loaded('qrzru') ?: $this->ci->load->library('qrzru');

		$callbook['source'] = $this->ci->qrzru->sourcename();
		$username = trim($this->ci->config->item('qrzru_username') ?? '');
		$password = trim($this->ci->config->item('qrzru_password') ?? '');

		if ($username == '' || $password == '') {
			$callbook['error'] = $this->logbook_not_configured;
		} else {

			if (!$this->ci->cache->get($this->qrzru_session_cachekey)) {
				$result = $this->ci->qrzru->session($username, $password);
				if (!$this->_validate_sessionkey($result)) {
					$callbook['error'] = sprintf($this->error_obtaining_sessionkey, $result);
					$this->ci->cache->delete($this->qrzru_session_cachekey);
					return $callbook;
				}
				$this->ci->cache->save($this->qrzru_session_cachekey, $result, self::QRZRU_SESSION_DURATION);
			}

			$callbook = $this->ci->qrzru->search($callsign, $this->ci->cache->get($this->qrzru_session_cachekey));

			if ($callbook['error'] ?? '' == 'Session does not exist or expired') {
				$qrzru_session_key = $this->ci->qrzru->session($username, $password);
				if (!$this->_validate_sessionkey($qrzru_session_key)) {
					$callbook['error'] = sprintf($this->error_obtaining_sessionkey, $qrzru_session_key);
					$this->ci->cache->delete($this->qrzru_session_cachekey);
					return $callbook;
				}
				$this->ci->cache->save($this->qrzru_session_cachekey, $qrzru_session_key, self::QRZRU_SESSION_DURATION);
				$callbook = $this->ci->qrzru->search($callsign, $this->ci->cache->get($this->qrzru_session_cachekey));
			}

			if (strpos($callbook['error'] ?? '', 'Callsign not found') !== false && strpos($callsign, "/") !== false) {
				$plaincall = $this->get_plaincall($callsign);
				// Now try again but give back reduced data, as we can't validate location and stuff (true at the end)
				$callbook = $this->ci->qrzru->search($plaincall, $this->ci->cache->get($this->qrzru_session_cachekey), true);
			}
		}

		return $callbook;
	}

	private function _validate_sessionkey($key) {
		// Session key must be a non-empty string
		if ($key == false || $key == '' || !is_string($key)) {
			return false;
		}

		// All session keys should be at least 10 characters. Regarding to their documentation all keys have aprox. the same format
		// "2331uf894c4bd29f3923f3bacf02c532d7bd9"
		// Since it can differ and we want to don't overcomplicate things we simply check if the key is at least 10 characters long.
		// If not, we consider it as invalid.
		if (strlen($key) < 10) {
			return false;
		}

		return true;
	}

	function get_plaincall($callsign) {
		$split_callsign = explode('/', $callsign);
		if (count($split_callsign) == 1) {				// case of plain callsign --> return callsign
			return $callsign;
		}

		// Case of known suffixes that are not part of the callsign
		if (in_array(strtoupper($split_callsign[1]), array('LGT', 'AM', 'LH', 'A', 'B', 'R', 'T', 'X', 'D', 'P', 'M', 'MM', 'QRP', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'))) {
			return $split_callsign[0];
		}
		// case EA/FOABC --> return 1
		if (strlen($split_callsign[1]) > 3) {	// Last Element longer than 3 chars? Take that as call
			return $split_callsign[1];
		}
		// case F0ABC/KH6 --> return cell 0
		return $split_callsign[0];
	}

}
