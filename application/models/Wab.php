<?php

class Wab extends CI_Model {

	function __construct() {
		$this->load->library('Genfunctions');
	}

    function get_wab_array($band, $location_list, $mode, $qsl, $lotw, $eqsl, $qrz, $sat, $orbit) {
		$worked = array();
		$confirmed = array();

		$worked = $this->getWabWorked($location_list, $band, $mode, $sat, $orbit);

		$confirmed = $this->getWabConfirmed($location_list, $band, $mode, $qsl, $lotw, $eqsl, $qrz, $sat, $orbit);

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
    function getWabWorked($location_list, $band, $mode, $sat, $orbit) {
        $sql = "SELECT distinct col_sig_info FROM " . $this->config->item('table_name') . " thcv
        where station_id in (" . $location_list . ") and col_sig = 'WAB' and coalesce(col_sig_info, '') <> ''";

		$sql .= $this->genfunctions->addBandToQuery($band);

		if ($band == 'SAT') {
			if ($sat != 'All') {
				$sql .= " and col_sat_name ='" . $sat . "'";
			}
		}

		if ($mode != 'All') {
			$sql .= " and (col_mode = '" . $mode . "' or col_submode = '" . $mode . "')";
		}

		$sql .= $this->addOrbitToQuery($orbit);

        $query = $this->db->query($sql);

        return $query->result();
    }

    /*
     * Function returns all confirmed states on given band and on LoTW or QSL
     * $postdata contains data from the form, in this case Lotw or QSL are used
     */
    function getWabConfirmed($location_list, $band, $mode, $qsl, $lotw, $eqsl, $qrz, $sat, $orbit) {
        $sql = "SELECT distinct col_sig_info FROM " . $this->config->item('table_name') . " thcv
            where station_id in (" . $location_list . ") and col_sig = 'WAB' and coalesce(col_sig_info, '') <> ''";

		$sql .= $this->genfunctions->addBandToQuery($band);

		if ($band == 'SAT') {
			if ($sat != 'All') {
				$sql .= " and col_sat_name ='" . $sat . "'";
			}
		}

		if ($mode != 'All') {
			$sql .= " and (col_mode = '" . $mode . "' or col_submode = '" . $mode . "')";
		}

		$sql .= $this->addOrbitToQuery($orbit);

        $sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $qrz);

        $query = $this->db->query($sql);

        return $query->result();
    }

    function addQslToQuery($qsl, $lotw, $eqsl, $qrz) {
		$sql = '';
		$qslarray = array();
		if ($qrz != NULL || $lotw != NULL || $qsl != NULL || $eqsl != NULL) {
			$sql .= ' and (';
			if ($qsl != NULL) {
				array_push($qslarray, "col_qsl_rcvd = 'Y'");
			}
			if ($lotw != NULL) {
				array_push($qslarray, "col_lotw_qsl_rcvd = 'Y'");
			}
			if ($eqsl != NULL) {
				array_push($qslarray, "col_eqsl_qsl_rcvd = 'Y'");
			}
			if ($qrz != NULL) {
				array_push($qslarray, "COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y'");
			}
			if (count($qslarray) > 0) {
				$sql .= implode(' or ', $qslarray);
			} else {
				$sql .= '1=0';
			}
			$sql .= ')';
		} else {
			$sql.=' and 1=0';
		}
		return $sql;
	}

	// Adds orbit type to query
	function addOrbitToQuery($orbit) {
		$sql = '';
		if ($orbit != 'All') {
			$sql .= ' AND satellite.orbit = \''.$orbit.'\'';
		}

		return $sql;
	}

}
