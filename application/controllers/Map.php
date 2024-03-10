<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Map extends CI_Controller {

	function index() {
		redirect('dashboard');
    }

	// Generic fonction for return Json for MAP //
	public function map_plot_json() {
		$this->load->model('Stations');
		$this->load->model('logbook_model');

		// set informations //
		$nb_qso = (intval($this->input->post('nb_qso'))>0)?xss_clean($this->input->post('nb_qso')):18;
		$offset = (intval($this->input->post('offset'))>0)?xss_clean($this->input->post('offset')):null;
		$qsos = $this->logbook_model->get_qsos($nb_qso, $offset);
		// [PLOT] ADD plot //
		$plot_array = $this->logbook_model->get_plot_array_for_map($qsos->result());
		// [MAP Custom] ADD Station //
		$station_array = $this->Stations->get_station_array_for_map();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array_merge($plot_array, $station_array));
	}

}
