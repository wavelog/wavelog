<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Statistics extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}


	public function index() {
		$this->load->model('user_model');
		$this->load->model('bands');

		// Render User Interface

		// Set Page Title
		$data['page_title'] = __("Statistics");
		$data['sat_active'] = array_search("SAT", $this->bands->get_user_bands(), true);
		$data['years'] = $this->get_years();

		// Load Views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('statistics/index');
		$this->load->view('interface_assets/footer');
	}

	function get_years() {
		$this->load->model('logbook_model');
		$totals_year = $this->logbook_model->totals_year();
		$years=[];
		if ($totals_year) {
			foreach($totals_year->result() as $years_obj) {
				$years[] = $years_obj->year;
			}
		}
		return $years;
	}

	public function get_year() {
		$this->load->model('logbook_model');

		// get data
		$totals_year = $this->logbook_model->totals_year();

		$yearstats = array();

		$i = 0;
		if ($totals_year) {
			foreach($totals_year->result() as $qso_numbers) {
				$yearstats[$i]['year'] = $qso_numbers->year;
				$yearstats[$i++]['total'] = $qso_numbers->total;
			}
		}

		header('Content-Type: application/json');
		echo json_encode($yearstats);
	}

	public function get_year_month() {
		$this->load->model('logbook_model');

		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));

		// get data
		$totals_month = $this->logbook_model->totals_year_month($dateFrom, $dateTo);

		$monthstats = array();

		$i = 0;
		if ($totals_month) {
			foreach($totals_month->result() as $qso_numbers) {
				$monthstats[$i]['month'] = intval($qso_numbers->month);
				$monthstats[$i++]['total'] = $qso_numbers->total;
			}
		}

		header('Content-Type: application/json');
		echo json_encode($monthstats);
	}

	public function get_mode() {
		$this->load->model('logbook_model');
		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));

		$modestats = array();

		$i = 0;
		$ssb = $this->logbook_model->total_ssb($dateFrom, $dateTo);
		$cw = $this->logbook_model->total_cw($dateFrom, $dateTo);
		$fm = $this->logbook_model->total_fm($dateFrom, $dateTo);
		$am = $this->logbook_model->total_am($dateFrom, $dateTo);
		$digi = $this->logbook_model->total_digi($dateFrom, $dateTo);
		if ($ssb > 0) {
			$modestats[$i]['mode'] = 'ssb';
			$modestats[$i++]['total'] = $ssb;
		}
		if ($cw > 0) {
			$modestats[$i]['mode'] = 'cw';
			$modestats[$i++]['total'] = $cw;
		}
		if ($fm > 0) {
			$modestats[$i]['mode'] = 'fm';
			$modestats[$i++]['total'] = $fm;
		}
		if ($am > 0) {
			$modestats[$i]['mode'] = 'am';
			$modestats[$i++]['total'] = $am;
		}
		if ($digi > 0) {
			$modestats[$i]['mode'] = 'digi';
			$modestats[$i]['total'] = $digi;
		}
		usort($modestats, fn($a, $b) => $b['total'] <=> $a['total']);

		header('Content-Type: application/json');

		echo json_encode($modestats);
	}

	public function get_band() {
		$this->load->model('logbook_model');

		$bandstats = array();

		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));
		$total_bands = $this->logbook_model->total_bands($dateFrom, $dateTo);

		$i = 0;

		if ($total_bands) {
			foreach($total_bands->result() as $qso_numbers) {
				$bandstats[$i]['band'] = $qso_numbers->band;
				$bandstats[$i++]['count'] = $qso_numbers->count;
			}
		}

		header('Content-Type: application/json');
		echo json_encode($bandstats);
	}

	public function get_operators() {

		//load logbook model
		$this->load->model('logbook_model');

		//define stats array
		$operatorstats = array();

		//get date range if present
		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));

		//load stats
		$total_operators = $this->logbook_model->total_operators($dateFrom, $dateTo);

		$i = 0;

		//convert to final form
		if ($total_operators) {
			foreach($total_operators->result() as $qso_numbers) {
				$operatorstats[$i]['operator'] = $qso_numbers->operator;
				$operatorstats[$i++]['count'] = $qso_numbers->count;
			}
		}

		//return as json
		header('Content-Type: application/json');
		echo json_encode($operatorstats);
	}

	public function get_sat() {
		$this->load->model('logbook_model');

		$satstats = array();

		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));
		$total_sat = $this->logbook_model->total_sat($dateFrom, $dateTo);
		$i = 0;

		if ($total_sat) {
			foreach($total_sat->result() as $qso_numbers) {
				$satstats[$i]['sat'] = $qso_numbers->COL_SAT_NAME;
				$satstats[$i++]['count'] = $qso_numbers->count;
			}
		}

		header('Content-Type: application/json');
		echo json_encode($satstats);
	}

	public function get_unique_sat_callsigns() {
		$this->load->model('stats');

		$total_qsos = array();

		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));
		$result = $this->stats->unique_sat_callsigns($dateFrom, $dateTo);
		$total_qsos['qsoarray'] = $result['qsoView'];
		$total_qsos['satunique'] = $result['satunique'];
		$total_qsos['modeunique'] = $result['modeunique'];
		$total_qsos['total'] = $result['total'];
		$total_qsos['sats'] = $this->stats->get_sats($dateFrom, $dateTo);
		$total_qsos['modes'] = $this->stats->get_sat_modes($dateFrom, $dateTo);

		$this->load->view('statistics/satuniquetable', $total_qsos);
	}

	public function get_unique_sat_grids() {
		$this->load->model('stats');

		$total_qsos = array();

		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));
		$result = $this->stats->unique_sat_grids($dateFrom, $dateTo);
		$total_qsos['qsoarray'] = $result['qsoView'];
		$total_qsos['satunique'] = $result['satunique'];
		$total_qsos['modeunique'] = $result['modeunique'];
		$total_qsos['total'] = $result['total'];
		$total_qsos['sats'] = $this->stats->get_sats($dateFrom, $dateTo);
		$total_qsos['modes'] = $this->stats->get_sat_modes($dateFrom, $dateTo);

		$this->load->view('statistics/satuniquegridtable', $total_qsos);
	}

	public function get_unique_callsigns() {
		$this->load->model('stats');

		$total_qsos = array();

		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));
		$result = $this->stats->unique_callsigns($dateFrom, $dateTo);
		$total_qsos['qsoarray'] = $result['qsoView'];
		$total_qsos['bandunique'] = $result['bandunique'];
		$total_qsos['modeunique'] = $result['modeunique'];
		$total_qsos['total'] = $result['total'];
		$total_qsos['bands'] = $this->stats->get_bands($dateFrom, $dateTo);

		$this->load->view('statistics/uniquetable', $total_qsos);
	}

	public function get_total_sat_qsos() {
		$this->load->model('stats');

		$total_qsos = array();

		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));
		$result = $this->stats->total_sat_qsos($dateFrom, $dateTo);
		$total_qsos['qsoarray'] = $result['qsoView'];
		$total_qsos['sattotal'] = $result['sattotal'];
		$total_qsos['modetotal'] = $result['modetotal'];
		$total_qsos['modes'] = $result['modes'];
		$total_qsos['sats'] = $this->stats->get_sats($dateFrom, $dateTo);

		$this->load->view('statistics/satqsotable', $total_qsos);
	}

	public function get_total_qsos() {
		$this->load->model('stats');

		$total_qsos = array();

		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));
		$result = $this->stats->total_qsos($dateFrom, $dateTo);
		$total_qsos['qsoarray'] = $result['qsoView'];
		$total_qsos['bandtotal'] = $result['bandtotal'];
		$total_qsos['modetotal'] = $result['modetotal'];
		$total_qsos['bands'] = $this->stats->get_bands($dateFrom, $dateTo);

		$this->load->view('statistics/qsotable', $total_qsos);
	}

	public function qslstats() {
		$this->load->model('stats');

		$total_qsos = array();

		$result = $this->stats->total_qsls();
		$total_qsos['qsoarray'] = $result['qsoView'];
		$total_qsos['qsosatarray'] = $result['qsoSatView'];
		$total_qsos['bands'] = $this->stats->get_bands();
		$total_qsos['sats'] = $this->stats->get_sats();

		// Set Page Title
		$data['page_title'] = __("QSL Statistics");

		// Load Views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('statistics/qsltable', $total_qsos);
		$this->load->view('interface_assets/footer');
	}

	public function antennaanalytics() {
		$this->load->model('stats');
		$this->load->model('logbookadvanced_model');
		$this->load->model('bands');

		$data = array();

		$headerData['page_title'] = __("Antenna Analytics");

		$data['satellites'] = $this->stats->get_sats();
		$data['bands'] = $this->bands->get_worked_bands();
		$data['modes'] = $this->logbookadvanced_model->get_modes();
		$data['sats'] = $this->bands->get_worked_sats();
		$data['orbits'] = $this->bands->get_worked_orbits();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/chart.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/chart.js")),
			'assets/js/sections/antennastats.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/antennastats.js")),
			'assets/js/bootstrap-multiselect.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/bootstrap-multiselect.js")),
		];

		// Load Views
		$this->load->view('interface_assets/header', $headerData);
		$this->load->view('statistics/antennaanalytics', $data);
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function get_azimuth_data() {
		$band = xss_clean($this->input->post('band'));
		$mode = xss_clean($this->input->post('mode'));
		$sat = xss_clean($this->input->post('sat'));
		$orbit = xss_clean($this->input->post('orbit'));
		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));

		$this->load->model('stats');
		$azimutharray = $this->stats->azimuthdata($band, $mode, $sat, $orbit, $dateFrom, $dateTo);

		header('Content-Type: application/json');
		echo json_encode($azimutharray);
	}

	public function get_elevation_data() {
		$sat = xss_clean($this->input->post('sat'));
		$orbit = xss_clean($this->input->post('orbit'));
		$dateFrom = xss_clean($this->input->post('dateFrom'));
		$dateTo = xss_clean($this->input->post('dateTo'));

		$this->load->model('stats');
		$elevationarray = $this->stats->elevationdata($sat, $orbit, $dateFrom, $dateTo);

		header('Content-Type: application/json');
		echo json_encode($elevationarray);
	}

	public function sat_qsos_ajax() {
		$this->load->model('stats');

		$sat = str_replace('"', "", $this->security->xss_clean($this->input->post("Sat")));
		$mode = str_replace('"', "", $this->security->xss_clean($this->input->post("Mode")));
		$dateFrom = $this->security->xss_clean($this->input->post("dateFrom"));
		$dateTo = $this->security->xss_clean($this->input->post("dateTo"));
		$data['results'] = $this->stats->sat_qsos($sat,$dateFrom,$dateTo,$mode);

		$data['page_title'] = __("Log View")." - " . __("Satellite QSOs");
		$data['filter'] = $sat;
		$data['ispopup'] = true;

		$this->load->view('statistics/details', $data);
	}

	public function initials() {
		$this->load->model('stats');
		$this->load->model('bands');

		$data['modes'] = $this->stats->get_eme_modes();

		$data['worked_bands'] = $this->bands->get_worked_bands_eme();

		// Set Page Title
		$data['page_title'] = __("EME Initials");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/initials.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/initials.js")),
		];

		// Load Views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('statistics/initials');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function getInitials() {
		$band = xss_clean($this->input->post('band'));
		$mode = xss_clean($this->input->post('mode'));

		if ($this->session->userdata('user_measurement_base') == NULL) {
			$measurement_base = $this->config->item('measurement_base');
		} else {
			$measurement_base = $this->session->userdata('user_measurement_base');
		}

		switch ($measurement_base) {
			case 'M':
				$unit = "mi";
				$factor = 0.621371;
				break;
			case 'K':
				$unit = "km";
				$factor = 1;
				break;
			case 'N':
				$unit = "nmi";
				$factor = 0.539957;
				break;
			default:
				$unit = "km";
				$factor = 1;
				break;
		}
		$this->load->model('stats');
		$data['factor'] = $factor;
		$data['unit'] = $unit;
		$data['intials_array'] = $this->stats->getInitialsFromDb($band, $mode);
		$this->load->view('statistics/initialresult', $data);
	}
}
