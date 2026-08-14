<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_external_account extends CI_Migration {
	
	public function up() {
		$this->dbtry("ALTER TABLE users ADD COLUMN external_account JSON DEFAULT NULL AFTER clubstation");
	}

	public function down() {
		$this->dbtry("ALTER TABLE users DROP COLUMN external_account");
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering users table. Executing: " . $this->db->last_query());
		}
	}
}
