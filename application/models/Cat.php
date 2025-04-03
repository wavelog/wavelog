<?php

	class Cat extends CI_Model {

		function update($result, $user_id, $operator) {

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
			$this->db->where('operator', $operator);
			$this->db->where('user_id', $user_id);
			$query = $this->db->get('cat');

			// Let's keep uplink_freq, downlink_freq, uplink_mode and downlink_mode for backward compatibility
			$data = array(
				'prop_mode' => $prop_mode,
				'sat_name' => $result['sat_name'] ?? NULL,
				'timestamp' => $timestamp,
			);

			if ( (isset($result['power'])) && ($result['power'] != "NULL") && ($result['power'] != '') && (is_numeric($result['power']))) {
				$data['power'] = $result['power'];
			} else {
				unset($data['power']);	// Do not update power since it isn't provided or not numeric
			}

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
				$data['operator'] = $operator;

				$this->db->insert('cat', $data);
			}
		}

		/**
		 * Get CAT radios statuses for given user ID 
		 *
		 * @param int|string $user_id
		 * @return object
		 */
		function status_for_user_id($user_id) {
			$this->db->where('user_id', $user_id);
			$query = $this->db->get('cat');

			return $query;
		}

		function status() {
			//$this->db->where('radio', $result['radio']);
			$this->db->where('user_id', $this->session->userdata('user_id'));
			if ($this->session->userdata('clubstation') == 1 && !clubaccess_check(9)) {
				$this->db->where('operator', $this->session->userdata('source_uid'));
			}
			$query = $this->db->get('cat');

			return $query;
		}

		function recent_status() {
			$this->db->where('user_id', $this->session->userdata('user_id'));
			if ($this->session->userdata('clubstation') == 1 && !clubaccess_check(9)) {
				$this->db->where('operator', $this->session->userdata('source_uid'));
			}
			$this->db->where("timestamp > date_sub(UTC_TIMESTAMP(), interval 15 minute)", NULL, FALSE);

			$query = $this->db->get('cat');
			return $query;
		}

		/* Return list of radios */
		function radios($only_operator = false) {
			$this->db->select('id, radio');
			$this->db->where('user_id', $this->session->userdata('user_id'));
			if ($only_operator && ($this->session->userdata('clubstation') == 1 && !clubaccess_check(9))) {
				$this->db->where('operator', $this->session->userdata('source_uid'));
			}
			$query = $this->db->get('cat');

			return $query;
		}

		function radio_status($id) {
			$binding = [];
			$sql = 'SELECT * FROM `cat` WHERE id = ? AND user_id = ?';
			$binding[] = $id;
			$binding[] = $this->session->userdata('user_id');
			if ($this->session->userdata('clubstation') == 1 && !clubaccess_check(9)) {
				$sql .= ' AND operator = ?';
				$binding[] = $this->session->userdata('source_uid');
			}
			return $this->db->query($sql, $binding);
		}

		function last_updated() {
			$binding = [];
			$sql = 'SELECT * FROM cat WHERE user_id = ?';
			$binding[] = $this->session->userdata('user_id');
			if ($this->session->userdata('clubstation') == 1 && !clubaccess_check(9)) {
				$sql .= ' AND operator = ?';
				$binding[] = $this->session->userdata('source_uid');
			}
			$sql .= ' ORDER BY timestamp DESC LIMIT 1';
			return $this->db->query($sql, $binding);
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
