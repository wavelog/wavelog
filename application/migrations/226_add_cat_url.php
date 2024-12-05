<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_cat_url extends CI_Migration {

	public function up() {
		$fields = array(
			'cat_url' => array(
				'type' => 'VARCHAR',
				'constraint' => '255',
				'null' => FALSE,
				'default' => 'http://127.0.0.1:54321'
			),
		);

		if (!$this->db->field_exists('cat_url', 'cat')) {
			$this->dbforge->add_column('cat', $fields);
		}
	}

	public function down() {
		if ($this->db->field_exists('cat_url', 'cat')) {
			$this->dbforge->drop_column('cat', 'cat_url');
		}
	}
}
