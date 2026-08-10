<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Stores per-park boundary geometry for the parks that have a published
// GeoJSON source (DE/AT/CH/CZ/DK/LU/LI from pota-map.info). A single park may
// span several non-contiguous parts (multi-part polygons, or linear parks as
// LineStrings), so one reference can have multiple rows — the activation
// planner merges them into a GeometryCollection on read. Coverage is partial:
// parks with no row keep their existing point marker. Populated by the
// update_pota_boundaries cron job, not here.
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
	}

	public function down() {
		$this->dbforge->drop_table('pota_boundaries', TRUE);
		$this->db->delete('cron', array('id' => 'update_pota_boundaries'));
	}
}
