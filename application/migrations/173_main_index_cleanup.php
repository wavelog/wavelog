<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_main_index_cleanup extends CI_Migration {

	public function up() {
		$this->rm_ix('HRD_IDX_COL_CQZ');
		$this->rm_ix('gridsquares');
		$this->rm_ix('station_id');
		$this->rm_ix('HRD_IDX_COL_BAND');
		$this->rm_ix('HRD_IDX_COL_CALL');
		$this->rm_ix('HRD_IDX_COL_CONT');
		$this->rm_ix('HRD_IDX_COL_DXCC');
		$this->rm_ix('HRD_IDX_COL_IOTA');
		$this->rm_ix('HRD_IDX_COL_MODE');
		$this->rm_ix('HRD_IDX_COL_PFX');
		$this->rm_ix('HRD_IDX_COL_TIME_ON');
		$this->add_ix('idx_HRD_DBL_CHK','`station_id`, `COL_CALL`,`COL_BAND`,`COL_MODE`');
		$this->add_ix('idx_HRD_COL_BAND_station_id','`station_id`,`COL_BAND`,`COL_TIME_ON`');
		$this->add_ix('idx_HRD_COL_CALL_station_id','`station_id`,`COL_CALL`,`COL_TIME_ON`');
		$this->add_ix('idx_HRD_COL_DXCC_station_id','`station_id`,`COL_DXCC`,`COL_TIME_ON`');
		$this->add_ix('idx_HRD_station_id','`station_id`,`COL_TIME_ON`');
	}


	public function down(){
		$this->rm_ix('idx_HRD_DBL_CHK');
		$this->rm_ix('idx_HRD_COL_BAND_station_id');
		$this->rm_ix('idx_HRD_COL_CALL_station_id');
		$this->rm_ix('idx_HRD_COL_DXCC_station_id');
		$this->rm_ix('idx_HRD_station_id');
		$this->add_ix('HRD_IDX_COL_CQZ','`COL_CQZ`');
		$this->add_ix('gridsquares','`COL_GRIDSQUARE`');
		$this->add_ix('station_id','`station_id`');
		$this->add_ix('HRD_IDX_COL_BAND','`COL_BAND`');
		$this->add_ix('HRD_IDX_COL_CALL','`COL_CALL`');
		$this->add_ix('HRD_IDX_COL_CONT','`COL_CONT`');
		$this->add_ix('HRD_IDX_COL_DXCC','`COL_DXCC`');
		$this->add_ix('HRD_IDX_COL_IOTA','`COL_IOTA`');
		$this->add_ix('HRD_IDX_COL_MODE','`COL_MODE`');
		$this->add_ix('HRD_IDX_COL_PFX','`COL_PFX`');
		$this->add_ix('HRD_IDX_COL_TIME_ON','`COL_TIME_ON`');
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
