<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_cron_table extends CI_Migration {

    public function up()
    {
		if (!$this->db->table_exists('cron')) {

			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 6,
					'unsigned' => TRUE,
					'auto_increment' => TRUE,
					'null' => FALSE
				),
				'name' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => TRUE,
				),
				'function' => array(
					'type' => 'VARCHAR',
					'constraint' => '255',
					'null' => FALSE,
				),
				'minute' => array(
					'type' => 'VARCHAR',
					'constraint' => '10',
					'null' => TRUE,
				),
				'hour' => array(
					'type' => 'VARCHAR',
					'constraint' => '10',
					'null' => TRUE,
				),
				'day_month' => array(
					'type' => 'VARCHAR',
					'constraint' => '10',
					'null' => TRUE,
				),
				'month' => array(
					'type' => 'VARCHAR',
					'constraint' => '10',
					'null' => TRUE,
				),
				'day_week' => array(
					'type' => 'VARCHAR',
					'constraint' => '10',
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
				array('name' => 'Upload QSOs to Clublog', 'function' => 'index.php/clublog/upload', 'minute' => '3', 'hour' => '*/6', 'day_month' => '*', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Upload QSOs to LoTW', 'function' => 'index.php/lotw/lotw_upload', 'minute' => '0', 'hour' => '*/1', 'day_month' => '*', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Upload QSOs to QRZ', 'function' => 'index.php/qrz/upload', 'minute' => '6', 'hour' => '*/6', 'day_month' => '*', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Download QSOs from QRZ', 'function' => 'index.php/qrz/download', 'minute' => '18', 'hour' => '*/6', 'day_month' => '*', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Upload QSOs to HRD', 'function' => 'index.php/hrdlog/upload', 'minute' => '12', 'hour' => '*/6', 'day_month' => '*', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Upload/download QSOs to/from Eqsl', 'function' => 'index.php/eqsl/sync', 'minute' => '9', 'hour' => '*/6', 'day_month' => '*', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Update LOTW Users Activity', 'function' => 'index.php/update/lotw_users', 'minute' => '10', 'hour' => '1', 'day_month' => '*', 'month' => '*', 'day_week' => '1', 'last_run' => null ),
				array('name' => 'Update Clublog SCP Database File', 'function' => 'index.php/update/update_clublog_scp', 'minute' => '20', 'hour' => '0', 'day_month' => '*', 'month' => '*', 'day_week' => '0', 'last_run' => null ),
				array('name' => 'Update DOK File', 'function' => 'index.php/update/update_dok', 'minute' => '0', 'hour' => '1', 'day_month' => '1', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Update SOTA File', 'function' => 'index.php/update/update_sota', 'minute' => '5', 'hour' => '1', 'day_month' => '1', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Update WWFF File', 'function' => 'index.php/update/update_wwff', 'minute' => '10', 'hour' => '1', 'day_month' => '1', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
				array('name' => 'Update POTA File', 'function' => 'index.php/update/update_pota', 'minute' => '15', 'hour' => '1', 'day_month' => '1', 'month' => '*', 'day_week' => '*', 'last_run' => null ),
			);
			
			$this->db->insert_batch('cron', $data);
		}
	}

    public function down()
    {
		$this->dbforge->drop_table('cron');
	}
}
