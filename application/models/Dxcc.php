<?php

class DXCC extends CI_Model {

	function __construct() {
		if(!$this->load->is_loaded('Genfunctions')) {
			$this->load->library('Genfunctions');
		}
	}

	/*
	 * Fetches a list of all dxcc's, both current and deleted
	 */
	function list() {
		$this->db->order_by('name', 'ASC');
		return $this->db->get('dxcc_entities');
	}

	/*
	 * Fetches a list of all current dxcc's (non-deleted)
	 */
	function list_current($orderer = 'name') {
		$this->db->where('end', null);
		$this->db->where('adif !=', 0);

		if ($orderer == 'name') {
			$this->db->order_by('name', 'ASC');
		} elseif ($orderer == 'prefix') {
			$this->db->order_by('prefix', 'ASC');
		}
		return $this->db->get('dxcc_entities');
	}

	function get_dxcc_array($dxccArray, $bands, $postdata, $location_list, $map = false) {
		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);

		// Initialize matrix with all DXCC entities
		foreach ($dxccArray as $dxcc) {
			$adif = $dxcc->adif ?? '0';
			$name = $dxcc->name ?? '';
			$prefix = $dxcc->prefix ?? '';
			$enddate = $dxcc->end ?? null;

			if ($adif == '0') {
				$dxccMatrix[$adif]['name'] = $name;
			} else {
				$dxccMatrix[$adif]['name'] = ucwords(strtolower($name), "- (/");
			}
			$dxccMatrix[$adif]['prefix'] = $prefix;
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

		// Track unique DXCC/band combinations for totals
		$workedDxccs = [];  // [band][dxcc] => true
		$confirmedDxccs = []; // [band][dxcc] => true

		// Track worked status for each DXCC
		$dxccWorkedStatus = []; // [dxcc] => count

		// Create a lookup array for valid bands
		$validBands = array_flip($bands);

		// Get all DXCC data in efficient queries
		$dxccData = $this->getDxccData($location_list, $postdata);

		$dxccDataSat = $this->getDxccDataSat($location_list, $postdata);

		foreach ($dxccData as $dxcc) {
			// Skip if this band is not in our requested bands list
			if (!isset($validBands[$dxcc->col_band])) {
				continue;
			}

			// Track worked status for this DXCC
			if (!isset($dxccWorkedStatus[$dxcc->dxcc])) {
				$dxccWorkedStatus[$dxcc->dxcc] = 0;
			}
			$dxccWorkedStatus[$dxcc->dxcc]++;

			// Check if confirmed based on the confirmation types selected in postdata
			$isConfirmed = false;
			$confirmationLetters = '';
			if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $dxcc->qsl > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'Q';
			}
			if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $dxcc->lotw > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'L';
			}
			if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $dxcc->eqsl > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'E';
			}
			if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $dxcc->qrz > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'Z';
			}
			if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $dxcc->clublog > 0) {
				$isConfirmed = true;
				$confirmationLetters .= 'C';
			}

			if ($isConfirmed) {
				$dxccMatrix[$dxcc->dxcc][$dxcc->col_band] = '<div class="bg-success awardsBgSuccess" additional_successinfo=">C<"><a href=\'javascript:displayContacts("'.$dxcc->dxcc.'","'. $dxcc->col_band . '","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","' . $postdata['mode'] . '","DXCC2","'.$qsl.'","'.$postdata['dateFrom'].'","'.$postdata['dateTo'].'")\'>'.$confirmationLetters.'</a></div>';
				// Track confirmed DXCCs for summary
				if (!isset($confirmedDxccs[$dxcc->col_band][$dxcc->dxcc])) {
					$confirmedDxccs[$dxcc->col_band][$dxcc->dxcc] = true;
					$summary['confirmed'][$dxcc->col_band]++;
				}
			} else {
				if ($postdata['worked'] != NULL) {
					$dxccMatrix[$dxcc->dxcc][$dxcc->col_band] = '<div class="bg-danger awardsBgWarning" ><a href=\'javascript:displayContacts("'.$dxcc->dxcc.'","'. $dxcc->col_band . '","'. $postdata['sat'] . '","' . $postdata['orbit'] . '","'. $postdata['mode'] . '","DXCC2", "", "'.$postdata['dateFrom'].'", "'.$postdata['dateTo'].'")\'>W</a></div>';
				}
			}

			// Track worked DXCCs for summary
			if (!isset($workedDxccs[$dxcc->col_band][$dxcc->dxcc])) {
				$workedDxccs[$dxcc->col_band][$dxcc->dxcc] = true;
				$summary['worked'][$dxcc->col_band]++;
			}
		}

		if ($postdata['band'] == 'SAT') {
			foreach ($dxccDataSat as $dxcc) {
				if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
					continue;
				}
				// Skip if this band is not in our requested bands list
				if (!isset($validBands[$dxcc->col_band])) {
					continue;
				}

				// Ensure string key for consistency
				$dxccKey = (string)$dxcc->dxcc;

				// Track worked status for this DXCC
				if (!isset($dxccWorkedStatus[$dxccKey])) {
					$dxccWorkedStatus[$dxccKey] = 0;
				}
				$dxccWorkedStatus[$dxccKey]++;

				// Check if confirmed based on the confirmation types selected in postdata
				$isConfirmed = false;
				$confirmationLetters = '';
				if (isset($postdata['qsl']) && $postdata['qsl'] == 1 && $dxcc->qsl > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'Q';
				}
				if (isset($postdata['lotw']) && $postdata['lotw'] == 1 && $dxcc->lotw > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'L';
				}
				if (isset($postdata['eqsl']) && $postdata['eqsl'] == 1 && $dxcc->eqsl > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'E';
				}
				if (isset($postdata['qrz']) && $postdata['qrz'] == 1 && $dxcc->qrz > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'Z';
				}
				if (isset($postdata['clublog']) && $postdata['clublog'] == 1 && $dxcc->clublog > 0) {
					$isConfirmed = true;
					$confirmationLetters .= 'C';
				}

				if ($isConfirmed) {
					$dxccMatrix[$dxccKey][$dxcc->col_band] = '<div class="bg-success awardsBgSuccess" additional_successinfo=">C<"><a href=\'javascript:displayContacts("'.$dxcc->dxcc.'","'. $dxcc->col_band . '","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","' . $postdata['mode'] . '","DXCC2","'.$qsl.'","'.$postdata['dateFrom'].'","'.$postdata['dateTo'].'")\'>'.$confirmationLetters.'</a></div>';
					// Track confirmed DXCCs for summary
					if (!isset($confirmedDxccs[$dxcc->col_band][$dxccKey])) {
						$confirmedDxccs[$dxcc->col_band][$dxccKey] = true;
						$summary['confirmed'][$dxcc->col_band]++;
					}
				} else {
					if ($postdata['worked'] != NULL) {
						$dxccMatrix[$dxccKey][$dxcc->col_band] = '<div class="bg-danger awardsBgWarning" ><a href=\'javascript:displayContacts("'.$dxcc->dxcc.'","'. $dxcc->col_band . '","'. $postdata['sat'] . '","' . $postdata['orbit'] . '","'. $postdata['mode'] . '","DXCC2", "", "'.$postdata['dateFrom'].'", "'.$postdata['dateTo'].'")\'>W</a></div>';
					}
				}

				// Track worked DXCCs for summary
				if (!isset($workedDxccs[$dxcc->col_band][$dxccKey])) {
					$workedDxccs[$dxcc->col_band][$dxccKey] = true;
					$summary['worked'][$dxcc->col_band]++;
				}
			}
		}

		// Calculate totals across all bands (excluding SAT)
		$totalWorkedDxccs = [];
		$totalConfirmedDxccs = [];
		foreach ($workedDxccs as $band => $dxccs) {
			foreach ($dxccs as $dxcc => $true) {
				if (!isset($totalWorkedDxccs[$dxcc])) {
					$totalWorkedDxccs[$dxcc] = true;
					if ($band === 'SAT') {
						continue;
					}
					$totalWorkedDxccsExSat[$dxcc] = true;
					$summary['worked']['Total']++;
				}
			}
		}
		foreach ($confirmedDxccs as $band => $dxccs) {
			foreach ($dxccs as $dxcc => $true) {
				if (!isset($totalConfirmedDxccs[$dxcc])) {
					$totalConfirmedDxccs[$dxcc] = true;
					if ($band === 'SAT') {
						continue;
					}
					$totalConfirmedDxccsExSat[$dxcc] = true; // For calculating total worked excluding SAT
					$summary['confirmed']['Total']++;
				}
			}
		}

		// Remove DXCCs based on postdata filters
		foreach ($dxccMatrix as $dxcc => $data) {
			// Remove not-worked DXCCs if filter is disabled
			if ($postdata['notworked'] == NULL && !isset($dxccWorkedStatus[$dxcc])) {
				unset($dxccMatrix[$dxcc]);
				continue;
			}

			// Remove worked-only DXCCs if filter is disabled
			if ($postdata['worked'] == NULL && isset($dxccWorkedStatus[$dxcc]) && !isset($totalConfirmedDxccs[$dxcc])) {
				unset($dxccMatrix[$dxcc]);
				continue;
			}

			// Remove confirmed DXCCs if filter is disabled
			if ($postdata['confirmed'] == NULL && isset($totalConfirmedDxccs[$dxcc])) {
				unset($dxccMatrix[$dxcc]);
				continue;
			}
		}

		// If this is for the map, return simplified format
		if ($map) {
			$mapDxccs = [];
			if ($bands[0] == 'SAT') {
				foreach ($dxccMatrix as $dxcc => $data) {
					if (isset($confirmedDxccs['SAT'][$dxcc])) {
						$mapDxccs[$dxcc] = 'C';  // Confirmed
					} elseif (isset($workedDxccs['SAT'][$dxcc])) {
						$mapDxccs[$dxcc] = 'W';  // Worked but not confirmed
					} else {
						$mapDxccs[$dxcc] = '-';  // Not worked
					}
				}
			} else {
				foreach ($dxccMatrix as $dxcc => $data) {
					if (isset($totalConfirmedDxccsExSat[$dxcc])) {
						$mapDxccs[$dxcc] = 'C';  // Confirmed
					} elseif (isset($totalWorkedDxccsExSat[$dxcc])) {
						$mapDxccs[$dxcc] = 'W';  // Worked but not confirmed
					} else {
						$mapDxccs[$dxcc] = '-';  // Not worked
					}
				}
			}
			return $mapDxccs;
		}

		if (isset($dxccMatrix)) {
			// Return both the matrix data and summary
			return ['matrix' => $dxccMatrix, 'summary' => $summary];
		} else {
			return 0;
		}
	}

	/*
	 * Gets all DXCC data with confirmation status in efficient query using MAX aggregation
	 */
	function getDxccData($location_list, $postdata) {
		$bindings = [];
		$sql = "SELECT thcv.col_dxcc as dxcc, thcv.col_band,
			MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
			MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
			MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		FROM " . $this->config->item('table_name') . " thcv
		join dxcc_entities on thcv.col_dxcc = dxcc_entities.adif
		WHERE station_id IN (" . $location_list . ") AND thcv.col_dxcc > 0";

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

		$sql .= " and thcv.col_prop_mode != 'SAT'";

		// Continent filters
		$sql .= $this->addContinentsToQuery($postdata);

		// Deleted DXCC filter
		if ($postdata['includedeleted'] == NULL) {
			$sql .= " AND (SELECT end FROM dxcc_entities d WHERE d.adif = thcv.col_dxcc) IS NULL";
		}

		$sql .= " GROUP BY thcv.col_dxcc, thcv.col_band";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	function getDxccDataSat($location_list, $postdata) {
		$bindings = [];
		$sql = "SELECT thcv.col_dxcc as dxcc, 'SAT' as col_band,
			MAX(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,
			MAX(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,
			MAX(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		FROM " . $this->config->item('table_name') . " thcv
		join dxcc_entities on thcv.col_dxcc = dxcc_entities.adif
		LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
		WHERE station_id IN (" . $location_list . ") AND thcv.col_dxcc > 0";

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

		// Continent filters
		$sql .= $this->addContinentsToQuery($postdata);

		// Deleted DXCC filter
		if ($postdata['includedeleted'] == NULL) {
			$sql .= " AND (SELECT end FROM dxcc_entities d WHERE d.adif = thcv.col_dxcc) IS NULL";
		}

		$sql .= " and col_prop_mode = 'SAT'";

		$sql .= " GROUP BY thcv.col_dxcc";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	function fetchDxcc($postdata, $location_list) {
		$bindings=[];

		$sql = "select adif, prefix, name, date(end) Enddate, date(start) Startdate, lat, `long`
			from dxcc_entities";

		if ($postdata['notworked'] == NULL) {
			$sql .= " join (select col_dxcc from " . $this->config->item('table_name') . " thcv
			LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
			where station_id in (" . $location_list . ") and col_dxcc > 0";

			if ($postdata['band'] != 'All') {
				if ($postdata['band'] == 'SAT') {
					$sql .= " and col_prop_mode = ?";
					$bindings[]=$postdata['band'];
					if ($postdata['sat'] != 'All') {
						$sql .= " and col_sat_name = ?";
						$bindings[]=$postdata['sat'];
					}
				} else {
					$sql .= " and col_prop_mode !='SAT'";
					$sql .= " and col_band = ?";
					$bindings[]=$postdata['band'];
				}
			} else {
				$sql.=" and (col_prop_mode != 'SAT' or col_prop_mode is null)";
			}

			if ($postdata['dateFrom'] != NULL) {
				$sql .= " and col_time_on >= ?";
				$bindings[]=$postdata['dateFrom'] . ' 00:00:00';
			}

			if ($postdata['dateTo'] != NULL) {
				$sql .= " and col_time_on <= ?";
				$bindings[]=$postdata['dateTo'] . ' 23:59:59';
			}

			if ($postdata['mode'] != 'All') {
				$sql .= " and (col_mode = ? or col_submode = ?)";
				$bindings[]=$postdata['mode'];
				$bindings[]=$postdata['mode'];
			}

			$sql .= $this->addOrbitToQuery($postdata, $bindings);

			$sql .= ' group by col_dxcc) x on dxcc_entities.adif = x.col_dxcc';
		}

		$sql .= " where 1 = 1";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$sql .= ' order by prefix';
		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	// Made function instead of repeating this several times
	function addContinentsToQuery($postdata) {
		$sql = '';
		if ($postdata['Africa'] == NULL) {
			$sql .= " and cont <> 'AF'";
		}

		if ($postdata['Europe'] == NULL) {
			$sql .= " and cont <> 'EU'";
		}

		if ($postdata['Asia'] == NULL) {
			$sql .= " and cont <> 'AS'";
		}

		if ($postdata['SouthAmerica'] == NULL) {
			$sql .= " and cont <> 'SA'";
		}

		if ($postdata['NorthAmerica'] == NULL) {
			$sql .= " and cont <> 'NA'";
		}

		if ($postdata['Oceania'] == NULL) {
			$sql .= " and cont <> 'OC'";
		}

		if ($postdata['Antarctica'] == NULL) {
			$sql .= " and cont <> 'AN'";
		}
		return $sql;
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

	/*
	 * Functions below are all used in the calltester controller
	*/

	/*
     * Check the dxcc_prefixes table and return (dxcc, country)
     */
	public function check_dxcc_table($call, $date) {

		$date = date("Y-m-d", strtotime($date));
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';

		$dxcc_exceptions = $this->db->select('`entity`, `adif`, `cqz`, `cont`')
			->where('`call`', $call)
			->where('(start <= ', $date)
			->or_where('start is null)', NULL, false)
			->where('(end >= ', $date)
			->or_where('end is null)', NULL, false)
			->get('dxcc_exceptions');

		if ($dxcc_exceptions->num_rows() > 0) {
			$row = $dxcc_exceptions->row_array();
			return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
		}
		if (preg_match('/(^KG4)[A-Z09]{3}/', $call)) {      // KG4/ and KG4 5 char calls are Guantanamo Bay. If 4 or 6 char, it is USA
			$call = "K";
		} elseif (preg_match('/(^OH\/)|(\/OH[1-9]?$)/', $call)) {   # non-Aland prefix!
			$call = "OH";                                             # make callsign OH = finland
		} elseif (preg_match('/(^CX\/)|(\/CX[1-9]?$)/', $call)) {   # non-Antarctica prefix!
			$call = "CX";                                             # make callsign CX = Uruguay
		} elseif (preg_match('/(^3D2R)|(^3D2.+\/R)/', $call)) {     # seems to be from Rotuma
			$call = "3D2/R";                                          # will match with Rotuma
		} elseif (preg_match('/^3D2C/', $call)) {                   # seems to be from Conway Reef
			$call = "3D2/C";                                          # will match with Conway
		} elseif (preg_match('/(^LZ\/)|(\/LZ[1-9]?$)/', $call)) {   # LZ/ is LZ0 by DXCC but this is VP8h
			$call = "LZ";
		} elseif (preg_match('/(^KG4)[A-Z09]{2}/', $call)) {
			$call = "KG4";
		} elseif (preg_match('/(^KG4)[A-Z09]{1}/', $call)) {
			$call = "K";
		} elseif (preg_match('/\w\/\w/', $call)) {
			if (preg_match_all('/^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/', $call, $matches)) {
				$prefix = $matches[1][0];
				$callsign = $matches[3][0];
				$suffix = $matches[5][0];
				if ($prefix) {
					$prefix = substr($prefix, 0, -1); # Remove the / at the end
				}
				if ($suffix) {
					$suffix = substr($suffix, 1); # Remove the / at the beginning
				};
				if (preg_match($csadditions, $suffix)) {
					if ($prefix) {
						$call = $prefix;
					} else {
						$call = $callsign;
					}
				} else {
					$result = $this->wpx($call, 1);                       # use the wpx prefix instead
					if ($result == '') {
						$row['adif'] = 0;
						$row['entity'] = '- NONE -';
						$row['cqz'] = 0;
						$row['cont'] = '';
						return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
					} else {
						$call = $result . "AA";
					}
				}
			}
		}

		$len = strlen($call);
		$dxcc_array = [];
		// Fetch all candidates in one shot instead of looping
		$dxcc_result = $this->db->query("SELECT `call`, `entity`, `adif`, `cqz`, `cont`
		    FROM `dxcc_prefixes`
		    WHERE ? like concat(`call`,'%')
		    and `call` like ?
		    AND (`start` <= ?  OR start is null)
		    AND (`end` >= ?  OR end is null) order by length(`call`) desc limit 1", array($call, substr($call, 0, 1) . '%', $date, $date));

		foreach ($dxcc_result->result_array() as $row) {
			$dxcc_array[$row['call']] = $row;
		}

		// query the table, removing a character from the right until a match
		for ($i = $len; $i > 0; $i--) {
			//printf("searching for %s\n", substr($call, 0, $i));
			if (array_key_exists(substr($call, 0, $i), $dxcc_array)) {
				$row = $dxcc_array[substr($call, 0, $i)];
				// $row = $dxcc_result->row_array();
				return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
			}
		}

		return array("Not Found", "Not Found");
	}

	function wpx($testcall, $i) {
		$prefix = '';
		$a = '';
		$b = '';
		$c = '';

		$lidadditions = '/^QRP$|^LGT$/';
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';
		$noneadditions = '/^MM$|^AM$/';

		# First check if the call is in the proper format, A/B/C where A and C
		# are optional (prefix of guest country and P, MM, AM etc) and B is the
		# callsign. Only letters, figures and "/" is accepted, no further check if the
		# callsign "makes sense".
		# 23.Apr.06: Added another "/X" to the regex, for calls like RV0AL/0/P
		# as used by RDA-DXpeditions....

		if (preg_match_all('/^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/', $testcall, $matches)) {

			# Now $1 holds A (incl /), $3 holds the callsign B and $5 has C
			# We save them to $a, $b and $c respectively to ensure they won't get
			# lost in further Regex evaluations.
			$a = $matches[1][0];
			$b = $matches[3][0];
			$c = $matches[5][0];

			if ($a) {
				$a = substr($a, 0, -1); # Remove the / at the end
			}
			if ($c) {
				$c = substr($c, 1); # Remove the / at the beginning
			};

			# In some cases when there is no part A but B and C, and C is longer than 2
			# letters, it happens that $a and $b get the values that $b and $c should
			# have. This often happens with liddish callsign-additions like /QRP and
			# /LGT, but also with calls like DJ1YFK/KP5. ~/.yfklog has a line called
			# "lidadditions", which has QRP and LGT as defaults. This sorts out half of
			# the problem, but not calls like DJ1YFK/KH5. This is tested in a second
			# try: $a looks like a call (.\d[A-Z]) and $b doesn't (.\d), they are
			# swapped. This still does not properly handle calls like DJ1YFK/KH7K where
			# only the OP's experience says that it's DJ1YFK on KH7K.
			if (!$c && $a && $b) {                          # $a and $b exist, no $c
				if (preg_match($lidadditions, $b)) {        # check if $b is a lid-addition
					$b = $a;
					$a = null;                              # $a goes to $b, delete lid-add
				} elseif ((preg_match('/\d[A-Z]+$/', $a)) && (preg_match('/\d$/', $b) || preg_match('/^[A-Z]\d[A-Z]$/', $b))) {   # check for call in $a
					$temp = $b;
					$b = $a;
					$a = $temp;
				}
			}

			# *** Added later ***  The check didn't make sure that the callsign
			# contains a letter. there are letter-only callsigns like RAEM, but not
			# figure-only calls.

			if (preg_match('/^[0-9]+$/', $b)) {            # Callsign only consists of numbers. Bad!
				return null;            # exit, undef
			}

			# Depending on these values we have to determine the prefix.
			# Following cases are possible:
			#
			# 1.    $a and $c undef --> only callsign, subcases
			# 1.1   $b contains a number -> everything from start to number
			# 1.2   $b contains no number -> first two letters plus 0
			# 2.    $a undef, subcases:
			# 2.1   $c is only a number -> $a with changed number
			# 2.2   $c is /P,/M,/MM,/AM -> 1.
			# 2.3   $c is something else and will be interpreted as a Prefix
			# 3.    $a is defined, will be taken as PFX, regardless of $c

			if (($a == null) && ($c == null)) {                     # Case 1
				if (preg_match('/\d/', $b)) {                       # Case 1.1, contains number
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # Prefix is all but the last
					$prefix = $matches[1];                          # Letters
				} else {                                            # Case 1.2, no number
					$prefix = substr($b, 0, 2) . "0";               # first two + 0
				}
			} elseif (($a == null) && (isset($c))) {                # Case 2, CALL/X
				if (preg_match('/^(\d)/', $c)) {                    # Case 2.1, number
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # regular Prefix in $1
					# Here we need to find out how many digits there are in the
					# prefix, because for example A45XR/0 is A40. If there are 2
					# numbers, the first is not deleted. If course in exotic cases
					# like N66A/7 -> N7 this brings the wrong result of N67, but I
					# think that's rather irrelevant cos such calls rarely appear
					# and if they do, it's very unlikely for them to have a number
					# attached.   You can still edit it by hand anyway..
					if (preg_match('/^([A-Z]\d{2,})$/', $matches[1])) {        # e.g. A45   $c = 0
						$prefix = $matches[1] . $c;  # ->   A40
					} else {                         # Otherwise cut all numbers
						preg_match('/(.*[A-Z])\d+/', $matches[1], $match); # Prefix w/o number in $1
						$prefix = $match[1] . $c; # Add attached number
					}
				} elseif (preg_match($csadditions, $c)) {
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # Known attachment -> like Case 1.1
					$prefix = $matches[1];
				} elseif (preg_match($noneadditions, $c)) {
					return '';
				} elseif (preg_match('/^\d\d+$/', $c)) {            # more than 2 numbers -> ignore
					preg_match('/(.+\d)[A-Z]* /', $b, $matches);    # see above
					$prefix = $matches[1][0];
				} else {                                            # Must be a Prefix!
					if (preg_match('/\d$/', $c)) {                  # ends in number -> good prefix
						$prefix = $c;
					} else {                                        # Add Zero at the end
						$prefix = $c . "0";
					}
				}
			} elseif (($a) && (preg_match($noneadditions, $c))) {                # Case 2.1, X/CALL/X ie TF/DL2NWK/MM - DXCC none
				return '';
			} elseif ($a) {
				# $a contains the prefix we want
				if (preg_match('/\d$/', $a)) {                      # ends in number -> good prefix
					$prefix = $a;
				} else {                                            # add zero if no number
					$prefix = $a . "0";
				}
			}
			# In very rare cases (right now I can only think of KH5K and KH7K and FRxG/T
			# etc), the prefix is wrong, for example KH5K/DJ1YFK would be KH5K0. In this
			# case, the superfluous part will be cropped. Since this, however, changes the
			# DXCC of the prefix, this will NOT happen when invoked from with an
			# extra parameter $_[1]; this will happen when invoking it from &dxcc.

			if (preg_match('/(\w+\d)[A-Z]+\d/', $prefix, $matches) && $i == null) {
				$prefix = $matches[1][0];
			}
			return $prefix;
		} else {
			return '';
		}
	}

	public function dxcc_lookup($call, $date) {

		$date = date("Y-m-d", strtotime($date));
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';

		$dxcc_exceptions = $this->db->select('`entity`, `adif`, `cqz`,`cont`,`long`,`lat`')
			->where('`call`', $call)
			->where('(start <= ', $date)
			->or_where('start is null)', NULL, false)
			->where('(end >= ', $date)
			->or_where('end is null)', NULL, false)
			->get('dxcc_exceptions');
		if ($dxcc_exceptions->num_rows() > 0) {
			$row = $dxcc_exceptions->row_array();
			return $row;
		} else {

			if (preg_match('/(^KG4)[A-Z09]{3}/', $call)) {       // KG4/ and KG4 5 char calls are Guantanamo Bay. If 4 or 6 char, it is USA
				$call = "K";
			} elseif (preg_match('/(^OH\/)|(\/OH[1-9]?$)/', $call)) {   # non-Aland prefix!
				$call = "OH";                                             # make callsign OH = finland
			} elseif (preg_match('/(^CX\/)|(\/CX[1-9]?$)/', $call)) {   # non-Antarctica prefix!
				$call = "CX";                                             # make callsign CX = Uruguay
			} elseif (preg_match('/(^3D2R)|(^3D2.+\/R)/', $call)) {     # seems to be from Rotuma
				$call = "3D2/R";                                          # will match with Rotuma
			} elseif (preg_match('/^3D2C/', $call)) {                   # seems to be from Conway Reef
				$call = "3D2/C";                                          # will match with Conway
			} elseif (preg_match('/(^LZ\/)|(\/LZ[1-9]?$)/', $call)) {   # LZ/ is LZ0 by DXCC but this is VP8h
				$call = "LZ";
			} elseif (preg_match('/(^KG4)[A-Z09]{2}/', $call)) {
				$call = "KG4";
			} elseif (preg_match('/(^KG4)[A-Z09]{1}/', $call)) {
				$call = "K";
			} elseif (preg_match('/\w\/\w/', $call)) {
				if (preg_match_all('/^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/', $call, $matches)) {
					$prefix = $matches[1][0];
					$callsign = $matches[3][0];
					$suffix = $matches[5][0];
					if ($prefix) {
						$prefix = substr($prefix, 0, -1); # Remove the / at the end
					}
					if ($suffix) {
						$suffix = substr($suffix, 1); # Remove the / at the beginning
					};
					if (preg_match($csadditions, $suffix)) {
						if ($prefix) {
							$call = $prefix;
						} else {
							$call = $callsign;
						}
					} else {
						$result = $this->wpx($call, 1);                       # use the wpx prefix instead
						if ($result == '') {
							$row['adif'] = 0;
							$row['cont'] = '';
							$row['entity'] = '- NONE -';
							$row['ituz'] = 0;
							$row['cqz'] = 0;
							$row['long'] = '0';
							$row['lat'] = '0';
							return $row;
						} else {
							$call = $result . "AA";
						}
					}
				}
			}

			$len = strlen($call);
			$dxcc_array = [];

			// Fetch all candidates in one shot instead of looping
			$dxcc_result = $this->db->query("SELECT `dxcc_prefixes`.`record`, `dxcc_prefixes`.`call`, `dxcc_prefixes`.`entity`, `dxcc_prefixes`.`adif`, `dxcc_prefixes`.`cqz`, `dxcc_entities`.`ituz`, `dxcc_prefixes`.`cont`, `dxcc_prefixes`.`long`, `dxcc_prefixes`.`lat`, `dxcc_prefixes`.`start`, `dxcc_prefixes`.`end`
			    FROM `dxcc_prefixes`
			    LEFT JOIN `dxcc_entities` ON `dxcc_entities`.`adif` = `dxcc_prefixes`.`adif`
			    WHERE ? like concat(`call`,'%')
			    and `dxcc_prefixes`.`call` like ?
			    AND (`dxcc_prefixes`.`start` <= ?  OR `dxcc_prefixes`.`start` is null)
			    AND (`dxcc_prefixes`.`end` >= ?  OR `dxcc_prefixes`.`end` is null) order by length(`call`) desc limit 1", array($call, substr($call, 0, 1) . '%', $date, $date));

			foreach ($dxcc_result->result_array() as $row) {
				$dxcc_array[$row['call']] = $row;
			}

			// query the table, removing a character from the right until a match
			for ($i = $len; $i > 0; $i--) {
				if (array_key_exists(substr($call, 0, $i), $dxcc_array)) {
					$row = $dxcc_array[substr($call, 0, $i)];
					return $row;
				}
			}
		}

		return array(
			'adif' => 0,
			'cqz' => 0,
			'ituz' => 0,
			'long' => '',
			'lat' => '',
			'entity' => 'None',
		);
	}

	function getQsos($station_id) {
		ini_set('memory_limit', '-1');
		$sql = 'select distinct col_country, col_call, col_dxcc, date(col_time_on) date, station_profile.station_profile_name, col_primary_key
			from ' . $this->config->item('table_name') . '
			join station_profile on ' . $this->config->item('table_name') . '.station_id = station_profile.station_id
			where station_profile.user_id = ?';
		$params[] = $this->session->userdata('user_id');

		if ($station_id && is_numeric($station_id)) {
			$sql .= ' and ' . $this->config->item('table_name') . '.station_id = ?';
			$params[] = $station_id;
		}

		$sql .= ' order by station_profile.station_profile_name asc, date desc';

        $query = $this->db->query($sql, $params);

		return $query;
	}
}
?>
