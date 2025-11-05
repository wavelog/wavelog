<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_station_uuid extends CI_Migration
{
	public function up()
	{
		$this->dbtry("ALTER TABLE station_profile ADD COLUMN station_uuid varchar(36) DEFAULT NULL AFTER webadifrealtime");
		$this->dbtry("UPDATE station_profile SET station_uuid = UUID() WHERE station_uuid IS NULL");
	}

	public function down()
	{
		$this->dbtry("ALTER TABLE station_profile DROP COLUMN station_uuid");
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering station_uuid: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
