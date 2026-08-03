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

		// Populate the table immediately so autocomplete works out of the box
		// before the admin/cron runs the update. Best-effort: a network failure
		// must never break this migration (it runs on every login), so swallow
		// any error and leave filling to the normal update path.
		$CI =& get_instance();
		$CI->load->model('update_model');
		try {
			$result = $CI->update_model->wwff();
			if (strncmp($result, 'DONE', 4) !== 0) {
				log_message('error', 'WWFF initial import during migration 289: ' . $result);
			}
		} catch (\Throwable $e) {
			log_message('error', 'WWFF initial import (migration 289) failed: ' . $e->getMessage());
		}
	}

	public function down() {
		$this->dbforge->drop_table('wwff_directory', TRUE);
	}
}
