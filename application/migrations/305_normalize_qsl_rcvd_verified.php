<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ADIF 3.1.6: 'V' (verified) in the QSL_Rcvd enumeration is import-only/deprecated
 * (spec: accepted on import, not to be emitted on export). Legacy rows may hold 'V'
 * in the QSL/LoTW/eQSL/DCL received columns. Normalize those rows to 'Y' (confirmed).
 *
 * Heavy query on large logs — a temporary index per column speeds up the update
 * (pattern: migration 294_fix_contest_id).
 */

class Migration_normalize_qsl_rcvd_verified extends CI_Migration {
	/**
	 * Normalize legacy 'V' QSL-received values to 'Y'.
	 *
	 * @return void
	 */
	public function up() {
		set_time_limit(0);
		$table_name = $this->config->item('table_name');

		$columns = ['COL_QSL_RCVD', 'COL_LOTW_QSL_RCVD', 'COL_EQSL_QSL_RCVD', 'COL_DCL_QSL_RCVD', ];

		foreach ($columns as $col) {
			if (!in_array($col, $columns, true)) {
				continue; // defense-in-depth: never interpolate anything not whitelisted
			}
			if (!$this->db->field_exists($col, $table_name)) {
				continue; // e.g. DCL columns added later (migration 231)
			}

			$ix_name = 'HRD_TMP_IDX_' . $col;
			$ix_created = false;

			$ix_exist = $this->db->query("SHOW INDEX FROM {$table_name} WHERE Column_name = ? AND Seq_in_index = 1",[$col])->num_rows();

			if ($ix_exist == 0) {
				// Identifiers only (whitelisted) — nothing bindable in DDL
				$this->dbtry("ALTER TABLE {$table_name} ADD INDEX `{$ix_name}` (`{$col}`)");
				$ix_created = true;
			}

			// Values bound
			$this->dbtry("UPDATE {$table_name} SET {$col} = ? WHERE {$col} = ?", ['Y', 'V']);

			if ($ix_created) {
				$this->dbtry("ALTER TABLE {$table_name} DROP INDEX `{$ix_name}`");
			}
		}
	}

	/**
	 * Not possible — 'V' cannot be reconstructed from 'Y'.
	 *
	 * @return void
	 */
	public function down() {
	}

	/**
	 * Run a query, log failures instead of aborting the migration.
	 *
	 * @param string $what   SQL statement (identifiers whitelisted by caller)
	 * @param array  $params Bindable parameter values
	 *
	 * @return void
	 */
	function dbtry($what, $params = []) {
		try {
			$this->db->query($what, $params);
		} catch (Exception $e) {
			log_message("error", "Mig 305: Something gone wrong while normalizing QSL rcvd 'V' to 'Y': ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
