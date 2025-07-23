<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_wapc_bandxuser extends CI_Migration
{
	public function up()
	{
		$fields = array(
			'wapc TINYINT NOT NULL DEFAULT 1',
		);

		if (!$this->db->field_exists('wapc', 'bandxuser')) {
			$this->dbforge->add_column('bandxuser', $fields);
		}
	}

	public function down()
	{
		if ($this->db->field_exists('wapc', 'bandxuser')) {
			$this->dbforge->drop_column('bandxuser', 'wapc');
		}
	}
}
