<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_remove_dupidx_lotw extends CI_Migration {

	public function up() {
		$this->db->query("ALTER TABLE lotw_users DROP INDEX id");
	}

	public function down(){
		$this->db->query("ALTER TABLE lotw_users ADD UNIQUE INDEX id (id)");
	}
}
