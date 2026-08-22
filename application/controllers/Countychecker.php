<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Countychecker extends CI_Controller {
	private $qralib;
	private $gridsquareCache = array(); // Cache gridsquare->county lookups
	private $datasets = array();        // dxcc => ['data' => geojson array, 'index' => spatial index]

	/* USACAG county names that no longer exist as polygons in the county
	   geojson files map to their modern equivalents instead of being reported
	   as mismatches. Connecticut switched from 8 counties to 9 planning
	   regions in 2022; the AK entries cover pseudo-counties (islands without
	   boundaries) and renamed/merged boroughs; Bedford City merged back into
	   Bedford County. Keys and targets are normalized county names
	   (see normCounty()). */
	private static $countyAliases = array(
		'CT' => array(
			'fairfield' => array('westernconnecticut', 'greaterbridgeport', 'naugatuckvalley'),
			'hartford' => array('capitol'),
			'litchfield' => array('northwesthills'),
			'middlesex' => array('lowerconnecticutrivervalley'),
			'newhaven' => array('southcentralconnecticut', 'naugatuckvalley'),
			'newlondon' => array('southeasternconnecticut', 'lowerconnecticutrivervalley'),
			'tolland' => array('northeasternconnecticut', 'capitol'),
			'windham' => array('northeasternconnecticut'),
		),
		'AK' => array(
			'fairbanks' => array('fairbanksnorthstar'),
			'pribilofislands' => array('aleutianswest'),
			'stmatthewisland' => array('aleutianswest'),
			'valdezcordova' => array('chugach', 'copperriver'),
			'waleshyder' => array('princeofwaleshyder'),
		),
		'VA' => array(
			'bedfordcity' => array('bedford'),
		),
	);

	/* The county geojson files by DXCC: 291 = contiguous US, 6 = Alaska,
	   110 = Hawaii. A point lookup searches all three, which also catches
	   QSOs logged against the wrong state/DXCC. */
	private static $countyFiles = array('291', '6', '110');

	function __construct() {
		parent::__construct();

		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {

		$this->load->model('stations');

		$data['station_profile'] = $this->stations->all_of_user();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/countychecker.js',
		];

		$data['page_title'] = __("Gridsquare County checker");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('countychecker/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	function getQsos($station_id, $include_missing = false) {
		$sql = 'select col_call, col_dxcc, col_state, col_cnty, col_time_on, station_profile.station_profile_name, col_primary_key, col_gridsquare, col_band,
				col_qsl_rcvd, col_qslrdate, col_lotw_qsl_rcvd, col_lotw_qslrdate, lotw_users.lastupload
			from ' . $this->config->item('table_name') . '
			join station_profile on ' . $this->config->item('table_name') . '.station_id = station_profile.station_id
			left outer join lotw_users on lotw_users.callsign = ' . $this->config->item('table_name') . '.col_call
			where station_profile.user_id = ?
			and length(col_gridsquare) >= 6';
		if ($include_missing) {
			// QSOs with a logged county, plus US-DXCC QSOs without one (whose
			// county could be backfilled from the gridsquare)
			$sql .= ' and (coalesce(col_cnty, \'\') <> \'\' or col_dxcc in (\'291\', \'6\', \'110\'))';
		} else {
			$sql .= ' and coalesce(col_cnty, \'\') <> \'\'';
		}
		$params[] = $this->session->userdata('user_id');

		if ($station_id && is_numeric($station_id)) {
			$sql .= ' and ' . $this->config->item('table_name') . '.station_id = ?';
			$params[] = $station_id;
		}

		$sql .= ' order by station_profile.station_profile_name asc, col_time_on desc';

		$query = $this->db->query($sql, $params);

		return $query;
	}

	function doCountyCheck() {
		set_time_limit(3600);
		// Decoding the three county geojson files peaks at ~256 MB
		ini_set('memory_limit', '512M');

		$de = $this->input->post('de', true);
		$include_missing = $this->input->post('includeMissing', true) === '1';

		$i = 0;
		$not_found = 0;
		$missing = 0;
		$result = array();
		$this->gridsquareCache = array(); // Reset cache

		$callarray = $this->getQsos($de, $include_missing)->result();
		if (!empty($callarray)) {
			$this->loadCountyDatasets();
		}

		// Starting clock time in seconds
		$start_time = microtime(true);

		$hits = 0; // Track cache hits for performance metrics
		$misses = 0; // Track cache misses

		foreach ($callarray as $qso) {
			$i++;
			$gridsquare = $qso->col_gridsquare;

			// Check cache first - avoid redundant gridsquare->county conversions
			if (!isset($this->gridsquareCache[$gridsquare])) {
				$county = null;
				$boxcount = 1;
				$coords = $this->gridsquareToLatLng($gridsquare);
				if ($coords !== null) {
					$county = $this->findCountyFromLatlng($coords['lat'], $coords['lng']);
					if ($county !== null) {
						// How many counties the grid box overlaps: more than
						// one means the grid straddles a county line and the
						// center-derived county is uncertain.
						$box = $this->gridBox($gridsquare, $coords['lat'], $coords['lng']);
						if ($box !== null) {
							$boxcount = max(1, $this->countiesIntersectingBox($box));
						}
					}
				}
				$this->gridsquareCache[$gridsquare] = $county === null
					? array('notfound' => true)
					: array('state' => $county['state'], 'county' => $county['county'], 'boxcount' => $boxcount);
				$county = $this->gridsquareCache[$gridsquare];
				$misses++;
			} else {
				$county = $this->gridsquareCache[$gridsquare];
				$hits++;
			}

			// Gridsquare resolved to water/nowhere with a county polygon - skip
			if (!isset($county['state'])) {
				$not_found++;
				continue;
			}

			// COL_CNTY is "STATE,COUNTY" (ADIF); fall back to COL_STATE when
			// the county was logged without the state prefix.
			$parts = explode(',', (string)$qso->col_cnty, 2);
			$logged_county = trim($parts[count($parts) - 1]);
			$logged_state = strtoupper(trim(count($parts) == 2 ? $parts[0] : (string)$qso->col_state));

			if ($logged_county === '') {
				// No county logged but the gridsquare resolves to one - a
				// candidate for backfilling, not a mismatch.
				$missing++;
				$result[] = [
					'id' => $qso->col_primary_key,
					'qso_date' => $qso->col_time_on,
					'callsign' => $qso->col_call,
					'station_profile' => $qso->station_profile_name,
					'gridsquare' => $qso->col_gridsquare,
					'band' => $qso->col_band,
					'logged_state' => $logged_state,
					'logged_county' => '',
					'derived_state' => $county['state'],
					'derived_county' => $county['county'],
					'confidence' => intdiv(100, max(1, $county['boxcount'])),
					'boxcount' => $county['boxcount'],
					'missing' => true,
					'lotw_lastupload' => $qso->lastupload,
					'qsl_rcvd' => $qso->col_qsl_rcvd,
					'qslrdate' => $qso->col_qslrdate,
					'lotw_rcvd' => $qso->col_lotw_qsl_rcvd,
					'lotwrdate' => $qso->col_lotw_qslrdate,
				];
				continue;
			}

			if ($this->countyMatches($logged_state, $logged_county, $county['state'], $county['county'])) {
				continue;
			}

			$result[] = [
				'id' => $qso->col_primary_key,
				'qso_date' => $qso->col_time_on,
				'callsign' => $qso->col_call,
				'station_profile' => $qso->station_profile_name,
				'gridsquare' => $qso->col_gridsquare,
				'band' => $qso->col_band,
				'logged_state' => $logged_state,
				'logged_county' => $logged_county,
				'derived_state' => $county['state'],
				'derived_county' => $county['county'],
				'confidence' => intdiv(100, max(1, $county['boxcount'])),
				'boxcount' => $county['boxcount'],
				'lotw_lastupload' => $qso->lastupload,
				'qsl_rcvd' => $qso->col_qsl_rcvd,
				'qslrdate' => $qso->col_qslrdate,
				'lotw_rcvd' => $qso->col_lotw_qsl_rcvd,
				'lotwrdate' => $qso->col_lotw_qslrdate,
			];
		}

		// End clock time in seconds
		$end_time = microtime(true);

		// Calculate script execution time
		$execution_time = ($end_time - $start_time);

		$data['execution_time'] = $execution_time;
		$data['calls_tested'] = $i;
		$data['not_found'] = $not_found;
		$data['missing'] = $missing;
		$data['cache_hits'] = $hits;
		$data['cache_misses'] = $misses;
		$data['cache_hit_rate'] = $i > 0 ? round(($hits / $i) * 100, 2) : 0;
		$data['result'] = $result;

		$this->loadView($data);
	}

	/* Lowercased letters only, with "St", "Ste", "Saint" and "Sainte" all
	   treated as the same word, so "St. Clair", "Saint Clair", "Ste.
	   Genevieve" and "Sainte Genevieve" compare equal - as do "La Salle"
	   and "LaSalle". */
	private static function normCounty($county) {
		$s = strtolower((string)$county);
		$s = preg_replace('/\bsainte?\b|\bste\b/', 'st', $s);
		return preg_replace('/[^a-z0-9]/', '', $s);
	}

	/* True when the logged state/county can be considered the same county as
	   the one derived from the geojson polygons. */
	private function countyMatches($logged_state, $logged_county, $derived_state, $derived_county) {
		if ($logged_state !== '' && strtoupper($logged_state) !== strtoupper($derived_state)) {
			return false;
		}

		if (self::normCounty($logged_county) === self::normCounty($derived_county)) {
			return true;
		}

		$aliases = self::$countyAliases[strtoupper($derived_state)][self::normCounty($logged_county)] ?? array();
		return in_array(self::normCounty($derived_county), $aliases, true);
	}

	// ============================================================================
	// COUNTY DATASETS
	// ============================================================================

	private function loadCountyDatasets() {
		foreach (self::$countyFiles as $dxcc) {
			if (!isset($this->datasets[$dxcc])) {
				$data = $this->loadGeoJsonFile("assets/json/geojson/counties_{$dxcc}.geojson");
				if ($data === null) {
					$this->datasets[$dxcc] = array('data' => null, 'index' => null);
					continue;
				}
				// Build the index (which bakes bounding boxes into $data's
				// features) before copying $data into the dataset - PHP arrays
				// are copied by value, so doing both in one array literal would
				// store the features without their bounding boxes.
				$index = $this->buildSpatialIndex($data);
				$this->datasets[$dxcc] = array(
					'data' => $data,
					'index' => $index,
				);
			}
		}
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

		return $data;
	}

	/**
	 * Pre-process a decoded FeatureCollection: bake a bounding box into every
	 * feature and build a coarse 10x10 degree grid index over them. This
	 * dramatically speeds up point-in-polygon lookups.
	 */
	private function buildSpatialIndex(&$geojsonData) {
		$index = array(
			'minLng' => 180,
			'maxLng' => -180,
			'minLat' => 90,
			'maxLat' => -90,
			'grid' => array()
		);

		if (!isset($geojsonData['features']) || !is_array($geojsonData['features'])) {
			return $index;
		}

		foreach ($geojsonData['features'] as $i => &$feature) {
			$bbox = $this->calculateFeatureBoundingBox($feature);

			if ($bbox === null) {
				continue;
			}

			$feature['bbox'] = $bbox;

			$index['minLng'] = min($index['minLng'], $bbox[0]);
			$index['maxLng'] = max($index['maxLng'], $bbox[2]);
			$index['minLat'] = min($index['minLat'], $bbox[1]);
			$index['maxLat'] = max($index['maxLat'], $bbox[3]);

			for ($lng = floor($bbox[0] / 10) * 10; $lng <= $bbox[2]; $lng += 10) {
				for ($lat = floor($bbox[1] / 10) * 10; $lat <= $bbox[3]; $lat += 10) {
					$index['grid'][$lng . ',' . $lat][] = $i;
				}
			}
		}

		return $index;
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
			$minLng = min($minLng, $point[0]);
			$maxLng = max($maxLng, $point[0]);
			$minLat = min($minLat, $point[1]);
			$maxLat = max($maxLat, $point[1]);
		}
	}

	/**
	 * Find the county containing a point across all three county datasets
	 * @return array|null Feature properties ({state, county}) or null
	 */
	private function findCountyFromLatlng($lat, $lng) {
		foreach (self::$countyFiles as $dxcc) {
			$props = $this->findFeatureContainingPoint($lat, $lng, $this->datasets[$dxcc]);
			if ($props !== null) {
				return $props;
			}
		}
		return null;
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
	 * Find which feature in a county dataset contains a given point, using
	 * the dataset's spatial index and per-feature bounding boxes
	 *
	 * @param float $lat Latitude of the point
	 * @param float $lng Longitude of the point
	 * @param array $dataset Entry from $this->datasets
	 * @return array|null Feature properties if found, null otherwise
	 */
	public function findFeatureContainingPoint($lat, $lng, $dataset) {
		if ($dataset === null || !isset($dataset['data']['features']) || !is_array($dataset['data']['features'])) {
			return null;
		}

		$index = $dataset['index'];
		$features = $dataset['data']['features'];

		// Early exit: check global bounds
		if ($lng < $index['minLng'] || $lng > $index['maxLng'] ||
			$lat < $index['minLat'] || $lat > $index['maxLat']) {
			return null;
		}

		// Only features whose bounding box covers this 10x10 degree cell can
		// contain the point; an unregistered cell means open water/land
		// without county polygons.
		$key = floor($lng / 10) * 10 . ',' . floor($lat / 10) * 10;
		if (!isset($index['grid'][$key])) {
			return null;
		}

		foreach ($index['grid'][$key] as $i) {
			$feature = $features[$i];

			// Fast bounding box check
			$bbox = $feature['bbox'];
			if ($lng < $bbox[0] || $lng > $bbox[2] || $lat < $bbox[1] || $lat > $bbox[3]) {
				continue;
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
	 * Bounding box [latMin, lngMin, latMax, lngMax] of the (sub)square the
	 * locator resolves to - Qra returns the square's exact center, so the box
	 * is simply center +/- half the square's extent at the locator's
	 * precision. Returns null for a malformed locator.
	 */
	private function gridBox($gridsquare, $lat, $lng) {
		$grid = strtoupper(preg_replace('/\s+/', '', (string)$gridsquare));
		if (!preg_match('/^[A-R]{2}[0-9]{2}([A-X]{2}([0-9]{2}([A-X]{2})?)?)?$/', $grid)) {
			return null;
		}

		switch (strlen($grid)) {
			case 4:  $w = 2;        $h = 1;        break;
			case 6:  $w = 1 / 12;   $h = 1 / 24;   break;
			case 8:  $w = 1 / 120;  $h = 1 / 240;  break;
			default: $w = 1 / 2880; $h = 1 / 5760; break;
		}

		return array($lat - $h / 2, $lng - $w / 2, $lat + $h / 2, $lng + $w / 2);
	}

	/**
	 * Number of distinct counties the box overlaps with. Counties do not
	 * overlap each other, so a count of 1 means the whole grid box is inside
	 * a single county and the center-derived county is certain.
	 */
	private function countiesIntersectingBox($box) {
		list($lat_min, $lng_min, $lat_max, $lng_max) = $box;
		$count = 0;

		foreach (self::$countyFiles as $dxcc) {
			$dataset = $this->datasets[$dxcc];
			$index = $dataset['index'];

			if ($lng_min > $index['maxLng'] || $lng_max < $index['minLng'] ||
				$lat_min > $index['maxLat'] || $lat_max < $index['minLat']) {
				continue;
			}

			// Union of the spatial-index cells the box touches
			$candidates = array();
			for ($lng = floor($lng_min / 10) * 10; $lng <= $lng_max; $lng += 10) {
				for ($lat = floor($lat_min / 10) * 10; $lat <= $lat_max; $lat += 10) {
					foreach ($index['grid'][$lng . ',' . $lat] ?? array() as $i) {
						$candidates[$i] = true;
					}
				}
			}

			foreach (array_keys($candidates) as $i) {
				$feature = $dataset['data']['features'][$i];
				$bbox = $feature['bbox'];
				if ($lng_min > $bbox[2] || $lng_max < $bbox[0] || $lat_min > $bbox[3] || $lat_max < $bbox[1]) {
					continue; // bounding boxes don't even touch
				}
				if ($this->boxIntersectsFeature($lat_min, $lng_min, $lat_max, $lng_max, $feature)) {
					$count++;
				}
			}
		}

		return $count;
	}

	/**
	 * True when the box intersects any outer ring of the feature
	 */
	private function boxIntersectsFeature($lat_min, $lng_min, $lat_max, $lng_max, $feature) {
		$coordinates = $feature['geometry']['coordinates'];
		if ($feature['geometry']['type'] === 'Polygon') {
			return $this->boxIntersectsRing($lat_min, $lng_min, $lat_max, $lng_max, $coordinates[0]);
		}
		foreach ($coordinates as $polygon) {
			if ($this->boxIntersectsRing($lat_min, $lng_min, $lat_max, $lng_max, $polygon[0])) {
				return true;
			}
		}
		return false;
	}

	/**
	 * True when the box and a filled polygon's outer ring share any area:
	 * either a box corner lies inside the polygon, or some polygon vertex
	 * lies inside the box, or some polygon edge crosses into the box.
	 */
	private function boxIntersectsRing($lat_min, $lng_min, $lat_max, $lng_max, $ring) {
		$n = count($ring);

		if ($this->isPointInPolygon($lat_min, $lng_min, $ring)) return true;
		if ($this->isPointInPolygon($lat_max, $lng_max, $ring)) return true;
		if ($this->isPointInPolygon($lat_min, $lng_max, $ring)) return true;
		if ($this->isPointInPolygon($lat_max, $lng_min, $ring)) return true;

		for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
			$lng = $ring[$i][0];
			$lat = $ring[$i][1];
			if ($lng >= $lng_min && $lng <= $lng_max && $lat >= $lat_min && $lat <= $lat_max) {
				return true; // vertex inside the box
			}
			if ($this->segmentIntersectsRect($ring[$j], $ring[$i], $lat_min, $lng_min, $lat_max, $lng_max)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * True when any part of segment p1-p2 lies inside the box
	 * (Liang-Barsky clipping)
	 */
	private function segmentIntersectsRect($p1, $p2, $lat_min, $lng_min, $lat_max, $lng_max) {
		$dx = $p2[0] - $p1[0];
		$dy = $p2[1] - $p1[1];
		$t0 = 0.0;
		$t1 = 1.0;

		// [distance from p1 to the boundary, direction component] per edge
		$edges = array(
			array($p1[0] - $lng_min, -$dx),
			array($lng_max - $p1[0],  $dx),
			array($p1[1] - $lat_min, -$dy),
			array($lat_max - $p1[1],  $dy),
		);

		foreach ($edges as $edge) {
			list($q, $p) = $edge;
			if ($p == 0) {
				if ($q < 0) return false; // parallel and outside this edge
			} else {
				$r = $q / $p;
				if ($p < 0) {
					if ($r > $t1) return false;
					if ($r > $t0) $t0 = $r;
				} else {
					if ($r < $t0) return false;
					if ($r < $t1) $t1 = $r;
				}
			}
		}

		return true;
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
		$this->load->view('countychecker/result', $data);
	}

	/* Updates COL_CNTY of the posted QSOs with the state/county pairs from
	   the result table's geojson column. Only QSOs belonging to the current
	   user are touched. */
	function updateCounties() {
		header('Content-Type: application/json');

		$rows = json_decode((string)$this->input->post('qsos'), true);
		if (!is_array($rows)) {
			echo json_encode(['error' => __("Invalid request")]);
			return;
		}

		$updated = 0;
		foreach ($rows as $row) {
			$id = (int)($row['id'] ?? 0);
			$state = strtoupper(trim((string)($row['state'] ?? '')));
			$county = trim((string)($row['county'] ?? ''));

			if ($id <= 0 || !preg_match('/^[A-Z]{2}$/', $state)
				|| $county === '' || !preg_match("/^[A-Za-z0-9 .'\\-]+$/", $county)) {
				continue;
			}

			$sql = 'update ' . $this->config->item('table_name') . ' qsos
				join station_profile on station_profile.station_id = qsos.station_id
				set qsos.COL_CNTY = ?, qsos.last_modified = now()
				where qsos.COL_PRIMARY_KEY = ?
				and station_profile.user_id = ?';
			$this->db->query($sql, array($state . ',' . $county, $id, $this->session->userdata('user_id')));
			$updated += $this->db->affected_rows();
		}

		echo json_encode(['updated' => $updated]);
	}

	/* Returns the full geojson feature for a state/county (alias-aware), so
	   the map dialog can draw the logged county's outline. */
	private function findCountyFeature($state, $county) {
		$state = strtoupper(trim((string)$state));
		$wanted = self::normCounty($county);

		$names = array($wanted);
		foreach (self::$countyAliases[$state][$wanted] ?? array() as $alias) {
			$names[] = $alias;
		}

		foreach ($this->datasets as $dataset) {
			if ($dataset['data'] === null) {
				continue;
			}
			foreach ($dataset['data']['features'] as $feature) {
				$props = $feature['properties'];
				if (strtoupper($props['state']) === $state && in_array(self::normCounty($props['county']), $names, true)) {
					return $feature;
				}
			}
		}

		return null;
	}

	/* Returns lat/lng of a gridsquare plus the county features for the map
	   dialog: the one derived from the gridsquare and the logged one. */
	function mapData() {
		// Decoding the three county geojson files peaks at ~256 MB
		ini_set('memory_limit', '512M');

		$gridsquare = $this->input->post('gridsquare', true);
		$loggedState = strtoupper(trim((string)$this->input->post('loggedState', true)));
		$loggedCounty = trim((string)$this->input->post('loggedCounty', true));

		header('Content-Type: application/json');

		$this->loadCountyDatasets();

		$coords = $this->gridsquareToLatLng($gridsquare);
		if ($coords === null) {
			echo json_encode(['error' => __("Invalid gridsquare")]);
			return;
		}

		// Bounding box of the gridsquare's (sub)square, so the map can draw
		// its outline
		$grid_bounds = $this->gridBox($gridsquare, $coords['lat'], $coords['lng']);

		$derived = $this->findCountyFromLatlng($coords['lat'], $coords['lng']);

		$feature = null;
		if ($derived !== null) {
			$feature = $this->findCountyFeature($derived['state'], $derived['county']);
		}

		$logged_feature = null;
		if ($loggedState !== '' && $loggedCounty !== '') {
			$logged_feature = $this->findCountyFeature($loggedState, $loggedCounty);
		}

		echo json_encode([
			'lat' => $coords['lat'],
			'lng' => $coords['lng'],
			'grid_bounds' => $grid_bounds,
			'state' => $derived['state'] ?? null,
			'county' => $derived['county'] ?? null,
			'logged_state' => $loggedState,
			'logged_county' => $loggedCounty,
			'feature' => $feature,
			'logged_feature' => $logged_feature,
		]);
	}

}
