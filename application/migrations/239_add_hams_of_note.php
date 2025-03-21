<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_hams_of_note extends CI_Migration
{
	public function up() {
		if (!$this->db->table_exists('hams_of_note')) {
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'constraint' => 20,
					'unsigned' => TRUE,
					'auto_increment' => TRUE,
					'unique' => TRUE
				),
				'callsign' => array(
					'type' => 'VARCHAR',
					'constraint' => 32,
					'unsigned' => TRUE,
				),
				'description' => array(
					'type' => 'VARCHAR',
					'constraint' => 256,
					'unsigned' => TRUE,
				),
				'linkname' => array(
					'type' => 'VARCHAR',
					'constraint' => 256,
					'unsigned' => TRUE,
					'null' => TRUE,
					'default' => '',
				),
				'link' => array(
					'type' => 'VARCHAR',
					'constraint' => 256,
					'unsigned' => TRUE,
					'null' => TRUE,
					'default' => '',
				),
			));
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->create_table('hams_of_note');
			$this->db->query("ALTER TABLE hams_of_note ADD INDEX `callsign` (`callsign`)");
		}
	}

	public function down() {
		if ($this->db->table_exists('hams_of_note')) {
			$this->dbforge->drop_table('hams_of_note');
		}
	}
}
