<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * ---------------------------------------------------------------------------
 * Activation Planner
 * ---------------------------------------------------------------------------
 * A simple full-page map: type a Maidenhead gridsquare, the map zooms to it
 * and draws a box around the exact grid cell. Pure Leaflet + OpenStreetMap.
 */
class Activationplanner extends CI_Controller {

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
		$data['page_title'] = __("Activation Planner");

		// Active station's DXCC (adif) so the share text can omit the entity
		// when the activation is in the user's own country. null if none/hidden.
		$this->load->model('stations');
		$active_id = $this->session->userdata('station_profile_id');
		$active_profile = ($active_id && $this->stations->check_station_is_accessible($active_id))
			? $this->stations->profile($active_id)->row() : null;
		$data['user_dxcc'] = $active_profile ? $active_profile->station_dxcc : null;

		$footerData['scripts'] = array(
			'assets/js/sections/activationplanner.js',
			'assets/js/leaflet/leaflet.markercluster.js',
		);



		$this->load->view('interface_assets/header', $data);
		$this->load->view('activationplanner/index');
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
					'dxcc'    => $dxcc,
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
		$json = json_encode($this->wwff->get_directory());

		$etag = '"' . md5($json) . '"';
		session_write_close();            // release session lock; allow header override
		header('Pragma: private');        // override nocache Pragma emitted by autoloaded session
		header('Cache-Control: private, max-age=3600');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
		header('ETag: ' . $etag);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304); // CI idiom for status codes
			return;
		}
		header('Content-Type: application/json');
		echo $json;
	}

	public function pota_directory() {
		$this->load->model('pota');
		$json = json_encode($this->pota->get_directory());

		$etag = '"' . md5($json) . '"';
		session_write_close();            // release session lock; allow header override
		header('Pragma: private');        // override nocache Pragma emitted by autoloaded session
		header('Cache-Control: private, max-age=3600');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
		header('ETag: ' . $etag);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304); // CI idiom for status codes
			return;
		}
		header('Content-Type: application/json');
		echo $json;
	}

	/*
	 * AJAX: the set of POTA references the user has activated in the past
	 * (as activator — COL_MY_POTA_REF across the active logbook's locations).
	 * Lets the planner mark already-activated parks distinctly on the map.
	 * User-scoped, so the response is private (never shared across users).
	 */
	public function activated_pota() {
		$this->load->model('pota');
		$json = json_encode($this->pota->activated_references());

		$etag = '"' . md5($json) . '"';
		session_write_close();
		header('Pragma: private');        // override nocache Pragma emitted by autoloaded session
		header('Cache-Control: private, no-cache');
		header('Expires: 0');
		header('ETag: ' . $etag);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304);
			return;
		}
		header('Content-Type: application/json');
		echo $json;
	}

	public function pota_boundary($reference = '') {
		$reference = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $reference);
		if ($reference === '') {
			$this->output->set_status_header(404);
			return;
		}
		$rows = $this->db->query('SELECT geom FROM pota_boundaries WHERE reference = ?', [$reference])->result();
		if (!$rows) {
			$this->output->set_status_header(404);
			return;
		}

		$geoms = [];
		foreach ($rows as $r) {
			$g = json_decode($r->geom, true);
			if (is_array($g)) { $geoms[] = $g; }
		}
		$geometry = count($geoms) === 1
			? $geoms[0]
			: ['type' => 'GeometryCollection', 'geometries' => $geoms];

		$feature = [
			'type'       => 'Feature',
			'geometry'   => $geometry,
			'properties' => ['reference' => $reference],
		];
		$json = json_encode($feature);
		$etag = '"' . md5($json) . '"';
		session_write_close();
		header('Pragma: private');        // override nocache Pragma emitted by autoloaded session
		header('Cache-Control: private, max-age=31536000');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
		header('ETag: ' . $etag);
		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304);
			return;
		}
		header('Content-Type: application/geo+json');
		echo $json;
	}

	public function sota_directory() {
		$this->load->model('sota');

		$etag = '"' . $this->sota->directory_signature() . '"';

		session_write_close();            // release session lock; allow header override
		header('Pragma: private');        // override nocache Pragma emitted by autoloaded session
		header('Cache-Control: private, max-age=3600');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
		header('ETag: ' . $etag);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304); // CI idiom for status codes
			return;
		}
		header('Content-Type: application/json');
		$this->sota->stream_directory_json();
	}

	/*
	 * AJAX: the full IOTA directory (tag, name, prefix, bounding box) for the
	 * optional rectangle overlay on this map. Deleted refs are excluded.
	 */
	public function iota_directory() {
		$this->load->model('iota');
		$json = json_encode($this->iota->get_directory());

		$etag = '"' . md5($json) . '"';
		session_write_close();            // release session lock; allow header override
		header('Pragma: private');        // override nocache Pragma emitted by autoloaded session
		header('Cache-Control: private, max-age=3600');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
		header('ETag: ' . $etag);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304); // CI idiom for status codes
			return;
		}
		header('Content-Type: application/json');
		echo $json;
	}

	/*
	 * AJAX: given a 4-character gridsquare, return the DXCC entities covering it
	 * (from the vuccgrids table) with their flag emoji, so the activation planner
	 * can show the flag beside the grid. Outputs JSON [{name, flag}, ...].
	 */
	public function dxcc_for_grid() {
		$grid = strtoupper((string) $this->input->get('grid', TRUE));

		if (strlen($grid) < 4) {
			header('Content-Type: application/json');
			echo json_encode(array());
			return;
		}
		$grid = substr($grid, 0, 4);

		$this->load->model('lookup_model');
		$this->load->library('DxccFlag');

		$out = array();
		foreach ($this->lookup_model->getDxccForVuccGrid($grid) as $row) {
			$out[] = array(
				'name' => ucwords(strtolower($row->name), "- (/"),
				'flag' => $this->dxccflag->get($row->adif),
				'adif' => (int) $row->adif,
			);
		}
		$json = json_encode($out);

		$etag = '"' . md5($grid . ':' . $json) . '"';
		session_write_close();            // release session lock; allow header override
		header('Pragma: private');        // override nocache Pragma emitted by autoloaded session
		header('Cache-Control: private, max-age=3600');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
		header('ETag: ' . $etag);

		if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
			$this->output->set_status_header(304); // CI idiom for status codes
			return;
		}
		header('Content-Type: application/json');
		echo $json;
	}

	/*
	 * AJAX: WWFF/POTA/SOTA references within 20 km of a point (the centre of the
	 * entered gridsquare). The distance math lives in Activationplanner_model.
	 * Outputs JSON [{type, ref, name, dist}, ...] sorted by distance (km).
	 */
	public function refs_nearby() {
		$lat = $this->input->get('lat', TRUE);
		$lng = $this->input->get('lng', TRUE);
		header('Content-Type: application/json');

		if ($lat === null || $lng === null || !is_numeric($lat) || !is_numeric($lng)) {
			echo json_encode(array());
			return;
		}

		$this->load->model('activationplanner_model');
		echo json_encode($this->activationplanner_model->refs_nearby((float) $lat, (float) $lng, 20.0));
	}
}
