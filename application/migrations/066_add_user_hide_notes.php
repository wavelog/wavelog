<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This allows the user to disable notes in the header menu.
*/

class Migration_add_user_hide_notes extends CI_Migration {

	public function up()
	{
		$fields = array(
			'user_show_notes integer DEFAULT 1',
		);

		$this->dbforge->add_column('users', $fields);
	}

	public function down()
	{
		$this->dbforge->drop_column('users', 'user_show_notes');
	}
}
