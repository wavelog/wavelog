<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_openhamclock_integration extends CI_Migration
{
	public function up()
	{
		// Connection details belong to the user and can be shared by their station profiles.
		if (!$this->db->field_exists('user_openhamclock_url', $this->config->item('auth_table'))) {
			$this->dbforge->add_column($this->config->item('auth_table'), array(
				'user_openhamclock_url VARCHAR(255) DEFAULT NULL',
			));
		}

		if (!$this->db->field_exists('user_openhamclock_api_key', $this->config->item('auth_table'))) {
			$this->dbforge->add_column($this->config->item('auth_table'), array(
				'user_openhamclock_api_key VARCHAR(255) DEFAULT NULL',
			));
		}

		// Realtime upload can be enabled independently for each station profile.
		if (!$this->db->field_exists('openhamclockrealtime', 'station_profile')) {
			$this->dbforge->add_column('station_profile', array(
				'openhamclockrealtime TINYINT NOT NULL DEFAULT 0',
			));
		}
	}

	public function down()
	{
		if ($this->db->field_exists('openhamclockrealtime', 'station_profile')) {
			$this->dbforge->drop_column('station_profile', 'openhamclockrealtime');
		}

		if ($this->db->field_exists('user_openhamclock_api_key', $this->config->item('auth_table'))) {
			$this->dbforge->drop_column($this->config->item('auth_table'), 'user_openhamclock_api_key');
		}

		if ($this->db->field_exists('user_openhamclock_url', $this->config->item('auth_table'))) {
			$this->dbforge->drop_column($this->config->item('auth_table'), 'user_openhamclock_url');
		}
	}
}
