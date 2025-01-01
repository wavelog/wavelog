<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Timeline_model extends CI_Model {
	function get_timeline($band, $mode, $propmode, $award, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew)  {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		switch ($award) {
		case 'dxcc': $result = $this->get_timeline_dxcc($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew); break;
		case 'was':  $result = $this->get_timeline_was($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew);  break;
		case 'iota': $result = $this->get_timeline_iota($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew); break;
		case 'waz':  $result = $this->get_timeline_waz($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew);  break;
		case 'vucc':  $result = $this->get_timeline_vucc($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew);  break;
		case 'waja':  $result = $this->get_timeline_waja($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew);  break;
		}

		return $result;
	}

	public function get_timeline_dxcc($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew) {
		$binding = [];
		$sql = "select min(date(COL_TIME_ON)) date, prefix, col_country, end, adif from "
			.$this->config->item('table_name'). " thcv
			join dxcc_entities on thcv.col_dxcc = dxcc_entities.adif
			where station_id in (" . $location_list . ") and col_dxcc > 0 ";

		if ($band == 'SAT') {				// Left for compatibility reasons
			$sql .= " and col_prop_mode = ?";
			$binding[] = $band;
		} else {					// Not SAT
			if ($band != 'All') {			// Band set? Take care of it
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
			if ( $propmode == 'NoSAT' ) {		// All without SAT
				$sql .= " and col_prop_mode !='SAT'";
			} elseif ($propmode == 'None') {	// Empty Propmode
				$sql .= " and (trim(col_prop_mode)='' or col_prop_mode is null)";
			} elseif ($propmode == 'All') {		// Dont care for propmode
				; // No Prop-Filter
			} else {				// Propmode set, take care of it
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propmode;
			}
		}

		if ($year != "All" && $onlynew == 0) {
			$sql .= " and year(col_time_on) = ?";
			$binding[] = $year;
		}

		if ($mode != 'All') {
			$sql .= " and col_mode = ?";
			$binding[] = $mode;
		}

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $clublog, $qrz);

		$sql .= " group by col_dxcc, col_country
			order by date desc";

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	public function get_timeline_waja($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew) {
		$binding = [];
		$sql = "select min(date(COL_TIME_ON)) date, col_state from "
			.$this->config->item('table_name'). " thcv
			where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {				// Left for compatibility reasons
			$sql .= " and col_prop_mode = ?";
			$binding[] = $band;
		} else {					// Not SAT
			if ($band != 'All') {			// Band set? Take care of it
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
			if ( $propmode == 'NoSAT' ) {		// All without SAT
				$sql .= " and col_prop_mode !='SAT'";
			} elseif ($propmode == 'None') {	// Empty Propmode
				$sql .= " and (trim(col_prop_mode)='' or col_prop_mode is null)";
			} elseif ($propmode == 'All') {		// Dont care for propmode
				; // No Prop-Filter
			} else {				// Propmode set, take care of it
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propmode;
			}
		}


		if ($mode != 'All') {
			$sql .= " and col_mode = ?";
			$binding[] = $mode;
		}

		if ($year != "All" && $onlynew == 0) {
			$sql .= " and year(col_time_on) = ?";
			$binding[] = $year;
		}

		$sql .= " and COL_DXCC = '339' and trim(coalesce(COL_STATE,'')) != '' ";

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $clublog, $qrz);

		$sql .= " group by col_state
			order by date desc";

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	public function get_timeline_was($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew) {
		$binding = [];
		$sql = "select min(date(COL_TIME_ON)) date, col_state from "
			.$this->config->item('table_name'). " thcv
			where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {				// Left for compatibility reasons
			$sql .= " and col_prop_mode = ?";
			$binding[] = $band;
		} else {					// Not SAT
			if ($band != 'All') {			// Band set? Take care of it
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
			if ( $propmode == 'NoSAT' ) {		// All without SAT
				$sql .= " and col_prop_mode !='SAT'";
			} elseif ($propmode == 'None') {	// Empty Propmode
				$sql .= " and (trim(col_prop_mode)='' or col_prop_mode is null)";
			} elseif ($propmode == 'All') {		// Dont care for propmode
				; // No Prop-Filter
			} else {				// Propmode set, take care of it
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propmode;
			}
		}

		if ($mode != 'All') {
			$sql .= " and col_mode = ?";
			$binding[] = $mode;
		}

		if ($year != "All" && $onlynew == 0) {
			$sql .= " and year(col_time_on) = ?";
			$binding[] = $year;
		}

		$sql .= " and COL_DXCC in ('291', '6', '110')";
		$sql .= " and COL_STATE in ('AK','AL','AR','AZ','CA','CO','CT','DE','FL','GA','HI','IA','ID','IL','IN','KS','KY','LA','MA','MD','ME','MI','MN','MO','MS','MT','NC','ND','NE','NH','NJ','NM','NV','NY','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VA','VT','WA','WI','WV','WY')";

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $clublog, $qrz);

		$sql .= " group by col_state
			order by date desc";

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	public function get_timeline_iota($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew) {
		$binding = [];
		$sql = "select min(date(COL_TIME_ON)) date,  col_iota, name, prefix from "
			.$this->config->item('table_name'). " thcv
			join iota on thcv.col_iota = iota.tag
			where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {				// Left for compatibility reasons
			$sql .= " and col_prop_mode = ?";
			$binding[] = $band;
		} else {					// Not SAT
			if ($band != 'All') {			// Band set? Take care of it
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
			if ( $propmode == 'NoSAT' ) {		// All without SAT
				$sql .= " and col_prop_mode !='SAT'";
			} elseif ($propmode == 'None') {	// Empty Propmode
				$sql .= " and (trim(col_prop_mode)='' or col_prop_mode is null)";
			} elseif ($propmode == 'All') {		// Dont care for propmode
				; // No Prop-Filter
			} else {				// Propmode set, take care of it
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propmode;
			}
		}


		if ($mode != 'All') {
			$sql .= " and col_mode = ?";
			$binding[] = $mode;
		}

		if ($year != "All" && $onlynew == 0) {
			$sql .= " and year(col_time_on) = ?";
			$binding[] = $year;
		}

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $clublog, $qrz);

		$sql .= " and col_iota <> '' group by col_iota, name, prefix
			order by date desc";

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	public function get_timeline_waz($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew) {
		$binding = [];
		$sql = "select min(date(COL_TIME_ON)) date, col_cqz from "
			.$this->config->item('table_name'). " thcv
			where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {				// Left for compatibility reasons
			$sql .= " and col_prop_mode = ?";
			$binding[] = $band;
		} else {					// Not SAT
			if ($band != 'All') {			// Band set? Take care of it
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
			if ( $propmode == 'NoSAT' ) {		// All without SAT
				$sql .= " and col_prop_mode !='SAT'";
			} elseif ($propmode == 'None') {	// Empty Propmode
				$sql .= " and (trim(col_prop_mode)='' or col_prop_mode is null)";
			} elseif ($propmode == 'All') {		// Dont care for propmode
				; // No Prop-Filter
			} else {				// Propmode set, take care of it
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propmode;
			}
		}


		if ($mode != 'All') {
			$sql .= " and col_mode = ?";
			$binding[] = $mode;
		}

		if ($year != "All" && $onlynew == 0) {
			$sql .= " and year(col_time_on) = ?";
			$binding[] = $year;
		}

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $clublog, $qrz);

		$sql .= " and col_cqz <> '' group by col_cqz
			order by date desc";

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}


	// Adds confirmation to query
	function addQslToQuery($qsl, $lotw, $eqsl, $clublog, $qrz) {
		$sql = '';
		if ( (($lotw ?? 0) != 0) || (($qsl ?? 0) != 0) || (($eqsl ?? 0) != 0) || (($clublog ?? 0) != 0) || (($qrz ?? 0) != 0)) {
			$sql .= 'and (';

			if ($lotw ?? 0 == 1) {
				$sql .= "col_lotw_qsl_rcvd = 'Y' or";
			}

			if ($qsl ?? 0 == 1) {
				$sql .= " col_qsl_rcvd = 'Y' or";
			}

			if ($eqsl ?? 0 == 1) {
				$sql .= " col_eqsl_qsl_rcvd = 'Y' or";
			}

			if ($clublog ?? 0 == 1) {
				$sql .= " col_clublog_qso_download_status = 'Y' or";
			}
			if ($qrz ?? 0 == 1) {
				$sql .= " col_qrzcom_qso_download_status = 'Y' or";
			}

			$sql.=' 1=0)';
		}
		return $sql;
	}

	public function timeline_qso_details($querystring, $band, $propmode, $mode, $type){
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$this->db->select('dxcc_entities.adif, lotw_users.callsign, COL_BAND, COL_CALL, COL_CLUBLOG_QSO_DOWNLOAD_DATE,
			COL_CLUBLOG_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_UPLOAD_DATE, COL_CLUBLOG_QSO_UPLOAD_STATUS, COL_CONTEST_ID,
			COL_DISTANCE, COL_EQSL_QSL_RCVD, COL_EQSL_QSLRDATE, COL_EQSL_QSLSDATE, COL_EQSL_QSL_SENT, COL_FREQ,
			COL_GRIDSQUARE, COL_IOTA, COL_LOTW_QSL_RCVD, COL_LOTW_QSLRDATE, COL_LOTW_QSLSDATE, COL_LOTW_QSL_SENT,
			COL_MODE, COL_NAME, COL_OPERATOR, COL_POTA_REF, COL_PRIMARY_KEY, COL_QRZCOM_QSO_DOWNLOAD_DATE,
			COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_QRZCOM_QSO_UPLOAD_DATE, COL_QRZCOM_QSO_UPLOAD_STATUS, COL_QSL_RCVD,
			COL_QSL_RCVD_VIA, COL_QSLRDATE, COL_QSLSDATE, COL_QSL_SENT, COL_QSL_SENT_VIA, COL_QSL_VIA, COL_RST_RCVD,
			COL_RST_SENT, COL_SAT_NAME, COL_SOTA_REF, COL_SRX, COL_SRX_STRING, COL_STATE, COL_STX, COL_STX_STRING,
			COL_SUBMODE, COL_TIME_ON, COL_VUCC_GRIDS, COL_WWFF_REF, dxcc_entities.end, lotw_users.lastupload,
			dxcc_entities.name, satellite.displayname AS sat_displayname, station_profile.station_callsign,
			station_profile.station_gridsquare, station_profile.station_profile_name');
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'dxcc_entities.adif = '.$this->config->item('table_name').'.COL_DXCC', 'left outer');
		$this->db->join('lotw_users', 'lotw_users.callsign = '.$this->config->item('table_name').'.col_call', 'left outer');
		$this->db->join('satellite', 'satellite.name = '.$this->config->item('table_name').'.COL_SAT_NAME', 'left outer');

		if ($band == 'SAT') {				// Left for compatibility reasons
			$this->db->where('col_prop_mode', $band);
		} else {					// Not SAT
			if ($band != 'All') {			// Band set? Take care of it
				$this->db->where('col_band', $band);
			}
			if ( $propmode == 'NoSAT' ) {		// All without SAT
				$this->db->where('col_prop_mode !=', 'SAT');
			} elseif ($propmode == 'None') {	// Empty Propmode
				$this->db->group_start();
				$this->db->where('trim(col_prop_mode)', '');
				$this->db->or_where('col_prop_mode is null');
				$this->db->group_end();
			} elseif ($propmode == 'All') {		// Dont care for propmode
				; // No Prop-Filter
			} else {				// Propmode set, take care of it
				$this->db->where('col_prop_mode', $propmode);
			}
		}

		if ($mode != 'All') {
			$this->db->where('col_mode', $mode);
		}

		$this->db->where_in('station_profile.station_id', $logbooks_locations_array);

		switch($type) {
			case 'dxcc': $this->db->where('COL_DXCC', $querystring); break;
			case 'was':  $this->db->where('COL_STATE', $querystring); $this->db->where("COL_DXCC in ('291', '6', '110')"); break;
			case 'iota': $this->db->where('COL_IOTA', $querystring); break;
			case 'waz':  $this->db->where('COL_CQZ', $querystring); break;
			case 'vucc':  $this->db->group_start(); $this->db->like('COL_GRIDSQUARE', $querystring);  $this->db->or_like('COL_VUCC_GRIDS',$querystring); $this->db->group_end();break;
		case 'waja':  $this->db->where('COL_STATE', $querystring); $this->db->where('COL_DXCC','339'); break;
		}
		$this->db->order_by('COL_TIME_ON', 'DESC');

		return $this->db->get($this->config->item('table_name'));
	}

	public function get_timeline_vucc($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew) {
		$timeline = array();

		$col_gridsquare = $this->get_gridsquare($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew);

		foreach ($col_gridsquare as $grid) {
			$timeline[] = array(
				'gridsquare' => $grid->gridsquare,
				'date'       => $grid->date);
		}

		$col_vucc_grids = $this->get_vucc_grids($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew);

		foreach ($col_vucc_grids as $gridSplit) {
			$grids = explode(",", $gridSplit->gridsquare);
			foreach($grids as $key) {
				$grid_four = strtoupper(substr(trim($key),0,4));
				if (!array_search($grid_four, array_column($timeline, 'gridsquare'))) {
					$timeline[] = array(
						'gridsquare' => $grid_four,
						'date'       => $gridSplit->date);
				}
			}
		}
		usort($timeline, function($a, $b) {
			return $b['date'] <=> $a['date'];
		});

		return $timeline;
	}

	public function get_gridsquare($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew) {
		$binding = [];
		$sql = "select min(COL_TIME_ON) date, upper(substring(col_gridsquare, 1, 4)) gridsquare from "
			.$this->config->item('table_name'). " thcv
			where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {				// Left for compatibility reasons
			$sql .= " and col_prop_mode = ?";
			$binding[] = $band;
		} else {					// Not SAT
			if ($band != 'All') {			// Band set? Take care of it
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
			if ( $propmode == 'NoSAT' ) {		// All without SAT
				$sql .= " and col_prop_mode !='SAT'";
			} elseif ($propmode == 'None') {	// Empty Propmode
				$sql .= " and (trim(col_prop_mode)='' or col_prop_mode is null)";
			} elseif ($propmode == 'All') {		// Dont care for propmode
				; // No Prop-Filter
			} else {				// Propmode set, take care of it
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propmode;
			}
		}

		if ($mode != 'All') {
			$sql .= " and col_mode = ?";
			$binding[] = $mode;
		}

		if ($year != "All" && $onlynew == 0) {
			$sql .= " and year(col_time_on) = ?";
			$binding[] = $year;
		}

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $clublog, $qrz);

		$sql .= " and col_gridsquare <> '' group by upper(substring(col_gridsquare, 1, 4))
			order by date desc";

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	public function get_vucc_grids($band, $mode, $propmode, $location_list, $qsl, $lotw, $eqsl, $clublog, $year, $qrz, $onlynew) {
		$binding = [];
		$sql = "select COL_TIME_ON as date, upper(col_vucc_grids) gridsquare from "
			.$this->config->item('table_name'). " thcv
			where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {				// Left for compatibility reasons
			$sql .= " and col_prop_mode = ?";
			$binding[] = $band;
		} else {					// Not SAT
			if ($band != 'All') {			// Band set? Take care of it
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
			if ( $propmode == 'NoSAT' ) {		// All without SAT
				$sql .= " and col_prop_mode !='SAT'";
			} elseif ($propmode == 'None') {	// Empty Propmode
				$sql .= " and (trim(col_prop_mode)='' or col_prop_mode is null)";
			} elseif ($propmode == 'All') {		// Dont care for propmode
				; // No Prop-Filter
			} else {				// Propmode set, take care of it
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propmode;
			}
		}

		if ($mode != 'All') {
			$sql .= " and col_mode = ?";
			$binding[] = $mode;
		}

		if ($year != "All" && $onlynew == 0) {
			$sql .= " and year(col_time_on) = ?";
			$binding[] = $year;
		}

		$sql .= $this->addQslToQuery($qsl, $lotw, $eqsl, $clublog, $qrz);

		$sql .= " and col_vucc_grids <> ''";

		$query = $this->db->query($sql, $binding);

		return $query->result();
	}

	function get_years() {
		$this->load->model('logbook_model');
		$totals_year = $this->logbook_model->totals_year();
		$years=[];
		if ($totals_year) {
			foreach($totals_year->result() as $years_obj) {
				$years[] = $years_obj->year;
			}
		}
		return $years;
	}

}
