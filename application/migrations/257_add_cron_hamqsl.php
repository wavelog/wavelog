<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Migration_add_cron_hamqsl extends CI_Migration {

	public function up() {
		// Add cron job for HAMqsl update
		if ($this->db->table_exists('cron')) {

			// add cron job for HAMqsl update
			$data = array(
				array(
					'id' => 'update_update_hamqsl',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Download HamQSL HF Propagation Tools and Solar Data',
					'function' => 'index.php/update/update_hamqsl',
					'expression' => '0 */1 * * *',
					'last_run' => null,
					'next_run' => null
				)
			);

			// Check if the cron job already exists
			$this->db->where('id', 'update_update_hamqsl');
			$query = $this->db->get('cron');

			if ($query->num_rows() == 0) {
				// Insert the cron job only if it does not exist
				$this->db->insert_batch('cron', $data);
			}
		}
	}

	public function down() {
		// Remove cron job for HAMqsl update
		if ($this->chk4cron('update_update_hamqsl') > 0) {
			$this->db->query("delete from cron where id = 'update_update_hamqsl';");
		}
	}

	function chk4cron($cronkey) {
		// Check if a cron job with the given ID exists
		$query = $this->db->query("select count(id) as cid from cron where id=?",$cronkey);
		$row = $query->row();
		return $row->cid ?? 0;
	}
}
