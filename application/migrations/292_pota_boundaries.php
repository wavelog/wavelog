<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_pota_boundaries extends CI_Migration {

	public function up() {
		if (!$this->db->table_exists('pota_boundaries')) {
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
			$this->dbforge->add_key('id', TRUE);     // surrogate primary key
			$this->dbforge->add_key('reference');    // lookup index (non-unique)
			$this->dbforge->add_key('source');       // import-time DELETE … WHERE source
			$this->dbforge->create_table('pota_boundaries', TRUE);
		}

		// Register the cron job if the cron table already exists (it is created
		// in migration 196). Use a monthly slot offset from the POTA directory
		// CSV job (00:15) so the two never contend.
		if ($this->db->table_exists('cron')) {
			$exists = $this->db->where('id', 'update_pota_boundaries')->count_all_results('cron');
			if ($exists == 0) {
				$this->db->insert('cron', array(
					'id' => 'update_pota_boundaries',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update POTA park boundaries (GeoJSON)',
					'function' => 'index.php/update/update_pota_boundaries',
					'expression' => '30 0 1 * *',
					'last_run' => null,
					'next_run' => null,
					'modified' => date('Y-m-d H:i:s'),
				));
			}
		}

		$validity_fields = array(
			'valid_from' => array(
				'type' => 'DATE',
				'null' => TRUE,
				'default' => null,
			),
			'valid_till' => array(
				'type' => 'DATE',
				'null' => TRUE,
				'default' => null,
			),
		);
		if ($this->db->table_exists('sota_directory') && !$this->db->field_exists('valid_from', 'sota_directory')) {
			$this->dbforge->add_column('sota_directory', $validity_fields);
		}
		if ($this->db->table_exists('wwff_directory') && !$this->db->field_exists('valid_from', 'wwff_directory')) {
			$this->dbforge->add_column('wwff_directory', $validity_fields);
		}

		// Last-activation fields ingested from the SOTA/WWFF directory CSVs.
		// SOTA also carries the activator's callsign; WWFF does not.
		$sota_activation_fields = array(
			'last_activated' => array(
				'type' => 'DATE',
				'null' => TRUE,
				'default' => null,
			),
			'last_activator' => array(
				'type' => 'VARCHAR',
				'constraint' => 32,
				'null' => TRUE,
				'default' => null,
			),
		);
		$wwff_activation_fields = array(
			'last_activated' => array(
				'type' => 'DATE',
				'null' => TRUE,
				'default' => null,
			),
		);
		if ($this->db->table_exists('sota_directory') && !$this->db->field_exists('last_activated', 'sota_directory')) {
			$this->dbforge->add_column('sota_directory', $sota_activation_fields);
		}
		if ($this->db->table_exists('wwff_directory') && !$this->db->field_exists('last_activated', 'wwff_directory')) {
			$this->dbforge->add_column('wwff_directory', $wwff_activation_fields);
		}
	}

	public function down() {
		$this->dbforge->drop_table('pota_boundaries', TRUE);
		$this->db->delete('cron', array('id' => 'update_pota_boundaries'));

		if ($this->db->table_exists('sota_directory')) {
			if ($this->db->field_exists('valid_from', 'sota_directory')) {
				$this->dbforge->drop_column('sota_directory', 'valid_from');
			}
			if ($this->db->field_exists('valid_till', 'sota_directory')) {
				$this->dbforge->drop_column('sota_directory', 'valid_till');
			}
			if ($this->db->field_exists('last_activated', 'sota_directory')) {
				$this->dbforge->drop_column('sota_directory', 'last_activated');
			}
			if ($this->db->field_exists('last_activator', 'sota_directory')) {
				$this->dbforge->drop_column('sota_directory', 'last_activator');
			}
		}
		if ($this->db->table_exists('wwff_directory')) {
			if ($this->db->field_exists('valid_from', 'wwff_directory')) {
				$this->dbforge->drop_column('wwff_directory', 'valid_from');
			}
			if ($this->db->field_exists('valid_till', 'wwff_directory')) {
				$this->dbforge->drop_column('wwff_directory', 'valid_till');
			}
			if ($this->db->field_exists('last_activated', 'wwff_directory')) {
				$this->dbforge->drop_column('wwff_directory', 'last_activated');
			}
		}
	}
}
