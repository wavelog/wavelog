<?php

class ITU extends CI_Model{

	function __construct() {
		$this->load->library('Genfunctions');
	}

	function get_itu_array($bands, $postdata, $location_list, $map = false) {
		$ituZ = array(); // Used for keeping track of which states that are not worked

		for ($i = 1; $i <= 90; $i++) {
			$ituZ[$i]['count'] = 0;                   // Inits each itu zone's count
		}

		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);

		// Initialize all bands to dash
		foreach ($bands as $band) {
			if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
				continue;
			}
			for ($i = 1; $i <= 90; $i++) {
				$bandItu[$i][$band] = '-';                  // Sets all to dash to indicate no result
			}
		}

		// Initialize summary counters only for the bands passed in
		foreach ($bands as $band) {
			if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
				continue;
			}
			$summary['worked'][$band] = 0;
			$summary['confirmed'][$band] = 0;
		}
		$summary['worked']['Total'] = 0;
		$summary['confirmed']['Total'] = 0;

		// Track unique zone/band combinations for totals
		$workedZones = [];  // [band][zone] = true
		$confirmedZones = []; // [band][zone] = true

		// Create a lookup array for valid bands
		$validBands = array_flip($bands); // ['160m' => true, '80m' => true, etc]

		$itudata = $this->getItuZoneData($location_list, $postdata);
		$itudata_sat = $this->getItuZoneDataSat($location_list, $postdata);

		foreach ($itudata as $itu) {
			// Skip if this band is not in our requested bands list
			if (!isset($validBands[$itu->col_band])) {
				continue;
			}

			$ituZ[$itu->col_ituz]['count']++; // Count each itu zone

			// Check if confirmed based on the confirmation types selected in postdata
			$isConfirmed = false;
			$confirmationLetters = '';
			if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $itu->qsl == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'Q';
			}
			if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $itu->lotw == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'L';
			}
			if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $itu->eqsl == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'E';
			}
			if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $itu->qrz == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'Z';
			}
			if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $itu->clublog == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'C';
			}

			if ($isConfirmed) {
				$bandItu[$itu->col_ituz][$itu->col_band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $itu->col_ituz) . '","' . $itu->col_band . '","All", "All","'. $postdata['mode'] . '","ITUZone","'.$qsl.'")\'>' . $confirmationLetters . '</a></div>';
				// Track confirmed zones for summary
				if (!isset($confirmedZones[$itu->col_band][$itu->col_ituz])) {
					$confirmedZones[$itu->col_band][$itu->col_ituz] = true;
					$summary['confirmed'][$itu->col_band]++;
				}
			} else {
				if ($postdata['worked'] != NULL) {
					$bandItu[$itu->col_ituz][$itu->col_band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $itu->col_ituz) . '","' . $itu->col_band . '","All", "All","'. $postdata['mode'] . '","ITUZone","")\'>W</a></div>';
				}
			}

			// Track worked zones for summary
			if (!isset($workedZones[$itu->col_band][$itu->col_ituz])) {
				$workedZones[$itu->col_band][$itu->col_ituz] = true;
				$summary['worked'][$itu->col_band]++;
			}
		}

		if ($postdata['band'] == 'SAT') {
			foreach ($itudata_sat as $itu) {
				if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
					continue;
				}
				// Skip if this band is not in our requested bands list
				if (!isset($validBands[$itu->col_band])) {
					continue;
				}

				$ituZ[$itu->col_ituz]['count']++; // Count each itu zone

				// Check if confirmed based on the confirmation types selected in postdata
				$isConfirmed = false;
				$confirmationLetters = '';
				if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $itu->qsl == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'Q';
				}
				if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $itu->lotw == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'L';
				}
				if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $itu->eqsl == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'E';
				}
				if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $itu->qrz == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'Z';
				}
				if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $itu->clublog == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'C';
				}

				if ($isConfirmed) {
					$bandItu[$itu->col_ituz][$itu->col_band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $itu->col_ituz) . '","' . $itu->col_band . '","All", "All","'. $postdata['mode'] . '","ITUZone","'.$qsl.'")\'>' . $confirmationLetters . '</a></div>';
					// Track confirmed zones for summary
					if (!isset($confirmedZones[$itu->col_band][$itu->col_ituz])) {
						$confirmedZones[$itu->col_band][$itu->col_ituz] = true;
						$summary['confirmed'][$itu->col_band]++;
					}
				} else {
					if ($postdata['worked'] != NULL) {
						$bandItu[$itu->col_ituz][$itu->col_band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $itu->col_ituz) . '","' . $itu->col_band . '","All", "All","'. $postdata['mode'] . '","ITUZone","")\'>W</a></div>';
					}
				}

				// Track worked zones for summary
				if (!isset($workedZones[$itu->col_band][$itu->col_ituz])) {
					$workedZones[$itu->col_band][$itu->col_ituz] = true;
					$summary['worked'][$itu->col_band]++;
				}
			}
		}

		// Calculate totals across all bands (excluding SAT)
		$totalWorkedZones = [];
		$totalConfirmedZones = [];
		foreach ($workedZones as $band => $zones) {
			foreach ($zones as $zone => $true) {
				if (!isset($totalWorkedZones[$zone])) {
					$totalWorkedZones[$zone] = true;
					if ($band === 'SAT') {
						continue;
					}
					$totalWorkedZonesExSat[$zone] = true; // For calculating total worked excluding SAT
					$summary['worked']['Total']++;
				}
			}
		}
		foreach ($confirmedZones as $band => $zones) {
			foreach ($zones as $zone => $true) {
				if (!isset($totalConfirmedZones[$zone])) {
					$totalConfirmedZones[$zone] = true;
					if ($band === 'SAT') {
						continue;
					}
					$totalConfirmedZonesExSat[$zone] = true; // For calculating total worked excluding SAT
					$summary['confirmed']['Total']++;
				}
			}
		}

		// Remove zones based on postdata filters
		for ($i = 1; $i <= 90; $i++) {
			// Remove not-worked zones if filter is disabled
			if ($postdata['notworked'] == NULL && $ituZ[$i]['count'] == 0) {
				unset($bandItu[$i]);
				continue;
			}

			// Remove worked-only zones if filter is disabled
			if ($postdata['worked'] == NULL && $ituZ[$i]['count'] > 0 && !isset($totalConfirmedZones[$i])) {
				unset($bandItu[$i]);
				continue;
			}

			// Remove confirmed zones if filter is disabled
			if ($postdata['confirmed'] == NULL && isset($totalConfirmedZones[$i])) {
				unset($bandItu[$i]);
				continue;
			}
		}

		// If this is for the map, return simplified format
		if ($map) {
			$mapZones = [];
			if ($bands[0] == 'SAT') {
				for ($i = 1; $i <= 90; $i++) {
					if ($ituZ[$i]['count'] == 0) {
						$mapZones[$i-1] = '-';  // Not worked
					} elseif (isset($confirmedZones['SAT'][$i])) {
						$mapZones[$i-1] = 'C';  // Confirmed
					} else {
						$mapZones[$i-1] = 'W';  // Worked but not confirmed
					}
				}
			} else {
				for ($i = 1; $i <= 90; $i++) {
					if (isset($totalConfirmedZonesExSat[$i])) {
						$mapZones[$i-1] = 'C';  // Confirmed
					} else if (isset($totalWorkedZonesExSat[$i])) {
						$mapZones[$i-1] = 'W';  // Worked but not confirmed
					} else {
						$mapZones[$i-1] = '-';  // Not worked
					}
				}
			}
			return $mapZones;
		}

		if (isset($bandItu)) {
			// Return both the band data and summary
			return ['bands' => $bandItu, 'summary' => $summary];
		} else {
			return 0;
		}
	}

	function getItuZoneData($location_list, $postdata) {
		$bindings=[];
		$sql = "SELECT thcv.col_ituz, thcv.col_band,
			MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
			MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
			MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ") and col_ituz <= 90 and col_ituz <> ''";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		if ($postdata['datefrom'] != NULL) {
			$sql .= " and col_time_on >= ?";
			$bindings[]=$postdata['datefrom'] . ' 00:00:00';
		}

		if ($postdata['dateto'] != NULL) {
			$sql .= " and col_time_on <= ?";
			$bindings[]=$postdata['dateto'] . ' 23:59:59';
		}

		$sql .= " and col_prop_mode != 'SAT'";

		$sql .= " GROUP BY thcv.col_ituz, thcv.col_band";

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getItuZoneDataSat($location_list, $postdata) {
		$bindings=[];
		$sql = "SELECT thcv.col_ituz, 'SAT' as col_band,
			MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
			MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
			MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ") and col_ituz <= 90 and col_ituz <> ''";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		if ($postdata['datefrom'] != NULL) {
			$sql .= " and col_time_on >= ?";
			$bindings[]=$postdata['datefrom'] . ' 00:00:00';
		}

		if ($postdata['dateto'] != NULL) {
			$sql .= " and col_time_on <= ?";
			$bindings[]=$postdata['dateto'] . ' 23:59:59';
		}

		$sql .= " and col_prop_mode = 'SAT'";

		$sql .= " GROUP BY thcv.col_ituz";

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

}
