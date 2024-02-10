<?php

class Wwff extends CI_Model {

	function get_all() {
		
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
            return null;
        }

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('wwff');

		if(!$bandslots) return null;

		$this->db->where_in("station_id", $logbooks_locations_array);
		$this->db->where_in("col_band", $bandslots);
		$this->db->order_by("COL_WWFF_REF", "ASC");
		$this->db->where('COL_WWFF_REF !=', '');

		return $this->db->get($this->config->item('table_name'));
	}
}

?>
