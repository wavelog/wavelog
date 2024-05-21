<?php

class WAJA extends CI_Model {

	function __construct() {
		$this->load->library('Genfunctions');
	}

	public $jaPrefectures = array(
		'01' => 'Hokkaido',
		'02' => 'Aomori',
		'03' => 'Iwate',
		'04' => 'Akita',
		'05' => 'Yamagata',
		'06' => 'Miyagi',
		'07' => 'Fukushima',
		'08' => 'Niigata',
		'09' => 'Nagano',
		'10' => 'Tokyo',
		'11' => 'Kanagawa',
		'12' => 'Chiba',
		'13' => 'Saitama',
		'14' => 'Ibaraki',
		'15' => 'Tochigi',
		'16' => 'Gunma',
		'17' => 'Yamanashi',
		'18' => 'Shizuoka',
		'19' => 'Gifu',
		'20' => 'Aichi',
		'21' => 'Mie',
		'22' => 'Kyoto',
		'23' => 'Shiga',
		'24' => 'Nara',
		'25' => 'Osaka',
		'26' => 'Wakayama',
		'27' => 'Hyogo',
		'28' => 'Toyama',
		'29' => 'Fukui',
		'30' => 'Ishikawa',
		'31' => 'Okayama',
		'32' => 'Shimane',
		'33' => 'Yamaguchi',
		'34' => 'Tottori',
		'35' => 'Hiroshima',
		'36' => 'Kagawa',
		'37' => 'Tokushima',
		'38' => 'Ehime',
		'39' => 'Kochi',
		'40' => 'Fukuoka',
		'41' => 'Saga',
		'42' => 'Nagasaki',
		'43' => 'Kumamoto',
		'44' => 'Oita',
		'45' => 'Miyazaki',
		'46' => 'Kagoshima',
		'47' => 'Okinawa');

	function get_waja_array($bands, $postdata) {
		$CI =& get_instance();
		$CI->load->model('logbooks_model');
		$logbooks_locations_array = $CI->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$wajaArray = array_keys($this->jaPrefectures);

		$prefectures = array(); // Used for keeping track of which states that are not worked
		foreach ($wajaArray as $state) {                         // Generating array for use in the table
			$prefectures[$state]['count'] = 0;                   // Inits each state's count
		}

        	$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);


		foreach ($bands as $band) {
			foreach ($wajaArray as $state) {                   // Generating array for use in the table
				$bandWaja[$state]['Number'] = $state;
				$bandWaja[$state]['Prefecture'] = $this->jaPrefectures[$state];
				$bandWaja[$state][$band] = '-';                  // Sets all to dash to indicate no result
			}

			if ($postdata['worked'] != NULL) {
				$wajaBand = $this->getWajaWorked($location_list, $band, $postdata);
				foreach ($wajaBand as $line) {
					$bandWaja[$line->col_state][$band] = '<div class="bg-danger awardsBgDanger"><a href=\'javascript:displayContacts("' . $line->col_state . '","' . $band . '","All","All","'. $postdata['mode'] . '","WAJA", "")\'>W</a></div>';
					$prefectures[$line->col_state]['count']++;
				}
			}
			if ($postdata['confirmed'] != NULL) {
				$wajaBand = $this->getWajaConfirmed($location_list, $band, $postdata);
				foreach ($wajaBand as $line) {
					$bandWaja[$line->col_state][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . $line->col_state . '","' . $band . '","All","All","'. $postdata['mode'] . '","WAJA", "'.$qsl.'")\'>C</a></div>';
					$prefectures[$line->col_state]['count']++;
				}
			}
		}

		// We want to remove the worked states in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$wajaBand = $this->getWajaWorked($location_list, $postdata['band'], $postdata);
			foreach ($wajaBand as $line) {
				unset($bandWaja[$line->col_state]);
			}
		}

		// We want to remove the confirmed states in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$wasBand = $this->getWajaConfirmed($location_list, $postdata['band'], $postdata);
			foreach ($wasBand as $line) {
				unset($bandWaja[$line->col_state]);
			}
		}

		if ($postdata['notworked'] == NULL) {
			foreach ($wajaArray as $state) {
				if ($prefectures[$state]['count'] == 0) {
					unset($bandWaja[$state]);
				};
			}
		}

		if (isset($bandWaja)) {
			return $bandWaja;
		}
		else {
			return 0;
		}
	}

	function getWajaBandConfirmed($location_list, $band, $postdata) {
		$sql = "select adif as waja, name from dxcc_entities
			join (
				select col_dxcc from ".$this->config->item('table_name')." thcv
				where station_id in (" . $location_list .
				") and col_dxcc > 0";

		$sql .= $this->genfunctions->addBandToQuery($band);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$query = $this->db->query($sql);

		return $query->result();
	}

	function getWajaBandWorked($location_list, $band, $postdata) {
		$sql = "select adif as waja, name from dxcc_entities
			join (
				select col_dxcc from ".$this->config->item('table_name')." thcv
				where station_id in (" . $location_list .
				") and col_dxcc > 0";

		$sql .= $this->genfunctions->addBandToQuery($band);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";;

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$query = $this->db->query($sql);

		return $query->result();
	}

	/*
	 * Function returns all worked, but not confirmed states
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getWajaWorked($location_list, $band, $postdata) {
		$sql = "SELECT distinct LPAD(col_state, 2, '0') AS col_state FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->addStateToQuery();

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= " and not exists (select 1 from ". $this->config->item('table_name') .
			" where station_id in (". $location_list . ")" .
			" and col_state = thcv.col_state";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addStateToQuery();

		$sql .= ")";

		$query = $this->db->query($sql);

		return $query->result();
	}

	/*
	 * Function returns all confirmed states on given band and on LoTW or QSL
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getWajaConfirmed($location_list, $band, $postdata) {
		$sql = "SELECT distinct LPAD(col_state, 2, '0') AS col_state FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->addStateToQuery();

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$query = $this->db->query($sql);

		return $query->result();
	}

	
	/*
	 * Function gets worked and confirmed summary on each band on the active stationprofile
	 */
	function get_waja_summary($bands, $postdata)
	{
		$CI =& get_instance();
		$CI->load->model('logbooks_model');
		$logbooks_locations_array = $CI->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		foreach ($bands as $band) {
			$worked = $this->getSummaryByBand($band, $postdata, $location_list);
			$confirmed = $this->getSummaryByBandConfirmed($band, $postdata, $location_list);
			$wajaSummary['worked'][$band] = $worked[0]->count;
			$wajaSummary['confirmed'][$band] = $confirmed[0]->count;
		}

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $location_list);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $location_list);

		$wajaSummary['worked']['Total'] = $workedTotal[0]->count;
		$wajaSummary['confirmed']['Total'] = $confirmedTotal[0]->count;

		return $wajaSummary;
	}

	function getSummaryByBand($band, $postdata, $location_list)
	{
		$sql = "SELECT count(distinct thcv.col_state) as count FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode ='" . $band . "'";
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('was');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band ='" . $band . "'";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->addStateToQuery();

		$query = $this->db->query($sql);

		return $query->result();
	}

	function getSummaryByBandConfirmed($band, $postdata, $location_list)
	{
		$sql = "SELECT count(distinct thcv.col_state) as count FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode ='" . $band . "'";
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('was');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band ='" . $band . "'";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addStateToQuery();

		$query = $this->db->query($sql);

		return $query->result();
	}


	function addStateToQuery() {
		$sql = '';
		$sql .= " and COL_DXCC in ('339')";
		$sql .= " and COL_STATE in (".implode(',', array_keys($this->jaPrefectures)).")";
		return $sql;
	}
}
?>
