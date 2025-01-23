<?php

class Lookup_model extends CI_Model{

	function getSearchResult($queryinfo){
		$modes = $this->get_worked_modes($queryinfo['location_list']);

		return $this->getResultFromDatabase($queryinfo, $modes);
	}

	function getResultFromDatabase($queryinfo, $modes) {
		// Creating an empty array with all the bands and modes from the database
		foreach ($modes as $mode) {
			foreach ($queryinfo['bands'] as $band) {
				$resultArray[$mode][$band] = '-';
			}
		}

		// Populating array with worked band/mode combinations
		$worked = $this->getQueryData($queryinfo, 'worked');
		foreach ($worked as $w) {
			if(in_array($w->col_band, $queryinfo['bands'])) {
				$resultArray[$w->col_mode][$w->col_band] = 'W';
			}
		}

		// Populating array with confirmed band/mode combinations
		$confirmed = $this->getQueryData($queryinfo, 'confirmed');
		foreach ($confirmed as $c) {
			if(in_array($c->col_band, $queryinfo['bands'])) {
				$resultArray[$c->col_mode][$c->col_band] = 'C';
			}
		}

		if (!(isset($resultArray))) $resultArray=[];
		return $resultArray;
	}

	/*
	 * Builds information-where-part of query depending on what we are searching for
	 */
	private function build_info_query($queryinfo,&$binds) {
		$sqlquerytypestring='';
		switch ($queryinfo['type']) {
		case 'dxcc': 
			$sqlquerytypestring .= " and col_dxcc = ?";
			$binds[]=$queryinfo['dxcc']; 
			break;
		case 'iota': 
			$sqlquerytypestring .= " and col_iota = ?";
			$binds[]=$queryinfo['iota']; 
			break;
		case 'vucc': 
			$sqlquerytypestring .= " and (col_gridsquare like ? or col_vucc_grids like ?)";
			$binds[]='%'.$fixedgrid.'%';
			$binds[]='%'.$fixedgrid.'%'; 
			break;
		case 'cq':   
			$sqlquerytypestring .= " and col_cqz = ?";
			$binds[]=$queryinfo['cqz'];
			break;
		case 'was':  
			$sqlquerytypestring .= " and col_state = ? and COL_DXCC in ('291', '6', '110')"; 
			$binds[]=$queryinfo['was'];
			break;
		case 'sota': 
			$sqlquerytypestring .= " and col_sota_ref = ?";
			$binds[]=$queryinfo['sota'];
			break;
		case 'wwff': 
			$sqlquerytypestring .= " and col_sig = 'WWFF' and col_sig_info = ?";
			$binds[]=$queryinfo['wwff'];
			break;
		case 'itu':  
			$sqlquerytypestring .= " and col_ituz = ?";
			$binds[]=$queryinfo['ituz'];
			break;
		default: break;
		}
		return $sqlquerytypestring;
	}

	/*
	 * Builds query depending on what we are searching for
	 */
	function getQueryData($queryinfo, $confirmedtype) {
		// If user inputs longer grid than 4 chars, we use only the first 4
		$binds=[];
		if (strlen($queryinfo['grid']) > 4) {
			$fixedgrid = substr($queryinfo['grid'], 0, 4);
		}
		else {
			$fixedgrid = $queryinfo['grid'];
		}

		$sqlquerytypestring = '';


		if ($confirmedtype == 'confirmed') {
			$user_default_confirmation = $this->session->userdata('user_default_confirmation');
			$extrawhere='';
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
				$extrawhere="COL_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_LOTW_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_EQSL_QSL_RCVD='Y'";
			}

			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_QRZCOM_QSO_DOWNLOAD_STATUS='Y'";
			}

			if (($confirmedtype == 'confirmed') && ($extrawhere != '')){
				$sqlqueryconfirmationstring = " and (".$extrawhere.")";
			} else {
				$sqlqueryconfirmationstring = ' and (1=0)';
			}
		} else {
			$sqlqueryconfirmationstring = '';
		}
		// Fetching info for all modes and bands except satellite
		$sql = "SELECT distinct col_band, lower(col_mode) as col_mode FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $queryinfo['location_list'] . ")";

		$sql .= " and coalesce(col_submode, '') = ''";

		$sql .= " and col_prop_mode != 'SAT'";

		$sql .= $this->build_info_query($queryinfo,$binds);

		$sql .= $sqlqueryconfirmationstring;

		// Fetching info for all sub_modes and bands except satellite
		$sql .= " union SELECT distinct col_band, lower(col_submode) as col_mode FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $queryinfo['location_list'] . ")";

		$sql .= " and coalesce(col_submode, '') <> ''";

		$sql .= " and col_prop_mode != 'SAT'";

		$sql .= $this->build_info_query($queryinfo,$binds);

		$sql .= $sqlqueryconfirmationstring;

		// Fetching info for all modes on satellite
		$sql .= " union SELECT distinct 'SAT' col_band, lower(col_mode) as col_mode FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $queryinfo['location_list'] . ")";

		$sql .= " and coalesce(col_submode, '') = ''";

		$sql .= " and col_prop_mode = 'SAT'";

		$sql .= $this->build_info_query($queryinfo,$binds);

		$sql .= $sqlqueryconfirmationstring;

		// Fetching info for all sub_modes on satellite
		$sql .= " union SELECT distinct 'SAT' col_band, lower(col_submode) as col_mode FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $queryinfo['location_list'] . ")";

		$sql .= " and coalesce(col_submode, '') <> ''";

		$sql .= " and col_prop_mode = 'SAT'";

		$sql .= $this->build_info_query($queryinfo,$binds);

		$sql .= $sqlqueryconfirmationstring;

		$query = $this->db->query($sql,$binds);

		return $query->result();
	}

	/*
	 * Get's the worked modes from the log
	 */
	function get_worked_modes($location_list)
	{
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

		return $results;
	}
}
