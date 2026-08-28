<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wabtool extends CI_Controller {

	// square name => ['ring' => [[lng, lat], ...], 'bbox' => [minLng, minLat, maxLng, maxLat]]
	private $wabIndex = null;
	private $globalBbox = null;
	private $gridCache = array(); // uppercased grid => resolveGrid() result

	// ADIF DXCC entity numbers valid for WAB: G England, GI Northern Ireland,
	// GJ Jersey, GM Scotland, GD Isle of Man, GU Guernsey, GW Wales.
	// The entity numbers also cover the regional call areas (M/2E, MM, MW, ...).
	private $wabDxccIds = array(223, 265, 122, 279, 114, 106, 294);

	// Adjacent square outlines in the source data do not share exact vertices,
	// leaving slivers a few metres wide between them. Points falling into a
	// sliver snap to the nearest ring within this distance.
	const SNAP_METERS = 50;
	const SNAP_BBOX_DEGREES = 0.001;

	function __construct() {
		parent::__construct();

		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$data['station_profile'] = $this->stations->all_of_user();

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/wabtool.js',
		];

		$data['page_title'] = __("WAB from Gridsquare");
		$this->load->view('interface_assets/header', $data);
		$this->load->view('wabtool/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	/*
	 * AJAX: list QSOs with a gridsquare (>= 6 chars) but no WAB square,
	 * resolved against the WAB square outlines in wab_geojson.js
	 */
	public function scan() {
		set_time_limit(3600);
		header('Content-Type: application/json');

		$station_id = $this->input->post('station_id', true);

		$this->load->model('wab');
		$query = $this->wab->get_wab_candidates(($station_id !== null && $station_id !== 'all') ? $station_id : null, $this->wabDxccIds);

		$rows = array();
		$summary = array('qsos_scanned' => 0, 'unique_grids' => 0, 'matched' => 0, 'unmatched' => 0, 'ambiguous' => 0);
		$seenGrids = array();

		foreach ($query->result() as $qso) {
			$summary['qsos_scanned']++;
			$grid = strtoupper(substr(trim($qso->col_gridsquare), 0, 8));
			$seenGrids[$grid] = true;

			$resolved = $this->resolveGrid($qso->col_gridsquare);

			$square = null;
			$ambiguous = false;
			$cornerSquares = array();
			if ($resolved !== null) {
				$square = $resolved['square'];
				$ambiguous = $resolved['ambiguous'];
				$cornerSquares = $resolved['corner_squares'];
			}

			if ($square === null) {
				$summary['unmatched']++;
			} else {
				$summary['matched']++;
				if ($ambiguous) {
					$summary['ambiguous']++;
				}
			}

			$rows[] = array(
				'id' => (int)$qso->col_primary_key,
				'callsign' => $qso->col_call,
				'datetime' => $qso->col_time_on,
				'band' => $qso->col_band,
				'grid' => $grid,
				'square' => $square,
				'ambiguous' => $ambiguous,
				'corner_squares' => array_values($cornerSquares),
				'station' => $qso->station_profile_name,
			);
		}

		$summary['unique_grids'] = count($seenGrids);

		echo json_encode(array('summary' => $summary, 'rows' => $rows));
	}

	/*
	 * AJAX: write the WAB square into the selected QSOs. Squares are always
	 * recomputed server side; ownership and the empty-SIG policy are re-checked.
	 */
	public function apply() {
		set_time_limit(3600);
		header('Content-Type: application/json');

		$ids = $this->input->post('ids');
		if (is_string($ids)) {
			$ids = json_decode($ids, true);
		}
		if (!is_array($ids) || count($ids) === 0) {
			echo json_encode(array('error' => __("No QSOs selected")));
			return;
		}
		$ids = array_values(array_filter(array_unique(array_map('intval', $ids))));
		if (count($ids) === 0) {
			echo json_encode(array('error' => __("No QSOs selected")));
			return;
		}

		$this->load->model('wab');
		$user_station_ids = array_filter(array_map('intval', explode(',', (string)$this->stations->all_station_ids_of_user())));
		if (count($user_station_ids) === 0) {
			echo json_encode(array('error' => __("No station locations found")));
			return;
		}

		$qsos = $this->wab->get_wab_candidates_by_ids($ids, $user_station_ids, $this->wabDxccIds);
		$skipped = count($ids) - count($qsos);

		$idsBySquare = array();
		foreach ($qsos as $qso) {
			$resolved = $this->resolveGrid($qso->col_gridsquare);
			if ($resolved === null || $resolved['square'] === null) {
				$skipped++;
				continue;
			}
			$idsBySquare[$resolved['square']][] = (int)$qso->col_primary_key;
		}

		$updated = 0;
		$squares = array();
		foreach ($idsBySquare as $square => $squareIds) {
			$updated += $this->wab->apply_wab_square($square, $squareIds, $user_station_ids);
			$squares[$square] = count($squareIds);
		}

		echo json_encode(array('updated' => $updated, 'skipped' => $skipped, 'squares' => $squares));
	}

	/*
	 * AJAX: map data for a gridsquare — the grid rectangle plus the outlines
	 * of the assigned WAB square and any extra squares the grid corners
	 * fall into
	 */
	public function map_data() {
		header('Content-Type: application/json');

		$result = $this->resolveGrid($this->input->post('grid', true));

		if ($result === null || $result['lat'] === null) {
			echo json_encode(array('error' => __("Invalid gridsquare")));
			return;
		}

		// extent of the grid: 6-char subsquare 5'x2.5', 8-char extended 0.5'x0.25'
		if (strlen($result['grid']) === 8) {
			$dLng = 0.5 / 60;
			$dLat = 0.25 / 60;
		} else {
			$dLng = 2.5 / 60;
			$dLat = 1.25 / 60;
		}

		// distinct squares to outline: the assigned one first, then any
		// additional squares among the corner results
		$names = array();
		if ($result['square'] !== null) {
			$names[] = $result['square'];
		}
		foreach ($result['corner_squares'] as $cornerSquare) {
			if (!in_array($cornerSquare, $names)) {
				$names[] = $cornerSquare;
			}
		}

		$this->loadWabIndex();

		$features = array();
		foreach ($names as $i => $name) {
			if (!isset($this->wabIndex[$name])) {
				continue;
			}
			$features[] = array(
				'type' => 'Feature',
				'properties' => array(
					'name' => $name,
					'role' => ($i === 0) ? 'assigned' : 'corner',
				),
				'geometry' => array('type' => 'Polygon', 'coordinates' => array($this->wabIndex[$name]['ring'])),
			);
		}

		echo json_encode(array(
			'grid' => $result['grid'],
			'lat' => $result['lat'],
			'lng' => $result['lng'],
			'square' => $result['square'],
			'grid_bounds' => array(
				'south' => $result['lat'] - $dLat,
				'north' => $result['lat'] + $dLat,
				'west' => $result['lng'] - $dLng,
				'east' => $result['lng'] + $dLng,
			),
			'features' => $features,
		));
	}

	// ============================================================================
	// WAB square geometry
	// ============================================================================

	/*
	 * Build a lookup index from the WAB square outlines in wab_geojson.js.
	 * Each square is stored with its (closed) ring and bounding box; the
	 * centroid Point features are skipped.
	 */
	private function loadWabIndex() {
		if ($this->wabIndex !== null) {
			return;
		}

		$this->load->library('geojson');
		$geojson = $this->geojson->loadGeoJsonFile('assets/js/sections/wab_geojson.js');

		$this->wabIndex = array();
		$this->globalBbox = null;

		if (!is_array($geojson) || !isset($geojson['features'])) {
			return;
		}

		foreach ($geojson['features'] as $feature) {
			if (($feature['geometry']['type'] ?? '') !== 'MultiLineString') {
				continue;
			}

			$name = $feature['properties']['name'] ?? null;
			$ring = $feature['geometry']['coordinates'][0] ?? null;
			if ($name === null || !is_array($ring)) {
				continue;
			}

			$bbox = array(INF, INF, -INF, -INF);
			foreach ($ring as $point) {
				$bbox[0] = min($bbox[0], $point[0]);
				$bbox[1] = min($bbox[1], $point[1]);
				$bbox[2] = max($bbox[2], $point[0]);
				$bbox[3] = max($bbox[3], $point[1]);
			}

			$this->wabIndex[$name] = array('ring' => $ring, 'bbox' => $bbox);

			if ($this->globalBbox === null) {
				$this->globalBbox = $bbox;
			} else {
				$this->globalBbox = array(
					min($this->globalBbox[0], $bbox[0]),
					min($this->globalBbox[1], $bbox[1]),
					max($this->globalBbox[2], $bbox[2]),
					max($this->globalBbox[3], $bbox[3]),
				);
			}
		}
	}

	/*
	 * Name of the WAB square containing the given point, or null. Points that
	 * fall into a sliver between adjacent rings snap to the nearest ring.
	 */
	private function squareForPoint($lat, $lng) {
		$this->loadWabIndex();

		if ($this->globalBbox !== null &&
			($lng < $this->globalBbox[0] - self::SNAP_BBOX_DEGREES || $lat < $this->globalBbox[1] - self::SNAP_BBOX_DEGREES ||
			$lng > $this->globalBbox[2] + self::SNAP_BBOX_DEGREES || $lat > $this->globalBbox[3] + self::SNAP_BBOX_DEGREES)) {
			return null;
		}

		$candidates = array();
		foreach ($this->wabIndex as $name => $square) {
			$bbox = $square['bbox'];
			if ($lng < $bbox[0] - self::SNAP_BBOX_DEGREES || $lat < $bbox[1] - self::SNAP_BBOX_DEGREES ||
				$lng > $bbox[2] + self::SNAP_BBOX_DEGREES || $lat > $bbox[3] + self::SNAP_BBOX_DEGREES) {
				continue;
			}
			if ($this->geojson->isPointInPolygon($lat, $lng, $square['ring'])) {
				return $name;
			}
			$candidates[$name] = $square['ring'];
		}

		// Inside no ring: the closest nearby ring wins if it is close enough
		$bestName = null;
		$bestDist = self::SNAP_METERS;
		foreach ($candidates as $name => $ring) {
			$dist = $this->distanceToRingMeters($lat, $lng, $ring);
			if ($dist < $bestDist) {
				$bestDist = $dist;
				$bestName = $name;
			}
		}

		return $bestName;
	}

	/*
	 * Approximate distance in metres from a point to a square's ring
	 * (point-to-segment minimum, local equirectangular projection)
	 */
	private function distanceToRingMeters($lat, $lng, $ring) {
		$mLat = 111132.0;
		$mLng = 111320.0 * cos(deg2rad($lat));

		$px = $lng * $mLng;
		$py = $lat * $mLat;

		$best = INF;
		$count = count($ring);
		for ($i = 0; $i < $count - 1; $i++) {
			$ax = $ring[$i][0] * $mLng;
			$ay = $ring[$i][1] * $mLat;
			$bx = $ring[$i + 1][0] * $mLng;
			$by = $ring[$i + 1][1] * $mLat;

			$dx = $bx - $ax;
			$dy = $by - $ay;
			$len2 = $dx * $dx + $dy * $dy;
			$t = $len2 > 0 ? max(0, min(1, (($px - $ax) * $dx + ($py - $ay) * $dy) / $len2)) : 0;

			$dist = sqrt(pow($px - ($ax + $t * $dx), 2) + pow($py - ($ay + $t * $dy), 2));
			if ($dist < $best) {
				$best = $dist;
			}
		}

		return $best;
	}

	/*
	 * Resolve a gridsquare to its WAB square. Returns null for invalid grids,
	 * otherwise an array with grid, lat, lng, square (may be null when the
	 * center lies outside WAB coverage), ambiguous and corner_squares.
	 * A 6-char subsquare spans exactly 5' lon x 2.5' lat, so grids whose
	 * corners fall into more than one square are flagged as ambiguous.
	 */
	private function resolveGrid($grid) {
		$grid = strtoupper(substr(trim((string)$grid), 0, 8));

		if (!preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}([0-9]{2})?$/', $grid)) {
			return null;
		}

		if (array_key_exists($grid, $this->gridCache)) {
			return $this->gridCache[$grid];
		}

		$this->load->library('geojson');

		$result = array(
			'grid' => $grid,
			'lat' => null,
			'lng' => null,
			'square' => null,
			'ambiguous' => false,
			'corner_squares' => array(),
		);

		$coords = $this->geojson->gridsquareToLatLng($grid);
		if ($coords === null) {
			$this->gridCache[$grid] = $result;
			return $result;
		}

		$result['lat'] = $coords['lat'];
		$result['lng'] = $coords['lng'];
		$result['square'] = $this->squareForPoint($coords['lat'], $coords['lng']);

		if ($result['square'] !== null && strlen($grid) === 6) {
			$dLat = 1.25 / 60;
			$dLng = 2.5 / 60;
			$cornerSquares = array();
			foreach (array(-1, 1) as $latSign) {
				foreach (array(-1, 1) as $lngSign) {
					$cornerSquare = $this->squareForPoint($coords['lat'] + $latSign * $dLat, $coords['lng'] + $lngSign * $dLng);
					if ($cornerSquare !== null) {
						$cornerSquares[$cornerSquare] = true;
					}
				}
			}

			$result['corner_squares'] = array_keys($cornerSquares);
			$result['ambiguous'] = count($result['corner_squares']) > 1;
		}

		$this->gridCache[$grid] = $result;
		return $result;
	}

}
