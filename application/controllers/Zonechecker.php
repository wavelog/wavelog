<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Zonechecker extends CI_Controller {
	private $geojsonFile = null;
	private $geojsonData = null;
	private $gridsquareCache = array(); // Cache gridsquare->zone lookups
	private $spatialIndex = null; // Spatial index for faster lookups

	function __construct() {
		parent::__construct();
		$this->load->library('Geojson');
		$this->load->helper('callsign');

		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {

		$this->load->model('stations');

		$data['station_profile'] = $this->stations->all_of_user();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/zonechecker.js',
		];

		$data['page_title'] = __("Gridsquare Zone finder");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('zonechecker/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	function getQsos($station_id) {
		$sql = 'select distinct col_country, col_call, col_dxcc, col_time_on, station_profile.station_profile_name, col_primary_key, col_cqz, col_ituz, col_gridsquare, col_band
			from ' . $this->config->item('table_name') . '
			join station_profile on ' . $this->config->item('table_name') . '.station_id = station_profile.station_id
			where station_profile.user_id = ?
			and length(col_gridsquare) >= 6';
		$params[] = $this->session->userdata('user_id');

		if ($station_id && is_numeric($station_id)) {
			$sql .= ' and ' . $this->config->item('table_name') . '.station_id = ?';
			$params[] = $station_id;
		}

		$sql .= ' order by station_profile.station_profile_name asc, col_time_on desc';

        $query = $this->db->query($sql, $params);

		return $query;
	}


	function doWazCheck() {
		set_time_limit(3600);
		$de = $this->input->post('de', true);
		$zoneType = $this->input->post('zoneType', true) ?: 'cq'; // Default to CQ if not specified

		$i = 0;
		$result = array();
		$this->gridsquareCache = array(); // Reset cache

		$callarray = $this->getQsos($de)->result();

		// Starting clock time in seconds
		$start_time = microtime(true);

		// Load appropriate GeoJSON file based on zone type
		if ($this->geojsonFile === null) {
			if ($zoneType === 'itu') {
				$this->geojsonFile = "assets/json/geojson/ituzones.geojson";
			} else {
				$this->geojsonFile = "assets/json/geojson/cqzones.geojson";
			}
			$this->geojsonData = $this->geojson->loadGeoJsonFile($this->geojsonFile);
			$this->spatialIndex = $this->geojson->buildSpatialIndex($this->geojsonData);
		}

        if ($this->geojsonData === null) {
            return null;
        }

        $hits = 0; // Track cache hits for performance metrics
        $misses = 0; // Track cache misses

		foreach ($callarray as $qso) {
			$i++;
			$gridsquare = $qso->col_gridsquare;

			// Check cache first - avoid redundant gridsquare->zone conversions
			if (!isset($this->gridsquareCache[$gridsquare])) {
				$zone = $this->findCqZoneFromGridsquare($gridsquare, $zoneType);
				$this->gridsquareCache[$gridsquare] = $zone;
				$misses++;
			} else {
				$zone = $this->gridsquareCache[$gridsquare];
				$hits++;
			}

			// Check zone based on type
			if ($zoneType === 'itu') {
				if (!isset($zone['itu_zone_number'])) {
					continue;
				}

				if ($qso->col_ituz != $zone['itu_zone_number']) {
					$result[] = [
						'id' => $qso->col_primary_key,
						'qso_date' => $qso->col_time_on,
						'callsign' => $qso->col_call,
						'station_profile' => $qso->station_profile_name,
						'ituzone' => $qso->col_ituz,
						'gridsquare' => $qso->col_gridsquare,
						'band' => $qso->col_band,
						'itugeo' => $zone['itu_zone_number'],
						'zone_type' => 'ITU',
					];
				}
			} else {
				// CQ Zone (default)
				if (!isset($zone['cq_zone_number'])) {
					continue;
				}

				if ($qso->col_cqz != $zone['cq_zone_number']) {
					$result[] = [
						'id' => $qso->col_primary_key,
						'qso_date' => $qso->col_time_on,
						'callsign' => $qso->col_call,
						'station_profile' => $qso->station_profile_name,
						'cqzone' => $qso->col_cqz,
						'gridsquare' => $qso->col_gridsquare,
						'band' => $qso->col_band,
						'cqgeo' => $zone['cq_zone_number'],
						'zone_type' => 'CQ',
					];
				}
			}
        }

		// End clock time in seconds
        $end_time = microtime(true);

        // Calculate script execution time
        $execution_time = ($end_time - $start_time);

        $data['execution_time'] = $execution_time;
        $data['calls_tested'] = $i;
        $data['cache_hits'] = $hits;
        $data['cache_misses'] = $misses;
        $data['cache_hit_rate'] = $i > 0 ? round(($hits / $i) * 100, 2) : 0;
		$data['result'] = $result;
		$data['zone_type'] = $zoneType;

		$this->loadView($data);
	}

	public function findCqZoneFromGridsquare($gridsquare, $type) {
        $coords = $this->geojson->gridsquareToLatLng($gridsquare);

        if ($coords === null) {
            return null;
        }

        return $this->geojson->findFeatureContainingPoint($coords['lat'], $coords['lng'], $this->geojsonData, $this->spatialIndex);
    }

	function loadView($data) {
		$this->load->view('zonechecker/result', $data);
	}

	/* Returns lat/lng of a gridsquare plus the geojson zone feature that
	   contains it, for plotting on a map dialog. */
	function mapData() {
		$gridsquare = $this->input->post('gridsquare', true);
		$zoneType = ($this->input->post('zoneType', true) === 'itu') ? 'itu' : 'cq';

		header('Content-Type: application/json');

		if ($this->geojsonFile === null) {
			$this->geojsonFile = ($zoneType === 'itu')
				? "assets/json/geojson/ituzones.geojson"
				: "assets/json/geojson/cqzones.geojson";
			$this->geojsonData = $this->geojson->loadGeoJsonFile($this->geojsonFile);
			$this->spatialIndex = $this->geojson->buildSpatialIndex($this->geojsonData);
		}

		if ($this->geojsonData === null) {
			echo json_encode(['error' => __("GeoJSON data unavailable")]);
			return;
		}

		$coords = $this->geojson->gridsquareToLatLng($gridsquare);
		if ($coords === null) {
			echo json_encode(['error' => __("Invalid gridsquare")]);
			return;
		}

		$props = $this->geojson->findFeatureContainingPoint($coords['lat'], $coords['lng'], $this->geojsonData, $this->spatialIndex);

		$zoneField = ($zoneType === 'itu') ? 'itu_zone_number' : 'cq_zone_number';
		$feature = null;
		if ($props !== null && isset($props[$zoneField])) {
			$target = $props[$zoneField];
			foreach ($this->geojsonData['features'] as $f) {
				if (isset($f['properties'][$zoneField]) && $f['properties'][$zoneField] == $target) {
					$feature = $f;
					break;
				}
			}
		}

		echo json_encode([
			'lat' => $coords['lat'],
			'lng' => $coords['lng'],
			'zone_type' => $zoneType,
			'feature' => $feature,
		]);
	}


}
