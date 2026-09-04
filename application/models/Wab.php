<?php

class Wab extends CI_Model {

	function __construct() {
		$this->load->library('Genfunctions');
	}

	function get_wab_array($location_list, $postdata) { // $mode, $qsl, $lotw, $eqsl, $qrz, $clublog, $sat, $orbit) {
		$worked = array();
		$confirmed = array();

		$worked = $this->getWabWorked($location_list, $postdata);

		$confirmed = $this->getWabConfirmed($location_list, $postdata);

		$wabarray = array();

		$workedGridArray = array();
		foreach ($worked as $w) {
			array_push($workedGridArray, $w->col_sig_info);
			$wabarray += array(
				$w->col_sig_info => 'W'
			);
		}

		$confirmedGridArray = array();
		foreach ($confirmed as $c) {
			array_push($confirmedGridArray, $c->col_sig_info);

			if(array_key_exists($c->col_sig_info, $wabarray)){
				$wabarray[$c->col_sig_info] = 'C';
			} else {
				$wabarray += array(
					$c->col_sig_info => 'C'
				);
			}
		}

		return $wabarray;
	}

	/*
	 * Function returns all worked, but not confirmed states
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getWabWorked($location_list, $postdata) { // $mode, $sat, $orbit) {
		$bindings=[];
		$sql = "SELECT distinct col_sig_info FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ") and col_sig = 'WAB' and coalesce(col_sig_info, '') <> ''";

		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);

		if ($postdata['band'] == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addOrbitToQuery($postdata['orbit'],$bindings);

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * Function returns all confirmed states on given band and on LoTW or QSL
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getWabConfirmed($location_list, $postdata) { // $mode, $qsl, $lotw, $eqsl, $qrz, $clublog, $sat, $orbit) {
		$bindings=[];
		$sql = "SELECT distinct col_sig_info FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ") and col_sig = 'WAB' and coalesce(col_sig_info, '') <> ''";

		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);

		if ($postdata['band'] == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addOrbitToQuery($postdata['orbit'],$bindings);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * Function returns one row per WAB QSO, for the WAB list. Applies the
	 * same band/sat/mode/orbit filters as getWabWorked(), but no
	 * confirmation filter: the list shows every QSO with its own
	 * confirmation state per QSL system.
	 */
	function get_wab_qsos($location_list, $postdata) {
		$bindings=[];
		$sql = "select col_primary_key, col_sig_info, col_call, col_time_on, col_band, col_mode, col_submode, col_gridsquare, col_sat_name,
				col_qsl_rcvd, col_lotw_qsl_rcvd, col_eqsl_qsl_rcvd,
				COL_QRZCOM_QSO_DOWNLOAD_STATUS as qrz,
				COL_CLUBLOG_QSO_DOWNLOAD_STATUS as clublog
			from " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ") and col_sig = 'WAB' and coalesce(col_sig_info, '') <> ''";

		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);

		if ($postdata['band'] == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addOrbitToQuery($postdata['orbit'],$bindings);

		$sql .= " order by col_sig_info, col_time_on";

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	// Adds orbit type to query
	function addOrbitToQuery($orbit,&$binding) {
		$sql = '';
		if ($orbit != 'All') {
			$sql .= ' AND satellite.orbit = ?';
			$binding[]=$orbit;
		}

		return $sql;
	}

	/*
	 * WAB tool: shared WHERE for the candidate queries. QSOs with a
	 * gridsquare (>= 6 chars) that have no SIG set (or are marked WAB with
	 * an empty square). QSOs carrying another SIG (e.g. SOTA) are never
	 * returned. $search filters on callsign and gridsquare.
	 */
	private function wab_candidates_sql($station_id, $dxcc_ids, $search, &$bindings) {
		$sql = "from " . $this->config->item('table_name') . " thcv
			join station_profile on thcv.station_id = station_profile.station_id
			where station_profile.user_id = ?
			and length(col_gridsquare) >= 6
			and (coalesce(col_sig, '') = '' or (col_sig = 'WAB' and coalesce(col_sig_info, '') = ''))";
		$bindings[]=$this->session->userdata('user_id');

		if ($station_id && is_numeric($station_id)) {
			$sql .= " and thcv.station_id = ?";
			$bindings[]=$station_id;
		}

		if (is_array($dxcc_ids) && count($dxcc_ids) > 0) {
			$sql .= " and col_dxcc in (" . implode(',', array_fill(0, count($dxcc_ids), '?')) . ")";
			$bindings = array_merge($bindings, array_map('intval', $dxcc_ids));
		}

		if ($search !== '') {
			$sql .= " and (col_call like ? or col_gridsquare like ?)";
			$bindings[] = '%' . $search . '%';
			$bindings[] = '%' . $search . '%';
		}

		return $sql;
	}

	/*
	 * WAB tool: candidate rows for the paginated scan table. $order_col is
	 * the DataTables column index (1 = date/time, 2 = callsign, 3 = band,
	 * 4 = grid, 6 = station); the checkbox and square columns are not
	 * sortable server side. $limit/$offset page the result. A null $limit
	 * returns everything (bulk apply).
	 */
	function get_wab_candidates($station_id = null, $dxcc_ids = null, $search = '', $order_col = 1, $order_dir = 'desc', $limit = null, $offset = 0) {
		$bindings=[];
		$sql = "select col_primary_key, col_call, col_time_on, col_band, col_gridsquare, col_sat_name, station_profile.station_profile_name,
			col_qsl_rcvd, col_lotw_qsl_rcvd, col_eqsl_qsl_rcvd,
			COL_QRZCOM_QSO_DOWNLOAD_STATUS as qrz,
			COL_CLUBLOG_QSO_DOWNLOAD_STATUS as clublog
			" . $this->wab_candidates_sql($station_id, $dxcc_ids, $search, $bindings);

		$sortable = array(1 => 'col_time_on', 2 => 'col_call', 3 => 'col_band', 4 => 'col_gridsquare', 6 => 'station_profile.station_profile_name');
		$order_by = $sortable[(int)$order_col] ?? 'col_time_on';
		$order_dir = strtolower((string)$order_dir) === 'asc' ? 'asc' : 'desc';
		// col_primary_key tiebreaker keeps the order stable across pages
		$sql .= " order by " . $order_by . " " . $order_dir . ", col_primary_key " . $order_dir;

		if ($limit !== null) {
			// limit/offset are int-cast inline: CI3 query bindings are escaped
			// as values, so they cannot be bound as placeholders
			$sql .= " limit " . (int)$limit . " offset " . (int)$offset;
		}

		$query = $this->db->query($sql,$bindings);

		return $query;
	}

	/*
	 * WAB tool: number of candidate rows (without/with the search filter),
	 * for the DataTables recordsTotal / recordsFiltered counters
	 */
	function count_wab_candidates($station_id = null, $dxcc_ids = null, $search = '') {
		$bindings=[];
		$sql = "select count(*) as n " . $this->wab_candidates_sql($station_id, $dxcc_ids, $search, $bindings);

		$query = $this->db->query($sql,$bindings);

		return (int)$query->row()->n;
	}

	/*
	 * WAB tool: distinct normalized gridsquares among the candidates, so the
	 * scan summary can be computed per grid instead of per QSO row
	 */
	function get_wab_candidate_grids($station_id = null, $dxcc_ids = null) {
		$bindings=[];
		$sql = "select distinct upper(left(trim(col_gridsquare), 8)) as grid
			" . $this->wab_candidates_sql($station_id, $dxcc_ids, '', $bindings) . "
			order by grid";

		$query = $this->db->query($sql,$bindings);

		$grids = array();
		foreach ($query->result() as $row) {
			$grids[] = $row->grid;
		}
		return $grids;
	}

/*
	 * WAB tool: re-fetch candidate QSOs by primary key. Ownership, the
	 * empty-SIG policy and the valid-DXCC check are re-applied so apply()
	 * can recompute squares server side without trusting anything submitted
	 * by the client.
	 */
	function get_wab_candidates_by_ids($ids, $user_station_ids, $dxcc_ids) {
		$bindings=[];
		$sql = "select col_primary_key, col_gridsquare
			from " . $this->config->item('table_name') . " thcv
			where thcv.station_id in (" . implode(',', array_fill(0, count($user_station_ids), '?')) . ")
			and col_primary_key in (" . implode(',', array_fill(0, count($ids), '?')) . ")
			and length(col_gridsquare) >= 6
			and col_dxcc in (" . implode(',', array_fill(0, count($dxcc_ids), '?')) . ")
			and (coalesce(col_sig, '') = '' or (col_sig = 'WAB' and coalesce(col_sig_info, '') = ''))";
		$bindings = array_merge(array_map('intval', $user_station_ids), array_map('intval', $ids), array_map('intval', $dxcc_ids));

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * WAB tool: write the WAB square into the given QSOs. The empty-SIG
	 * policy is re-checked at write time, so QSOs that gained another SIG
	 * between scan and apply are never touched.
	 */
	function apply_wab_square($square, $ids, $user_station_ids) {
		$bindings = array_merge(
			array('WAB', strtoupper($square)),
			array_map('intval', $ids),
			array_map('intval', $user_station_ids)
		);

		$sql = "update " . $this->config->item('table_name') . " thcv
			set col_sig = ?, col_sig_info = ?
			where col_primary_key in (" . implode(',', array_fill(0, count($ids), '?')) . ")
			and station_id in (" . implode(',', array_fill(0, count($user_station_ids), '?')) . ")
			and (coalesce(col_sig, '') = '' or (col_sig = 'WAB' and coalesce(col_sig_info, '') = ''))";

		$this->db->query($sql, $bindings);

		return $this->db->affected_rows();
	}

}
