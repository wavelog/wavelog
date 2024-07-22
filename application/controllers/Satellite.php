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
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }
	}

	public function index()
	{
		$this->load->model('satellite_model');

		$pageData['satellites'] = $this->satellite_model->get_all_satellites();

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
		$data['page_title'] = __("Create Satellite");
		$this->load->view('satellite/create', $data);
	}

	public function createSatellite() {
		$this->load->model('satellite_model');

		$this->satellite_model->add();
	}

	public function edit()
	{
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
		$this->load->model('satellite_model');

		$id = $this->security->xss_clean($this->input->post('id', true));
		$satellite['name'] 	= $this->security->xss_clean($this->input->post('name'));
		$satellite['exportname'] 	= $this->security->xss_clean($this->input->post('exportname'));
		$satellite['orbit'] 	= $this->security->xss_clean($this->input->post('orbit'));

        $this->satellite_model->saveupdatedsatellite($id, $satellite);
		echo json_encode(array('message' => 'OK'));
        return;
	}

	public function delete() {
	    $id = $this->input->post('id');
		$this->load->model('satellite_model');
		$this->satellite_model->delete($id);
	}

	public function deleteSatMode() {
	    $id = $this->input->post('id');
		$this->load->model('satellite_model');
		$this->satellite_model->deleteSatMode($id);
	}

	public function saveSatellite() {
		$id 				= $this->security->xss_clean($this->input->post('id'));
		$satellite['name'] 	= $this->security->xss_clean($this->input->post('name'));

		$this->load->model('satellite_model');
        $this->satellite_model->saveSatellite($id, $satellite);

		header('Content-Type: application/json');
        echo json_encode(array('message' => 'OK'));
		return;
    }

	public function saveSatModeChanges() {
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
		$this->load->model('satellite_model');
        $inserted_id = $this->satellite_model->insertSatelliteMode();

		header('Content-Type: application/json');
        echo json_encode(array('inserted_id' => $inserted_id));
		return;
	}

	public function satellite_data() {
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
		$sat = $this->security->xss_clean($this->input->post('sat'));
		$this->load->model('satellite_model');
		$satellite_data = $this->satellite_model->get_tle($sat);

		header('Content-Type: application/json');
		echo json_encode($satellite_data, JSON_FORCE_OBJECT);
	}

	public function pass() {
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

	public function searchpasses() {
		try {
			$result = $this->get_tle_for_predict();
			$this->calcpass($result);
		}
		catch (Exception $e) {
			header("Content-type: application/json");
			echo json_encode(['ok' => 'Error', 'message' => $e->getMessage() . $e->getCode()]);
		}
	}

	public function get_tle_for_predict() {
		$sat = $this->security->xss_clean($this->input->post('sat'));
		$this->load->model('satellite_model');
		return $this->satellite_model->get_tle($sat);
	}

	function calcpass($sat_tle) {
		require_once realpath(__DIR__ . "/../../predict/Predict.php");
		require_once realpath(__DIR__ . "/../../predict/Predict/Sat.php");
		require_once realpath(__DIR__ . "/../../predict/Predict/QTH.php");
		require_once realpath(__DIR__ . "/../../predict/Predict/Time.php");
		require_once realpath(__DIR__ . "/../../predict/Predict/TLE.php");

		// The observer or groundstation is called QTH in ham radio terms
		$predict  = new Predict();
		$qth      = new Predict_QTH();
		$qth->alt = $this->security->xss_clean($this->input->post('altitude')); // Altitude in meters

		$strQRA = $this->security->xss_clean($this->input->post('yourgrid'));

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
		$homecoordinates = $this->qra->qra2latlong($this->security->xss_clean($this->input->post('yourgrid')));

		$qth->lat = $homecoordinates[0];
		$qth->lon = $homecoordinates[1];

		$temp = preg_split('/\n/', $sat_tle->tle);

		$tle     = new Predict_TLE($sat_tle->satellite, $temp[0], $temp[1]); // Instantiate it
		$sat     = new Predict_Sat($tle); // Load up the satellite data

		$now     = Predict_Time::get_current_daynum(); // get the current time as Julian Date (daynum)

		// You can modify some preferences in Predict(), the defaults are below
		//
		$predict->minEle     = intval($this->security->xss_clean($this->input->post('minelevation'))); // Minimum elevation for a pass
		$predict->timeRes    = 1; // Pass details: time resolution in seconds
		$predict->numEntries = 20; // Pass details: number of entries per pass
		// $predict->threshold  = -6; // Twilight threshold (sun must be at this lat or lower)

		// Get the passes and filter visible only, takes about 4 seconds for 10 days
		$results  = $predict->get_passes($sat, $qth, $now, 1);
		$filtered = $predict->filterVisiblePasses($results);

		$zone   = $this->security->xss_clean($this->input->post('timezone'));
		$format = 'm-d-Y H:i:s';         // Time format from PHP's date() function

		$data['filtered'] = $filtered;
		$data['zone'] = $zone;
		$data['format'] = $format;
		$this->load->view('satellite/passtable', $data);
	}
}
