<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_webadif_status extends CI_Migration
{
	public function up()
	{
		$this->dbtry("ALTER TABLE webadif ADD COLUMN status CHAR(1) NOT NULL DEFAULT 'Y' AFTER qso_id");
		$this->dbtry("ALTER TABLE webadif ADD COLUMN message VARCHAR(255) DEFAULT NULL AFTER status");
	}

	public function down()
	{
		$this->dbtry("ALTER TABLE webadif DROP COLUMN message");
		$this->dbtry("ALTER TABLE webadif DROP COLUMN status");
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Error modifying columns in webadif: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
