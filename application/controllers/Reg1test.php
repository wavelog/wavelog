<?php

if (! defined('BASEPATH')) exit('No direct script access allowed');


/*
	This controller contains features for REG1TEST EDI
*/

class Reg1test extends CI_Controller {

	function __construct() {
		parent::__construct();

		// do authorization check
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		//define Page title
		$data['page_title'] = __("Export EDI");

		//Load models
		$this->load->model('Contesting_model');
		$this->load->model('stations');

		//get station profile
		$data['station_profile'] = $this->stations->all_of_user();
		$active_station_id = $this->stations->find_active();
		$station_profile = $this->stations->profile($active_station_id);

		//set station profile to view data
		$data['active_station_info'] = $station_profile->row();

		//provide REG1TEST JS
		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/reg1test.js'
		];

		//load view
		$this->load->view('interface_assets/header', $data);
		$this->load->view('reg1test/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function getContests() {

		//load models
		$this->load->model('Contesting_model');
		$this->load->model('stations');

		//get cleaned station id and year
		$station_id = $this->input->post('station_id', true);
		$year = $this->input->post('year', true);

		//deny acccess if station is not accessible
		if (!$this->stations->check_station_is_accessible($station_id)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			return;
		}

		//get logged contests for station
		$result = $this->Contesting_model->get_logged_contests($station_id, $year);

		//return result as json
		header('Content-Type: application/json');
		echo json_encode($result);
	}

	public function getYears() {

		//load model
		$this->load->model('Contesting_model');

		//get cleaned station id
		$station_id = $this->input->post('station_id', true);

		//get logged year for station id
		$result = $this->Contesting_model->get_logged_years($station_id);

		//return result as json
		header('Content-Type: application/json');
		echo json_encode($result);
	}

	public function getContestDates() {
		//load models
		$this->load->model('Contesting_model');
		$this->load->model('stations');

		//get cleaned station id
		$station_id = $this->input->post('station_id', true);

		//deny access if station is not accessible
		if (!$this->stations->check_station_is_accessible($station_id)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			return;
		}

		//get cleaned year, contest id, from and to values
		$year = $this->input->post('year', true);
		$contestid = $this->input->post('contestid', true);

		//get contestdates from database
		$result = $this->Contesting_model->get_contest_dates($station_id, $year, $contestid);

		//return result as json
		header('Content-Type: application/json');
		echo json_encode($result);
	}

	public function getContestBands() {

		//load models
		$this->load->model('Contesting_model');
		$this->load->model('stations');

		//get cleaned station id
		$station_id = $this->input->post('station_id', true);

		//deny access if station is not accessible
		if (!$this->stations->check_station_is_accessible($station_id)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			return;
		}

		//get cleaned contest id, from and to values
		$contestid = $this->input->post('contestid', true);
		$from = $this->input->post('contestdatesfrom', true);
		$to = $this->input->post('contestdatesto', true);

		//get contestdates from database
		$result = $this->Contesting_model->get_contest_bands($station_id, $contestid, $from, $to);

		//return result as json
		header('Content-Type: application/json');
		echo json_encode($result);
	}

	public function export() {
		// Set memory limit to unlimited to allow heavy usage
		ini_set('memory_limit', '-1');

		//load models
		$this->load->model('Contesting_model');
		$this->load->model('stations');
		$this->load->model('user_model');

		//Load distance calculator
		$this->load->library('Qra');

		//deny access if station is not accessible
		$station_id = $this->input->post('station_id', true);
		
		if (!$this->stations->check_station_is_accessible($station_id)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			return;
		}

		//get user input to define the export parameters
		$contest_id = $this->input->post('contestid', true);
		$from = $this->input->post('contestdatesfrom', true);
		$to = $this->input->post('contestdatesto', true);
		$band = $this->input->post('contestband', true);

		//load station
		$station = $this->stations->profile($station_id);
		$station = $station->row();

		//load userinfo
		$userinfo = $this->user_model->get_by_id($this->session->userdata('user_id'));
		$userinfo = $userinfo->row();

		//get qsos and set qso data for export
		$data['qsos'] = $this->Contesting_model->export_custom($from, $to, $contest_id, $station_id, $band);

		//set contest header data for export
		$data['band'] = $band;
		$data['qso_count'] = count($data['qsos']->result());
		$data['sentexchange'] = $this->input->post('sentexchange', true);
		$data['contest_id'] = $contest_id;
		$data['from'] = $from;
		$data['to'] = $to;
		$data['callsign'] = $station->station_callsign;
		$data['gridlocator'] = $station->station_gridsquare;
		$data['contestaddress1'] = $this->input->post('contestaddress1', true);
		$data['contestaddress2'] = $this->input->post('contestaddress2', true);
		$data['categoryoperator'] = $this->input->post('categoryoperator', true);
		$data['club'] = $this->input->post('club', true);
		$data['name'] = $userinfo->user_firstname . ' ' . $userinfo->user_lastname;
		$data['responsible_operator'] = $this->input->post('responsible_operator', true);
		$data['address1'] = $this->input->post('address1', true);
		$data['address2'] = $this->input->post('address2', true);
		$data['addresspostalcode'] = $this->input->post('addresspostalcode', true);
		$data['addresscity'] = $this->input->post('addresscity', true);
		$data['addresscountry'] = $this->input->post('addresscountry', true);
		$data['operatorphone'] = $this->input->post('operatorphone', true);
		$data['operators'] = $this->input->post('operators', true);
		$data['txequipment'] = $this->input->post('txequipment', true);
		$data['power'] = $this->input->post('power', true);
		$data['rxequipment'] = $this->input->post('rxequipment', true);
		$data['antenna'] = $this->input->post('antenna', true);
		$data['antennaheight'] = $this->input->post('antennaheight', true);
		$data['maxdistanceqso'] = $this->qra->getMaxDistanceQSO($station->station_gridsquare, $data['qsos'], "K");
		$data['bandmultiplicator'] = $this->input->post('bandmultiplicator', true);

		$data['soapbox'] = $this->input->post('soapbox', true);

		//load view for export
		$this->load->view('reg1test/export', $data);
	}
}
