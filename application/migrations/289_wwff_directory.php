<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_wwff_directory extends CI_Migration {

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
		$this->dbforge->create_table('wwff_directory', TRUE);
	}

	public function down() {
		$this->dbforge->drop_table('wwff_directory', TRUE);
	}
}
