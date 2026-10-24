<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_propagation_data_cron extends CI_Migration {
	public function up() {
		if ($this->db->table_exists('cron') &&
			$this->db->where('id', 'update_update_propagation')->get('cron')->num_rows() === 0) {
			$this->db->insert('cron', [
				'id' => 'update_update_propagation',
				'enabled' => '1',
				'status' => 'pending',
				'description' => 'Install and verify ITU-R propagation reference data',
				'function' => 'index.php/update/update_propagation',
				'expression' => '17 * * * *',
				'last_run' => null,
				'next_run' => null,
			]);
		}
	}

	public function down() {
		$this->db->where('id', 'update_update_propagation')->delete('cron');
	}
}
