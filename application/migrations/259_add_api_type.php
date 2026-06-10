<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Migration_Add_api_type extends CI_Migration {

    public function up() {
        $this->db->query(
            "ALTER TABLE `api` ADD COLUMN `api_type` VARCHAR(50) NOT NULL DEFAULT 'standard' AFTER `rights`"
        );
    }

    public function down() {
        $this->db->query("ALTER TABLE `api` DROP COLUMN `api_type`");
    }
}
