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
			$this->geojsonData = $this->loadGeoJsonFile($geojsonFile);
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

        if ($this->geojsonFile === null) {
			$this->geojsonFile = "assets/json/geojson/states_{$dxcc}.geojson";
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
     * @param float $lat Latitude of the point
     * @param float $lng Longitude of the point
     * @param array $geojsonData Decoded GeoJSON FeatureCollection
     * @return array|null Feature properties if found, null otherwise
     */
    public function findFeatureContainingPoint($lat, $lng, $geojsonData) {
        if (!isset($geojsonData['features']) || !is_array($geojsonData['features'])) {
            return null;
        }

        foreach ($geojsonData['features'] as $feature) {
            if (!isset($feature['geometry']['coordinates']) || !isset($feature['geometry']['type'])) {
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
}
