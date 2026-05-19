<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Adds QSO upload to QRZCALL.EU, mirroring the existing QRZ.com upload:
 *  - station_profile.qrzcallapikey   — per-station QRZCALL.EU PAT (pat_xxx)
 *  - station_profile.qrzcallrealtime — -1 disabled / 0 enabled / 1 realtime
 *  - COL_QRZCALL_QSO_UPLOAD_DATE / _STATUS — per-QSO upload tracking
 *  - a background upload cron (disabled by default, like qrz_upload)
 */
class Migration_add_qrzcall_logbook_upload extends CI_Migration {

	public function up() {
		// Per-station QRZCALL.EU upload credentials
		if (!$this->db->field_exists('qrzcallapikey', 'station_profile')) {
			$this->dbforge->add_column('station_profile', array(
				'qrzcallapikey VARCHAR(64) NULL DEFAULT NULL',
			));
		}
		if (!$this->db->field_exists('qrzcallrealtime', 'station_profile')) {
			$this->dbforge->add_column('station_profile', array(
				'qrzcallrealtime TINYINT(1) NOT NULL DEFAULT 0',
			));
		}

		// Per-QSO QRZCALL.EU upload tracking
		if (!$this->db->field_exists('COL_QRZCALL_QSO_UPLOAD_STATUS', $this->config->item('table_name'))) {
			$fields = array(
				'COLUMN COL_QRZCALL_QSO_UPLOAD_DATE DATETIME NULL DEFAULT NULL',
				'COLUMN COL_QRZCALL_QSO_UPLOAD_STATUS VARCHAR(10) DEFAULT NULL',
			);
			$this->dbforge->add_column($this->config->item('table_name'), $fields);
		}

		// Background upload cron — disabled by default, mirrors qrz_upload
		if ($this->chk4cron('qrzcall_upload') == 0) {
			$data = array(
				array(
					'id' => 'qrzcall_upload',
					'enabled' => '0',
					'status' => 'pending',
					'description' => 'Upload QSOs to QRZCALL.EU',
					'function' => 'index.php/qrzcallupload/upload',
					'expression' => '12 */6 * * *',
					'last_run' => null,
					'next_run' => null
				));
			$this->db->insert_batch('cron', $data);
		}
	}

	public function down() {
		if ($this->db->field_exists('qrzcallapikey', 'station_profile')) {
			$this->dbforge->drop_column('station_profile', 'qrzcallapikey');
		}
		if ($this->db->field_exists('qrzcallrealtime', 'station_profile')) {
			$this->dbforge->drop_column('station_profile', 'qrzcallrealtime');
		}
		if ($this->db->field_exists('COL_QRZCALL_QSO_UPLOAD_DATE', $this->config->item('table_name'))) {
			$this->dbforge->drop_column($this->config->item('table_name'), 'COL_QRZCALL_QSO_UPLOAD_DATE');
		}
		if ($this->db->field_exists('COL_QRZCALL_QSO_UPLOAD_STATUS', $this->config->item('table_name'))) {
			$this->dbforge->drop_column($this->config->item('table_name'), 'COL_QRZCALL_QSO_UPLOAD_STATUS');
		}
		if ($this->chk4cron('qrzcall_upload') > 0) {
			$this->db->query("delete from cron where id='qrzcall_upload'");
		}
	}

	function chk4cron($cronkey) {
		$query = $this->db->query("select count(id) as cid from cron where id=?", $cronkey);
		$row = $query->row();
		return $row->cid ?? 0;
	}
}
