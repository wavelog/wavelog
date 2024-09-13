<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_version_check_to_cron extends CI_Migration {
	public function up() {
		if ($this->chk4cron('update_version_check') == 0) {
			$data = array(
				array(
					'id' => 'update_version_check',
					'enabled' => '0',
					'status' => 'pending',
					'description' => 'Check for new Wavelog releases',
					'function' => 'index.php/update/version_check',
					'expression' => '45 4 * * *',
					'last_run' => null,
					'next_run' => null
				));
			$this->db->insert_batch('cron', $data);
		}
	}

	public function down() {
		if ($this->chk4cron('update_version_check') > 0) {
			$this->db->query("delete from cron where id='update_version_check'");
		}
	}

	function chk4cron($cronkey) {
		$query = $this->db->query("select count(id) as cid from cron where id=?",$cronkey);
		$row = $query->row();
		return $row->cid ?? 0;
	}
}
