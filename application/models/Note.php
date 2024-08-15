<?php

class Note extends CI_Model {

	function list_all($api_key = null) {
        if ($api_key == null) {
			$user_id = $this->session->userdata('user_id');
		} else {
			$this->load->model('api_model');
			if (strpos($this->api_model->access($api_key), 'r') !== false) {
				$this->api_model->update_last_used($api_key);
				$user_id = $this->api_model->key_userid($api_key);
			}
		}
		
		$this->db->where('user_id', $user_id);
		return $this->db->get('notes');
	}

	function add() {
		$data = array(
			'cat' => $this->input->post('category', TRUE),
			'title' => $this->input->post('title', TRUE),
			'note' => $this->input->post('content', TRUE),
			'user_id' => $this->session->userdata('user_id')
		);

		$this->db->insert('notes', $data);
	}

	function edit() {
		$data = array(
			'cat' => $this->input->post('category', TRUE),
			'title' => $this->input->post('title', TRUE),
			'note' => $this->input->post('content', TRUE)
		);

		$this->db->where('id', $this->input->post('id', TRUE));
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->update('notes', $data);
	}

	function delete($id) {

		$clean_id = $this->security->xss_clean($id);

		if (! is_numeric($clean_id)) {
			show_404();
		}

		$this->db->delete('notes', array('id' => $clean_id, 'user_id' => $this->session->userdata('user_id')));
	}

	function view($id) {

		$clean_id = $this->security->xss_clean($id);

		if (! is_numeric($clean_id)) {
			show_404();
		}

		// Get Note
		$this->db->where('id', $clean_id);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		return $this->db->get('notes');
	}

	function CountAllNotes() {
		// count all notes
		$this->db->where('user_id =', NULL);
		$query = $this->db->get('notes');
		return $query->num_rows();
	}

}

?>
