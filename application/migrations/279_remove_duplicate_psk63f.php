<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_remove_duplicate_psk63f extends CI_Migration
{
	public function up()
	{
		$db_result = $this->dbtry("SELECT submode, COUNT(*) AS total_rows, SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) AS active_rows, SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) AS inactive_rows FROM adif_modes WHERE mode='PSK' AND submode = 'PSK63F' AND qrgmode='DATA' GROUP BY submode HAVING COUNT(*) = 2 AND SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) = 1 AND SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) = 1;");
		if($db_result->num_rows() > 0) {
			if(($db_result->active_rows == 1) && ($db_result->inactive_rows == 1)) {
				// found one active and one inactive entry of PSK63F
				// DB table rules allow for exactly one 'mode','submode','qrgmode','active' combination
				// remove the inactive entry, allowing for active entry to be inactivated if desired
				$this->dbtry("DELETE FROM adif_modes WHERE mode = 'PSK' AND submode = 'PSK63F' AND qrgmode = 'DATA' AND active = 0 LIMIT 1;");
			}
		}
	}

	public function down()
	{
		// Not Possible
	}

	function dbtry($what) {
		try {
			return $this->db->query($what);
		} catch (Exception $e) {
			log_message("error", "Error removing duplicate PSK63F entry: ".$e." // Executing: ".$this->db->last_query());
		}
	}
}
