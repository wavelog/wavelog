<?php

class Cron_model extends CI_Model
{
    function get_crons() {
		$this->db->from('cron');

		$results = array();

		$results = $this->db->get()->result();

		return $results;
	}

	function cron($id) {

		$clean_id = $this->security->xss_clean($id);

		$this->db->where('id', $clean_id);

		return $this->db->get('cron');
	}

	function set_modified($cron) {
		$data = array(
			'modified' => date('Y-m-d H:i:s')
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}

	function set_status($cron, $status) {
		$data = array(
			'status' => $status
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}

	function set_last_run($cron) {
		$data = array(
			'last_run' => date('Y-m-d H:i:s')
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}

	function set_next_run($cron,$timestamp) {
		$data = array(
			'next_run' => $timestamp
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}

	function set_cron_enabled($cron, $cron_enabled) {
		$data = array (
			'enabled' => ($cron_enabled === 'true' ? 1 : 0),
			'status' => ($cron_enabled === 'true' ? 'pending' : 'disabled'),
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
		
		$this->set_modified($cron);
	}
}
