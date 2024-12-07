<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_tle_cron extends CI_Migration {
	public function up() {
		if ($this->chk4cron('update_update_tle') == 0) {
			$data = array(
				array(
					'id' => 'update_update_tle',
					'enabled' => '0',
					'status' => 'disabled',
					'description' => 'Update TLE for satellites',
					'function' => 'index.php/update/update_tle',
					'expression' => '45 4 * * *',
					'last_run' => null,
					'next_run' => null
				));
			$this->db->insert_batch('cron', $data);
		}
	}

	public function down() {
		if ($this->chk4cron('update_tle') > 0) {
			$this->db->query("delete from cron where id='update_update_tle'");
		}
	}

	function chk4cron($cronkey) {
		$query = $this->db->query("select count(id) as cid from cron where id=?",$cronkey);
		$row = $query->row();
		return $row->cid ?? 0;
	}
}
