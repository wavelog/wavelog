<?php

class Setup_model extends CI_Model {

	function getCountryCount() {
		$sql = 'SELECT count(*) as count FROM dxcc_entities';
		$query = $this->db->query($sql);

		return $query->row()->count;
	}

	function getLogbookCount() {
		$userid = xss_clean($this->session->userdata('user_id'));
		$sql = 'SELECT count(*) as count FROM station_logbooks WHERE user_id =' . $userid;
		$query = $this->db->query($sql);

		return $query->row()->count;
	}

	function getLocationCount() {
		$userid = xss_clean($this->session->userdata('user_id'));
		$sql = 'SELECT count(*) as count FROM station_profile WHERE user_id =' . $userid;
		$query = $this->db->query($sql);

		return $query->row()->count;
	}

	function checkThemesWithoutMode() {
		$sql = "SELECT COUNT(*) AS count FROM themes WHERE theme_mode IS NULL OR theme_mode = ''";
		$query = $this->db->query($sql);

		return $query->row()->count;
	}
}

?>
