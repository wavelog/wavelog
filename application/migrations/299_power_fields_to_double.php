<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_power_fields_to_double extends CI_Migration {

	public function up() {
			$this->dbtry("ALTER TABLE cat MODIFY power double DEFAULT 0;");
			$this->dbtry("ALTER TABLE station_profile MODIFY station_power double DEFAULT NULL;");
	}

	public function down() {
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
