<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Azimuthal extends CI_Controller {

	function __construct() {
		parent::__construct();

		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$this->load->library('qra');

		$data['page_title'] = __("Azimuthal Map");

		// Session-first active station location (pattern from Qso controller)
		if ($this->stations->check_station_is_accessible($this->session->userdata('station_profile_id') ?? 0)) {
			$station_id = $this->session->userdata('station_profile_id');
		} else {
			$station_id = $this->stations->find_active();
		}

		$station = ($station_id) ? $this->stations->profile($station_id)->row() : null;

		$data['has_center'] = false;
		$data['center_lat'] = null;
		$data['center_lng'] = null;
		$data['homegrid'] = '';
		$data['station_label'] = '';

		if ($station && $station->station_gridsquare && $station->station_gridsquare != '0') {
			// qra2latlong is VUCC-aware: comma-separated grids are averaged to their midpoint
			$latlng = $this->qra->qra2latlong($station->station_gridsquare);
			if ($latlng !== false) {
				$data['center_lat'] = round((float)$latlng[0], 6);
				$data['center_lng'] = round((float)$latlng[1], 6);
				$data['homegrid'] = strtoupper(explode(',', trim($station->station_gridsquare))[0]);
				$data['station_label'] = trim(($station->station_callsign ?? '') . ' · ' . ($station->station_profile_name ?? ''), ' ·');
				$data['has_center'] = true;
			}
		}

		// Current (non-deleted) DXCC entities for the prefix marker overlay,
		// positioned at each entity's reference coordinates
		$this->load->model('dxcc');
		$data['dxcc_markers'] = [];
		foreach ($this->dxcc->list_current()->result() as $row) {
			if ($row->lat !== null && $row->long !== null) {
				$data['dxcc_markers'][] = ['name' => $row->name, 'prefix' => $row->prefix, 'lat' => (float)$row->lat, 'lng' => (float)$row->long];
			}
		}

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/d3.min.js',
			'assets/js/sections/azimuthal.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('azimuthal/index');
		$this->load->view('interface_assets/footer', $footerData);
	}
}
