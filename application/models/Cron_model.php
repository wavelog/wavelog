<?php

class Cron_model extends CI_Model
{
	// get all crons from the database
    function get_crons() {
		$this->db->from('cron');

		$results = array();

		$results = $this->db->get()->result();

		return $results;
	}

	// get details for a specific cron
	function cron($id) {

		$clean_id = $this->security->xss_clean($id);

		$this->db->where('id', $clean_id);

		return $this->db->get('cron');
	}

	// set the modified timestamp
	function set_modified($cron) {
		$data = array(
			'modified' => date('Y-m-d H:i:s')
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}

	// set a new status for the cron
	function set_status($cron, $status) {
		$data = array(
			'status' => $status
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}

	// set the last run
	function set_last_run($cron) {
		$data = array(
			'last_run' => date('Y-m-d H:i:s')
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}

	// set the calculated next run
	function set_next_run($cron,$timestamp) {
		$data = array(
			'next_run' => $timestamp
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
	}

	// set the cron enabled flag
	function set_cron_enabled($cron, $cron_enabled) {
		$data = array (
			'enabled' => ($cron_enabled === 'true' ? 1 : 0),
			'status' => ($cron_enabled === 'true' ? 'pending' : 'disabled'),
		);

		$this->db->where('id', $cron);
		$this->db->update('cron', $data);
		
		$this->set_modified($cron);
	}

	// set the edited details for a cron
	function edit_cron($id, $description, $expression, $enabled) {
		
		$data = array (
			'description' => $description,
			'expression' => $expression,
			'enabled' => ($enabled === 'true' ? 1 : 0)
		);

		$this->db->where('id', $id);
		$this->db->update('cron', $data);
		
		$this->set_modified($id);
	}

	function get_next_run($what) {
		$crons=$this->get_crons();
		$one_cron = $crons[array_search($what,array_column($crons,'id'),true)];
		if (property_exists($one_cron,'next_run')) {
			return $one_cron->next_run;
		} else {
			return null;
		}
	}
}
