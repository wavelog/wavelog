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

		// Populate the table immediately so autocomplete works out of the box
		// before the admin/cron runs the update. Best-effort: a network failure
		// must never break this migration (it runs on every login), so swallow
		// any error and leave filling to the normal update path.
		$CI =& get_instance();
		$CI->load->model('update_model');
		try {
			$result = $CI->update_model->pota();
			if (strncmp($result, 'DONE', 4) !== 0) {
				log_message('error', 'POTA initial import during migration 290: ' . $result);
			}
			$result = $CI->update_model->sota();
			if (strncmp($result, 'DONE', 4) !== 0) {
				log_message('error', 'SOTA initial import during migration 290: ' . $result);
			}
		} catch (\Throwable $e) {
			log_message('error', 'SOTA or POTA initial import (migration 290) failed: ' . $e->getMessage());
		}
	}

	public function down() {
		$this->dbforge->drop_table('pota_directory', TRUE);
		$this->dbforge->drop_table('sota_directory', TRUE);
	}
}
