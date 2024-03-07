<?php

class Satellite_model extends CI_Model {

	function get_all_satellites() {
		$sql = "select satellite.id, satellite.name as satname, group_concat(distinct satellitemode.name) as modename, satellite.exportname, satellite.orbit
		from satellite
		join satellitemode on satellite.id = satellitemode.satelliteid
		group by satellite.name, satellite.exportname, satellite.orbit, satellite.id";

		return $this->db->query($sql)->result();
	}

	function delete($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Mode
		$this->db->delete('satellite', array('id' => $clean_id));
	}

	function saveupdatedsatellite($id, $satellite) {
		$data = array(
			'name' 		=> $satellite['name'],
        );

        $this->db->where('satellite.id', $id);

        $this->db->update('satellite', $data);

        return true;
	}

	function add() {
		$data = array(
			'name' 		=> xss_clean($this->input->post('name', true)),
		);

		$this->db->where('name', xss_clean($this->input->post('name', true)));
		$result = $this->db->get('satellite');

		if ($result->num_rows() == 0) {
		   $this->db->insert('satellite', $data);
		}

	}

	function getsatellite($id) {
		$this->db->where('id', $id);
		return $this->db->get('bands');
	}

}

?>
