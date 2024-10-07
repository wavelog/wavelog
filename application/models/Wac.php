<?php

class Wac extends CI_Model{

	private $validContinents = ['AF', 'EU', 'AS', 'SA', 'NA', 'OC', 'AN'];

	function __construct() {
		$this->load->library('Genfunctions');
	}

	function get_wac_array($bands, $postdata, $location_list) {
		$wac = array();

		foreach ($this->validContinents as $cont) {
			$wac[$cont]['count'] = 0;                   // Inits each wac's count
		}

		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);

		foreach ($bands as $band) {
			foreach ($this->validContinents as $cont) {
				$bandWac[$cont][$band] = '-';                  // Sets all to dash to indicate no result
			}

			if ($postdata['worked'] != NULL) {
				$wacBand = $this->getWACWorked($location_list, $band, $postdata);
				foreach ($wacBand as $line) {
					$bandWac[$line->col_cont][$band] = '<div class="bg-danger awardsBgDanger"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $line->col_cont) . '","' . $band . '","All", "All","'. $postdata['mode'] . '","WAC","")\'>W</a></div>';
					$wac[$line->col_cont]['count']++;
				}
			}
			if ($postdata['confirmed'] != NULL) {
				$wacBand = $this->getWACConfirmed($location_list, $band, $postdata);
				foreach ($wacBand as $line) {
					$bandWac[$line->col_cont][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . str_replace("&", "%26", $line->col_cont) . '","' . $band . '","All", "All","'. $postdata['mode'] . '","WAC","'.$qsl.'")\'>C</a></div>';
					$wac[$line->col_cont]['count']++;
				}
			}
		}

		// We want to remove the worked continents in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$wacBand = $this->getWACWorked($location_list, $postdata['band'], $postdata);
			foreach ($wacBand as $line) {
				unset($bandWac[$line->col_cont]);
			}
		}

		// We want to remove the confirmed continents in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$wacBand = $this->getWACConfirmed($location_list, $postdata['band'], $postdata);
			foreach ($wacBand as $line) {
				unset($bandWac[$line->col_cont]);
			}
		}

		if ($postdata['notworked'] == NULL) {
			foreach ($this->validContinents as $cont) {
				if ($wac[$cont]['count'] == 0) {
					unset($bandWac[$cont]);
				};
			}
		}

		if (isset($bandWac)) {
			return $bandWac;
		} else {
			return 0;
		}
	}

	/*
	 * Function returns all worked, but not confirmed continents
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getWACWorked($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "SELECT distinct col_cont FROM " . $this->config->item('table_name') . " thcv
			LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
			where station_id in (" . $location_list . ") and col_cont in ('AF', 'EU', 'AS', 'SA', 'NA', 'OC', 'AN')";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		if ($band == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		}
		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= " and not exists (select 1 from " . $this->config->item('table_name') . " thcv2
			LEFT JOIN satellite on thcv2.COL_SAT_NAME = satellite.name
			where station_id in (" . $location_list .
			") and col_cont = thcv.col_cont and col_cont <> '' ";

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		if ($band == 'SAT') {
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

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= ")";

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * Function returns all confirmed continents on given band and on LoTW or QSL
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getWACConfirmed($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "SELECT distinct col_cont FROM " . $this->config->item('table_name') . " thcv
			LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
			where station_id in (" . $location_list . ") and col_cont in ('AF', 'EU', 'AS', 'SA', 'NA', 'OC', 'AN')";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		if ($band == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}


	/*
	 * Function gets worked and confirmed summary on each band on the active stationprofile
	 */
	function get_wac_summary($bands, $postdata, $location_list) {
		foreach ($bands as $band) {
			$worked = $this->getSummaryByBand($band, $postdata, $location_list);
			$confirmed = $this->getSummaryByBandConfirmed($band, $postdata, $location_list);
			$wacSummary['worked'][$band] = $worked[0]->count;
			$wacSummary['confirmed'][$band] = $confirmed[0]->count;
		}

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $location_list);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $location_list);

		$wacSummary['worked']['Total'] = $workedTotal[0]->count;
		$wacSummary['confirmed']['Total'] = $confirmedTotal[0]->count;

		return $wacSummary;
	}

	function getSummaryByBand($band, $postdata, $location_list) {
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_cont) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name";

		$sql .= " where station_id in (" . $location_list . ") and col_cont in ('AF', 'EU', 'AS', 'SA', 'NA', 'OC', 'AN')";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$bindings[]=$band;
			if ($band != 'All' && $postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands();

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$bindings[]=$band;
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}

	function getSummaryByBandConfirmed($band, $postdata, $location_list){
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_cont) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name";

		$sql .= " where station_id in (" . $location_list . ") and col_cont in ('AF', 'EU', 'AS', 'SA', 'NA', 'OC', 'AN')";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode ='" . $band . "'";
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands();

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$bindings[]=$band;
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$query = $this->db->query($sql,$bindings);

		return $query->result();
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

}
