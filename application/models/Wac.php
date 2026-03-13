<?php

class Wac extends CI_Model{

	private $validContinents = ['AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'];

	function __construct() {
		if(!$this->load->is_loaded('Genfunctions')) {
			$this->load->library('Genfunctions');
		}
	}

	/*
	 * Gets all WAC data with confirmation status in efficient query using MAX aggregation
	 */
	function getWacData($location_list, $postdata) {
		$bindings = [];
		$sql = "SELECT thcv.col_cont, thcv.col_band,
			MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
			MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
			MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		FROM " . $this->config->item('table_name') . " thcv
		WHERE station_id IN (" . $location_list . ")
		AND thcv.col_cont IN ('AF', 'EU', 'AS', 'SA', 'NA', 'OC', 'AN')
		AND thcv.col_cont != ''";

		// Mode filter
		if ($postdata['mode'] != 'All') {
			$sql .= " AND (thcv.col_mode = ? OR thcv.col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		$sql .= " AND thcv.col_prop_mode != 'SAT'";

		$sql .= " GROUP BY thcv.col_cont, thcv.col_band";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	/*
	 * Gets all WAC satellite data with confirmation status
	 */
	function getWacDataSat($location_list, $postdata) {
		$bindings = [];
		$sql = "SELECT thcv.col_cont, 'SAT' as col_band,
			MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
			MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
			MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		FROM " . $this->config->item('table_name') . " thcv
		LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
		WHERE station_id IN (" . $location_list . ")
		AND thcv.col_cont IN ('AF', 'EU', 'AS', 'SA', 'NA', 'OC', 'AN')
		AND thcv.col_cont != ''";

		// Mode filter
		if ($postdata['mode'] != 'All') {
			$sql .= " AND (thcv.col_mode = ? OR thcv.col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		// Satellite filter
		if ($postdata['sat'] != 'All') {
			$sql .= " AND thcv.col_sat_name = ?";
			$bindings[] = $postdata['sat'];
		}

		// Orbit filter
		$sql .= $this->addOrbitToQuery($postdata, $bindings);

		$sql .= " AND thcv.col_prop_mode = 'SAT'";

		$sql .= " GROUP BY thcv.col_cont";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	function get_wac_array($bands, $postdata, $location_list) {
		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);

		// Initialize matrix with all continents
		foreach ($this->validContinents as $cont) {
			foreach ($bands as $band) {
				if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
					continue;
				}
				$wacMatrix[$cont][$band] = '-';
			}
		}

		// Initialize summary counters
		foreach ($bands as $band) {
			if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
				continue;
			}
			$summary['worked'][$band] = 0;
			$summary['confirmed'][$band] = 0;
		}
		$summary['worked']['Total'] = 0;
		$summary['confirmed']['Total'] = 0;

		// Track unique continent/band combinations
		$workedContinents = [];  // [band][continent] => true
		$confirmedContinents = []; // [band][continent] => true

		// Track worked status for each continent
		$continentWorkedStatus = []; // [continent] => count

		// Create a lookup array for valid bands
		$validBands = array_flip($bands);

		// Get all WAC data in efficient queries
		$wacData = $this->getWacData($location_list, $postdata);

		// Process regular band data
		foreach ($wacData as $wac) {
			// Skip if this band is not in our requested bands list
			if (!isset($validBands[$wac->col_band])) {
				continue;
			}

			// Track worked status for this continent
			if (!isset($continentWorkedStatus[$wac->col_cont])) {
				$continentWorkedStatus[$wac->col_cont] = 0;
			}
			$continentWorkedStatus[$wac->col_cont]++;

			// Check if confirmed based on the confirmation types selected in postdata
			$isConfirmed = false;
			$confirmationLetters = '';
			if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $wac->qsl > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'Q';
			}
			if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $wac->lotw > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'L';
			}
			if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $wac->eqsl > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'E';
			}
			if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $wac->qrz > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'Z';
			}
			if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $wac->clublog > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'C';
			}

			if ($isConfirmed) {
				$wacMatrix[$wac->col_cont][$wac->col_band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $wac->col_cont) . '","' . $wac->col_band . '","All", "All","'. $postdata['mode'] . '","WAC","'.$qsl.'","","")\'>'.$confirmationLetters.'</a></div>';
				// Track confirmed continents for summary
				if (!isset($confirmedContinents[$wac->col_band][$wac->col_cont])) {
					$confirmedContinents[$wac->col_band][$wac->col_cont] = true;
					$summary['confirmed'][$wac->col_band]++;
				}
			} else {
				$wacMatrix[$wac->col_cont][$wac->col_band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $wac->col_cont) . '","' . $wac->col_band . '","All", "All","'. $postdata['mode'] . '","WAC","","","")\'>W</a></div>';
			}

			// Track worked continents for summary
			if (!isset($workedContinents[$wac->col_band][$wac->col_cont])) {
				$workedContinents[$wac->col_band][$wac->col_cont] = true;
				$summary['worked'][$wac->col_band]++;
			}
		}

		// Process SAT data if needed
		if ($postdata['band'] == 'SAT') {
			if (in_array('SAT', $bands)) {
				$wacDataSat = $this->getWacDataSat($location_list, $postdata);

				foreach ($wacDataSat as $wac) {
					// Track worked status for this continent
					if (!isset($continentWorkedStatus[$wac->col_cont])) {
						$continentWorkedStatus[$wac->col_cont] = 0;
					}
					$continentWorkedStatus[$wac->col_cont]++;

					// Check if confirmed based on the confirmation types selected in postdata
					$isConfirmed = false;
					$confirmationLetters = '';
					if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $wac->qsl > 0) {
						$isConfirmed = true;
						$confirmationLetters .= 'Q';
					}
					if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $wac->lotw > 0) {
						$isConfirmed = true;
						$confirmationLetters .= 'L';
					}
					if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $wac->eqsl > 0) {
						$isConfirmed = true;
						$confirmationLetters .= 'E';
					}
					if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $wac->qrz > 0) {
						$isConfirmed = true;
						$confirmationLetters .= 'Z';
					}
					if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $wac->clublog > 0) {
						$isConfirmed = true;
						$confirmationLetters .= 'C';
					}

					if ($isConfirmed) {
						$wacMatrix[$wac->col_cont]['SAT'] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $wac->col_cont) . '","SAT","All", "All","'. $postdata['mode'] . '","WAC","'.$qsl.'","","")\'>'.$confirmationLetters.'</a></div>';
						// Track confirmed continents for summary
						if (!isset($confirmedContinents['SAT'][$wac->col_cont])) {
							$confirmedContinents['SAT'][$wac->col_cont] = true;
							$summary['confirmed']['SAT']++;
						}
					} else {
						if ($postdata['worked'] != NULL) {
							$wacMatrix[$wac->col_cont]['SAT'] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $wac->col_cont) . '","SAT","All", "All","'. $postdata['mode'] . '","WAC","","","")\'>W</a></div>';
						}
					}

					// Track worked continents for summary
					if (!isset($workedContinents['SAT'][$wac->col_cont])) {
						$workedContinents['SAT'][$wac->col_cont] = true;
						$summary['worked']['SAT']++;
					}
				}
			}
		}

		// Calculate totals across all bands (excluding SAT)
		$totalWorkedContinents = [];
		$totalConfirmedContinents = [];
		foreach ($workedContinents as $band => $continents) {
			foreach ($continents as $cont => $true) {
				if (!isset($totalWorkedContinents[$cont])) {
					$totalWorkedContinents[$cont] = true;
					if ($band === 'SAT') {
						continue;
					}
					$summary['worked']['Total']++;
				}
			}
		}
		foreach ($confirmedContinents as $band => $continents) {
			foreach ($continents as $cont => $true) {
				if (!isset($totalConfirmedContinents[$cont])) {
					$totalConfirmedContinents[$cont] = true;
					if ($band === 'SAT') {
						continue;
					}
					$summary['confirmed']['Total']++;
				}
			}
		}

		if (isset($wacMatrix)) {
			// Return both the matrix data and summary
			return ['matrix' => $wacMatrix, 'summary' => $summary];
		} else {
			return ['matrix' => [], 'summary' => $summary];
		}
	}

	/*
	 * Function gets worked and confirmed summary on each band on the active stationprofile
	 * This is now integrated into get_wac_array for efficiency
	 */
	function get_wac_summary($bands, $postdata, $location_list) {
		$result = $this->get_wac_array($bands, $postdata, $location_list);
		return $result['summary'];
	}

	// Adds orbit type to query
	function addOrbitToQuery($postdata,&$binding) {
		$sql = '';
		if ($postdata['orbit'] != 'All') {
			$sql .= ' AND satellite.orbit = ?';
			$binding[]=$postdata['orbit'];
		}
		return $sql;
	}

}
