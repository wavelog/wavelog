<?php
class Contesting_model extends CI_Model {

	/*
	 * This function gets the QSOs to fill the "Contest Logbook" under the contesting form.
	 */
	function getSessionQsos($qso) {
		$this->load->model('Stations');
		$station_id = $this->Stations->find_active();

		$qsoarray = explode(',', $this->security->xss_clean($qso));

		$contestid = $qsoarray[2];
		$date = DateTime::createFromFormat('d-m-Y H:i:s', $qsoarray[0]);
		if ($date == false) $date = DateTime::createFromFormat('d-m-Y H:i', $qsoarray[0]);
		$date = $date->format('Y-m-d H:i:s');

		$sql = "SELECT date_format(col_time_on, '%d-%m-%Y %H:%i:%s') as col_time_on, col_call, col_band, col_mode,
			col_submode, col_rst_sent, col_rst_rcvd, coalesce(col_srx, '') col_srx, coalesce(col_srx_string, '') col_srx_string,
			coalesce(col_stx, '') col_stx, coalesce(col_stx_string, '') col_stx_string, coalesce(col_gridsquare, '') col_gridsquare,
			coalesce(col_vucc_grids, '') col_vucc_grids FROM " .
			$this->config->item('table_name') .
			" WHERE station_id =  ?  AND COL_TIME_ON >= ? AND COL_CONTEST_ID = ? ORDER BY COL_PRIMARY_KEY ASC";

		$data = $this->db->query($sql, array($station_id, $date, $contestid));
		return $data->result();
	}

	function getSession() {
		$this->load->model('Stations');
		$station_id = $this->Stations->find_active();

		$binding = [];
		$sql = "SELECT * from contest_session where station_id = ?";
		$binding[] = $station_id;

		$data = $this->db->query($sql, $binding);
		return $data->row();
	}



	function deleteSession() {
		$this->load->model('Stations');
		$station_id = $this->Stations->find_active();

		$binding = [];
		$sql = "delete from contest_session where station_id = ?";
		$binding[] = $station_id;

		$this->db->query($sql, $binding);
		return;
	}

	function setSession() {
		$this->load->model('Stations');
		$station_id = $this->Stations->find_active();

		$qso = "";

		if ($this->input->post('callsign', true) ?? '' != '') {
			$qso = $this->input->post('start_date', true) . ' ' . $this->input->post('start_time', true) . ',' . $this->input->post('callsign', true) . ',' . $this->input->post('contestname', true);
		} else {
			$qso = $this->input->post('start_date', true) . ' ' . $this->input->post('start_time', true) . ',,' . $this->input->post('contestname', true);
		}

		$settings = array(
			'exchangetype' 			=> $this->input->post('exchangetype', true),
			'exchangesequence' 		=> $this->input->post('exchangesequence_select', true),
			'copyexchangeto'		=> $this->input->post('copyexchangeto', true) == "" ? 0 : $this->input->post('copyexchangeto', true),
			'radio'					=> $this->input->post('radio', true),
			'freq_display'			=> $this->input->post('freq_display', true),
			'mode'					=> $this->input->post('mode', true),
			'band'					=> $this->input->post('band', true),
		);

		$data = array(
			'contestid' 			=> $this->input->post('contestname', true),
			'exchangesent' 			=> $this->input->post('exch_sent', true),
			'serialsent' 			=> $this->input->post('exch_serial_s', true),
			'qso' 					=> $qso,
			'station_id' 			=> $station_id,
			'settings' 				=> json_encode($settings),
		);

		$binding = [];
		$sql = "SELECT * from contest_session where station_id = ?";
		$binding[] = $station_id;

		$querydata = $this->db->query($sql, $binding);

		if ($querydata->num_rows() == 0) {
			$this->db->insert('contest_session', $data);
			return;
		}

		$result = $querydata->row();
		$qsoarray = explode(',', $result->qso);
		if ($qsoarray[1] != "") {
			$data['qso'] = $result->qso;
		}

		/**
		 * catch the case if the user already logged a QSO with contest a and then switches to contest b 
		 * this case is similar to a new session, therefore we need to reset the qso list.
		 * Anyway we try to catch this case by disabling the contest field in the form if the user already logged a QSO.
		 * Only "Start a new contest session" is allowed to change the contest. So this is just the fallback.
		 */
		if ($qsoarray[2] != $this->input->post('contestname', true)) {
			$data['qso'] = $qso;
		}

		$this->updateSession($data, $station_id);

		return;
	}

	function updateSession($data, $station_id) {
		$this->db->where('station_id', $station_id);

		$this->db->update('contest_session', $data);
	}

	function getActivecontests() {

		$sql = "SELECT name, adifname FROM contest WHERE active = 1 ORDER BY name ASC";

		$data = $this->db->query($sql);

		return ($data->result_array());
	}

	function getAllContests() {

		$sql = "SELECT id, name, adifname, active FROM contest ORDER BY name ASC";

		$data = $this->db->query($sql);

		return ($data->result_array());
	}

	function delete($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Contest
		$this->db->delete('contest', array('id' => $clean_id));
	}

	function activate($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$data = array(
			'active' => '1',
		);

		$this->db->where('id', $clean_id);

		$this->db->update('contest', $data);

		return true;
	}

	function deactivate($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$data = array(
			'active' => '0',
		);

		$this->db->where('id', $clean_id);

		$this->db->update('contest', $data);

		return true;
	}

	function add() {
		$data = array(
			'name' => $this->input->post('name', true),
			'adifname' => $this->input->post('adifname', true),
		);

		$this->db->insert('contest', $data);
	}

	function contest($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$binding = [];
		$sql = "SELECT id, name, adifname, active FROM contest where id = ?";
		$binding[] = $clean_id;

		$data = $this->db->query($sql, $binding);

		return ($data->row());
	}

	function edit($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$data = array(
			'name' => $this->input->post('name', true),
			'adifname' => $this->input->post('adifname', true),
			'active' =>  $this->input->post('active', true),
		);

		$this->db->where('id', $clean_id);
		$this->db->update('contest', $data);
	}

	function activateall() {
		$data = array(
			'active' => '1',
		);

		$this->db->update('contest', $data);

		return true;
	}

	function deactivateall() {
		$data = array(
			'active' => '0',
		);

		$this->db->update('contest', $data);

		return true;
	}

	function checkIfWorkedBefore($call, $band, $mode, $contest) {
		$this->load->model('Stations');
		$station_id = $this->Stations->find_active();

		$contest_session = $this->getSession();

		if ($contest_session && $contest_session->qso != "") {
			$qsoarray = explode(',', $contest_session->qso);

			$date = DateTime::createFromFormat('d-m-Y H:i:s', $qsoarray[0]);
			if ($date == false) $date = DateTime::createFromFormat('d-m-Y H:i', $qsoarray[0]);
			$date = $date->format('Y-m-d H:i:s');

			$this->db->select('timediff(UTC_TIMESTAMP(),col_time_off) b4, COL_TIME_OFF');
			$this->db->where('STATION_ID', $station_id);
			$this->db->where('COL_CALL', xss_clean($call));
			$this->db->where("COL_BAND", xss_clean($band));
			$this->db->where("COL_CONTEST_ID", xss_clean($contest));
			$this->db->where("COL_TIME_ON >=", $date);
			$this->db->group_start();
			$this->db->where("COL_MODE", xss_clean($mode));
			$this->db->or_where("COL_SUBMODE", xss_clean($mode));
			$this->db->group_end();
			$this->db->order_by($this->config->item('table_name') . ".COL_TIME_ON", "DESC");
			$query = $this->db->get($this->config->item('table_name'));

			return $query;
		}
		return;
	}

	function export_custom($from, $to, $contest_id, $station_id, $band = null) {
		$this->db->select('' . $this->config->item('table_name') . '.*, station_profile.*');
		$this->db->from($this->config->item('table_name'));
		$this->db->where($this->config->item('table_name') . '.station_id', $station_id);

		// If date is set, we format the date and add it to the where-statement
		if ($from != 0) {
			$from = DateTime::createFromFormat('Y-m-d', $this->security->xss_clean($from));
			$from = $from->format('Y-m-d');
			$this->db->where("date(" . $this->config->item('table_name') . ".COL_TIME_ON) >= '" . $from . "'");
		}
		if ($to != 0) {
			$to = DateTime::createFromFormat('Y-m-d', $this->security->xss_clean($to));
			$to = $to->format('Y-m-d');
			$this->db->where("date(" . $this->config->item('table_name') . ".COL_TIME_ON) <= '" . $to . "'");
		}

		// If band is set, we only load contacts for that band
		if ($band != null) {
			$this->db->where($this->config->item('table_name') . '.COL_BAND', $band);
		}

		$this->db->where($this->config->item('table_name') . '.COL_CONTEST_ID', $this->security->xss_clean($contest_id));

		$this->db->order_by($this->config->item('table_name') . ".COL_TIME_ON", "ASC");

		$this->db->join('station_profile', 'station_profile.station_id = ' . $this->config->item('table_name') . '.station_id');

		return $this->db->get();
	}

	function get_logged_contests2() {
		$this->load->model('Stations');
		$station_id = $this->Stations->find_active();

		$binding = [];
		$sql = "select col_contest_id, min(date(col_time_on)) mindate, max(date(col_time_on)) maxdate, year(col_time_on) year, month(col_time_on) month
			from " . $this->config->item('table_name') . "
			where coalesce(COL_CONTEST_ID, '') <> ''
			and station_id = ?";

		$binding[] = $station_id;

		$sql .= " group by COL_CONTEST_ID , year(col_time_on), month(col_time_on) order by year(col_time_on) desc";

		$data = $this->db->query($sql, $binding);

		return ($data->result());
	}

	function get_logged_years($station_id) {

		$station_id = $this->security->xss_clean($station_id);

		$binding = [];
		$sql = "select distinct year(col_time_on) year
			from " . $this->config->item('table_name') . "
			where coalesce(COL_CONTEST_ID, '') <> ''
			and station_id = ?";

		$binding[] = $station_id;

		$sql .= " order by year(col_time_on) desc";

		$data = $this->db->query($sql, $binding);

		return $data->result();
	}

	function get_logged_contests($station_id, $year) {

		$station_id = $this->security->xss_clean($station_id);
		$year = $this->security->xss_clean($year);

		$binding = [];
		$sql = "select distinct col_contest_id, coalesce(contest.name, col_contest_id) contestname
			from " . $this->config->item('table_name') . " thcv
			left outer join contest on thcv.col_contest_id = contest.adifname
			where coalesce(COL_CONTEST_ID, '') <> ''
			and station_id = ?" .
			" and year(col_time_on) = ?";

		$binding[] = $station_id;
		$binding[] = $year;

		$sql .= " order by COL_CONTEST_ID asc";

		$data = $this->db->query($sql, $binding);

		return $data->result();
	}

	function get_contest_dates($station_id, $year, $contestid) {

		$station_id = $this->security->xss_clean($station_id);
		$year = $this->security->xss_clean($year);
		$contestid = $this->security->xss_clean($contestid);

		$binding = [];
		$sql = "select distinct (date(col_time_on)) date
			from " . $this->config->item('table_name') . "
			where coalesce(COL_CONTEST_ID, '') <> ''
			and station_id = ?" .
			" and year(col_time_on) = ? and col_contest_id = ?";

		$binding[] = $station_id;
		$binding[] = $year;
		$binding[] = $contestid;

		$data = $this->db->query($sql, $binding);

		return $data->result();
	}

	function get_contest_bands($station_id, $contestid, $from, $to) {

		//get distinct bands for the selected timeframe	
		$binding = [];
		$sql = "select distinct COL_BAND band
			from " . $this->config->item('table_name') . "
			where date(" . $this->config->item('table_name') . ".COL_TIME_ON) >= ?
			and date(" . $this->config->item('table_name') . ".COL_TIME_ON) <= ?
			and station_id = ? and COL_CONTEST_ID = ?";

		//add data to bindings
		$binding[] = $from;
		$binding[] = $to;
		$binding[] = $station_id;
		$binding[] = $contestid;

		//get database result
		$data = $this->db->query($sql, $binding);

		//return data
		return $data->result();
	}
}
