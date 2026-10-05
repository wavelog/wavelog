<?php

defined('BASEPATH') or exit('No direct script access allowed');

/* WWFF reference boundaries (mirror of pota_boundaries) + monthly cron 00:45. */
class Migration_wwff_boundaries extends CI_Migration {

	public function up() {
		if (!$this->db->table_exists('wwff_boundaries')) {
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'BIGINT',
					'constraint' => 20,
					'unsigned' => TRUE,
					'auto_increment' => TRUE,
				),
				'reference' => array(
					'type' => 'VARCHAR',
					'constraint' => 20,
					'null' => FALSE,
				),
				'geom' => array(
					'type' => 'LONGTEXT',
					'null' => FALSE,
				),
				'source' => array(
					'type' => 'CHAR',
					'constraint' => 2,
					'null' => FALSE,
				),
			));
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_key('reference');
			$this->dbforge->add_key('source');
			$this->dbforge->create_table('wwff_boundaries', TRUE);
		}

		// Register the cron job if the cron table exists (created in migration 196).
		if ($this->db->table_exists('cron')) {
			$exists = $this->db->where('id', 'update_wwff_boundaries')->count_all_results('cron');
			if ($exists == 0) {
				$this->db->insert('cron', array(
					'id' => 'update_wwff_boundaries',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update WWFF reference boundaries (DLFF GeoJSON)',
					'function' => 'index.php/update/update_wwff_boundaries',
					'expression' => '45 0 1 * *',
					'last_run' => null,
					'next_run' => null,
					'modified' => date('Y-m-d H:i:s'),
				));
			}
		}

		// Best-effort initial import; failures are left to the cron/manual update path.
		$CI =& get_instance();
		$CI->load->model('update_model');
		try {
			$result = $CI->update_model->wwff_boundaries();
			if (strncmp($result, 'DONE', 4) !== 0) {
				log_message('error', 'WWFF-Boundaries initial import during migration 303: ' . $result);
			}
		} catch (\Throwable $e) {
			log_message('error', 'WWFF-Boundaries initial import (migration 303) failed: ' . $e->getMessage());
		}
	}

	public function down() {
		$this->dbforge->drop_table('wwff_boundaries', TRUE);
		$this->db->delete('cron', array('id' => 'update_wwff_boundaries'));
	}
}
