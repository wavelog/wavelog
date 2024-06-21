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

}
