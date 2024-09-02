<?php

class Distancerecords_model extends CI_Model {

	function get_records() {
		$dbversion = $this->db->version();
		$dbversion = explode('.', $dbversion);
		if ($dbversion[0] >= "8") {
			return $this->fastquery();
		} else {
			return $this->slowquery();
		}
	}

	function fastquery() {
		ini_set('memory_limit', '-1');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}
		$sql = 'SELECT t1.sat, t1.distance, t2.COL_TIME_ON AS time, t2.COL_CALL AS callsign, t2.COL_GRIDSQUARE AS grid, t2.COL_MODE AS mode, t2.COL_PRIMARY_KEY AS primarykey
			FROM (
				SELECT MAX(col_distance) AS distance, COL_SAT_NAME AS sat
					FROM '.$this->config->item('table_name').'
					WHERE station_id IN ('.implode(', ', $logbooks_locations_array).')
					AND COALESCE(COL_SAT_NAME, "") <> ""
					GROUP BY col_sat_name
			) t1
			LEFT JOIN (
				SELECT *, ROW_NUMBER() OVER (PARTITION BY COL_SAT_NAME, COL_DISTANCE ORDER BY COL_TIME_ON asc) AS rn
					FROM '.$this->config->item('table_name').'
			) t2
				ON t1.sat = t2.COL_SAT_NAME
				AND t1.distance = t2.COL_DISTANCE
				WHERE t2.rn = 1
				ORDER BY t1.distance DESC;';

		$query = $this->db->query($sql);
		return $query->result();
	}

	function slowquery() {
		ini_set('memory_limit', '-1');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}
		// First get max distance per sat
		$sql = 'SELECT MAX(col_distance) AS distance, COL_SAT_NAME AS sat
			FROM '.$this->config->item('table_name').'
			WHERE station_id IN ('.implode(', ', $logbooks_locations_array).')
			AND COALESCE(COL_SAT_NAME, "") <> ""
			GROUP BY col_sat_name
			ORDER BY distance DESC;';
		$query = $this->db->query($sql);

      $result = array();

		// With that query for oldest QSO per sat and distance
		foreach ($query->result() as $row) {
			$subsql = 'SELECT COL_SAT_NAME AS sat, COL_TIME_ON as time, COL_CALL as callsign, COL_GRIDSQUARE as grid, COL_MODE AS mode, COL_PRIMARY_KEY as primarykey
				FROM '.$this->config->item('table_name').'
				WHERE station_id IN ('.implode(', ', $logbooks_locations_array).')
				AND COL_SAT_NAME = "'.$row->sat.'"
				AND COL_DISTANCE = '.$row->distance.'
				ORDER BY COL_TIME_ON ASC LIMIT 1;';
			$subquery = $this->db->query($subsql);
			$subrow = $subquery->row();
			array_push($result, (object) ["sat" => $row->sat, "distance" => $row->distance, "time" => $subrow->time, "primarykey" => $subrow->primarykey, "callsign" => $subrow->callsign, "mode" => $subrow->mode, "grid" => $subrow->grid]);
		}
		return($result);
	}
}

?>
