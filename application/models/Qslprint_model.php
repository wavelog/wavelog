<?php

class Qslprint_model extends CI_Model {

	function mark_qsos_printed($station_id2 = NULL) {
		$this->load->model('Stations');
		$station_ids = array();

		if ($station_id2 == NULL) {
			$station_id = $this->Stations->find_active();
			array_push($station_ids, $station_id);
		} else if ($station_id2 == 'All') {
			// get all stations of user
			$stations = $this->Stations->all_of_user();
			$station_ids = array();
			foreach ($stations->result() as $row) {
				array_push($station_ids, $row->station_id);
			}
		} else {
			// be sure that station belongs to user
			if (!$this->Stations->check_station_is_accessible($station_id2)) {
				return;
			}
			array_push($station_ids, $station_id2);
		}

		$this->update_qsos_bureau($station_ids);

		$this->update_qsos($station_ids);
	}

	/*
	 * Updates the QSOs that do not have any COL_QSL_SENT_VIA set
	 */
	 function update_qsos_bureau($station_ids) {
		$data = array(
			'COL_QSLSDATE' => date('Y-m-d'),
			'COL_QSL_SENT' => "Y",
			'COL_QSL_SENT_VIA' => "B",
		);

		$this->db->where_in("station_id", $station_ids);
		$this->db->where_in("COL_QSL_SENT", array("R","Q"));
		$this->db->where("coalesce(COL_QSL_SENT_VIA, '') = ''");

		$this->db->update($this->config->item('table_name'), $data);
	}

	/*
	 * Updates the QSOs that do have COL_QSL_SENT_VIA set
	 */
	function update_qsos($station_ids) {
		$data = array(
			'COL_QSLSDATE' => date('Y-m-d'),
			'COL_QSL_SENT' => "Y",
		);

		$this->db->where_in("station_id", $station_ids);
		$this->db->where_in("COL_QSL_SENT", array("R","Q"));
		$this->db->where("coalesce(COL_QSL_SENT_VIA, '') != ''");

		$this->db->update($this->config->item('table_name'), $data);
	}

	/*
	 * We list out the QSL's ready for print.
	 * station_id is not provided when loading page.
	 * It will be provided when calling the function when the dropdown is changed and the javascript fires
	 */
	function get_qsos_for_print($station_id = 'All') {
		$binding=[];
		$binding[]=$this->session->userdata('user_id');
		$sql="SELECT count(distinct oldlog.col_primary_key) as previous_qsl, log.*,sp.*,o.*
			FROM ".$this->config->item('table_name')." log
			INNER JOIN station_profile sp ON sp.`station_id` = log.`station_id`
			LEFT OUTER JOIN oqrs o ON o.`qsoid` = log.`COL_PRIMARY_KEY`
			LEFT OUTER JOIN ".$this->config->item('table_name')." oldlog on (oldlog.COL_QSL_SENT = 'Y' and oldlog.station_id=sp.station_id and oldlog.COL_BAND=log.col_band and oldlog.COL_CALL=log.col_call and oldlog.COL_MODE=log.col_mode and oldlog.COL_SAT_NAME=log.col_sat_name and oldlog.COL_PRIMARY_KEY!=log.col_primary_key)
			WHERE sp.`user_id` = ?";
		if ($station_id != 'All') {
			$sql.=' AND log.`station_id` = ?';
			$binding[]=$station_id;
		}
		$sql.=" AND log.`COL_QSL_SENT` IN('R', 'Q')
			GROUP BY log.col_primary_key
			ORDER BY log.`COL_DXCC` ASC, log.`COL_CALL` ASC, log.`COL_SAT_NAME` ASC, log.`COL_SAT_MODE` ASC, log.`COL_BAND_RX` ASC, log.`COL_TIME_ON` ASC, log.`COL_MODE` ASC LIMIT 1000";

		$query = $this->db->query($sql, $binding);
		return $query;
	}

	function get_qsos_for_print_ajax($station_id) {
		$query = $this->get_qsos_for_print($station_id);

		return $query;
	}

	function delete_from_qsl_queue($id) {
		// be sure that QSO belongs to user
		$this->load->model('logbook_model');
		if (!$this->logbook_model->check_qso_is_accessible($id)) {
			return;
		}

		$data = array(
			'COL_QSL_SENT' => "N",
		);

		$this->db->where("COL_PRIMARY_KEY", $id);
		$this->db->update($this->config->item('table_name'), $data);

		return true;
	}

	function add_qso_to_print_queue($id) {
		// be sure that QSO belongs to user
		$this->load->model('logbook_model');
		if (!$this->logbook_model->check_qso_is_accessible($id)) {
			return;
		}

		$data = array(
			'COL_QSL_SENT' => "R",
		);

		$this->db->where("COL_PRIMARY_KEY", $id);
		$this->db->update($this->config->item('table_name'), $data);

		return true;
	}

	function open_qso_list($callsign) {
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		// always filter user. this ensures that no inaccesible QSOs will be returned
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$this->db->where('(COL_CALL like "%/'.$callsign.'/%" OR COL_CALL like "%/'.$callsign.'" OR COL_CALL like "'.$callsign.'/%" OR COL_CALL = "'.$callsign.'")');
		$this->db->where('coalesce(COL_QSL_SENT, "") not in ("R", "Q")');
		$this->db->order_by("COL_DXCC", "ASC");
		$this->db->order_by("COL_CALL", "ASC");
		$this->db->order_by("COL_SAT_NAME", "ASC");
		$this->db->order_by("COL_SAT_MODE", "ASC");
		$this->db->order_by("COL_BAND_RX", "ASC");
		$this->db->order_by("COL_TIME_ON", "ASC");
		$this->db->order_by("COL_MODE", "ASC");
		$query = $this->db->get($this->config->item('table_name'));

		return $query;
	}

	function get_previous_qsls($qso_id) {
		if (empty($qso_id)) {
			return 0;
		}
	
		$table_name = $this->config->item('table_name');
		$sql = "SELECT COUNT(COL_PRIMARY_KEY) AS previous_qsl 
				FROM $table_name 
				WHERE COL_QSL_SENT = 'Y'
				AND station_id = (SELECT station_id FROM $table_name WHERE COL_PRIMARY_KEY = ?)
				AND (COL_CALL, COL_MODE, COL_BAND, COALESCE(COL_SAT_NAME, '')) = 
					(SELECT COL_CALL, COL_MODE, COL_BAND, COALESCE(COL_SAT_NAME, '') 
					 FROM $table_name 
					 WHERE COL_PRIMARY_KEY = ?)
				GROUP BY COL_CALL, COL_MODE, COL_BAND, COL_SAT_NAME";
	
		// we only return the count of previous QSLs as an integer
		return (int) ($this->db->query($sql, [$qso_id, $qso_id])->row()->previous_qsl ?? 0);
	}

	function show_oqrs($id) {
		$this->db->select('requesttime as "Request time", requestcallsign as "Requester", email as "Email", note as "Note"');
		$this->db->join('station_profile', 'station_profile.station_id = oqrs.station_id');
		// always filter user. this ensures that no inaccesible QSOs will be returned
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$this->db->where('oqrs.id = ' .$id);
		$query = $this->db->get('oqrs');

		return $query->result();
	}

}

?>
