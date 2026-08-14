<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_cron_next_run_datetime extends CI_Migration {

	public function up() {
		$this->db->query("ALTER TABLE `cron` MODIFY `next_run` DATETIME NULL DEFAULT NULL");
	}

	public function down() {
		$this->db->query("ALTER TABLE `cron` MODIFY `next_run` TIMESTAMP NULL DEFAULT NULL");
	}
}
