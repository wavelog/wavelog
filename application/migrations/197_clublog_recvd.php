<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_clublog_recvd extends CI_Migration {
	public function up() {
                if (!$this->db->field_exists('COL_CLUBLOG_QSO_DOWNLOAD_STATUS', $this->config->item('table_name'))) {
                        $fields = array(
                                'COLUMN COL_CLUBLOG_QSO_DOWNLOAD_DATE DATETIME NULL DEFAULT NULL',
                                'COLUMN COL_CLUBLOG_QSO_DOWNLOAD_STATUS VARCHAR(10) DEFAULT NULL',
                        );
                        $this->dbforge->add_column($this->config->item('table_name'), $fields);
		}
	}

	public function down() {
                if ($this->db->field_exists('COL_CLUBLOG_QSO_DOWNLOAD_DATE', $this->config->item('table_name'))) {
                        $this->dbforge->drop_column($this->config->item('table_name'), 'COL_CLUBLOG_QSO_DOWNLOAD_DATE');
                }
                if ($this->db->field_exists('COL_CLUBLOG_QSO_DOWNLOAD_STATUS', $this->config->item('table_name'))) {
                        $this->dbforge->drop_column($this->config->item('table_name'), 'COL_CLUBLOG_QSO_DOWNLOAD_STATUS');
                }
	}
}
