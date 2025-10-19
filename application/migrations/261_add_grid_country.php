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

		if ($this->chk4cron('vucc_grid_file') == 0) {
			$data = array(
				array(
					'id' => 'vucc_grid_file',
					'enabled' => '0',
					'status' => 'disabled',
					'description' => 'Update TQSL VUCC Grids file',
					'function' => 'index.php/update/update_vucc_grids',
					'expression' => '45 4 * * *',
					'last_run' => null,
					'next_run' => null
				));
			$this->db->insert_batch('cron', $data);
		}
    }

    public function down()
    {
		$sql = "DROP TABLE IF EXISTS vuccgrids;";
		$this->dbtry($sql);

		if ($this->chk4cron('vucc_grid_file') > 0) {
			$this->db->query("delete from cron where id='vucc_grid_file'");
		}
    }

	function dbtry($what) {
		try {
			$this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Something gone wrong while altering a table: ".$e." // Executing: ".$this->db->last_query());
		}
	}

	function chk4cron($cronkey) {
		$query = $this->db->query("select count(id) as cid from cron where id=?",$cronkey);
		$row = $query->row();
		return $row->cid ?? 0;
	}

}
