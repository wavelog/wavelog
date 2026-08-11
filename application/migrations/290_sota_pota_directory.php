<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_sota_pota_directory extends CI_Migration {

	public function up() {
		$this->dbforge->add_field(array(
			'reference' => array(
				'type' => 'VARCHAR',
				'constraint' => 20,
				'null' => FALSE,
			),
			'name' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,
			),
			'active' => array(
				'type' => 'INTEGER',
				'constraint' => 255,
				'null' => TRUE,
			),
			'entityid' => array(
				'type' => 'INTEGER',
				'null' => TRUE,
			),
			'locationdesc' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,
			),
			'lat' => array(
				'type' => 'DECIMAL',
				'constraint' => '10,7',
				'null' => TRUE,
			),
			'lon' => array(
				'type' => 'DECIMAL',
				'constraint' => '10,7',
				'null' => TRUE,
			),
			'gridsquare' => array(
				'type' => 'VARCHAR',
				'constraint' => 12,
				'null' => TRUE,
			),
		));
		$this->dbforge->add_key('reference', TRUE);
		$this->dbforge->create_table('pota_directory', TRUE);

		$this->dbforge->add_field(array(
			'reference' => array(
				'type' => 'VARCHAR',
				'constraint' => 20,
				'null' => FALSE,
			),
			'name' => array(
				'type' => 'VARCHAR',
				'constraint' => 255,
				'null' => TRUE,
			),
			'altitude' => array(
				'type' => 'INTEGER',
				'null' => TRUE,
			),
			'lat' => array(
				'type' => 'DECIMAL',
				'constraint' => '10,7',
				'null' => TRUE,
			),
			'lon' => array(
				'type' => 'DECIMAL',
				'constraint' => '10,7',
				'null' => TRUE,
			),
		));
		$this->dbforge->add_key('reference', TRUE);
		$this->dbforge->create_table('sota_directory', TRUE);
	}

	public function down() {
		$this->dbforge->drop_table('pota_directory', TRUE);
		$this->dbforge->drop_table('sota_directory', TRUE);
	}
}
