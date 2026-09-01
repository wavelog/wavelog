<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PR #3063 (new contesting) introduced a bug where the COL_CONTEST_ID was set to the display
 * name of the contest instead of the ADIF name. This migration fixes that by updating all
 * QSOs with the correct ADIF name.
 *
 * Since this is a heavy query we create an index on COL_CONTEST_ID to speed up the update.
 */

class Migration_fix_contest_id extends CI_Migration
{
	public function up()
	{
		$table_name = $this->config->item('table_name');
		$ix_name = 'HRD_IDX_COL_CONTEST_ID';
		$ix_created = false;

		$ix_exist = $this->db->query("SHOW INDEX FROM {$table_name} WHERE Column_name = 'COL_CONTEST_ID' AND Seq_in_index = 1")->num_rows();

		if ($ix_exist == 0) {
			$this->dbtry("ALTER TABLE {$table_name} ADD INDEX `{$ix_name}` (COL_CONTEST_ID)");
			$ix_created = true;
		}

		$this->dbtry("UPDATE {$table_name} t
						JOIN (
							SELECT c1.name, MIN(c1.adifname) AS adifname
							FROM contest c1
							WHERE NOT EXISTS (
								SELECT 1 FROM contest x WHERE x.adifname = c1.name
							)
							GROUP BY c1.name
							HAVING COUNT(*) = 1
						) c ON t.COL_CONTEST_ID = c.name
						SET t.COL_CONTEST_ID = c.adifname");
		
		if ($ix_created) {
			$this->dbtry("ALTER TABLE {$table_name} DROP INDEX `{$ix_name}`");
		}
	}

	public function down()
	{
		// Not possible - the contest id should be the adif name, not the display name.
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Mig 294: Something gone wrong while fixing the COL_CONTEST_ID: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
