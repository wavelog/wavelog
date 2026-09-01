<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of band information
*/

class Satellite extends CI_Controller {

	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');
		$this->load->model('logbook_model');

		$satellites = $this->satellite_model->get_all_satellites();
		$pageData['satellites'] = $satellites;

		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$pageData['custom_date_format'] = $custom_date_format;

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/satellite.js',
		];

		// Render Page
		$pageData['page_title'] = __("Satellites");
		$this->load->view('interface_assets/header', $pageData);
		$this->load->view('satellite/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function create() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['page_title'] = __("Create Satellite");
		$this->load->view('satellite/create', $data);
	}

	public function createSatellite() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');

		$this->satellite_model->add();
	}

	public function edit() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');

		$item_id_clean = $this->security->xss_clean($this->input->post('id'));

		$satellite_query = $this->satellite_model->getsatellite($item_id_clean);

		$mode_query = $this->satellite_model->getsatmodes($item_id_clean);

		$data['satellite'] = $satellite_query->row();
		$data['satmodes'] = $mode_query->result();

		$data['page_title'] = __("Edit Satellite");

		$this->load->view('satellite/edit', $data);
	}

	public function saveupdatedSatellite() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');

		$id = $this->input->post('id', true);
		$satellite['name'] 	= $this->security->xss_clean($this->input->post('name'));
		$satellite['displayname'] 	= $this->security->xss_clean($this->input->post('displayname'));
		$satellite['orbit'] 	= $this->security->xss_clean($this->input->post('orbit'));
		if ($this->security->xss_clean($this->input->post('lotw')) == 'Y') {
			$satellite['lotw'] = 'Y';
		} else {;
		$satellite['lotw'] = 'N';
		}

		$this->satellite_model->saveupdatedsatellite($id, $satellite);
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function delete() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$id = $this->input->post('id');
		$this->load->model('satellite_model');
		$this->satellite_model->delete($id);
	}

	public function deleteSatMode() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$id = $this->input->post('id');
		$this->load->model('satellite_model');
		$this->satellite_model->deleteSatMode($id);
	}

	public function saveSatellite() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$id 				= $this->security->xss_clean($this->input->post('id'));
		$satellite['name'] 	= $this->security->xss_clean($this->input->post('name'));

		$this->load->model('satellite_model');
		$this->satellite_model->saveSatellite($id, $satellite);

		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function saveSatModeChanges() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$id 						= $this->security->xss_clean($this->input->post('id'));
		$satmode['name'] 			= $this->security->xss_clean($this->input->post('name'));
		$satmode['uplink_mode'] 	= $this->security->xss_clean($this->input->post('uplink_mode'));
		$satmode['uplink_freq'] 	= filter_var($this->security->xss_clean($this->input->post('uplink_freq')),FILTER_VALIDATE_INT);
		$satmode['downlink_mode'] 	= $this->security->xss_clean($this->input->post('downlink_mode'));
		$satmode['downlink_freq'] 	= filter_var($this->security->xss_clean($this->input->post('downlink_freq')),FILTER_VALIDATE_INT);;

		$this->load->model('satellite_model');
		$this->satellite_model->saveSatelliteMode($id, $satmode);

		header('Content-Type: application/json');
		echo json_encode(array('message' => 'OK'));
		return;
	}

	public function addSatMode() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');
		$inserted_id = $this->satellite_model->insertSatelliteMode();

		header('Content-Type: application/json');
		echo json_encode(array('inserted_id' => $inserted_id));
		return;
	}

	public function satellite_data() {
		session_write_close();

		$this->load->model('satellite_model');
		$satellite_data = $this->satellite_model->satellite_data();
		$sat_list = array();
		foreach ($satellite_data as $sat) {
			$satellite_key = $sat->satellite ?? '';
			$satmode_key = $sat->satmode ?? '';
			$sat_list[$satellite_key]['Modes'][$satmode_key][0]['Uplink_Mode'] = $sat->Uplink_Mode;
			$sat_list[$satellite_key]['Modes'][$satmode_key][0]['Uplink_Freq'] = $sat->Uplink_Freq;
			$sat_list[$satellite_key]['Modes'][$satmode_key][0]['Downlink_Mode'] = $sat->Downlink_Mode;
			$sat_list[$satellite_key]['Modes'][$satmode_key][0]['Downlink_Freq'] = $sat->Downlink_Freq;
		}
		header('Content-Type: application/json');
		echo json_encode($sat_list, JSON_FORCE_OBJECT);
	}

	public function flightpath($sat = null) {

		$this->load->model('satellite_model');
		$this->load->model('stations');

		$pageData['satellites'] = $this->satellite_model->get_all_satellites_with_tle();
		$data['selsat'] = strtoupper($sat ?? $this->satellite_model->get_last_worked_sat() ?? '');

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/satellite.js',
			'assets/js/sections/three-orbit-controls.js',
			'assets/js/sections/satellite_functions.js',
			'assets/js/sections/flightpath.js',
			'assets/js/leaflet/L.Maidenhead.js',
			'assets/js/leaflet/geocoding.js',
		];

		$homegrid = explode(',', $this->stations->find_gridsquare());

		$this->load->library('Qra');
		$pageData['latlng'] = $this->qra->qra2latlong($homegrid[0]);
		$pageData['homegrid'] = $homegrid[0];
		// Render Page
		$pageData['page_title'] = __("Satellite Flightpath");
		$this->load->view('interface_assets/header', $pageData);
		$this->load->view('satellite/flightpath', $data);
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function create_ics($raw_sat,$raw_aos,$raw_los) {

		$data['sat'] = $this->security->xss_clean($raw_sat);
		$data['aos'] = $this->security->xss_clean($raw_aos);
		$data['los'] = $this->security->xss_clean($raw_los);

		header("Content-type:text/calendar");
		header('Content-Disposition: attachment; filename="'.$data['sat'].'.ics"');
		$this->load->view('satellite/schedule',$data);
	}

	public function get_sat_info() {

		$sat = $this->security->xss_clean($this->input->post('sat'));
		$this->load->model('satellite_model');
		$satellite_data = $this->satellite_model->get_sat_info($sat);

		// Synthesize TLE text at render time for the legacy JS propagator (OMM-stored sats).
		if ($satellite_data && !empty($satellite_data->tle)) {
			require_once './src/predict/Predict/TLE.php';
			if (Predict_TLE::isOmmJson($satellite_data->tle)) {
				try {
					$t = Predict_TLE::fromOmmJson($satellite_data->tle);
					$satellite_data->tle = $t->toTwolineTle();
				} catch (\Throwable $e) {
					log_message('error', 'OMM->TLE synthesis failed for "'.$sat.'": '.$e->getMessage());
				}
			}
		}

		header('Content-Type: application/json');
		echo json_encode($satellite_data, JSON_FORCE_OBJECT);
	}

	public function lotw_support() {
		$sat = $this->security->xss_clean($this->input->post('sat'));
		$this->load->model('satellite_model');
		$lotw_data = $this->satellite_model->lotw_support($sat);

		header('Content-Type: application/json');
		echo json_encode($lotw_data);
	}

	public function pass() {

		$this->load->model('satellite_model');
		$this->load->model('stations');
		$active_station_id = $this->stations->find_active();
		// Prefer a gridsquare passed in the query string (e.g. a link from the
		// activation planner); otherwise fall back to the active station's grid.
		$grid_param = strtoupper((string) $this->input->get('gridsquare', TRUE));
		if (!preg_match('/^[A-R]{2}[0-9A-X]{0,8}$/', $grid_param)) {
			$grid_param = '';
		}
		$pageData['activegrid'] = ($grid_param !== '') ? $grid_param : $this->stations->gridsquare_from_station($active_station_id);

		$pageData['satellites'] = $this->satellite_model->get_all_satellites_with_tle();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/bootstrap-multiselect.js',
			'assets/js/sections/satpasses.js',
		];

		// Render Page
		$pageData['page_title'] = __("Satellite pass");
		$this->load->view('interface_assets/header', $pageData);
		$this->load->view('satellite/pass');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function searchPasses() {

		try {
			$tles = $this->get_tle_for_predict();
			$yourgrid = $this->security->xss_clean($this->input->post('yourgrid'));
			$date = $this->security->xss_clean($this->input->post('date'));
			$mintime = $this->security->xss_clean($this->input->post('mintime'));
			$minelevation = $this->security->xss_clean($this->input->post('minelevation'));
			$data = $this->calcPasses($tles, $yourgrid, $date, $mintime, $minelevation);

			$this->load->view('satellite/passtable', $data);
		}
		catch (Exception $e) {
			header("Content-type: application/json");
			echo json_encode(['ok' => 'Error', 'message' => $e->getMessage() . $e->getCode()]);
		}
	}

	public function searchSkedPasses() {

		try {
			$tle = $this->get_tle_for_predict();
			$this->calcSkedPasses($tle);
		}
		catch (Exception $e) {
			header("Content-type: application/json");
			echo json_encode(['ok' => 'Error', 'message' => $e->getMessage() . $e->getCode()]);
		}
	}

	public function savePassSettings() {

		$settings = [];
		$msg = [];

		$settings['name']         	= $this->input->post('setting_name', true);
		$settings['minelevation']  	= $this->input->post('minelevation', true);
		$settings['minazimuth']    	= $this->input->post('minazimuth', true);
		$settings['maxazimuth']    	= $this->input->post('maxazimuth', true);
		$settings['grid']          	= $this->input->post('grid', true);
		$settings['sat']           	= $this->input->post('sat', true);

		$settings['sked_minelevation'] 	= $this->input->post('sked_minelevation', true) ?? '';
		$settings['sked_minazimuth']   	= $this->input->post('sked_minazimuth', true) ?? '';
		$settings['sked_maxazimuth']  	= $this->input->post('sked_maxazimuth', true) ?? '';
		$settings['sked_grid']         	= $this->input->post('sked_grid', true) ?? '';

		$settings_id  				= md5($settings['name']);
		if(!$this->user_options_model->set_option('sat_pass_settings', $settings_id, array('data' => json_encode($settings)))) {
			log_message('error', 'Failed to save pass settings; User: '.$this->session->userdata('user_id').'; Settings: '.json_encode($settings));
			$msg['ok'] = 'Error';
			$msg['message'] = __("Failed to save pass settings!");
		} else {
			$msg['ok'] = 'OK';
			$msg['message'] = __("Pass settings saved!");
		}

		header('Content-Type: application/json');
		echo json_encode($msg);
	}

	public function loadPassSettings() {

		$settings_id = $this->input->post('settings_id', true) ?? '';
		$settings = $this->user_options_model->get_options('sat_pass_settings', array('option_name' => $settings_id))->row();

		if ($settings == false) {
			echo json_encode(array('ok' => 'Error', 'message' => __("No settings found!")));
			return;
		}

		header('Content-Type: application/json');
		echo json_encode($settings->option_value);
	}

	public function delPassSettings() {

		$settings_id = $this->input->post('settings_id', true);

		if(!$this->user_options_model->del_option('sat_pass_settings', $settings_id)) {
			log_message('error', 'Failed to delete pass settings; User: '.$this->session->userdata('user_id').'; settings id: '.$settings_id);
			echo json_encode(array('ok' => 'Error', 'message' => __("Failed to delete pass settings!")));
		} else {
			echo json_encode(array('ok' => 'OK', 'message' => __("Pass settings deleted!")));
		}
	}

	public function getPassSettingsList() {

		$sat_pass_settings = $this->user_options_model->get_options('sat_pass_settings', null, $this->session->userdata('user_id'))->result();

		$r = '';
		if (!empty($sat_pass_settings)) {
			$r .= '<li class="dropdown-header">'.__("Saved Pass Settings").'</li>';
			foreach ($sat_pass_settings as $setting) {
				$value = json_decode($setting->option_value);
				$settings_id  = $setting->option_name;

				$r .= 	'<li>
							<div class="dropdown-item d-flex justify-content-between align-items-center">
								<button type="button" class="btn d-flex align-items-center" onclick="loadPassSettings(\''. $settings_id .'\')">
									<i class="fas fa-download me-2"></i>'. $value->name .'
								</button>
								<button type="button" class="btn btn-sm bg-danger d-flex align-items-center" onclick="delPassSettings(\''. $settings_id .'\')">
									<i class="fas fa-trash-alt"></i>
								</button>
							</div>
						</li>';
			}
		} else {
			$r .= '<li><p class="text-muted text-center">'.__("No presets available").'</p></li>';
		}

		echo $r;
	}

	/**
	 * Build a Predict_TLE from stored elements, handling both legacy TLE text
	 * and CCSDS OMM JSON.
	 */
	private function build_predict_tle($sat_tle) {
		$this->load->library('satpredict');
		return $this->satpredict->build_tle($sat_tle);
	}

	public function get_tle_for_predict() {

		$input_sat = (array) ($this->security->xss_clean($this->input->post('sat')) ?? []);
		$this->load->model('satellite_model');
		$tles=[];
		$satellites = $this->satellite_model->get_all_satellites_with_tle();
		foreach ($satellites as $sat) {			// Loop through known SATs
			if ( (count($input_sat) > 0) && !((count($input_sat) == 1) && (($input_sat[0] ?? '') == '')) ) {		// User wants specific SATs (which isn't "All" or empty)??
				if (in_array($sat->satname,$input_sat) || in_array($sat->displayname,$input_sat)) {
					$tles[]=$this->satellite_model->get_sat_info($sat->satname ? $sat->satname : $sat->displayname);
				} else {
					continue;
				}
			} else {				// No specific SAT, but all
				$tles[]=$this->satellite_model->get_sat_info($sat->satname ? $sat->satname : $sat->displayname);
			}
		}
		return $tles;
	}

	function calcPasses($sat_tles, $yourgrid, $date, $mintime, $minelevation, $timezone = 'UTC') {
		$this->load->library('satpredict');
		return $this->satpredict->calcPasses($sat_tles, $yourgrid, $date, $mintime, $minelevation, $timezone);
	}

	function calcPass($sat_tle, $yourgrid, $date, $mintime, $minelevation, $timezone = 'UTC') {
		$this->load->library('satpredict');
		return $this->satpredict->calcPass($sat_tle, $yourgrid, $date, $mintime, $minelevation, $timezone);
	}

	function calcSkedPasses($tles) {
		$overlaps=[];
		$yourgrid = '';
		$skedgrid = '';
		$date = '';
		
		// Get Date format
		if ($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		foreach ($tles as $tle) {

			$yourgrid = $this->security->xss_clean($this->input->post('yourgrid'));
			$date = $this->security->xss_clean($this->input->post('date'));
			$mintime = $this->security->xss_clean($this->input->post('mintime'));
			$minelevation = $this->security->xss_clean($this->input->post('minelevation'));

			$homePass =	$this->calcPass($tle, $yourgrid, $date, $mintime, $minelevation);

			$skedgrid = $this->security->xss_clean($this->input->post('skedgrid'));
			$minskedelevation = $this->security->xss_clean($this->input->post('minskedelevation'));

			$skedPass = $this->calcPass($tle, $skedgrid, $date, $mintime, $minskedelevation);

			$data['format'] = $custom_date_format . ' H:i:s';

			array_push($overlaps, ...$this->findOverlaps($homePass, $skedPass));
		}
		usort($overlaps, function($a, $b) {
			return $a['grid1']->aos <=> $b['grid1']->aos;
		});
		$data['overlaps'] = $overlaps;
		$data['yourgrid'] = $yourgrid;
		$data['skedgrid'] = $skedgrid;
		$data['date'] = $date;
		$data['custom_date_format'] = $custom_date_format;

		$this->load->view('satellite/skedtable', $data);
	}

	function findOverlaps($homePass, $skedPass) {
		$overlaps = []; // Store overlapping passes

		foreach ($homePass['filtered'] as $pass1) {
			foreach ($skedPass['filtered'] as $pass2) {
				if ($this->checkOverlap($pass1, $pass2)) {
					$overlaps[] = [
						'grid1' => $pass1,
						'grid2' => $pass2
					];
				}
			}
		}

		return $overlaps;
	}

	function checkOverlap($pass1, $pass2) {
		// Calculate the overlap condition
		$start = max($pass1->visible_aos, $pass2->visible_aos); // Latest start time
		$end = min($pass1->visible_los, $pass2->visible_los);   // Earliest end time

		return $start <= $end; // True if intervals overlap
	}

	public static function get_daynum_from_date($date) {
		require_once "./src/predict/Predict/Time.php";
		// Convert a Y-m-d date to a day number

		// Convert date to Unix timestamp
		$timestamp = strtotime($date);
		if ($timestamp === false) {
			throw new Exception("Invalid date format. Expected Y-m-d.");
		}

		// Calculate the day number
		return Predict_Time::unix2daynum($timestamp, 0);
	}

	public function getSatelliteInfo() {
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$data['custom_date_format'] = $custom_date_format;

		$satname = $this->input->post('sat', true);
		$this->load->model('satellite_model');

		$data['satinfo'] = $this->satellite_model->get_satellite_information($satname);

		$this->load->view('satellite/satinfo', $data);
	}

	public function editTleDialog() {
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$data['custom_date_format'] = $custom_date_format;

		$id = $this->input->post('id', true);
		$this->load->model('satellite_model');

		$data['satinfo'] = $this->satellite_model->getsatellite($id)->result();
		$data['tleinfo'] = $this->satellite_model->get_sat_info($data['satinfo'][0]->name ? $data['satinfo'][0]->name : $data['satinfo'][0]->displayname);

		$this->load->view('satellite/tleinfo', $data);
	}

	public function deleteTle() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$id = $this->input->post('id', true);
		$this->load->model('satellite_model');

		$data['satinfo'] = $this->satellite_model->deleteTle($id);
	}

	public function saveTle() {
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$id = $this->input->post('id', true);
		$tle = $this->input->post('tle', true);
		$this->load->model('satellite_model');

		$this->satellite_model->saveTle($id, $tle);
	}
}
