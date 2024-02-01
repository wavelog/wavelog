<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/* 
* This adds a link between logo's and the different themes. This allows later to add a custom logo and a 
* logo which fits the theme design.
*/

class Migration_add_logo_to_themes extends CI_Migration {

	public function up()
	{
		// we add a column to set the path for the main logo
		if (!$this->db->field_exists('main_logo', 'themes')) {
			$fields = array(
				"main_logo varchar(50) DEFAULT 'wavelog_logo_default' AFTER foldername",
			);

			$this->dbforge->add_column('themes', $fields);
		}

		// and we set the path for the small header logo/icon
		if (!$this->db->field_exists('header_logo', 'themes')) {
			$fields = array(
				"header_logo varchar(50) DEFAULT 'wavelog_icon_only_default' AFTER foldername",
			);

			$this->dbforge->add_column('themes', $fields);
		}

		// Now we can set the default values for the main logo
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_cosmo' WHERE foldername = 'cosmo'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_cosmo_wide' WHERE foldername = 'cosmo_wide'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_cyborg' WHERE foldername = 'cyborg'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_cyborg_wide' WHERE foldername = 'cyborg_wide'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_darkly' WHERE foldername = 'darkly'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_darkly_wide' WHERE foldername = 'darkly_wide'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_default' WHERE foldername = 'default'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_default_wide' WHERE foldername = 'default_wide'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_superhero' WHERE foldername = 'superhero'");
		$this->db->query("UPDATE themes SET main_logo = 'wavelog_logo_superhero_wide' WHERE foldername = 'superhero_wide'");

		// and we set the default values for the header logo
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_cosmo' WHERE foldername = 'cosmo'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_cosmo_wide' WHERE foldername = 'cosmo_wide'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_cyborg' WHERE foldername = 'cyborg'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_cyborg_wide' WHERE foldername = 'cyborg_wide'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_darkly' WHERE foldername = 'darkly'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_darkly_wide' WHERE foldername = 'darkly_wide'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_default' WHERE foldername = 'default'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_default_wide' WHERE foldername = 'default_wide'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_superhero' WHERE foldername = 'superhero'");
		$this->db->query("UPDATE themes SET header_logo = 'wavelog_icon_only_superhero_wide' WHERE foldername = 'superhero_wide'");

	}

	public function down()
	{
		if ($this->db->field_exists('main_logo', 'themes')) {
			$this->dbforge->drop_column('themes', 'main_logo');
		}
		if ($this->db->field_exists('header_logo', 'themes')) {
			$this->dbforge->drop_column('themes', 'header_logo');
		}
	}
}
