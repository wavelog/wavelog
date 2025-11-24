<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_lotw_cert_serial extends CI_Migration
{
	public function up()
	{
		$this->dbtry("ALTER TABLE lotw_certs ADD COLUMN serial INT DEFAULT NULL AFTER lotw_cert_id");
		$this->dbtry("ALTER TABLE lotw_certs ADD COLUMN status TINYINT DEFAULT 0 AFTER serial");
	}

	public function down()
	{
		$this->dbtry("ALTER TABLE lotw_certs DROP COLUMN status");
		$this->dbtry("ALTER TABLE lotw_certs DROP COLUMN serial");
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Error modifying columns in lotw_certs: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
