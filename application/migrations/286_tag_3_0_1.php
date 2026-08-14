<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Tag Wavelog as Version 3.0.1
*/

class Migration_tag_3_0_1 extends CI_Migration {

	public function up() {
		// Tag Wavelog New Version
		$this->db->where('option_name', 'version');
		$this->db->update('options', array('option_value' => '3.0.1'));

		// Trigger Version Info Dialog
		$this->db->where('option_type', 'version_dialog');
		$this->db->where('option_name', 'confirmed');
		$this->db->update('user_options', array('option_value' => 'false'));

		// Also set Version Dialog to "both" if only custom text is applied
		$this->db->where('option_name', 'version_dialog');
		$this->db->where('option_value', 'custom_text');
		$this->db->update('options', array('option_value' => 'both'));

		$this->db->query("ALTER TABLE `station_profile`
				MODIFY `station_pota` varchar(255) DEFAULT NULL
		");

		$this->dbtry("INSERT INTO primary_subdivisions (adif, state, subdivision, deprecated) VALUES
		(266,'31','Østfold',0),
		(266,'32','Akershus',0),
		(266,'33','Buskerud',0),
		(266,'39','Vestfold',0),
		(266,'40','Telemark',0),
		(266,'55','Troms',0),
		(266,'56','Finnmark',0)");

		$this->dbtry("delete from primary_subdivisions where adif = 266 and state = 54");
		$this->dbtry("delete from primary_subdivisions where adif = 266 and state = 30");
		$this->dbtry("delete from primary_subdivisions where adif = 266 and state = 38");
	}

	public function down() {
		$this->db->where('option_name', 'version');
		$this->db->update('options', array('option_value' => '3.0.0'));

		// Do not cut station_pota back down to 50
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Error executing operations on primary_subdivisions: ".$e." // Executing: ".$this->db->last_query());
		}
	}

}
