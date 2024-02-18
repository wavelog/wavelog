<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_main_recreate_iota_idx extends CI_Migration {

	public function up() {
		$this->add_ix('HRD_IDX_COL_IOTA','`station_id`,`COL_IOTA`');
	}


	public function down(){
		$this->rm_ix('HRD_IDX_COL_IOTA');
	}

	private function add_ix($index,$cols) {
		$ix_exist = $this->db->query("SHOW INDEX FROM ".$this->config->item('table_name')." WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist == 0) {
			$sql = "ALTER TABLE ".$this->config->item('table_name')." ADD INDEX `".$index."` (".$cols.");";
			$this->db->query($sql);
		}
	}

	private function rm_ix($index) {
		$ix_exist = $this->db->query("SHOW INDEX FROM ".$this->config->item('table_name')." WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist >= 1) {
			$sql = "ALTER TABLE ".$this->config->item('table_name')." DROP INDEX `".$index."`;";
			$this->db->query($sql);
		}
	}
}
