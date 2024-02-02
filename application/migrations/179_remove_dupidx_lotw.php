<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_remove_dupidx_lotw extends CI_Migration {

	public function up() {
		$prefixes_index = $this->db->query("SHOW INDEX FROM lotw_users WHERE Key_name = 'id'")->num_rows();
		if ($prefixes_index == 1) {
			$this->db->query("ALTER TABLE lotw_users DROP INDEX id");
		}
	}

	public function down(){
		$prefixes_index = $this->db->query("SHOW INDEX FROM lotw_users WHERE Key_name = 'id'")->num_rows();
		if ($prefixes_index == 0) {
			$this->db->query("ALTER TABLE lotw_users ADD UNIQUE INDEX id (id)");
		}
	}
}
