<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_user_language extends CI_Migration {

	// Rename 'language' column in users table to 'user_language'

	public function up() {
		$fields = $this->db->field_data('users');

		foreach ($fields as $field) {
			if ($field->name == 'language') {
				$this->db->query('ALTER TABLE users CHANGE language user_language VARCHAR(32)');
			}
		}
	}

	public function down() {
		$this->db->query('ALTER TABLE users CHANGE user_language language VARCHAR(32)');
	}
}
