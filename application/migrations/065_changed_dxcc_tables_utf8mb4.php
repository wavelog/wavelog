<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
*   This migration converts the dxcc_* tables to a utf8mb4 charachter set.
*/

class Migration_changed_dxcc_tables_utf8mb4 extends CI_Migration {

	public function up()
	{
        $this->db->query("ALTER TABLE dxcc_entities CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
		$this->db->query("ALTER TABLE dxcc_exceptions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
        $this->db->query("ALTER TABLE dxcc_prefixes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
	}

	public function down()
	{

	}
}
