<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_cron_table extends CI_Migration {

	public function up() {
		if (!$this->db->table_exists('cron')) {

			// define the structure of the new cron table
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'VARCHAR',
					'constraint' => '191',
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

			// we set the key for the id, in this case the id is not numerical
			$this->dbforge->add_key('id', TRUE);

			// now we can create the new table
			$this->dbforge->create_table('cron');

			// to transfer data for the file updates we load the optionslib library
			$this->load->library('OptionsLib');

			// and we fill the table with the cronjobs
			$data = array(
				array(
					'id' => 'clublog_upload',
					'enabled' => '0',
					'status' => 'pending',
					'description' => 'Upload QSOs to Clublog',
					'function' => 'index.php/clublog/upload',
					'expression' => '3 */6 * * *',
					'last_run' => null,
					'next_run' => null
				),
				array(
					'id' => 'lotw_lotw_upload',
					'enabled' => '0',
					'status' => 'pending',
					'description' => 'Upload QSOs to LoTW',
					'function' => 'index.php/lotw/lotw_upload',
					'expression' => '0 */1 * * *',
					'last_run' => null,
					'next_run' => null
				),
				array(
					'id' => 'qrz_upload',
					'enabled' => '0',
					'status' => 'pending',
					'description' => 'Upload QSOs to QRZ',
					'function' => 'index.php/qrz/upload',
					'expression' => '6 */6 * * *',
					'last_run' => null,
					'next_run' => null
				),
				array(
					'id' => 'qrz_download',
					'enabled' => '0',
					'status' => 'pending',
					'description' => 'Download QSOs from QRZ',
					'function' => 'index.php/qrz/download',
					'expression' => '18 */6 * * *',
					'last_run' => null,
					'next_run' => null
				),
				array(
					'id' => 'hrdlog_upload',
					'enabled' => '0',
					'status' => 'pending',
					'description' => 'Upload QSOs to HRD',
					'function' => 'index.php/hrdlog/upload',
					'expression' => '12 */6 * * *',
					'last_run' => null,
					'next_run' => null
				),
				array(
					'id' => 'eqsl_sync',
					'enabled' => '0',
					'status' => 'pending',
					'description' => 'Upload/download QSOs to/from Eqsl',
					'function' => 'index.php/eqsl/sync',
					'expression' => '9 */6 * * *',
					'last_run' => null,
					'next_run' => null
				),
				array(
					'id' => 'update_lotw_users',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update LOTW Users Activity',
					'function' => 'index.php/update/lotw_users',
					'expression' => '10 1 * * 1',
					'last_run' => ($this->optionslib->get_option('lotw_users_update') ? date("Y-m-d H:i", strtotime($this->optionslib->get_option('lotw_users_update'))) : null),
					'next_run' => null
				),
				array(
					'id' => 'update_update_clublog_scp',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update Clublog SCP Database File',
					'function' => 'index.php/update/update_clublog_scp',
					'expression' => '0 0 * * 0',
					'last_run' => ($this->optionslib->get_option('scp_update') ? date("Y-m-d H:i", strtotime($this->optionslib->get_option('scp_update'))) : null),
					'next_run' => null
				),
				array(
					'id' => 'update_update_dok',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update DOK File',
					'function' => 'index.php/update/update_dok',
					'expression' => '0 0 1 * *',
					'last_run' => ($this->optionslib->get_option('dok_file_update') ? date("Y-m-d H:i", strtotime($this->optionslib->get_option('dok_file_update'))) : null),
					'next_run' => null
				),
				array(
					'id' => 'update_update_sota',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update SOTA File',
					'function' => 'index.php/update/update_sota',
					'expression' => '5 0 1 * *',
					'last_run' => ($this->optionslib->get_option('sota_file_update') ? date("Y-m-d H:i", strtotime($this->optionslib->get_option('sota_file_update'))) : null),
					'next_run' => null
				),
				array(
					'id' => 'update_update_wwff',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update WWFF File',
					'function' => 'index.php/update/update_wwff',
					'expression' => '10 0 1 * *',
					'last_run' => ($this->optionslib->get_option('wwff_file_update') ? date("Y-m-d H:i", strtotime($this->optionslib->get_option('wwff_file_update'))) : null),
					'next_run' => null
				),
				array(
					'id' => 'update_update_pota',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update POTA File',
					'function' => 'index.php/update/update_pota',
					'expression' => '15 0 1 * *',
					'last_run' => ($this->optionslib->get_option('pota_file_update') ? date("Y-m-d H:i", strtotime($this->optionslib->get_option('pota_file_update'))) : null),
					'next_run' => null
				),
				array(
					'id' => 'update_dxcc',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update DXCC data',
					'function' => 'index.php/update/dxcc',
					'expression' => '20 0 1 */2 *',
					'last_run' => ($this->optionslib->get_option('dxcc_clublog_update') ? date("Y-m-d H:i", strtotime($this->optionslib->get_option('dxcc_clublog_update'))) : null),
					'next_run' => null
				),
			);
			$this->db->insert_batch('cron', $data);

			// since we transfered the source for the file update timestamps we don't need this options anymore
			$this->db->delete('options', array('option_name' => 'lotw_users_update'));
			$this->db->delete('options', array('option_name' => 'scp_update'));
			$this->db->delete('options', array('option_name' => 'dok_file_update'));
			$this->db->delete('options', array('option_name' => 'sota_file_update'));
			$this->db->delete('options', array('option_name' => 'wwff_file_update'));
			$this->db->delete('options', array('option_name' => 'pota_file_update'));
			$this->db->delete('options', array('option_name' => 'dxcc_clublog_update'));
		}
	}

	public function down() {

		$this->dbforge->drop_table('cron');

	}
}
