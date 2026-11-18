<?php

defined('BASEPATH') or exit('No direct script access allowed');

/* Generic WWFF boundary storage. */
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
					'type' => 'VARCHAR',
					'constraint' => 4,
					'null' => FALSE,
				),
			));
			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_key('reference');
			$this->dbforge->add_key('source');
			$this->dbforge->create_table('wwff_boundaries', TRUE);
		} else {
			// Normalize tables created by earlier revisions of this feature branch.
			$this->dbforge->modify_column('wwff_boundaries', array(
				'source' => array(
					'name' => 'source',
					'type' => 'VARCHAR',
					'constraint' => 4,
					'null' => FALSE,
				),
			));
			$this->db->where('source', 'DL')->update('wwff_boundaries', array('source' => 'DLFF'));
		}

		// Register the cron job if the cron table exists (created in migration 196).
		if ($this->db->table_exists('cron')) {
			$exists = $this->db->where('id', 'update_wwff_boundaries')->count_all_results('cron');
			if ($exists == 0) {
				$this->db->insert('cron', array(
					'id' => 'update_wwff_boundaries',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update WWFF reference boundaries',
					'function' => 'index.php/update/update_wwff_boundaries',
					'expression' => '45 0 1 * *',
					'last_run' => null,
					'next_run' => null,
					'modified' => date('Y-m-d H:i:s'),
				));
			}
		}
	}

	public function down() {
		$this->dbforge->drop_table('wwff_boundaries', TRUE);
		$this->db->delete('cron', array('id' => 'update_wwff_boundaries'));
		$this->db->delete('options', array('option_name' => 'wwff_boundaries_last_modified_dlff'));
	}
}
