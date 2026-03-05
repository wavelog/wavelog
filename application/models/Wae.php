<?php

class WAE extends CI_Model {

	// Make an array of the WAE countries

	// Reference: https://www.darc.de/der-club/referate/dx/diplome/wae-diplom/wae-laenderliste/
	// ADIF refrence: https://www.adif.org.uk/315/ADIF_315.htm#Region_Enumeration

	// 4U1V (OE for DXCC), JW/b, GM/s, IT, TA1,
	private $eucountries = '5,7,21,27,40,45,52,54,61,106,114,117,118,122,126,145,146,149,167,179,180,203,206,209,212,214,221,222,223,224,225,227,230,233,236,239,242,245,246,248,251,254,257,259,260,263,265,266,269,272,275,278,279,281,284,287,288,294,295,296,390,497,499,501,502,503,504,514,522';


	// Deleted
	// Prefix	Country	valid since	valid until
	// 9S4	Saarland	Nov 8, 1947	Mar 31, 1957
	// I1	Triest	 	Mar 31, 1957
	// UN	Karelo Finish Republic	 	Jun 30, 1960
	// DL	Germany	 	Sep 16, 1973
	// Y2	German Democratic Republic	Sep 17, 1973	Oct 2, 1990
	// UA1N	Karelia	Jul 1, 1960	Dec 31, 1991
	// OK	Czechoslovakia	 	Dec 31, 1992
	// R1MV	Maliy Vysotskij Isl.		Feb 17, 2012

	private $validWaeRegions = ['IV', 'SY', 'BI', 'SI', 'ET'];

	private $location_list;

	function __construct() {
		if(!$this->load->is_loaded('Genfunctions')) {
			$this->load->library('Genfunctions');
		}

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if ($logbooks_locations_array) {
			// Create the location_list string
			$this->location_list = "'" . implode("','", $logbooks_locations_array) . "'";
		} else {
			// Handle the case where $logbooks_locations_array is empty or not set
			$this->location_list = '';
		}
	}

	function get_wae_array($bands, $postdata) {
		if ($this->location_list == '') {
			return null;
		}

		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);
		$dxccArray = $this->fetchdxcc($postdata, $this->location_list);

		// WAE special regions
		$waeRegions = [
			'IV' => ['name' => 'ITU Vienna', 'prefix' => '4U1V'],
			'SY' => ['name' => 'Sicily', 'prefix' => 'IT9'],
			'BI' => ['name' => 'Bear Island', 'prefix' => 'JW/b'],
			'SI' => ['name' => 'Shetland Islands', 'prefix' => 'GM/s'],
			'ET' => ['name' => 'European Turkey', 'prefix' => 'TA1']
		];

		// Initialize matrix with all DXCC entities and WAE regions
		foreach ($dxccArray as $dxcc) {
			$adif = $dxcc->adif ?? '0';
			$name = $dxcc->name ?? '';
			$prefix = $dxcc->prefix ?? '';
			$enddate = $dxcc->Enddate ?? null;

			if ($adif == '0') {
				$dxccMatrix[$adif]['name'] = $name;
			} else {
				$dxccMatrix[$adif]['name'] = ucwords(strtolower($name), "- (/");
			}
			$dxccMatrix[$adif]['Dxccprefix'] = $prefix;
			if ($postdata['includedeleted']) {
				$dxccMatrix[$adif]['Deleted'] = isset($enddate) ? 1 : 0;
			}

			// Initialize all bands to dash
			foreach ($bands as $band) {
				if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
					continue;
				}
				$dxccMatrix[$adif][$band] = '-';
			}
		}

		// Initialize WAE regions
		foreach ($waeRegions as $region => $info) {
			$dxccMatrix[$region]['name'] = $info['name'];
			$dxccMatrix[$region]['Dxccprefix'] = $info['prefix'];
			foreach ($bands as $band) {
				if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
					continue;
				}
				$dxccMatrix[$region][$band] = '-';
			}
		}

		// Initialize summary counters only for the bands passed in
		foreach ($bands as $band) {
			$summary['worked'][$band] = 0;
			$summary['confirmed'][$band] = 0;
		}
		$summary['worked']['Total'] = 0;
		$summary['confirmed']['Total'] = 0;

		// Track unique entity/band combinations for totals
		$workedEntities = [];  // [band][entity] => true
		$confirmedEntities = []; // [band][entity] => true

		// Track worked status for each entity
		$entityWorkedStatus = []; // [entity] => count

		// Create a lookup array for valid bands
		$validBands = array_flip($bands);

		// Get all WAE data in efficient queries
		$waeData = $this->getWaeData($this->location_list, $postdata);
		$waeDataSat = $this->getWaeDataSat($this->location_list, $postdata);

		foreach ($waeData as $wae) {
			// Skip if this band is not in our requested bands list
			if (!isset($validBands[$wae->col_band])) {
				continue;
			}

			// Use region only if it's a valid WAE region, otherwise use DXCC
			$entityKey = (!empty($wae->col_region) && in_array($wae->col_region, $this->validWaeRegions)) ? $wae->col_region : (string)$wae->dxcc;

			// Track worked status for this entity
			if (!isset($entityWorkedStatus[$entityKey])) {
				$entityWorkedStatus[$entityKey] = 0;
			}
			$entityWorkedStatus[$entityKey]++;

			// Check if confirmed based on the confirmation types selected in postdata
			$isConfirmed = false;
			$confirmationLetters = '';
			if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $wae->qsl > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'Q';
			}
			if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $wae->lotw > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'L';
			}
			if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $wae->eqsl > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'E';
			}
			if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $wae->qrz > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'Z';
			}
			if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $wae->clublog > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'C';
			}

			if ($isConfirmed) {
				$dxccMatrix[$entityKey][$wae->col_band] = '<div class="bg-success awardsBgSuccess" additional_successinfo=">C<"><a href=\'javascript:displayContacts("'.$entityKey.'","'. $wae->col_band . '","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","'. $postdata['mode'] . '","WAE","'.$qsl.'","'.$postdata['dateFrom'].'","'.$postdata['dateTo'].'")\'>'.$confirmationLetters.'</a></div>';
				// Track confirmed entities for summary
				if (!isset($confirmedEntities[$wae->col_band][$entityKey])) {
					$confirmedEntities[$wae->col_band][$entityKey] = true;
					$summary['confirmed'][$wae->col_band]++;
				}
			} else {
				if ($postdata['worked'] != NULL) {
					$dxccMatrix[$entityKey][$wae->col_band] = '<div class="bg-danger awardsBgWarning" ><a href=\'javascript:displayContacts("'.$entityKey.'","'. $wae->col_band . '","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","'. $postdata['mode'] . '","WAE", "", "'.$postdata['dateFrom'].'", "'.$postdata['dateTo'].'")\'>W</a></div>';
				}
			}

			// Track worked entities for summary
			if (!isset($workedEntities[$wae->col_band][$entityKey])) {
				$workedEntities[$wae->col_band][$entityKey] = true;
				$summary['worked'][$wae->col_band]++;
			}
		}

		if ($postdata['band'] == 'SAT') {

			foreach ($waeDataSat as $wae) {
				// Skip if this band is not in our requested bands list
				if (!isset($validBands['SAT'])) {
					continue;
				}

				// Use region only if it's a valid WAE region, otherwise use DXCC
				$entityKey = (!empty($wae->col_region) && in_array($wae->col_region, $this->validWaeRegions)) ? $wae->col_region : (string)$wae->dxcc;

				// Track worked status for this entity
				if (!isset($entityWorkedStatus[$entityKey])) {
					$entityWorkedStatus[$entityKey] = 0;
				}
				$entityWorkedStatus[$entityKey]++;

				// Check if confirmed based on the confirmation types selected in postdata
				$isConfirmed = false;
				$confirmationLetters = '';
				if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $wae->qsl > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'Q';
				}
				if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $wae->lotw > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'L';
				}
				if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $wae->eqsl > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'E';
				}
				if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $wae->qrz > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'Z';
				}
				if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $wae->clublog > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'C';
				}

				if ($isConfirmed) {
					$dxccMatrix[$entityKey]['SAT'] = '<div class="bg-success awardsBgSuccess" additional_successinfo=">C<"><a href=\'javascript:displayContacts("'.$entityKey.'","SAT","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","'. $postdata['mode'] . '","WAE","'.$qsl.'","'.$postdata['dateFrom'].'","'.$postdata['dateTo'].'")\'>'.$confirmationLetters.'</a></div>';
					// Track confirmed entities for summary
					if (!isset($confirmedEntities['SAT'][$entityKey])) {
						$confirmedEntities['SAT'][$entityKey] = true;
						$summary['confirmed']['SAT']++;
					}
				} else {
					$dxccMatrix[$entityKey]['SAT'] = '<div class="bg-danger awardsBgWarning" ><a href=\'javascript:displayContacts("'.$entityKey.'","SAT","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","'. $postdata['mode'] . '","WAE", "", "'.$postdata['dateFrom'].'", "'.$postdata['dateTo'].'")\'>W</a></div>';
				}

				// Track worked entities for summary
				if (!isset($workedEntities['SAT'][$entityKey])) {
					$workedEntities['SAT'][$entityKey] = true;
					$summary['worked']['SAT']++;
				}
			}
		}

		// Calculate totals across all bands (excluding SAT)
		$totalWorkedEntities = [];
		$totalConfirmedEntities = [];
		foreach ($workedEntities as $band => $entities) {
			// Skip SAT for totals
			if ($band === 'SAT') {
				continue;
			}
			foreach ($entities as $entity => $true) {
				if (!isset($totalWorkedEntities[$entity])) {
					$totalWorkedEntities[$entity] = true;
					$summary['worked']['Total']++;
				}
			}
		}
		foreach ($confirmedEntities as $band => $entities) {
			// Skip SAT for totals
			if ($band === 'SAT') {
				continue;
			}
			foreach ($entities as $entity => $true) {
				if (!isset($totalConfirmedEntities[$entity])) {
					$totalConfirmedEntities[$entity] = true;
					$summary['confirmed']['Total']++;
				}
			}
		}

		// Remove entities based on postdata filters
		foreach ($dxccMatrix as $entity => $data) {
			// Remove not-worked entities if filter is disabled
			if ($postdata['notworked'] == NULL && !isset($entityWorkedStatus[$entity])) {
				unset($dxccMatrix[$entity]);
				continue;
			}

			// Remove worked-only entities if filter is disabled
			if ($postdata['worked'] == NULL && isset($entityWorkedStatus[$entity]) && !isset($totalConfirmedEntities[$entity])) {
				unset($dxccMatrix[$entity]);
				continue;
			}

			// Remove confirmed entities if filter is disabled
			if ($postdata['confirmed'] == NULL && isset($totalConfirmedEntities[$entity])) {
				unset($dxccMatrix[$entity]);
				continue;
			}
		}

		if (isset($dxccMatrix) && !empty($dxccMatrix)) {
			// Convert associative array to indexed array for sorting
			$dxccIndexed = array_values($dxccMatrix);

			// Sort the indexed array by the 'Dxccprefix' key
			usort($dxccIndexed, function ($a, $b) {
				$aPrefix = $a['Dxccprefix'] ?? '';
				$bPrefix = $b['Dxccprefix'] ?? '';
				return strcmp($aPrefix, $bPrefix);
			});

			// Return both the matrix data and summary
			return ['matrix' => $dxccIndexed, 'summary' => $summary];
		} else {
			return ['matrix' => [], 'summary' => $summary ?? []];
		}
	}

	/*
	 * Gets all WAE data with confirmation status in efficient query using MAX aggregation
	 */
	function getWaeData($location_list, $postdata) {
		$bindings = [];
		$sql = "SELECT
					COALESCE(thcv.col_region, CAST(thcv.col_dxcc AS CHAR)) as entity_key,
					thcv.col_dxcc as dxcc,
					thcv.col_region,
					thcv.col_band,
					MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
					MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
					MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
					MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
					MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
				FROM " . $this->config->item('table_name') . " thcv
				JOIN dxcc_entities d ON thcv.col_dxcc = d.adif
				WHERE station_id IN (" . $location_list . ")";

		// Filter for European DXCC entities and WAE regions
		$sql .= " AND thcv.col_dxcc IN (" . $this->eucountries . ") AND d.end IS NULL";

		// Mode filter
		if ($postdata['mode'] != 'All') {
			$sql .= " AND (thcv.col_mode = ? OR thcv.col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		// Date filters
		if ($postdata['dateFrom'] != NULL) {
			$sql .= " AND thcv.col_time_on >= ?";
			$bindings[] = $postdata['dateFrom'] . ' 00:00:00';
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " AND thcv.col_time_on <= ?";
			$bindings[] = $postdata['dateTo'] . ' 23:59:59';
		}

		$sql .= " AND thcv.col_prop_mode != 'SAT'";

		// Orbit filter
		$sql .= $this->addOrbitToQuery($postdata, $bindings);

		$sql .= " GROUP BY COALESCE(thcv.col_region, CAST(thcv.col_dxcc AS CHAR)), thcv.col_dxcc, thcv.col_region, thcv.col_band";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	function getWaeDataSat($location_list, $postdata) {
		$bindings = [];
		$sql = "SELECT
					COALESCE(thcv.col_region, CAST(thcv.col_dxcc AS CHAR)) as entity_key,
					thcv.col_dxcc as dxcc,
					thcv.col_region,
					'SAT' as col_band,
					MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
					MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
					MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
					MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
					MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
				FROM " . $this->config->item('table_name') . " thcv
				JOIN dxcc_entities d ON thcv.col_dxcc = d.adif
				LEFT JOIN satellite ON thcv.COL_SAT_NAME = satellite.name
				WHERE station_id IN (" . $location_list . ")";

		// Filter for European DXCC entities and WAE regions
		$sql .= " AND thcv.col_dxcc IN (" . $this->eucountries . ") AND d.end IS NULL";

		// Mode filter
		if ($postdata['mode'] != 'All') {
			$sql .= " AND (thcv.col_mode = ? OR thcv.col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		// Date filters
		if ($postdata['dateFrom'] != NULL) {
			$sql .= " AND thcv.col_time_on >= ?";
			$bindings[] = $postdata['dateFrom'] . ' 00:00:00';
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " AND thcv.col_time_on <= ?";
			$bindings[] = $postdata['dateTo'] . ' 23:59:59';
		}

		// Satellite filter
		if ($postdata['sat'] != 'All') {
			$sql .= " AND thcv.col_sat_name = ?";
			$bindings[] = $postdata['sat'];
		}

		// Orbit filter
		$sql .= $this->addOrbitToQuery($postdata, $bindings);

		$sql .= " AND thcv.col_prop_mode = 'SAT'";

		$sql .= " GROUP BY COALESCE(thcv.col_region, CAST(thcv.col_dxcc AS CHAR)), thcv.col_dxcc, thcv.col_region";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	// Adds orbit type to query
	function addOrbitToQuery($postdata,&$binding) {
		$sql = '';
		if ($postdata['orbit'] != 'All') {
			$sql .= ' AND satellite.orbit = ?';
			$binding[] = $postdata['orbit'];
		}

		return $sql;
	}

	function fetchDxcc($postdata, $location_list) {
		$bindings = [];

		$sql = "select adif, prefix, name, date(end) Enddate, date(start) Startdate, lat, `long`
			from dxcc_entities";

		if ($postdata['notworked'] == NULL) {
			$sql .= " join (select col_dxcc, col_region from " . $this->config->item('table_name') . " thcv
			LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
						where station_id in (" . $location_list . ")";
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";

			if ($postdata['band'] != 'All') {
				if ($postdata['band'] == 'SAT') {
					$sql .= " and col_prop_mode = ?";
					$bindings[] = $postdata['band'];
					if ($postdata['sat'] != 'All') {
						$sql .= " and col_sat_name = ?";
						$bindings[] = $postdata['sat'];
					}
				} else {
					$sql .= " and col_prop_mode !='SAT'";
					$sql .= " and col_band = ?";
					$bindings[] = $postdata['band'];
				}
			}

			if ($postdata['mode'] != 'All') {
				$sql .= " and (col_mode = ? or col_submode = ?)";
				$bindings[] = $postdata['mode'];
				$bindings[] = $postdata['mode'];
			}

			$sql .= $this->addOrbitToQuery($postdata, $bindings);

			$sql .= ' group by col_dxcc, col_region) x on dxcc_entities.adif = x.col_dxcc';
		}

		$sql .= " where 1 = 1";

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and end is null";
		// }

		$sql .= ' and dxcc_entities.adif in (' . $this->eucountries . ')';

		$sql .= ' order by prefix';
		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

}
?>
