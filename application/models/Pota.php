<?php

class Pota extends CI_Model {

	function get_all() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('pota');

		if(!$bandslots) return null;

		$this->db->where_in("station_id", $logbooks_locations_array);
		$this->db->where_in("col_band", $bandslots);
		$this->db->order_by("COL_POTA_REF", "ASC");
		$this->db->where('COL_POTA_REF !=', '');

		return $this->db->get($this->config->item('table_name'));
	}

	function ham_of_note($callsign) {
		$this->db->where('callsign', $callsign);
		$this->db->limit(1);

		return $this->db->get('hams_of_note');
	}

}

?>
