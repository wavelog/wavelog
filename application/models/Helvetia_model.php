<?php

class helvetia_model extends CI_Model {

	function __construct() {
		$this->load->library('Genfunctions');
	}

	public $stateString = 'AG,AI,AR,BE,BL,BS,FR,GE,GL,GR,JU,LU,NE,NW,OW,SG,SH,SO,SZ,TG,TI,UR,VD,VS,ZG,ZH';

	function get_helvetia_array($bands, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$stateArray = explode(',', $this->stateString);

		$states = array(); // Used for keeping thelvetiak of which states that are not worked

        	$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);

		foreach ($stateArray as $state) {                   // Generating array for use in the table
			$states[$state]['count'] = 0;                   // Inits each state's count
		}


		foreach ($bands as $band) {
			foreach ($stateArray as $state) {                   // Generating array for use in the table
				$bandhelvetia[$state][$band] = '-';                  // Sets all to dash to indicate no result
			}

			if ($postdata['worked'] != NULL) {
				$helvetiaBand = $this->gethelvetiaWorked($location_list, $band, $postdata);
				foreach ($helvetiaBand as $line) {
					$bandhelvetia[$line->col_state][$band] = '<div class="bg-danger awardsBgDanger"><a href=\'javascript:displayContacts("' . $line->col_state . '","' . $band . '","All","All","'. $postdata['mode'] . '","helvetia", "")\'>W</a></div>';
					$states[$line->col_state]['count']++;
				}
			}
			if ($postdata['confirmed'] != NULL) {
				$helvetiaBand = $this->gethelvetiaConfirmed($location_list, $band, $postdata);
				foreach ($helvetiaBand as $line) {
					$bandhelvetia[$line->col_state][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . $line->col_state . '","' . $band . '","All","All","'. $postdata['mode'] . '","helvetia", "'.$qsl.'")\'>C</a></div>';
					$states[$line->col_state]['count']++;
				}
			}
		}

		// We want to remove the worked states in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$helvetiaBand = $this->gethelvetiaWorked($location_list, $postdata['band'], $postdata);
			foreach ($helvetiaBand as $line) {
				unset($bandhelvetia[$line->col_state]);
			}
		}

		// We want to remove the confirmed states in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$helvetiaBand = $this->gethelvetiaConfirmed($location_list, $postdata['band'], $postdata);
			foreach ($helvetiaBand as $line) {
				unset($bandhelvetia[$line->col_state]);
			}
		}

		if ($postdata['notworked'] == NULL) {
			foreach ($stateArray as $state) {
				if ($states[$state]['count'] == 0) {
					unset($bandhelvetia[$state]);
				};
			}
		}

		if (isset($bandhelvetia)) {
			return $bandhelvetia;
		}
		else {
			return 0;
		}
	}

	/*
	 * Function gets worked and confirmed summary on each band on the active stationprofile
	 */
	function get_helvetia_summary($bands, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		foreach ($bands as $band) {
			$worked = $this->getSummaryByBand($band, $postdata, $location_list);
			$confirmed = $this->getSummaryByBandConfirmed($band, $postdata, $location_list);
			$helvetiaSummary['worked'][$band] = $worked[0]->count;
			$helvetiaSummary['confirmed'][$band] = $confirmed[0]->count;
		}

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $location_list);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $location_list);

		$helvetiaSummary['worked']['Total'] = $workedTotal[0]->count;
		$helvetiaSummary['confirmed']['Total'] = $confirmedTotal[0]->count;

		return $helvetiaSummary;
	}

	function getSummaryByBand($band, $postdata, $location_list) {
		$binding=[];

		$sql = "SELECT count(distinct thcv.col_state) as count FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = 'SAT'";
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('helvetia');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$binding[] = $band;	
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}

		$sql .= $this->addStateToQuery();

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	function getSummaryByBandConfirmed($band, $postdata, $location_list) {
		$binding=[];
		$sql = "SELECT count(distinct thcv.col_state) as count FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode ='SAT'";
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('helvetia');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$binding[] = $band;
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addStateToQuery();

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	/*
	 * Function returns all worked, but not confirmed states
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function gethelvetiaWorked($location_list, $band, $postdata) {
		$binding=[];
		$sql = "SELECT distinct col_state FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}

		$sql .= $this->addStateToQuery();

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= " and not exists (select 1 from ". $this->config->item('table_name') .
			" where station_id in (". $location_list . ")" .
			" and col_state = thcv.col_state";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addStateToQuery();

		$sql .= ")";

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	/*
	 * Function returns all confirmed states on given band and on LoTW or QSL
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function gethelvetiaConfirmed($location_list, $band, $postdata) {
		$binding=[];
		$sql = "SELECT distinct col_state FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}

		$sql .= $this->addStateToQuery();

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	function addStateToQuery() {
		$sql = '';
		$sql .= " and COL_DXCC = 287";
		$sql .= " and COL_STATE in ('AG','AI','AR','BE','BL','BS','FR','GE','GL','GR','JU','LU','NE','NW','OW','SG','SH','SO','SZ','TG','TI','UR','VD','VS','ZG','ZH')";
		return $sql;
	}
}
?>
