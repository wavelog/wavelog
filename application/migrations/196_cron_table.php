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
			));
			
			$this->dbforge->add_key('id', TRUE);

			$this->dbforge->create_table('cron');

			$data = array(
				array('id' => 'upload_clublog', 'description' => 'Upload QSOs to Clublog', 'function' => 'index.php/clublog/upload', 'expression' => '3 */6 * * *', 'last_run' => null ),
				array('id' => 'upload_lotw', 'description' => 'Upload QSOs to LoTW', 'function' => 'index.php/lotw/lotw_upload', 'expression' => '0 */1 * * *', 'last_run' => null ),
				array('id' => 'upload_qrz', 'description' => 'Upload QSOs to QRZ', 'function' => 'index.php/qrz/upload', 'expression' => '6 */6 * * *', 'last_run' => null ),
				array('id' => 'download_qrz', 'description' => 'Download QSOs from QRZ', 'function' => 'index.php/qrz/download', 'expression' => '18 */6 * * *', 'last_run' => null ),
				array('id' => 'upload_hrd', 'description' => 'Upload QSOs to HRD', 'function' => 'index.php/hrdlog/upload', 'expression' => '12 */6 * * *', 'last_run' => null ),
				array('id' => 'sync_eqsl', 'description' => 'Upload/download QSOs to/from Eqsl', 'function' => 'index.php/eqsl/sync', 'expression' => '9 */6 * * *', 'last_run' => null ),
				array('id' => 'lotw_activity', 'description' => 'Update LOTW Users Activity', 'function' => 'index.php/update/lotw_users', 'expression' => '10 1 * * 1', 'last_run' => null ),
				array('id' => 'clublog_scp', 'description' => 'Update Clublog SCP Database File', 'function' => 'index.php/update/update_clublog_scp', 'expression' => '@weekly', 'last_run' => null ),
				array('id' => 'update_dok', 'description' => 'Update DOK File', 'function' => 'index.php/update/update_dok', 'expression' => '@monthly', 'last_run' => null ),
				array('id' => 'update_sota', 'description' => 'Update SOTA File', 'function' => 'index.php/update/update_sota', 'expression' => '@monthly', 'last_run' => null ),
				array('id' => 'update_wwff', 'description' => 'Update WWFF File', 'function' => 'index.php/update/update_wwff', 'expression' => '@monthly', 'last_run' => null ),
				array('id' => 'update_pota', 'description' => 'Update POTA File', 'function' => 'index.php/update/update_pota', 'expression' => '@monthly', 'last_run' => null ),
			);
			
			$this->db->insert_batch('cron', $data);
		}
	}

    public function down()
    {
		$this->dbforge->drop_table('cron');
	}
}
