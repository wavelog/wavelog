<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_clubstation_direct_login extends CI_Migration {

	public function up() {
		$this->add_column_if_not_exists('users', 'direct_login_enabled', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER clubstation');
		$this->add_column_if_not_exists('users', 'direct_login_p_level', 'INT(2) UNSIGNED NOT NULL DEFAULT 9 AFTER direct_login_enabled');
	}

	public function down() {
		if ($this->db->field_exists('direct_login_enabled', 'users')) {
			$this->db->query("ALTER TABLE `users` DROP COLUMN direct_login_enabled;");
		}
		if ($this->db->field_exists('direct_login_p_level', 'users')) {
			$this->db->query("ALTER TABLE `users` DROP COLUMN direct_login_p_level;");
		}
	}

	private function add_column_if_not_exists($table, $column, $definition) {
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table` LIKE '$column';")->num_rows() > 0;
		if (!$col_check) {
			try {
				$this->db->query("ALTER TABLE `$table` ADD COLUMN $column $definition;");
				log_message('info', "Mig 300 - Column '$column' added to table '$table'.");
			} catch (Exception $e) {
				log_message('error', "Mig 300 - Error adding column '$column' to table '$table': " . $e->getMessage());
			}
		} else {
			log_message('info', "Mig 300 - Column '$column' already exists in table '$table', skipping ALTER TABLE.");
		}
	}
}
