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
		if(!$this->load->is_loaded('DxccFlag')) {
			$this->load->library('DxccFlag');
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
		$pageData['sats'] = $this->logbookadvanced_model->get_worked_sats();
		$pageData['orbits'] = $this->bands->get_worked_orbits();
		$pageData['station_profile'] = $this->stations->all_of_user();
		$pageData['active_station_info'] = $station_profile->row();
		$pageData['homegrid'] = explode(',', $this->stations->find_gridsquare());
		$pageData['active_station_id'] = $active_station_id;

		$pageData['bands'] = $this->logbookadvanced_model->get_worked_bands();

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
			'assets/js/leaflet/L.MaidenheadColouredGridMap.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('logbookadvanced/index', $pageData);
		$this->load->view('interface_assets/footer', $footerData);
	}

	function mapParameters() {
		return array(
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
			'county' => xss_clean($this->input->post('county')),
			'cqzone' => xss_clean($this->input->post('cqzone')),
			'ituzone' => xss_clean($this->input->post('ituzone')),
			'qsoresults' => xss_clean($this->input->post('qsoresults')),
			'sats' => xss_clean($this->input->post('sats')),
			'orbits' => xss_clean($this->input->post('orbits')),
			'lotwSent' => xss_clean($this->input->post('lotwSent')),
			'lotwReceived' => xss_clean($this->input->post('lotwReceived')),
			'eqslSent' => xss_clean($this->input->post('eqslSent')),
			'eqslReceived' => xss_clean($this->input->post('eqslReceived')),
			'dclSent' => xss_clean($this->input->post('dclSent')),
			'dclReceived' => xss_clean($this->input->post('dclReceived')),
			'clublogSent' => xss_clean($this->input->post('clublogSent')),
			'clublogReceived' => xss_clean($this->input->post('clublogReceived')),
			'qslvia' => xss_clean($this->input->post('qslvia')),
			'sota' => xss_clean($this->input->post('sota')),
			'pota' => xss_clean($this->input->post('pota')),
			'wwff' => xss_clean($this->input->post('wwff')),
			'qslimages' => xss_clean($this->input->post('qslimages')),
			'dupes' => xss_clean($this->input->post('dupes')),
			'dupedate' => xss_clean($this->input->post('dupedate')),
			'dupemode' => xss_clean($this->input->post('dupemode')),
			'dupeband' => xss_clean($this->input->post('dupeband')),
			'dupesat' => xss_clean($this->input->post('dupesat')),
			'operator' => xss_clean($this->input->post('operator')),
			'contest' => xss_clean($this->input->post('contest')),
			'invalid' => xss_clean($this->input->post('invalid')),
			'continent' => xss_clean($this->input->post('continent')),
			'comment' => xss_clean($this->input->post('comment')),
			'qsoids' => xss_clean($this->input->post('qsoids')),
			'dok' => xss_clean($this->input->post('dok')),
			'qrzSent' => xss_clean($this->input->post('qrzSent')),
			'qrzReceived' => xss_clean($this->input->post('qrzReceived')),
			'distance' => xss_clean($this->input->post('distance')),
			'sortcolumn' => xss_clean($this->input->post('sortcolumn')),
			'sortdirection' => xss_clean($this->input->post('sortdirection'))
		);
	}

	public function search() {
		$this->load->model('logbookadvanced_model');

		$searchCriteria = $this->mapParameters();
		$qsos = [];

		foreach ($this->logbookadvanced_model->searchQsos($searchCriteria) as $qso) {
			$qsoArray = $qso->toArray();
			$flag = $this->dxccflag->get($qso->getDXCCId());
			if ($flag != null) {
				$qsoArray['flag'] = ' '.$flag;
			} else {
				$qsoArray['flag'] = '';
			}
			$qsos[] = $qsoArray;
		}

		header("Content-Type: application/json");
		print json_encode($qsos);
	}

	public function updateFromCallbook() {
		if(!clubaccess_check(9)) return;

		$this->load->model('logbook_model');
		$this->load->model('logbookadvanced_model');

		$qsoID[] = xss_clean($this->input->post('qsoID'));
		$qso = $this->logbookadvanced_model->getQsosForAdif(json_encode($qsoID), $this->session->userdata('user_id'))->row_array();
		if ($qso === null) {
			header("Content-Type: application/json");
			echo json_encode([]);
			return;
		}

		$callbook = $this->logbook_model->loadCallBook($qso['COL_CALL'], $this->config->item('use_fullname'));
		$gridsquareAccuracyCheck = xss_clean($this->input->post('gridsquareAccuracyCheck'));

		if ($callbook['callsign'] ?? "" !== "") {
			$this->load->model('stations');
			$active_station_id = $this->stations->find_active();
			$station_profile = $this->stations->profile($active_station_id)->row_array();
			$this->logbookadvanced_model->updateQsoWithCallbookInfo($qso['COL_PRIMARY_KEY'], $qso, $callbook, $gridsquareAccuracyCheck, $station_profile['station_gridsquare']);
			$qso = $this->logbookadvanced_model->getQsosForAdif(json_encode($qsoID), $this->session->userdata('user_id'))->row_array();
		}

		$qsoObj = new QSO($qso);		// Redirection via Object to clean/convert QSO (get rid of cols)
		$cleaned_qso = $qsoObj->toArray();	// And back to Array for the JSON

		$flag = $this->dxccflag->get($qsoObj->getDXCCId());
		if ($flag != null) {
			$cleaned_qso['flag'] = ' ' . $flag;
		} else {
			$cleaned_qso['flag'] = '';
		}

		header("Content-Type: application/json");
		echo json_encode($cleaned_qso);
	}

	function export_to_adif() {
		if(!clubaccess_check(9)) return;

		ini_set('memory_limit', '-1');
		set_time_limit(0);
		$this->load->model('logbookadvanced_model');

		$ids = xss_clean($this->input->post('id'));
		$sortcolumn = xss_clean($this->input->post('sortcolumn'));
		$sortdirection = xss_clean($this->input->post('sortdirection'));
		$user_id = (int)$this->session->userdata('user_id');

		$data['qsos'] = $this->logbookadvanced_model->getQsosForAdif($ids, $user_id, $sortcolumn, $sortdirection);

		$this->load->view('adif/data/exportall', $data);
	}

	function export_to_adif_params() {
		if(!clubaccess_check(9)) return;

		ini_set('memory_limit', '-1');
		set_time_limit(0);
		$this->load->model('logbookadvanced_model');

		$postdata = $this->mapParameters();
		$postdata['de'] = explode(',', $postdata['de']); // The reason for doing this different, is that the parameter is sent in differently than the regular search
		$postdata['qsoresults'] = 'All'; // We want all the QSOs regardless of what is set in the qsoresults, to be able to export all QSOs with the filter critera
		$data['qsos'] = $this->logbookadvanced_model->getSearchResult($postdata);

		$this->load->view('adif/data/exportall', $data);
	}

	function update_qsl() {
		if(!clubaccess_check(9)) return;

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
			$singleQso = $qso->toArray();
			$flag = $this->dxccflag->get($qso->getDXCCId());
			if ($flag != null) {
				$singleQso['flag'] = ' '.$flag;
			} else {
				$singleQso['flag'] = '';
			}
			$q[]=$singleQso;
		}

		header("Content-Type: application/json");
		print json_encode($q);
	}

	function update_qsl_received() {
		if(!clubaccess_check(9)) return;

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
			$singleQso = $qso->toArray();
			$flag = $this->dxccflag->get($qso->getDXCCId());
			if ($flag != null) {
				$singleQso['flag'] = ' '.$flag;
			} else {
				$singleQso['flag'] = '';
			}
			$q[]=$singleQso;
		}

		header("Content-Type: application/json");
		print json_encode($q);
	}

	public function startAtLabel() {
		$this->load->view('logbookadvanced/startatform');
	}

	public function qslSlideshow() {
		$cleanids = json_decode($this->security->xss_clean($this->input->post('ids')));
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
			'dx' => '*',
			'mode' => '',
			'band' => '',
			'qslSent' => '',
			'qslReceived' => '',
			'qslSentMethod' => '',
			'qslReceivedMethod' => '',
			'iota' => '',
			'dxcc' => '',
			'propmode' => '',
			'gridsquare' => '*',
			'state' => '*',
			'county' => '*',
			'cqzone' => 'All',
			'ituzone' => 'All',
			'qsoresults' => count(json_decode($this->input->post('ids',true))),
			'sats' => '',
			'orbits' => '',
			'lotwSent' => '',
			'lotwReceived' => '',
			'eqslSent' => '',
			'eqslReceived' => '',
			'dclSent' => '',
			'dclReceived' => '',
			'clublogSent' => '',
			'clublogReceived' => '',
			'qslvia' => '*',
			'sota' => '*',
			'pota' => '*',
			'wwff' => '*',
			'qslimages' => '',
			'operator' => '*',
			'contest' => '*',
			'continent' => '',
			'comment' => '*',
			'dok' => '*',
			'qrzSent' => '',
			'qrzReceived' => '',
			'distance' => '*',
			'qrzSent' => '',
			'qrzReceived' => '',
			'ids' => json_decode(xss_clean($this->input->post('ids'))),
			'qsoids' => xss_clean($this->input->post('qsoids')),
			'sortcolumn' => 'qsotime',
			'sortdirection' => 'desc'
		);

		$result = $this->logbookadvanced_model->getSearchResultArray($searchCriteria);
		$this->prepareMappedQSos($result);
	}

	public function mapQsos() {
        $this->load->model('logbookadvanced_model');

		$searchCriteria = $this->mapParameters();

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


		$data['distance'] = $this->qra->distance($locator1, $locator2, $measurement_base, $qso['COL_ANT_PATH']) . $var_dist;
		$data['bearing'] = $this->qra->get_bearing($locator1, $locator2, $qso['COL_ANT_PATH']) . "&#186;";
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
		$data['dxccFlag'] = $this->dxccflag->get($qso['COL_DXCC']);
		$data['id'] = $qso['COL_PRIMARY_KEY'];

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
		$data['dxccFlag'] = $this->dxccflag->get($qso['COL_DXCC']);
		$data['id'] = $qso['COL_PRIMARY_KEY'];

		return $data;
	}

	public function userOptions() {
		if(!clubaccess_check(9)) return;

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
		if(!clubaccess_check(9)) return;

		$json_string['datetime']['show'] = $this->def_boolean($this->input->post('datetime'),'true');
		$json_string['de']['show'] = $this->def_boolean($this->input->post('de'),'true');
		$json_string['dx']['show'] = $this->def_boolean($this->input->post('dx'),'true');
		$json_string['mode']['show'] = $this->def_boolean($this->input->post('mode'),'true');
		$json_string['rstr']['show'] = $this->def_boolean($this->input->post('rstr'));
		$json_string['rsts']['show'] = $this->def_boolean($this->input->post('rsts'));
		$json_string['band']['show'] = $this->def_boolean($this->input->post('band'));
		$json_string['myrefs']['show'] = $this->def_boolean($this->input->post('myrefs'));
		$json_string['name']['show'] = $this->def_boolean($this->input->post('name'));
		$json_string['qslvia']['show'] = $this->def_boolean($this->input->post('qslvia'));
		$json_string['qsl']['show'] = $this->def_boolean($this->input->post('qsl'));
		$json_string['lotw']['show'] = $this->def_boolean($this->input->post('lotw'));
		$json_string['eqsl']['show'] = $this->def_boolean($this->input->post('eqsl'));
		$json_string['clublog']['show'] = $this->def_boolean($this->input->post('clublog'));
		$json_string['qslmsgs']['show'] = $this->def_boolean($this->input->post('qslmsgs'));
		$json_string['qslmsgr']['show'] = $this->def_boolean($this->input->post('qslmsgr'));
		$json_string['dxcc']['show'] = $this->def_boolean($this->input->post('dxcc'));
		$json_string['state']['show'] = $this->def_boolean($this->input->post('state'));
		$json_string['county']['show'] = $this->def_boolean($this->input->post('county'));
		$json_string['cqzone']['show'] = $this->def_boolean($this->input->post('cqzone'));
		$json_string['ituzone']['show'] = $this->def_boolean($this->input->post('ituzone'));
		$json_string['iota']['show'] = $this->def_boolean($this->input->post('iota'));
		$json_string['pota']['show'] = $this->def_boolean($this->input->post('pota'));
		$json_string['operator']['show'] = $this->def_boolean($this->input->post('operator'));
		$json_string['comment']['show'] = $this->def_boolean($this->input->post('comment'));
		$json_string['propagation']['show'] = $this->def_boolean($this->input->post('propagation'));
		$json_string['contest']['show'] = $this->def_boolean($this->input->post('contest'));
		$json_string['gridsquare']['show'] = $this->def_boolean($this->input->post('gridsquare'));
		$json_string['sota']['show'] = $this->def_boolean($this->input->post('sota'));
		$json_string['dok']['show'] = $this->def_boolean($this->input->post('dok'));
		$json_string['sig']['show'] = $this->def_boolean($this->input->post('sig'));
		$json_string['wwff']['show'] = $this->def_boolean($this->input->post('wwff'));
		$json_string['continent']['show'] = $this->def_boolean($this->input->post('continent'));
		$json_string['qrz']['show'] = $this->def_boolean($this->input->post('qrz'));
		$json_string['profilename']['show'] = $this->def_boolean($this->input->post('profilename'));
		$json_string['stationpower']['show'] = $this->def_boolean($this->input->post('stationpower'));
		$json_string['distance']['show'] = $this->def_boolean($this->input->post('distance'));
		$json_string['antennaazimuth']['show'] = $this->def_boolean($this->input->post('antennaazimuth'));
		$json_string['antennaelevation']['show'] = $this->def_boolean($this->input->post('antennaelevation'));
		$json_string['region']['show'] = $this->def_boolean($this->input->post('region'));
		$json_string['qth']['show'] = $this->def_boolean($this->input->post('qth'));
		$json_string['frequency']['show'] = $this->def_boolean($this->input->post('frequency'));
		$json_string['dcl']['show'] = $this->def_boolean($this->input->post('dcl'));
		$json_string['last_modification']['show'] = $this->def_boolean($this->input->post('last_modification'));

		$obj['column_settings']= json_encode($json_string);

		$this->load->model('user_options_model');
		$this->user_options_model->set_option('LogbookAdvanced', 'LogbookAdvanced', $obj);


		$this->user_options_model->set_option('LogbookAdvancedMap', 'gridsquare_layer',  array('boolean' => $this->def_boolean(xss_clean($this->input->post('gridsquare_layer')))));
		$this->user_options_model->set_option('LogbookAdvancedMap', 'path_lines',  array('boolean' => $this->def_boolean(xss_clean($this->input->post('path_lines')))));
		$this->user_options_model->set_option('LogbookAdvancedMap', 'cqzones_layer',  array('boolean' => $this->def_boolean(xss_clean($this->input->post('cqzone_layer')))));
		$this->user_options_model->set_option('LogbookAdvancedMap', 'ituzones_layer',  array('boolean' => $this->def_boolean(xss_clean($this->input->post('ituzone_layer')))));
		$this->user_options_model->set_option('LogbookAdvancedMap', 'nightshadow_layer',  array('boolean' => $this->def_boolean(xss_clean($this->input->post('nightshadow_layer')))));
	}

	private function def_boolean($value, $default_value='false') {
		if ((($value ?? '') == '') || (($value != 'false') && ($value != 'true'))) {
			$value = $default_value;
		}
		return $value;
	}

	public function editDialog() {
		if(!clubaccess_check(9)) return;

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
		if(!clubaccess_check(9)) return;

		$ids = xss_clean($this->input->post('ids'));
		$column = xss_clean($this->input->post('column'));
		$value = xss_clean($this->input->post('value'));
		$value2 = xss_clean($this->input->post('value2'));
		$value3 = xss_clean($this->input->post('value3'));
		$value4 = xss_clean($this->input->post('value4'));

		$this->load->model('logbookadvanced_model');
		$this->logbookadvanced_model->saveEditedQsos($ids, $column, $value, $value2, $value3, $value4);

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
			$singleQso = $qso->toArray();
			$flag = $this->dxccflag->get($qso->getDXCCId());
			if ($flag != null) {
				$singleQso['flag'] = ' '.$flag;
			} else {
				$singleQso['flag'] = '';
			}
			$q[]=$singleQso;
		}

		header("Content-Type: application/json");
		print json_encode($q);
	}

	public function batchDeleteQsos() {
		if(!clubaccess_check(9)) return;

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

	public function helpDialog() {
		$this->load->view('logbookadvanced/help');
	}

	public function continentDialog() {
		$this->load->view('logbookadvanced/continentdialog');
	}

	public function stateDialog() {
		$this->load->library('Geojson');

		// Get supported countries from Geojson library
		$supported_states = $this->geojson::SUPPORTED_STATES;
		$country_names = array();

		foreach ($supported_states as $dxcc => $info) {
			if ($info['enabled']) {
				$country_names[] = $info['name'];
			}
		}

		sort($country_names);
		$data['supported_countries'] = implode(', ', $country_names);

		$this->load->view('logbookadvanced/statedialog', $data);
	}

	public function distanceDialog() {
		$this->load->view('logbookadvanced/distancedialog');
	}

	public function fixCqZones() {
		if(!clubaccess_check(9)) return;

		$ids = xss_clean($this->input->post('ids'));

		$this->load->model('logbookadvanced_model');
		$result = $this->logbookadvanced_model->fixCqZones($ids);

		header("Content-Type: application/json");
		print json_encode($result);
	}

	public function fixItuZones() {
		if(!clubaccess_check(9)) return;

		$ids = xss_clean($this->input->post('ids'));

		$this->load->model('logbookadvanced_model');
		$result = $this->logbookadvanced_model->fixItuZones($ids);

		header("Content-Type: application/json");
		print json_encode($result);
	}

	public function fixContinent() {
		$this->load->model('logbookadvanced_model');
		$result = $this->logbookadvanced_model->check_missing_continent();

		$data['result'] = $result;

		$data['type'] = 'continent';

		$this->load->view('logbookadvanced/showUpdateResult', $data);
	}

	public function fixStateProgress() {
		if(!clubaccess_check(9)) return;

		$this->load->model('logbook_model');
		$this->load->model('logbookadvanced_model');

		$qsoID = xss_clean($this->input->post('qsoID'));

		// Process single QSO state fix
		$result = $this->logbookadvanced_model->fixStateSingle($qsoID);

		// Get updated QSO data if successful
		if ($result['success']) {
			$qsoID_array = [$qsoID];
			$qso = $this->logbookadvanced_model->getQsosForAdif(json_encode($qsoID_array), $this->session->userdata('user_id'))->row_array();

			if ($qso !== null) {
				$qsoObj = new QSO($qso);
				$cleaned_qso = $qsoObj->toArray();

				$flag = $this->dxccflag->get($qsoObj->getDXCCId());
				if ($flag != null) {
					$cleaned_qso['flag'] = ' ' . $flag;
				} else {
					$cleaned_qso['flag'] = '';
				}

				$result['qso'] = $cleaned_qso;
			}
		}

		header("Content-Type: application/json");
		echo json_encode($result);
	}

	public function updateDistances() {
		if(!clubaccess_check(9)) return;

		$this->load->model('logbookadvanced_model');
		$result = $this->logbookadvanced_model->update_distances_batch();

		$data['result'] = $result;

		$data['type'] = 'distance';

		$this->load->view('logbookadvanced/showUpdateResult', $data);
	}

	public function callbookDialog() {
		$this->load->view('logbookadvanced/callbookdialog');
	}

	public function dbtoolsDialog() {
		$this->load->view('logbookadvanced/dbtoolsdialog');
	}

	public function checkDb() {
		if(!clubaccess_check(9)) return;

		$type = $this->input->post('type', true);
		$this->load->model('logbookadvanced_model');

		$data['result'] = $this->logbookadvanced_model->runCheckDb($type);
		if ($type == 'checkstate') {
			$this->load->view('logbookadvanced/statecheckresult', $data);
		} else {
			$data['type'] = $type;
			$this->load->view('logbookadvanced/checkresult', $data);
		}

	}

	public function fixStateBatch() {
		if(!clubaccess_check(9)) return;

		$this->load->model('logbook_model');
		$this->load->model('logbookadvanced_model');

		$dxcc = $this->input->post('dxcc', true);
		$data['country'] = $this->input->post('country', true);

		// Process for batch QSO state fix
		$result = $this->logbookadvanced_model->fixStateBatch($dxcc);

		$data['result'] = $result;

		$data['type'] = 'state';

		$this->load->view('logbookadvanced/showUpdateResult', $data);
	}

	public function openStateList() {
		if(!clubaccess_check(9)) return;

		$this->load->model('logbookadvanced_model');

		$data['dxcc'] = $this->input->post('dxcc', true);
		$data['country'] = $this->input->post('country', true);

		// Process for batch QSO state fix
		$data['qsos'] = $this->logbookadvanced_model->getStateListQsos($data['dxcc']);

		$this->load->view('logbookadvanced/showStateQsos', $data);
	}

	public function batchFix() {
		if(!clubaccess_check(9)) return;

		$type = $this->input->post('type', true);
		$this->load->model('logbookadvanced_model');
		$result = $this->logbookadvanced_model->batchFix($type);

		$data['result'] = $result;
		$data['type'] = $type;

		$this->load->view('logbookadvanced/showUpdateResult', $data);
	}

	function dupeSearchDialog() {
		if(!clubaccess_check(9)) return;

		$this->load->view('logbookadvanced/dupesearchdialog');
	}

	function fixDxccSelected() {
		if(!clubaccess_check(9)) return;

		$ids = xss_clean($this->input->post('ids'));

		$this->load->model('logbookadvanced_model');
		$result = $this->logbookadvanced_model->fixDxccSelected($ids);
		$result['message'] = '<div class="alert alert-' . ($result['count'] == 0 ? 'danger' : 'success') . '" role="alert">' . sprintf(__("DXCC updated for %d QSO(s)."), $result['count']) . '</div>';

		header("Content-Type: application/json");
		print json_encode($result);
	}

	function showMapForIncorrectGrid() {
		if(!clubaccess_check(9)) return;

		$this->load->model('logbookadvanced_model');
		$dxcc = $this->input->post('dxcc', true);

		$data['grids'] = $this->logbookadvanced_model->getGridsForDxcc($dxcc);
		$data['dxcc'] = $dxcc;
		$data['gridsquare'] = $this->input->post('gridsquare', true);
		$dxccname = $this->input->post('dxccname', true);
		$data['title'] = sprintf(__("Map for DXCC %s and gridsquare %s."), $dxccname, $data['gridsquare']);

		header("Content-Type: application/json");
		print json_encode($data);
	}

}
