<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sattimers extends CI_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$this->load->model('stations');
		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/sattimers.js?'
		];

		$url = 'https://www.df2et.de/tevel/api2.php?grid='.strtoupper($this->stations->find_gridsquare());

		$this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
		if (!$RawData = $this->cache->get('SatTimers'.strtoupper($this->stations->find_gridsquare()))) {
			$RawData = file_get_contents($url, true);
			$this->cache->save('SatTimers'.strtoupper($this->stations->find_gridsquare()), $RawData, (60*1));
		} 


		$json = $RawData;
		$response = json_decode($json, true);
		if (array_key_exists('data', $response)) {
			$data['activations'] = json_decode($json, true)['data'] ?? [];
		} else if (array_key_exists('error', $response)) {
			$this->session->set_flashdata('message', 'Error: '.$response['error']);
			$data['activations'] = [];
		} else {
			$data['activations'] = [];
		}

		$data['gridsquare'] = strtoupper($this->stations->find_gridsquare());
		if ($data['gridsquare'] == "0") {
			$this->session->set_flashdata('message', sprintf(__("You have no station locations. Go %s to create it!"), '<a href="' . site_url('stationsetup') . '">' . __("here") . '</a>'));
		}

		$data['page_title'] = __("Satellite Timers");

		if($this->session->userdata('user_date_format')) {
			$data['custom_date_format'] = $this->session->userdata('user_date_format');
		} else {
			$data['custom_date_format'] = $this->config->item('qso_date_format');
		}

		switch ($data['custom_date_format']) {
		case "d/m/y": $data['custom_date_format'] = 'DD/MM/YY'; break;
		case "d/m/Y": $data['custom_date_format'] = 'DD/MM/YYYY'; break;
		case "m/d/y": $data['custom_date_format'] = 'MM/DD/YY'; break;
		case "m/d/Y": $data['custom_date_format'] = 'MM/DD/YYYY'; break;
		case "d.m.Y": $data['custom_date_format'] = 'DD.MM.YYYY'; break;
		case "y/m/d": $data['custom_date_format'] = 'YY/MM/DD'; break;
		case "Y-m-d": $data['custom_date_format'] = 'YYYY-MM-DD'; break;
		case "M d, Y": $data['custom_date_format'] = 'MMM DD, YYYY'; break;
		case "M d, y": $data['custom_date_format'] = 'MMM DD, YY'; break;
		default: $data['custom_date_format'] = 'DD/MM/YYYY';
		}

		$this->load->view('interface_assets/header', $data);
		$this->load->view('/sattimers/index', $data);
		$this->load->view('interface_assets/footer', $footerData);
	}
}
