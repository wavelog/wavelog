<?php

class Distancerecords_model extends CI_Model {

	function get_records() {
		ini_set('memory_limit', '-1');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}
		$sql = 'SELECT t1.sat, t1.distance, t2.COL_TIME_ON time, t2.COL_CALL callsign, t2.COL_GRIDSQUARE grid, t2.COL_PRIMARY_KEY AS primarykey FROM
			(
				SELECT MAX(col_distance) distance, COL_SAT_NAME sat
				FROM '.$this->config->item('table_name').'
				WHERE station_id in ('.implode(', ', $logbooks_locations_array).')
				AND COALESCE(COL_SAT_NAME, "") <> ""
				GROUP BY col_sat_name
			) t1
			LEFT JOIN TABLE_HRD_CONTACTS_V01 t2 ON t1.sat = t2.COL_SAT_NAME AND t1.distance = t2.COL_DISTANCE ORDER BY t1.distance DESC;
		';

		return $this->db->query($sql);
	}
}

?>
