<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
 *   This adds an option to enable grid and name lookup
 *   for WWFF references
*/

class Migration_login_attempts extends CI_Migration {

	public function up()
	{
		if (!$this->db->field_exists('login_attempts', 'users')) {
			$fields = array(
				'login_attempts integer DEFAULT 0'
			);

			$this->dbforge->add_column('users', $fields);
		}
	}

	public function down()
	{
		if ($this->db->field_exists('login_attempts', 'users')) {
			$this->dbforge->drop_column('users', 'login_attempts');
		}
	}
}
