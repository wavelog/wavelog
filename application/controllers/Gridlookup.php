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

		// Distance unit follows the user's preference, same as Qrbcalc / the logbook.
		if ($this->session->userdata('user_measurement_base') == NULL) {
			$measurement_base = $this->config->item('measurement_base');
		} else {
			$measurement_base = $this->session->userdata('user_measurement_base');
		}
		$data['measurement_base'] = $measurement_base;

		// DXCC entity prefixes so the overlay labels read "Norway (LA)" etc.
		$prefixes = array();
		if (!empty($supported)) {
			$this->db->select('adif, prefix')->where_in('adif', array_keys($supported));
			foreach ($this->db->get('dxcc_entities')->result() as $row) {
				$prefixes[$row->adif] = $row->prefix;
			}
		}

		$states = array();
		foreach ($supported as $dxcc => $info) {
			if (empty($info['enabled'])) {
				continue;
			}
			$states[] = array(
				'id'      => 'states_' . $dxcc,
				'file'    => 'states_' . $dxcc . '.geojson',
				'label'   => $info['name'] . (!empty($prefixes[$dxcc]) ? ' (' . $prefixes[$dxcc] . ')' : ''),
				'group'   => __("States / Provinces"),
				'type'    => 'state',
				'nameKey' => 'name',
				'codeKey' => 'code',
			);
		}
		usort($states, function ($a, $b) { return strcasecmp($a['label'], $b['label']); });

		$data['overlays']   = array_merge($overlays, $states);
		$data['page_title'] = __("Gridsquare Lookup");

		$footerData['scripts'] = array(
			'assets/js/sections/gridlookup.js',
			'assets/js/leaflet/leaflet.markercluster.js',
		);



		$this->load->view('interface_assets/header', $data);
		$this->load->view('gridlookup/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	/*
	 * AJAX: given lat&lng, return the state/province (and its country) whose
	 * subdivision GeoJSON contains the point — the same per-country files the
	 * page offers as overlays. Outputs JSON {country, state, code} or null.
	 * Scanned server-side so the (multi-MB) files never go to the browser.
	 */
	public function state_for_point() {
		$lat = $this->input->get('lat', TRUE);
		$lng = $this->input->get('lng', TRUE);

		header('Content-Type: application/json');

		if ($lat === null || $lng === null || !is_numeric($lat) || !is_numeric($lng)) {
			echo json_encode(null);
			return;
		}

		$this->load->library('Geojson');
		foreach ($this->geojson->getSupportedDxccs() as $dxcc => $info) {
			if (empty($info['enabled'])) {
				continue;
			}
			$data = $this->geojson->loadGeoJsonFile('assets/json/geojson/states_' . $dxcc . '.geojson');
			if ($data === null) {
				continue;
			}
			$props = $this->geojson->findFeatureContainingPoint((float) $lat, (float) $lng, $data);
			if ($props !== null) {
				echo json_encode(array(
					'country' => $info['name'],
					'state'   => array_key_exists('name', $props) ? $props['name'] : null,
					'code'    => array_key_exists('code', $props) ? $props['code'] : null,
				));
				return;
			}
		}

		echo json_encode(null);
	}

	/*
	 * AJAX: the full WWFF reference directory (reference, name, lat, lon) for the
	 * optional clustered overlay on this map. Toggled from the page itself (default off).
	 */
	public function wwff_directory() {
		$this->load->model('wwff');
		header('Content-Type: application/json');
		echo json_encode($this->wwff->get_directory());
	}

	public function pota_directory() {
		$this->load->model('pota');
		$json = json_encode($this->pota->get_directory());

		$etag = '"' . md5($json) . '"';
		session_write_close();            // release session lock; allow header override
		session_cache_limiter('private'); // defeat PHP's default nocache limiter (cf. Eqsl.php)
		header('Cache-Control: private, max-age=3600');
		header('ETag: ' . $etag);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304); // CI idiom for status codes
			return;
		}
		header('Content-Type: application/json');
		echo $json;
	}

	public function sota_directory() {
		$this->load->model('sota');
		$json = json_encode($this->sota->get_directory());

		$etag = '"' . md5($json) . '"';
		session_write_close();            // release session lock; allow header override
		session_cache_limiter('private'); // defeat PHP's default nocache limiter (cf. Eqsl.php)
		header('Cache-Control: private, max-age=3600');
		header('ETag: ' . $etag);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304); // CI idiom for status codes
			return;
		}
		header('Content-Type: application/json');
		echo $json;
	}
}
