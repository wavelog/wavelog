<?php

class Usermodes extends CI_Model {

	function all() {
		$this->db->order_by('mode', 'ASC');
		$this->db->order_by('submode', 'ASC');
		return $this->db->get('adif_modes');
	}

	function active() {
		$this->db->where('active', 1);
		$this->db->order_by('mode', 'ASC');
		$this->db->order_by('submode', 'ASC');
		return $this->db->get('adif_modes');
	}

	function mode($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->where('id', $clean_id);
		return $this->db->get('adif_modes');
	}



	function activate($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);
		$data = array(
			'active' => '1',
		);
		$this->db->where('id', $clean_id);
		$this->db->update('adif_modes', $data);
		return true;
	}

	function deactivate($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);
		$data = array(
			'active' => '0',
		);
		$this->db->where('id', $clean_id);
		$this->db->update('adif_modes', $data);
		return true;
	}
}

?>
