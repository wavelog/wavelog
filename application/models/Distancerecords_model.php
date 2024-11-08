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
		$sql = 'SELECT t1.sat, t1.distance, t2.COL_TIME_ON AS time, t2.COL_CALL AS callsign, t2.COL_GRIDSQUARE AS grid, t2.COL_MODE AS mode, t2.COL_PRIMARY_KEY AS primarykey, t2.station_gridsquare AS mygrid
			FROM (
				SELECT MAX(col_distance) AS distance, COL_SAT_NAME AS sat
					FROM '.$this->config->item('table_name').'
					WHERE station_id IN ('.implode(', ', $logbooks_locations_array).')
					AND COALESCE(COL_SAT_NAME, "") <> ""
					AND COL_DISTANCE IS NOT NULL
					AND COL_GRIDSQUARE IS NOT NULL
					GROUP BY col_sat_name
			) t1
			LEFT JOIN (
				SELECT COL_PRIMARY_KEY, COL_CALL, COL_TIME_ON, COL_GRIDSQUARE, COL_MODE, COL_SAT_NAME, COL_DISTANCE, station_gridsquare, ROW_NUMBER() OVER (PARTITION BY COL_SAT_NAME, COL_DISTANCE ORDER BY COL_TIME_ON asc) AS rn
					FROM '.$this->config->item('table_name').'
					LEFT JOIN station_profile ON station_profile.station_id = '.$this->config->item('table_name').'.station_id
					WHERE '.$this->config->item('table_name').'.station_id IN ('.implode(', ', $logbooks_locations_array).')
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
			AND COL_DISTANCE IS NOT NULL
			AND COL_GRIDSQUARE IS NOT NULL
			GROUP BY col_sat_name
			ORDER BY distance DESC;';
		$query = $this->db->query($sql);

		$result = array();

		// With that query for oldest QSO per sat and distance
		foreach ($query->result() as $row) {
			$bindings=[];
			$subsql = 'SELECT COL_SAT_NAME AS sat, COL_TIME_ON as time, COL_CALL as callsign, COL_GRIDSQUARE as grid, station_profile.station_gridsquare AS mygrid, COL_MODE AS mode, COL_PRIMARY_KEY as primarykey
				FROM '.$this->config->item('table_name').'
				LEFT JOIN station_profile ON station_profile.station_id = '.$this->config->item('table_name').'.station_id
				WHERE '.$this->config->item('table_name').'.station_id IN ('.implode(', ', $logbooks_locations_array).')
				AND COL_SAT_NAME = ?
				AND COL_DISTANCE = ?
				ORDER BY COL_TIME_ON ASC LIMIT 1;';
			$bindings[]=$row->sat;
			$bindings[]=$row->distance;
			$subquery = $this->db->query($subsql, $bindings);
			$subrow = $subquery->row();
			array_push($result, (object) ["sat" => $row->sat, "distance" => $row->distance, "time" => $subrow->time, "primarykey" => $subrow->primarykey, "callsign" => $subrow->callsign, "mode" => $subrow->mode, "grid" => $subrow->grid, "mygrid" => $subrow->mygrid]);
		}
		return($result);
	}

	public function sat_distances($sat){
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->where('COL_SAT_NAME', $sat);
		$this->db->where_in($this->config->item('table_name').'.station_id', $logbooks_locations_array);
		$this->db->order_by("COL_DISTANCE", "desc");
		$this->db->limit(500);

		return $this->db->get($this->config->item('table_name'));
	 }

}

?>
