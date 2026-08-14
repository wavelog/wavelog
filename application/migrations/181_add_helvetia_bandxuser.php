<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_add_helvetia_bandxuser extends CI_Migration
{
	public function up()
	{
		$fields = array(
			'helvetia TINYINT NOT NULL DEFAULT 1',
		);

		if (!$this->db->field_exists('helvetia', 'bandxuser')) {
			$this->dbforge->add_column('bandxuser', $fields);
		}
	}

	public function down()
	{
		if ($this->db->field_exists('helvetia', 'bandxuser')) {
			$this->dbforge->drop_column('bandxuser', 'helvetia');
		}
	}
}
