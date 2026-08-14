<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_longer_dok extends CI_Migration {

	public function up() {
			$this->dbtry("ALTER TABLE ".$this->config->item('table_name')." MODIFY COLUMN COL_DARC_DOK varchar(20);");
			$this->dbtry("ALTER TABLE ".$this->config->item('table_name')." MODIFY COLUMN COL_MY_DARC_DOK varchar(20);");
	}

	public function down(){
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering the QSO table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
