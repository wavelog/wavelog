<?php

class WAPC extends CI_Model {

	private $location_list=null;
	function __construct() {
		$this->load->library('Genfunctions');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->location_list = "'".implode("','",$logbooks_locations_array)."'";
	}

	// Province Code => Name
	public $cnProvinces = array(
		'BJ' => 'Beijing',
		'HL' => 'Heilongjiang',
		'LN' => 'Liaoning',
		'JL' => 'Jilin',
		'HE' => 'Hebei',
		'TJ' => 'Tianjin',
		'NM' => 'Nei Mongol',
		'SX' => 'Shanxi',
		'SH' => 'Shanghai',
		'SD' => 'Shandong',
		'JS' => 'Jiangsu',
		'ZJ' => 'Zhejiang',
		'JX' => 'Jiangxi',
		'FJ' => 'Fujian',
		'AH' => 'Anhui',
		'HA' => 'Henan',
		'HB' => 'Hubei',
		'HN' => 'Hunan',
		'GD' => 'Guangdong',
		'GX' => 'Guangxi',
		'HI' => 'Hainan',
		'SC' => 'Sichuan',
		'CQ' => 'Chongqing',
		'GZ' => 'Guizhou',
		'YN' => 'Yunnan',
		'SN' => 'Shaanxi',
		'GS' => 'Gansu',
		'NX' => 'Ningxia',
		'QH' => 'Qinghai',
		'XJ' => 'Xinjiang',
		'XZ' => 'Xizang',
		'TW' => 'Taiwan',
		'HK' => 'Hong Kong',
		'MO' => 'Macau');

	function get_wapc_array($bands, $postdata) {

		$wapcArray = array_keys($this->cnProvinces);

		$provinces = array(); // Used for keeping track of which states that are not worked
		foreach ($wapcArray as $state) {                         // Generating array for use in the table
			$provinces[$state]['count'] = 0;                   // Inits each state's count
		}

		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);


		foreach ($bands as $band) {
			foreach ($wapcArray as $state) {                   // Generating array for use in the table
				$bandWapc[$state]['Number'] = $state;
				$bandWapc[$state]['Province'] = $this->cnProvinces[$state];
				$bandWapc[$state][$band] = '-';                  // Sets all to dash to indicate no result
			}

			if ($postdata['worked'] != NULL) {
				$wapcBand = $this->getWapcWorked($this->location_list, $band, $postdata);
				foreach ($wapcBand as $line) {
					// B
					if($line->col_dxcc == '318'){
						$bandWapc[$line->col_state][$band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . $line->col_state . '","' . $band . '","All","All","'. $postdata['mode'] . '","WAPC", "")\'>W</a></div>';
						$provinces[$line->col_state]['count']++;
					}
					// BS7
					else if($line->col_dxcc == '506'){
						$bandWapc['HI'][$band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("HI","' . $band . '","All","All","'. $postdata['mode'] . '","WAPC", "")\'>W</a></div>';
						$provinces['HI']['count']++;
					}
					// VR
					else if($line->col_dxcc == '321'){
						$bandWapc['HK'][$band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . "321" . '","' . $band . '","All","All","'. $postdata['mode'] . '","DXCC2", "")\'>W</a></div>';
						$provinces['HK']['count']++;
					}
					// XX9
					else if($line->col_dxcc == '152'){
						$bandWapc['MO'][$band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . "152" . '","' . $band . '","All","All","'. $postdata['mode'] . '","DXCC2", "")\'>W</a></div>';
						$provinces['MO']['count']++;
					}
					// BU-BX/BV9P
					else if($line->col_dxcc == '386' || $line->col_dxcc == '505'){
						$bandWapc['TW'][$band] = '<div class="bg-danger awardsBgWarning"><a href=\'javascript:displayContacts("' . "386" . '","' . $band . '","All","All","'. $postdata['mode'] . '","DXCC2", "")\'>W</a></div>';
						$provinces['TW']['count']++;
					}
				}
			}
			if ($postdata['confirmed'] != NULL) {
				$wapcBand = $this->getWapcConfirmed($this->location_list, $band, $postdata);
				foreach ($wapcBand as $line) {
					// B
					if($line->col_dxcc === '318'){
						$bandWapc[$line->col_state][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . $line->col_state . '","' . $band . '","All","All","'. $postdata['mode'] . '","WAPC", "'.$qsl.'")\'>C</a></div>';
						$provinces[$line->col_state]['count']++;
					}
					// BS7
					else if($line->col_dxcc === '506'){
						$bandWapc['HI'][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("HI","' . $band . '","All","All","'. $postdata['mode'] . '","WAPC", "'.$qsl.'")\'>C</a></div>';
						$provinces['HI']['count']++;
					}
					// VR
					else if($line->col_dxcc == '321'){
						$bandWapc['HK'][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . "321" . '","' . $band . '","All","All","'. $postdata['mode'] . '","DXCC2", "'.$qsl.'")\'>C</a></div>';
						$provinces['HK']['count']++;
					}
					// XX9
					else if($line->col_dxcc == '152'){
						$bandWapc['MO'][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . "152" . '","' . $band . '","All","All","'. $postdata['mode'] . '","DXCC2", "'.$qsl.'")\'>C</a></div>';
						$provinces['MO']['count']++;
					}
					// BU-BX/BV9P
					else if($line->col_dxcc == '386' || $line->col_dxcc == '505'){
						$bandWapc['TW'][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . "386" . '","' . $band . '","All","All","'. $postdata['mode'] . '","DXCC2", "'.$qsl.'")\'>C</a></div>';
						$provinces['MO']['count']++;
					}
				}
			}
		}

		// We want to remove the worked states in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$wapcBand = $this->getWapcWorked($this->location_list, $postdata['band'], $postdata);
			foreach ($wapcBand as $line) {
				unset($bandWapc[$line->col_state]);
			}
		}

		// We want to remove the confirmed states in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$wapcBand = $this->getWapcConfirmed($this->location_list, $postdata['band'], $postdata);
			foreach ($wapcBand as $line) {
				unset($bandWapc[$line->col_state]);
			}
		}

		if ($postdata['notworked'] == NULL) {
			foreach ($wapcArray as $state) {
				if ($provinces[$state]['count'] == 0) {
					unset($bandWapc[$state]);
				};
			}
		}

		if (isset($bandWapc)) {
			return $bandWapc;
		} else {
			return 0;
		}
	}

	function getWapcBandConfirmed($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "select adif as wapc, name from dxcc_entities
			join (
				select col_dxcc from ".$this->config->item('table_name')." thcv
				where station_id in (" . $location_list .
				") and col_dxcc > 0";

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getWapcBandWorked($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "select adif as wapc, name from dxcc_entities
			join (
				select col_dxcc from ".$this->config->item('table_name')." thcv
				where station_id in (" . $location_list .
				") and col_dxcc > 0";

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";;

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * Function returns all worked, but not confirmed states
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getWapcWorked($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "SELECT distinct col_state, col_dxcc FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addStateToQuery();
		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);

		$sql .= " and not exists (select 1 from ". $this->config->item('table_name') .
			" where station_id in (". $location_list . ")" .
			" and (col_state = thcv.col_state and col_dxcc = thcv.col_dxcc)";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= $this->addStateToQuery();
		$sql .= ")";
		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * Function returns all confirmed states on given band and on LoTW or QSL
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getWapcConfirmed($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "SELECT distinct col_state, col_dxcc FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addStateToQuery();
		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * Function gets worked and confirmed summary on each band on the active stationprofile
	 */
	function get_wapc_summary($bands, $postdata) {
		foreach ($bands as $band) {
			$worked = $this->getSummaryByBand($band, $postdata, $this->location_list);
			$confirmed = $this->getSummaryByBandConfirmed($band, $postdata, $this->location_list);
			$wapcSummary['worked'][$band] = $worked[0]->count;
			$wapcSummary['confirmed'][$band] = $confirmed[0]->count;
		}

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $this->location_list);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $this->location_list);

		$wapcSummary['worked']['Total'] = $workedTotal[0]->count;
		$wapcSummary['confirmed']['Total'] = $confirmedTotal[0]->count;

		return $wapcSummary;
	}

	function getSummaryByBand($band, $postdata, $location_list) {
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_state, thcv.col_dxcc) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('wapc');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")";
			$sql .= " and thcv.col_prop_mode !='SAT'";
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

		$sql .= $this->addStateToQuery();

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getSummaryByBandConfirmed($band, $postdata, $location_list) {
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_state, thcv.col_dxcc) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$bindings[]=$band;
		} else if ($band == 'All') {
			$this->load->model('bands');
			$bandslots = $this->bands->get_worked_bands('wapc');
			$bandslots_list = "'".implode("','",$bandslots)."'";
			$sql .= " and thcv.col_band in (" . $bandslots_list . ")";
			$sql .= " and thcv.col_prop_mode !='SAT'";
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
		$sql .= $this->addStateToQuery();
		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}


	function addStateToQuery() {
		$sql = '';
		$sql .= " and ((COL_DXCC in ('318') and COL_STATE in ('".implode("','", array_keys($this->cnProvinces))."')) OR (COL_DXCC in ('321','152','386','505','506')))";
		return $sql;
	}
}
?>
