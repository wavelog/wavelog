<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of information for station tools.
*/

class Stationsetup extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }
	}

	public function index() {
		$this->load->model('stations');
		$this->load->model('Logbook_model');
		$this->load->model('logbooks_model');

		$data['my_logbooks'] = $this->logbooks_model->show_all();

		$data['stations'] = $this->stations->all_with_count();
		$data['current_active'] = $this->stations->find_active();
		$data['is_there_qsos_with_no_station_id'] = $this->Logbook_model->check_for_station_id();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/stationsetup.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/stationsetup.js")),
		];

		// Render Page
		$data['page_title'] = "Station Setup";
		$this->load->view('interface_assets/header', $data);
		$this->load->view('stationsetup/stationsetup');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function setActiveLogbook_json() {
		$id2act=xss_clean($this->input->post('id2setActive',true));
		if ($id2act ?? '' != '') {
			$this->load->model('logbooks_model');
			$this->logbooks_model->set_logbook_active($id2act);
			$data['success']=1;
		} else {
			$data['success']=0;
			$data['flashdata']='Error';
		}
		echo json_encode($data);
	}

	public function deleteLogbook_json() {
		$id2del=xss_clean($this->input->post('id2delete',true));
		if ($id2del ?? '' != '') {
			$this->load->model('logbooks_model');
			$this->logbooks_model->delete($id2del);
			$data['success']=1;
		} else {
			$data['success']=0;
			$data['flashdata']='Error';
		}
		echo json_encode($data);
	}

	public function newLogbook_json() {
		$this->load->library('form_validation');

		$this->form_validation->set_rules('stationLogbook_Name', 'Station Logbook Name', 'required');

		if ($this->form_validation->run() == FALSE) {
			$data['flashdata']=validation_errors();
			$data['success']=0;
			echo json_encode($data);
		} else {	
			$this->load->model('logbooks_model');
			$newId=$this->logbooks_model->add(xss_clean($this->input->post('stationLogbook_Name', true)));
			if ($newId > 0) {
				$data['success']=1;
			} else {
				$data['success']=0;
				$data['flashdata']='Error';
			}
			echo json_encode($data);
		}
	}

	public function newLogbook() {
		$data['page_title'] = "Create Station Logbook";
		$this->load->view('stationsetup/create', $data);
	}

	public function newLocation() {
		$this->load->model('stations');
		$this->load->model('dxcc');
		$data['dxcc_list'] = $this->dxcc->list();

		$this->load->model('logbook_model');
		$data['iota_list'] = $this->logbook_model->fetchIota();

		$data['page_title'] = lang('station_location_create_header');
		$this->load->view('station_profile/create', $data);
	}



}
