<?php

class Lookup_model extends CI_Model{

	function getSatResult($queryinfo){
		$resultArray = [];
		foreach ($queryinfo['sats'] as $sat) {
			$resultArray[$sat] = '-';
		}
		$sql = "SELECT COL_SAT_NAME,
			MAX(case when col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when col_lotw_qsl_rcvd = 'Y' then 1 else 0 end) as lotw,
			MAX(case when col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when col_qrzcom_qso_download_status = 'Y' then 1 else 0 end) as qrz,
			MAX(case when col_clublog_qso_download_status = 'Y' then 1 else 0 end) as clublog
			FROM ".$this->config->item('table_name')." WHERE COL_PROP_MODE = 'SAT' AND COL_CALL = ?
			GROUP BY COL_SAT_NAME";
		$query = $this->db->query($sql, [$queryinfo['callsign']]);
		foreach ($query->result() as $sat) {
			$letters = ($sat->qsl ? 'Q' : '').($sat->lotw ? 'L' : '').($sat->eqsl ? 'E' : '').($sat->qrz ? 'Z' : '').($sat->clublog ? 'C' : '');
			$resultArray[$sat->COL_SAT_NAME] = $letters ?: 'W';
		}
		return $resultArray;
	}

	function getSearchResult($queryinfo){
		$modes = $this->get_worked_modes($queryinfo['location_list']);

		return $this->getResultFromDatabase($queryinfo, $modes);
	}

	function getDxccForVucc($grid) {
		$fixedgrid = (strlen($grid) > 4) ? substr($grid, 0, 4) : $grid;

		$sql = "select name from dxcc_entities
		join vuccgrids on dxcc_entities.adif = vuccgrids.adif
		where gridsquare = ?";
		$binds[] = $fixedgrid;

		$query = $this->db->query($sql, $binds);
		$dxccArray = [];

		foreach ($query->result() as $row) {
			$dxccArray[] = ucwords(strtolower($row->name), "- (/");
		}

		return $dxccArray;
	}

	/* Like getDxccForVucc, but returns adif + name rows (the flag is added by the
	 * caller via the DxccFlag library). Powers the activation-planner grid flag. */
	function getDxccForVuccGrid($grid) {
		$fixedgrid = (strlen($grid) > 4) ? substr($grid, 0, 4) : $grid;

		$sql = "select dxcc_entities.adif, dxcc_entities.name from dxcc_entities
		join vuccgrids on dxcc_entities.adif = vuccgrids.adif
		where gridsquare = ?";
		$query = $this->db->query($sql, array($fixedgrid));

		return $query->result();
	}

	function getResultFromDatabase($queryinfo, $modes) {
		// Creating an empty array with all the bands and modes from the database
		foreach ($modes as $mode) {
			foreach ($queryinfo['bands'] as $band) {
				$resultArray[$mode][$band] = '-';
			}
		}

		// Populating array with worked/confirmed band/mode combinations (Q/L/E/Z/C letters like the DXCC award table)
		foreach ($this->getQueryData($queryinfo) as $r) {
			if (in_array($r->col_band, $queryinfo['bands'])) {
				$letters = ($r->qsl ? 'Q' : '').($r->lotw ? 'L' : '').($r->eqsl ? 'E' : '').($r->qrz ? 'Z' : '').($r->clublog ? 'C' : '');
				$resultArray[$r->col_mode][$r->col_band] = $letters ?: 'W';
			}
		}

		if (!(isset($resultArray))) $resultArray=[];
		return $resultArray;
	}

	/*
	 * Builds information-where-part of query depending on what we are searching for
	 */
	private function build_info_query($queryinfo, &$binds) {
		$sqlquerytypestring='';
		if (strlen($queryinfo['grid']) > 4) {
			$fixedgrid = substr($queryinfo['grid'], 0, 4);
		}
		else {
			$fixedgrid = $queryinfo['grid'];
		}

		switch ($queryinfo['type']) {
		case 'dxcc':
			$sqlquerytypestring .= " and col_dxcc = ?";
			$binds[] = $queryinfo['dxcc'];
			break;
		case 'iota':
			$sqlquerytypestring .= " and col_iota = ?";
			$binds[] = $queryinfo['iota'];
			break;
		case 'vucc':
			$sqlquerytypestring .= " and (col_gridsquare like ? or col_vucc_grids like ?)";
			$binds[] = '%'.$fixedgrid.'%';
			$binds[] = '%'.$fixedgrid.'%';
			break;
		case 'cq':
			$sqlquerytypestring .= " and col_cqz = ?";
			$binds[] = $queryinfo['cqz'];
			break;
		case 'was':
			$sqlquerytypestring .= " and col_state = ? and COL_DXCC in ('291', '6', '110')";
			$binds[] = $queryinfo['was'];
			break;
		case 'sota':
			$sqlquerytypestring .= " and col_sota_ref = ?";
			$binds[] = $queryinfo['sota'];
			break;
		case 'pota':
			$sqlquerytypestring .= " and col_pota_ref = ?";
			$binds[] = $queryinfo['pota'];
			break;
		case 'wwff':
			$sqlquerytypestring .= " and col_wwff_ref = ?";
			$binds[] = $queryinfo['wwff'];
			break;
		case 'itu':
			$sqlquerytypestring .= " and col_ituz = ?";
			$binds[] = $queryinfo['ituz'];
			break;
		case 'continent':
			$sqlquerytypestring .= " and col_cont = ?";
			$binds[] = $queryinfo['continent'];
			break;
		case 'dok':
			$sqlquerytypestring .= " and col_darc_dok = ?";
			$binds[] = $queryinfo['dok'];
			break;
		default: break;
		}
		return $sqlquerytypestring;
	}

	/*
	 * Builds query depending on what we are searching for.
	 * One row per band/mode, MAX-flags per confirmation type (same logic as the DXCC award table).
	 */
	function getQueryData($queryinfo) {
		$binds = [];

		$sql = "SELECT CASE WHEN col_prop_mode = 'SAT' THEN 'SAT' ELSE col_band END as col_band,
			LOWER(COALESCE(NULLIF(col_submode, ''), col_mode)) as col_mode,
			MAX(case when col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,
			MAX(case when col_lotw_qsl_rcvd = 'Y' then 1 else 0 end) as lotw,
			MAX(case when col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,
			MAX(case when col_qrzcom_qso_download_status = 'Y' then 1 else 0 end) as qrz,
			MAX(case when col_clublog_qso_download_status = 'Y' then 1 else 0 end) as clublog
			FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $queryinfo['location_list'] . ")";

		$sql .= $this->build_info_query($queryinfo,$binds);

		$sql .= " GROUP BY 1, 2";

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
