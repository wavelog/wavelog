<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
* The autoload column of the options table was never used to actually preload options.
* Every option is fetched on demand, so the column is dropped.
*/

class Migration_drop_options_autoload extends CI_Migration {

	public function up()
	{
		if ($this->db->field_exists('autoload', 'options')) {
			$this->dbforge->drop_column('options', 'autoload');
		}
	}

	public function down()
	{
		if (!$this->db->field_exists('autoload', 'options')) {
			$this->dbforge->add_column('options', array(
				"autoload varchar(20) DEFAULT NULL",
			));

			// mastercron_last_run was the only option that was never autoloaded
			$this->db->where('option_name !=', 'mastercron_last_run');
			$this->db->update('options', array('autoload' => 'yes'));
		}
	}
}
