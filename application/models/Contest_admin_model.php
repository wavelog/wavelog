<?php
class Contest_admin_model extends CI_Model {

	function getAllContests() {

		$sql = "SELECT id, name, adifname, active FROM contest ORDER BY name ASC";

		$data = $this->db->query($sql);

		return ($data->result_array());
	}

	function getActiveContests() {

		$sql = "SELECT id, name, adifname FROM contest WHERE active = 1 ORDER BY (id = 1) DESC, name ASC";

		$data = $this->db->query($sql);

		return ($data->result_array());
	}

	function delete($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Contest
		$this->db->delete('contest', array('id' => $clean_id));
	}

	function activate($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$data = array(
			'active' => '1',
		);

		$this->db->where('id', $clean_id);

		$this->db->update('contest', $data);

		return true;
	}

	function deactivate($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$data = array(
			'active' => '0',
		);

		$this->db->where('id', $clean_id);

		$this->db->update('contest', $data);

		return true;
	}

	function add() {
		$data = array(
			'name' => $this->input->post('name', true),
			'adifname' => $this->input->post('adifname', true),
		);

		$this->db->insert('contest', $data);
	}

	function contest($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$binding = [];
		$sql = "SELECT id, name, adifname, active FROM contest where id = ?";
		$binding[] = $clean_id;

		$data = $this->db->query($sql, $binding);

		return ($data->row());
	}

	function edit($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$data = array(
			'name' => $this->input->post('name', true),
			'adifname' => $this->input->post('adifname', true),
			'active' =>  $this->input->post('active', true),
		);

		$this->db->where('id', $clean_id);
		$this->db->update('contest', $data);
	}

	function activateall() {
		$data = array(
			'active' => '1',
		);

		$this->db->update('contest', $data);

		return true;
	}

	function deactivateall() {
		$data = array(
			'active' => '0',
		);

		$this->db->update('contest', $data);

		return true;
	}
}
