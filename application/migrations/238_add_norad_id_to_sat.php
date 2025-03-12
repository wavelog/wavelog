<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_norad_id_to_sat extends CI_Migration {

	public function up() {
		if ($this->db->table_exists('satellite')) {
			$fields = array(
				'norad_id' => array(
					'type' => 'INT',
					'constraint' => 9,
					'unsigned' => TRUE,
					'null' => TRUE,
					'default' => NULL
				),
			);
			$this->dbforge->add_column('satellite', $fields);
		}
	}

	public function down() {
		if ($this->db->table_exists('satellite')) {
			if ($this->db->field_exists('norad_id', 'satellite')) {
				$this->dbforge->drop_column('satellite', 'norad_id');
			}
		}
	}
}
