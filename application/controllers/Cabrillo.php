<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/*
	This controller contains features for Cabrillo
*/

class Cabrillo extends CI_Controller {

	public function __construct() {
        parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$data['page_title'] = __("Export Cabrillo");

		$this->load->model('Contesting_model');
		$this->load->model('stations');

		$data['station_profile'] = $this->stations->all_of_user();
		$active_station_id = $this->stations->find_active();
		$station_profile = $this->stations->profile($active_station_id);

		$data['active_station_info'] = $station_profile->row();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/cabrillo.js'
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('cabrillo/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function getContests() {
		$this->load->model('Contesting_model');

		$station_id = $this->security->xss_clean($this->input->post('station_id'));
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($station_id)) {
			$year = $this->security->xss_clean($this->input->post('year'));
			$result = $this->Contesting_model->get_logged_contests($station_id, $year);

			header('Content-Type: application/json');
			echo json_encode($result);
		} else {
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard');
		}
	}

	public function getYears() {
		$this->load->model('Contesting_model');
		$station_id = $this->security->xss_clean($this->input->post('station_id'));

		$result = $this->Contesting_model->get_logged_years($station_id);

		header('Content-Type: application/json');
		echo json_encode($result);
	}

	public function getContestDates() {
		$this->load->model('Contesting_model');
		$station_id = $this->security->xss_clean($this->input->post('station_id'));
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($station_id)) {
			$year = $this->security->xss_clean($this->input->post('year'));
			$contestid = $this->security->xss_clean($this->input->post('contestid'));

			$result = $this->Contesting_model->get_contest_dates($station_id, $year, $contestid);

			header('Content-Type: application/json');
			echo json_encode($result);
		} else {
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard');
		}
	}

	public function export() {
		// Set memory limit to unlimited to allow heavy usage
		ini_set('memory_limit', '-1');
		$this->load->model('Contesting_model');

		$this->load->model('stations');

		$this->load->model('user_model');

		$station_id = $this->security->xss_clean($this->input->post('station_id'));
		if ($this->stations->check_station_is_accessible($station_id)) {
			$contest_id = $this->security->xss_clean($this->input->post('contestid'));

			$from = $this->security->xss_clean($this->input->post('contestdatesfrom'));
			$to = $this->security->xss_clean($this->input->post('contestdatesto'));

			$station = $this->stations->profile($station_id);

			$station = $station->row();

			$userinfo = $this->user_model->get_by_id($this->session->userdata('user_id'));

			$userinfo = $userinfo->row();

			$data['qsos'] = $this->Contesting_model->export_custom($from, $to, $contest_id, $station_id);

			$data['contest_id'] = $contest_id;
			$data['callsign'] = $station->station_callsign;
			$data['claimed_score'] = '';
			$data['categoryoperator'] = $this->security->xss_clean($this->input->post('categoryoperator'));
			$data['categoryassisted'] = $this->security->xss_clean($this->input->post('categoryassisted'));
			$data['categoryband'] = $this->security->xss_clean($this->input->post('categoryband'));
			$data['categorymode'] = $this->security->xss_clean($this->input->post('categorymode'));
			$data['categorypower'] = $this->security->xss_clean($this->input->post('categorypower'));
			$data['categorystation'] = $this->security->xss_clean($this->input->post('categorystation'));
			$data['categorytransmitter'] = $this->security->xss_clean($this->input->post('categorytransmitter'));
			$data['categorytime'] = $this->security->xss_clean($this->input->post('categorytime'));
			$data['categoryoverlay'] = $this->security->xss_clean($this->input->post('categoryoverlay'));
			$data['operators'] = $this->security->xss_clean($this->input->post('operators'));
			$data['club'] = $this->security->xss_clean($this->input->post('club'));
			$data['location'] = $this->security->xss_clean($this->input->post('location'));
			$data['name'] = $userinfo->user_firstname . ' ' . $userinfo->user_lastname;
			$data['email'] = $userinfo->user_email;
			$data['address'] = $this->security->xss_clean($this->input->post('address'));
			$data['addresscity'] = $this->security->xss_clean($this->input->post('addresscity'));
			$data['addressstateprovince'] = $this->security->xss_clean($this->input->post('addressstateprovince'));
			$data['addresspostalcode'] = $this->security->xss_clean($this->input->post('addresspostalcode'));
			$data['addresscountry'] = $this->security->xss_clean($this->input->post('addresscountry'));
			$data['soapbox'] = $this->security->xss_clean($this->input->post('soapbox'));
			$data['gridlocator'] = $station->station_gridsquare;
			$data['certificate'] = $this->security->xss_clean($this->input->post('certificate'));
			$data['grid_export'] = $this->security->xss_clean($this->input->post('grid_export')) == '1' ? true : false;

			$this->load->view('cabrillo/export', $data);
		}else {
			$this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard');
		}
	}

	public function cbrimport(){
		
		//load user stations
		$this->load->model('stations');
		$data['station_profile'] = $this->stations->all_of_user();

		//set page title and target tab
		$data['page_title'] = __("Cabrillo Import");
		$data['tab'] = "cbr";

		//configure upload
		$config['upload_path'] = './uploads/';
		$config['allowed_types'] = 'cbr|CBR|log|LOG';

		//load upload library
		$this->load->library('upload', $config);

		//if upload fails, return with errors, reset upload filesize
		if ( ! $this->upload->do_upload()) {
			$data['error'] = $this->upload->display_errors();

			$data['max_upload'] = ini_get('upload_max_filesize');

			$this->load->view('interface_assets/header', $data);
			$this->load->view('adif/import', $data);
			$this->load->view('interface_assets/footer');
			return;
		} 

		//get data from upload
		$contest_id = $this->input->post('contest_id', false) ?? '';
		$data = array('upload_data' => $this->upload->data());

		//set memory limit to allow big files
		ini_set('memory_limit', '-1');
		set_time_limit(0);

		//load the logbook model
		$this->load->model('logbook_model');

		//load the Cabrillo parser
		if (!$this->load->is_loaded('cbr_parser')) {
			$this->load->library('cbr_parser');
		}

		//get flag about the presence of the serial number
		$serial_number_present = ($this->input->post('serial_number_present', true) == 1);

		//parse the uploaded file
		$parsed_cbr = $this->cbr_parser->parse_from_file('./uploads/'.$data['upload_data']['file_name'], $serial_number_present);

		//return with error, reset upload filesize
		if(count($parsed_cbr["QSOS"]) < 1)
		{
			$data['error'] = __("Broken CBR file - no QSO data or incomplete header found.");

			$data['max_upload'] = ini_get('upload_max_filesize');

			//delete uploaded file
			unlink('./uploads/' . $data['upload_data']['file_name']);

			$this->load->view('interface_assets/header', $data);
			$this->load->view('adif/import', $data);
			$this->load->view('interface_assets/footer');
			return;
		}

		//get all station ids for the active user
		$this->load->model('stations');
		$station_ids = [];
		foreach ($this->stations->all_of_user()->result() as $station) {
			array_push($station_ids, $station->station_id);
		}

		//overwrite contest id if chosen during upload
		if($contest_id != ''){
			$parsed_cbr["HEADER"]["CONTEST"] = $contest_id;
		}

		//create helper variables
		$custom_errors = [];
		$i = 1;

		//process each contest qso
		foreach ($parsed_cbr["QSOS"] as $qso) {

			//get relevant data from header and qso line
			$station_callsign = $parsed_cbr["HEADER"]["CALLSIGN"];
			$contest_id = $parsed_cbr["HEADER"]["CONTEST"];
			$callsign = $qso["RCVD_CALLSIGN"];
			$band = $qso["BAND"];
			$mode = $qso["MODE"];
			$date = $qso["DATE"];
			$time = $qso["TIME"];
			
			//load QSO
			$contest_qsos = $this->logbook_model->getContestQSO($station_ids, $station_callsign, $contest_id, $callsign, $band, $mode, $date, $time)->result();
			
			//create error if more than 1 QSO is found and skip
			if(count($contest_qsos) != 1){
				array_push($custom_errors, sprintf(__("QSO %d not found or more than 1 QSO found that match the criteria of the CBR file. Skipping as a safety measure."), $i));
				$i++;
				continue;
			}

			//load the first and only row
			$contest_qso = $contest_qsos[0];

			//load unique primary key
			$contest_qso_id = $contest_qso->COL_PRIMARY_KEY;

			//get new serial numbers if required, otherwise default to null. If serial is not numeric, use 0
			$stx = $serial_number_present ? (is_numeric($qso["SENT_SERIAL"]) ? (int)$qso["SENT_SERIAL"] : 0) : null;
			$srx = $serial_number_present ? (is_numeric($qso["RCVD_SERIAL"]) ? (int)$qso["RCVD_SERIAL"] : 0) : null;
			
			//get count of exchanges
			$sent_exchange_count = $parsed_cbr["SENT_EXCHANGE_COUNT"];
			$rcvd_exchange_count = $parsed_cbr["RCVD_EXCHANGE_COUNT"];
			
			//default to empty exchange strings
			$stxstring = null;
			$srxstring = null;

			//process all sent exchanges
			for ($i=1; $i <= $sent_exchange_count; $i++) { 
				if($stxstring == null)
				{
					$stxstring = $qso["SENT_EXCH_" . $i];
				}else{
					$stxstring = $stxstring . ' ' . $qso["SENT_EXCH_" . $i];
				}
			}

			//process all sent exchanges
			for ($i=1; $i <= $rcvd_exchange_count; $i++) { 
				if($srxstring == null)
				{
					$srxstring = $qso["RCVD_EXCH_" . $i];
				}else{
					$srxstring = $srxstring . ' ' . $qso["RCVD_EXCH_" . $i];
				}
			}

			//correct data on contest qso
			$this->logbook_model->set_contest_fields($contest_qso_id, $stx, $stxstring, $srx, $srxstring);

			//increment counter
			$i++;

		}

		//delete uploaded file
		unlink('./uploads/' . $data['upload_data']['file_name']);
		
		//set data for view
		$data['cbr_errors'] = $custom_errors;
		$data['cbr_error_count'] = count($custom_errors);
		$data['cbr_update_count'] = count($parsed_cbr["QSOS"]) - count($custom_errors);
		$data['page_title'] = __("CBR Data Imported");
		
		//get view to user
		$this->load->view('interface_assets/header', $data);
		$this->load->view('cabrillo/cbr_success');
		$this->load->view('interface_assets/footer');
	}
}
