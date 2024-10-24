<?php
class Themes_model extends CI_Model {

	// FUNCTION: array getThemes()
	// Returns a list of themes
	function getThemes() {
		$result = $this->db->query('SELECT * FROM themes order by name');

		return $result->result();
	}

	function delete($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Theme
		$this->db->delete('themes', array('id' => $clean_id));
	}

	function add() {
		$data = array(
			'name' => xss_clean($this->input->post('name', true)),
			'foldername' => xss_clean($this->input->post('foldername', true)),
			'theme_mode' => xss_clean($this->input->post('theme_mode', true)),
			'header_logo' => xss_clean($this->input->post('header_logo', true)),
			'main_logo' => xss_clean($this->input->post('main_logo', true)),
		);

		$this->db->insert('themes', $data);
	}


	function theme($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$sql = "SELECT * FROM themes where id = ?";

		$data = $this->db->query($sql, $clean_id);

		return ($data->row());
	}

	function edit($id) {
		$data = array(
			'name' => xss_clean($this->input->post('name', true)),
			'foldername' => xss_clean($this->input->post('foldername', true)),
			'theme_mode' => xss_clean($this->input->post('theme_mode', true)),
			'header_logo' => xss_clean($this->input->post('header_logo', true)),
			'main_logo' => xss_clean($this->input->post('main_logo', true)),
		);

		$this->db->where('id', $id);
		$this->db->update('themes', $data);
	}

	function get_logo_from_theme($theme, $logo_location) {
		$clean_theme = $this->security->xss_clean($theme);
		$clean_location = $this->security->xss_clean($logo_location);

		$sql = "SELECT " . $clean_location . " FROM themes WHERE foldername = ?";

		$query = $this->db->query($sql, $clean_theme);

		if ($query) {
			$result = $query->row();
			$value = isset($result->$clean_location) ? $result->$clean_location : null;
	
			return ($value !== null) ? (string) $value : null;
		} else {
			log_message('error', 'get_logo_from_theme failed');
			return null;
		}
	}

	function get_theme_mode($theme) {
		$clean_theme = $this->security->xss_clean($theme);

		$sql = "SELECT theme_mode FROM themes WHERE foldername = ?";

		$query = $this->db->query($sql, $clean_theme);

		if ($query) {
			$result = $query->row();
			$value = isset($result->theme_mode) ? $result->theme_mode : null;
	
			return ($value !== null) ? (string) $value : null;
		} else {
			log_message('error', 'get_theme_mode failed');
			return null;
		}
	}

}
