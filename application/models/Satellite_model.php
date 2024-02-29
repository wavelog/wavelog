<?php

class Satellite_model extends CI_Model {

	function get_all_satellites() {
		$sql = "select satellite.name as satname, satellitemode.name as modename, satellite.exportname, satellite.orbit from satellite join satellitemode on satellite.id = satellitemode.satelliteid";

		return $this->db->query($sql)->result();
	}

}

?>
