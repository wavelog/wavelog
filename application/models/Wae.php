<?php

class WAE extends CI_Model {

	// Make an array of the WAE countries

	// Reference: https://www.darc.de/der-club/referate/dx/diplome/wae-diplom/wae-laenderliste/
	// ADIF refrence: https://www.adif.org.uk/315/ADIF_315.htm#Region_Enumeration

	// $sql = select * from dxcc_entities where cont = 'EU' and end is null

	private $eucountries = '5,7,21,27,40,45,52,54,61,106,114,117,118,122,126,145,146,149,167,179,180,203,206,209,212,214,221,222,223,224,225,227,230,233,236,239,242,245,246,248,251,254,257,259,260,263,265,266,269,272,275,278,279,281,284,287,288,294,295,296,497,499,501,502,503,504,514,522';

	// 4U1V (OE for DXCC), JW/b, GM/s, IT, TA1,
	private $waecountries = '206, 248, 259, 279, 390';

	private $region = "'IV', 'SY', 'BI', 'SI', 'ET'";

	// $sql = select * from dxcc_entities where cont = 'EU' and end is not null

	// Need to handle deleted eu countries
	// Deleted
	// Prefix	Country	valid since	valid until
	// 9S4	Saarland	Nov 8, 1947	Mar 31, 1957
	// I1	Triest	 	Mar 31, 1957
	// UN	Karelo Finish Republic	 	Jun 30, 1960
	// DL	Germany	 	Sep 16, 1973
	// Y2	German Democratic Republic	Sep 17, 1973	Oct 2, 1990
	// UA1N	Karelia	Jul 1, 1960	Dec 31, 1991
	// OK	Czechoslovakia	 	Dec 31, 1992
	// R1MV	Maliy Vysotskij Isl.		Feb 17, 2012

	private $location_list;

	function __construct() {
		if(!$this->load->is_loaded('Genfunctions')) {
			$this->load->library('Genfunctions');
		}

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if ($logbooks_locations_array) {
						// Create the location_list string
			$this->location_list = "'" . implode("','", $logbooks_locations_array) . "'";
		} else {
			// Handle the case where $logbooks_locations_array is empty or not set
			$this->location_list = '';
		}
	}

	function get_wae_array($bands, $postdata) {
		if ($this->location_list == '') {
			return null;
		}

		$waeCount = array(); // Used for keeping track of which WAE are not worked

		$waeCount['IV']['count'] = 0;
		$waeCount['SY']['count'] = 0;
		$waeCount['BI']['count'] = 0;
		$waeCount['SI']['count'] = 0;
		$waeCount['ET']['count'] = 0;

		$dxccArray = $this->fetchdxcc($postdata, $this->location_list);

		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);

		foreach ($bands as $band) {             	// Looping through bands and entities to generate the array needed for display
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
			$dxccMatrix['IV']['name'] = 'ITU Vienna';
			$dxccMatrix['IV']['Dxccprefix'] = '4U1V';
			$dxccMatrix['IV'][$band] = '-';
			$dxccMatrix['SY']['name'] = 'Sicily';
			$dxccMatrix['SY']['Dxccprefix'] = 'IT9';
			$dxccMatrix['SY'][$band] = '-';
			$dxccMatrix['BI']['name'] = 'Bear Island';
			$dxccMatrix['BI']['Dxccprefix'] = 'JW/b';
			$dxccMatrix['BI'][$band] = '-';
			$dxccMatrix['SI']['name'] = 'Shetland Islands';
			$dxccMatrix['SI']['Dxccprefix'] = 'GM/s';
			$dxccMatrix['SI'][$band] = '-';
			$dxccMatrix['ET']['name'] = 'European Turkey';
			$dxccMatrix['ET']['Dxccprefix'] = 'TA1';
			$dxccMatrix['ET'][$band] = '-';

			// If worked is checked, we add worked entities to the array
			if ($postdata['worked'] != NULL) {
				$workedDXCC = $this->getDxccBandWorked($this->location_list, $band, $postdata);
				foreach ($workedDXCC as $wdxcc) {
					$dxccMatrix[$wdxcc->dxcc][$band] = '<div class="bg-danger awardsBgDanger" ><a href=\'javascript:displayContacts("'.$wdxcc->dxcc.'","'. $band . '","'. $postdata['sat'] . '","' . $postdata['orbit'] . '","'. $postdata['mode'] . '","WAE", "")\'>W</a></div>';
				}

				$workedDXCC = $this->getDxccBandWorked($this->location_list, $band, $postdata, true);
				foreach ($workedDXCC as $wdxcc) {
					$dxccMatrix[$wdxcc->col_region][$band] = '<div class="bg-danger awardsBgDanger" ><a href=\'javascript:displayContacts("'.$wdxcc->col_region.'","'. $band . '","'. $postdata['sat'] . '","' . $postdata['orbit'] . '","'. $postdata['mode'] . '","WAE", "")\'>W</a></div>';
					$waeCount[$wdxcc->col_region]['count']++;
				}
			}

			// If confirmed is checked, we add confirmed entities to the array
			if ($postdata['confirmed'] != NULL) {
				$confirmedDXCC = $this->getDxccBandConfirmed($this->location_list, $band, $postdata);
				foreach ($confirmedDXCC as $cdxcc) {
					$dxccMatrix[$cdxcc->dxcc][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("'.$cdxcc->dxcc.'","'. $band . '","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","' . $postdata['mode'] . '","WAE","'.$qsl.'")\'>C</a></div>';
				}
				$confirmedDXCC = $this->getDxccBandConfirmed($this->location_list, $band, $postdata, true);
				foreach ($confirmedDXCC as $cdxcc) {
					$dxccMatrix[$cdxcc->col_region][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("'.$cdxcc->col_region.'","'. $band . '","'. $postdata['sat'] . '","'. $postdata['orbit'] . '","' . $postdata['mode'] . '","WAE","'.$qsl.'")\'>C</a></div>';
					$waeCount[$cdxcc->col_region]['count']++;
				}
			}
		}

		// We want to remove the worked dxcc's in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$workedDxcc = $this->getDxccWorked($this->location_list, $postdata);
			foreach ($workedDxcc as $wdxcc) {
				if (array_key_exists($wdxcc->dxcc, $dxccMatrix)) {
					unset($dxccMatrix[$wdxcc->dxcc]);
				}
			}
			$workedWae = $this->getDxccWorked($this->location_list, $postdata, true);
			foreach ($workedWae as $wdxcc) {
				if (array_key_exists($wdxcc->col_region, $dxccMatrix)) {
					unset($dxccMatrix[$wdxcc->col_region]);
				}
			}
		}

		// We want to remove the confirmed dxcc's in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$confirmedDxcc = $this->getDxccConfirmed($this->location_list, $postdata);
			foreach ($confirmedDxcc as $cdxcc) {
				if (array_key_exists($cdxcc->dxcc, $dxccMatrix)) {
					unset($dxccMatrix[$cdxcc->dxcc]);
				}
			}

			$confirmedWae = $this->getDxccConfirmed($this->location_list, $postdata, true);
			foreach ($confirmedWae as $cdxcc) {
				if (array_key_exists($cdxcc->col_region, $dxccMatrix)) {
					unset($dxccMatrix[$cdxcc->col_region]);
				}
			}
		}

		if ($postdata['notworked'] == NULL) {
			if ($waeCount['IV']['count'] == 0) {
				unset($dxccMatrix['IV']);
			};
			if ($waeCount['SY']['count'] == 0) {
				unset($dxccMatrix['SY']);
			};
			if ($waeCount['BI']['count'] == 0) {
				unset($dxccMatrix['BI']);
			};
			if ($waeCount['SI']['count'] == 0) {
				unset($dxccMatrix['SI']);
			};
			if ($waeCount['ET']['count'] == 0) {
				unset($dxccMatrix['ET']);
			};
		}

		if (isset($dxccMatrix)) {
			// Convert associative array to indexed array for sorting
			$dxccIndexed = array_values($dxccMatrix);

			// Sort the indexed array by the 'name' key
			usort($dxccIndexed, function ($a, $b) {
				return strcmp($a['Dxccprefix'], $b['Dxccprefix']);
			});

			// Optionally reindex the sorted array back to associative format
			$dxccSorted = [];
			foreach ($dxccIndexed as $item) {
				$key = array_search($item, $dxccMatrix);
				$dxccSorted[$key] = $item;
			}
			return $dxccSorted;
		} else {
			return 0;
		}
	}

	function getDxccBandConfirmed($location_list, $band, $postdata, $wae = false) {
		$bindings = [];
		$sql = "select adif as dxcc, name, x.col_region from dxcc_entities
				join (
					select col_region, col_dxcc from ".$this->config->item('table_name')." thcv
					LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
					where station_id in (" . $location_list . ")";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		if ($band == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[] = $postdata['sat'];
			}
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= " group by col_dxcc, col_region
				) x on dxcc_entities.adif = x.col_dxcc";

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		// }

		if ($wae) {
			$sql .= ' and dxcc_entities.adif in ( '. $this->waecountries . ')';
		} else {
			$sql .= ' and dxcc_entities.adif in (' . $this->eucountries . ')';
		}

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getDxccBandWorked($location_list, $band, $postdata, $wae = false) {
		$bindings=[];
		$sql = "select adif as dxcc, name, x.col_region from dxcc_entities
				join (
					select col_region, col_dxcc from ".$this->config->item('table_name')." thcv
					LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
					where station_id in (" . $location_list . ")";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		if ($band == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[] = $postdata['sat'];
			}
		}
		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}
		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= " group by col_dxcc, col_region
				) x on dxcc_entities.adif = x.col_dxcc";;

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		// }

		if ($wae) {
			$sql .= ' and dxcc_entities.adif in ( '. $this->waecountries . ')';
		} else {
			$sql .= ' and dxcc_entities.adif in (' . $this->eucountries . ')';
		}

		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}

	function fetchDxcc($postdata, $location_list) {
		$bindings = [];

		$sql = "select adif, prefix, name, date(end) Enddate, date(start) Startdate, lat, `long`
			from dxcc_entities";

		if ($postdata['notworked'] == NULL) {
			$sql .= " join (select col_dxcc, col_region from " . $this->config->item('table_name') . " thcv
			LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
						where station_id in (" . $location_list . ")";
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";

			if ($postdata['band'] != 'All') {
				if ($postdata['band'] == 'SAT') {
					$sql .= " and col_prop_mode = ?";
					$bindings[] = $postdata['band'];
					if ($postdata['sat'] != 'All') {
						$sql .= " and col_sat_name = ?";
						$bindings[] = $postdata['sat'];
					}
				} else {
					$sql .= " and col_prop_mode !='SAT'";
					$sql .= " and col_band = ?";
					$bindings[] = $postdata['band'];
				}
			}

			if ($postdata['mode'] != 'All') {
				$sql .= " and (col_mode = ? or col_submode = ?)";
				$bindings[] = $postdata['mode'];
				$bindings[] = $postdata['mode'];
			}

			$sql .= $this->addOrbitToQuery($postdata, $bindings);

			$sql .= ' group by col_dxcc, col_region) x on dxcc_entities.adif = x.col_dxcc';
		}

		$sql .= " where 1 = 1";

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and end is null";
		// }

		$sql .= ' and dxcc_entities.adif in (' . $this->eucountries . ')';

		$sql .= ' order by prefix';
		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getDxccWorked($location_list, $postdata, $wae = false) {
		$bindings = [];

		$sql = "SELECT adif as dxcc, ll.col_region FROM dxcc_entities
			join (
				select col_dxcc, col_region
				from ".$this->config->item('table_name')." thcv
				LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
				where station_id in (" . $location_list . ")";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}
		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		if ($postdata['band'] == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[] = $postdata['sat'];
			}
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		$sql .= " and not exists (select 1 from ".$this->config->item('table_name')." where station_id in (". $location_list .") and col_dxcc = thcv.col_dxcc";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);

		if ($postdata['band'] == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[] = $postdata['sat'];
			}
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= ')';
		$sql .= " group by col_dxcc, col_region
		) ll on dxcc_entities.adif = ll.col_dxcc
		where 1=1";

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		// }

		if ($wae) {
			$sql .= ' and dxcc_entities.adif in ( '. $this->waecountries . ')';
		} else {
			$sql .= ' and dxcc_entities.adif in (' . $this->eucountries . ')';
		}

		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}

	function getDxccConfirmed($location_list, $postdata, $wae = false) {
		$bindings = [];

		$sql = "SELECT adif as dxcc, ll.col_region FROM dxcc_entities
		join (
		select col_dxcc, col_region
		from ".$this->config->item('table_name')." thcv
		LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name
		where station_id in (" . $location_list . ")";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		if ($postdata['band'] == 'SAT') {
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[] = $postdata['sat'];
			}
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= " group by col_dxcc, col_region
		) ll on dxcc_entities.adif = ll.col_dxcc
		where 1=1";

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		// }

		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
     * Function gets worked and confirmed summary on each band on the active stationprofile
     */
	function get_wae_summary($bands, $postdata) {
		if ($this->location_list == '') {
			return null;
		}

		foreach ($bands as $band) {
			$dxccSummary['worked'][$band] = 0;
			$dxccSummary['confirmed'][$band] = 0;
		}

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('dxcc');

		//WAE
		$confirmed = $this->getSummaryConfirmed($postdata, $this->location_list, true);

		foreach ($confirmed as $c) {
			if (isset($dxccSummary['confirmed'][$c->col_band])) {
				$dxccSummary['confirmed'][$c->col_band] += $c->regioncount;
			}
		}

		$confirmed = '';

		// EU DXCC
		$confirmed = $this->getSummaryConfirmed($postdata, $this->location_list);

		foreach ($confirmed as $c) {
			if (isset($dxccSummary['confirmed'][$c->col_band])) {
				$dxccSummary['confirmed'][$c->col_band] += $c->count;
			}
		}

		// EU DXCC
		$worked = $this->getSummary($postdata, $this->location_list);

		foreach ($worked as $w) {
			if (isset($dxccSummary['worked'][$w->col_band])) {
				$dxccSummary['worked'][$w->col_band] += $w->count;
			}
		}

		$worked = '';

		//WAE
		$worked = $this->getSummary($postdata, $this->location_list, true);

		foreach ($worked as $w) {
			if (isset($dxccSummary['worked'][$w->col_band])) {
				$dxccSummary['worked'][$w->col_band] += $w->regioncount;
			}
		}

		if (isset($dxccSummary['worked']['SAT'])) {
			$workedSat = $this->getSummaryByBand('SAT', $postdata, $this->location_list, $bandslots, true);
			$dxccSummary['worked']['SAT'] += $workedSat[0]->regioncount;
			$workedSat = $this->getSummaryByBand('SAT', $postdata, $this->location_list, $bandslots);
			$dxccSummary['worked']['SAT'] += $workedSat[0]->count;
		}

		if (isset($dxccSummary['confirmed']['SAT'])) {
			$confirmedSat = $this->getSummaryByBandConfirmed('SAT', $postdata, $this->location_list, $bandslots, true);
			$dxccSummary['confirmed']['SAT'] += $confirmedSat[0]->regioncount;

			$confirmedSat = $this->getSummaryByBandConfirmed('SAT', $postdata, $this->location_list, $bandslots);
			$dxccSummary['confirmed']['SAT'] += $confirmedSat[0]->count;
		}

		$dxccSummary['worked']['Total'] = 0;
		$dxccSummary['confirmed']['Total'] = 0;

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $this->location_list, $bandslots);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $this->location_list, $bandslots);

		$dxccSummary['worked']['Total'] += $workedTotal[0]->count;
		$dxccSummary['confirmed']['Total'] += $confirmedTotal[0]->count;

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $this->location_list, $bandslots, true);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $this->location_list, $bandslots, true);

		$dxccSummary['worked']['Total'] += $workedTotal[0]->regioncount;
		$dxccSummary['confirmed']['Total'] += $confirmedTotal[0]->regioncount;

		return $dxccSummary;
	}

	function getSummary($postdata, $location_list, $wae = false) {
		$bindings = [];

		$sql = "SELECT count(distinct thcv.col_dxcc) as count, count(distinct thcv.col_region) regioncount, col_band FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name";
		$sql .= " join dxcc_entities d on thcv.col_dxcc = d.adif";

		$sql .= " where station_id in (" . $location_list . ")";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		$sql .=	" and thcv.col_prop_mode !='SAT'";

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and d.end is null";
		// }

		if ($wae) {
			$sql .= ' and d.adif in ( '. $this->waecountries . ')';
		} else {
			$sql .= ' and d.adif in (' . $this->eucountries . ')';
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$sql .= ' group by col_band';

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getSummaryByBand($band, $postdata, $location_list, $bandslots, $wae = false) {
		$bindings = [];
		$sql = "SELECT count(distinct thcv.col_dxcc) as count, count(distinct thcv.col_region) regioncount FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name";
		$sql .= " join dxcc_entities d on thcv.col_dxcc = d.adif";

		$sql .= " where station_id in (" . $location_list . ")";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode ='" . $band . "'";
			if ($band != 'All' && $postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[] = $postdata['sat'];
			}
		} else if ($band == 'All') {
			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$bindings[] = $band;
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and d.end is null";
		// }

		if ($wae) {
			$sql .= ' and d.adif in ( '. $this->waecountries . ')';
		} else {
			$sql .= ' and d.adif in (' . $this->eucountries . ')';
		}

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	// Adds orbit type to query
	function addOrbitToQuery($postdata,&$binding) {
		$sql = '';
		if ($postdata['orbit'] != 'All') {
			$sql .= ' AND satellite.orbit = ?';
			$binding[] = $postdata['orbit'];
		}

		return $sql;
	}

	function getSummaryConfirmed($postdata, $location_list, $wae = false) {
		$bindings = [];

		$sql = "SELECT count(distinct thcv.col_dxcc) as count, count(distinct thcv.col_region) regioncount, thcv.col_band FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name";
		$sql .= " join dxcc_entities d on thcv.col_dxcc = d.adif";

		$sql .= " where station_id in (" . $location_list . ")";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		$sql .=	" and thcv.col_prop_mode !='SAT'";

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and d.end is null";
		// }

		if ($wae) {
			$sql .= ' and d.adif in ( '. $this->waecountries . ')';
		} else {
			$sql .= ' and d.adif in (' . $this->eucountries . ')';
		}

		$sql .= ' group by thcv.col_band';

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getSummaryByBandConfirmed($band, $postdata, $location_list, $bandslots, $wae = false) {
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_dxcc) as count, count(distinct thcv.col_region) regioncount FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " LEFT JOIN satellite on thcv.COL_SAT_NAME = satellite.name";
		$sql .= " join dxcc_entities d on thcv.col_dxcc = d.adif";

		$sql .= " where station_id in (" . $location_list . ")";
		if ($wae) {
			$sql .= ' and col_dxcc in ( '. $this->waecountries . ') and col_region in ('. $this->region.')';
		} else {
			$sql .= " and col_dxcc in ( ". $this->eucountries . ") and coalesce(col_region, '') = ''";
		}

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$bindings[] = $band;
			if ($postdata['sat'] != 'All') {
				$sql .= " and col_sat_name = ?";
				$bindings[] = $postdata['sat'];
			}
		} else if ($band == 'All') {
			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$bindings[] = $band;
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $postdata['mode'];
			$bindings[] = $postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addOrbitToQuery($postdata,$bindings);

		// if ($postdata['includedeleted'] == NULL) {
			$sql .= " and d.end is null";
		// }

		if ($wae) {
			$sql .= ' and d.adif in ( '. $this->waecountries . ')';
		} else {
			$sql .= ' and d.adif in (' . $this->eucountries . ')';
		}

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}
}
?>
