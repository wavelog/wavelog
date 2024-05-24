<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Add index for table to speedup LBA

class Migration_contest_idx extends CI_Migration {

	public function up() {
		$this->add_ix('contest','idx_contest_adifname','`adifname`');
	}

	public function down() { 
		$this->rm_ix('contest','idx_contest_adifname');
	}

	private function add_ix($table_name,$index,$cols) {
		$ix_exist = $this->db->query("SHOW INDEX FROM ".$table_name." WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist == 0) {
			$sql = "ALTER TABLE ".$table_name." ADD INDEX `".$index."` (".$cols.");";
			$this->db->query($sql);
		}
	}

	private function rm_ix($table_name,$index) {
		$ix_exist = $this->db->query("SHOW INDEX FROM ".$table_name." WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist >= 1) {
			$sql = "ALTER TABLE ".$table_name." DROP INDEX `".$index."`;";
			$this->db->query($sql);
		}
	}

}
