<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * ---------------------------------------------------------------------------
 * Gridsquare Lookup
 * ---------------------------------------------------------------------------
 * A simple full-page map: type a Maidenhead gridsquare, the map zooms to it
 * and draws a box around the exact grid cell. Pure Leaflet + OpenStreetMap.
 */
class Gridlookup extends CI_Controller {

	function __construct() {
		parent::__construct();

		// authorize(2) => any logged-in user. The site owner (admin) passes.
		if ( ! $this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			return;
		}
	}

	public function index() {
		// Build the list of toggleable GeoJSON overlays (zones + per-country
		// states/provinces) for the map's Overlays dropdown. The view fetches
		// each file on demand, so the multi-megabyte ones are never eager-loaded.
		$this->load->library('Geojson');
		$supported = $this->geojson->getSupportedDxccs();

		$overlays = array(
			array('id' => 'cq',  'file' => 'cqzones.geojson',  'label' => __("CQ Zones"),  'group' => __("Zones"), 'type' => 'cq',  'nameKey' => 'cq_zone_name',  'numKey' => 'cq_zone_number'),
			array('id' => 'itu', 'file' => 'ituzones.geojson', 'label' => __("ITU Zones"), 'group' => __("Zones"), 'type' => 'itu', 'nameKey' => 'itu_zone_name', 'numKey' => 'itu_zone_number'),
		);

		$data['layer'] = $this->optionslib->get_option('option_map_tile_server');

		$data['attribution'] = $this->optionslib->get_option('option_map_tile_server_copyright');

		$states = array();
		foreach ($supported as $dxcc => $info) {
			if (empty($info['enabled'])) {
				continue;
			}
			$states[] = array(
				'id'      => 'states_' . $dxcc,
				'file'    => 'states_' . $dxcc . '.geojson',
				'label'   => $info['name'],
				'group'   => __("States / Provinces"),
				'type'    => 'state',
				'nameKey' => 'name',
				'codeKey' => 'code',
			);
		}
		usort($states, function ($a, $b) { return strcasecmp($a['label'], $b['label']); });

		$data['overlays']   = array_merge($overlays, $states);
		$data['page_title'] = __("Gridsquare Lookup");

		$footerData['scripts'] = array('assets/js/sections/gridlookup.js');

		$this->load->view('interface_assets/header', $data);
		$this->load->view('gridlookup/index');
		$this->load->view('interface_assets/footer', $footerData);
	}
}
