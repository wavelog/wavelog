<?php

class Satellite_model extends CI_Model {

	function get_all_satellites() {
		$sql = "select satellite.id, satellite.name as satname, group_concat(distinct satellitemode.name separator ', ') as modename, satellite.displayname, satellite.orbit, satellite.lotw as lotw, tle.updated
		from satellite
		left outer join satellitemode on satellite.id = satellitemode.satelliteid
		left outer join tle on satellite.id = tle.satelliteid
		group by satellite.name, satellite.displayname, satellite.orbit, satellite.id, tle.updated";

		return $this->db->query($sql)->result();
	}

	function get_satellite_information($satname = null) {
		$bindings = [];
		$sql = "select satellite.id, satellite.name as satname, satellitemode.name as modename, satellite.displayname, satellite.orbit, satellite.lotw as lotw, tle.updated, satellitemode.uplink_mode, satellitemode.downlink_mode, FORMAT((satellitemode.uplink_freq / 1000000), 3) AS uplink_freq, FORMAT((satellitemode.downlink_freq / 1000000), 3) AS downlink_freq
		from satellite
		left outer join satellitemode on satellite.id = satellitemode.satelliteid
		left outer join tle on satellite.id = tle.satelliteid ";

		if ($satname != null) {
			$sql .= " where satellite.name = ? ";
			$bindings[] = $satname;
		}

		return $this->db->query($sql, $bindings)->result();
	}

	function get_all_satellites_with_tle() {
		$sql = "select satellite.id, satellite.name as satname, tle.tle
		from satellite
		join tle on satellite.id = tle.satelliteid
		order by satellite.name
		";

		return $this->db->query($sql)->result();
	}

	function get_last_worked_sat() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$location_list = "'" . implode("','", $logbooks_locations_array) . "'";
		$sql = "select COL_SAT_NAME as sat from " . $this->config->item('table_name') .
			" where station_id in (" . $location_list . ")" .
			" AND COL_PROP_MODE = 'SAT' AND COL_SAT_NAME is not null AND COL_SAT_NAME != '' ".
			"order by COL_TIME_ON DESC LIMIT 1";

		$query = $this->db->query($sql);
		$row = $query->row();
		if (isset($row)) {
			return ($row->sat);
		}
		return null;
	}

	function delete($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Satellite
		$this->db->delete('satellite', array('id' => $clean_id));
	}

	function deleteSatMode($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// Delete Satellite
		$this->db->delete('satellitemode', array('id' => $clean_id));
	}

	function saveupdatedsatellite($id, $satellite) {
		$this->db->where('satellite.id', $id);
		$this->db->update('satellite', $satellite);
		return true;
	}

	function saveSatelliteMode($id, $satmode) {
		$this->db->where('satellitemode.id', $id);
		$this->db->update('satellitemode', $satmode);
		return true;
	}

	function add() {
		$data = array(
			'name' 			=> xss_clean($this->input->post('name', true)),
			'displayname' 	=> xss_clean($this->input->post('displayname', true)),
			'orbit' 		=> xss_clean($this->input->post('orbit', true)),
		);
		if (xss_clean($this->input->post('lotw', true)) == 'Y') {
			$data['lotw'] = 'Y';
		} else {
			$data['lotw'] = 'N';
		}

		$this->db->where('name', xss_clean($this->input->post('name', true)));
		$result = $this->db->get('satellite');

		if ($result->num_rows() == 0) {
			$this->db->insert('satellite', $data);
			$insert_id = $this->db->insert_id();

			$data = array(
				'name' 				=> xss_clean($this->input->post('modename', true)),
				'satelliteid' 		=> $insert_id,
				'uplink_mode'		=> xss_clean($this->input->post('uplinkmode', true)),
				'uplink_freq'		=> xss_clean($this->input->post('uplinkfrequency', true)),
				'downlink_mode'		=> xss_clean($this->input->post('downlinkmode', true)),
				'downlink_freq'		=> xss_clean($this->input->post('downlinkfrequency', true)),
			);

			$this->db->insert('satellitemode', $data);
		}

	}

	function getsatellite($id) {
		$this->db->where('id', $id);
		return $this->db->get('satellite');
	}

	function getsatmodes($id) {
		$this->db->where('satelliteid', $id);
		return $this->db->get('satellitemode');
	}

	function insertSatelliteMode() {
		$data = array(
			'name' 				=> xss_clean($this->input->post('name', true)),
			'satelliteid' 		=> xss_clean($this->input->post('id', true)),
			'uplink_mode'		=> xss_clean($this->input->post('uplink_mode', true)),
			'uplink_freq'		=> xss_clean($this->input->post('uplink_freq', true)),
			'downlink_mode'		=> xss_clean($this->input->post('downlink_mode', true)),
			'downlink_freq'		=> xss_clean($this->input->post('downlink_freq', true)),
		);
		$this->db->insert('satellitemode', $data);
		$insert_id = $this->db->insert_id();
		return $insert_id;
	}

	function satellite_data() {
		$this->db->select('COALESCE(NULLIF(satellite.name, \'\'), satellite.displayname) AS satellite, satellitemode.name AS satmode, satellitemode.uplink_mode AS Uplink_Mode, satellitemode.uplink_freq AS Uplink_Freq, satellitemode.downlink_mode AS Downlink_Mode, satellitemode.downlink_freq AS Downlink_Freq');
		$this->db->join('satellitemode', 'satellite.id = satellitemode.satelliteid', 'LEFT OUTER');
		$this->db->order_by('satellite', 'ASC');
		$query = $this->db->get('satellite');
		return $query->result();
	}

    function array_group_by($flds, $arr) {
		$groups = array();
		foreach ($arr as $rec) {
			$keys = array_map(function($f) use($rec) { return $rec[$f]; }, $flds);
			$k = implode('@', $keys);
			if (isset($groups[$k])) {
				$groups[$k][] = $rec;
			} else {
				$groups[$k] = array($rec);
			}
		}
		return $groups;
	}

	function get_tle($sat) {
		$this->db->select('satellite.name AS satellite, tle.tle');
		$this->db->join('tle', 'satellite.id = tle.satelliteid');
		$this->db->where('name', $sat);
		$query = $this->db->get('satellite');
		return $query->row();
	}

}

?>
