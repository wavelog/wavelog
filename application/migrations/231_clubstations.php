<?php

class Migration_clubstations extends CI_Migration {

    public function up() {
        // Add the clubstation flag to the users table
        $this->add_column_if_not_exists('users', 'clubstation', 'TINYINT(1) DEFAULT 0 AFTER user_type');

        // Create a new table for the club permissions
        if (!$this->db->table_exists('club_permissions')) {
            try {
                $this->db->query("CREATE TABLE `club_permissions` (
                    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    club_id INT(6) UNSIGNED NOT NULL,
                    user_id INT(6) UNSIGNED NOT NULL,
                    p_level INT(2) UNSIGNED NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    CONSTRAINT fk_user_id FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE RESTRICT,
                    UNIQUE (user_id, club_id)
                );");
            } catch (Exception $e) {
                log_message('error', 'Mig 230 - Error creating table "club_permissions": ' . $e->getMessage());
            }
        }

        // Add 'created_by' column to 'api' table
        $this->add_column_if_not_exists('api', 'created_by', 'INT(6) NOT NULL DEFAULT 0');
        $this->db->query("UPDATE `api` SET created_by = user_id;");
        $this->db->query("ALTER TABLE `api` MODIFY created_by INT(6) NOT NULL;");

        // Add 'created_at' column to 'api' table
        $this->add_column_if_not_exists('api', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');

        // Add 'operator' column to 'cat' table
        $this->add_column_if_not_exists('cat', 'operator', 'INT(6) NOT NULL DEFAULT 0');
        $this->db->query("UPDATE `cat` SET operator = user_id;");
        $this->db->query("ALTER TABLE `cat` MODIFY operator INT(6) NOT NULL;");
    }

	public function down() {
        // we don't want to loose data in case of a down migration, so we don't drop the column here
    }

    private function add_column_if_not_exists($table, $column, $definition) {
        $col_check = $this->db->query("SHOW COLUMNS FROM `$table` LIKE '$column';")->num_rows() > 0;
        if (!$col_check) {
            try {
                $this->db->query("ALTER TABLE `$table` ADD COLUMN $column $definition;");
                log_message('info', "Mig 230 - Column '$column' added to table '$table'.");
            } catch (Exception $e) {
                log_message('error', "Mig 230 - Error adding column '$column' to table '$table': " . $e->getMessage());
            }
        } else {
            log_message('info', "Mig 230 - Column '$column' already exists in table '$table', skipping ALTER TABLE.");
        }
    }
}