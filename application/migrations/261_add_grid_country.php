<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
        Create table vuccgrids for use in Gridmap country filtering
*/

class Migration_add_grid_country extends CI_Migration {

    public function up()
    {
		$sql = "CREATE TABLE IF NOT EXISTS vuccgrids (
			id INT AUTO_INCREMENT PRIMARY KEY,
			adif INT NOT NULL,
			gridsquare VARCHAR(8) NOT NULL,
			UNIQUE KEY uq_adif_grid (adif, gridsquare)
		);";

		$this->dbtry($sql);
    }

    public function down()
    {
		$sql = "DROP TABLE IF EXISTS vuccgrids;";
		$this->dbtry($sql);
    }

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering a table: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
