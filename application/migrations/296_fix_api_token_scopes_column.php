<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_fix_api_token_scopes_column extends CI_Migration {
    public function up() {
        $this->db->query('ALTER TABLE `api_token` MODIFY COLUMN `scopes` TEXT NOT NULL');
    }

    public function down() {}
}
