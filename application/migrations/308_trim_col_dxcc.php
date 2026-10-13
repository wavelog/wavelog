<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * COL_DXCC is expected to be a plain digit string. ADIF ingests could deliver
 * values with a leading space (e.g. "<DXCC:4> 230" parsed as " 230"), which
 * broke string comparisons, award counts and entity lookups. Trim leading
 * spaces on existing rows; new ingests are sanitized in Logbook_model.
 *
 * The LIKE ' %' predicate is sargable and uses HRD_IDX_COL_DXCC, so only the
 * affected rows are touched (pattern: migration 305).
 */

class Migration_trim_col_dxcc extends CI_Migration {

	/**
	 * Trim leading spaces from COL_DXCC.
	 *
	 * @return void
	 */
	public function up() {
		set_time_limit(0);
		$table_name = $this->config->item('table_name');

		// Values bound; identifier from config, not user input
		$this->dbtry("UPDATE {$table_name} SET COL_DXCC = TRIM(COL_DXCC) WHERE COL_DXCC LIKE ?", [' %']);
	}

	/**
	 * Not possible — the removed leading spaces cannot be reconstructed.
	 *
	 * @return void
	 */
	public function down() {
	}

	/**
	 * Run a query, log failures instead of aborting the migration.
	 *
	 * @param string $what   SQL statement (identifier from config, not user input)
	 * @param array  $params Bindable parameter values
	 *
	 * @return void
	 */
	function dbtry($what, $params = []) {
		try {
			$this->db->query($what, $params);
		} catch (Exception $e) {
			log_message("error", "Mig 308: Something gone wrong while trimming COL_DXCC: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
