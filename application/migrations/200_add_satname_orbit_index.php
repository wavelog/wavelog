<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_satname_orbit_index extends CI_Migration {

	public function up() {
		$this->add_ix('satellite','SAT_IDX_NAME','`name`');
		$this->add_ix('satellite','SAT_IDX_ORBIT','`orbit`');
		$this->add_ix('qsl_images','IDX_QSL_IMAGES_QSOID','`qsoid`');
	}


	public function down(){
		$this->rm_ix('satellite','SAT_IDX_NAME');
		$this->rm_ix('satellite','SAT_IDX_ORBIT');
		$this->rm_ix('qsl_images','IDX_QSL_IMAGES_QSOID');
	}

	private function add_ix($tbl,$index,$cols) {
		$ix_exist = $this->db->query("SHOW INDEX FROM ".$tbl." WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist == 0) {
			$sql = "ALTER TABLE ".$tbl." ADD INDEX `".$index."` (".$cols.");";
			$this->db->query($sql);
		}
	}

	private function rm_ix($tbl,$index) {
		$ix_exist = $this->db->query("SHOW INDEX FROM ".$tbl." WHERE Key_name = '".$index."'")->num_rows();
		if ($ix_exist >= 1) {
			$sql = "ALTER TABLE ".$tbl." DROP INDEX `".$index."`;";
			$this->db->query($sql);
		}
	}
}
