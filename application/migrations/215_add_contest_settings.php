<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_contest_settings extends CI_Migration {

    public function up()
    {
		$fields = array(
			'settings TEXT default NULL',
		);

		if (!$this->db->field_exists('settings', 'contest_session')) {
			$this->dbforge->add_column('contest_session', $fields);
		}
	}

    public function down()
    {
		if ($this->db->field_exists('settings', 'contest_session')) {
			$this->dbforge->drop_column('contest_session', 'settings');
		}
	}
}
