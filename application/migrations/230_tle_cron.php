<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_tle_cron extends CI_Migration {
	public function up() {
		if ($this->db->table_exists('tle')) {
			$this->db->query("DROP TABLE tle");
		}
		$this->db->query("CREATE TABLE `tle` (`id` int(6) unsigned NOT NULL AUTO_INCREMENT, `satelliteid` int(6) unsigned NOT NULL,
			`tle` text DEFAULT NULL, `updated` timestamp NOT NULL DEFAULT current_timestamp(),
			PRIMARY KEY (`satelliteid`), UNIQUE KEY `tle_unique_id` (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		$this->dbtry("ALTER TABLE tle ADD CONSTRAINT tle_satellite_FK FOREIGN KEY (satelliteid) REFERENCES satellite(id) ON DELETE CASCADE");
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

	}

	public function down() {
		if ($this->chk4cron('update_tle') > 0) {
			$this->db->query("delete from cron where id='update_update_tle'");
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
