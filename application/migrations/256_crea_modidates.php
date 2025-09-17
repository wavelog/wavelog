<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_crea_modidates extends CI_Migration {
	protected array $tables;

	public function __construct() {
		$this->tables = [$this->config->item('table_name'), 'adif_modes','api','bandedges','bands','bandxuser','cat','club_permissions','contest','contest_session','cron','cwmacros','dxpedition','eQSL_images','hams_of_note','iota','label_types','lotw_certs','lotw_users','migrations','notes','oqrs','paper_types','primary_subdivisions','qsl_images','queries','satellite','satellitemode','station_logbooks','station_logbooks_relationship','station_profile','themes','thirdparty_logins','timezones','tle','user_options','users','webadif'];
	}

	public function up() {
		foreach ($this->tables as $tname) {
			$this->add_create_modi($tname);
		}
	}

	public function down(){
		foreach ($this->tables as $tname) {
			$this->rm_create_modi($tname);
		}
	}

	function add_create_modi($table) {
			$this->dbtry("ALTER TABLE ".$table." ADD COLUMN `CREATION_DATE` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,  ADD COLUMN `LAST_MODIFIED` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;");
	}

	function rm_create_modi($table) {
			$this->dbtry("ALTER TABLE ".$table." DROP COLUMN IF EXISTS `CREATION_DATE`, DROP COLUMN IF EXISTS `LAST_MODIFIED`;");
	}

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering the OQRS table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}

