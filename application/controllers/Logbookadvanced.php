<?php

use Wavelog\QSLManager\QSO;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logbookadvanced extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url', 'psr4_autoloader'));

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}

	function index() {
		$this->load->model('stations');
		$this->load->model('logbookadvanced_model');
		$this->load->model('logbook_model');
		$this->load->model('bands');
		$this->load->model('iota');
		$this->load->model('dxcc');
		$this->load->model('user_options_model');

		$data = [];
		$data['page_title'] = __("Advanced logbook");
		$data['hasDatePicker'] = true;

		$userOptions = $this->user_options_model->get_options('LogbookAdvanced')->result();
		if (isset($userOptions[0])) {
			$data['options'] = $userOptions[0]->option_value;
		}

		$mapoptions['gridsquare_layer'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'gridsquare_layer','option_key'=>'boolean'))->row();
		$mapoptions['path_lines'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'path_lines','option_key'=>'boolean'))->row();
		$mapoptions['cqzones_layer'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'cqzones_layer','option_key'=>'boolean'))->row();
		$mapoptions['ituzones_layer'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'ituzones_layer','option_key'=>'boolean'))->row();
		$mapoptions['nightshadow_layer'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'nightshadow_layer','option_key'=>'boolean'))->row();

		$data['mapoptions'] = $mapoptions;
		$data['user_map_custom'] = $this->optionslib->get_map_custom();

		$active_station_id = $this->stations->find_active();
        $station_profile = $this->stations->profile($active_station_id);

		$pageData = [];
		$pageData['datePlaceholder'] = 'DD/MM/YYYY';
		$pageData['modes'] = $this->logbookadvanced_model->get_modes();
		$pageData['dxccarray'] = $this->logbook_model->fetchDxcc();
		$pageData['iotaarray'] = $this->logbook_model->fetchIota();
		$pageData['sats'] = $this->bands->get_worked_sats();
		$pageData['orbits'] = $this->bands->get_worked_orbits();
		$pageData['station_profile'] = $this->stations->all_of_user();
		$pageData['active_station_info'] = $station_profile->row();
		$pageData['homegrid'] = explode(',', $this->stations->find_gridsquare());
		$pageData['active_station_id'] = $active_station_id;

		$pageData['bands'] = $this->bands->get_worked_bands();

		// Get Date format
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$pageData['custom_date_format'] = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$pageData['custom_date_format'] = $this->config->item('qso_date_format');
		}

		switch ($pageData['custom_date_format']) {
			case "d/m/y": $pageData['custom_date_format'] = 'DD/MM/YY'; break;
			case "d/m/Y": $pageData['custom_date_format'] = 'DD/MM/YYYY'; break;
			case "m/d/y": $pageData['custom_date_format'] = 'MM/DD/YY'; break;
			case "m/d/Y": $pageData['custom_date_format'] = 'MM/DD/YYYY'; break;
			case "d.m.Y": $pageData['custom_date_format'] = 'DD.MM.YYYY'; break;
			case "y/m/d": $pageData['custom_date_format'] = 'YY/MM/DD'; break;
			case "Y-m-d": $pageData['custom_date_format'] = 'YYYY-MM-DD'; break;
			case "M d, Y": $pageData['custom_date_format'] = 'MMM DD, YYYY'; break;
			case "M d, y": $pageData['custom_date_format'] = 'MMM DD, YY'; break;
			default: $pageData['custom_date_format'] = 'DD/MM/YYYY';
		}

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/moment.min.js',
			'assets/js/datetime-moment.js',
			'assets/js/sections/logbookadvanced.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/logbookadvanced.js")),
			'assets/js/sections/logbookadvanced_edit.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/logbookadvanced_edit.js")),
			'assets/js/sections/logbookadvanced_map.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/logbookadvanced_map.js")),
			'assets/js/sections/cqmap_geojson.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/cqmap_geojson.js")),
			'assets/js/sections/itumap_geojson.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/itumap_geojson.js")),
			'assets/js/leaflet/L.Terminator.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/leaflet/L.Terminator.js")),
			'assets/js/leaflet/geocoding.js',
			'assets/js/globe/globe.gl.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/globe/globe.gl.js")),
			'assets/js/bootstrap-multiselect.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/bootstrap-multiselect.js")),
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('logbookadvanced/index', $pageData);
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function search() {
		$this->load->model('logbookadvanced_model');

		$searchCriteria = array(
			'user_id' => (int)$this->session->userdata('user_id'),
			'dateFrom' => xss_clean($this->input->post('dateFrom')),
			'dateTo' => xss_clean($this->input->post('dateTo')),
			'de' => xss_clean($this->input->post('de')),
			'dx' => xss_clean($this->input->post('dx')),
			'mode' => xss_clean($this->input->post('mode')),
			'band' => xss_clean($this->input->post('band')),
			'qslSent' => xss_clean($this->input->post('qslSent')),
			'qslReceived' => xss_clean($this->input->post('qslReceived')),
			'qslSentMethod' => xss_clean($this->input->post('qslSentMethod')),
			'qslReceivedMethod' => xss_clean($this->input->post('qslReceivedMethod')),
			'iota' => xss_clean($this->input->post('iota')),
			'dxcc' => xss_clean($this->input->post('dxcc')),
			'propmode' => xss_clean($this->input->post('propmode')),
			'gridsquare' => xss_clean($this->input->post('gridsquare')),
			'state' => xss_clean($this->input->post('state')),
			'cqzone' => xss_clean($this->input->post('cqzone')),
			'ituzone' => xss_clean($this->input->post('ituzone')),
			'qsoresults' => xss_clean($this->input->post('qsoresults')),
			'sats' => xss_clean($this->input->post('sats')),
			'orbits' => xss_clean($this->input->post('orbits')),
			'lotwSent' => xss_clean($this->input->post('lotwSent')),
			'lotwReceived' => xss_clean($this->input->post('lotwReceived')),
			'eqslSent' => xss_clean($this->input->post('eqslSent')),
			'eqslReceived' => xss_clean($this->input->post('eqslReceived')),
			'clublogSent' => xss_clean($this->input->post('clublogSent')),
			'clublogReceived' => xss_clean($this->input->post('clublogReceived')),
			'qslvia' => xss_clean($this->input->post('qslvia')),
			'sota' => xss_clean($this->input->post('sota')),
			'pota' => xss_clean($this->input->post('pota')),
			'wwff' => xss_clean($this->input->post('wwff')),
			'qslimages' => xss_clean($this->input->post('qslimages')),
			'dupes' => xss_clean($this->input->post('dupes')),
			'operator' => xss_clean($this->input->post('operator')),
			'contest' => xss_clean($this->input->post('contest')),
			'invalid' => xss_clean($this->input->post('invalid')),
			'continent' => xss_clean($this->input->post('continent')),
		);

		$qsos = [];

		foreach ($this->logbookadvanced_model->searchQsos($searchCriteria) as $qso) {
			$qsos[] = $qso->toArray();
		}

		header("Content-Type: application/json");
		print json_encode($qsos);
	}

	public function updateFromCallbook() {
		$this->load->model('logbook_model');
		$this->load->model('logbookadvanced_model');

		$qsoID = xss_clean($this->input->post('qsoID'));
		$qso = $this->logbook_model->qso_info($qsoID)->row_array();
		if ($qso === null) {
			header("Content-Type: application/json");
			echo json_encode([]);
			return;
		}

		$callbook = $this->logbook_model->loadCallBook($qso['COL_CALL'], $this->config->item('use_fullname'));

		if ($callbook['callsign'] ?? "" !== "") {
			$this->logbookadvanced_model->updateQsoWithCallbookInfo($qsoID, $qso, $callbook);
			$qso = $this->logbook_model->qso_info($qsoID)->row_array();
		}

		$qsoObj = new QSO($qso);

		header("Content-Type: application/json");
		echo json_encode($qsoObj->toArray());
	}

	function export_to_adif() {
		ini_set('memory_limit', '-1');
		set_time_limit(0);
		$this->load->model('logbookadvanced_model');

		$ids = xss_clean($this->input->post('id'));
		$sortorder = xss_clean($this->input->post('sortorder'));
		$user_id = (int)$this->session->userdata('user_id');

		$data['qsos'] = $this->logbookadvanced_model->getQsosForAdif($ids, $user_id, $sortorder);

		$this->load->view('adif/data/exportall', $data);
	}

	function export_to_adif_params() {
		ini_set('memory_limit', '-1');
		set_time_limit(0);
		$this->load->model('logbookadvanced_model');

		$postdata = $this->input->post();
		$postdata['user_id'] = (int)$this->session->userdata('user_id');
		$postdata['qsoresults'] = 'All';
		$postdata['de'] = explode(',', $postdata['de']);
		$data['qsos'] = $this->logbookadvanced_model->getSearchResult($postdata);

		$this->load->view('adif/data/exportall', $data);
	}

	function update_qsl() {
		$this->load->model('logbookadvanced_model');

		$ids = xss_clean($this->input->post('id'));
		$user_id = (int)$this->session->userdata('user_id');
		$method = xss_clean($this->input->post('method'));
		$sent = xss_clean($this->input->post('sent'));

		$status = $this->logbookadvanced_model->updateQsl($ids, $user_id, $method, $sent);

		$data = $this->logbookadvanced_model->getQsosForAdif($ids, $user_id);

		$results = $data->result('array');

        $qsos = [];
        foreach ($results as $data) {
            $qsos[] = new QSO($data);
        }

		$q = [];
		foreach ($qsos as $qso) {
			$q[] = $qso->toArray();
		}

		header("Content-Type: application/json");
		print json_encode($q);
	}

	function update_qsl_received() {
		$this->load->model('logbookadvanced_model');

		$ids = xss_clean($this->input->post('id'));
		$user_id = (int)$this->session->userdata('user_id');
		$method = xss_clean($this->input->post('method'));
		$sent = xss_clean($this->input->post('sent'));

		$status = $this->logbookadvanced_model->updateQslReceived($ids, $user_id, $method, $sent);

		$data = $this->logbookadvanced_model->getQsosForAdif($ids, $user_id);

		$results = $data->result('array');

        $qsos = [];
        foreach ($results as $data) {
            $qsos[] = new QSO($data);
        }

		$q = [];
		foreach ($qsos as $qso) {
			$q[] = $qso->toArray();
		}

		header("Content-Type: application/json");
		print json_encode($q);
	}

	public function startAtLabel() {
		$this->load->view('logbookadvanced/startatform');
	}

	public function qslSlideshow() {
		$cleanids = $this->security->xss_clean($this->input->post('ids'));
        $this->load->model('logbookadvanced_model');
        $data['qslimages'] = $this->logbookadvanced_model->getQslsForQsoIds($cleanids);
        $this->load->view('logbookadvanced/qslcarousel', $data);
	}

	public function mapSelectedQsos() {
		$this->load->model('logbookadvanced_model');

		$searchCriteria = array(
			'user_id' => (int)$this->session->userdata('user_id'),
			'dateFrom' => '',
			'dateTo' => '',
			'de' => $this->input->post('de'),
			'dx' => '',
			'mode' => '',
			'band' => '',
			'qslSent' => '',
			'qslReceived' => '',
			'qslSentMethod' => '',
			'qslReceivedMethod' => '',
			'iota' => '',
			'dxcc' => '',
			'propmode' => '',
			'gridsquare' => '',
			'state' => '',
			'cqzone' => '',
			'ituzone' => '',
			'qsoresults' => count($this->input->post('ids')),
			'sats' => '',
			'orbits' => '',
			'lotwSent' => '',
			'lotwReceived' => '',
			'eqslSent' => '',
			'eqslReceived' => '',
			'clublogSent' => '',
			'clublogReceived' => '',
			'qslvia' => '',
			'sota' => '',
			'pota' => '',
			'wwff' => '',
			'qslimages' => '',
			'operator' => '',
			'contest' => '',
			'continent' => '',
			'ids' => xss_clean($this->input->post('ids'))
		);

		$result = $this->logbookadvanced_model->getSearchResultArray($searchCriteria);
		$this->prepareMappedQSos($result);
	}

	public function mapQsos() {
        $this->load->model('logbookadvanced_model');

		$searchCriteria = array(
			'user_id' => (int)$this->session->userdata('user_id'),
			'dateFrom' => xss_clean($this->input->post('dateFrom')),
			'dateTo' => xss_clean($this->input->post('dateTo')),
			'de' => xss_clean($this->input->post('de')),
			'dx' => xss_clean($this->input->post('dx')),
			'mode' => xss_clean($this->input->post('mode')),
			'band' => xss_clean($this->input->post('band')),
			'qslSent' => xss_clean($this->input->post('qslSent')),
			'qslReceived' => xss_clean($this->input->post('qslReceived')),
			'qslSentMethod' => xss_clean($this->input->post('qslSentMethod')),
			'qslReceivedMethod' => xss_clean($this->input->post('qslReceivedMethod')),
			'iota' => xss_clean($this->input->post('iota')),
			'dxcc' => xss_clean($this->input->post('dxcc')),
			'propmode' => xss_clean($this->input->post('propmode')),
			'gridsquare' => xss_clean($this->input->post('gridsquare')),
			'state' => xss_clean($this->input->post('state')),
			'cqzone' => xss_clean($this->input->post('cqzone')),
			'ituzone' => xss_clean($this->input->post('ituzone')),
			'qsoresults' => xss_clean($this->input->post('qsoresults')),
			'sats' => xss_clean($this->input->post('sats')),
			'orbits' => xss_clean($this->input->post('orbits')),
			'lotwSent' => xss_clean($this->input->post('lotwSent')),
			'lotwReceived' => xss_clean($this->input->post('lotwReceived')),
			'eqslSent' => xss_clean($this->input->post('eqslSent')),
			'eqslReceived' => xss_clean($this->input->post('eqslReceived')),
			'clublogSent' => xss_clean($this->input->post('clublogSent')),
			'clublogReceived' => xss_clean($this->input->post('clublogReceived')),
			'qslvia' => xss_clean($this->input->post('qslvia')),
			'sota' => xss_clean($this->input->post('sota')),
			'pota' => xss_clean($this->input->post('pota')),
			'wwff' => xss_clean($this->input->post('wwff')),
			'operator' => xss_clean($this->input->post('operator')),
			'contest' => xss_clean($this->input->post('contest')),
			'qslimages' => xss_clean($this->input->post('qslimages')),
			'continent' => xss_clean($this->input->post('continent')),
		);

		$result = $this->logbookadvanced_model->getSearchResultArray($searchCriteria);
		$this->prepareMappedQSos($result);
	}

	public function prepareMappedQSos($qsos) {
		if ($this->session->userdata('user_measurement_base') == NULL) {
			$measurement_base = $this->config->item('measurement_base');
		}
		else {
			$measurement_base = $this->session->userdata('user_measurement_base');
		}

		// Get Date format
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		switch ($measurement_base) {
			case 'M':
				$var_dist = " miles";
				break;
			case 'N':
				$var_dist = " nautic miles";
				break;
			case 'K':
				$var_dist = " kilometers";
				break;
		}

		$mappedcoordinates = array();
		foreach ($qsos as $qso) {
			if (!empty($qso['station_gridsquare']) && $this->isValidMaidenheadGrid($qso['station_gridsquare'])) {
				if (!empty($qso['COL_GRIDSQUARE'])  || !empty($qso['COL_VUCC_GRIDS'])) {
					$mappedcoordinates[] = $this->calculate($qso, ($qso['station_gridsquare'] ?? ''), ($qso['COL_GRIDSQUARE'] ?? '') == '' ? $qso['COL_VUCC_GRIDS'] : $qso['COL_GRIDSQUARE'], $measurement_base, $var_dist, $custom_date_format);
				} else {
					if (!empty($qso['lat'])  && !empty($qso['long'])) {
						$mappedcoordinates[] = $this->calculateCoordinates($qso, $qso['lat'], $qso['long'], ($qso['station_gridsquare'] ?? ''), $measurement_base, $var_dist, $custom_date_format);
					}
				}
			}
		}

		header("Content-Type: application/json");
		print json_encode($mappedcoordinates);
	}

	function isValidMaidenheadGrid($grid) {
		if (strlen($grid) == 4)  $grid .= "LL";	// Only 4 Chars? Fill with center "LL" as only A-R allowed
		if (strlen($grid) == 6)  $grid .= "55";	// Only 6 Chars? Fill with center "55"
		if (strlen($grid) == 8)  $grid .= "LL";	// Only 8 Chars? Fill with center "LL" as only A-R allowed
		// Regex pattern to match a single valid Maidenhead grid square (with optional extensions)
		$singleGridPattern = '[A-R]{2}[0-9]{2}([A-X]{2})?([0-9]{2})?([A-X]{2})?';

		// Regex to match VUCC grids, allowing multiple grids separated by commas
		$compoundPattern = '/^(' . $singleGridPattern . ')(,' . $singleGridPattern . ')*$/i';

		// Check if the overall format is valid
		if (preg_match($compoundPattern, $grid) !== 1) {
			return false;
		}

		// Split the string by commas to count the number of grid squares
		$gridArray = explode(',', $grid);
		$gridCount = count($gridArray);

		// Validate if the count is 1, 2, or 4
		if ($gridCount === 1 || $gridCount === 2 || $gridCount === 4) {
			return true;
		}

		// Return false if it's not exactly 1, 2, or 4 grids
		return false;
	}

	public function calculate($qso, $locator1, $locator2, $measurement_base, $var_dist, $custom_date_format) {
		if(!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}
		$this->load->model('logbook_model');

		$data['distance'] = $this->qra->distance($locator1, $locator2, $measurement_base) . $var_dist;
		$data['bearing'] = $this->qra->get_bearing($locator1, $locator2) . "&#186;";
		$latlng1 = $this->qra->qra2latlong($locator1);
		$latlng2 = $this->qra->qra2latlong($locator2);
		$latlng1[0] = number_format((float)$latlng1[0], 3, '.', '');;
		$latlng1[1] = number_format((float)$latlng1[1], 3, '.', '');;
		$latlng2[0] = number_format((float)$latlng2[0], 3, '.', '');;
		$latlng2[1] = number_format((float)$latlng2[1], 3, '.', '');;

		$data['latlng1'] = $latlng1;
		$data['latlng2'] = $latlng2;

		$data['callsign'] = $qso['COL_CALL'];
		$data['band'] = $qso['COL_BAND'];
		$data['mode'] = $qso['COL_MODE'];
		$data['gridsquare'] = $locator2;
		$data['mygridsquare'] = $locator1;
		$data['mycallsign'] = $qso['station_callsign'];
		$data['datetime'] = date($custom_date_format, strtotime($qso['COL_TIME_ON'])). date(' H:i',strtotime($qso['COL_TIME_ON']));
		$data['satname'] = $qso['COL_SAT_NAME'];
		$data['orbit'] = $qso['orbit'];
		$data['confirmed'] = ($this->logbook_model->qso_is_confirmed($qso)==true) ? true : false;

		return $data;
	}

	public function calculateCoordinates($qso, $lat, $long, $mygrid, $measurement_base, $var_dist, $custom_date_format) {
		if(!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}
		$this->load->model('logbook_model');

		$latlng1 = $this->qra->qra2latlong($mygrid);
		$latlng2[0] = $lat;
		$latlng2[1] = $long;
		$latlng1[0] = number_format((float)$latlng1[0], 3, '.', '');;
		$latlng1[1] = number_format((float)$latlng1[1], 3, '.', '');;
		$latlng2[0] = number_format((float)$latlng2[0], 3, '.', '');;
		$latlng2[1] = number_format((float)$latlng2[1], 3, '.', '');;

		$data['latlng1'] = $latlng1;
		$data['latlng2'] = $latlng2;
		$data['callsign'] = $qso['COL_CALL'];
		$data['band'] = $qso['COL_BAND'];
		$data['mode'] = $qso['COL_MODE'];
		$data['mygridsquare'] = $mygrid;
		$data['mycallsign'] = $qso['station_callsign'];
		$data['datetime'] = date($custom_date_format, strtotime($qso['COL_TIME_ON'])). date(' H:i',strtotime($qso['COL_TIME_ON']));
		$data['satname'] = $qso['COL_SAT_NAME'];
		$data['orbit'] = $qso['orbit'];
		$data['confirmed'] = ($this->logbook_model->qso_is_confirmed($qso)==true) ? true : false;

		return $data;
	}

	public function userOptions() {
		$this->load->model('user_options_model');
		$userOptions = $this->user_options_model->get_options('LogbookAdvanced')->result();
		if (isset($userOptions[0])) {
			$data['options'] = $options = json_decode($userOptions[0]->option_value);
		} else {
			$data['options'] = null;
		}

		$mapoptions['gridsquare_layer'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'gridsquare_layer','option_key'=>'boolean'))->row();
		$mapoptions['path_lines'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'path_lines','option_key'=>'boolean'))->row();
		$mapoptions['cqzones_layer'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'cqzones_layer','option_key'=>'boolean'))->row();
		$mapoptions['ituzones_layer'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'ituzones_layer','option_key'=>'boolean'))->row();
		$mapoptions['nightshadow_layer'] = $this->user_options_model->get_options('LogbookAdvancedMap',array('option_name'=>'nightshadow_layer','option_key'=>'boolean'))->row();

		$data['mapoptions'] = $mapoptions;

		$this->load->view('logbookadvanced/useroptions', $data);
	}

	public function setUserOptions() {
		$json_string['datetime']['show'] = $this->input->post('datetime');
		$json_string['de']['show'] = $this->input->post('de');
		$json_string['dx']['show'] = $this->input->post('dx');
		$json_string['mode']['show'] = $this->input->post('mode');
		$json_string['rstr']['show'] = $this->input->post('rstr');
		$json_string['rsts']['show'] = $this->input->post('rsts');
		$json_string['band']['show'] = $this->input->post('band');
		$json_string['myrefs']['show'] = $this->input->post('myrefs');
		$json_string['name']['show'] = $this->input->post('name');
		$json_string['qslvia']['show'] = $this->input->post('qslvia');
		$json_string['qsl']['show'] = $this->input->post('qsl');
		$json_string['lotw']['show'] = $this->input->post('lotw');
		$json_string['eqsl']['show'] = $this->input->post('eqsl');
		$json_string['clublog']['show'] = $this->input->post('clublog');
		$json_string['qslmsg']['show'] = $this->input->post('qslmsg');
		$json_string['dxcc']['show'] = $this->input->post('dxcc');
		$json_string['state']['show'] = $this->input->post('state');
		$json_string['cqzone']['show'] = $this->input->post('cqzone');
		$json_string['ituzone']['show'] = $this->input->post('ituzone');
		$json_string['iota']['show'] = $this->input->post('iota');
		$json_string['pota']['show'] = $this->input->post('pota');
		$json_string['operator']['show'] = $this->input->post('operator');
		$json_string['comment']['show'] = $this->input->post('comment');
		$json_string['propagation']['show'] = $this->input->post('propagation');
		$json_string['contest']['show'] = $this->input->post('contest');
		$json_string['gridsquare']['show'] = $this->input->post('gridsquare');
		$json_string['sota']['show'] = $this->input->post('sota');
		$json_string['dok']['show'] = $this->input->post('dok');
		$json_string['sig']['show'] = $this->input->post('sig');
		$json_string['wwff']['show'] = $this->input->post('wwff');
		$json_string['continent']['show'] = $this->input->post('continent');
		$json_string['qrz']['show'] = $this->input->post('qrz');
		$json_string['profilename']['show'] = $this->input->post('profilename');
		$json_string['stationpower']['show'] = $this->input->post('stationpower');
		$json_string['distance']['show'] = $this->input->post('distance');

		$obj['column_settings']= json_encode($json_string);

		$this->load->model('user_options_model');
		$this->user_options_model->set_option('LogbookAdvanced', 'LogbookAdvanced', $obj);


		$this->user_options_model->set_option('LogbookAdvancedMap', 'gridsquare_layer',  array('boolean' => xss_clean($this->input->post('gridsquare_layer'))));
		$this->user_options_model->set_option('LogbookAdvancedMap', 'path_lines',  array('boolean' => xss_clean($this->input->post('path_lines'))));
		$this->user_options_model->set_option('LogbookAdvancedMap', 'cqzones_layer',  array('boolean' => xss_clean($this->input->post('cqzone_layer'))));
		$this->user_options_model->set_option('LogbookAdvancedMap', 'ituzones_layer',  array('boolean' => xss_clean($this->input->post('ituzone_layer'))));
		$this->user_options_model->set_option('LogbookAdvancedMap', 'nightshadow_layer',  array('boolean' => xss_clean($this->input->post('nightshadow_layer'))));
	}

	public function editDialog() {
		$this->load->model('bands');
		$this->load->model('modes');
		$this->load->model('logbookadvanced_model');
		$this->load->model('contesting_model');

		$data['stateDxcc'] = $this->logbookadvanced_model->getPrimarySubdivisonsDxccs();

		$data['modes'] = $this->modes->active();
		$data['bands'] = $this->bands->get_user_bands_for_qso_entry();
		$data['contests'] = $this->contesting_model->getActivecontests();
		$this->load->view('logbookadvanced/edit', $data);
	}

	public function saveBatchEditQsos() {
		$ids = xss_clean($this->input->post('ids'));
		$column = xss_clean($this->input->post('column'));
		$value = xss_clean($this->input->post('value'));
		$value2 = xss_clean($this->input->post('value2'));

		$this->load->model('logbookadvanced_model');
		$this->logbookadvanced_model->saveEditedQsos($ids, $column, $value, $value2);

		$data = $this->logbookadvanced_model->getQsosForAdif($ids, $this->session->userdata('user_id'));

		$results = $data->result('array');

        $qsos = [];
        foreach ($results as $data) {
            $qsos[] = new QSO($data);
        }

		$q = [];
		// Get Date format
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		foreach ($qsos as $qso) {
			$q[] = $qso->toArray();
		}

		header("Content-Type: application/json");
		print json_encode($q);
	}

	public function batchDeleteQsos() {
		$ids = xss_clean($this->input->post('ids'));

		$this->load->model('logbookadvanced_model');
		$this->logbookadvanced_model->deleteQsos($ids);
	}

	public function getSubdivisionsForDxcc() {
		$dxcc = xss_clean($this->input->post('dxcc'));

		$this->load->model('logbookadvanced_model');
		$result = $this->logbookadvanced_model->getSubdivisons($dxcc);

		header("Content-Type: application/json");
		print json_encode($result);
	}
}
