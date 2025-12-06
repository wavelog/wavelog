<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_cron_rss_feeds extends CI_Migration {

	public function up() {
		// Add cron job for RSS feeds update
		if ($this->db->table_exists('cron')) {

			$data = array(
				array(
					'id' => 'update_update_rss_feeds',
					'enabled' => '1',
					'status' => 'pending',
					'description' => 'Update RSS feeds for DX Calendar and Contest Calendar',
					'function' => 'index.php/update/update_rss_feeds',
					'expression' => '0 */12 * * *',
					'last_run' => null,
					'next_run' => null
				)
			);

			// Check if the cron job already exists
			$this->db->where('id', 'update_update_rss_feeds');
			$query = $this->db->get('cron');

			if ($query->num_rows() == 0) {
				// Insert the cron job only if it does not exist
				$this->db->insert_batch('cron', $data);
			}
		}
	}

	public function down() {
		// Remove cron job for RSS feeds update
		if ($this->chk4cron('update_update_rss_feeds') > 0) {
			$this->db->query("delete from cron where id = 'update_update_rss_feeds';");
		}
	}

	function chk4cron($cronkey) {
		// Check if a cron job with the given ID exists
		$query = $this->db->query("select count(id) as cid from cron where id=?", $cronkey);
		$row = $query->row();
		return $row->cid ?? 0;
	}
}
