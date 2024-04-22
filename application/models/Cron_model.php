<?php

class Cron_model extends CI_Model
{
    function get_crons() {
		$this->db->from('cron');

		$results = array();

		$results = $this->db->get()->result();

		return $results;
	}

	function set_last_run($cron) {
		$data = array(
			'last_run' => date('Y-m-d H:i:s')
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}
}
