<?php

use Wavelog\Dxcc\Dxcc_id;

require_once APPPATH . '../src/Dxcc/Dxcc_id.php';

class DXCC extends CI_Model {

	private $dxcc_object;

	function __construct() {
		if(!$this->load->is_loaded('Genfunctions')) {
			$this->load->library('Genfunctions');
		}
		if ($this->dxcc_object == null) {
			$this->dxcc_object = new Dxcc_id(null);
		}
	}

	/**
	 *	Function: mostactive
	 *	Information: Returns the most active band
	 **/
	function info($callsign) {
		$exceptions = $this->db->query('
				SELECT *
				FROM `dxcc_exceptions`
				WHERE `prefix` = ?
				LIMIT 1
			',array($callsign));

		if ($exceptions->num_rows() > 0) {
			return $exceptions;
		} else {
			$query = $this->db->query('
					SELECT *
					FROM dxcc_entities
					WHERE prefix = SUBSTRING(?, 1, LENGTH( prefix ) )
					ORDER BY LENGTH( prefix ) DESC
					LIMIT 1
				',array($callsign));

			return $query;
		}
	}

	/*
	 * Fetches a list of all dxcc's, both current and deleted
	 */
	function list() {
		$this->db->order_by('name', 'ASC');
		return $this->db->get('dxcc_entities');
	}

	/*
	 * Fetches a list of all current dxcc's (non-deleted)
	 */
	function list_current($orderer = 'name') {
		$this->db->where('end', null);
		$this->db->where('adif !=', 0);

		if ($orderer == 'name') {
			$this->db->order_by('name', 'ASC');
		} elseif ($orderer == 'prefix') {
			$this->db->order_by('prefix', 'ASC');
		}
		return $this->db->get('dxcc_entities');
	}

	function get_dxcc_array($dxccArray, $bands, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);

		foreach ($bands as $band) {             	// Looping through bands and entities to generate the array needed for display
			if (($postdata['band'] != 'SAT') && ($band == 'SAT')) {
				continue;
			}
			foreach ($dxccArray as $dxcc) {
				if ($dxcc->adif == '0') {
					$dxccMatrix[$dxcc->adif]['name'] = $dxcc->name;
				} else {
					$dxccMatrix[$dxcc->adif]['name'] = ucwords(strtolower($dxcc->name), "- (/");
				}
				$dxccMatrix[$dxcc->adif]['Dxccprefix'] = $dxcc->prefix;
				if ($postdata['includedeleted'])
					$dxccMatrix[$dxcc->adif]['Deleted'] = isset($dxcc->Enddate) ? 1 : 0;
				$dxccMatrix[$dxcc->adif][$band] = '-';
			}

			// If worked is checked, we add worked entities to the array
			if ($postdata['worked'] != NULL) {
				$workedDXCC = $this->getDxccBandWorked($location_list, $band, $postdata);
				foreach ($workedDXCC as $wdxcc) {
					$dxccMatrix[$wdxcc->dxcc][$band] = '<div class="bg-danger awardsBgWarning" ><a href=\'javascript:displayContacts("'.$wdxcc->dxcc.'","'. $band . '","'. $postdata['sat'] . '","' . $postdata['orbit'] . '","'. $postdata['mode'] . '","DXCC2", "", "'.$postdata['dateFrom'].'", "'.$postdata['dateTo'].'")\'>W</a></div>';
				}
			}

			// If confirmed is checked, we add confirmed entities to the array
			if ($postdata['confirmed'] != NULL) {
				$confirmedDXCC = $this->getDxccBandConfirmed($location_list, $band, $postdata);
				foreach ($confirmedDXCC as $cdxcc) {
					$dxccMatrix[$cdxcc->dxcc][$band] = '<div class="bg-success awardsBgSuccess" additional_successinfo=">C<"><a href=\'javascript:displayContacts("'.$cdxcc->dxcc.'","'. $band . '","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","' . $postdata['mode'] . '","DXCC2","'.$qsl.'","'.$postdata['dateFrom'].'","'.$postdata['dateTo'].'")\'>'.$this->cf_type($postdata, $cdxcc->qsl,$cdxcc->lotw, $cdxcc->eqsl, $cdxcc->qrz, $cdxcc->clublog).'</a></div>';
				}
			}
		}

		// We want to remove the worked dxcc's in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$workedDxcc = $this->getDxccWorked($location_list, $postdata);
			foreach ($workedDxcc as $wdxcc) {
				if (array_key_exists($wdxcc->dxcc, $dxccMatrix)) {
					unset($dxccMatrix[$wdxcc->dxcc]);
				}
			}
		}

		// We want to remove the confirmed dxcc's in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$confirmedDxcc = $this->getDxccConfirmed($location_list, $postdata);
			foreach ($confirmedDxcc as $cdxcc) {
				if (array_key_exists($cdxcc->dxcc, $dxccMatrix)) {
					unset($dxccMatrix[$cdxcc->dxcc]);
				}
			}
		}

		if (isset($dxccMatrix)) {
			return $dxccMatrix;
		} else {
			return 0;
		}
	}

	private function cf_type($postdata,$qsl,$lotw,$eqsl,$qrz,$clublog) {
		$string='';
		if ((($qsl ?? 0)>0) && (($postdata['qsl'] ?? '') != '')) { $string.='Q'; }
		if ((($lotw ?? 0)>0) && (($postdata['lotw'] ?? '') != '')) { $string.='L'; }
		if ((($eqsl ?? 0)>0) && (($postdata['eqsl'] ?? '') != '')) { $string.='E'; }
		if ((($qrz ?? 0)>0) && (($postdata['qrz'] ?? '') != '')) { $string.='Z'; }
		if ((($clublog ?? 0)>0) && (($postdata['clublog'] ?? '') != '')) { $string.='C'; }
		if ($string == '') { $string='C'; }
		return $string;
	}

	function getDxccBandConfirmed($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "select adif as dxcc, name, lotw, qsl, eqsl, qrz, clublog from dxcc_entities
				join (
					select col_dxcc, sum(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,sum(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,sum(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,sum(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,sum(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog from ".$this->config->item('table_name')." thcv
					LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
					where station_id in (" . $location_list .
				  ") and col_dxcc > 0";

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		if ($band == 'SAT') {
			$sql .= " and col_prop_mode='SAT'";
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		} else {
			$sql.=" and (col_prop_mode!='SAT' or col_prop_mode is null)";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		if ($postdata['dateFrom'] != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$bindings[]=$postdata['dateFrom'];
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$bindings[]=$postdata['dateTo'];
		}

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getDxccBandWorked($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "select adif as dxcc, name from dxcc_entities
				join (
					select col_dxcc from ".$this->config->item('table_name')." thcv
					LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
					where station_id in (" . $location_list .
					") and col_dxcc > 0";
		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		if ($band == 'SAT') {
			$sql .= " and col_prop_mode ='SAT'";
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		} else {
			$sql.=" and (col_prop_mode != 'SAT' or col_prop_mode is null)";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		if ($postdata['dateFrom'] != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$bindings[]=$postdata['dateFrom'];
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$bindings[]=$postdata['dateTo'];
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";;
		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}
		$sql .= $this->addContinentsToQuery($postdata);

		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}

	function fetchDxcc($postdata) {
		$bindings=[];
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$sql = "select adif, prefix, name, date(end) Enddate, date(start) Startdate, lat, `long`
			from dxcc_entities";

		if ($postdata['notworked'] == NULL) {
			$sql .= " join (select col_dxcc from " . $this->config->item('table_name') . " thcv
			LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
			where station_id in (" . $location_list . ") and col_dxcc > 0";

			if ($postdata['band'] != 'All') {
				if ($postdata['band'] == 'SAT') {
					$sql .= " and col_prop_mode = ?";
					$bindings[]=$postdata['band'];
					if ($postdata['sat'] != 'All') {
						$sql .= " and col_sat_name = ?";
						$bindings[]=$postdata['sat'];
					}
				} else {
					$sql .= " and col_prop_mode !='SAT'";
					$sql .= " and col_band = ?";
					$bindings[]=$postdata['band'];
				}
			} else {
				$sql.=" and (col_prop_mode != 'SAT' or col_prop_mode is null)";
			}

			if ($postdata['dateFrom'] != NULL) {
				$sql .= " and date(col_time_on) >= ?";
				$bindings[]=$postdata['dateFrom'];
			}

			if ($postdata['dateTo'] != NULL) {
				$sql .= " and date(col_time_on) <= ?";
				$bindings[]=$postdata['dateTo'];
			}

			if ($postdata['mode'] != 'All') {
				$sql .= " and (col_mode = ? or col_submode = ?)";
				$bindings[]=$postdata['mode'];
				$bindings[]=$postdata['mode'];
			}

			$sql .= $this->addOrbitToQuery($postdata, $bindings);

			$sql .= ' group by col_dxcc) x on dxcc_entities.adif = x.col_dxcc';
		}

		$sql .= " where 1 = 1";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$sql .= ' order by prefix';
		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getDxccWorked($location_list, $postdata) {
		$bindings=[];
		$sql = "SELECT adif as dxcc FROM dxcc_entities
			join (
				select col_dxcc
				from ".$this->config->item('table_name')." thcv
				LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
				where station_id in (" . $location_list .
				") and col_dxcc > 0";
		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		if ($postdata['band'] == 'SAT') {
			$sql .= " and col_prop_mode = 'SAT'";
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
			$sql .= $this->addOrbitToQuery($postdata,$bindings);
		} else {
			$sql.=" and (col_prop_mode != 'SAT' or col_prop_mode is null)";
		}


		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		if ($postdata['dateFrom'] != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$bindings[]=$postdata['dateFrom'];
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$bindings[]=$postdata['dateTo'];
		}

		$sql .= " and not exists (select 1 from ".$this->config->item('table_name')." where station_id in (". $location_list .") and col_dxcc = thcv.col_dxcc and col_dxcc > 0";
		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		if ($postdata['band'] == 'SAT') {
			$sql .= " and col_prop_mode = 'SAT'";
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
			$sql .= $this->addOrbitToQuery($postdata,$bindings);
		} else {
			$sql.=" and (col_prop_mode != 'SAT' or col_prop_mode is null)";
		}

		if ($postdata['dateFrom'] != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$bindings[]=$postdata['dateFrom'];
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$bindings[]=$postdata['dateTo'];
		}


		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= ')';
		$sql .= " group by col_dxcc
	    ) ll on dxcc_entities.adif = ll.col_dxcc
	    where 1=1";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);
		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}

	function getDxccConfirmed($location_list, $postdata) {
		$bindings=[];
		$sql = "SELECT adif as dxcc, lotw, qsl, eqsl, qrz, clublog FROM dxcc_entities
	    join (
		select col_dxcc, sum(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,sum(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,sum(case when thcv.col_eqsl_qsl_rcvd = 'Y' then 1 else 0 end) as eqsl,sum(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,sum(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog
		from ".$this->config->item('table_name')." thcv
		LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
		where station_id in (". $location_list .
		    ") and col_dxcc > 0";

		if ($postdata['dateFrom'] != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$bindings[]=$postdata['dateFrom'];
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$bindings[]=$postdata['dateTo'];
		}

		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		if ($postdata['band'] == 'SAT') {
			$sql .= " and col_prop_mode = 'SAT'";
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		} else {
			$sql.=" and (col_prop_mode != 'SAT' or col_prop_mode is null)";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= " group by col_dxcc
	    ) ll on dxcc_entities.adif = ll.col_dxcc
	    where 1=1";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);


		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	// Made function instead of repeating this several times
	function addContinentsToQuery($postdata) {
		$sql = '';
		if ($postdata['Africa'] == NULL) {
			$sql .= " and cont <> 'AF'";
		}

		if ($postdata['Europe'] == NULL) {
			$sql .= " and cont <> 'EU'";
		}

		if ($postdata['Asia'] == NULL) {
			$sql .= " and cont <> 'AS'";
		}

		if ($postdata['SouthAmerica'] == NULL) {
			$sql .= " and cont <> 'SA'";
		}

		if ($postdata['NorthAmerica'] == NULL) {
			$sql .= " and cont <> 'NA'";
		}

		if ($postdata['Oceania'] == NULL) {
			$sql .= " and cont <> 'OC'";
		}

		if ($postdata['Antarctica'] == NULL) {
			$sql .= " and cont <> 'AN'";
		}
		return $sql;
	}

	/*
     * Function gets worked and confirmed summary on each band on the active stationprofile
     */
	function get_dxcc_summary($bands, $postdata) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		foreach ($bands as $band) {
			$worked = $this->getSummaryByBand($band, $postdata, $location_list);
			$confirmed = $this->getSummaryByBandConfirmed($band, $postdata, $location_list);
			$dxccSummary['worked'][$band] = $worked[0]->count;
			$dxccSummary['confirmed'][$band] = $confirmed[0]->count;
			$dxccSummary['confirmed_lotw'][$band] = $confirmed[0]->lotw;
			$dxccSummary['confirmed_qsl'][$band] = $confirmed[0]->qsl;
		}

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $location_list);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $location_list);

		$dxccSummary['worked']['Total'] = $workedTotal[0]->count;
		$dxccSummary['confirmed']['Total'] = $confirmedTotal[0]->count;

		return $dxccSummary;
	}

	function getSummaryByBand($band, $postdata, $location_list) {
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_dxcc) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name";
		$sql .= " join dxcc_entities d on thcv.col_dxcc = d.adif";

		$sql .= " where station_id in (" . $location_list . ") and col_dxcc > 0";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode ='" . $band . "'";
			if ($band != 'All' && $postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('dxcc');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$bindings[]=$band;
		}

		if ($postdata['dateFrom'] != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$bindings[]=$postdata['dateFrom'];
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$bindings[]=$postdata['dateTo'];
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and d.end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);

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

	function getSummaryByBandConfirmed($band, $postdata, $location_list) {
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_dxcc) as count, sum(case when thcv.col_lotw_qsl_rcvd ='Y' then 1 else 0 end) as lotw,sum(case when thcv.col_qsl_rcvd = 'Y' then 1 else 0 end) as qsl,sum(case when thcv.col_eqsl_qsl_rcvd = 'Y'     then 1 else 0 end) as eqsl,sum(case when thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS= 'Y' then 1 else 0 end) as qrz,sum(case when thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' then 1 else 0 end) as clublog FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name";
		$sql .= " join dxcc_entities d on thcv.col_dxcc = d.adif";

		$sql .= " where station_id in (" . $location_list . ") and col_dxcc > 0";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$bindings[]=$band;
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[]=$postdata['sat'];
			}
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('dxcc');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$bindings[]=$band;
		}

		if ($postdata['dateFrom'] != NULL) {
			$sql .= " and date(col_time_on) >= ?";
			$bindings[]=$postdata['dateFrom'];
		}

		if ($postdata['dateTo'] != NULL) {
			$sql .= " and date(col_time_on) <= ?";
			$bindings[]=$postdata['dateTo'];
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addOrbitToQuery($postdata,$bindings);


		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and d.end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function lookup_country($country) {
		$bindings=[];
		$query = $this->db->query('
					SELECT *
					FROM dxcc_entities
					WHERE name = ?
					ORDER BY LENGTH( prefix ) DESC
					LIMIT 1
				',array($country));

		return $query->row();
	}

	public function dxcc_lookup_old($call, $date) {

		$date = date("Y-m-d", strtotime($date));
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';

		$dxcc_exceptions = $this->db->select('`entity`, `adif`, `cqz`,`cont`,`long`,`lat`')
			->where('`call`', $call)
			->where('(start <= ', $date)
			->or_where('start is null)', NULL, false)
			->where('(end >= ', $date)
			->or_where('end is null)', NULL, false)
			->get('dxcc_exceptions');
		if ($dxcc_exceptions->num_rows() > 0) {
			$row = $dxcc_exceptions->row_array();
			return $row;
		} else {

			if (preg_match('/(^KG4)[A-Z09]{3}/', $call)) {       // KG4/ and KG4 5 char calls are Guantanamo Bay. If 4 or 6 char, it is USA
				$call = "K";
			} elseif (preg_match('/(^OH\/)|(\/OH[1-9]?$)/', $call)) {   # non-Aland prefix!
				$call = "OH";                                             # make callsign OH = finland
			} elseif (preg_match('/(^CX\/)|(\/CX[1-9]?$)/', $call)) {   # non-Antarctica prefix!
				$call = "CX";                                             # make callsign CX = Uruguay
			} elseif (preg_match('/(^3D2R)|(^3D2.+\/R)/', $call)) {     # seems to be from Rotuma
				$call = "3D2/R";                                          # will match with Rotuma
			} elseif (preg_match('/^3D2C/', $call)) {                   # seems to be from Conway Reef
				$call = "3D2/C";                                          # will match with Conway
			} elseif (preg_match('/(^LZ\/)|(\/LZ[1-9]?$)/', $call)) {   # LZ/ is LZ0 by DXCC but this is VP8h
				$call = "LZ";
			} elseif (preg_match('/(^KG4)[A-Z09]{2}/', $call)) {
				$call = "KG4";
			} elseif (preg_match('/(^KG4)[A-Z09]{1}/', $call)) {
				$call = "K";
			} elseif (preg_match('/\w\/\w/', $call)) {
				if (preg_match_all('/^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/', $call, $matches)) {
					$prefix = $matches[1][0];
					$callsign = $matches[3][0];
					$suffix = $matches[5][0];
					if ($prefix) {
						$prefix = substr($prefix, 0, -1); # Remove the / at the end
					}
					if ($suffix) {
						$suffix = substr($suffix, 1); # Remove the / at the beginning
					};
					if (preg_match($csadditions, $suffix)) {
						if ($prefix) {
							$call = $prefix;
						} else {
							$call = $callsign;
						}
					} else {
						$result = $this->wpx($call, 1);                       # use the wpx prefix instead
						if ($result == '') {
							$row['adif'] = 0;
							$row['cont'] = '';
							$row['entity'] = '- NONE -';
							$row['ituz'] = 0;
							$row['cqz'] = 0;
							$row['long'] = '0';
							$row['lat'] = '0';
							return $row;
						} else {
							$call = $result . "AA";
						}
					}
				}
			}

			$len = strlen($call);
			$dxcc_array = [];

			// Fetch all candidates in one shot instead of looping
			$dxcc_result = $this->db->query("SELECT `dxcc_prefixes`.`record`, `dxcc_prefixes`.`call`, `dxcc_prefixes`.`entity`, `dxcc_prefixes`.`adif`, `dxcc_prefixes`.`cqz`, `dxcc_entities`.`ituz`, `dxcc_prefixes`.`cont`, `dxcc_prefixes`.`long`, `dxcc_prefixes`.`lat`, `dxcc_prefixes`.`start`, `dxcc_prefixes`.`end`
			    FROM `dxcc_prefixes`
			    LEFT JOIN `dxcc_entities` ON `dxcc_entities`.`adif` = `dxcc_prefixes`.`adif`
			    WHERE ? like concat(`call`,'%')
			    and `dxcc_prefixes`.`call` like ?
			    AND (`dxcc_prefixes`.`start` <= ?  OR `dxcc_prefixes`.`start` is null)
			    AND (`dxcc_prefixes`.`end` >= ?  OR `dxcc_prefixes`.`end` is null) order by length(`call`) desc limit 1", array($call, substr($call, 0, 1) . '%', $date, $date));

			foreach ($dxcc_result->result_array() as $row) {
				$dxcc_array[$row['call']] = $row;
			}

			// query the table, removing a character from the right until a match
			for ($i = $len; $i > 0; $i--) {
				//printf("searching for %s\n", substr($call, 0, $i));
				if (array_key_exists(substr($call, 0, $i), $dxcc_array)) {
					$row = $dxcc_array[substr($call, 0, $i)];
					// $row = $dxcc_result->row_array();
					return $row;
				}
			}
		}

		return array(
			'adif' => 0,
			'cqz' => 0,
			'ituz' => 0,
			'long' => '',
			'lat' => '',
			'entity' => 'None',
		);
	}

	function wpx($testcall, $i) {
		$prefix = '';
		$a = '';
		$b = '';
		$c = '';

		$lidadditions = '/^QRP$|^LGT$/';
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';
		$noneadditions = '/^MM$|^AM$/';

		# First check if the call is in the proper format, A/B/C where A and C
		# are optional (prefix of guest country and P, MM, AM etc) and B is the
		# callsign. Only letters, figures and "/" is accepted, no further check if the
		# callsign "makes sense".
		# 23.Apr.06: Added another "/X" to the regex, for calls like RV0AL/0/P
		# as used by RDA-DXpeditions....

		if (preg_match_all('/^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/', $testcall, $matches)) {

			# Now $1 holds A (incl /), $3 holds the callsign B and $5 has C
			# We save them to $a, $b and $c respectively to ensure they won't get
			# lost in further Regex evaluations.
			$a = $matches[1][0];
			$b = $matches[3][0];
			$c = $matches[5][0];

			if ($a) {
				$a = substr($a, 0, -1); # Remove the / at the end
			}
			if ($c) {
				$c = substr($c, 1); # Remove the / at the beginning
			};

			# In some cases when there is no part A but B and C, and C is longer than 2
			# letters, it happens that $a and $b get the values that $b and $c should
			# have. This often happens with liddish callsign-additions like /QRP and
			# /LGT, but also with calls like DJ1YFK/KP5. ~/.yfklog has a line called
			# "lidadditions", which has QRP and LGT as defaults. This sorts out half of
			# the problem, but not calls like DJ1YFK/KH5. This is tested in a second
			# try: $a looks like a call (.\d[A-Z]) and $b doesn't (.\d), they are
			# swapped. This still does not properly handle calls like DJ1YFK/KH7K where
			# only the OP's experience says that it's DJ1YFK on KH7K.
			if (!$c && $a && $b) {                          # $a and $b exist, no $c
				if (preg_match($lidadditions, $b)) {        # check if $b is a lid-addition
					$b = $a;
					$a = null;                              # $a goes to $b, delete lid-add
				} elseif ((preg_match('/\d[A-Z]+$/', $a)) && (preg_match('/\d$/', $b) || preg_match('/^[A-Z]\d[A-Z]$/', $b))) {   # check for call in $a
					$temp = $b;
					$b = $a;
					$a = $temp;
				}
			}

			# *** Added later ***  The check didn't make sure that the callsign
			# contains a letter. there are letter-only callsigns like RAEM, but not
			# figure-only calls.

			if (preg_match('/^[0-9]+$/', $b)) {            # Callsign only consists of numbers. Bad!
				return null;            # exit, undef
			}

			# Depending on these values we have to determine the prefix.
			# Following cases are possible:
			#
			# 1.    $a and $c undef --> only callsign, subcases
			# 1.1   $b contains a number -> everything from start to number
			# 1.2   $b contains no number -> first two letters plus 0
			# 2.    $a undef, subcases:
			# 2.1   $c is only a number -> $a with changed number
			# 2.2   $c is /P,/M,/MM,/AM -> 1.
			# 2.3   $c is something else and will be interpreted as a Prefix
			# 3.    $a is defined, will be taken as PFX, regardless of $c

			if (($a == null) && ($c == null)) {                     # Case 1
				if (preg_match('/\d/', $b)) {                       # Case 1.1, contains number
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # Prefix is all but the last
					$prefix = $matches[1];                          # Letters
				} else {                                            # Case 1.2, no number
					$prefix = substr($b, 0, 2) . "0";               # first two + 0
				}
			} elseif (($a == null) && (isset($c))) {                # Case 2, CALL/X
				if (preg_match('/^(\d)/', $c)) {                    # Case 2.1, number
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # regular Prefix in $1
					# Here we need to find out how many digits there are in the
					# prefix, because for example A45XR/0 is A40. If there are 2
					# numbers, the first is not deleted. If course in exotic cases
					# like N66A/7 -> N7 this brings the wrong result of N67, but I
					# think that's rather irrelevant cos such calls rarely appear
					# and if they do, it's very unlikely for them to have a number
					# attached.   You can still edit it by hand anyway..
					if (preg_match('/^([A-Z]\d{2,})$/', $matches[1])) {        # e.g. A45   $c = 0
						$prefix = $matches[1] . $c;  # ->   A40
					} else {                         # Otherwise cut all numbers
						preg_match('/(.*[A-Z])\d+/', $matches[1], $match); # Prefix w/o number in $1
						$prefix = $match[1] . $c; # Add attached number
					}
				} elseif (preg_match($csadditions, $c)) {
					preg_match('/(.+\d)[A-Z]*/', $b, $matches);     # Known attachment -> like Case 1.1
					$prefix = $matches[1];
				} elseif (preg_match($noneadditions, $c)) {
					return '';
				} elseif (preg_match('/^\d\d+$/', $c)) {            # more than 2 numbers -> ignore
					preg_match('/(.+\d)[A-Z]* /', $b, $matches);    # see above
					$prefix = $matches[1][0];
				} else {                                            # Must be a Prefix!
					if (preg_match('/\d$/', $c)) {                  # ends in number -> good prefix
						$prefix = $c;
					} else {                                        # Add Zero at the end
						$prefix = $c . "0";
					}
				}
			} elseif (($a) && (preg_match($noneadditions, $c))) {                # Case 2.1, X/CALL/X ie TF/DL2NWK/MM - DXCC none
				return '';
			} elseif ($a) {
				# $a contains the prefix we want
				if (preg_match('/\d$/', $a)) {                      # ends in number -> good prefix
					$prefix = $a;
				} else {                                            # add zero if no number
					$prefix = $a . "0";
				}
			}
			# In very rare cases (right now I can only think of KH5K and KH7K and FRxG/T
			# etc), the prefix is wrong, for example KH5K/DJ1YFK would be KH5K0. In this
			# case, the superfluous part will be cropped. Since this, however, changes the
			# DXCC of the prefix, this will NOT happen when invoked from with an
			# extra parameter $_[1]; this will happen when invoking it from &dxcc.

			if (preg_match('/(\w+\d)[A-Z]+\d/', $prefix, $matches) && $i == null) {
				$prefix = $matches[1][0];
			}
			return $prefix;
		} else {
			return '';
		}
	}

	/*
     * Check the dxcc_prefixes table and return (dxcc, country)
     */
	public function check_dxcc_table($call, $date) {

		$date = date("Y-m-d", strtotime($date));
		$csadditions = '/^X$|^D$|^T$|^P$|^R$|^B$|^A$|^M$|^LH$|^L$|^J$|^SK$/';

		$dxcc_exceptions = $this->db->select('`entity`, `adif`, `cqz`, `cont`')
			->where('`call`', $call)
			->where('(start <= ', $date)
			->or_where('start is null)', NULL, false)
			->where('(end >= ', $date)
			->or_where('end is null)', NULL, false)
			->get('dxcc_exceptions');

		if ($dxcc_exceptions->num_rows() > 0) {
			$row = $dxcc_exceptions->row_array();
			return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
		}
		if (preg_match('/(^KG4)[A-Z09]{3}/', $call)) {      // KG4/ and KG4 5 char calls are Guantanamo Bay. If 4 or 6 char, it is USA
			$call = "K";
		} elseif (preg_match('/(^OH\/)|(\/OH[1-9]?$)/', $call)) {   # non-Aland prefix!
			$call = "OH";                                             # make callsign OH = finland
		} elseif (preg_match('/(^CX\/)|(\/CX[1-9]?$)/', $call)) {   # non-Antarctica prefix!
			$call = "CX";                                             # make callsign CX = Uruguay
		} elseif (preg_match('/(^3D2R)|(^3D2.+\/R)/', $call)) {     # seems to be from Rotuma
			$call = "3D2/R";                                          # will match with Rotuma
		} elseif (preg_match('/^3D2C/', $call)) {                   # seems to be from Conway Reef
			$call = "3D2/C";                                          # will match with Conway
		} elseif (preg_match('/(^LZ\/)|(\/LZ[1-9]?$)/', $call)) {   # LZ/ is LZ0 by DXCC but this is VP8h
			$call = "LZ";
		} elseif (preg_match('/(^KG4)[A-Z09]{2}/', $call)) {
			$call = "KG4";
		} elseif (preg_match('/(^KG4)[A-Z09]{1}/', $call)) {
			$call = "K";
		} elseif (preg_match('/\w\/\w/', $call)) {
			if (preg_match_all('/^((\d|[A-Z])+\/)?((\d|[A-Z]){3,})(\/(\d|[A-Z])+)?(\/(\d|[A-Z])+)?$/', $call, $matches)) {
				$prefix = $matches[1][0];
				$callsign = $matches[3][0];
				$suffix = $matches[5][0];
				if ($prefix) {
					$prefix = substr($prefix, 0, -1); # Remove the / at the end
				}
				if ($suffix) {
					$suffix = substr($suffix, 1); # Remove the / at the beginning
				};
				if (preg_match($csadditions, $suffix)) {
					if ($prefix) {
						$call = $prefix;
					} else {
						$call = $callsign;
					}
				} else {
					$result = $this->wpx($call, 1);                       # use the wpx prefix instead
					if ($result == '') {
						$row['adif'] = 0;
						$row['entity'] = '- NONE -';
						$row['cqz'] = 0;
						$row['cont'] = '';
						return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
					} else {
						$call = $result . "AA";
					}
				}
			}
		}

		$len = strlen($call);
		$dxcc_array = [];
		// Fetch all candidates in one shot instead of looping
		$dxcc_result = $this->db->query("SELECT `call`, `entity`, `adif`, `cqz`, `cont`
		    FROM `dxcc_prefixes`
		    WHERE ? like concat(`call`,'%')
		    and `call` like ?
		    AND (`start` <= ?  OR start is null)
		    AND (`end` >= ?  OR end is null) order by length(`call`) desc limit 1", array($call, substr($call, 0, 1) . '%', $date, $date));

		foreach ($dxcc_result->result_array() as $row) {
			$dxcc_array[$row['call']] = $row;
		}

		// query the table, removing a character from the right until a match
		for ($i = $len; $i > 0; $i--) {
			//printf("searching for %s\n", substr($call, 0, $i));
			if (array_key_exists(substr($call, 0, $i), $dxcc_array)) {
				$row = $dxcc_array[substr($call, 0, $i)];
				// $row = $dxcc_result->row_array();
				return array($row['adif'], $row['entity'], $row['cqz'], $row['cont']);
			}
		}

		return array("Not Found", "Not Found");
	}

	public function dxcc_lookup($call, $date) {
		$result = $this->dxcc_object->dxcc_lookup($call, $date);
		return $result;
	}
}
?>
