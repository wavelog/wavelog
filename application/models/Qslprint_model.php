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
		$binding = [];
		$binding[] = $this->session->userdata('user_id');
		$sql = "SELECT count(distinct oldlog.col_primary_key) as previous_qsl,
			count(distinct sentlog.col_primary_key) as qsl_sent_to_call,
			count(distinct rcvdlog.col_primary_key) as qsl_rcvd_from_call,
			log.COL_QSL_SENT, log.COL_PRIMARY_KEY, log.COL_DXCC, log.COL_CALL, log.COL_SAT_NAME, log.COL_SAT_MODE, log.COL_BAND_RX, log.COL_FREQ as frequency, log.COL_FREQ_RX as frequency_rx, log.COL_TIME_ON, log.COL_MODE, log.COL_RST_SENT, log.COL_RST_RCVD, log.COL_QSL_VIA, log.COL_QSL_SENT_VIA, log.COL_SUBMODE, log.COL_BAND, sp.station_id, sp.station_callsign, sp.station_profile_name, o.qsoid
			FROM ".$this->config->item('table_name')." log
			INNER JOIN station_profile sp ON sp.`station_id` = log.`station_id`
			LEFT OUTER JOIN oqrs o ON o.`qsoid` = log.`COL_PRIMARY_KEY`
			LEFT OUTER JOIN ".$this->config->item('table_name')." oldlog on (oldlog.COL_QSL_SENT = 'Y' and oldlog.station_id=sp.station_id and oldlog.COL_BAND=log.col_band and oldlog.COL_CALL=log.col_call and oldlog.COL_MODE=log.col_mode and oldlog.COL_SAT_NAME=log.col_sat_name and oldlog.COL_PRIMARY_KEY != log.col_primary_key)
			LEFT OUTER JOIN ".$this->config->item('table_name')." sentlog on (sentlog.COL_QSL_SENT = 'Y' and sentlog.station_id=sp.station_id and sentlog.COL_CALL=log.col_call)
			LEFT OUTER JOIN ".$this->config->item('table_name')." rcvdlog on (rcvdlog.COL_QSL_RCVD = 'Y' and rcvdlog.station_id=sp.station_id and rcvdlog.COL_CALL=log.col_call)
			WHERE sp.`user_id` = ?";
		if ($station_id != 'All') {
			$sql .= ' AND log.`station_id` = ?';
			$binding[] = $station_id;
		}
		$sql .= " AND log.`COL_QSL_SENT` IN('R', 'Q')
			GROUP BY log.col_primary_key, log.COL_QSL_SENT, log.COL_PRIMARY_KEY, log.COL_DXCC, log.COL_CALL, log.COL_SAT_NAME, log.COL_SAT_MODE, log.COL_BAND_RX, log.COL_FREQ, log.COL_FREQ_RX, log.COL_TIME_ON, log.COL_MODE, log.COL_RST_SENT, log.COL_RST_RCVD, log.COL_QSL_VIA, log.COL_QSL_SENT_VIA, log.COL_SUBMODE, log.COL_BAND, sp.station_id, sp.station_callsign, sp.station_profile_name, o.qsoid
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

		$this->db->where('qsoid', $id);
		$this->db->where_not_in('status', [2, 4]);
		$this->db->update('oqrs', ['status' => '3']);

		return true;
	}

	function open_qso_list($callsign) {
		$binding = [];

		$sql = "SELECT log.COL_QSL_SENT, log.COL_QSLRDATE, log.COL_QSL_RCVD, log.COL_QSL_RCVD_VIA, log.COL_QSLSDATE, log.COL_QSL_VIA, log.COL_QSL_SENT_VIA,
			log.COL_LOTW_QSL_SENT, log.COL_LOTW_QSLSDATE, log.COL_LOTW_QSL_RCVD, log.COL_LOTW_QSLRDATE,
			log.COL_PRIMARY_KEY, log.COL_DXCC, log.COL_CALL, log.COL_SAT_NAME, log.COL_SAT_MODE, log.COL_BAND_RX, log.COL_FREQ,
			log.COL_FREQ_RX, log.COL_TIME_ON, log.COL_MODE, log.COL_RST_SENT, log.COL_RST_RCVD,
			log.COL_SUBMODE, log.COL_BAND, sp.station_id, sp.station_callsign, sp.station_profile_name,
			(SELECT COUNT(*) FROM ".$this->config->item('table_name')." sentlog WHERE sentlog.COL_QSL_SENT = 'Y' AND sentlog.station_id = log.station_id AND sentlog.COL_CALL = log.COL_CALL) as qsl_sent_to_call,
			(SELECT COUNT(*) FROM ".$this->config->item('table_name')." rcvdlog WHERE rcvdlog.COL_QSL_RCVD = 'Y' AND rcvdlog.station_id = log.station_id AND rcvdlog.COL_CALL = log.COL_CALL) as qsl_rcvd_from_call,
			(SELECT COUNT(*) FROM ".$this->config->item('table_name')." prevlog WHERE prevlog.COL_QSL_SENT = 'Y' AND prevlog.station_id = log.station_id AND prevlog.COL_BAND = log.COL_BAND AND prevlog.COL_CALL = log.COL_CALL AND prevlog.COL_MODE = log.COL_MODE AND COALESCE(prevlog.COL_SAT_NAME, '') = COALESCE(log.COL_SAT_NAME, '') AND prevlog.COL_PRIMARY_KEY != log.COL_PRIMARY_KEY) as previous_qsl
			FROM ".$this->config->item('table_name')." log
			JOIN station_profile sp ON sp.`station_id` = log.`station_id`
			WHERE sp.`user_id` = ?
			AND (log.COL_CALL like ? OR log.COL_CALL like ? OR log.COL_CALL like ? OR log.COL_CALL = ?)
			AND coalesce(log.COL_QSL_SENT, '') not in ('R', 'Q')
			ORDER BY log.`COL_DXCC` ASC, log.`COL_CALL` ASC, log.`COL_SAT_NAME` ASC, log.`COL_SAT_MODE` ASC, log.`COL_BAND_RX` ASC, log.`COL_TIME_ON` ASC, log.`COL_MODE` ASC LIMIT 1000";

		$binding[] = $this->session->userdata('user_id');
		$binding[] = "%/".$callsign."/%";
		$binding[] = "%/".$callsign;
		$binding[] = $callsign."/%";
		$binding[] = $callsign;

		$query = $this->db->query($sql, $binding);
		return $query;
	}

	function show_oqrs($id) {
		$sql = "SELECT requesttime as 'Request time', requestcallsign as 'Requester', email as 'Email', note as 'Note'
		FROM oqrs
		JOIN station_profile ON station_profile.station_id = oqrs.station_id
		WHERE station_profile.user_id = ?
		AND oqrs.id = ?";

		$binding = [];
		$binding[] = $this->session->userdata('user_id');
		$binding[] = $id;

		return $this->db->query($sql, $binding)->result();
	}

}

?>
