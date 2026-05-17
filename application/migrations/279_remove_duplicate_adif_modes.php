<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_remove_duplicate_adif_modes extends CI_Migration
{
	public function up()
	{
		$db_result = $this->dbtry("SELECT * FROM (SELECT *, ROW_NUMBER() over (PARTITION by mode, submode, qrgmode ORDER BY active DESC) AS row_num FROM adif_modes) AS temp_table WHERE row_num > 1;");
		if($db_result->num_rows() > 0) {
			foreach($db_result->result() as $row) {
				if(($row->row_num == 2) && $row->active == 0) {
					// found one active and one inactive entry of a mode,submode,qrgmode combination
					// DB table rules currently allow for exactly one 'mode','submode','qrgmode','active' combination
					// remove the inactive entry, allowing for active entry to be inactivated if desired
					$this->dbtry("DELETE FROM adif_modes WHERE id = " . $row->id . " LIMIT 1;");
				}
			}
		}

		// add virtual column for mysql to index against to handle NULL values in submode
		$this->dbtry("ALTER TABLE adif_modes ADD COLUMN submode_strnull VARCHAR(25) AS (IFNULL(submode, 'NULL')) VIRTUAL;");

		// drop index that allows for duplicate mode entries as long as one is active and one is not active
		$ix_exist = $this->db->query("SHOW INDEX FROM adif_modes WHERE Key_name = 'IDX_UNIQ_ADIF_MODES#mode#submode#qrgmode#active'")->num_rows();
		if($ix_exist == 1) {
			$this->dbtry("ALTER TABLE adif_modes DROP INDEX `IDX_UNIQ_ADIF_MODES#mode#submode#qrgmode#active`;");
		}

		// add index ignoring active status, prevents duplicates
		$ix_exist = $this->db->query("SHOW INDEX FROM adif_modes WHERE Key_name = 'IDX_UNIQ_ADIF_MODES#mode#submode_strnull#qrgmode'")->num_rows();
		if($ix_exist == 0) {
			$this->dbtry("ALTER TABLE adif_modes ADD UNIQUE INDEX `IDX_UNIQ_ADIF_MODES#mode#submode_strnull#qrgmode` (`mode`,`submode_strnull`,`qrgmode`);");
		}
	}

	public function down()
	{
		// Not Possible
	}

	function dbtry($what) {
		try {
			return $this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Error fixing duplicate ADIF Mode entry: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
