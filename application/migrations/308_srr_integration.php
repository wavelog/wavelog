<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
	Adds the SRR QSL fields, the SRR sync cron and the table for QSOs
	which were accepted by SRR but not yet confirmed as processed
	(SRR inbox). These QSOs are verified later.
*/

class Migration_srr_integration extends CI_Migration {

	public function up() {
		$table_name = $this->config->item('table_name');

		if (!$this->db->field_exists('COL_SRR_QSLRDATE', $table_name)) {
			$this->dbtry("ALTER TABLE `$table_name` ADD COLUMN COL_SRR_QSLRDATE DATETIME DEFAULT NULL AFTER COL_DCL_QSL_SENT;");
		}
		if (!$this->db->field_exists('COL_SRR_QSL_RCVD', $table_name)) {
			$this->dbtry("ALTER TABLE `$table_name` ADD COLUMN COL_SRR_QSL_RCVD VARCHAR(10) DEFAULT NULL AFTER COL_SRR_QSLRDATE;");
		}
		if (!$this->db->field_exists('COL_SRR_QSLSDATE', $table_name)) {
			$this->dbtry("ALTER TABLE `$table_name` ADD COLUMN COL_SRR_QSLSDATE DATETIME DEFAULT NULL AFTER COL_SRR_QSL_RCVD;");
		}
		if (!$this->db->field_exists('COL_SRR_QSL_SENT', $table_name)) {
			$this->dbtry("ALTER TABLE `$table_name` ADD COLUMN COL_SRR_QSL_SENT VARCHAR(10) DEFAULT NULL AFTER COL_SRR_QSLSDATE;");
		}

		if ($this->chk4cron('sync_srr') == 0) {
			$data = array(
				array(
					'id' => 'sync_srr',
					'enabled' => '0',
					'status' => 'disabled',
					'description' => 'Sync with SRR',
					'function' => 'index.php/srr/srr_sync',
					'expression' => '15 5 * * *',
					'last_run' => null,
					'next_run' => null
				));
			$this->db->insert_batch('cron', $data);
		}

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
		$table_name = $this->config->item('table_name');

		$this->dbtry("DROP TABLE IF EXISTS `srr_pending`");

		if ($this->chk4cron('sync_srr') > 0) {
			$this->db->query("delete from cron where id='sync_srr'");
		}

		foreach (array('COL_SRR_QSL_SENT', 'COL_SRR_QSLSDATE', 'COL_SRR_QSL_RCVD', 'COL_SRR_QSLRDATE') as $column) {
			if ($this->db->field_exists($column, $table_name)) {
				$this->dbforge->drop_column($table_name, $column);
			}
		}
	}

	function chk4cron($cronkey) {
		$query = $this->db->query("select count(id) as cid from cron where id=?",$cronkey);
		$row = $query->row();
		return $row->cid ?? 0;
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while adding SRR columns and table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
