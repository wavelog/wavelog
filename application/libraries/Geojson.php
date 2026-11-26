<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Geojson Library
 *
 * This library provides GeoJSON-based geographic operations for Wavelog,
 * used for determining states, provinces, and other administrative subdivisions
 * from gridsquare locators using point-in-polygon detection.
 *
 * Main functionality:
 * - Convert Maidenhead gridsquares to lat/lng coordinates
 * - Determine state/province from coordinates using GeoJSON boundary data
 * - Point-in-polygon detection for Polygon and MultiPolygon geometries
 *
 */
class Geojson {

    /**
     * DXCC entities that support state/province subdivision lookups
     *
     * Key: DXCC number
     * Value: Array with 'name' and 'enabled' flag
     */
    const SUPPORTED_STATES = [
        1 => ['name' => 'Canada', 'enabled' => true],              // 13 provinces/territories
        6 => ['name' => 'Alaska', 'enabled' => true],              // 1 state
        27 => ['name' => 'Belarus', 'enabled' => true],            // 7 subdivisions
        29 => ['name' => 'Canary Islands', 'enabled' => true],     // 2 provinces
        32 => ['name' => 'Ceuta & Melilla', 'enabled' => true],    // 2 autonomous cities
        50 => ['name' => 'Mexico', 'enabled' => true],             // 32 states
        100 => ['name' => 'Argentina', 'enabled' => true],         // 24 subdivisions
        108 => ['name' => 'Brazil', 'enabled' => true],            // 27 subdivisions
        110 => ['name' => 'Hawaii', 'enabled' => true],            // 1 state
        112 => ['name' => 'Chile', 'enabled' => true],             // 16 regions
        137 => ['name' => 'Republic of Korea', 'enabled' => true], // 17 subdivisions
        144 => ['name' => 'Uruguay', 'enabled' => true],           // 19 subdivisions
        148 => ['name' => 'Venezuela', 'enabled' => true],         // 24 states
        149 => ['name' => 'Azores', 'enabled' => true],            // 1 autonomous region
        150 => ['name' => 'Australia', 'enabled' => true],         // 8 subdivisions
        163 => ['name' => 'Papua New Guinea', 'enabled' => true],  // 22 provinces
        170 => ['name' => 'New Zealand', 'enabled' => true],       // 16 regions
        209 => ['name' => 'Belgium', 'enabled' => true],           // 11 subdivisions
        212 => ['name' => 'Bulgaria', 'enabled' => true],          // 28 subdivisions
        214 => ['name' => 'Corsica', 'enabled' => true],           // 2 departments (2A, 2B)
        225 => ['name' => 'Sardinia', 'enabled' => true],          // 5 provinces
        227 => ['name' => 'France', 'enabled' => true],            // 96 departments
        230 => ['name' => 'Germany', 'enabled' => true],           // 16 federal states
        239 => ['name' => 'Hungary', 'enabled' => true],           // 20 subdivisions
        245 => ['name' => 'Ireland', 'enabled' => true],           // 27 subdivisions
        248 => ['name' => 'Italy', 'enabled' => true],             // 107 provinces
        256 => ['name' => 'Madeira Islands', 'enabled' => true],   // 1 autonomous region
        263 => ['name' => 'Netherlands', 'enabled' => true],       // 12 provinces
        266 => ['name' => 'Norway', 'enabled' => true],            // 15 counties
        269 => ['name' => 'Poland', 'enabled' => true],            // 16 voivodeships
        272 => ['name' => 'Portugal', 'enabled' => true],          // 18 districts
        275 => ['name' => 'Romania', 'enabled' => true],           // 42 counties
        281 => ['name' => 'Spain', 'enabled' => true],             // 47 provinces
        284 => ['name' => 'Sweden', 'enabled' => true],            // 21 subdivisions
        287 => ['name' => 'Switzerland', 'enabled' => true],       // 26 cantons
        291 => ['name' => 'USA', 'enabled' => true],               // 52 states/territories
        318 => ['name' => 'China', 'enabled' => true],             // 31 provinces
        324 => ['name' => 'India', 'enabled' => true],             // 36 states/territories
        339 => ['name' => 'Japan', 'enabled' => true],             // 47 prefectures
        386 => ['name' => 'Taiwan', 'enabled' => true],            // 22 subdivisions
        497 => ['name' => 'Croatia', 'enabled' => true],           // 21 subdivisions
    ];

    private $qra;
	private $geojsonFile = null;
	private $geojsonData = null;

    public function __construct($dxcc = null) {
        $CI =& get_instance();
        $CI->load->library('qra');
        $this->qra = $CI->qra;
		if ($dxcc !== null) {
			$this->geojsonFile = "assets/json/geojson/states_{$dxcc}.geojson";
			$this->geojsonData = $this->loadGeoJsonFile($this->geojsonFile);
		}
    }

    // ============================================================================
    // PUBLIC API METHODS - Main entry points for state lookup
    // ============================================================================

    /**
     * Find state from grid square locator
     *
     * This is the main method used by the application to determine state/province
     * from a Maidenhead gridsquare.
     *
     * @param string $gridsquare Maidenhead grid square (e.g., "FM18lw")
     * @param int $dxcc DXCC entity number (e.g., 291 for USA)
     * @return array|null State properties (including 'code' and 'name') or null if not found
     */
    public function findStateFromGridsquare($gridsquare, $dxcc) {
        $coords = $this->gridsquareToLatLng($gridsquare);

        if ($coords === null) {
            return null;
        }

        return $this->findStateByDxcc($coords['lat'], $coords['lng'], $dxcc);
    }

    /**
     * Find state by DXCC entity number and coordinates
     *
     * This method loads the appropriate GeoJSON file for the DXCC entity
     * and searches for the state/province containing the given coordinates.
     *
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @param int $dxcc DXCC entity number (e.g., 291 for USA)
     * @return array|null State properties or null if not found
     */
    public function findStateByDxcc($lat, $lng, $dxcc) {
        // Check if state lookup is supported for this DXCC
        if (!$this->isStateSupported($dxcc)) {
            return null;
        }

        // Reload the boundary file when a different DXCC is requested. The
        // library is a singleton per request (load->library() won't re-run the
        // constructor), so without this a multi-DXCC pass would keep using the
        // first DXCC's polygons for every subsequent lookup.
        $expectedFile = "assets/json/geojson/states_{$dxcc}.geojson";
        if ($this->geojsonFile !== $expectedFile) {
			$this->geojsonFile = $expectedFile;
			$this->geojsonData = $this->loadGeoJsonFile($this->geojsonFile);
		}

        if ($this->geojsonData === null) {
            return null;
        }

        return $this->findFeatureContainingPoint($lat, $lng, $this->geojsonData);
    }

    /**
     * Check if state lookup is supported for given DXCC entity
     *
     * @param int $dxcc DXCC entity number
     * @return bool True if state lookup is supported and enabled
     */
    public function isStateSupported($dxcc) {
        return isset(self::SUPPORTED_STATES[$dxcc]) && self::SUPPORTED_STATES[$dxcc]['enabled'] === true;
    }

	/**
     * Retrieve list of DXCC entities that support state/province lookups
     *
     * @return array List of supported DXCC entities
     */
	public function getSupportedDxccs() {
		return self::SUPPORTED_STATES;
	}

    // ============================================================================
    // COORDINATE CONVERSION
    // ============================================================================

    /**
     * Convert Maidenhead grid square to latitude/longitude
     *
     * Uses the Qra library for gridsquare conversion.
     * Supports 2, 4, 6, 8, and 10 character gridsquares.
     * Also supports grid lines and grid corners (comma-separated).
     *
     * @param string $gridsquare Maidenhead grid square (e.g., "JO70va")
     * @return array|null Array with 'lat' and 'lng' or null on error
     */
    public function gridsquareToLatLng($gridsquare) {
        if (!is_string($gridsquare) || strlen($gridsquare) < 2) {
            return null;
        }

        $result = $this->qra->qra2latlong($gridsquare);

        if ($result === false || !is_array($result) || count($result) < 2) {
            return null;
        }

        // Qra library returns [lat, lng], we need to return associative array
        return [
            'lat' => $result[0],
            'lng' => $result[1]
        ];
    }

    // ============================================================================
    // GEOJSON FILE OPERATIONS
    // ============================================================================

    /**
     * Load and parse a GeoJSON file
     *
     * @param string $filepath Path to GeoJSON file (relative to FCPATH)
     * @return array|null Decoded GeoJSON data or null on error
     */
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

    // ============================================================================
    // GEOMETRIC ALGORITHMS - Point-in-polygon detection
    // ============================================================================

    /**
     * Check if a point (latitude, longitude) is inside a polygon
     * Uses ray casting algorithm
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

        // Ray casting algorithm
        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            $xi = $polygon[$i][0]; // longitude
            $yi = $polygon[$i][1]; // latitude
            $xj = $polygon[$j][0]; // longitude
            $yj = $polygon[$j][1]; // latitude

            $intersect = (($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Find which feature in a GeoJSON FeatureCollection contains a given point
     *
     * With a spatial index (see buildSpatialIndex()) only the features of the
     * point's 10x10 degree cell are checked, after a bounding box pre-check;
     * without one every feature is checked.
     *
     * @param float $lat Latitude of the point
     * @param float $lng Longitude of the point
     * @param array $geojsonData Decoded GeoJSON FeatureCollection
     * @param array|null $index Spatial index from buildSpatialIndex(), or null to check every feature
     * @return array|null Feature properties if found, null otherwise
     */
    public function findFeatureContainingPoint($lat, $lng, $geojsonData, $index = null) {
        if (!isset($geojsonData['features']) || !is_array($geojsonData['features'])) {
            return null;
        }

        $features = $geojsonData['features'];

        if ($index === null) {
            $candidates = array_keys($features);
        } else {
            // Early exit: check global bounds
            if ($lng < $index['minLng'] || $lng > $index['maxLng'] ||
                $lat < $index['minLat'] || $lat > $index['maxLat']) {
                return null;
            }

            // The point's 10x10 degree cell lists every feature that can contain it
            $candidates = $index['grid'][floor($lng / 10) * 10 . ',' . floor($lat / 10) * 10] ?? array();
        }

        // Check only candidate features
        foreach ($candidates as $i) {
            $feature = $features[$i];
            if (!isset($feature['geometry']['coordinates']) || !isset($feature['geometry']['type'])) {
                continue;
            }

            // Fast bounding box check
            if ($index !== null) {
                $bbox = $index['bbox'][$i];
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

    // ============================================================================
    // SPATIAL INDEX - fast point lookups in large FeatureCollections
    // ============================================================================

    /**
     * Calculate a bounding box for every feature of a decoded FeatureCollection
     * and build a coarse 10x10 degree grid index over them. This dramatically
     * speeds up point-in-polygon lookups.
     *
     * @param array $geojsonData Decoded FeatureCollection
     * @return array Spatial index: global bounds, a 10x10 grid of feature
     *               indexes and the bounding box of every feature
     */
    public function buildSpatialIndex($geojsonData) {
        $index = array(
            'minLng' => 180,
            'maxLng' => -180,
            'minLat' => 90,
            'maxLat' => -90,
            'grid' => array(),
            'bbox' => array()
        );

        if (!isset($geojsonData['features']) || !is_array($geojsonData['features'])) {
            return $index;
        }

        foreach ($geojsonData['features'] as $i => $feature) {
            $bbox = $this->calculateFeatureBoundingBox($feature);

            if ($bbox === null) {
                continue;
            }

            $index['bbox'][$i] = $bbox;

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
     *
     * @param array $feature GeoJSON feature
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
     *
     * @param array $coords Coordinate ring: [[lng, lat], ...]
     * @param float $minLng Updated with the smallest longitude
     * @param float $minLat Updated with the smallest latitude
     * @param float $maxLng Updated with the largest longitude
     * @param float $maxLat Updated with the largest latitude
     */
    private function updateBoundsFromCoords($coords, &$minLng, &$minLat, &$maxLng, &$maxLat) {
        foreach ($coords as $point) {
            $minLng = min($minLng, $point[0]);
            $maxLng = max($maxLng, $point[0]);
            $minLat = min($minLat, $point[1]);
            $maxLat = max($maxLat, $point[1]);
        }
    }
}
