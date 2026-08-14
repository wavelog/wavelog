<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_dcl_cron extends CI_Migration {
	public function up() {
		if ($this->chk4cron('sync_dcl') == 0) {
			$data = array(
				array(
					'id' => 'sync_dcl',
					'enabled' => '0',
					'status' => 'disabled',
					'description' => 'Sync with DARC-DCL',
					'function' => 'index.php/dcl/dcl_sync',
					'expression' => '45 4 * * *',
					'last_run' => null,
					'next_run' => null
				));
			$this->db->insert_batch('cron', $data);
		}

	}

	public function down() {
		if ($this->chk4cron('sync_dcl') > 0) {
			$this->db->query("delete from cron where id='sync_dcl'");
		}
		// No way back to tle-table
	}

	function chk4cron($cronkey) {
		$query = $this->db->query("select count(id) as cid from cron where id=?",$cronkey);
		$row = $query->row();
		return $row->cid ?? 0;
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering FKs: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
