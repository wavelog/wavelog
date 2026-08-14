<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_fix_dbcollate_again extends CI_Migration
{
	public function up()
	{
		$tables = array(
			'club_permissions',
			'dxcc_master',
			'themes',
			'tle',
			'user_options',
			'vuccgrids'
		);
		foreach ($tables as $table) {
			$this->dbtry('ALTER TABLE ' . $table . ' CONVERT TO CHARACTER SET ' . $this->db->char_set . ' COLLATE ' . $this->db->dbcollat); // fix existing tables that haven't already been fixed
		}
		$this->dbtry('ALTER DATABASE `' . $this->db->database . '` CHARACTER SET ' . $this->db->char_set . ' COLLATE ' . $this->db->dbcollat); // fix the database default
	}

	public function down()
	{
		// Not Possible
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Error setting character set/collation: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
?>