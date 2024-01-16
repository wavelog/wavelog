<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_theme_mode extends CI_Migration {

	public function up() {
		$fields = array(
			'theme_mode VARCHAR(20) AFTER foldername',
		);

		if (!$this->db->field_exists('theme_mode', 'themes')) {
			$this->dbforge->add_column('themes', $fields);
		}

		$this->db->query("UPDATE themes SET theme_mode = 'light' WHERE foldername = 'cosmo'");
		$this->db->query("UPDATE themes SET theme_mode = 'light' WHERE foldername = 'cosmo_wide'");
		$this->db->query("UPDATE themes SET theme_mode = 'dark' WHERE foldername = 'cyborg'");
		$this->db->query("UPDATE themes SET theme_mode = 'dark' WHERE foldername = 'cyborg_wide'");
		$this->db->query("UPDATE themes SET theme_mode = 'dark' WHERE foldername = 'darkly'");
		$this->db->query("UPDATE themes SET theme_mode = 'dark' WHERE foldername = 'darkly_wide'");
		$this->db->query("UPDATE themes SET theme_mode = 'light' WHERE foldername = 'default'");
		$this->db->query("UPDATE themes SET theme_mode = 'light' WHERE foldername = 'default_wide'");
		$this->db->query("UPDATE themes SET theme_mode = 'dark' WHERE foldername = 'superhero'");
		$this->db->query("UPDATE themes SET theme_mode = 'dark' WHERE foldername = 'superhero_wide'");
	}


	public function down(){
		if ($this->db->field_exists('theme_mode', 'themes')) {
			$this->dbforge->drop_column('themes', 'theme_mode');
		}
	}
}
