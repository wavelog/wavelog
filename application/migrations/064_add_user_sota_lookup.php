<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This migration creates a table called options which will hold global options needed within wavelog
*   removing the need for lots of configuration files.
*/

class Migration_add_user_sota_lookup extends CI_Migration {

	public function up()
	{
		$fields = array(
			'user_sota_lookup integer DEFAULT 0',
		);

		$this->dbforge->add_column('users', $fields);
	}

	public function down()
	{
		$this->dbforge->drop_column('users', 'user_sota_lookup');
	}
}
