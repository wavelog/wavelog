<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller to interact with the Clublog API
*/

class Hamsat extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$data['scripts'] = [
			'assets/js/sections/hamsat.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/hamsat.js")),
			'assets/js/moment.min.js',
			'assets/js/datetime-moment.js'
		];

		// Get Date format
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$pageData['custom_date_format'] = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$pageData['custom_date_format'] = $this->config->item('qso_date_format');
		}

		switch ($pageData['custom_date_format']) {
			case "d/m/y": $pageData['custom_date_format'] = 'DD/MM/YY'; break;
			case "d/m/Y": $pageData['custom_date_format'] = 'DD/MM/YYYY'; break;
			case "m/d/y": $pageData['custom_date_format'] = 'MM/DD/YY'; break;
			case "m/d/Y": $pageData['custom_date_format'] = 'MM/DD/YYYY'; break;
			case "d.m.Y": $pageData['custom_date_format'] = 'DD.MM.YYYY'; break;
			case "y/m/d": $pageData['custom_date_format'] = 'YY/MM/DD'; break;
			case "Y-m-d": $pageData['custom_date_format'] = 'YYYY-MM-DD'; break;
			case "M d, Y": $pageData['custom_date_format'] = 'MMM DD, YYYY'; break;
			case "M d, y": $pageData['custom_date_format'] = 'MMM DD, YY'; break;
			default: $pageData['custom_date_format'] = 'DD/MM/YYYY';
		}

		$hkey_opt=$this->user_options_model->get_options('hamsat',array('option_name'=>'hamsat_key','option_key'=>'workable'))->result();
		if (count($hkey_opt)>0) {
			$data['user_hamsat_workable_only'] = $hkey_opt[0]->option_value;
		} else {
			$data['user_hamsat_workable_only'] = 0;
		}
		$hkey_opt=$this->user_options_model->get_options('hamsat',array('option_name'=>'hamsat_key','option_key'=>'api'))->result();
		if (count($hkey_opt)>0) {
			$data['user_hamsat_key'] = $hkey_opt[0]->option_value;
		} else {
			$data['user_hamsat_key']='';
		}

		// Load public view
		$data['page_title'] = __("Hamsat - Satellite Roving");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('/hamsat/index', $pageData);
		$this->load->view('interface_assets/footer');
	}

	public function activations() {
		$this->load->model("user_options_model");
		$this->load->model("logbooks_model");
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->load->model('logbook_model');
		$hkey_opt=$this->user_options_model->get_options('hamsat',array('option_name'=>'hamsat_key','option_key'=>'api'))->result();
		if (count($hkey_opt)>0) {
			$data['user_hamsat_key'] = $hkey_opt[0]->option_value;
		} else {
			$data['user_hamsat_key']='';
		}
		$this->load->model('stations');
		$my_gridsquare = strtoupper($this->stations->find_gridsquare());

		// Get Date format
		if ($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/wavelog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}
		$url = 'https://hams.at/api/alerts/upcoming';
		if ($data['user_hamsat_key'] ?? '' != '') {
			$options = array(
				'http' => array(
					'method' => 'GET',
					'header' => "Authorization: Bearer ".$data['user_hamsat_key']."\r\n"
				)
			);
			$context = stream_context_create($options);
			$json = file_get_contents($url, false, $context);
		} else {
			$json = file_get_contents($url);
		}
		$hkey_opt=$this->user_options_model->get_options('hamsat',array('option_name'=>'hamsat_key','option_key'=>'workable'))->result();
		if (count($hkey_opt)>0) {
			$data['user_hamsat_workable_only'] = $hkey_opt[0]->option_value;
		} else {
			$data['user_hamsat_workable_only'] = 0;
		}

		$decoded_json = json_decode($json);
		for ($i=0; $i < count($decoded_json->data); $i++) {
			$aos_at = strtotime($decoded_json->data[$i]->aos_at);
			$los_at = strtotime($decoded_json->data[$i]->los_at);
			$decoded_json->data[$i]->aos_at_date = date($custom_date_format, $aos_at);
			$decoded_json->data[$i]->aos_to_los = date("H:i:s", $aos_at).' - '.date("H:i:s", $los_at);

			if ($this->logbook_model->check_if_callsign_worked_in_logbook($decoded_json->data[$i]->callsign, $logbooks_locations_array, "SAT")) {
				$decoded_json->data[$i]->callsign_wkd = 1;
			} else {
				$decoded_json->data[$i]->callsign_wkd = 0;
			}
			$modeclass = '';
			if ($decoded_json->data[$i]->mode == 'SSB' || $decoded_json->data[$i]->mode== 'CW') {
				$modeclass = 'hamsatBgLin';
			} else if ($decoded_json->data[$i]->mode == 'Data') {
				$modeclass = 'hamsatBgData';
			} else if ($decoded_json->data[$i]->mode == 'FM') {
				$modeclass = 'hamsatBgFm';
			}
			$decoded_json->data[$i]->mode_class = $modeclass;
			for($j = 0; $j < count($decoded_json->data[$i]->grids); $j++) {
				$worked = $this->logbook_model->check_if_grid_worked_in_logbook(substr($decoded_json->data[$i]->grids[$j], 0, 4), null, "SAT");
				if ($worked->num_rows() != 0) {
					$decoded_json->data[$i]->grids_wkd[$j] = 1;
				} else {
					$decoded_json->data[$i]->grids_wkd[$j] = 0;
				}
			}

			$workable_start_at = strtotime($decoded_json->data[$i]->workable_start_at ?? '');
			$workable_end_at = strtotime($decoded_json->data[$i]->workable_end_at ?? '');
			$decoded_json->data[$i]->workable_from_to = date("H:i", $workable_start_at).' - '.date("H:i", $workable_end_at);

			if (strtoupper($decoded_json->data[$i]->satellite->name) == 'GREENCUBE') {
				$decoded_json->data[$i]->sat_export_name = 'IO-117';
			} else if (strtoupper($decoded_json->data[$i]->satellite->name) == 'AO-07') {
				$decoded_json->data[$i]->sat_export_name = 'AO-7';
			} else {
				$decoded_json->data[$i]->sat_export_name = $decoded_json->data[$i]->satellite->name;
			}
			$decoded_json->data[$i]->my_gridsquare = $my_gridsquare;

		}

		header('Content-Type: application/json');
		echo(json_encode($decoded_json->data));
    }
}
