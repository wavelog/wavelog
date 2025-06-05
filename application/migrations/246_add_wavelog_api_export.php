<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_wavelog_api_export extends CI_Migration {

public function up() {
		if (!$this->db->field_exists('wavelog_apiurl', 'station_profile')) {
			$fields = array(
				'wavelog_apiurl varchar(100) DEFAULT NULL'
			);
			$this->dbforge->add_column('station_profile', $fields);
		}
		if (!$this->db->field_exists('wavelog_apikey', 'station_profile')) {
			$fields = array(
				'wavelog_apikey varchar(25) DEFAULT NULL'
			);
			$this->dbforge->add_column('station_profile', $fields);
		}
		if (!$this->db->field_exists('wavelog_profileid', 'station_profile')) {
			$fields = array(
				'wavelog_profileid int DEFAULT NULL'
			);
			$this->dbforge->add_column('station_profile', $fields);
		}

		if (!$this->db->table_exists('wavelog')) {
			$this->dbforge->add_field(array(
				'id' => array(
					'type' => 'INT',
					'auto_increment' => TRUE
				),
				'qso_id' => array(
					'type' => 'int',
				),
				'upload_date' => array(
					'type' => 'datetime',
				),
			));

			$this->dbforge->add_key('id', TRUE);
			$this->dbforge->add_key(array('qso_id','upload_date'), FALSE);

			$this->dbforge->create_table('wavelog');
		}
	}

	public function down() {
		$this->dbforge->drop_column('station_profile', 'wavelog_apiurl');
		$this->dbforge->drop_column('station_profile', 'wavelog_apikey');
		$this->dbforge->drop_column('station_profile', 'wavelog_profileid');
		$this->dbforge->drop_table('wavelog');
    }
}
