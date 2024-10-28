<?php

class Visitor_model extends CI_Model {

	function get_qsos($num, $StationLocationsArray, $band = '', $continent = '') {
		$this->db->select($this->config->item('table_name').'.*, station_profile.*');
		$this->db->from($this->config->item('table_name'));

		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');

		if ($band != '') {
			if ($band == 'SAT') {
				$this->db->where($this->config->item('table_name').'.col_prop_mode', 'SAT');
			} else {
				$this->db->where($this->config->item('table_name').'.col_prop_mode !="SAT"');
				$this->db->where($this->config->item('table_name').'.col_band', $band);
			}
		}

		if ($continent != '') {
			$this->db->where($this->config->item('table_name').'.COL_CONT', $continent);
		}

		$this->db->group_start();
		$this->db->where("(" . $this->config->item('table_name') . ".COL_GRIDSQUARE != '' AND " . $this->config->item('table_name') . ".COL_GRIDSQUARE IS NOT NULL)");
		$this->db->or_where("(" . $this->config->item('table_name') . ".COL_VUCC_GRIDS != '' AND " . $this->config->item('table_name') . ".COL_VUCC_GRIDS IS NOT NULL)");
		$this->db->group_end();

		$this->db->where_in($this->config->item('table_name').'.station_id', $StationLocationsArray);
		$this->db->order_by(''.$this->config->item('table_name').'.COL_TIME_ON', "desc");

		$this->db->limit($num);

		return $this->db->get();
	}

	function getlastqsodate ($slug) {
		$this->load->model('stationsetup_model');
        $logbook_id = $this->stationsetup_model->public_slug_exists_logbook_id($slug);
		$userid = $this->stationsetup_model->public_slug_exists_userid($slug);
		$band = $this->user_options_model->get_options('ExportMapOptions',array('option_name'=>'band','option_key'=>$slug), $userid)->row()->option_value ?? '';

		$sql = "select max(col_time_on) lastqso from " . $this->config->item('table_name') .
		" join station_profile on station_profile.station_id = " . $this->config->item('table_name') . ".station_id where 1 = 1";

		if ($band != '') {
			if ($band == 'SAT') {
				$sql .= " and " . $this->config->item('table_name') . ".col_prop_mode = 'SAT'";
			} else {
				$sql .= " and " . $this->config->item('table_name') . ".col_prop_mode != 'SAT'";
				$sql .= " and " . $this->config->item('table_name') . ".col_band = '". $band . "'";
			}
		}

		return $this->db->query($sql);
	}

	function qso_is_confirmed($qso, $user_default_confirmation) {
		$confirmed = false;
		$qso = (array) $qso;
		if (strpos($user_default_confirmation, 'Q') !== false) { // QSL
			if ($qso['COL_QSL_RCVD']=='Y') { $confirmed = true; }
		}
		if (strpos($user_default_confirmation, 'L') !== false) { // LoTW
			if ($qso['COL_LOTW_QSL_RCVD']=='Y') { $confirmed = true; }
		}
		if (strpos($user_default_confirmation, 'E') !== false) { // eQsl
			if ($qso['COL_EQSL_QSL_RCVD']=='Y') { $confirmed = true; }
		}
		if (strpos($user_default_confirmation, 'Z') !== false) { // QRZ
			if ($qso['COL_QRZCOM_QSO_DOWNLOAD_STATUS']=='Y') { $confirmed = true; }
		}
		if (strpos($user_default_confirmation, 'C') !== false) { // Clublog
			if ($qso['COL_CLUBLOG_QSO_DOWNLOAD_STATUS']=='Y') { $confirmed = true; }
		}
		return $confirmed;
	}

	function get_user_default_confirmation($userid) {
		$this->load->model('user_model');
		return $this->user_model->get_by_id($userid)->row()->user_default_confirmation ?? '';
	}
}
