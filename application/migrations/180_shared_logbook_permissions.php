<?php

defined('BASEPATH') or exit('No direct script access allowed');

// This migration introduces shareable logbooks for a multiuser feature 

class Migration_shared_logbook_permissions extends CI_Migration
{

	public function up()
	{
		// first we add a shared flag to station_logbooks
		if (!$this->db->field_exists('shared', 'station_logbooks')) {
			$fields = array(
				"shared integer DEFAULT 0 AFTER public_search",
			);

			$this->dbforge->add_column('station_logbooks', $fields);
		}

		// and we create a new table for permission levels
		if (!$this->db->table_exists('shared_logbook_permissions')) {
			$this->db->query("CREATE TABLE `shared_logbook_permissions` ( `logbook_id` int(11) NOT NULL, `user_id` int(11) NOT NULL, `permission_level` int(11) NOT NULL, `modified` timestamp NOT NULL, `modified_by_user` int(11) NOT NULL, PRIMARY KEY (`logbook_id`,`user_id`,`permission_level`), INDEX (`modified`, `modified_by_user`))");
		}
	}

	public function down()
	{
		if ($this->db->field_exists('shared', 'station_logbooks')) {
			$this->dbforge->drop_column('station_logbooks', 'shared');
		}

		if ($this->db->table_exists('shared_logbook_permissions')) {
			$this->dbforge->drop_table('shared_logbook_permissions');
		}
	}
}
