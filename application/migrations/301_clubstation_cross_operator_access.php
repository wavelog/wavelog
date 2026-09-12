<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_clubstation_cross_operator_access extends CI_Migration {

	public function up() {
		$this->add_column_if_not_exists('users', 'allow_cross_operator_edit', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER direct_login_p_level');
		$this->add_column_if_not_exists('users', 'allow_cross_operator_delete', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_cross_operator_edit');
	}

	public function down() {
		if ($this->db->field_exists('allow_cross_operator_edit', 'users')) {
			$this->db->query("ALTER TABLE `users` DROP COLUMN allow_cross_operator_edit;");
		}
		if ($this->db->field_exists('allow_cross_operator_delete', 'users')) {
			$this->db->query("ALTER TABLE `users` DROP COLUMN allow_cross_operator_delete;");
		}
	}

	private function add_column_if_not_exists($table, $column, $definition) {
		$col_check = $this->db->query("SHOW COLUMNS FROM `$table` LIKE '$column';")->num_rows() > 0;
		if (!$col_check) {
			try {
				$this->db->query("ALTER TABLE `$table` ADD COLUMN $column $definition;");
				log_message('info', "Mig 301 - Column '$column' added to table '$table'.");
			} catch (Exception $e) {
				log_message('error', "Mig 301 - Error adding column '$column' to table '$table': " . $e->getMessage());
			}
		} else {
			log_message('info', "Mig 301 - Column '$column' already exists in table '$table', skipping ALTER TABLE.");
		}
	}
}
