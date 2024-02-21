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

		// Render Page
		$data['page_title'] = "Station Setup";
		$this->load->view('interface_assets/header', $data);
		$this->load->view('stations/stationsetup');
		$this->load->view('interface_assets/footer');
	}

}
