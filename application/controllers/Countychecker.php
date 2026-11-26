<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Countychecker extends CI_Controller {
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

	/**
	 * Constructor; requires a logged-in user
	 */
	function __construct() {
		parent::__construct();
		$this->load->model('countychecker_model');
		$this->load->library('Geojson');
		$this->load->helper('callsign');

		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
	}

	/**
	 * Renders the checker's start page with the station location selector
	 */
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

	/**
	 * Runs the county check over the QSOs from the model and renders the
	 * result table. QSOs whose gridsquare resolves to no county polygon or
	 * to more than one (uncertain) are left out; QSOs without a logged
	 * county are listed as backfill candidates, the others as potential
	 * wrong counties.
	 */
	function doCountyCheck() {
		set_time_limit(3600);
		// Decoding the three county geojson files peaks at ~256 MB
		ini_set('memory_limit', '512M');

		$de = $this->input->post('de', true);

		$result = array();
		$this->gridsquareCache = array(); // Reset cache

		$callarray = $this->countychecker_model->getQsos($de, self::$countyFiles)->result();
		if (!empty($callarray)) {
			$this->loadCountyDatasets();
		}

		foreach ($callarray as $qso) {
			$gridsquare = $qso->col_gridsquare;

			// Check cache first - avoid redundant gridsquare->county conversions
			if (!isset($this->gridsquareCache[$gridsquare])) {
				$this->gridsquareCache[$gridsquare] = $this->deriveCounty($gridsquare);
			}
			$derived = $this->gridsquareCache[$gridsquare];

			// COL_CNTY is "STATE,COUNTY" (ADIF); fall back to COL_STATE when
			// the county was logged without the state prefix.
			$parts = explode(',', (string)$qso->col_cnty, 2);
			$logged_county = trim($parts[count($parts) - 1]);
			$logged_state = strtoupper(trim(count($parts) == 2 ? $parts[0] : (string)$qso->col_state));

			// Gridsquare invalid or resolved to water/nowhere with a county polygon
			if ($derived['county'] === null) {
				continue;
			}
			$county = $derived['county'];

			// The grid box overlaps more than one county: the derived county
			// is ambiguous, so the QSO is skipped entirely - it is neither
			// reported as a mismatch nor offered as a backfill candidate
			if ($derived['boxcount'] > 1) {
				continue;
			}

			// No county logged (a backfill candidate), or one that differs from the
			// county derived from the gridsquare (a mismatch)
			if ($logged_county === ''
				|| !$this->countyMatches($logged_state, $logged_county, $county['state'], $county['county'])) {
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
					'lotw_lastupload' => $qso->lastupload,
					'qsl_rcvd' => $qso->col_qsl_rcvd,
					'qslrdate' => $qso->col_qslrdate,
					'lotw_rcvd' => $qso->col_lotw_qsl_rcvd,
					'lotwrdate' => $qso->col_lotw_qslrdate,
				];
			}
		}

		$data['result'] = $result;

		$this->loadView($data);
	}

	/**
	 * Lowercased letters only, with "St", "Ste", "Saint" and "Sainte" all
	 * treated as the same word, so "St. Clair", "Saint Clair", "Ste.
	 * Genevieve" and "Sainte Genevieve" compare equal - as do "La Salle"
	 * and "LaSalle".
	 *
	 * @param string $county County name
	 * @return string Normalized county name
	 */
	private static function normCounty($county) {
		$s = strtolower((string)$county);
		$s = preg_replace('/\bsainte?\b|\bste\b/', 'st', $s);
		return preg_replace('/[^a-z0-9]/', '', $s);
	}

	/**
	 * True when the logged state/county can be considered the same county as
	 * the one derived from the geojson polygons.
	 *
	 * @param string $logged_state   State as logged ('' when unknown)
	 * @param string $logged_county County as logged
	 * @param string $derived_state  State derived from the geojson polygons
	 * @param string $derived_county County derived from the geojson polygons
	 * @return bool
	 */
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

	/**
	 * Loads the county geojson files and builds their spatial indexes,
	 * caching one dataset per DXCC
	 */
	private function loadCountyDatasets() {
		foreach (self::$countyFiles as $dxcc) {
			if (!isset($this->datasets[$dxcc])) {
				$data = $this->geojson->loadGeoJsonFile("assets/json/geojson/counties_{$dxcc}.geojson");
				if ($data === null) {
					$this->datasets[$dxcc] = array('data' => null, 'index' => null);
					continue;
				}
				$this->datasets[$dxcc] = array(
					'data' => $data,
					'index' => $this->geojson->buildSpatialIndex($data),
				);
			}
		}
	}

	/**
	 * Find the county containing a point across all three county datasets
	 *
	 * @param float $lat Latitude of the point
	 * @param float $lng Longitude of the point
	 * @return array|null Feature properties ({state, county}) or null
	 */
	private function findCountyFromLatlng($lat, $lng) {
		foreach (self::$countyFiles as $dxcc) {
			$props = $this->geojson->findFeatureContainingPoint($lat, $lng, $this->datasets[$dxcc]['data'], $this->datasets[$dxcc]['index']);
			if ($props !== null) {
				return $props;
			}
		}
		return null;
	}

	/**
	 * The county the four corners of a grid box land in, when every corner
	 * that is inside a county polygon agrees on the same one. Used when the
	 * box center is not inside any county (e.g. coastal water). Returns null
	 * when no corner lands in a county or the corners disagree.
	 *
	 * @param array $box Grid box from gridBox(): [latMin, lngMin, latMax, lngMax]
	 * @return array|null Feature properties ({state, county}) or null
	 */
	private function findCountyAtCorners($box) {
		list($lat_min, $lng_min, $lat_max, $lng_max) = $box;

		$county = null;
		foreach (array(
			array($lat_min, $lng_min), array($lat_min, $lng_max),
			array($lat_max, $lng_min), array($lat_max, $lng_max),
		) as $corner) {
			$props = $this->findCountyFromLatlng($corner[0], $corner[1]);
			if ($props === null) {
				continue;
			}
			if ($county === null) {
				$county = $props;
			} elseif (strtoupper($county['state']) !== strtoupper($props['state'])
				|| self::normCounty($county['county']) !== self::normCounty($props['county'])) {
				return null; // corners land in different counties: ambiguous
			}
		}

		return $county;
	}

	/**
	 * Derives the county a gridsquare lies in: the polygon containing the
	 * square's center or, failing that (e.g. coastal water), the one all four
	 * corners of the square agree on
	 *
	 * @param string $gridsquare Maidenhead locator
	 * @return array coords and box of the square (null for an invalid locator),
	 *               county: the feature properties ({state, county}) or null,
	 *               boxcount: how many counties the square overlaps
	 */
	private function deriveCounty($gridsquare) {
		$coords = $this->geojson->gridsquareToLatLng($gridsquare);
		$box = $coords === null ? null : $this->gridBox($gridsquare, $coords['lat'], $coords['lng']);
		$county = $coords === null ? null : $this->findCountyFromLatlng($coords['lat'], $coords['lng']);

		// The center point is not inside any county polygon (e.g. coastal
		// water): fall back to the corners of the grid box instead
		if ($county === null && $box !== null) {
			$county = $this->findCountyAtCorners($box);
		}

		// How many counties the grid box overlaps: more than one means the
		// grid straddles a county line and the derived county is uncertain
		$boxcount = $box === null ? 1 : max(1, count($this->findCountiesIntersectingBox($box)));

		return array('coords' => $coords, 'box' => $box, 'county' => $county, 'boxcount' => $boxcount);
	}

	/**
	 * Bounding box [latMin, lngMin, latMax, lngMax] of the (sub)square the
	 * locator resolves to - Qra returns the square's exact center, so the box
	 * is simply center +/- half the square's extent at the locator's
	 * precision.
	 *
	 * @param string $gridsquare Maidenhead locator
	 * @param float $lat Latitude of the square's center
	 * @param float $lng Longitude of the square's center
	 * @return array|null [latMin, lngMin, latMax, lngMax], or null for a malformed locator
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
	 * All county features the box overlaps with. Counties do not overlap each
	 * other, so a single result means the whole grid box is inside one county
	 * and the center-derived county is certain.
	 *
	 * @param array $box Grid box from gridBox(): [latMin, lngMin, latMax, lngMax]
	 * @return array The overlapping county features
	 */
	private function findCountiesIntersectingBox($box) {
		list($lat_min, $lng_min, $lat_max, $lng_max) = $box;
		$hits = array();

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
				$bbox = $dataset['index']['bbox'][$i];
				if ($lng_min > $bbox[2] || $lng_max < $bbox[0] || $lat_min > $bbox[3] || $lat_max < $bbox[1]) {
					continue; // bounding boxes don't even touch
				}
				if ($this->boxIntersectsFeature($lat_min, $lng_min, $lat_max, $lng_max, $feature)) {
					$hits[] = $feature;
				}
			}
		}

		return $hits;
	}

	/**
	 * True when the box intersects any outer ring of the feature
	 *
	 * @param float $lat_min Southern edge of the box
	 * @param float $lng_min Western edge of the box
	 * @param float $lat_max Northern edge of the box
	 * @param float $lng_max Eastern edge of the box
	 * @param array $feature GeoJSON feature
	 * @return bool
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
	 *
	 * @param float $lat_min Southern edge of the box
	 * @param float $lng_min Western edge of the box
	 * @param float $lat_max Northern edge of the box
	 * @param float $lng_max Eastern edge of the box
	 * @param array $ring Polygon outer ring: [[lng, lat], ...]
	 * @return bool
	 */
	private function boxIntersectsRing($lat_min, $lng_min, $lat_max, $lng_max, $ring) {
		$n = count($ring);

		if ($this->geojson->isPointInPolygon($lat_min, $lng_min, $ring)) return true;
		if ($this->geojson->isPointInPolygon($lat_max, $lng_max, $ring)) return true;
		if ($this->geojson->isPointInPolygon($lat_min, $lng_max, $ring)) return true;
		if ($this->geojson->isPointInPolygon($lat_max, $lng_min, $ring)) return true;

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
	 *
	 * @param array $p1 Segment endpoint [lng, lat]
	 * @param array $p2 Segment endpoint [lng, lat]
	 * @param float $lat_min Southern edge of the box
	 * @param float $lng_min Western edge of the box
	 * @param float $lat_max Northern edge of the box
	 * @param float $lng_max Eastern edge of the box
	 * @return bool
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

	/**
	 * Renders the result table of a finished county check
	 *
	 * @param array $data View data built by doCountyCheck()
	 */
	function loadView($data) {
		$this->load->view('countychecker/result', $data);
	}

	/**
	 * Updates COL_CNTY of the posted QSOs with the state/county pairs from
	 * the result table's geojson column. Only QSOs belonging to the current
	 * user are touched.
	 *
	 * @return void Outputs JSON: {updated: n} or {error: message}
	 */
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

			$updated += $this->countychecker_model->updateCounty($id, $state . ',' . $county);
		}

		echo json_encode(['updated' => $updated]);
	}

	/**
	 * Returns the full geojson feature for a state/county (alias-aware), so
	 * the map dialog can draw the logged county's outline.
	 *
	 * @param string $state Two-letter state code
	 * @param string $county County name
	 * @return array|null GeoJSON feature or null when no polygon matches
	 */
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

	/**
	 * Returns lat/lng of a gridsquare plus the county features for the map
	 * dialog: the one derived from the gridsquare and the logged one.
	 *
	 * @return void Outputs JSON with the gridsquare's coordinates, the
	 *               derived and logged county features and the other
	 *               counties the grid box overlaps
	 */
	function mapData() {
		// Decoding the three county geojson files peaks at ~256 MB
		ini_set('memory_limit', '512M');

		$gridsquare = $this->input->post('gridsquare', true);
		$loggedState = strtoupper(trim((string)$this->input->post('loggedState', true)));
		$loggedCounty = trim((string)$this->input->post('loggedCounty', true));

		header('Content-Type: application/json');

		$this->loadCountyDatasets();

		// Same derivation as doCountyCheck, so the map shows the county the
		// result table derived
		$located = $this->deriveCounty($gridsquare);
		if ($located['coords'] === null) {
			echo json_encode(['error' => __("Invalid gridsquare")]);
			return;
		}
		$coords = $located['coords'];

		// Bounding box of the gridsquare's (sub)square, so the map can draw
		// its outline
		$grid_bounds = $located['box'];

		$derived = $located['county'];

		$feature = null;
		if ($derived !== null) {
			$feature = $this->findCountyFeature($derived['state'], $derived['county']);
		}

		// Every county the grid box overlaps, minus the derived one
		$candidates = array();
		if ($grid_bounds !== null) {
			foreach ($this->findCountiesIntersectingBox($grid_bounds) as $f) {
				if ($derived !== null
					&& strtoupper($f['properties']['state']) === strtoupper($derived['state'])
					&& self::normCounty($f['properties']['county']) === self::normCounty($derived['county'])) {
					continue;
				}
				$candidates[] = array(
					'state' => $f['properties']['state'],
					'county' => $f['properties']['county'],
					'feature' => $f,
				);
			}
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
			'candidates' => $candidates,
		]);
	}

}
