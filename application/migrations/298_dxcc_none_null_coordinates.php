<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Lat/lon of 0/0 are valid coordinates though not for a DXCC.
 * So we allow null as value here to allow differentiation
 */

class Migration_dxcc_none_null_coordinates extends CI_Migration {

	private $table_name = 'dxcc_entities';

	public function up()
	{
		$this->dbtry("ALTER TABLE {$this->table_name} CHANGE `long` `long` FLOAT NULL;");
		$this->dbtry("ALTER TABLE {$this->table_name} CHANGE `lat` `lat` FLOAT NULL;");
		$this->dbtry("UPDATE {$this->table_name} SET `long` = NULL, `lat` = NULL WHERE {$this->table_name}.`adif` = 0;");
	}

	public function down()
	{
		$this->dbtry("ALTER TABLE {$this->table_name} CHANGE `long` `long` FLOAT NOT NULL;");
		$this->dbtry("ALTER TABLE {$this->table_name} CHANGE `lat` `lat` FLOAT NOT NULL;");
		$this->dbtry("UPDATE {$this->table_name} SET `long` = 0, `lat` = 0 WHERE {$this->table_name}.`adif` = 0;");
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Mig 298: Error updating dxcc_entitites table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
