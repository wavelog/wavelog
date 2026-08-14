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

}
