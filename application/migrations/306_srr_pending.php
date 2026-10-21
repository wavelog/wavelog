<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
	Adds the table for QSOs which were accepted by SRR but not yet
	confirmed as processed (SRR inbox). These QSOs are verified later.
*/

class Migration_srr_pending extends CI_Migration {

	public function up() {
		if (!$this->db->table_exists('srr_pending')) {
			$this->dbtry("CREATE TABLE `srr_pending` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`qso_id` int(11) NOT NULL,
				`upload_date` datetime NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `srr_pending_qso_id` (`qso_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
		}
	}

	public function down() {
		$this->dbtry("DROP TABLE IF EXISTS `srr_pending`");
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while creating the srr_pending table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
