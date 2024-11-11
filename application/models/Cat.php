<?php

	class Cat extends CI_Model {

		function update($result, $user_id) {

			$timestamp = gmdate("Y-m-d H:i:s");

			if (isset($result['prop_mode'])) {
				$prop_mode = $result['prop_mode'];
			// For backward compatibility, SatPC32 does not set propergation mode
			} else if (isset($result['sat_name'])) {
				$prop_mode = "SAT";
			} else {
				$prop_mode = NULL;
			}

			$this->db->where('radio', $result['radio']);
			$this->db->where('user_id', $user_id);
			$query = $this->db->get('cat');

			// Let's keep uplink_freq, downlink_freq, uplink_mode and downlink_mode for backward compatibility
			$data = array(
				'prop_mode' => $prop_mode,
				'power' => $result['power'] ?? NULL,
				'sat_name' => $result['sat_name'] ?? NULL,
				'timestamp' => $timestamp,
			);
			if ( (isset($result['frequency'])) && ($result['frequency'] != "NULL") && ($result['frequency'] != '') && (is_numeric($result['frequency']))) {
				$data['frequency'] = $result['frequency'];
			} else {
				if ( (isset($result['uplink_freq'])) && ($result['uplink_freq'] != "NULL") && ($result['uplink_freq'] != '') && (is_numeric($result['uplink_freq'])) ) {
					$data['frequency'] = $result['uplink_freq'];
				} else {
					unset($data['frequency']);	// Do not update Frequency since it wasn't provided
				}
			}
			if (isset($result['mode']) && $result['mode'] != "NULL") {
				$data['mode'] = $result['mode'];
			} else {
				if (isset($result['uplink_mode']) && $result['uplink_mode'] != "NULL") {
					$data['mode'] = $result['uplink_mode'];
				} else {
					$data['mode'] = NULL;
				}
			}
			if ( (isset($result['frequency_rx'])) && (is_numeric($result['frequency_rx'])) ) {
				$data['frequency_rx'] = $result['frequency_rx'];
			} else if (isset($result['downlink_freq']) && ($result['downlink_freq'] != "NULL") && (is_numeric($result['downlink_freq'])))  {
				$data['frequency_rx'] = $result['downlink_freq'];
			} else {
				$data['frequency_rx'] = NULL;
			}
			if (isset($result['mode_rx'])) {
				$data['mode_rx'] = $result['mode_rx'];
			} else if (isset($result['downlink_mode']) && $result['downlink_mode'] != "NULL") {
				$data['mode_rx'] = $result['downlink_mode'];
			} else {
				$data['mode_rx'] = NULL;
			}

			if ($query->num_rows() > 0)
			{
				// Update the record
				foreach ($query->result() as $row)
				{
					$radio_id = $row->id;

					$this->db->where('id', $radio_id);
					$this->db->where('user_id', $user_id);
					$this->db->update('cat', $data);
				}
			} else {
				// Add a new record
				$data['radio'] = $result['radio'];
				$data['user_id'] = $user_id;

				$this->db->insert('cat', $data);
			}
		}

		function status() {
			//$this->db->where('radio', $result['radio']);
			$this->db->where('user_id', $this->session->userdata('user_id'));
			$query = $this->db->get('cat');

			return $query;
		}

		function recent_status() {
			$this->db->where('user_id', $this->session->userdata('user_id'));
			$this->db->where("timestamp > date_sub(UTC_TIMESTAMP(), interval 15 minute)", NULL, FALSE);

			$query = $this->db->get('cat');
			return $query;
		}

		/* Return list of radios */
		function radios() {
			$this->db->select('id, radio');
			$this->db->where('user_id', $this->session->userdata('user_id'));
			$query = $this->db->get('cat');

			return $query;
		}

		function radio_status($id) {
			$sql = 'SELECT * FROM `cat` WHERE id = ? AND user_id = ?';
			return $this->db->query($sql, array($id, $this->session->userdata('user_id')));
		}

		function last_updated() {
			$sql = 'SELECT * FROM cat WHERE user_id = ? ORDER BY timestamp DESC LIMIT 1';
			return $this->db->query($sql, $this->session->userdata('user_id'));
		}

		function delete($id) {
			$this->db->where('id', $id);
			$this->db->where('user_id', $this->session->userdata('user_id'));
			$this->db->delete('cat');

			return true;
		}

		function updateCatUrl($id,$caturl) {
			$this->db->where('id', $id);
			$this->db->where('user_id', $this->session->userdata('user_id'));
			$this->db->update('cat',array('cat_url' => $caturl));

			return true;
		}
	}
?>
