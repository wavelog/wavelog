<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Migration_randomize extends CI_Migration {

	/**
	 * We randomize the minute for clublog cronjobs to reduce the load on clublog servers
	 * We only apply this change if the cronjob expression is still set to the default value
	 */

	public function up() {
		/**
		 * Reference:
		 *   - clublog_upload was added in mig 196
		 *   - clublog_download was added in mig 197
		 */
		$cron_jobs = [
			'clublog_download' => '7 00 * * *',
			'clublog_upload' => '3 */6 * * *',
		];

		foreach ($cron_jobs as $id => $default_expression) {
			$query = $this->db->query("SELECT expression FROM cron WHERE id=?", [$id]);
			$row = $query->row();
			if ($row && $row->expression === $default_expression) {
				
				$random_minute = mt_rand(0, 59);
				
				$parts = explode(' ', $default_expression);
				$parts[0] = (string)$random_minute;
				$new_expression = implode(' ', $parts);

				// all expressions should have the same format
				$new_expression = str_replace(' 00 ', ' 0 ', $new_expression);
				
				$this->dbtry("UPDATE cron SET expression='" . $new_expression . "' WHERE id='" . $id . "'");
			}
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
