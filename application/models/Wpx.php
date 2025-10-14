<?php

class WPX extends CI_Model {

	function __construct() {
		if(!$this->load->is_loaded('Genfunctions')) {
			$this->load->library('Genfunctions');
		}
	}

	function get_wpx_array($bands, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}
		$this->load->model('bands');

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		foreach ($bands as $band) {             	// Looping through bands and entities to generate the array needed for display
			//'<div class="bg-danger awardsBgDanger" ><a href=\'javascript:displayContacts("'.$wdxcc->dxcc.'","'. $band . '","'. $postdata['sat'] . '","' . $postdata['orbit'] . '","'. $postdata['mode'] . '","DXCC2", "")\'>W</a></div>';
			$worked = $this->getWpxForBand($band, $location_list, $postdata);
			$confirmed = $this->getWpxForBandConfirmed($band, $location_list, $postdata);
			$wpxSummary['worked'][$band] = $worked[0]->wpxcount;
			$wpxSummary['confirmed'][$band] = $confirmed[0]->wpxcount;
		}

		$workedTotal = $this->getWpxForBand($postdata['band'], $location_list, $postdata);
		$confirmedTotal = $this->getWpxForBandConfirmed($postdata['band'], $location_list, $postdata);

		$wpxSummary['worked']['Total'] = $workedTotal[0]->wpxcount;
		$wpxSummary['confirmed']['Total'] = $confirmedTotal[0]->wpxcount;

		if (isset($wpxSummary)) {
			return $wpxSummary;
		} else {
			return 0;
		}
	}

	function getWpxForBand($band, $location_list, $postdata) {
		$bindings = [];
		$sql = "select count(distinct wpx_prefix) wpxcount from (
					SELECT
				col_call,
				CASE
					/* case 1: /digit suffix → handle multi-digit prefixes correctly */
					WHEN col_call REGEXP '/[0-9]$' THEN
					CASE
						/* If prefix has multiple digits, replace the last digit */
						WHEN SUBSTRING_INDEX(col_call, '/', 1) REGEXP '^[0-9]?[A-Z]{1,3}[0-9]{2,}' THEN
						CONCAT(
							REGEXP_REPLACE(
							SUBSTRING_INDEX(col_call, '/', 1),
							'^([0-9]?[A-Z]{1,3}[0-9]*)[0-9].*$',
							CASE
								WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
								ELSE '$1'
							END
							),
							SUBSTRING_INDEX(col_call, '/', -1)
						)
						/* If prefix has single digit, replace it */
						ELSE
						CONCAT(
							REGEXP_REPLACE(
							SUBSTRING_INDEX(col_call, '/', 1),
							'^([0-9]?[A-Z]{1,3})[0-9].*$',
							CASE
								WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
								ELSE '$1'
							END
							),
							SUBSTRING_INDEX(col_call, '/', -1)
						)
					END

					/* case 2: no digit at all → append 0 */
					WHEN call_core NOT REGEXP '[A-Z][0-9]' THEN CONCAT(call_core, '0')

					/* case 3: normal/anniversary calls → keep prefix+digits */
					ELSE
					REGEXP_REPLACE(call_core, '^([0-9]?[A-Z]{1,3}[0-9]{1,4}).*$',
					CASE
						WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
						ELSE '$1'
					END)
				END AS wpx_prefix
				FROM (
				SELECT
					col_call,
					CASE
					WHEN num_slashes >= 2 THEN left_part
					WHEN num_slashes = 1 AND NOT (call_raw REGEXP '/[0-9]$') THEN
						CASE
						WHEN (left_has_digit + left_short) > (right_has_digit + right_short) THEN left_part
						WHEN (left_has_digit + left_short) < (right_has_digit + right_short) THEN right_part
						ELSE left_part
						END
					ELSE call_raw
					END AS call_core
				FROM (
					SELECT
					col_call,
					UPPER(TRIM(col_call)) AS call_raw,
					(LENGTH(UPPER(TRIM(col_call))) - LENGTH(REPLACE(UPPER(TRIM(col_call)), '/', ''))) AS num_slashes,
					SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1) AS left_part,
					SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1) AS right_part,
					(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1) REGEXP '[0-9]') AS left_has_digit,
					(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1) REGEXP '[0-9]') AS right_has_digit,
					(LENGTH(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1)) <= 3) AS left_short,
					(LENGTH(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1)) <= 3) AS right_short
					FROM ".$this->config->item('table_name')." thcv
					left join satellite on thcv.COL_SAT_NAME = satellite.name
						WHERE station_id in (" . $location_list . ") ";

					if ($postdata['mode'] != 'All') {
						$sql .= " and (col_mode = ? or col_submode = ?)";
						$bindings[] = $postdata['mode'];
						$bindings[] = $postdata['mode'];
					}
					$sql .= $this->addBandToQuery($band, $bindings);

					$sql .= $this->addContinentsToQuery($postdata);

					$sql .= $this->addOrbitToQuery($postdata, $bindings);

					if ($band == 'SAT') {
						if ($postdata['sat'] != 'All') {
							$sql .= " and col_sat_name = ?";
							$bindings[] = $postdata['sat'];
						}
					}

				$sql .= " ) AS s
					) AS t
					) as x";

		if (!empty($bindings)) {
			$query = $this->db->query($sql, $bindings);
		} else {
			$query = $this->db->query($sql);
		}

		return $query->result();
	}

	function getWpxForBandConfirmed($band, $location_list, $postdata) {
		$bindings=[];
		$sql = "select count(distinct wpx_prefix) wpxcount from (
					SELECT
				col_call,
				CASE
					/* case 1: /digit suffix → handle multi-digit prefixes correctly */
					WHEN col_call REGEXP '/[0-9]$' THEN
					CASE
						/* If prefix has multiple digits, replace the last digit */
						WHEN SUBSTRING_INDEX(col_call, '/', 1) REGEXP '^[0-9]?[A-Z]{1,3}[0-9]{2,}' THEN
						CONCAT(
							REGEXP_REPLACE(
							SUBSTRING_INDEX(col_call, '/', 1),
							'^([0-9]?[A-Z]{1,3}[0-9]*)[0-9].*$',
							CASE
								WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
								ELSE '$1'
							END
							),
							SUBSTRING_INDEX(col_call, '/', -1)
						)
						/* If prefix has single digit, replace it */
						ELSE
						CONCAT(
							REGEXP_REPLACE(
							SUBSTRING_INDEX(col_call, '/', 1),
							'^([0-9]?[A-Z]{1,3})[0-9].*$',
							CASE
								WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
								ELSE '$1'
							END
							),
							SUBSTRING_INDEX(col_call, '/', -1)
						)
					END

					/* case 2: no digit at all → append 0 */
					WHEN call_core NOT REGEXP '[A-Z][0-9]' THEN CONCAT(call_core, '0')

					/* case 3: normal/anniversary calls → keep prefix+digits */
					ELSE
					REGEXP_REPLACE(call_core, '^([0-9]?[A-Z]{1,3}[0-9]{1,4}).*$',
					CASE
						WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
						ELSE '$1'
					END)
				END AS wpx_prefix
				FROM (
				SELECT
					col_call,
					CASE
					WHEN num_slashes >= 2 THEN left_part
					WHEN num_slashes = 1 AND NOT (call_raw REGEXP '/[0-9]$') THEN
						CASE
						WHEN (left_has_digit + left_short) > (right_has_digit + right_short) THEN left_part
						WHEN (left_has_digit + left_short) < (right_has_digit + right_short) THEN right_part
						ELSE left_part
						END
					ELSE call_raw
					END AS call_core
				FROM (
					SELECT
					col_call,
					UPPER(TRIM(col_call)) AS call_raw,
					(LENGTH(UPPER(TRIM(col_call))) - LENGTH(REPLACE(UPPER(TRIM(col_call)), '/', ''))) AS num_slashes,
					SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1) AS left_part,
					SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1) AS right_part,
					(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1) REGEXP '[0-9]') AS left_has_digit,
					(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1) REGEXP '[0-9]') AS right_has_digit,
					(LENGTH(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1)) <= 3) AS left_short,
					(LENGTH(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1)) <= 3) AS right_short
					FROM ".$this->config->item('table_name')." thcv
					left join satellite on thcv.COL_SAT_NAME = satellite.name
						WHERE station_id in (" . $location_list . ") ";
				$sql .= $this->genfunctions->addQslToQuery($postdata);

				$sql .= $this->addBandToQuery($band, $bindings);

				$sql .= $this->addContinentsToQuery($postdata);

				if ($postdata['mode'] != 'All') {
					$sql .= " and (col_mode = ? or col_submode = ?)";
					$bindings[]=$postdata['mode'];
					$bindings[]=$postdata['mode'];
				}

				$sql .= $this->addContinentsToQuery($postdata);

				$sql .= $this->addOrbitToQuery($postdata,$bindings);

				if ($band == 'SAT') {
						if ($postdata['sat'] != 'All') {
							$sql .= " and col_sat_name = ?";
							$bindings[] = $postdata['sat'];
						}
					}

				$sql .= " ) AS s
					) AS t
					) as x";

		if (!empty($bindings)) {
			$query = $this->db->query($sql, $bindings);
		} else {
			$query = $this->db->query($sql);
		}

		return $query->result();
	}

	// Made function instead of repeating this several times
	function addContinentsToQuery($postdata) {
		$sql = '';
		if ($postdata['Africa'] == NULL) {
			$sql .= " and col_cont <> 'AF'";
		}

		if ($postdata['Europe'] == NULL) {
			$sql .= " and col_cont <> 'EU'";
		}

		if ($postdata['Asia'] == NULL) {
			$sql .= " and col_cont <> 'AS'";
		}

		if ($postdata['SouthAmerica'] == NULL) {
			$sql .= " and col_cont <> 'SA'";
		}

		if ($postdata['NorthAmerica'] == NULL) {
			$sql .= " and col_cont <> 'NA'";
		}

		if ($postdata['Oceania'] == NULL) {
			$sql .= " and col_cont <> 'OC'";
		}

		if ($postdata['Antarctica'] == NULL) {
			$sql .= " and col_cont <> 'AN'";
		}
		if (strlen($sql) > 0) {
			$sql .= " and col_cont <> '' and col_cont IS NOT NULL";
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

	public function get_band_details($band, $status)
	{
		$this->db->select('col_call, col_time_on, col_mode, col_qsl_rcvd, col_lotw_qsl_rcvd, col_eqsl_qsl_rcvd');

		$this->db->from($this->config->item('table_name') . ' thcv');

		$station_id = $this->session->userdata('active_station_logbook');
		if (is_array($station_id)) {
			$this->db->where_in('station_id', $station_id);
		} else {
			$this->db->where('station_id', $station_id);
		}

		$this->db->where('col_band', $band);

		// filter by status
		if ($status === 'confirmed') {
			$this->db->where("(col_qsl_rcvd = 'Y' OR col_lotw_qsl_rcvd = 'Y' OR col_eqsl_qsl_rcvd = 'Y')", null, false);
		}

		$this->db->order_by('col_time_on', 'DESC');

		$query = $this->db->get();
		return $query->result();
	}

	function getWpxBandDetails($postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}
		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$bindings = [];
		$sql = "select wpx_prefix, col_primary_key, col_call, col_time_on, col_band, col_mode, col_submode,
					col_lotw_qsl_rcvd, col_qsl_rcvd, col_eqsl_qsl_rcvd, COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_DOWNLOAD_STATUS
				from (
				select wpx_prefix, col_primary_key, col_call, col_time_on, col_band, col_mode, col_submode,
				col_lotw_qsl_rcvd, col_qsl_rcvd, col_eqsl_qsl_rcvd, COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_DOWNLOAD_STATUS,
				ROW_NUMBER() OVER (
					PARTITION BY wpx_prefix
					ORDER BY
						/* Prioritize confirmed contacts first */
						CASE
							WHEN col_lotw_qsl_rcvd = 'Y' OR
								col_qsl_rcvd = 'Y' OR
								col_eqsl_qsl_rcvd = 'Y' OR
								COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y' OR
								COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y'
							THEN 0
							ELSE 1
						END,
						/* Then by time */
						col_time_on ASC
				) as rn  from (
				SELECT col_primary_key, col_call, col_time_on, col_band, col_mode, col_submode,
				col_lotw_qsl_rcvd, col_qsl_rcvd, col_eqsl_qsl_rcvd, COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_DOWNLOAD_STATUS,
				CASE
					/* case 1: /digit suffix → handle multi-digit prefixes correctly */
					WHEN col_call REGEXP '/[0-9]$' THEN
					CASE
						/* If prefix has multiple digits, replace the last digit */
						WHEN SUBSTRING_INDEX(col_call, '/', 1) REGEXP '^[0-9]?[A-Z]{1,3}[0-9]{2,}' THEN
						CONCAT(
							REGEXP_REPLACE(
							SUBSTRING_INDEX(col_call, '/', 1),
							'^([0-9]?[A-Z]{1,3}[0-9]*)[0-9].*$',
							CASE
								WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
								ELSE '$1'
							END
							),
							SUBSTRING_INDEX(col_call, '/', -1)
						)
						/* If prefix has single digit, replace it */
						ELSE
						CONCAT(
							REGEXP_REPLACE(
							SUBSTRING_INDEX(col_call, '/', 1),
							'^([0-9]?[A-Z]{1,3})[0-9].*$',
							CASE
								WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
								ELSE '$1'
							END
							),
							SUBSTRING_INDEX(col_call, '/', -1)
						)
					END

					/* case 2: no digit at all → append 0 */
					WHEN call_core NOT REGEXP '[A-Z][0-9]' THEN CONCAT(call_core, '0')

					/* case 3: normal/anniversary calls → keep prefix+digits */
					ELSE
					REGEXP_REPLACE(call_core, '^([0-9]?[A-Z]{1,3}[0-9]{1,4}).*$',
					CASE
						WHEN VERSION() LIKE '%MariaDB%' THEN '\\\\1'
						ELSE '$1'
					END)
				END AS wpx_prefix
				FROM (
				SELECT
					col_primary_key, col_call, col_time_on, col_band, col_mode, col_submode,
					col_lotw_qsl_rcvd, col_qsl_rcvd, col_eqsl_qsl_rcvd, COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_DOWNLOAD_STATUS,
					CASE
					WHEN num_slashes >= 2 THEN left_part
					WHEN num_slashes = 1 AND NOT (call_raw REGEXP '/[0-9]$') THEN
						CASE
						WHEN (left_has_digit + left_short) > (right_has_digit + right_short) THEN left_part
						WHEN (left_has_digit + left_short) < (right_has_digit + right_short) THEN right_part
						ELSE left_part
						END
					ELSE call_raw
					END AS call_core
				FROM (
					SELECT
					col_primary_key, col_call, col_time_on, col_band, col_mode, col_submode, col_lotw_qsl_rcvd, col_qsl_rcvd, col_eqsl_qsl_rcvd, COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_DOWNLOAD_STATUS,
					UPPER(TRIM(col_call)) AS call_raw,
					(LENGTH(UPPER(TRIM(col_call))) - LENGTH(REPLACE(UPPER(TRIM(col_call)), '/', ''))) AS num_slashes,
					SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1) AS left_part,
					SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1) AS right_part,
					(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1) REGEXP '[0-9]') AS left_has_digit,
					(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1) REGEXP '[0-9]') AS right_has_digit,
					(LENGTH(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', 1)) <= 3) AS left_short,
					(LENGTH(SUBSTRING_INDEX(UPPER(TRIM(col_call)), '/', -1)) <= 3) AS right_short
					FROM ".$this->config->item('table_name')." thcv
					LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
					WHERE station_id in (" . $location_list . ") ";

					if ($postdata['mode'] != 'All') {
						$sql .= " and (col_mode = ? or col_submode = ?)";
						$bindings[] = $postdata['mode'];
						$bindings[] = $postdata['mode'];
					}
					$sql .= $this->addBandToQuery($postdata['band'], $bindings);

					if ($postdata['status'] === 'confirmed') {
						$sql .= $this->addQslToQuery($postdata);
					}
					$sql .= $this->addContinentsToQuery($postdata);

					if ($band == 'SAT') {
						if ($postdata['sat'] != 'All') {
							$sql .= " and col_sat_name = ?";
							$bindings[] = $postdata['sat'];
						}
					}

				$sql .= " ) AS s
					) AS t
					) as x
					) as ranked
					WHERE rn = 1
					ORDER BY wpx_prefix";

		if (!empty($bindings)) {
			$query = $this->db->query($sql, $bindings);
		} else {
			$query = $this->db->query($sql);
		}

		return $query->result();
	}

	function addQslToQuery($postdata) {
		$sql = '';
		$qsl = array();
		if ( (($postdata['clublog'] ?? '') != '') ||
			(($postdata['qrz'] ?? '') != '') ||
			(($postdata['lotw'] ?? '') != '') ||
			(($postdata['qsl'] ?? '') != '') ||
			(($postdata['dcl'] ?? '') != '') ||
			(($postdata['eqsl'] ?? '') != '') ) {
			$sql .= ' and (';
			if (($postdata['qsl'] ?? '') != '') {
				array_push($qsl, "col_qsl_rcvd = 'Y'");
			}
			if (($postdata['lotw'] ?? '') != '') {
				array_push($qsl, "col_lotw_qsl_rcvd = 'Y'");
			}
			if (($postdata['eqsl'] ?? '') != '') {
				array_push($qsl, "col_eqsl_qsl_rcvd = 'Y'");
			}
			if (($postdata['qrz'] ?? '') != '') {
				array_push($qsl, "COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y'");
			}
			if (($postdata['clublog'] ?? '') != '') {
				array_push($qsl, "COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y'");
			}
			if (($postdata['dcl'] ?? '') != '') {
				array_push($qsl, "COL_DCL_QSL_RCVD = 'Y'");
			}
			if (count($qsl) > 0) {
				$sql .= implode(' or ', $qsl);
			} else {
				$sql .= '1=0';
			}
			$sql .= ')';
		} else {
			$sql.=' and 1=0';
		}
		return $sql;
	}

	function addBandToQuery($band,&$binding) {
		$sql = '';
		if ($band == 'SAT') {
			$sql .= " and col_prop_mode = ?";
			$binding[]=$band;
		} else {
			if ($band == 'All' || $band == 'Total') {
				$sql .=" and (col_prop_mode!='SAT' or col_prop_mode is null)";
			} else {
				$sql .=" and (col_prop_mode!='SAT' or col_prop_mode is null)";
				$sql .= " and col_band = ?";
				$binding[]=$band;
			}
		}

		return $sql;
	}

}
?>
