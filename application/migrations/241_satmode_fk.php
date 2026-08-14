<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_satmode_fk extends CI_Migration {

	public function up() {
			$this->dbtry("delete from satellitemode where not exists (select 1 from satellite where satelliteid = satellite.id);");
			$this->dbtry("ALTER TABLE satellitemode ADD CONSTRAINT satmode_satellite_FK FOREIGN KEY (satelliteid) REFERENCES satellite (id) ON DELETE CASCADE ON UPDATE RESTRICT;");
	}

	public function down() {
			$this->dbtry("alter table satellitemode drop foreign key satmode_satellite_FK;");
	}	
	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering FKs: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
