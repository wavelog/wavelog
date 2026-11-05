<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Migration_randomize_lotw extends CI_Migration {

	/**
	 * We randomize the minute for the LoTW cronjob to reduce the load on LoTW servers at the top of the hour
	 * We only apply this change if the cronjob expression is still set to the default value
	 * Minute 0 and the other multiples of five are skipped, as many other clients already run at those minutes
	 */

	public function up() {
		/**
		 * Reference:
		 *   - lotw_lotw_upload was added in mig 196
		 */
		$id = 'lotw_lotw_upload';
		$default_expression = '0 */1 * * *';

		$query = $this->db->query("SELECT expression FROM cron WHERE id=?", [$id]);
		$row = $query->row();
		if ($row && $row->expression === $default_expression) {

			do {
				$random_minute = mt_rand(1, 59);
			} while ($random_minute % 5 == 0);

			$parts = explode(' ', $default_expression);
			$parts[0] = (string)$random_minute;
			$new_expression = implode(' ', $parts);

			$this->dbtry("UPDATE cron SET expression='" . $new_expression . "' WHERE id='" . $id . "'");
		}
	}

	public function down() {
		// no way back necessary
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering a table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
