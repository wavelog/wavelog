<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_tle_cron extends CI_Migration {
	public function up() {
		if ($this->chk4cron('update_update_tle') == 0) {
			$data = array(
				array(
					'id' => 'update_update_tle',
					'enabled' => '0',
					'status' => 'disabled',
					'description' => 'Update TLE for satellites',
					'function' => 'index.php/update/update_tle',
					'expression' => '45 4 * * *',
					'last_run' => null,
					'next_run' => null
				));
			$this->db->insert_batch('cron', $data);
		}

		$this->dbtry("ALTER TABLE tle ADD CONSTRAINT tle_unique_satelliteid_FK FOREIGN KEY (satelliteid) REFERENCES satellite (id) ON DELETE CASCADE ON UPDATE RESTRICT;");
	}

	public function down() {
		if ($this->chk4cron('update_tle') > 0) {
			$this->db->query("delete from cron where id='update_update_tle'");
		}
		$this->dbtry("alter table tle drop foreign key tle_unique_satelliteid_FK;");
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
