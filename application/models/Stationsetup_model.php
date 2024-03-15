<?php

class Stationsetup_model extends CI_Model {

	function getContainer($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $clean_id);
		return $this->db->get('station_logbooks');
	}

	function saveContainer() {
		$data = array(
			'logbook_name' => xss_clean($this->input->post('name', true)),
		);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', xss_clean($this->input->post('id', true)));
		$this->db->update('station_logbooks', $data);
	}

	function remove_public_slug($logbook_id) {
		$this->db->set('public_slug', null);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', xss_clean($logbook_id));
		$this->db->update('station_logbooks');
	}

	function saveVisitorLink() {
		$this->db->set('public_slug', xss_clean($this->input->post('name', true)));
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', xss_clean($this->input->post('id', true)));
		$this->db->update('station_logbooks');
	}
}

?>
