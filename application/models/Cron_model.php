<?php

class Cron_model extends CI_Model
{
    function get_crons() {
		$this->db->from('cron');

		$results = array();

		$results = $this->db->get()->result();

		return $results;
	}
}
