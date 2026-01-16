<?php

class Gridmap_model extends CI_Model {

	function get_band_confirmed($band, $mode, $qsl, $lotw, $eqsl, $qrz, $sat, $orbit, $propagation, $dxcc, $grids, $datefrom, $dateto, $logbooks_locations_array = NULL, $call = '') {
		if ($logbooks_locations_array == NULL) {
			$this->load->model('logbooks_model');
			$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		}

		if (!$logbooks_locations_array) {
			return null;
        }

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$binding = [];
		$sql = 'SELECT distinct substring(COL_GRIDSQUARE,1,6) as GRID_SQUARES, COL_BAND FROM '
			.$this->config->item('table_name')
			.' LEFT JOIN `satellite` on '.$this->config->item('table_name').'.COL_SAT_NAME = satellite.name'
			.' WHERE station_id in ('
			.$location_list.') AND COL_GRIDSQUARE != ""';

		if ($call != '' && $call != '*') {
			$sql .= " and col_call = ?";
			$binding[] = $call;
		}

		if ($band != 'All') {
			if ($band == 'SAT') {
				$sql .= " and col_prop_mode = ? ";
				$binding[] = $band;
				if ($sat != 'All' && $sat != '') {
					$sql .= " and col_sat_name = ?";
					$binding[] = $sat;
				}
			} else {
				if ($propagation == 'None') {
					$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
				} elseif ($propagation == 'NoSAT') {
					$sql .= " and col_prop_mode != 'SAT'";
				} elseif ($propagation != '') {
					$sql .= " and col_prop_mode = ?";
					$binding[] = $propagation;
				}
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
		} else {
			if ($propagation == 'None') {
				$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
			} elseif ($propagation == 'NoSAT') {
				$sql .= " and col_prop_mode != 'SAT'";
			} elseif ($propagation != '') {
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propagation;
			}
		}

		if ($mode != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		if ($orbit != 'All') {
			$sql .= " AND satellite.orbit = ?";
			$binding[] = $orbit;
		}

		if ($datefrom != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$binding[] = $datefrom;
		}

		if ($dateto != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$binding[] = $dateto;
		}

		if ($dxcc != 'All') {
			if (!empty($grids)) {
				$sql .= ' AND substring(COL_GRIDSQUARE,1,4) IN (\'' . implode("','", $grids) . '\')';
			}
		}

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $qrz);

		return $this->db->query($sql, $binding);
	}

	function get_band($band, $mode, $qsl, $lotw, $eqsl, $qrz, $sat, $orbit, $propagation, $dxcc, $grids, $datefrom, $dateto, $logbooks_locations_array = NULL, $call = '') {
		if ($logbooks_locations_array == NULL) {
			$this->load->model('logbooks_model');
			$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		}

		if (!$logbooks_locations_array) {
			return null;
        }

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$binding = [];
		$sql = 'SELECT distinct substring(COL_GRIDSQUARE,1,6) as GRID_SQUARES, COL_BAND FROM '
			.$this->config->item('table_name')
			.' LEFT JOIN `satellite` on '.$this->config->item('table_name').'.COL_SAT_NAME = satellite.name'
			.' WHERE station_id in ('
			.$location_list.') AND COL_GRIDSQUARE != ""';

		if ($call != '' && $call != '*') {
			$sql .= " and col_call = ?";
			$binding[] = $call;
		}

		if ($band != 'All') {
			if ($band == 'SAT') {
				$sql .= " and col_prop_mode = ?";
				$binding[] = $band;
				if ($sat != 'All' && $sat != '') {
					$sql .= " and col_sat_name = ?";
					$binding[] = $sat;
				}
			} else {
				if ($propagation == 'None') {
					$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
				} elseif ($propagation == 'NoSAT') {
					$sql .= " and col_prop_mode != 'SAT'";
				} elseif ($propagation != '') {
					$sql .= " and col_prop_mode = ?";
					$binding[] = $propagation;
				}
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
		} else {
			if ($propagation == 'None') {
				$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
			} elseif ($propagation == 'NoSAT') {
				$sql .= " and col_prop_mode != 'SAT'";
			} elseif ($propagation != '') {
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propagation;
			}
		}

		if ($mode != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		if ($datefrom != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$binding[] = $datefrom;
		}

		if ($dateto != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$binding[] = $dateto;
		}

		if ($orbit != 'All') {
			$sql .= " AND satellite.orbit = ?";
			$binding[] = $orbit;
		}

		if ($dxcc != 'All') {
			if (!empty($grids)) {
				$sql .= ' AND substring(COL_GRIDSQUARE,1,4) IN (\'' . implode("','", $grids) . '\')';
			}
		}

		return $this->db->query($sql, $binding);
	}

	function get_band_worked_vucc_squares($band, $mode, $qsl, $lotw, $eqsl, $qrz, $sat, $orbit, $propagation, $datefrom, $dateto, $logbooks_locations_array = NULL, $call = '') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$binding = [];
		$sql = 'SELECT distinct COL_VUCC_GRIDS, COL_BAND FROM '
			.$this->config->item('table_name')
			.' LEFT JOIN `satellite` on '.$this->config->item('table_name').'.COL_SAT_NAME = satellite.name'
			.' WHERE station_id in ('
			.$location_list.') AND COL_VUCC_GRIDS != ""';

		if ($call != '' && $call != '*') {
			$sql .= " and col_call = ?";
			$binding[] = $call;
		}

		if ($band != 'All') {
			if ($band == 'SAT') {
				$sql .= " and col_prop_mode = ?";
				$binding[] = $band;
				if ($sat != 'All' && $sat != '') {
					$sql .= " and col_sat_name = ?";
					$binding[] = $sat;
				}
			} else {
				if ($propagation == 'None') {
					$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
				} elseif ($propagation == 'NoSAT') {
					$sql .= " and col_prop_mode != 'SAT'";
				} elseif ($propagation != '') {
					$sql .= " and col_prop_mode = ?";
					$binding[] = $propagation;
				}
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
		} else {
			if ($propagation == 'None') {
				$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
			} elseif ($propagation == 'NoSAT') {
				$sql .= " and col_prop_mode != 'SAT'";
			} elseif ($propagation != '') {
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propagation;
			}
		}

		if ($mode != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		if ($datefrom != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$binding[] = $datefrom;
		}

		if ($dateto != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$binding[] = $dateto;
		}

		if ($orbit != 'All') {
			$sql .= " AND satellite.orbit = ?";
			$binding[] = $orbit;
		}

		return $this->db->query($sql, $binding);
	}

	function get_band_confirmed_vucc_squares($band, $mode, $qsl, $lotw, $eqsl, $qrz, $sat, $orbit, $propagation, $datefrom, $dateto, $logbooks_locations_array = NULL, $call = '') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$binding = [];
		$sql = 'SELECT distinct COL_VUCC_GRIDS, COL_BAND FROM '
			.$this->config->item('table_name')
			.' LEFT JOIN `satellite` on '.$this->config->item('table_name').'.COL_SAT_NAME = satellite.name'
			.' WHERE station_id in ('
			.$location_list.') AND COL_VUCC_GRIDS != ""';

		if ($call != '' && $call != '*') {
			$sql .= " and col_call = ?";
			$binding[] = $call;
		}

		if ($band != 'All') {
			if ($band == 'SAT') {
				$sql .= " and col_prop_mode = ?";
				$binding[] = $band;
				if ($sat != 'All' && $sat != '') {
					$sql .= " and col_sat_name = ?";
					$binding[] = $sat;
				}
			} else {
				if ($propagation == 'None') {
					$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
				} elseif ($propagation == 'NoSAT') {
					$sql .= " and col_prop_mode != 'SAT'";
				} elseif ($propagation != '') {
					$sql .= " and col_prop_mode = ?";
					$binding[] = $propagation;
				}
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
		} else {
			if ($propagation == 'None') {
				$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
			} elseif ($propagation == 'NoSAT') {
				$sql .= " and col_prop_mode != 'SAT'";
			} elseif ($propagation != '') {
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propagation;
			}
		}

		if ($datefrom != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$binding[] = $datefrom;
		}

		if ($dateto != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$binding[] = $dateto;
		}

		if ($mode != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $qrz);

		if ($orbit != 'All') {
			$sql .= " AND satellite.orbit = ?";
			$binding[] = $orbit;
		}

		return $this->db->query($sql, $binding);
	}

	// Adds confirmation to query
	function addQslToQuery($qsl, $lotw, $eqsl, $qrz) {
		$sql = '';
		if ($lotw == "true") {
			$sql .= " or col_lotw_qsl_rcvd = 'Y'";
		}

		if ($qsl == "true") {
			$sql .= " or col_qsl_rcvd = 'Y'";
		}

		if ($eqsl == "true") {
			$sql .= " or col_eqsl_qsl_rcvd = 'Y'";
		}

		if ($qrz == "true") {
			$sql .= " or col_qrzcom_qso_download_status = 'Y'";
		}

		if ($sql != '') {
			$sql=' and (1=0 '.$sql.')';
		}

		if ($sql == '') {
			$sql=' and 1=0';
		}

		return $sql;
	}

    /*
	 * Get's the worked modes from the log
	 */
	function get_worked_modes() {

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		// get all worked modes from database
		$data = $this->db->query(
			"SELECT distinct LOWER(`COL_MODE`) as `COL_MODE` FROM `" . $this->config->item('table_name') . "` WHERE station_id in (" . $location_list . ") order by COL_MODE ASC"
		);
		$results = array();
		foreach ($data->result() as $row) {
			array_push($results, $row->COL_MODE);
		}

		$data = $this->db->query(
			"SELECT distinct LOWER(`COL_SUBMODE`) as `COL_SUBMODE` FROM `" . $this->config->item('table_name') . "` WHERE station_id in (" . $location_list . ") and coalesce(COL_SUBMODE, '') <> '' order by COL_SUBMODE ASC"
		);
		foreach ($data->result() as $row) {
			if (!in_array($row, $results)) {
				array_push($results, $row->COL_SUBMODE);
			}
		}
		asort($results);

		return $results;
	}

	function get_countries() {
		$sql = 'SELECT distinct vuccgrids.adif, dxcc_entities.name, dxcc_entities.prefix from vuccgrids join dxcc_entities on vuccgrids.adif = dxcc_entities.adif
		where name is not null and name != "" order by prefix';

		return $this->db->query($sql);
	}

	function get_grids_for_country($dxcc) {
		$gridsql = 'select gridsquare from vuccgrids where adif = ?';
		$gridquery = $this->db->query($gridsql, array($dxcc));
		$gridarray = array_column($gridquery->result_array(), 'gridsquare');

		return $gridarray;
	}

	function get_coordinates_for_dxcc($dxcc) {
		// Get country coordinates if a specific country is selected
		$country_coords = null;
		if ($dxcc && $dxcc != 'All') {
			// Query dxcc_entities table for country info
			$sql = "SELECT adif, lat, `long`, name FROM dxcc_entities WHERE adif = ?";
			$query = $this->db->query($sql, array($dxcc));

			if ($query && $query->num_rows() > 0) {
				$country_info = $query->row();
				$country_coords = [
					'lat' => (float)$country_info->lat,
					'long' => (float)$country_info->long,
					'name' => $country_info->name
				];
			}
		}
		return $country_coords;
	}
}
