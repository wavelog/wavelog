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

	function get_wab_list($location_list, $postdata) {
		$wabarray = array();

		$workedGridArray = array();
		$worked = $this->getWabWorked($location_list, $postdata);
		foreach ($worked as $w) {
			array_push($workedGridArray, $w->col_sig_info);
			$wabarray += array(
				$w->col_sig_info => 'W'
			);
		}

		// Confirmed squares carry the confirming QSL systems as letters
		// (Q=QSL, L=LoTW, E=eQSL, Z=QRZ, C=Clublog) instead of just 'C'
		$confirmedGridArray = array();
		$confirmed = $this->getWabConfirmedSystems($location_list, $postdata);
		foreach ($confirmed as $c) {
			array_push($confirmedGridArray, $c->col_sig_info);

			$letters = '';
			if ($c->qsl) { $letters .= 'Q'; }
			if ($c->lotw) { $letters .= 'L'; }
			if ($c->eqsl) { $letters .= 'E'; }
			if ($c->qrz) { $letters .= 'Z'; }
			if ($c->clublog) { $letters .= 'C'; }

			if(array_key_exists($c->col_sig_info, $wabarray)){
				$wabarray[$c->col_sig_info] = $letters;
			} else {
				$wabarray += array(
					$c->col_sig_info => $letters
				);
			}
		}

		ksort($wabarray);

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
	 * Function returns the worked squares with a flag per QSL system that
	 * confirmed them (Q=QSL, L=LoTW, E=eQSL, Z=QRZ, C=Clublog), so the WAB
	 * list can show how each square is confirmed. Applies the same filters
	 * as getWabConfirmed().
	 */
	function getWabConfirmedSystems($location_list, $postdata) {
		$bindings=[];
		$sql = "select col_sig_info,
				max(col_qsl_rcvd = 'Y') as qsl,
				max(col_lotw_qsl_rcvd = 'Y') as lotw,
				max(col_eqsl_qsl_rcvd = 'Y') as eqsl,
				max(COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y') as qrz,
				max(COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y') as clublog
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

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= " group by col_sig_info";

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


}
