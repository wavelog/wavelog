<?php

class CQ extends CI_Model{

	function __construct() {
		$this->load->library('Genfunctions');
	}

	function get_cq_array($bands, $postdata, $location_list, $map = false) {
		$cqZ = array(); // Used for keeping track of which states that are not worked

		for ($i = 1; $i <= 40; $i++) {
			$cqZ[$i]['count'] = 0;                   // Inits each cq zone's count
		}

		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);

		// Initialize all bands to dash
		foreach ($bands as $band) {
			if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
				continue;
			}
			for ($i = 1; $i <= 40; $i++) {
				$bandCq[$i][$band] = '-';                  // Sets all to dash to indicate no result
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

		$cqdata = $this->getCqZoneData($location_list, $postdata);
		$cqdata_sat = $this->getCqZoneDataSat($location_list, $postdata);

		foreach ($cqdata as $cq) {
			// Skip if this band is not in our requested bands list
			if (!isset($validBands[$cq->col_band])) {
				continue;
			}

			$cqZ[$cq->col_cqz]['count']++; // Count each cq zone

			// Check if confirmed based on the confirmation types selected in postdata
			$isConfirmed = false;
			$confirmationLetters = '';
			if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $cq->qsl == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'Q';
			}
			if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $cq->lotw == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'L';
			}
			if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $cq->eqsl == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'E';
			}
			if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $cq->qrz == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'Z';
			}
			if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $cq->clublog == 1) {
				$isConfirmed = true;
				$confirmationLetters .= 'C';
			}

			if ($isConfirmed) {
				$bandCq[$cq->col_cqz][$cq->col_band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $cq->col_cqz) . '","' . $cq->col_band . '","All", "All","'. $postdata['mode'] . '","CQZone","'.$qsl.'")\'>' . $confirmationLetters . '</a></div>';
				// Track confirmed zones for summary
				if (!isset($confirmedZones[$cq->col_band][$cq->col_cqz])) {
					$confirmedZones[$cq->col_band][$cq->col_cqz] = true;
					$summary['confirmed'][$cq->col_band]++;
				}
			} else {
				if ($postdata['worked'] != NULL) {
					$bandCq[$cq->col_cqz][$cq->col_band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $cq->col_cqz) . '","' . $cq->col_band . '","All", "All","'. $postdata['mode'] . '","CQZone","")\'>W</a></div>';
				}
			}

			// Track worked zones for summary
			if (!isset($workedZones[$cq->col_band][$cq->col_cqz])) {
				$workedZones[$cq->col_band][$cq->col_cqz] = true;
				$summary['worked'][$cq->col_band]++;
			}
		}

		if ($postdata['band'] == 'SAT') {
			foreach ($cqdata_sat as $cq) {
				if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
					continue;
				}
				// Skip if this band is not in our requested bands list
				if (!isset($validBands[$cq->col_band])) {
					continue;
				}

				$cqZ[$cq->col_cqz]['count']++; // Count each cq zone

				// Check if confirmed based on the confirmation types selected in postdata
				$isConfirmed = false;
				$confirmationLetters = '';
				if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $cq->qsl == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'Q';
				}
				if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $cq->lotw == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'L';
				}
				if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $cq->eqsl == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'E';
				}
				if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $cq->qrz == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'Z';
				}
				if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $cq->clublog == 1) {
					$isConfirmed = true;
					$confirmationLetters .= 'C';
				}

				if ($isConfirmed) {
					$bandCq[$cq->col_cqz][$cq->col_band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $cq->col_cqz) . '","' . $cq->col_band . '","All", "All","'. $postdata['mode'] . '","CQZone","'.$qsl.'")\'>' . $confirmationLetters . '</a></div>';
					// Track confirmed zones for summary
					if (!isset($confirmedZones[$cq->col_band][$cq->col_cqz])) {
						$confirmedZones[$cq->col_band][$cq->col_cqz] = true;
						$summary['confirmed'][$cq->col_band]++;
					}
				} else {
					if ($postdata['worked'] != NULL) {
						$bandCq[$cq->col_cqz][$cq->col_band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $cq->col_cqz) . '","' . $cq->col_band . '","All", "All","'. $postdata['mode'] . '","CQZone","")\'>W</a></div>';
					}
				}

				// Track worked zones for summary
				if (!isset($workedZones[$cq->col_band][$cq->col_cqz])) {
					$workedZones[$cq->col_band][$cq->col_cqz] = true;
					$summary['worked'][$cq->col_band]++;
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
		// Determine which band's zones to use for filtering
		$filterBand = (count($bands) == 1) ? $bands[0] : null;

		for ($i = 1; $i <= 40; $i++) {
			// For single band view, check band-specific status; for all bands, check totals
			$isWorked = $filterBand
				? isset($workedZones[$filterBand][$i])
				: isset($totalWorkedZones[$i]);
			$isConfirmed = $filterBand
				? isset($confirmedZones[$filterBand][$i])
				: isset($totalConfirmedZones[$i]);

			// Remove not-worked zones if filter is disabled
			if ($postdata['notworked'] == NULL && !$isWorked) {
				unset($bandCq[$i]);
				continue;
			}

			// Remove worked-only zones if filter is disabled
			if ($postdata['worked'] == NULL && $isWorked && !$isConfirmed) {
				unset($bandCq[$i]);
				continue;
			}

			// Remove confirmed zones if filter is disabled
			if ($postdata['confirmed'] == NULL && $isConfirmed) {
				unset($bandCq[$i]);
				continue;
			}
		}

		// If this is for the map, return simplified format
		if ($map) {
			$mapZones = [];
			if ($bands[0] == 'SAT') {
				for ($i = 1; $i <= 40; $i++) {
					if ($cqZ[$i]['count'] == 0) {
						$mapZones[$i-1] = '-';  // Not worked
					} elseif (isset($confirmedZones['SAT'][$i])) {
						$mapZones[$i-1] = 'C';  // Confirmed
					} else {
						$mapZones[$i-1] = 'W';  // Worked but not confirmed
					}
				}
			} else {
				for ($i = 1; $i <= 40; $i++) {
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

		if (isset($bandCq)) {
			// Return both the band data and summary
			return ['bands' => $bandCq, 'summary' => $summary];
		} else {
			return 0;
		}
	}

	function getCqZoneData($location_list, $postdata) {
		$bindings=[];
		$sql = "SELECT thcv.col_cqz, thcv.col_band,
			MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
			MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
			MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ") and col_cqz <= 40 and col_cqz <> ''";

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

		$sql .= " GROUP BY thcv.col_cqz, thcv.col_band";

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getCqZoneDataSat($location_list, $postdata) {
		$bindings=[];
		$sql = "SELECT thcv.col_cqz, 'SAT' as col_band,
			MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
			MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
			MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ") and col_cqz <= 40 and col_cqz <> ''";

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

		$sql .= " GROUP BY thcv.col_cqz";

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

}
