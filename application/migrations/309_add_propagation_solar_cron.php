<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_propagation_solar_cron extends CI_Migration {
	public function up() {
		if ($this->db->table_exists('cron') &&
			$this->db->where('id', 'update_update_propagation_solar')->get('cron')->num_rows() === 0) {
			$this->db->insert('cron', [
				'id' => 'update_update_propagation_solar',
				'enabled' => '1',
				'status' => 'pending',
				'description' => 'Update propagation smoothed sunspot numbers (NOAA)',
				'function' => 'index.php/update/update_propagation_solar',
				'expression' => '23 2 * * *',
				'last_run' => null,
				'next_run' => null,
			]);
		}
	}

	public function down() {
		$this->db->where('id', 'update_update_propagation_solar')->delete('cron');
	}
}
