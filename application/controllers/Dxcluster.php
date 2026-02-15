<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Wavelog\Dxcc\Dxcc;

require_once APPPATH . '../src/Dxcc/Dxcc.php';

class Dxcluster extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->is_loaded('user_model') ?: $this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->is_loaded('dxcluster_model') ?: $this->load->model('dxcluster_model');
	}


	public function spots($band, $age = '', $de = '', $mode = 'All') {
		// Sanitize inputs
		$band = $this->security->xss_clean($band);
		$mode = $this->security->xss_clean($mode);


		// Only load cache driver if caching is enabled
		if (($this->config->item('enable_dxcluster_file_cache_band') ?? false) || ($this->config->item('enable_dxcluster_file_cache_worked') ?? false)) {
			$this->load->driver('cache', [
				'adapter' => $this->config->item('cache_adapter') ?? 'file', 
				'backup' => $this->config->item('cache_backup') ?? 'file',
				'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
			]);
		}

		if ($age == '') {
			$age = $this->optionslib->get_option('dxcluster_maxage') ?? 60;
		} else {
			$age = (int)$age;
		}

		if ($de == '') {
			$de = $this->optionslib->get_option('dxcluster_decont') ?? 'EU';
		} else {
			$de = $this->security->xss_clean($de);
		}
		$calls_found = $this->dxcluster_model->dxc_spotlist($band, $age, $de, $mode);

		header('Content-Type: application/json');
		http_response_code(200);
		if ($calls_found && !empty($calls_found)) {
			echo json_encode($calls_found, JSON_PRETTY_PRINT);
		} else {
			echo json_encode([], JSON_PRETTY_PRINT);  // "error: not found" would be misleading here. No spots are not an error. Therefore we return an empty array
		}
	}

	public function qrg_lookup($qrg) {
		$call_found = $this->dxcluster_model->dxc_qrg_lookup($this->security->xss_clean($qrg));
		header('Content-Type: application/json');
		http_response_code(200);
		if ($call_found) {
			echo json_encode($call_found, JSON_PRETTY_PRINT);
		} else {
			echo json_encode([], JSON_PRETTY_PRINT); // "error: not found" would be misleading here. No call is not an error, the call is just not in the spotlist. Therefore we return an empty array
		}
	}

	// TODO: Is this used anywhere? If not, remove it!
	public function call($call) {
		$date = date('Y-m-d', time());
		$dxccobj = new Dxcc($date);

		$dxcc = $dxccobj->dxcc_lookup($call, $date);

		header('Content-Type: application/json');
		http_response_code(200);
		if ($dxcc) {
			echo json_encode($dxcc, JSON_PRETTY_PRINT);
		} else {
			echo json_encode(['error' => 'not found'], JSON_PRETTY_PRINT);
		}
	}

	public function spot() {
		// Only allow POST
		if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
			show_404();
			return;
		}

		// Basic auth already handled in constructor
		$this->load->library('optionslib');
		$this->load->library('Dxcluster_spotter');

		// Accept both form-encoded and JSON payloads
		$payload = $this->input->post(NULL, TRUE);
		$raw = file_get_contents('php://input');
		if (is_string($raw) && strlen($raw) > 0 && ($raw[0] === '{' || $raw[0] === '[')) {
			$json = json_decode($raw, true);
			if (is_array($json)) {
				$payload = array_merge($payload ?: [], $json);
			}
		}

		$dxcall = strtoupper(trim($payload['dxcall'] ?? $payload['callsign'] ?? ''));
		$comment = trim($payload['comment'] ?? $payload['info'] ?? '');
		$mode = trim($payload['mode'] ?? '');

		// Frequency: accept Hz or kHz
		$freq_khz = null;
		if (isset($payload['freq_khz'])) {
			$freq_khz = floatval($payload['freq_khz']);
		} elseif (isset($payload['frequency'])) {
			$f = floatval($payload['frequency']);
			$freq_khz = ($f > 100000) ? ($f / 1000.0) : $f;
		} elseif (isset($payload['freq'])) {
			$f = floatval($payload['freq']);
			$freq_khz = ($f > 100000) ? ($f / 1000.0) : $f;
		}

		// Validate
		if ($dxcall === '' || !preg_match('/^[A-Z0-9\\/]+$/', $dxcall)) {
			$this->_spot_json(false, 'Invalid DX callsign.');
			return;
		}
		if ($freq_khz === null || $freq_khz <= 0) {
			$this->_spot_json(false, 'Missing or invalid frequency.');
			return;
		}

		$spotter_call = strtoupper(trim($this->session->userdata('operator_callsign') ?: $this->session->userdata('user_callsign') ?: ''));
		if ($spotter_call === '' || !preg_match('/^[A-Z0-9\\/]+$/', $spotter_call)) {
			$this->_spot_json(false, 'Your user callsign is not set. Please set it in your profile.');
			return;
		}

		// Load outbound config
		$enabled = ($this->optionslib->get_option('dxcluster_out_enabled') ?? '0') === '1';
		if (!$enabled) {
			$this->_spot_json(false, 'Outbound spotting is disabled in Options → DXCluster.');
			return;
		}

		$host = trim((string)($this->optionslib->get_option('dxcluster_out_host') ?? ''));
		$port = intval($this->optionslib->get_option('dxcluster_out_port') ?? 0);
		$timeout = intval($this->optionslib->get_option('dxcluster_out_timeout') ?? 5);
		$password = (string)($this->optionslib->get_option('dxcluster_out_password') ?? '');

		if ($host === '' || $port < 1 || $port > 65535) {
			$this->_spot_json(false, 'DX Cluster host/port not configured in Options → DXCluster.');
			return;
		}
		if ($timeout < 2) $timeout = 2;
		if ($timeout > 30) $timeout = 30;

		// Rate limit: 1 spot / 60s per session
		$last = intval($this->session->userdata('dxcluster_last_spot_ts') ?? 0);
		if ($last > 0 && (time() - $last) < 60) {
			$this->_spot_json(false, 'Please wait a moment before sending another spot.');
			return;
		}

		// Default comment
		if ($comment === '' && $mode !== '') {
			$comment = $mode;
		}
		if ($comment !== '') {
			$comment = preg_replace('/[\\r\\n]+/', ' ', $comment);
			$comment = substr($comment, 0, 60);
		}

		$err = '';
		$ok = $this->dxcluster_spotter->send_spot($host, $port, $spotter_call, $dxcall, $freq_khz, $comment, $timeout, $password, $err);

		if ($ok) {
			$this->session->set_userdata('dxcluster_last_spot_ts', time());
			$this->_spot_json(true, 'Spot sent.');
		} else {
			$this->_spot_json(false, $err !== '' ? $err : 'Failed to send spot.');
		}
	}

	private function _spot_json($ok, $message) {
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode([
				'ok' => (bool)$ok,
				'message' => (string)$message,
			]));
	}

}
