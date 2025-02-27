<?php

class Seven3on73 extends CI_Model {

	function get_all() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$sql = "SELECT MIN(COL_TIME_ON) as time, COL_CALL as callsign FROM ".$this->config->item('table_name')." WHERE station_id IN (".implode(', ', $logbooks_locations_array).") AND COL_SAT_NAME = 'AO-73' AND COL_TIME_ON > '2014-09-01 00:00:00' GROUP BY callsign ORDER BY time DESC;";
		$query = $this->db->query($sql);

		$result = array();

		foreach($query->result() as $row) {
			$bindings=[];
			$subsql = "SELECT COL_PRIMARY_KEY AS pkey, COL_MODE AS mode, COL_RST_RCVD AS rst_r, COL_RST_SENT AS rst_s
				FROM ".$this->config->item('table_name')."
				WHERE ".$this->config->item('table_name').".station_id IN (".implode(', ', $logbooks_locations_array).")
				AND COL_CALL = ?
				AND COL_TIME_ON = ?
				ORDER BY COL_TIME_ON ASC LIMIT 1;";
			$bindings[] = $row->callsign;
			$bindings[] = $row->time;
			$subquery = $this->db->query($subsql, $bindings);
			$subrow = $subquery->row();
			array_push($result, (object) ["time" => $row->time, "callsign" => $row->callsign, "pkey" => $subrow->pkey, "mode" => $subrow->mode, "rst_r" => $subrow->rst_r, "rst_s" => $subrow->rst_s]);
		}

		return $result;

	}
}

?>
