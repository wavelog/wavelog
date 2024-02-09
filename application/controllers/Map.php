<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Map extends CI_Controller {

	function index() {
    }

	// Generic fonction for return Json for MAP //
	public function map_plot_json() {
		$this->load->model('Stations');
		$this->load->model('logbook_model');

		// set informations //
		if ($this->input->post('isCustom') == true) {
			$date_from = xss_clean($this->input->post('date_from'));
			$date_to = xss_clean($this->input->post('date_to'));
			$band = xss_clean($this->input->post('band'));
			$mode = xss_clean($this->input->post('mode'));
			$prop_mode = xss_clean($this->input->post('prop_mode'));
			$qsos = $this->logbook_model->map_custom_qsos($date_from, $date_to, $band, $mode, $prop_mode);
		} else {
			$nb_qso = (intval($this->input->post('nb_qso'))>0)?xss_clean($this->input->post('nb_qso')):18;
			$offset = (intval($this->input->post('offset'))>0)?xss_clean($this->input->post('offset')):null;
			$qsos = $this->logbook_model->get_qsos($nb_qso, $offset);
		}
		// [PLOT] ADD plot //
		$plot_array = $this->logbook_model->get_plot_array_for_map($qsos->result());
		// [MAP Custom] ADD Station //
		$station_array = $this->Stations->get_station_array_for_map();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array_merge($plot_array, $station_array));
	}

}
