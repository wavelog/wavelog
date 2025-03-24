<?php

class IOTA extends CI_Model {
	function __construct() {
		$this->load->library('Genfunctions');
	}

	function get_iota_array($iotaArray, $bands, $postdata, $location_list) {
		foreach ($bands as $band) {             	// Looping through bands and iota to generate the array needed for display
			if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
				continue;
			}
			foreach ($iotaArray as $iota) {
				$iotaMatrix[$iota->tag]['prefix'] = $iota->prefix;
				$iotaMatrix[$iota->tag]['name'] = $iota->name;
				if ($postdata['includedeleted'])
					$iotaMatrix[$iota->tag]['Deleted'] = isset($iota->status) && $iota->status == 'D' ? "<div class='alert-danger'>Y</div>" : '';
				$iotaMatrix[$iota->tag][$band] = '-';
			}

			// If worked is checked, we add worked iotas to the array
			if ($postdata['worked'] != NULL) {
				$workedIota = $this->getIotaBandWorked($location_list, $band, $postdata);
				foreach ($workedIota as $wiota) {
					$iotaMatrix[$wiota->tag][$band] = '<div class="bg-danger awardsBgDanger"><a href=\'javascript:displayContacts("'.$wiota->tag.'","'. $band . '","All","All","'. $postdata['mode'] . '","IOTA")\'>W</a></div>';
				}
			}

			// If confirmed is checked, we add confirmed iotas to the array
			if ($postdata['confirmed'] != NULL) {
				$confirmedIota = $this->getIotaBandConfirmed($location_list, $band, $postdata);
				foreach ($confirmedIota as $ciota) {
					$iotaMatrix[$ciota->tag][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("'.$ciota->tag.'","'. $band . '","All","All","'. $postdata['mode'] . '","IOTA")\'>C</a></div>';
				}
			}
		}

		// We want to remove the worked iotas in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$workedIota = $this->getIotaWorked($location_list, $postdata);
			foreach ($workedIota as $wiota) {
				if (array_key_exists($wiota->tag, $iotaMatrix)) {
					unset($iotaMatrix[$wiota->tag]);
				}
			}
		}

		// We want to remove the confirmed iotas in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$confirmedIOTA = $this->getIotaConfirmed($location_list, $postdata);
			foreach ($confirmedIOTA as $ciota) {
				if (array_key_exists($ciota->tag, $iotaMatrix)) {
					unset($iotaMatrix[$ciota->tag]);
				}
			}
		}

		if (isset($iotaMatrix)) {
			return $iotaMatrix;
		} else {
			return 0;
		}
	}

	function getIotaBandConfirmed($location_list, $band, $postdata) {
		$binding = [];

		$sql = "SELECT distinct UPPER(col_iota) as tag FROM " . $this->config->item('table_name') . " thcv
			join iota on thcv.col_iota = iota.tag
			where station_id in (" . $location_list . ") and thcv.col_iota is not null";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}
		if ($band == 'SAT') {
			$sql .= " and col_prop_mode='SAT'";
		} else {
			$sql.=" and (col_prop_mode!='SAT' or col_prop_mode is null)";
		}

		$sql .= $this->genfunctions->addBandToQuery($band,$binding);

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and coalesce(iota.status, '') <> 'D'";
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= $this->addContinentsToQuery($postdata);

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	function getIotaBandWorked($location_list, $band, $postdata) {
		$binding = [];

		$sql = 'SELECT distinct UPPER(col_iota) as tag FROM ' . $this->config->item('table_name'). ' thcv
			join iota on thcv.col_iota = iota.tag
			where station_id in (' . $location_list .
			') and thcv.col_iota is not null';

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}
		if ($band == 'SAT') {
			$sql .= " and col_prop_mode='SAT'";
		} else {
			$sql.=" and (col_prop_mode!='SAT' or col_prop_mode is null)";
		}

		$sql .= $this->genfunctions->addBandToQuery($band,$binding);

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and coalesce(iota.status, '') <> 'D'";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	function fetchIota($postdata, $location_list) {
		$binding = [];

		$sql = "select tag, name, prefix, dxccid, status, lat1, lat2, lon1, lon2 from iota where 1=1";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and coalesce(iota.status, '') <> 'D'";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		if ($postdata['notworked'] == NULL) {
			$sql .= " and exists (select 1 from " . $this->config->item('table_name') . " where station_id in (". $location_list . ") and col_iota = iota.tag";

			if ($postdata['mode'] != 'All') {
				$sql .= " and (col_mode = ? or col_submode = ?)";
				$binding[] = $postdata['mode'];
				$binding[] = $postdata['mode'];
			}

			if ($postdata['band'] != 'All') {
				if ($postdata['band'] == 'SAT') {
					$sql .= " and col_prop_mode = ?";
					$binding[] = $postdata['band'];
				} else {
					$sql .= " and col_prop_mode !='SAT'";
					$sql .= " and col_band = ?";
					$binding[] = $postdata['band'];
				}
			} else {
				$sql.=" and (col_prop_mode != 'SAT' or col_prop_mode is null)";
			}
			$sql .= ")";
		}

		$sql .= ' order by tag';
		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	function getIotaWorked($location_list, $postdata) {
		$binding = [];

		$sql = "SELECT distinct UPPER(col_iota) as tag FROM " . $this->config->item('table_name') . " thcv
			join iota on thcv.col_iota = iota.tag
			where station_id in (" . $location_list . ") and thcv.col_iota is not null
			and not exists (select 1 from ". $this->config->item('table_name') . " where station_id in (". $location_list . ") and col_iota = thcv.col_iota)";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}
		if ($postdata['band'] == 'SAT') {
			$sql .= " and col_prop_mode='SAT'";
		} else {
			$sql.=" and (col_prop_mode!='SAT' or col_prop_mode is null)";
		}

		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$binding);

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and coalesce(iota.status, '') <> 'D'";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	function getIotaConfirmed($location_list, $postdata) {
		$binding = [];

		$sql = "SELECT distinct UPPER(col_iota) as tag FROM " . $this->config->item('table_name') . " thcv
			join iota on thcv.col_iota = iota.tag
			where station_id in (" . $location_list . ") and thcv.col_iota is not null";
		$sql .= $this->genfunctions->addQslToQuery($postdata);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}
		if ($postdata['band'] == 'SAT') {
			$sql .= " and col_prop_mode='SAT'";
		} else {
			$sql.=" and (col_prop_mode!='SAT' or col_prop_mode is null)";
		}

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and coalesce(iota.status, '') <> 'D'";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$binding);
		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	// Made function instead of repeating this several times
	function addContinentsToQuery($postdata) {
		$sql = '';
		if ($postdata['Africa'] == NULL) {
			$sql .= " and left(tag, 2) <> 'AF'";
		}

		if ($postdata['Europe'] == NULL) {
			$sql .= " and left(tag, 2) <> 'EU'";
		}

		if ($postdata['Asia'] == NULL) {
			$sql .= " and left(tag, 2) <> 'AS'";
		}

		if ($postdata['SouthAmerica'] == NULL) {
			$sql .= " and left(tag, 2) <> 'SA'";
		}

		if ($postdata['NorthAmerica'] == NULL) {
			$sql .= " and left(tag, 2) <> 'NA'";
		}

		if ($postdata['Oceania'] == NULL) {
			$sql .= " and left(tag, 2) <> 'OC'";
		}

		if ($postdata['Antarctica'] == NULL) {
			$sql .= " and left(tag, 2) <> 'AN'";
		}
		return $sql;
	}

	/*
	 * Function gets worked and confirmed summary on each band on the active stationprofile
	 */
	function get_iota_summary($bands, $postdata, $location_list) {
		foreach ($bands as $band) {
			$worked = $this->getSummaryByBand($band, $postdata, $location_list);
			$confirmed = $this->getSummaryByBandConfirmed($band, $postdata, $location_list);
			$iotaSummary['worked'][$band] = $worked[0]->count;
			$iotaSummary['confirmed'][$band] = $confirmed[0]->count;
		}

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $location_list);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $location_list);

		$iotaSummary['worked']['Total'] = $workedTotal[0]->count;
		$iotaSummary['confirmed']['Total'] = $confirmedTotal[0]->count;

		return $iotaSummary;
	}

	function getSummaryByBand($band, $postdata, $location_list) {
		$binding = [];

		$sql = "SELECT count(distinct UPPER(thcv.col_iota)) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= ' join iota on thcv.col_iota = iota.tag';
		$sql .= " where station_id in (" . $location_list . ")";
		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$binding[] = $band;
		} else if ($band == 'All') {
			$this->load->model('bands');
			$bandslots = $this->bands->get_worked_bands('iota');
			$bandslots_list = "'".implode("','",$bandslots)."'";
			$sql .= " and thcv.col_band in (" . $bandslots_list . ")";
			$sql .= " and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$binding[] = $band;
		}
		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and coalesce(iota.status, '') <> 'D'";
		}
		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}
		$sql .= $this->addContinentsToQuery($postdata);
		$query = $this->db->query($sql, $binding);
		return $query->result();
	}

	function getSummaryByBandConfirmed($band, $postdata, $location_list) {
		$binding = [];

		$sql = "SELECT count(distinct thcv.col_iota) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= ' join iota on thcv.col_iota = iota.tag';
		$sql .= " where station_id in (" . $location_list . ")";
		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$binding[] = $band;
		} else if ($band == 'All') {
			$this->load->model('bands');
			$bandslots = $this->bands->get_worked_bands('iota');
			$bandslots_list = "'".implode("','",$bandslots)."'";
			$sql .= " and thcv.col_band in (" . $bandslots_list . ")";
			$sql .= " and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$binding[] = $band;
		}
		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and coalesce(iota.status, '') <> 'D'";
		}
		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $postdata['mode'];
			$binding[] = $postdata['mode'];
		}
		$sql .= $this->addContinentsToQuery($postdata);
		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

}
?>
