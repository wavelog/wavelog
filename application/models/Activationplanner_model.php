<?php

class Activationplanner_model extends CI_Model {

	/*
	 * WWFF/POTA/SOTA references within $max_km of [$lat, $lng], each tagged with
	 * its type and great-circle distance (km). Sorted ascending by distance.
	 */
	function refs_nearby($lat, $lng, $max_km = 20.0) {
		$sources = array(
			'wwff_directory' => 'WWFF',
			'pota_directory' => 'POTA',
			'sota_directory' => 'SOTA',
		);
		$out = array();
		foreach ($sources as $table => $type) {
			foreach ($this->refs_nearby_in($table, $type, $lat, $lng, $max_km) as $row) {
				$out[] = $row;
			}
		}
		usort($out, function ($a, $b) { return $a['dist'] <=> $b['dist']; });
		return $out;
	}

	/*
	 * Bounding-box prefilter on lat/lon, then exact haversine distance in PHP.
	 * $table is a hard-coded key from refs_nearby() above, never user input.
	 */
	private function refs_nearby_in($table, $type, $lat, $lng, $max_km) {
		$lat_deg = $max_km / 110.0;                                  // ~km per degree of latitude
		$lon_deg = $lat_deg / max(0.2, cos(deg2rad(abs($lat))));     // wider near the poles
		// POTA uses an active flag; WWFF/SOTA use a valid_till date
		// (NULL = no expiry, today == valid_till still counts as active).
		if ($table === 'pota_directory') {
			$active = ' AND active = 1';
		} elseif ($table === 'wwff_directory' || $table === 'sota_directory') {
			$active = " AND (valid_till IS NULL OR valid_till >= CURDATE())";
		} else {
			$active = '';
		}
		$sql = "SELECT reference, name, lat, lon FROM `" . $table . "`
			WHERE lat IS NOT NULL AND lon IS NOT NULL" . $active . "
			  AND lat BETWEEN ? AND ? AND lon BETWEEN ? AND ?";
		$query = $this->db->query($sql, array($lat - $lat_deg, $lat + $lat_deg, $lng - $lon_deg, $lng + $lon_deg));

		$result = array();
		foreach ($query->result() as $r) {
			$d = $this->haversine_km($lat, $lng, (float) $r->lat, (float) $r->lon);
			if ($d <= $max_km) {
				$result[] = array(
					'type' => $type,
					'ref'  => $r->reference,
					'name' => $r->name,
					'dist' => round($d, 1),
				);
			}
		}
		return $result;
	}

	/* Great-circle distance in km. */
	private function haversine_km($lat1, $lon1, $lat2, $lon2) {
		$R = 6371.0;
		$dLat = deg2rad($lat2 - $lat1);
		$dLon = deg2rad($lon2 - $lon1);
		$a = sin($dLat / 2) * sin($dLat / 2) +
			cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
		return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
	}
}
