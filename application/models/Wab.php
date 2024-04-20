<?php

class Wab extends CI_Model{

    function get_wab_array($band, $location_list) {
		$worked = array();
		$confirmed = array();

		$worked = $this->getWabWorked($location_list, $band);

		$confirmed = $this->getWabConfirmed($location_list, $band);

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
    function getWabWorked($location_list, $band) {
        $sql = "SELECT distinct col_sig_info FROM " . $this->config->item('table_name') . " thcv
        where station_id in (" . $location_list . ") and col_sig = 'WAB' and coalesce(col_sig_info, '') <> ''";

        $query = $this->db->query($sql);

        return $query->result();
    }

    /*
     * Function returns all confirmed states on given band and on LoTW or QSL
     * $postdata contains data from the form, in this case Lotw or QSL are used
     */
    function getWabConfirmed($location_list, $bands) {
        $sql = "SELECT distinct col_sig_info FROM " . $this->config->item('table_name') . " thcv
            where station_id in (" . $location_list . ") and col_sig = 'WAB' and coalesce(col_sig_info, '') <> ''";

        $sql .= $this->addQslToQuery();

        $query = $this->db->query($sql);

        return $query->result();
    }

    function addQslToQuery() {
        $sql = 'and (';
        $qsl = array();
		array_push($qsl, "col_qsl_rcvd = 'Y'");
		array_push($qsl, "col_lotw_qsl_rcvd = 'Y'");
		//array_push($qsl, "col_eqsl_qsl_rcvd = 'Y'");
		$sql .= implode(' or ', $qsl);
		$sql .= ')';
        return $sql;
    }



}
