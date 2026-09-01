<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dbtools extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url', 'psr4_autoloader'));

		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}

	function index() {
		$this->load->model('stations');

		$data = [];
		$data['page_title'] = __("Database Tools");

		$data['user_map_custom'] = $this->optionslib->get_map_custom();

		$pageData = [];
		$pageData['station_profile'] = $this->stations->all_of_user();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/dbtools.js',
			'assets/js/leaflet/L.MaidenheadColouredGridMap.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('dbtools/index', $pageData);
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function fixContinent() {
		if(!clubaccess_check(9)) return;

		$this->load->model('dbtools_model');

		$stationid = $this->input->post('stationid', true);
		$result = $this->dbtools_model->check_missing_continent($stationid);

		$data['result'] = $result;

		$data['type'] = 'continent';

		$this->load->view('dbtools/showUpdateResult', $data);
	}

	public function updateDistances() {
		if(!clubaccess_check(9)) return;

		$stationid = $this->input->post('stationid', true);

		$this->load->model('dbtools_model');
		$result = $this->dbtools_model->update_distances_batch($stationid);

		$data['result'] = $result;

		$data['type'] = 'distance';

		$this->load->view('dbtools/showUpdateResult', $data);
	}

	public function checkDb() {
		if(!clubaccess_check(9)) return;

		$type = $this->input->post('type', true);
		$stationid = $this->input->post('stationid', true);
		$this->load->model('dbtools_model');

		$data['result'] = $this->dbtools_model->runCheckDb($type, $stationid);
		if ($type == 'checkstate') {
			$this->load->view('dbtools/statecheckresult', $data);
		} else {
			$data['type'] = $type;
			$this->load->view('dbtools/checkresult', $data);
		}

	}

	public function fixStateBatch() {
		if(!clubaccess_check(9)) return;

		$this->load->model('dbtools_model');

		$dxcc = $this->input->post('dxcc', true);
		$stationid = $this->input->post('stationid', true);
		$data['country'] = $this->input->post('country', true);

		// Process for batch QSO state fix
		$result = $this->dbtools_model->fixStateBatch($dxcc, $stationid);

		$data['result'] = $result;

		$data['type'] = 'state';

		$this->load->view('dbtools/showUpdateResult', $data);
	}

	public function fixStateAll() {
		if(!clubaccess_check(9)) return;

		$this->load->model('dbtools_model');

		$stationid = $this->input->post('stationid', true);

		// Run the state fix for every DXCC in one pass and aggregate the result
		$result = $this->dbtools_model->fixStateAll($stationid);

		$data['result'] = $result;
		$data['type'] = 'stateall';

		$this->load->view('dbtools/showUpdateResult', $data);
	}

	public function openStateList() {
		if(!clubaccess_check(9)) return;

		$this->load->model('dbtools_model');

		$data['dxcc'] = $this->input->post('dxcc', true);
		$data['country'] = $this->input->post('country', true);
		$data['stationid'] = $this->input->post('stationid', true);

		// Process for batch QSO state fix
		$data['qsos'] = $this->dbtools_model->getStateListQsos($data['dxcc'], $data['stationid']);

		$this->load->view('dbtools/showStateQsos', $data);
	}

	// Fetches the QSOs with missing gridsquare and renders the list used by the callbook lookup dialog
	public function missingGridList() {
		if(!clubaccess_check(9)) return;

		$this->load->model('dbtools_model');

		$data['stationid'] = $this->input->post('stationid', true);
		$data['qsos'] = $this->dbtools_model->getMissingGridQsos($data['stationid']);

		$this->load->view('dbtools/missinggridlist', $data);
	}

	// Does the callbook lookup for a single QSO and sets the gridsquare if one is found.
	// Called one QSO at a time from the dialog, so progress can be shown and the run can be cancelled.
	public function lookupMissingGrid() {
		if(!clubaccess_check(9)) return;

		$this->load->model('dbtools_model');

		$result = $this->dbtools_model->lookup_missing_grid((int)$this->input->post('qsoID', true));

		header("Content-Type: application/json");
		print json_encode($result);
	}

	function fixDxccSelected() {
		if(!clubaccess_check(9)) return;

		$ids = xss_clean($this->input->post('ids'));

		$this->load->model('dbtools_model');
		$result = $this->dbtools_model->fixDxccSelected($ids);
		$result['message'] = '<div class="alert alert-' . ($result['count'] == 0 ? 'danger' : 'success') . '" role="alert">' . sprintf(__("DXCC updated for %d QSO(s)."), $result['count']) . '</div>';

		header("Content-Type: application/json");
		print json_encode($result);
	}

	function showMapForIncorrectGrid() {
		if(!clubaccess_check(9)) return;

		$this->load->model('dbtools_model');
		$dxcc = $this->input->post('dxcc', true);

		$data['grids'] = $this->dbtools_model->getGridsForDxcc($dxcc);
		$data['dxcc'] = $dxcc;
		$data['gridsquare'] = $this->input->post('gridsquare', true);
		$dxccname = $this->input->post('dxccname', true);
		$data['title'] = sprintf(__("Map for DXCC %s and gridsquare %s."), $dxccname, $data['gridsquare']);

		header("Content-Type: application/json");
		print json_encode($data);
	}
}
