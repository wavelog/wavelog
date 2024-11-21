<?php

class Migration_extend_sig_info  extends CI_Migration
{

	public function up() {
		$this->db->query("ALTER TABLE ".$this->config->item('table_name')." CHANGE COLUMN COL_SIG COL_SIG VARCHAR(64) NULL DEFAULT NULL");
		$this->db->query("ALTER TABLE ".$this->config->item('table_name')." CHANGE COLUMN COL_SIG_INFO COL_SIG_INFO VARCHAR(128) NULL DEFAULT NULL");
		$this->db->query("ALTER TABLE ".$this->config->item('table_name')." CHANGE COLUMN COL_MY_SIG_INFO COL_MY_SIG_INFO VARCHAR(128) NULL DEFAULT NULL");
		$this->db->query("ALTER TABLE ".$this->config->item('table_name')." CHANGE COLUMN COL_MY_SIG COL_MY_SIG VARCHAR(64) NULL DEFAULT NULL");
		$this->db->query("ALTER TABLE station_profile CHANGE COLUMN station_sig_info station_sig_info vARCHAR(128) NULL DEFAULT NULL");
		$this->db->query("ALTER TABLE station_profile CHANGE COLUMN station_sig station_sig vARCHAR(64) NULL DEFAULT NULL");
	}

	public function down() {
	}
}
