<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_h26_bandxuser extends CI_Migration
{
	public function up()
	{
		$fields = array(
			'h26 TINYINT NOT NULL DEFAULT 1',
		);

		if (!$this->db->field_exists('h26', 'bandxuser')) {
			$this->dbforge->add_column('bandxuser', $fields);
		}
	}

	public function down()
	{
		if ($this->db->field_exists('h26', 'bandxuser')) {
			$this->dbforge->drop_column('bandxuser', 'h26');
		}
	}
}
