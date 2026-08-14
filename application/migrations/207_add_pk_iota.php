<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_pk_iota extends CI_Migration {

	public function up() {
		$this->add_ix('iota','idx_iota_Tag','`tag`');
	}

	public function down() {
		$this->rm_ix('iota','idx_iota_Tag');
	}

	private function add_ix($table_name,$index,$cols) {
		$ix_exist = $this->db->query("SHOW INDEX FROM ".$table_name." WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist == 0) {
			$sql = "CREATE UNIQUE INDEX `idx_iota_Tag` ON `iota` (Tag) COMMENT '' ALGORITHM DEFAULT LOCK DEFAULT";
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
