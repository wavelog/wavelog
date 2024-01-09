<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_primary_subdivisions extends CI_Migration
{
	public function up()
	{
		if (!$this->db->table_exists('primary_subdivisions')) {
			$this->dbforge->add_field(array(
				'adif' => array(
					'type' => 'SMALLINT',
					'constraint' => 20,
					'unsigned' => TRUE,
				),
				'state' => array(
					'type' => 'VARCHAR',
					'constraint' => 20,
					'null' => FALSE,
				),
				'subdivision' => array(
					'type' => 'VARCHAR',
					'constraint' => 250,
					'null' => FALSE,
				),
			));
			$this->dbforge->create_table('primary_subdivisions');
			$data = array(
				array('adif' => 339, 'state' => '01', 'subdivision' => 'Hokkaido'),
			);
			$this->db->insert_batch('primary_subdivisions', $data);
		}
	}

	public function down()
	{
		$this->dbforge->drop_table('primary_subdivisions', 'TRUE');
	}
}
