<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Map extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}

	function index() {
		redirect('dashboard');
    }

	/**
	 * QSO Map with country selection and OpenStreetMap
	 */
	public function qso_map() {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->library('Geojson');
		$this->load->model('Map_model');
		$this->load->model('stations');

		// Get supported DXCC countries with state data
		$data['supported_dxccs'] = $this->geojson->getSupportedDxccs();

		$supported_country_codes = array_keys($data['supported_dxccs']);

		// Fetch available countries from the logbook
		$data['countries'] = $this->Map_model->get_available_countries($supported_country_codes);

		// Fetch station profiles
		$data['station_profiles'] = $this->stations->all_of_user()->result();

		$data['homegrid'] = explode(',', $this->stations->find_gridsquare());

		$data['page_title'] = __("QSO Map");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/leaflet/geocoding.js',
			'assets/js/leaflet/L.Maidenhead.js',
			'assets/js/sections/qso_map.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/qso_map.js")),
			'assets/js/sections/itumap_geojson.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/itumap_geojson.js")),
			'assets/js/sections/cqmap_geojson.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/cqmap_geojson.js")),
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('map/qso_map');
		$this->load->view('interface_assets/footer', $footerData);
	}

	/**
	 * AJAX endpoint to get QSO data for a specific country
	 */
	public function get_qsos_for_country() {
		$this->load->model('Map_model');
		$this->load->library('Geojson');
		$country = $this->input->post('country', true);
		$dxcc = $this->input->post('dxcc', true);
		$station_id = $this->input->post('station_id', true);

		if (empty($country)) {
			while (ob_get_level()) ob_end_clean();
			$this->output
				->set_content_type('application/json')
				->set_output(json_encode(['error' => 'Country not specified']));
			return;
		}

		// Convert "all" to null for all stations
		$station_id = ($station_id === 'all') ? null : $station_id;

		try {
			$qsos = $this->Map_model->get_qsos_by_country($country, $station_id);

			if (empty($qsos)) {
				while (ob_get_level()) ob_end_clean();
				$this->output
					->set_content_type('application/json')
					->set_output(json_encode(['error' => 'No QSOs found with 6+ character gridsquares']));
				return;
			}
		} catch (Exception $e) {
			while (ob_get_level()) ob_end_clean();
			$this->output
				->set_content_type('application/json')
				->set_output(json_encode(['error' => 'Database query failed: ' . $e->getMessage()]));
			return;
		}

		// Check if QSOs are inside GeoJSON boundaries
		try {
			if ($country === 'all') {
				// For all countries, optimize by caching GeoJSON files and checking in batches
				$geojsonCache = [];
				foreach ($qsos as &$qso) {
					if ($qso['COL_DXCC'] && $this->geojson->isStateSupported($qso['COL_DXCC'])) {
						$dxcc = $qso['COL_DXCC'];

						// Cache GeoJSON data to avoid repeated file loading
						if (!isset($geojsonCache[$dxcc])) {
							$geojsonFile = "assets/json/geojson/states_{$dxcc}.geojson";
							$geojsonCache[$dxcc] = $this->geojson->loadGeoJsonFile($geojsonFile);
						}

						$geojsonData = $geojsonCache[$dxcc];
						if ($geojsonData !== null) {
							$state = $this->geojson->findFeatureContainingPoint($qso['lat'], $qso['lng'], $geojsonData);
							$qso['inside_geojson'] = ($state !== null);
							$qso['state_info'] = $state;
						} else {
							$qso['inside_geojson'] = true; // Assume inside if no GeoJSON file
							$qso['state_info'] = null;
						}
					} else {
						$qso['inside_geojson'] = true; // Assume inside for countries without GeoJSON
						$qso['state_info'] = null;
					}
				}
				// Free cache memory
				unset($geojsonCache);
			} elseif ($dxcc && $this->geojson->isStateSupported($dxcc)) {
				// For single country, use original logic
				$geojsonFile = "assets/json/geojson/states_{$dxcc}.geojson";
				$geojsonData = $this->geojson->loadGeoJsonFile($geojsonFile);

				if ($geojsonData !== null) {
					// Check each QSO if it's inside the GeoJSON
					foreach ($qsos as &$qso) {
						$state = $this->geojson->findFeatureContainingPoint($qso['lat'], $qso['lng'], $geojsonData);
						$qso['inside_geojson'] = ($state !== null);
						$qso['state_info'] = $state;
					}
				}
			}
		} catch (Exception $e) {
			// If GeoJSON processing fails, log error but continue without boundary checking
			log_message('error', 'GeoJSON processing error: ' . $e->getMessage());
			foreach ($qsos as &$qso) {
				if (!isset($qso['inside_geojson'])) {
					$qso['inside_geojson'] = true;
					$qso['state_info'] = null;
				}
			}
		}

		// Clear any output buffers that might contain warnings/errors
		while (ob_get_level()) {
			ob_end_clean();
		}

		// Set proper content type header
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($qsos));
		}

	/**
	 * Get country boundaries as GeoJSON
	 */
	public function get_country_geojson() {
		$dxcc = $this->input->post('dxcc', true);
		$this->load->library('geojson');

 		if (!$this->geojson->isStateSupported($dxcc)) {
            return null;
        }

		$geojsonFile = "assets/json/geojson/states_{$dxcc}.geojson";
		$geojsonData = $this->geojson->loadGeoJsonFile($geojsonFile);

		if ($geojsonData === null) {
			echo json_encode(['error' => 'GeoJSON file not found']);
			return;
		}

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($geojsonData));
	}

	/**
	 * Get all supported DXCC countries with GeoJSON
	 */
	public function get_all_supported_countries() {
		$this->load->library('Geojson');
		$supported_dxccs = $this->geojson->getSupportedDxccs();

		$country_list = [];
		foreach ($supported_dxccs as $dxcc => $data) {
			$geojsonFile = "assets/json/geojson/states_{$dxcc}.geojson";
			if (file_exists(FCPATH . $geojsonFile)) {
				$country_list[] = [
					'dxcc' => $dxcc,
					'name' => $data['name'],
					'geojson_file' => $geojsonFile
				];
			}
		}

		echo json_encode($country_list);
	}

	// Generic fonction for return Json for MAP //
	public function map_plot_json() {
		$this->load->model('Stations');
		$this->load->model('logbook_model');

		// set informations //
		$nb_qso = (intval($this->input->post('nb_qso'))>0)?xss_clean($this->input->post('nb_qso')):18;
		$offset = (intval($this->input->post('offset'))>0)?xss_clean($this->input->post('offset')):null;
		$qsos = $this->logbook_model->get_qsos($nb_qso, $offset, null, '', true);
		// [PLOT] ADD plot //
		$plot_array = $this->logbook_model->get_plot_array_for_map($qsos->result());
		// [MAP Custom] ADD Station //
		$station_array = $this->Stations->get_station_array_for_map();

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(array_merge($plot_array, $station_array));
	}

}
