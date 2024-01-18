<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This migration adds a fifth column recarding to migration no. 69
*/

class Migration_add_fifth_column extends CI_Migration {

	public function up()
	{
		$fields = array(
			'user_column5 varchar(32) default "Country"',
		);

		$this->dbforge->add_column('users', $fields);
	}

	public function down()
	{
		$this->dbforge->drop_column('users', 'user_column5');
	}
}
