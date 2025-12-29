<?php

class Oqrs_model extends CI_Model {

    function get_oqrs_stations($userid) {
        $this->db->where('oqrs', "1");
		$this->db->where('user_id', $userid);
		return $this->db->get('station_profile');
	}

    function get_station_info($station_id) {

		$binding = [];
        $sql = 'select
        count(1) as count,
        min(log.col_time_on) as mindate,
        max(log.col_time_on) as maxdate
        from ' . $this->config->item('table_name') . ' log inner join station_profile on (station_profile.station_id=log.station_id and station_profile.oqrs=\'1\') where log.station_id = ?';
		$binding[] = $station_id;

        $query = $this->db->query($sql, $binding);

        return $query->row();
    }

    function get_qsos($station_id, $callsign, $bands){
		$modes = $this->get_worked_modes($station_id);

		// Creating an empty array with all the bands and modes from the database
		foreach ($modes as $mode) {
			foreach ($bands as $band) {
				$resultArray[$mode][$band] = '-';
			}
		}

		// Populating array with worked band/mode combinations
		$worked = $this->getQueryData($station_id, $callsign);
		foreach ($worked as $w) {
			$resultArray[$w->col_mode][$w->col_band] = '<i class="fa fa-check" aria-hidden="true"></i>';
		}

		$result['qsocount'] = count($worked);
		$result['qsoarray'] = $resultArray;

		return $result;
	}

	/*
	 * Builds query depending on what we are searching for
	 */
	function getQueryData($station_id, $callsign) {

		$binding = [];

		$sql = 'select lower(log.col_mode) col_mode, coalesce(log.col_submode, "") col_submode, log.col_band from ' . $this->config->item('table_name') . ' log inner join station_profile on (station_profile.station_id=log.station_id and station_profile.oqrs=\'1\') where log.station_id = ? and log.col_call = ? and log.col_prop_mode != "SAT"';
		$binding[] = $station_id;
		$binding[] = $callsign;

		$sql .= ' union all select lower(log.col_mode) col_mode, coalesce(log.col_submode, "") col_submode, "SAT" col_band from ' . $this->config->item('table_name') . ' log inner join station_profile on (station_profile.station_id=log.station_id and station_profile.oqrs=\'1\') where log.station_id = ? and log.col_call = ? and log.col_prop_mode = "SAT"';
		$binding[] = $station_id;
		$binding[] = $callsign;

        $query = $this->db->query($sql, $binding);

        return $query->result();
	}

	/*
	 * Builds query depending on what we are searching for
	 */
	function getQueryDataGrouped($callsign, $userid) {

        $binding = [];

        $sql = 'select lower(col_mode) col_mode, coalesce(col_submode, "") col_submode, col_band, station_callsign, station_profile_name, l.station_id from ' . $this->config->item('table_name') . ' as l join station_profile on l.station_id = station_profile.station_id where station_profile.oqrs = "1" and l.col_call = ? and l.col_prop_mode != "SAT" and station_profile.user_id = ?';
		$binding[] = $callsign;
		$binding[] = $userid;

		$sql .= ' union all select lower(col_mode) col_mode, coalesce(col_submode, "") col_submode, "SAT" col_band, station_callsign, station_profile_name, l.station_id from ' .
			$this->config->item('table_name') . ' l' .
			' join station_profile on l.station_id = station_profile.station_id where station_profile.oqrs = "1" and col_call = ? and col_prop_mode = "SAT" and station_profile.user_id = ?';
		$binding[] = $callsign;
		$binding[] = $userid;

        $query = $this->db->query($sql, $binding);

		if ($query) {
			return $query->result();
		}

		return;
	}

	/*
	 * Get's the worked modes from the log
	 */
	function get_worked_modes($station_id)
	{
		// get all worked modes from database
		$data = $this->db->query(
			"SELECT distinct LOWER(log.`COL_MODE`) as `COL_MODE` FROM `" . $this->config->item('table_name') . "` log inner join station_profile on (station_profile.station_id=log.station_id and station_profile.oqrs='1')  WHERE log.station_id = ? order by log.COL_MODE ASC", $station_id
		);
		$results = array();
		foreach ($data->result() as $row) {
			array_push($results, $row->COL_MODE);
		}

		$data = $this->db->query(
			"SELECT distinct LOWER(log.`COL_SUBMODE`) as `COL_SUBMODE` FROM `" . $this->config->item('table_name') . "` log inner join station_profile on (station_profile.station_id=log.station_id and station_profile.oqrs='1') WHERE log.station_id = ? and coalesce(log.COL_SUBMODE, '') <> '' order by log.COL_SUBMODE ASC", $station_id
		);
		foreach ($data->result() as $row) {
			if (!in_array($row, $results)) {
				array_push($results, $row->COL_SUBMODE);
			}
		}

		return $results;
	}

	function getOqrsRequests($location_list) {
		$sql = 'select * from oqrs
				join station_profile on oqrs.station_id = station_profile.station_id
				join ' . $this->config->item('table_name') . ' as l on oqrs.qsoid = l.col_primary_key
				where oqrs.station_id in (' . $location_list . ')';

        $query = $this->db->query($sql);

        $result = $query->result();

		foreach ($result as $row) {
			if (strtolower($row->COL_QSL_SENT ?? '') == 'y') {
				$sql = 'update oqrs set status = 2 where qsoid = ? and status = 3 and requesttime < ?';
				$binding = [$row->qsoid, $row->COL_QSLSDATE];
				$query = $this->db->query($sql, $binding);
			}
		}

        $sql = 'select * from oqrs join station_profile on oqrs.station_id = station_profile.station_id where oqrs.station_id in (' . $location_list . ')';

        $query = $this->db->query($sql);

        return $query->result();
	}

	function save_oqrs_request($postdata) {
		$station_ids = array();
		$qsos = $postdata['qsos'];
		foreach($qsos as $qso) {
			$data = array(
				'date' 				=> $qso[0],
				'time'	 			=> $qso[1],
				'band' 				=> $qso[2],
				'mode' 				=> $qso[3],
				'requestcallsign' 	=> $postdata['callsign'],
				'station_id' 		=> $postdata['station_id'],
				'note' 				=> $postdata['message'],
				'email' 			=> $postdata['email'],
				'qslroute' 			=> $postdata['qslroute'],
				'status' 			=> '0',
			);

			if ($this->get_auto_queue_option($postdata['station_id']) == 'on') {
				if($this->get_direct_auto_queue_option($postdata['station_id']) == 'off' && $data['qslroute'] == 'D') {
					$data['qsoid'] = null; // Explicitly set qsoid when skipping auto-queue
				} else {
					$qsoid = $this->check_oqrs($data);

					if ($qsoid != null) {
						$data['status'] = '3';
					}
					$data['qsoid'] = $qsoid;
				}
			}

			// Check if this entry already exists in the oqrs table on the same date
			$this->db->from('oqrs');
			$this->db->where([
				'date' => $data['date'],
				'band' => $data['band'],
				'mode' => $data['mode'],
				'requestcallsign' => $data['requestcallsign'],
				'station_id' => $data['station_id']
			]);

			$exists = $this->db->get()->num_rows() > 0;

			if (!$exists) {
				$this->db->insert('oqrs', $data);
			}

			if(!in_array($postdata['station_id'], $station_ids)){
				array_push($station_ids, $postdata['station_id']);
			}
		}

		return $station_ids;
	}

	function save_oqrs_request_grouped($postdata) {
		$station_ids = array();
		$qsos = $postdata['qsos'];
		foreach ($qsos as $qso) {
			$data = array(
				'date' 				=> $qso[0],
				'time'	 			=> $qso[1],
				'band' 				=> $qso[2],
				'mode' 				=> $qso[3],
				'requestcallsign' 	=> $postdata['callsign'],
				'station_id' 		=> $qso[4],
				'note' 				=> $postdata['message'],
				'email' 			=> $postdata['email'],
				'qslroute' 			=> $postdata['qslroute'],
				'status' 			=> '0',
			);

			if ($this->get_auto_queue_option($qso[4]) == 'on') {
				if($this->get_direct_auto_queue_option($qso[4]) == 'off' && $data['qslroute'] == 'D') {
					$data['qsoid'] = null; // Explicitly set qsoid when skipping auto-queue
				} else {
					$qsoid = $this->check_oqrs($data);

					if ($qsoid != null) {
						$data['status'] = '3';
					}
					$data['qsoid'] = $qsoid;
				}
			}

			// Check if this entry already exists in the oqrs table on the same date
			$this->db->from('oqrs');
			$this->db->where([
				'date' => $data['date'],
				'band' => $data['band'],
				'mode' => $data['mode'],
				'requestcallsign' => $data['requestcallsign'],
				'station_id' => $data['station_id']
			]);

			$exists = $this->db->get()->num_rows() > 0;

			if (!$exists) {
				$this->db->insert('oqrs', $data);
			}

			if (!in_array($qso[4], $station_ids)){
				array_push($station_ids, $qso[4]);
			}
		}

		return $station_ids;
	}

	function delete_oqrs_line($id) {
		$sql = 'update ' . $this->config->item('table_name') . ' set COL_QSL_SENT = "N", COL_QSLSDATE = null, COL_QSL_SENT_VIA = ""
		where COL_PRIMARY_KEY = (select oqrs.qsoid from oqrs join station_profile on station_profile.station_id = oqrs.station_id where oqrs.id = ? and station_profile.user_id = ?)';
		$binding = [$id, $this->session->userdata('user_id')];

		$this->db->query($sql, $binding);

		$binding = [];

		$binding = [$id, $this->session->userdata('user_id')];

		$sql = '
			DELETE oqrs
			FROM oqrs
			JOIN station_profile ON station_profile.station_id = oqrs.station_id
			WHERE oqrs.id = ? AND station_profile.user_id = ?
		';

		$query = $this->db->query($sql, $binding);

		return true;
	}

	function reject_oqrs_line($id) {
		$sql = 'update ' . $this->config->item('table_name') . ' set COL_QSL_SENT = "N", COL_QSLSDATE = null, COL_QSL_SENT_VIA = ""
		where COL_PRIMARY_KEY = (select oqrs.qsoid from oqrs join station_profile on station_profile.station_id = oqrs.station_id where oqrs.id = ? and station_profile.user_id = ?)';
		$binding = [$id, $this->session->userdata('user_id')];

		$this->db->query($sql, $binding);

		$binding = [];

		$binding = [$id, $this->session->userdata('user_id')];

		$sql = 'UPDATE oqrs
			JOIN station_profile ON station_profile.station_id = oqrs.station_id
			SET oqrs.status = 4
			WHERE oqrs.id = ?
			AND station_profile.user_id = ?';

		$query = $this->db->query($sql, $binding);

		return true;
	}

	// Status:
	// 0 = open request
	// 1 = not in log request
	// 2 = request done, means we found a match in the log
	// 3 = pending
	// 4 = request rejected
	function save_not_in_log($postdata) {
		$qsos = $postdata['qsos'];
		foreach($qsos as $qso) {
			$data = array(
				'date' 				=> $qso[0],
				'time'	 			=> $qso[1],
				'band' 				=> $qso[2],
				'mode' 				=> $qso[3],
				'requestcallsign' 	=> $postdata['callsign'],
				'station_id' 		=> $postdata['station_id'],
				'note' 				=> $postdata['message'],
				'email' 			=> $postdata['email'],
				'qslroute' 			=> '',
				'status' 			=> '1',
				'qsoid' 			=> null,
			);

			$this->db->insert('oqrs', $data);
		}
	}

	function get_auto_queue_option($station_id) {
		$this->load->model('stations');
		$user = $this->stations->get_user_from_station($station_id);

		$qkey_opt = $this->user_options_model->get_options('oqrs',array('option_name'=>'oqrs_auto_matching','option_key'=>'boolean'), $user->user_id)->result();
		if (count($qkey_opt) > 0) {
			return $qkey_opt[0]->option_value;
		}

		return 'on';
	}

	function get_direct_auto_queue_option($station_id) {
		$this->load->model('stations');
		$user = $this->stations->get_user_from_station($station_id);

		$qkey_opt = $this->user_options_model->get_options('oqrs',array('option_name'=>'oqrs_direct_auto_matching','option_key'=>'boolean'), $user->user_id)->result();
		if (count($qkey_opt) > 0) {
			return $qkey_opt[0]->option_value;
		}

		return 'on';
	}

	function check_oqrs($qsodata) {
		$binding = [];

		$sql = 'select * from ' . $this->config->item('table_name') .
		' log inner join station_profile on (station_profile.station_id=log.station_id and station_profile.oqrs=\'1\')
		 where (log.col_band = ? or log.col_prop_mode = ?)
		 and log.col_call = ?
		 and date(log.col_time_on) = ?
		 and (log.col_mode = ?
		 or log.col_submode = ?)
		 and timediff(time(log.col_time_on), ?) <= 3000
		 and log.station_id = ?';

		$binding[] = $qsodata['band'];
		$binding[] = $qsodata['band'];
		$binding[] = $qsodata['requestcallsign'];
		$binding[] = $qsodata['date'];
		$binding[] = $qsodata['mode'];
		$binding[] = $qsodata['mode'];
		$binding[] = $qsodata['time'];
		$binding[] = $qsodata['station_id'];

		$query = $this->db->query($sql, $binding);

		if ($result = $query->result()) {
			$id = 0;
			foreach ($result as $qso) {
				$this->paperqsl_requested($qso->COL_PRIMARY_KEY, $qsodata['qslroute']);
				$id = $qso->COL_PRIMARY_KEY;
			}
			return $id;
		}

		return null;
	}

	function add_oqrs_to_print_queue($id) {
		$sql = 'SELECT * FROM oqrs join station_profile on oqrs.station_id = station_profile.station_id WHERE oqrs.id = ? AND station_profile.user_id = ?';
		$binding = [$id, $this->session->userdata('user_id')];
		$query = $this->db->query($sql, $binding);

		if ($query->num_rows() > 0) {
			$this->paperqsl_requested($query->row()->qsoid, $query->row()->qslroute);
		}
	}

	// Set Paper to requested
	function paperqsl_requested($qso_id, $method) {
		$data = array(
				'COL_QSLSDATE' => date('Y-m-d H:i:s'),
				'COL_QSL_SENT' => 'R',
				'COL_QSL_SENT_VIA ' => $method
		);

		$this->db->where('COL_PRIMARY_KEY', $qso_id);

		$this->db->update($this->config->item('table_name'), $data);
	}

	function search_log($callsign) {
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('oqrs', 'oqrs.qsoid = '.$this->config->item('table_name').'.COL_PRIMARY_KEY', 'left');
		// always filter user. this ensures that no inaccesible QSOs will be returned
		$this->db->where('station_profile.oqrs', '1');
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$this->db->where('COL_CALL like "%'.$callsign.'%"');
		$this->db->order_by("COL_TIME_ON", "ASC");
		$query = $this->db->get($this->config->item('table_name'));

		return $query;
	}

	function search_log_time_date($time, $date, $band, $mode) {
		$binding = [];

		$sql = 'select * from ' . $this->config->item('table_name') . ' thcv
		 join station_profile on (thcv.station_id = station_profile.station_id and station_profile.oqrs=\'1\')
		 left join oqrs on oqrs.qsoid = thcv.COL_PRIMARY_KEY
		 where date(col_time_on) = ?
		 AND TIME_TO_SEC(TIMEDIFF(TIME(col_time_on), ?)) <= 3000
		 and station_profile.user_id = ?';
		$binding[] = $date;
		$binding[] = $time;
		$binding[] = $this->session->userdata('user_id');

		return $this->db->query($sql, $binding);
	}

	function mark_oqrs_line_as_done($id) {
		$data = array(
			'status' => '2',
	   );

	   $this->db->where('id', $id);

	   $this->db->update('oqrs', $data);
	}

	function getQslInfo($station_id) {
		$binding = [];
		$sql = 'select oqrs_text from station_profile where station_id = ? and oqrs=\'1\'';
		$binding[] = $station_id;

		$query = $this->db->query($sql, $binding);

		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			return $row->oqrs_text;
		}

		return '';
	}

	function getOqrsEmailSetting($station_id) {
		$binding = [];
		$sql = 'select oqrs_email from station_profile where station_id = ? and oqrs=\'1\'';
		$binding[] = $station_id;

		$query = $this->db->query($sql, $binding);

		if ($query->num_rows() > 0)
		{
			$row = $query->row();
			return $row->oqrs_email;
		}

		return '';
	}

	/*
   * @param array $searchCriteria
   * @return array
   */
  public function searchOqrs($searchCriteria) : array {
		$conditions = [];
		$binding = [$searchCriteria['user_id']];

		if ($searchCriteria['de'] !== '') {
			$conditions[] = "station_profile.station_id = ?";
			$binding[] = trim($searchCriteria['de']);
		}
		if ($searchCriteria['dx'] !== '') {
			$conditions[] = "oqrs.requestcallsign LIKE ?";
			$binding[] = '%' . trim($searchCriteria['dx']) . '%';
		}
		if ($searchCriteria['status'] !== '') {
			$conditions[] = "oqrs.status = ?";
			$binding[] = $searchCriteria['status'];
		}

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}


		$sql = "
		SELECT *, DATE_FORMAT(requesttime, \"%Y-%m-%d %H:%i\") as requesttime, DATE_FORMAT(time, \"%H:%i\") as time
		FROM oqrs
		INNER JOIN station_profile ON (oqrs.station_id=station_profile.station_id and station_profile.oqrs='1')
		WHERE station_profile.user_id =  ?
		$where
		ORDER BY oqrs.id
		";

		if ($searchCriteria['oqrsResults'] !== 'All') {
			$limit = max(1, min(1000, intval($searchCriteria['oqrsResults']))); // Sanitize and enforce max
			$sql .= " LIMIT " . $limit;
		}

		$data = $this->db->query($sql, $binding);

		return $data->result('array');
	}

	public function oqrs_requests($location_list) {
		if ($location_list != "") {
			$sql = 'SELECT status, COUNT(*) AS number FROM oqrs JOIN station_profile ON oqrs.station_id = station_profile.station_id WHERE oqrs.station_id IN ('.$location_list.') GROUP BY status';
			$query = $this->db->query($sql);
			$sum = 0;
			$open = 0;
			foreach ($query->result_array() as $row) {
				$sum += $row['number'];
				if ($row['status'] == 1) {
					$open += $row['number'];
				}
			}
			if ($open == 0 && $sum == 0) {
				return 0;
			} else {
				return $open."/".$sum;
			}
		} else {
			return 0;
		}
	}

	function getOqrsStationsFromSlug($logbook_id) {
		$binding = [];
		$sql = 'SELECT station_callsign FROM `station_logbooks_relationship` JOIN `station_profile` ON station_logbooks_relationship.station_location_id = station_profile.station_id WHERE station_profile.oqrs = 1 AND station_logbook_id = ?;';
		$binding[] = $logbook_id;

		$query = $this->db->query($sql, $binding);

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function oqrs_enabled($slug) {
		if ($slug) {
			$this->load->model('Logbooks_model');
			$logbook_id = $this->Logbooks_model->public_slug_exists_logbook_id($slug);
			if (!empty($this->getOqrsStationsFromSlug($logbook_id))) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function delete_oqrs_qso_match($id, $qsoid) {
		// Step 1: Check if this QSO belongs to the current user
		$this->db->select('oqrs.id');
		$this->db->from('oqrs');
		$this->db->join('station_profile', 'station_profile.station_id = oqrs.station_id');
		$this->db->where('oqrs.id', $id);
		$this->db->where('oqrs.qsoid', $qsoid);
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$query = $this->db->get();

		if ($query->num_rows() === 0) {
			return false; // Not authorized or not found
		}

		// Step 2: Update if authorized
		$this->db->where('id', $id);
		$this->db->update('oqrs', ['qsoid' => null]);

		return $this->db->affected_rows() > 0;
	}

	public function add_qso_match_to_oqrs($qsoid, $oqrsid) {
		// Step 1: Check if this QSO belongs to the current user
		$this->db->select('oqrs.id');
		$this->db->from('oqrs');
		$this->db->join('station_profile', 'station_profile.station_id = oqrs.station_id');
		$this->db->where('oqrs.id', $oqrsid);
		$this->db->where('oqrs.qsoid IS NULL', null, false); // Ensure we are adding a match to an empty qsoid
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$query = $this->db->get();

		if ($query->num_rows() === 0) {
			return false; // Not authorized or not found
		}

		// Step 2: Update if authorized
		$this->db->where('id', $oqrsid);
		$this->db->update('oqrs', ['qsoid' => $qsoid]);

		return $this->db->affected_rows() > 0;
	}

}
