<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
	Adds the award.srr QSL fields and the award.srr sync cron
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
					'description' => 'Sync with award.srr',
					'function' => 'index.php/srr/srr_sync',
					'expression' => '15 5 * * *',
					'last_run' => null,
					'next_run' => null
				));
			$this->db->insert_batch('cron', $data);
		}
	}

	public function down() {
		$table_name = $this->config->item('table_name');

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
			log_message("error", "Something gone wrong while adding award.srr columns: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
