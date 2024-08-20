<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*

	Data lookup functions used within Wavelog

*/
class Qrbcalc extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$data['page_title'] = __("QRB Calculator");

		$this->load->model('stations');
        $data['station_locator'] = $this->stations->find_gridsquare();

		$this->load->view('qrbcalc/index', $data);
	}

	public function calculate() {

		if(!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}

		$locator1 = strtoupper($this->input->post("locator1", TRUE));
		$locator2 = strtoupper($this->input->post("locator2", TRUE));

		if ($this->session->userdata('user_measurement_base') == NULL) {
			$measurement_base = $this->config->item('measurement_base');
		}
		else {
			$measurement_base = $this->session->userdata('user_measurement_base');
		}

		$latlng1 = $this->qra->qra2latlong($locator1);
		$latlng2 = $this->qra->qra2latlong($locator2);
		$latlng1[0] = number_format((float)$latlng1[0], 3, '.', '');;
		$latlng1[1] = number_format((float)$latlng1[1], 3, '.', '');;
		$latlng2[0] = number_format((float)$latlng2[0], 3, '.', '');;
		$latlng2[1] = number_format((float)$latlng2[1], 3, '.', '');;
		$distance = $this->qra->distance($locator1, $locator2, $measurement_base);

		$text_latlng1 = $locator1 . '  ' . sprintf(__("Latitude: %s, Longitude: %s"), $latlng1[0], $latlng1[1]);
		$text_latlng2 = $locator2 . '  ' . sprintf(__("Latitude: %s, Longitude: %s"), $latlng2[0], $latlng2[1]);

		switch ($measurement_base) {
			case 'M':
				$var_dist = sprintf(_ngettext("The distance between %s and %s is %s mile.", "The distance between %s and %s is %s miles.", intval($distance)), $locator1, $locator2, $distance);
				break;
			case 'N':
				$var_dist = sprintf(_ngettext("The distance between %s and %s is %s nautical mile.", "The distance between %s and %s is %s nautical miles.", intval($distance)), $locator1, $locator2, $distance);
				break;
			case 'K':
				$var_dist = sprintf(_ngettext("The distance between %s and %s is %s kilometer.", "The distance between %s and %s is %s kilometers.", intval($distance)), $locator1, $locator2, $distance);
				break;
		}

		$data['result'] = $this->qra->bearing($locator1, $locator2, $measurement_base);
		$data['distance'] = $var_dist;
		$data['bearing'] = sprintf(__("The bearing is %s."), $this->qra->get_bearing($locator1, $locator2) . "&#186;");
		$data['latlng1'] = $latlng1;
		$data['text_latlng1'] = $text_latlng1;
		$data['text_latlng2'] = $text_latlng2;
		$data['latlng2'] = $latlng2;

		$data['latlong_info_text'] = __("Negative latitudes are south of the equator, negative longitudes are west of Greenwich.");

		header('Content-Type: application/json');
		echo json_encode($data);
	}
}
