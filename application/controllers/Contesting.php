<?php
if (! defined('BASEPATH')) exit('No direct script access allowed');

/*
	This controller contains features for contesting.
	It replaces the old contesting which had no club compatibility.
*/

use Wavelog\Dxcc\Dxcc;

require_once APPPATH . '../src/Dxcc/Dxcc.php';

class Contesting extends CI_Controller {
	/**
	 * Holds QSOs created during the current sync cycle
	 */
	private $new_qsos = [];

	/**
	 * Worker availability
	 */
	private $worker_available = false;

	/**
	 * Active Station Location
	 */
	private $active_station_location = null;

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		if ($this->session->userdata('station_profile_id') ?? 0) {	// Last Station from session accessible? Take it!
			$this->active_station_location = $this->session->userdata('station_profile_id');
		} else {
			$this->active_station_location = $this->stations->find_active();
		}

		$this->load->is_loaded('Worker') ?: $this->load->library('Worker');
		$this->worker_available = $this->worker->is_enabled();
	}

	/**
	 * Contest Management Dashboard
	 */
	public function index() {
		$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');
		$this->load->is_loaded('form_validation') ?: $this->load->library('form_validation');

		$data['page_title'] = __("Contest Management");
		$data['my_contests'] = $this->contesting_model->get_user_contests();

		if ($this->session->userdata('user_date_format')) {
			$data['custom_date_format'] = $this->session->userdata('user_date_format');
		} else {
			$data['custom_date_format'] = $this->config->item('qso_date_format');
		}

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/contesting/manager.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('contesting/manager/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function quickstart() {
		if (!clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("Officers must set up contests."));
			redirect('contesting'); 
		}

		$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');
		$this->load->is_loaded('stations') ?: $this->load->model('stations');

		if ($this->session->userdata('user_date_format')) {
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$contest_adif_id = 1; // Contest "Other"
		$session_start = date('Y-m-d H:i');
		$session_end = date('Y-m-d H:i', strtotime('+48 hours')); // 48 hours from now
		$station_location = $this->active_station_location;
		$session_notes = sprintf(__("Quickstart Session: %s"), date($custom_date_format . ' H:i'));

		$session_id = $this->contesting_model->create_contest_session($contest_adif_id, $session_start, $session_end, $station_location, $session_notes, true);

		$logging_token = $this->paths->create_contesting_logging_token($session_id);

		redirect('contesting/logging_engine/' . $logging_token);
	}

	/**
	 * Export Page for contests
	 */
	public function export($contest_session_id) {
		$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');

		if (!$this->contesting_model->check_user_contest($contest_session_id)) {
			$this->session->set_flashdata('error', __("Contest session not found."));
			redirect('contesting');
		}

		$this->load->is_loaded('user_model') ?: $this->load->model('user_model');

		$session_info = $this->contesting_model->get_session_info($contest_session_id);
		$cabrillo     = $this->contesting_model->get_cabrillo_settings($contest_session_id);
		$userinfo     = $this->user_model->get_by_id($this->session->userdata('user_id'))->row();

		$session_operators = $this->contesting_model->get_session_operators($contest_session_id);

		$data['page_title']         = sprintf(__("Export: %s"), $session_info['contest_name']);
		$data['session_info']       = $session_info;
		$data['cabrillo']           = $cabrillo;
		$data['qso_count']          = $this->contesting_model->get_session_qso_count($contest_session_id);
		$data['user_name']          = trim($userinfo->user_firstname . ' ' . $userinfo->user_lastname);
		$data['user_email']         = $userinfo->user_email;
		$data['session_operators']  = $session_operators;
		$data['contest_session_id'] = $contest_session_id;

		$this->load->view('interface_assets/header', $data);
		$this->load->view('contesting/manager/export');
		$this->load->view('interface_assets/footer');
	}

	/**
	 * ADIF export for a specific contest session.
	 * POST /contesting/export_adif/<id>
	 */
	public function export_adif($contest_session_id) {
		$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');

		if (!$this->contesting_model->check_user_contest($contest_session_id) || !clubaccess_check(6)) {
			show_404();
		}

		$session_info = $this->contesting_model->get_session_info($contest_session_id);
		$qsos         = $this->contesting_model->get_session_qsos_for_adif($contest_session_id);

		$callsign = strtoupper(str_replace('/', '-', $session_info['station_callsign'] ?? 'STATION'));
		$contest  = strtoupper($session_info['contest_adifname'] ?? 'CONTEST');
		$filename = $callsign . '-' . $contest . '-' . date('Ymd') . '.adi';

		$this->load->library('AdifHelper');

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		echo $this->adifhelper->getAdifHeader(
			$this->config->item('app_name'),
			$this->optionslib->get_option('version'),
			$this->optionslib->get_option('adif_version')
		);

		foreach ($qsos->result() as $qso) {
			echo $this->adifhelper->getAdifLine($qso);
		}
	}

	/**
	 * Cabrillo export for a specific contest session.
	 * Saves Cabrillo category settings back to the session before streaming the file.
	 * POST /contesting/export_cabrillo/<id>
	 */
	public function export_cabrillo($contest_session_id) {
		$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');

		if (!$this->contesting_model->check_user_contest($contest_session_id) || !clubaccess_check(6)) {
			show_404();
		}

		$cabrillo = [
			'category_operator'    => $this->input->post('categoryoperator', true)    ?? 'SINGLE-OP',
			'category_assisted'    => $this->input->post('categoryassisted', true)    ?? 'NON-ASSISTED',
			'category_band'        => $this->input->post('categoryband', true)        ?? 'ALL',
			'category_mode'        => $this->input->post('categorymode', true)        ?? 'MIXED',
			'category_power'       => $this->input->post('categorypower', true)       ?? 'LOW',
			'category_station'     => $this->input->post('categorystation', true)     ?? 'FIXED',
			'category_transmitter' => $this->input->post('categorytransmitter', true) ?? 'ONE',
			'category_time'        => $this->input->post('categorytime', true)        ?? '',
			'category_overlay'     => $this->input->post('categoryoverlay', true)     ?? '',
			'club'                 => $this->input->post('club', true)                ?? '',
			'location'             => $this->input->post('location', true)            ?? '',
			'operators'            => $this->input->post('operators', true)           ?? '',
			'name'                 => $this->input->post('cbr_name', true)            ?? '',
			'email'                => $this->input->post('cbr_email', true)           ?? '',
			'address'              => $this->input->post('address', true)             ?? '',
			'addresscity'          => $this->input->post('addresscity', true)         ?? '',
			'addressprovince'      => $this->input->post('addressprovince', true)     ?? '',
			'addresspostalcode'    => $this->input->post('addresspostalcode', true)   ?? '',
			'addresscountry'       => $this->input->post('addresscountry', true)      ?? '',
			'soapbox'              => $this->input->post('soapbox', true)             ?? '',
			'certificate'          => $this->input->post('certificate', true)         ?? '',
			'grid_export'          => $this->input->post('grid_export', true)         ?? '0',
			'grid_precision'       => $this->input->post('grid_precision', true) === '6' ? '6' : '4',
		];

		$this->contesting_model->save_cabrillo_settings($contest_session_id, $cabrillo);

		$session_info = $this->contesting_model->get_session_info($contest_session_id);
		$qsos         = $this->contesting_model->get_session_qsos_for_cabrillo($contest_session_id);

		$this->load->is_loaded('user_model') ?: $this->load->model('user_model');
		$userinfo = $this->user_model->get_by_id($this->session->userdata('user_id'))->row();

		$contest_id = $session_info['contest_adifname'];
		$callsign   = strtoupper(str_replace('/', '-', $session_info['station_callsign'] ?? 'STATION'));
		$filename   = $callsign . '-' . $contest_id . '-' . date('Ymd-Hi') . '.cbr';

		$this->load->library('Cabrilloformat');

		header('Content-Type: text/plain; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');

		$grid_export = ($cabrillo['grid_export'] === '1');
		$grid_chars  = (int)($cabrillo['grid_precision'] ?? 4);

		$cbr_name  = $cabrillo['name']  ?: trim($userinfo->user_firstname . ' ' . $userinfo->user_lastname);
		$cbr_email = $cabrillo['email'] ?: $userinfo->user_email;

		echo $this->cabrilloformat->header(
			$contest_id,
			$callsign,
			null,
			$cabrillo['operators'] ?: $callsign,
			$cabrillo['club']      ?: null,
			$cabrillo['location']  ?: null,
			$cbr_name,
			$cabrillo['address']           ?? '',
			$cabrillo['addresscity']       ?? '',
			$cabrillo['addressprovince']   ?? '',
			$cabrillo['addresspostalcode'] ?? '',
			$cabrillo['addresscountry']    ?? '',
			$cabrillo['soapbox']  ?: '',
			$session_info['station_gridsquare'] ?: null,
			$cabrillo['category_overlay']     ?: '',
			$cabrillo['category_transmitter'],
			$cabrillo['category_time']        ?: '',
			$cabrillo['category_station'],
			$cabrillo['category_power'],
			$cabrillo['category_mode'],
			$cabrillo['category_band'],
			$cabrillo['category_assisted'],
			$cabrillo['category_operator'],
			$cbr_email,
			$cabrillo['certificate'] ?: null
		);

		foreach ($qsos->result() as $qso) {
			echo $this->cabrilloformat->qso($qso, $grid_export, $grid_chars);
		}

		echo $this->cabrilloformat->footer();
	}

	/**
	 * This starts the main logging engine for contesting
	 */
	public function logging_engine($logging_token) {
		$this->load->model('contesting_model');
		$this->load->model('user_model');

		// Decode logging token
		$decoded_token = $this->paths->decode_contesting_logging_token($logging_token);
		if (!$decoded_token || !isset($decoded_token['contest_session_id'])) {
			$this->session->set_flashdata('error', __("Invalid logging token."));
			redirect('contesting');
		}

		// setting up worker if available
		$worker_topic = 'contest_session.' . $decoded_token['contest_session_id']; // shared topic for all operators in this contest session
		if ($this->worker_available) {
			$this->worker->register_topic($worker_topic);
		}

		if ($this->worker_available && $decoded_token) {
			$data['worker_client_url'] = $this->worker->client_url();
			$data['worker_topic']      = $worker_topic;
			$data['worker_token']      = $this->worker->create_token((int) $decoded_token['contest_session_id']);
		}

		$contest_session_id = $decoded_token['contest_session_id'];

		// Load session data
		$data['session_info'] = $this->contesting_model->get_session_info($contest_session_id);
		if (!$data['session_info']) {
			$this->session->set_flashdata('error', __("Contest session not found."));
			redirect('contesting');
		}

		// Generate storage key for localStorage. This needs to be collision-free between different Wavelog Instances and different users
		$data['storage_key'] = md5($this->config->item('base_url') . $contest_session_id . $this->session->userdata('user_id'));

		$data['operator'] = $this->user_model->get_by_id($decoded_token['user_id'])->row()->user_callsign;
		$data['page_title'] = $data['session_info']['contest_name'];

		// Load available radios for CAT control
		$this->load->model('cat');
		$data['radios'] = $this->cat->radios();

		// Load available modes for manual frequency/mode entry
		$this->load->model('usermodes');
		$data['modes'] = $this->usermodes->active();

		// Load Bands
		$this->load->model('bands');
		$data['bands'] = $this->bands->get_user_bands_for_qso_entry();
		log_message('debug', 'Loaded bands for contest logger: ' . json_encode($data['bands']));

		// Set custom date format
		if ($this->session->userdata('user_date_format')) {
			$data['custom_date_format'] = $this->session->userdata('user_date_format');
		} else {
			$data['custom_date_format'] = $this->config->item('qso_date_format');
		}

		// Component and layout configuration (percentage-based: 0-100)
		// Component names as keys, layout configuration as values
		// This can later be overridden by user-defined layouts
		$data['components'] = [
			'qso-form' => [
				'x' => 1,      // 1% from left
				'y' => 1,      // 1% from top
				'width' => 70, // 70% of workspace width
				'height' => 60, // 60% of workspace height
			],
			'radio' => [
				'x' => 73,     // 73% from left (right side)
				'y' => 1,      // 1% from top
				'width' => 26, // 26% of workspace width
				'height' => 30, // 30% of workspace height
			],
			'clock' => [
				'x' => 1,      // 1% from left
				'y' => 63,     // 63% from top (below qso-form)
				'width' => 20, // 20% of workspace width
				'height' => 12, // 12% of workspace height
			],
			'map' => [
				'x' => 22,     // 22% from left (next to clock)
				'y' => 63,     // 63% from top (below qso-form)
				'width' => 51, // 51% of workspace width
				'height' => 36, // 36% of workspace height
			],
			'scp' => [
				'x' => 73,     // 73% from left (right side)
				'y' => 32,     // 32% from top (below radio)
				'width' => 26, // 26% of workspace width
				'height' => 67, // 67% of workspace height
			],
		];

		if ($this->session->userdata('isWinkeyEnabled')) {
			$data['components']['winkeyer'] = [
				'x'      => 73,
				'y'      => 63,
				'width'  => 26,
				'height' => 35,
			];
		}

		$this->load->view('contesting/logger/header', $data);
		$this->load->view('contesting/logger/index');
		$this->load->view('contesting/logger/footer');
	}

	public function create_session() {
		$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');
		switch ($this->input->method()) {
			case 'get':
				$this->load->is_loaded('contest_admin_model') ?: $this->load->model('contest_admin_model');
				$this->load->is_loaded('stations') ?: $this->load->model('stations');

				$data['available_contests'] = $this->contest_admin_model->getActiveContests();
				$data['stations'] = $this->stations->all_of_user();
				$data['active_station_location'] = $this->active_station_location;

				$this->load->view('contesting/manager/components/session_modal', $data);
				break;

			case 'post':
				if (!clubaccess_check(9)) {
					$this->session->set_flashdata('error', __("Officers must set up contests."));
					redirect('contesting'); 
				}
				$contest_adif_id = $this->input->post('contest_adif_id', true);
				$session_start = $this->input->post('session_start', true);
				$session_end = $this->input->post('session_end', true);
				$station_location = $this->input->post('station_location', true);
				$session_notes = $this->input->post('session_notes', true);
				$exchangefields = $this->_parseExchangeFields($this->input->post('exchangefields', true));
				$exchangetype   = $this->_fieldsToLegacyType($exchangefields);
				$copyexchangeto = $this->input->post('copyexchangeto', true) ?? '';

				$result = $this->contesting_model->create_contest_session($contest_adif_id, $session_start, $session_end, $station_location, $session_notes, false, $exchangetype, $copyexchangeto, $exchangefields);

				if ($result) {
					$this->session->set_flashdata('success', __("Contest session created successfully."));
				} else {
					$this->session->set_flashdata('error', __("There was an error creating the contest session. Please try again."));
				}

				redirect('contesting');

			default:
				$this->session->set_flashdata('error', __("Invalid request method."));
				redirect('contesting');
		}
	}

	public function edit_session(){
		$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');

		switch ($this->input->method()) {
			case 'get':
				$contest_session_id = $this->input->get('contest_session_id');
				
				$this->load->is_loaded('contest_admin_model') ?: $this->load->model('contest_admin_model');
				$this->load->is_loaded('stations') ?: $this->load->model('stations');

				$data['session_info'] = $this->contesting_model->get_session_info($contest_session_id);
				if (!$data['session_info']) {
					$this->session->set_flashdata('error', __("Contest session not found."));
					redirect('contesting');
				}

				$data['available_contests'] = $this->contest_admin_model->getActiveContests();
				$data['stations'] = $this->stations->all_of_user();
				$data['active_station_location'] = $this->active_station_location;

				$this->load->view('contesting/manager/components/session_modal', $data);
				break;

			case 'post':
				$contest_session_id = $this->input->post('contest_session_id', true);
				$time_start = $this->input->post('session_start', true);
				$time_end = $this->input->post('session_end', true);
				$station_id = $this->input->post('station_location', true);
				$notes = $this->input->post('session_notes', true);
				$contest_id = $this->input->post('contest_adif_id', true);
				$exchangefields = $this->_parseExchangeFields($this->input->post('exchangefields', true));
				$exchangetype   = $this->_fieldsToLegacyType($exchangefields);
				$copyexchangeto = $this->input->post('copyexchangeto', true) ?? '';

				$result = $this->contesting_model->update_contest_session($contest_session_id, $contest_id, $time_start, $time_end, $station_id, $notes, $exchangetype, $copyexchangeto, $exchangefields);

				if ($result) {
					$this->session->set_flashdata('success', __("Contest session updated successfully."));
				} else {
					$this->session->set_flashdata('error', __("There was an error updating the contest session. Please try again."));
				}

				redirect('contesting');

			default:
				$this->session->set_flashdata('error', __("Invalid request method."));
				redirect('contesting');
		}
	}

	public function delete_session(){
		$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');

		switch ($this->input->method()) {
			case 'get':
				$contest_session_id = $this->input->get('contest_session_id');
				
				$this->load->is_loaded('contest_admin_model') ?: $this->load->model('contest_admin_model');
				$this->load->is_loaded('stations') ?: $this->load->model('stations');

				$data['session_info'] = $this->contesting_model->get_session_info($contest_session_id);
				if (!$data['session_info']) {
					$this->session->set_flashdata('error', __("Contest session not found."));
					redirect('contesting');
				}

				$data['available_contests'] = $this->contest_admin_model->getActiveContests();
				$data['stations'] = $this->stations->all_of_user();
				$data['active_station_location'] = $this->active_station_location;

				$this->load->view('contesting/manager/components/confirm_delete', $data);
				break;	

			case 'post':
				$contest_session_id = $this->input->post('contest_session_id', true);

				$result = $this->contesting_model->delete_contest_session($contest_session_id);

				if ($result) {
					$this->session->set_flashdata('success', __("Contest session deleted successfully."));
				} else {
					$this->session->set_flashdata('error', __("There was an error deleting the contest session. Please try again."));
				}

				redirect('contesting');

			default:
				$this->session->set_flashdata('error', __("Invalid request method."));
				redirect('contesting');
		}
	}

	/**
	 * Inline QSO edit endpoint.
	 * Endpoint: POST /contesting/update_qso
	 *
	 * Accepts JSON: { contest_session_id, qso_id, callsign?, mode?, frequency?,
	 *                 rst_sent?, rst_rcvd?, serial_sent?, serial_rcvd?,
	 *                 exchange_sent?, exchange_rcvd?, gridsquare_rcvd? }
	 *
	 * Authorization: PHP session user must own the contest session AND be the
	 * operator recorded on the QSO (operator check).
	 */
	public function update_qso() {
		if ($this->input->method() !== 'post') {
			$this->_teapot();
			return;
		}

		header('Content-Type: application/json');

		try {
			$payload = json_decode($this->input->raw_input_stream, true);
			if (!$payload) {
				throw new Exception('Invalid JSON payload');
			}

			$contest_session_id = (int)($payload['contest_session_id'] ?? 0);
			$qso_id = (int)($payload['qso_id'] ?? 0);

			if (!$contest_session_id || !$qso_id) {
				throw new Exception('Missing contest_session_id or qso_id');
			}

			$this->load->model('contesting_model');

			// Session ownership check
			if (!$this->contesting_model->check_user_contest($contest_session_id)) {
				http_response_code(403);
				echo json_encode(['success' => false, 'error' => 'Access denied']);
				return;
			}

			// Verify QSO belongs to this session and get its operator
			$qso = $this->contesting_model->get_contest_qso($qso_id, $contest_session_id);
			if (!$qso) {
				http_response_code(404);
				echo json_encode(['success' => false, 'error' => 'QSO not found in this session']);
				return;
			}

			// Operator check: only the user who logged the QSO may edit it
			$current_callsign = strtoupper(trim($this->session->userdata('user_callsign')));
			$qso_operator = strtoupper(trim($qso['operator'] ?? ''));
			if ($qso_operator !== $current_callsign) {
				http_response_code(403);
				echo json_encode(['success' => false, 'error' => 'You can only edit QSOs you logged']);
				return;
			}

			// Whitelist of editable columns
			$allowed = [
				'callsign'      => 'COL_CALL',
				'mode'          => 'COL_MODE',
				'frequency'     => 'COL_FREQ',
				'band'          => 'COL_BAND',
				'rst_sent'      => 'COL_RST_SENT',
				'rst_rcvd'      => 'COL_RST_RCVD',
				'serial_sent'   => 'COL_STX',
				'serial_rcvd'   => 'COL_SRX',
				'exchange_sent' => 'COL_STX_STRING',
				'exchange_rcvd' => 'COL_SRX_STRING',
				'gridsquare_rcvd' => 'COL_GRIDSQUARE',
				'time_on'         => 'COL_TIME_ON',
			];

			$fields = [];
			foreach ($allowed as $key => $col) {
				if (array_key_exists($key, $payload)) {
					$val = $payload[$key];
					if (in_array($key, ['callsign', 'mode', 'band', 'rst_sent', 'rst_rcvd',
					                    'serial_sent', 'serial_rcvd', 'exchange_sent',
					                    'exchange_rcvd', 'gridsquare_rcvd'])) {
						$val = $val !== null ? strtoupper(trim((string)$val)) : null;
					}
					if ($key === 'time_on' && $val !== null) {
						$dt = DateTime::createFromFormat('Y-m-d H:i:s', trim((string)$val));
						if (!$dt) throw new Exception('Invalid time_on format');
						$val = $dt->format('Y-m-d H:i:s');
					}
					$fields[$col] = $val;
					if ($key === 'time_on') $fields['COL_TIME_OFF'] = $val;
				}
			}

			if (empty($fields)) {
				throw new Exception('No editable fields provided');
			}

			$this->contesting_model->update_contest_qso($qso_id, $fields);

			if ($this->worker_available) {
				$this->worker->publish('contest_session.' . $contest_session_id, ['type' => 'sync_required']);
			}

			echo json_encode(['success' => true, 'qso_id' => $qso_id]);
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode(['success' => false, 'error' => $e->getMessage()]);
		}
	}

	/**
	 * Delete a single QSO from a contest session and from the main logbook.
	 * Endpoint: POST /contesting/delete_qso
	 *
	 * Accepts JSON: { contest_session_id, qso_id }
	 */
	public function delete_qso() {
		if ($this->input->method() !== 'post') {
			$this->_teapot();
			return;
		}

		header('Content-Type: application/json');

		try {
			$payload = json_decode($this->input->raw_input_stream, true);
			if (!$payload) {
				throw new Exception('Invalid JSON payload');
			}

			$contest_session_id = (int)($payload['contest_session_id'] ?? 0);
			$qso_id             = (int)($payload['qso_id'] ?? 0);

			if (!$contest_session_id || !$qso_id) {
				throw new Exception('Missing contest_session_id or qso_id');
			}

			$this->load->model('contesting_model');

			if (!$this->contesting_model->check_user_contest($contest_session_id)) {
				http_response_code(403);
				echo json_encode(['success' => false, 'error' => 'Access denied']);
				return;
			}

			$qso = $this->contesting_model->get_contest_qso($qso_id, $contest_session_id);
			if (!$qso) {
				http_response_code(404);
				echo json_encode(['success' => false, 'error' => 'QSO not found in this session']);
				return;
			}

			$current_callsign = strtoupper(trim($this->session->userdata('user_callsign')));
			$qso_operator     = strtoupper(trim($qso['operator'] ?? ''));
			if ($qso_operator !== $current_callsign) {
				http_response_code(403);
				echo json_encode(['success' => false, 'error' => 'You can only delete QSOs you logged']);
				return;
			}

			$this->contesting_model->unlink_qso($qso_id, $contest_session_id);

			$this->load->model('logbook_model');
			$this->logbook_model->delete($qso_id);

			if ($this->worker_available) {
				$this->worker->publish('contest_session.' . $contest_session_id, ['type' => 'sync_required']);
			}

			echo json_encode(['success' => true, 'qso_id' => $qso_id]);
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode(['success' => false, 'error' => $e->getMessage()]);
		}
	}

	/**
	 * Sync Endpoint for Contest Engine
	 * Handles bidirectional communication (Commands + Requests)
	 * Endpoint: POST /contesting/heartbeat
	 */
	// TODO: Add Link to documentation
	public function heartbeat() {
		// Only accept POST requests
		if ($this->input->method() !== 'post') {
			$this->_teapot();
			return;
		}

		header('Content-Type: application/json');
		session_write_close();

		try {
			// Get JSON payload
			$json_input = $this->input->raw_input_stream;
			$payload = json_decode($json_input, true);

			if (!$payload) {
				throw new Exception('Invalid JSON payload');
			}

			$session_info = $payload['session_info'] ?? null;
			if (!$session_info) {
				throw new Exception('Missing contest_session_id');
			}
				
			$this->load->model('contesting_model');

			$response = [
				'success' => true,
				'commands_processed' => 0,
				'data' => [
					'saved_qsos' => [],
					'needs_resync' => false,
					'all_qsos' => null
				],
				'errors' => []
			];

			// Process Commands (Push Operations from client to server)
			if (isset($payload['commands']) && is_array($payload['commands'])) {
				foreach ($payload['commands'] as $command) {
					try {
						$response['commands_processed'] += $this->_processCommand($command, $session_info);
					} catch (Exception $e) {
						$response['errors'][] = $e->getMessage();
					}
				}
			}

			// Transfer saved QSOs from commands to response
			$response['data']['saved_qsos'] = $this->new_qsos;

			// Process Requests (Pull Operations from server to client)
			if (isset($payload['requests']) && is_array($payload['requests'])) {
				foreach ($payload['requests'] as $request) {
					try {
						$this->_processRequest($request, $session_info, $response);
					} catch (Exception $e) {
						$response['errors'][] = $e->getMessage();
					}
				}
			}

			echo json_encode($response);
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage(),
				'data' => []
			]);
		}
	}

	/**
	 * Process a single command (server write operation)
	 * @private
	 */
	private function _processCommand($command, $session_info) {
		if (!isset($command['type'])) {
			throw new Exception('Command missing type');
		}

		switch ($command['type']) {
			case 'save_qso': 
				// TODO: Make this one faster
				// TODO: Add better error handling
				if (!isset($command['data'])) {
					throw new Exception('save_qso command missing data');
				}

				// Only load models if needed
				$this->load->is_loaded('logbook_model') ?: $this->load->model('logbook_model');
				$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');

				// Prepare QSO data for saving
				$qso_data = [
					'manual' => 0, // work always as non-manual entry; TODO: Implement something like POST CONTEST LOGGING
					'start_date' => $command['data']['date'],
					'start_time' => $command['data']['time'],
					'end_time' => $command['data']['time'],
					'callsign' => $command['data']['callsign'],
					'freq_display' => $command['data']['frequency'],
					'mode' => $command['data']['mode'],
					'rst_sent' => $command['data']['rst_sent'],
					'rst_rcvd' => $command['data']['rst_rcvd'],
					'exch_serial_s' => $command['data']['serial_sent'] ?? NULL,
					'exch_serial_r' => $command['data']['serial_rcvd'] ?? NULL,
					'exch_sent' => $command['data']['exchange_sent'] ?? NULL,
					'exch_rcvd' => $command['data']['exchange_rcvd'] ?? NULL,
					'locator' => $command['data']['gridsquare_rcvd'] ?? NULL,
					'country' => $command['data']['country'] ?? NULL,
					'continent' => $command['data']['continent'] ?? NULL,
					'dxcc_id' => $command['data']['dxcc_id'] ?? NULL,
					'cqz' => $command['data']['cqz'] ?? NULL,
					'operator_callsign' => $command['data']['operator'] ?: $this->session->userdata('user_callsign'),
					'contestname' => $session_info['contest_adifname'],
					'exchangetype' => $session_info['exchangetype'] ?? 'Exchange',
					'copyexchangeto' => $session_info['copyexchangeto'] ?? NULL
				];

				// Save QSO to database
				$save_result = $this->logbook_model->create_qso($qso_data, false);

				// Link QSO to contest session
				if ($save_result['qso_id']) {
					$this->contesting_model->link_qso($save_result['qso_id'], $session_info['contest_session_id']);
					// Notify worker clients about new QSO if worker is available
					if ($this->worker_available) {
						$this->worker->publish('contest_session.' . $session_info['contest_session_id'], ['type' => 'sync_required']);
						log_message('debug', 'published sync_required for contest session ' . $session_info['contest_session_id']);
					}
				} else {
					throw new Exception('Failed to save QSO');;
				}

				// Store mapping of tmp_id to real ID for client response
				// This will be sent back to the client to update local state
				$this->new_qsos[] = [
					'tmp_id' => $command['data']['tmp_id'],
					'server_id' => $save_result['qso_id']
				];

				return 1; // 1 command processed

			default:
				throw new Exception("Unknown command type: {$command['type']}");
		}
	}

	/**
	 * Process a single request (server read operation)
	 * @private
	 */
	private function _processRequest($request, $session_info, &$response) {
		if (!isset($request['type'])) {
			throw new Exception('Request missing type');
		}

		switch ($request['type']) {
			case 'get_radio_status':
				// Radio CAT status request
				if (!isset($request['radio_id']) || $request['radio_id'] === '0') {
					break; // No radio selected
				}

				$this->load->model('cat');
				$radio_query = $this->cat->radio_status($request['radio_id']);

				if ($radio_query->num_rows() > 0) {
					$row = $radio_query->row();
					$response['data']['radio_status'] = [
						'frequency' => $row->frequency,
						'mode' => $row->mode,
						'timestamp' => strtotime($row->timestamp) * 1000, // Unix timestamp in ms
						'updated_minutes_ago' => floor((time() - strtotime($row->timestamp)) / 60)
					];
				} else {
					$response['data']['radio_status'] = null;
				}
				break;

			case 'check_sync':
				// Count-based sync check (replaces get_new_qsos)
				$client_qso_count = $request['client_qso_count'] ?? 0;

				// Get current server count (includes just-saved QSOs)
				$this->load->is_loaded('contesting_model') ?: $this->load->model('contesting_model');
				$server_qso_count = $this->contesting_model->get_session_qso_count($session_info['contest_session_id']);

				// Make server count available to response without extra query later
				$response['server_qso_count'] = $server_qso_count;

				// Calculate expected client count after processing saved_qsos
				$expected_client_count = $client_qso_count + count($response['data']['saved_qsos']);

				// If counts don't match, trigger full resync
				if ($expected_client_count !== $server_qso_count) {
					$response['data']['needs_resync'] = true;
					$all_qsos = $this->contesting_model->get_session_qsos($session_info['contest_session_id']);

					// Map qso_id to id for frontend compatibility
					if (is_array($all_qsos)) {
						$all_qsos = array_map(function ($qso) {
							$qso['id'] = $qso['qso_id'];
							return $qso;
						}, $all_qsos);
					}

					$response['data']['all_qsos'] = $all_qsos;

					log_message('info', "Resync triggered for session {$session_info['contest_session_id']}: Client={$client_qso_count} + Saved=" . count($response['data']['saved_qsos']) . " = {$expected_client_count}, Server={$server_qso_count}");
				} else {
					$response['data']['needs_resync'] = false;
				}

				// Timestamp-based edit detection: resync when any QSO was modified after the
				// client's last known sync time (catches inline edits that don't change QSO count).
				// Skip when QSOs were saved in this same heartbeat — their last_modified being
				// newer than lastSyncTime is expected and the count-check already handled them.
				$last_sync_time_ms = (int)($request['last_sync_time'] ?? 0);
				$just_saved = count($response['data']['saved_qsos'] ?? []) > 0;
				if ($last_sync_time_ms > 0 && !$response['data']['needs_resync'] && !$just_saved) {
					$server_last_update = $this->contesting_model->get_session_last_update($session_info['contest_session_id']);
					// Compare at second precision: DB stores last_modified as DATETIME (no sub-second),
					// while the client sends Date.now() in ms. Using >= prevents missed edits when the
					// edit and the last heartbeat land in the same DB second.
					if ((int)($server_last_update / 1000) >= (int)($last_sync_time_ms / 1000)) {
						$response['data']['needs_resync'] = true;
						if (!isset($response['data']['all_qsos'])) {
							$all_qsos = $this->contesting_model->get_session_qsos($session_info['contest_session_id']);
							if (is_array($all_qsos)) {
								$all_qsos = array_map(function ($qso) {
									$qso['id'] = $qso['qso_id'];
									return $qso;
								}, $all_qsos);
							}
							$response['data']['all_qsos'] = $all_qsos;
						}
					}
				}
				break;

			case 'get_propagation_info':
				// TODO: Implement propagation info
				$response['data']['propagation_info'] = null;
				break;

			default:
				throw new Exception("Unknown request type: {$request['type']}");
		}
	}

	/**
	 * Get all saved layouts for user
	 * POST: /contesting/get_layouts
	 */
	public function get_layouts() {
		header('Content-Type: application/json');

		if ($this->input->method() !== 'post') {
			$this->_teapot();
			return;
		}

		try {
			$this->load->model('user_options_model');
			$result = $this->user_options_model->get_options('contest_logger_layout');

			// Get default layout name
			$default_result = $this->user_options_model->get_options('contest_logger_settings', ['option_name' => 'default_layout']);
			$default_layout = null;
			if ($default_result && $default_result->num_rows() > 0) {
				$default_layout = $default_result->row()->option_value;
			}

			$layouts = [];
			if ($result && $result->num_rows() > 0) {
				foreach ($result->result() as $row) {
					$layouts[] = [
						'name' => $row->option_name,
						'data' => json_decode($row->option_value, true),
						'is_default' => ($row->option_name === $default_layout)
					];
				}
			}

			echo json_encode([
				'success' => true,
				'layouts' => $layouts,
				'default_layout' => $default_layout
			]);
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage()
			]);
		}
	}

	/**
	 * Set default layout for contest logger
	 * POST: /contesting/set_default_layout
	 */
	public function set_default_layout() {
		header('Content-Type: application/json');

		if ($this->input->method() !== 'post') {
			$this->_teapot();
			return;
		}

		try {
			$json_input = $this->input->raw_input_stream;
			$payload = json_decode($json_input, true);

			if (!$payload || !isset($payload['name'])) {
				throw new Exception('Missing required field: name');
			}

			$name = $payload['name'];

			// Save default layout preference
			$this->load->model('user_options_model');
			$this->user_options_model->set_option(
				'contest_logger_settings',
				'default_layout',
				['value' => $name]
			);

			echo json_encode([
				'success' => true,
				'message' => 'Default layout set successfully'
			]);
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage()
			]);
		}
	}

	/**
	 * Save user layout for contest logger
	 * POST: /contesting/save_layout
	 */
	public function save_layout() {
		header('Content-Type: application/json');

		if ($this->input->method() !== 'post') {
			$this->_teapot();
			return;
		}

		try {
			$json_input = $this->input->raw_input_stream;
			$payload = json_decode($json_input, true);

			if (!$payload || !isset($payload['layout']) || !isset($payload['name'])) {
				throw new Exception('Missing required fields: layout or name');
			}

			$layout = $payload['layout'];
			$name = trim($payload['name']);

			if (empty($name)) {
				throw new Exception('Layout name cannot be empty');
			}

			// Save layout to user_options
			$this->load->model('user_options_model');
			$this->user_options_model->set_option(
				'contest_logger_layout',
				$name,
				['data' => json_encode($layout)]
			);

			echo json_encode([
				'success' => true,
				'message' => 'Layout saved successfully'
			]);
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage()
			]);
		}
	}

	/**
	 * Load user layout for contest logger
	 * POST: /contesting/load_layout
	 */
	public function load_layout() {
		header('Content-Type: application/json');

		if ($this->input->method() !== 'post') {
			$this->_teapot();
			return;
		}

		try {
			$json_input = $this->input->raw_input_stream;
			$payload = json_decode($json_input, true);

			if (!$payload || !isset($payload['name'])) {
				throw new Exception('Missing required field: name');
			}

			$name = $payload['name'];

			// Load layout from user_options
			$this->load->model('user_options_model');
			$result = $this->user_options_model->get_options(
				'contest_logger_layout',
				['option_name' => $name]
			);

			if ($result && $result->num_rows() > 0) {
				$row = $result->row();
				$layout = json_decode($row->option_value, true);

				echo json_encode([
					'success' => true,
					'layout' => $layout
				]);
			} else {
				echo json_encode([
					'success' => true,
					'layout' => null
				]);
			}
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage()
			]);
		}
	}

	/**
	 * Delete user layout for contest logger
	 * POST: /contesting/delete_layout
	 */
	public function delete_layout() {
		header('Content-Type: application/json');

		if ($this->input->method() !== 'post') {
			$this->_teapot();
			return;
		}

		try {
			$json_input = $this->input->raw_input_stream;
			$payload = json_decode($json_input, true);

			if (!$payload || !isset($payload['name'])) {
				throw new Exception('Missing required field: name');
			}

			$name = $payload['name'];

			// Delete layout from user_options
			$this->load->model('user_options_model');

			// Delete specific layout
			$this->user_options_model->del_option(
				'contest_logger_layout',
				$name
			);

			echo json_encode([
				'success' => true,
				'message' => 'Layout deleted successfully'
			]);
		} catch (Exception $e) {
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage()
			]);
		}
	}

	/*
	 * Provide a dxcc search, returning results json encoded
	 */
	public function dxcheck() {

		$call = $this->input->get('call') ?? NULL;
		$date = $this->input->get('date') ?? date("Y-m-d");

		if (!$call) {
			http_response_code(400);
			echo json_encode(['success' => false, 'error' => 'Missing required parameter: call']);
			return;
		}

		$dxccobj = new Dxcc($date);
		$result = $dxccobj->dxcc_lookup($call, $date);

		header('Content-Type: application/json');
		echo json_encode($result);
	}

	private function _parseExchangeFields($json) {
		$allowed = ['serial', 'gridsquare', 'exchange'];
		$decoded = json_decode($json ?? '', true);
		if (!is_array($decoded)) {
			return ['exchange'];
		}
		$fields = array_values(array_filter($decoded, fn($f) => in_array($f, $allowed)));
		return $fields ?: ['exchange'];
	}

	private function _fieldsToLegacyType($fields) {
		$s = in_array('serial',    $fields);
		$g = in_array('gridsquare', $fields);
		$e = in_array('exchange',  $fields);
		if ($s && $g && $e) return 'SerialGridExchange';
		if ($s && $g)       return 'Serialgridsquare';
		if ($s && $e)       return 'Serialexchange';
		if ($e && $g)       return 'Exchangegridsquare';
		if ($s)             return 'Serial';
		return 'Exchange';
	}

	private function _teapot() {
		http_response_code(418);
		echo json_encode(['success' => false, 'error' => "I'm a teapot. Don't brew coffee with me."]);
	}
}
