<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Zonechecker extends CI_Controller {
	private $geojsonFile = null;
	private $geojsonData = null;
	private $qralib;
	private $gridsquareCache = array(); // Cache gridsquare->zone lookups
	private $spatialIndex = null; // Spatial index for faster lookups
	private $featureBoundingBoxes = array(); // Pre-calculated bounding boxes

	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {

		$this->load->model('stations');

		$data['station_profile'] = $this->stations->all_of_user();

		$data['page_title'] = __("Gridsquare Zone finder");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('zonechecker/index');
		$this->load->view('interface_assets/footer');
	}

	function getQsos($station_id) {
		$sql = 'select distinct col_country, col_call, col_dxcc, col_time_on, station_profile.station_profile_name, col_primary_key, col_cqz, col_ituz, col_gridsquare
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
			$this->geojsonData = $this->loadGeoJsonFile($this->geojsonFile);
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

	public function loadGeoJsonFile($filepath) {
        $fullpath = FCPATH . $filepath;

        if (!file_exists($fullpath)) {
            return null;
        }

        $geojsonData = file_get_contents($fullpath);

        if ($geojsonData === false) {
            return null;
        }

        // Remove BOM if present (UTF-8, UTF-16, UTF-32)
        $geojsonData = preg_replace('/^\xEF\xBB\xBF|\xFF\xFE|\xFE\xFF|\x00\x00\xFE\xFF|\xFF\xFE\x00\x00/', '', $geojsonData);

        // Additional cleanup: trim whitespace
        $geojsonData = trim($geojsonData);

        $data = json_decode($geojsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Pre-process: calculate bounding boxes and build spatial index
        $this->preProcessGeoJson($data);

        return $data;
    }

    /**
     * Pre-process GeoJSON to calculate bounding boxes and build spatial index
     * This dramatically speeds up point-in-polygon lookups
     */
    private function preProcessGeoJson(&$geojsonData) {
        if (!isset($geojsonData['features']) || !is_array($geojsonData['features'])) {
            return;
        }

        $this->featureBoundingBoxes = array();
        $this->spatialIndex = array(
            'minLng' => 180,
            'maxLng' => -180,
            'minLat' => 90,
            'maxLat' => -90,
            'grid' => array() // 10x10 degree grid for coarse filtering
        );

        foreach ($geojsonData['features'] as $index => &$feature) {
            $bbox = $this->calculateFeatureBoundingBox($feature);

            if ($bbox !== null) {
                // Store bbox for quick access
                $feature['bbox'] = $bbox;
                $this->featureBoundingBoxes[$index] = $bbox;

                // Update global bounds
                $this->spatialIndex['minLng'] = min($this->spatialIndex['minLng'], $bbox[0]);
                $this->spatialIndex['maxLng'] = max($this->spatialIndex['maxLng'], $bbox[2]);
                $this->spatialIndex['minLat'] = min($this->spatialIndex['minLat'], $bbox[1]);
                $this->spatialIndex['maxLat'] = max($this->spatialIndex['maxLat'], $bbox[3]);

                // Add to spatial grid (10x10 degree cells)
                $minGridLng = floor($bbox[0] / 10) * 10;
                $maxGridLng = floor($bbox[2] / 10) * 10;
                $minGridLat = floor($bbox[1] / 10) * 10;
                $maxGridLat = floor($bbox[3] / 10) * 10;

                for ($lng = $minGridLng; $lng <= $maxGridLng; $lng += 10) {
                    for ($lat = $minGridLat; $lat <= $maxGridLat; $lat += 10) {
                        $key = $lng . ',' . $lat;
                        if (!isset($this->spatialIndex['grid'][$key])) {
                            $this->spatialIndex['grid'][$key] = array();
                        }
                        $this->spatialIndex['grid'][$key][] = $index;
                    }
                }
            }
        }
    }

    /**
     * Calculate bounding box for a feature
     * @return array|null [minLng, minLat, maxLng, maxLat] or null
     */
    private function calculateFeatureBoundingBox($feature) {
        if (!isset($feature['geometry']['coordinates']) || !isset($feature['geometry']['type'])) {
            return null;
        }

        $geometryType = $feature['geometry']['type'];
        $coordinates = $feature['geometry']['coordinates'];

        $minLng = 180;
        $maxLng = -180;
        $minLat = 90;
        $maxLat = -90;

        if ($geometryType === 'Polygon') {
            $this->updateBoundsFromCoords($coordinates[0], $minLng, $minLat, $maxLng, $maxLat);
        } elseif ($geometryType === 'MultiPolygon') {
            foreach ($coordinates as $polygon) {
                $this->updateBoundsFromCoords($polygon[0], $minLng, $minLat, $maxLng, $maxLat);
            }
        } else {
            return null;
        }

        if ($minLng > $maxLng || $minLat > $maxLat) {
            return null;
        }

        return array($minLng, $minLat, $maxLng, $maxLat);
    }

    /**
     * Update min/max bounds from coordinate array
     */
    private function updateBoundsFromCoords($coords, &$minLng, &$minLat, &$maxLng, &$maxLat) {
        foreach ($coords as $point) {
            $lng = $point[0];
            $lat = $point[1];
            $minLng = min($minLng, $lng);
            $maxLng = max($maxLng, $lng);
            $minLat = min($minLat, $lat);
            $maxLat = max($maxLat, $lat);
        }
    }

	public function findCqZoneFromGridsquare($gridsquare, $type) {
        $coords = $this->gridsquareToLatLng($gridsquare);

        if ($coords === null) {
            return null;
        }

        return $this->findFeatureContainingPoint($coords['lat'], $coords['lng'],  $this->geojsonData);
    }

	 // ============================================================================
    // GEOMETRIC ALGORITHMS - Point-in-polygon detection
    // ============================================================================

    /**
     * Check if a point (latitude, longitude) is inside a polygon
     * Uses optimized ray casting algorithm
     *
     * @param float $lat Latitude of the point
     * @param float $lng Longitude of the point
     * @param array $polygon GeoJSON polygon coordinates array [[[lng, lat], [lng, lat], ...]]
     * @return bool True if point is inside polygon, false otherwise
     */
    public function isPointInPolygon($lat, $lng, $polygon) {
        if (!is_numeric($lat) || !is_numeric($lng) || !is_array($polygon) || empty($polygon)) {
            return false;
        }

        $inside = false;
        $count = count($polygon);

        // Ray casting algorithm - optimized with minimal variable assignments
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $lat) != ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            $inside ^= $intersect;
        }

        return $inside;
    }

    /**
     * Find which feature in a GeoJSON FeatureCollection contains a given point
     * Optimized with spatial indexing and bounding box pre-checks
     *
     * @param float $lat Latitude of the point
     * @param float $lng Longitude of the point
     * @param array $geojsonData Decoded GeoJSON FeatureCollection
     * @return array|null Feature properties if found, null otherwise
     */
    public function findFeatureContainingPoint($lat, $lng, $geojsonData) {
        if (!isset($geojsonData['features']) || !is_array($geojsonData['features'])) {
            return null;
        }

        // Early exit: check global bounds
        if ($this->spatialIndex !== null) {
            if ($lng < $this->spatialIndex['minLng'] || $lng > $this->spatialIndex['maxLng'] ||
                $lat < $this->spatialIndex['minLat'] || $lat > $this->spatialIndex['maxLat']) {
                return null;
            }
        }

        // Use spatial index to get candidate features
        $candidateIndices = $this->getCandidateFeatures($lat, $lng);

        // If no spatial index, fall back to checking all features
        if ($candidateIndices === null) {
            $candidateIndices = array_keys($geojsonData['features']);
        }

        // Check only candidate features
        foreach ($candidateIndices as $index) {
            if (!isset($geojsonData['features'][$index])) {
                continue;
            }

            $feature = $geojsonData['features'][$index];

            if (!isset($feature['geometry']['coordinates']) || !isset($feature['geometry']['type'])) {
                continue;
            }

            // Fast bounding box check (always available now due to pre-processing)
            if (isset($feature['bbox'])) {
                $bbox = $feature['bbox'];
                if ($lng < $bbox[0] || $lng > $bbox[2] || $lat < $bbox[1] || $lat > $bbox[3]) {
                    continue; // Point is outside bounding box, skip detailed check
                }
            }

            $geometryType = $feature['geometry']['type'];
            $coordinates = $feature['geometry']['coordinates'];

            // Handle Polygon geometry
            if ($geometryType === 'Polygon') {
                // For Polygon, coordinates[0] is the outer ring
                if ($this->isPointInPolygon($lat, $lng, $coordinates[0])) {
                    return $feature['properties'];
                }
            }
            // Handle MultiPolygon geometry
            elseif ($geometryType === 'MultiPolygon') {
                foreach ($coordinates as $polygon) {
                    // For MultiPolygon, each polygon is [[[lng,lat],...]]
                    // We need to pass just the outer ring (first element)
                    if ($this->isPointInPolygon($lat, $lng, $polygon[0])) {
                        return $feature['properties'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get candidate features that might contain the point using spatial index
     * @return array|null Array of feature indices or null if index not available
     */
    private function getCandidateFeatures($lat, $lng) {
        if ($this->spatialIndex === null || !isset($this->spatialIndex['grid'])) {
            return null;
        }

        // Find which grid cell the point falls into
        $gridLng = floor($lng / 10) * 10;
        $gridLat = floor($lat / 10) * 10;
        $key = $gridLng . ',' . $gridLat;

        if (isset($this->spatialIndex['grid'][$key])) {
            return $this->spatialIndex['grid'][$key];
        }

        // If not found in grid, return null (fallback to all features)
        return null;
    }

	public function gridsquareToLatLng($gridsquare) {
		if (!$this->qralib) {
			$this->load->library('Qra');
			$this->qralib = $this->qra;
		}

        if (!is_string($gridsquare) || strlen($gridsquare) < 2) {
            return null;
        }

        $result = $this->qralib->qra2latlong($gridsquare);

        if ($result === false || !is_array($result) || count($result) < 2) {
            return null;
        }

        // Qra library returns [lat, lng], we need to return associative array
        return [
            'lat' => $result[0],
            'lng' => $result[1]
        ];
    }

	function loadView($data) {
		$this->load->view('zonechecker/result', $data);
	}


}
