<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_cron_table extends CI_Migration {

    public function up()
    {
		if (!$this->db->table_exists('cron')) {

			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => FALSE,
				),
				'enabled' => array(
					'type' => 'TINYINT',
					'constraint' => '1',
					'null' => FALSE,
				),
				'status' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'description' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'function' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => FALSE,
				),
				'expression' => array(
					'type' => 'VARCHAR',
					'constraint' => '100',
					'null' => TRUE,
				),
				'last_run' => array(
					'type' => 'TIMESTAMP',
					'null' => TRUE,
				),
				'next_run' => array(
					'type' => 'TIMESTAMP',
					'null' => TRUE,
				),
				'modified' => array(
					'type' => 'TIMESTAMP',
					'null' => TRUE,
				),
			));
			
			$this->dbforge->add_key('id', TRUE);

			$this->dbforge->create_table('cron');

			$data = array(
				array('id' => 'clublog_upload', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Upload QSOs to Clublog', 'function' => 'index.php/clublog/upload', 'expression' => '3 */6 * * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'lotw_lotw_upload', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Upload QSOs to LoTW', 'function' => 'index.php/lotw/lotw_upload', 'expression' => '0 */1 * * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'qrz_upload', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Upload QSOs to QRZ', 'function' => 'index.php/qrz/upload', 'expression' => '6 */6 * * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'qrz_download', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Download QSOs from QRZ', 'function' => 'index.php/qrz/download', 'expression' => '18 */6 * * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'hrdlog_upload', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Upload QSOs to HRD', 'function' => 'index.php/hrdlog/upload', 'expression' => '12 */6 * * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'eqsl_sync', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Upload/download QSOs to/from Eqsl', 'function' => 'index.php/eqsl/sync', 'expression' => '9 */6 * * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'update_lotw_users', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Update LOTW Users Activity', 'function' => 'index.php/update/lotw_users', 'expression' => '10 1 * * 1', 'last_run' => null, 'next_run' => null ),
				array('id' => 'update_update_clublog_scp', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Update Clublog SCP Database File', 'function' => 'index.php/update/update_clublog_scp', 'expression' => '0 0 * * 0', 'last_run' => null, 'next_run' => null ),
				array('id' => 'update_update_dok', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Update DOK File', 'function' => 'index.php/update/update_dok', 'expression' => '0 0 1 * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'update_update_sota', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Update SOTA File', 'function' => 'index.php/update/update_sota', 'expression' => '0 0 1 * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'update_update_wwff', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Update WWFF File', 'function' => 'index.php/update/update_wwff', 'expression' => '0 0 1 * *', 'last_run' => null, 'next_run' => null ),
				array('id' => 'update_update_pota', 'enabled' => '1', 'status' => 'healthy', 'description' => 'Update POTA File', 'function' => 'index.php/update/update_pota', 'expression' => '0 0 1 * *', 'last_run' => null, 'next_run' => null ),
			);
			
			$this->db->insert_batch('cron', $data);
		}
	}

    public function down()
    {
		$this->dbforge->drop_table('cron');
	}
}
