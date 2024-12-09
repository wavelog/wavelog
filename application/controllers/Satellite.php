<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of band information
*/

class Satellite extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url'));
		$this->load->model('user_model');
	}

	public function index()
	{
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');

		$pageData['satellites'] = $this->satellite_model->get_all_satellites();

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
			'assets/js/sections/satellite.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/satellite.js")),
		];

		// Render Page
		$pageData['page_title'] = "Satellites";
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

	public function edit()
	{
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

		$id = $this->security->xss_clean($this->input->post('id', true));
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
		$satmode['uplink_freq'] 	= $this->security->xss_clean($this->input->post('uplink_freq'));
		$satmode['downlink_mode'] 	= $this->security->xss_clean($this->input->post('downlink_mode'));
		$satmode['downlink_freq'] 	= $this->security->xss_clean($this->input->post('downlink_freq'));

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
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');
		$satellite_data = $this->satellite_model->satellite_data();
		$sat_list = array();
		foreach ($satellite_data as $sat) {
			$sat_list[$sat->satellite]['Modes'][$sat->satmode][0]['Uplink_Mode'] = $sat->Uplink_Mode;
			$sat_list[$sat->satellite]['Modes'][$sat->satmode][0]['Uplink_Freq'] = $sat->Uplink_Freq;
			$sat_list[$sat->satellite]['Modes'][$sat->satmode][0]['Downlink_Mode'] = $sat->Downlink_Mode;
			$sat_list[$sat->satellite]['Modes'][$sat->satmode][0]['Downlink_Freq'] = $sat->Downlink_Freq;
		}
		header('Content-Type: application/json');
		echo json_encode($sat_list, JSON_FORCE_OBJECT);
	}

	public function flightpath() {
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');
		$this->load->model('stations');

		$pageData['satellites'] = $this->satellite_model->get_all_satellites_with_tle();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/satellite.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/satellite.js")),
			'assets/js/sections/three-orbit-controls.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/three-orbit-controls.js")),
			'assets/js/sections/satellite_functions.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/satellite_functions.js")),
			'assets/js/sections/flightpath.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/flightpath.js")),
		];

		$homegrid = explode(',', $this->stations->find_gridsquare());

		$this->load->library('Qra');
		$pageData['latlng'] = $this->qra->qra2latlong($homegrid[0]);

		// Render Page
		$pageData['page_title'] = "Satellite Flightpath";
		$this->load->view('interface_assets/header', $pageData);
		$this->load->view('satellite/flightpath');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function get_tle() {
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$sat = $this->security->xss_clean($this->input->post('sat'));
		$this->load->model('satellite_model');
		$satellite_data = $this->satellite_model->get_tle($sat);

		header('Content-Type: application/json');
		echo json_encode($satellite_data, JSON_FORCE_OBJECT);
	}

	public function pass() {
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('satellite_model');
		$this->load->model('stations');
        $active_station_id = $this->stations->find_active();
		$pageData['activegrid'] = $this->stations->gridsquare_from_station($active_station_id);

		$pageData['satellites'] = $this->satellite_model->get_all_satellites_with_tle();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/satpasses.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/satpasses.js")),
		];

		// Render Page
		$pageData['page_title'] = "Satellite pass";
		$this->load->view('interface_assets/header', $pageData);
		$this->load->view('satellite/pass');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function searchPasses() {
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		try {
			$tle = $this->get_tle_for_predict();
			$yourgrid = $this->security->xss_clean($this->input->post('yourgrid'));
			$altitude = $this->security->xss_clean($this->input->post('altitude'));
			$date = $this->security->xss_clean($this->input->post('date'));
			$minelevation = $this->security->xss_clean($this->input->post('minelevation'));
			$timezone = $this->security->xss_clean($this->input->post('timezone'));
			$data = $this->calcPass($tle, $yourgrid, $altitude, $date, $minelevation, $timezone);

			$this->load->view('satellite/passtable', $data);
		}
		catch (Exception $e) {
			header("Content-type: application/json");
			echo json_encode(['ok' => 'Error', 'message' => $e->getMessage() . $e->getCode()]);
		}
	}

	public function searchSkedPasses() {
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		try {
			$tle = $this->get_tle_for_predict();
			$this->calcSkedPass($tle);
		}
		catch (Exception $e) {
			header("Content-type: application/json");
			echo json_encode(['ok' => 'Error', 'message' => $e->getMessage() . $e->getCode()]);
		}
	}

	public function get_tle_for_predict() {
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$sat = $this->security->xss_clean($this->input->post('sat'));
		$this->load->model('satellite_model');
		return $this->satellite_model->get_tle($sat);
	}

	function calcPass($sat_tle, $yourgrid, $altitude, $date, $minelevation, $timezone) {
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		require_once "./src/predict/Predict.php";
		require_once "./src/predict/Predict/Sat.php";
		require_once "./src/predict/Predict/QTH.php";
		require_once "./src/predict/Predict/Time.php";
		require_once "./src/predict/Predict/TLE.php";

		// The observer or groundstation is called QTH in ham radio terms
		$predict  = new Predict();
		$qth      = new Predict_QTH();
		$qth->alt = $altitude; // Altitude in meters

		$strQRA = $yourgrid;

		if ((strlen($strQRA) % 2 == 0) && (strlen($strQRA) <= 10)) {	// Check if QRA is EVEN (the % 2 does that) and smaller/equal 8
			$strQRA = strtoupper($strQRA);
			if (strlen($strQRA) == 4)  $strQRA .= "LL";	// Only 4 Chars? Fill with center "LL" as only A-R allowed
			if (strlen($strQRA) == 6)  $strQRA .= "55";	// Only 6 Chars? Fill with center "55"
			if (strlen($strQRA) == 8)  $strQRA .= "LL";	// Only 8 Chars? Fill with center "LL" as only A-R allowed

			if (!preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}[0-9]{2}[A-X]{2}$/', $strQRA)) {
				return false;
			}
		}

		if(!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}
		$homecoordinates = $this->qra->qra2latlong($yourgrid);

		$qth->lat = $homecoordinates[0];
		$qth->lon = $homecoordinates[1];

		$temp = preg_split('/\n/', $sat_tle->tle);

		$tle     = new Predict_TLE($sat_tle->satellite, $temp[0], $temp[1]); // Instantiate it
		$sat     = new Predict_Sat($tle); // Load up the satellite data

		$now     = $this->get_daynum_from_date($date); // get the current time as Julian Date (daynum)

		// You can modify some preferences in Predict(), the defaults are below
		//
		$predict->minEle     = intval($minelevation); // Minimum elevation for a pass
		$predict->timeRes    = 1; // Pass details: time resolution in seconds
		$predict->numEntries = 20; // Pass details: number of entries per pass
		// $predict->threshold  = -6; // Twilight threshold (sun must be at this lat or lower)

		// Get the passes and filter visible only, takes about 4 seconds for 10 days
		$results  = $predict->get_passes($sat, $qth, $now, 1);
		$filtered = $predict->filterVisiblePasses($results);

		// Get Date format
		if ($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$data['format'] = $custom_date_format . ' H:i:s';

		$data['filtered'] = $filtered;
		$data['zone'] = $timezone;

		return $data;

	}

	function calcSkedPass($tle) {
		if(!$this->user_model->authorize(3)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$yourgrid = $this->security->xss_clean($this->input->post('yourgrid'));
		$altitude = $this->security->xss_clean($this->input->post('altitude'));
		$date = $this->security->xss_clean($this->input->post('date'));
		$minelevation = $this->security->xss_clean($this->input->post('minelevation'));
		$timezone = $this->security->xss_clean($this->input->post('timezone'));

		$homePass =	$this->calcPass($tle, $yourgrid, $altitude, $date, $minelevation, $timezone);

		$skedgrid = $this->security->xss_clean($this->input->post('skedgrid'));
		$minskedelevation = $this->security->xss_clean($this->input->post('minskedelevation'));

		$skedPass = $this->calcPass($tle, $skedgrid, 0, $date, $minskedelevation, $timezone);

		// Get Date format
		if ($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$data['format'] = $custom_date_format . ' H:i:s';

		$data['overlaps'] = $this->findOverlaps($homePass, $skedPass);
		$data['zone'] = $timezone;
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
		// Convert a Y-m-d date to a day number

		// Convert date to Unix timestamp
		$timestamp = strtotime($date);
		if ($timestamp === false) {
			throw new Exception("Invalid date format. Expected Y-m-d.");
		}

		// Calculate the day number
		return Predict_Time::unix2daynum($timestamp, 0);
	}
}
