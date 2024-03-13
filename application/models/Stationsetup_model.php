<?php

class Stationsetup_model extends CI_Model {

	function getContainer($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $clean_id);
		return $this->db->get('station_logbooks');
	}
}

?>
