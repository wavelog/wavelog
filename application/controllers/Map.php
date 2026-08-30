<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Map extends CI_Controller {

	function __construct()
	{
		parent::__construct();

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
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->library('Geojson');
		$this->load->model('Map_model');
		$this->load->model('stations');

		// Get supported DXCC countries (those with GeoJSON boundary data)
		$data['supported_dxccs'] = $this->geojson->getSupportedDxccs();

		$supported_country_codes = array_keys($data['supported_dxccs']);

		// List all GeoJSON-supported DXCC countries, independent of the logbook
		$data['countries'] = $this->Map_model->get_available_countries($supported_country_codes);

		// Fetch station profiles
		$data['station_profiles'] = $this->stations->all_of_user()->result();

		// Active station location, to pre-select in the location dropdown
		$data['active_station_id'] = $this->stations->find_active();

		// Worked bands for the band filter
		$this->load->model('bands');
		$data['bands'] = $this->bands->get_worked_bands();

		// User's custom map colors (worked / confirmed) for the region choropleth
		$data['user_map_custom'] = $this->optionslib->get_map_custom();

		$data['homegrid'] = explode(',', $this->stations->find_gridsquare());

		$data['page_title'] = __("QSO Map");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/leaflet/geocoding.js',
			'assets/js/leaflet/L.Maidenhead.js',
			'assets/js/sections/qso_map.js',
			'assets/js/sections/itumap_geojson.js',
			'assets/js/sections/cqmap_geojson.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('map/qso_map');
		$this->load->view('interface_assets/footer', $footerData);
	}

	/**
 * Propagation map
 */
public function propagation() {
        if (!$this->user_model->authorize(99)) {
                $this->session->set_flashdata('error', __("You're not allowed to do that!"));
                redirect('dashboard');
        }

        $this->load->model('stations');

        $data['homegrid'] = explode(',', $this->stations->find_gridsquare());
        $data['page_title'] = __("Propagation Map");

        $footerData = [];
        $footerData['scripts'] = [
                'assets/js/leaflet/L.Maidenhead.js',
                'assets/js/leaflet/L.Terminator.js',
                'assets/js/sections/propagation_map.js',
        ];

        $this->load->view('interface_assets/header', $data);
        $this->load->view('map/propagation');
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
		$band = $this->input->post('band', true);

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
			$qsos = $this->Map_model->get_qsos_by_country($country, $station_id, $band);
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

		// Gridsquares that belong to the selected DXCC, used to restrict the
		// maidenhead grid to that DXCC (same source as the gridmap).
		$this->load->model('gridmap_model');
		$grid_dxcc = $this->input->post('dxcc', true);
		$grids = $grid_dxcc ? $this->gridmap_model->get_grids_for_country($grid_dxcc) : [];

		// Set proper content type header
		$this->output
			->set_content_type('application/json')
			->set_output(json_encode(['qsos' => $qsos, 'grids' => $grids]));
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
        public function get_heard_me() {
                $this->load->model('stations');

                $active_id = $this->stations->find_active();

                if ($active_id === "0") {
                        return $this->output
                                ->set_content_type('application/json')
                                ->set_output(json_encode([
                                        'error' => 'No active station profile'
                                ]));
                }

                $station = $this->stations->profile($active_id)->row();

                if (!$station || empty($station->station_callsign)) {
                        return $this->output
                                ->set_content_type('application/json')
                                ->set_output(json_encode([
                                        'error' => 'No callsign configured for active station'
                                ]));
                }

                $callsign = strtoupper(trim($station->station_callsign));

                $query = http_build_query([
                        'senderCallsign'   => $callsign,
                        'flowStartSeconds' => -900,
                        'mode'             => 'FT8',
                        'frange'           => '28000000-29700000',
                        'rptlimit'         => 500,
                        'rronly'           => 1,
                        'noactive'         => 1
                ]);

                $url = 'https://retrieve.pskreporter.info/query?' . $query;

                $context = stream_context_create([
                        'http' => [
                                'timeout' => 10,
                                'user_agent' => 'Wavelog Propagation Map'
                        ]
                ]);
	
	$xml = @file_get_contents($url, false, $context);

	if ($xml === false) {
        $last_error = error_get_last();

        return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                        'error' => 'Could not retrieve PSK Reporter data',
                        'detail' => $last_error['message'] ?? 'Unknown error',
                        'url' => $url
                ]));
}
		             

                libxml_use_internal_errors(true);
                $data = simplexml_load_string($xml);

                if ($data === false) {
                        return $this->output
                                ->set_content_type('application/json')
                                ->set_output(json_encode([
                                        'error' => 'Invalid PSK Reporter response'
                                ]));
                }

                $reports = [];

                foreach ($data->receptionReport as $report) {
                        $a = $report->attributes();

                        if (empty($a['receiverLocator'])) {
                                continue;
                        }

                        $reports[] = [
                                'receiver_callsign' => (string) $a['receiverCallsign'],
                                'receiver_locator'  => (string) $a['receiverLocator'],
                                'sender_callsign'   => (string) $a['senderCallsign'],
                                'frequency'         => (int) $a['frequency'],
                                'snr'               => isset($a['sNR']) ? (int) $a['sNR'] : null,
                                'mode'              => isset($a['mode']) ? (string) $a['mode'] : '',
                                'timestamp'         => (int) $a['flowStartSeconds']
                        ];
                }

                return $this->output
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                                'callsign' => $callsign,
                                'reports'  => $reports
                        ]));
        }

}
