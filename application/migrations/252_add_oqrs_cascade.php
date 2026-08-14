<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_oqrs_cascade extends CI_Migration {

	public function up() {
			$this->dbtry("ALTER TABLE oqrs MODIFY COLUMN qsoid BIGINT(20) UNSIGNED;");
			$this->dbtry("ALTER TABLE oqrs ADD UNIQUE (qsoid)");
			$this->dbtry("ALTER TABLE oqrs ADD CONSTRAINT oqrs_logbook_fk FOREIGN KEY (qsoid) REFERENCES " . $this->config->item('table_name') . " (COL_PRIMARY_KEY) ON DELETE CASCADE ON UPDATE RESTRICT;");
			$this->dbtry("ALTER TABLE oqrs MODIFY requesttime TIMESTAMP DEFAULT CURRENT_TIMESTAMP;");
	}

	public function down(){
			$this->dbtry("ALTER TABLE oqrs MODIFY COLUMN qsoid INT;");
			$this->dbtry("ALTER TABLE oqrs DROP UNIQUE (qsoid);");
			$this->dbtry("ALTER TABLE oqrs DROP FOREIGN KEY oqrs_logbook_fk;");
	}
	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering the OQRS table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
