<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_qsl_methods_extend extends CI_Migration {

	public function up() {
		$this->db->query("ALTER TABLE users CHANGE COLUMN `user_default_confirmation` `user_default_confirmation` VARCHAR(10) NULL DEFAULT NULL"); // Do 10 Characters for Future Use
	}

	public function down(){
		// Down is senseless, since value in field could be longer than 4
        }
}
