<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_satname_orbit_index extends CI_Migration {

	public function up() {
		$this->add_ix('SAT_IDX_NAME','`name`');
		$this->add_ix('SAT_IDX_ORBIT','`orbit`');
	}


	public function down(){
		$this->rm_ix('SAT_IDX_NAME');
		$this->rm_ix('SAT_IDX_ORBIT');
	}

	private function add_ix($index,$cols) {
		$ix_exist = $this->db->query("SHOW INDEX FROM satellite WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist == 0) {
			$sql = "ALTER TABLE satellite ADD INDEX `".$index."` (".$cols.");";
			$this->db->query($sql);
		}
	}

	private function rm_ix($index) {
		$ix_exist = $this->db->query("SHOW INDEX FROM satellite WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist >= 1) {
			$sql = "ALTER TABLE satellite DROP INDEX `".$index."`;";
			$this->db->query($sql);
		}
	}
}
