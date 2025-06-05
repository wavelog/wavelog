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
	}

	public function down() {
		$this->dbforge->drop_column('station_profile', 'wavelog_apiurl');
		$this->dbforge->drop_column('station_profile', 'wavelog_apikey');
		$this->dbforge->drop_column('station_profile', 'wavelog_profileid');
    }
}
