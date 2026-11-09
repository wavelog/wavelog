<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mostwantedgrids extends CI_Controller {

	function __construct() {
		parent::__construct();

		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

    public function index() {
		$data['page_title'] = __("Most Wanted Grids (Satellite)");

		$this->load->model('bands');
		$this->load->model('gridmap_model');
		$this->load->model('mostwantedgrids_model');
		$this->load->model('stations');

		$data['layer'] = $this->optionslib->get_option('option_map_tile_server');

		$data['attribution'] = $this->optionslib->get_option('option_map_tile_server_copyright');

		$data['user_map_custom'] = $this->optionslib->get_map_custom();

		$data['adif_propmodes'] = $this->config->item('adif_propmodes');
		$data['homegrid'] = explode(',', $this->stations->find_gridsquare());

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/leaflet/geocoding.js',
			'assets/js/sections/mostwantedgrids.js',
			'assets/js/leaflet/L.MaidenheadMostwantedGridMap.js',
		];

		$stats = $this->mostwantedgrids_model->get_stats()->result();
		if ($stats[0]->number == 0) {
			$this->session->set_flashdata('warning', __("Most wanted grid table is empty. Please update the table first."));
		}
		$data['stats'] = $stats[0];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('mostwantedgrids/index');
		$this->load->view('interface_assets/footer', $footerData);
    }

	public function getGridsjs() {

		$this->load->model('mostwantedgrids_model');
		$query = $this->mostwantedgrids_model->get_grids();

		$array_grid_4char = array();
		foreach ($query->result() as $row) {
			$array_grid_4char[$row->grid] = (int) $row->perc;
		}

		$data['grid_4char'] = ($array_grid_4char);

		header('Content-Type: application/json');
		echo json_encode($data);
    }
}
