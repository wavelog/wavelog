<?php

	class Cat extends CI_Model {

		/**
		 * Non-standard rig mode strings mapped to their ADIF mode: Yaesu use
		 * CW-U/CW-L, Icom CW-R, Flex CWU/CWL, and various data submodes
		 * (USB-D/LSB-D...). Applied to every radio update via normalize_mode().
		 */
		const MODE_OVERRIDES = [
			'CW-U'   => 'CW',   'CW-L'   => 'CW',   'CW-R'   => 'CW', 'CWU' => 'CW', 'CWL' => 'CW',
			'RTTY-L' => 'RTTY', 'RTTY-U' => 'RTTY', 'RTTY-R' => 'RTTY',
			'USB-D'  => 'USB',  'USB-D1' => 'USB',
			'LSB-D'  => 'LSB',  'LSB-D1' => 'LSB',
		];

		/**
		 * Map a rig mode string to its ADIF equivalent (see MODE_OVERRIDES);
		 * unknown and empty/null values pass through unchanged.
		 */
		private function normalize_mode($mode) {
			if ($mode === null || $mode === '') {
				return $mode;
			}
			return self::MODE_OVERRIDES[strtoupper($mode)] ?? $mode;
		}

		/**
	 * Validate a radio status payload (v1 Api::radio() and API v2 share this).
	 *
	 * Absent / null / '' / 'NULL' values are tolerated (legacy compatibility);
	 * present values must match the cat table column shapes. Numeric fields
	 * that pass are cast to int in place.
	 *
	 * @param array $result Decoded payload, modified by reference.
	 * @return array List of invalid field names (empty = valid).
	 */
	function validate_radio_payload(&$result) {
		$invalid = [];

		if (!isset($result['radio']) || !is_string($result['radio']) || trim($result['radio']) == '' || strlen($result['radio']) > 250) {
			$invalid[] = 'radio';
		}

		$numeric_fields = ['frequency', 'frequency_rx', 'uplink_freq', 'downlink_freq', 'power'];
		foreach ($numeric_fields as $field) {
			if (isset($result[$field]) && $result[$field] !== null && $result[$field] !== '' && $result[$field] !== 'NULL') {
				if (!is_numeric($result[$field]) || (int) $result[$field] != $result[$field]) {
					$invalid[] = $field;
				} else {
					$result[$field] = (int) $result[$field];
				}
			}
		}

		$string_limits = ['mode' => 10, 'mode_rx' => 10, 'uplink_mode' => 10, 'downlink_mode' => 10, 'prop_mode' => 10, 'sat_name' => 255];
		foreach ($string_limits as $field => $limit) {
			if (isset($result[$field]) && $result[$field] !== null && $result[$field] !== '' && $result[$field] !== 'NULL') {
				if (!is_string($result[$field]) || strlen($result[$field]) > $limit) {
					$invalid[] = $field;
				}
			}
		}

		if (isset($result['cat_url']) && $result['cat_url'] !== null && $result['cat_url'] !== '' && $result['cat_url'] !== 'NULL') {
			if (!is_string($result['cat_url']) || !filter_var($result['cat_url'], FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//', $result['cat_url'])) {
				$invalid[] = 'cat_url';
			}
		}

		return $invalid;
	}

	function update($result, $user_id, $operator) {
			$timestamp = gmdate("Y-m-d H:i:s");

			if (isset($result['prop_mode'])) {
				$prop_mode = $result['prop_mode'];
			// For backward compatibility, SatPC32 does not set propagation mode
			} else if (isset($result['sat_name']) && trim($result['sat_name']) != '') {
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

			// Handle callback URL if provided
			if (isset($result['cat_url']) && !empty($result['cat_url'])) {
				$data['cat_url'] = $result['cat_url'];
			}

			if ( (isset($result['power'])) && ($result['power'] != "NULL") && ($result['power'] != '') && (is_numeric($result['power']))) {
				$data['power'] = $result['power'];
			} // else we do not set power as it is not provided or not numeric

			if ( (isset($result['frequency'])) && ($result['frequency'] != "NULL") && ($result['frequency'] != '') && (is_numeric($result['frequency']))) {
				$data['frequency'] = $result['frequency'];
			} else {
				if ( (isset($result['uplink_freq'])) && ($result['uplink_freq'] != "NULL") && ($result['uplink_freq'] != '') && (is_numeric($result['uplink_freq'])) ) {
					$data['frequency'] = $result['uplink_freq'];
				} // else we do not set frequency as it is not provided at all
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

			// Normalise non-standard rig mode strings to their ADIF mode. Single
			// source of truth for every caller (v1 Api::radio() and API v2).
			$data['mode'] = $this->normalize_mode($data['mode']);
			$data['mode_rx'] = $this->normalize_mode($data['mode_rx']);

			if (($this->config->item('mqtt_server') ?? '') != '') {
				$h_user=$this->user_model->get_by_id($user_id);
				$this->load->library('Mh');
				$eventdata=$data;
				$eventdata['user_name']=$h_user->row()->user_name;
				$eventdata['user_id']=$h_user->row()->user_id ?? '';
			}
			$radio_ids = [];
			if ($query->num_rows() > 0) {
				// Update the record
				foreach ($query->result() as $row) {
					$radio_ids[] = $row->id;
					$this->db->where('id', $row->id);
					$this->db->where('user_id', $user_id);
					$this->db->update('cat', $data);
					if (($this->config->item('mqtt_server') ?? '') != '') {
                				$this->mh->wl_event('cat/'.$user_id, json_encode(array_merge($data,$eventdata)));
					}
				}
			} else {
				// Add a new record
				$data['radio'] = $result['radio'];
				$data['user_id'] = $user_id;
				$data['operator'] = $operator;
				$this->db->insert('cat', $data);
				$radio_ids[] = $this->db->insert_id();
				if (($this->config->item('mqtt_server') ?? '') != '') {
                			$this->mh->wl_event('cat/'.$user_id, json_encode(array_merge($data,$eventdata)));
				}
			}
			unset($eventdata);
			unset($h_user);

			$this->load->library('worker');
			if (!empty($radio_ids) && $this->worker->is_enabled()) {
				foreach ($radio_ids as $id) {
					$row = $this->db->get_where('cat', ['id' => $id, 'user_id' => $user_id])->row();
					if (!$row) {
						log_message('error', "There was a radio update for radio id $id, but the row was not found in the cat table for user_id $user_id. This should not happen.");
						continue;
					}
					// Same shape as radio/json, plus a ms timestamp for client-side staleness
					$radio_status = $this->format_status($row);
					$radio_status['timestamp'] = (int) round(microtime(true) * 1000);
					// Per-radio topic — for single-radio views (QSO entry, bandmap, contesting)
					$this->worker->publish('radio.' . $id, [
						'type'         => 'radio_updated',
						'radio_status' => $radio_status,
					]);
					// Per-user topic — carries all of the user's radios for multi-radio
					// views (dashboard, hardware interfaces); radio_id routes the update.
					$this->worker->publish('radios_user.' . $user_id, [
						'type'         => 'radio_updated',
						'radio_id'     => (int) $id,
						'radio_status' => $radio_status,
					]);
				}
			}
		}

		/**
		 * Shape a cat table row into the canonical radio status array.
		 * Single source of truth for both the radio/json endpoint and the worker
		 * push payload, so both always carry the identical structure. Null values
		 * are omitted (not sent as null), matching the historic radio/json output.
		 *
		 * @param object $row  A cat table row (from radio_status()->row()).
		 * @return array
		 */
		function format_status($row) {
			$a_ret = [];

			// Check Mode
			if (isset($row->mode) && ($row->mode != null)) {
				$mode = strtoupper($row->mode);
				if ($mode == "FMN") {
					$mode = "FM";
				}
			} else {
				$mode = null;
			}

			if ($row->prop_mode == "SAT") {
				// Get Satellite Name
				if ($row->sat_name == "AO-07") {
					$sat_name = "AO-7";
				} elseif ($row->sat_name == "LILACSAT") {
					$sat_name = "CAS-3H";
				} else {
					$sat_name = strtoupper($row->sat_name);
				}

				// Get Satellite Mode
				$sat_mode_uplink = $this->get_mode_designator($row->frequency);
				$sat_mode_downlink = $this->get_mode_designator($row->frequency_rx);

				if (empty($sat_mode_uplink)) {
					$sat_mode = "";
				} elseif ($sat_mode_uplink !== $sat_mode_downlink) {
					$sat_mode = $sat_mode_uplink . "/" . $sat_mode_downlink;
				} else {
					$sat_mode = $sat_mode_uplink;
				}
			} else {
				$sat_name = "";
				$sat_mode = "";
			}

			// Calculate how old the data is in minutes
			$datetime1 = new DateTime("now", new DateTimeZone('UTC'));
			$datetime2 = new DateTime($row->timestamp, new DateTimeZone('UTC'));
			$interval = $datetime1->diff($datetime2);
			$minutes = $interval->days * 24 * 60;
			$minutes += $interval->h * 60;
			$minutes += $interval->i;

			$a_ret['frequency'] = $row->frequency;
			$a_ret['frequency_formatted'] = $this->frequency->qrg_conversion($row->frequency);
			if (!empty($row->frequency_rx)) {
				$a_ret['frequency_rx'] = $row->frequency_rx;
				$a_ret['frequency_rx_formatted'] = $this->frequency->qrg_conversion($row->frequency_rx);
			}
			if (isset($mode) && ($mode != null)) {
				$a_ret['mode'] = $mode;
			}
			if (isset($row->mode_rx) && ($row->mode_rx != null) && ($row->mode_rx != 'non')) {
				$a_ret['mode_rx'] = strtoupper($row->mode_rx);
			}
			if (isset($sat_mode) && ($sat_mode != null)) {
				$a_ret['satmode'] = $sat_mode;
			}
			if (isset($sat_name) && ($sat_name != null)) {
				$a_ret['satname'] = $sat_name;
			}
			if (isset($row->power) && ($row->power != null)) {
				$a_ret['power'] = $row->power;
			}
			if (isset($row->prop_mode) && ($row->prop_mode != null)) {
				$a_ret['prop_mode'] = $row->prop_mode;
			}
			if (isset($row->cat_url) && ($row->cat_url != null)) {
				$a_ret['cat_url'] = $row->cat_url;
			}
			if (isset($row->radio) && ($row->radio != null)) {
				$a_ret['radio'] = $row->radio;
			}

			$a_ret['updated_minutes_ago'] = $minutes;

			return $a_ret;
		}

		/**
		 * Map a frequency (Hz) to its satellite band mode designator (H/A/V/U/...).
		 * Used only for satellite QSOs when building the status shape.
		 */
		private function get_mode_designator($frequency) {
			if ($frequency > 21000000 && $frequency < 22000000)
				return "H";
			if ($frequency > 28000000 && $frequency < 30000000)
				return "A";
			if ($frequency > 144000000 && $frequency < 147000000)
				return "V";
			if ($frequency > 432000000 && $frequency < 438000000)
				return "U";
			if ($frequency > 1240000000 && $frequency < 1300000000)
				return "L";
			if ($frequency > 2320000000 && $frequency < 2450000000)
				return "S";
			if ($frequency > 3400000000 && $frequency < 3475000000)
				return "S2";
			if ($frequency > 5650000000 && $frequency < 5850000000)
				return "C";
			if ($frequency > 10000000000 && $frequency < 10500000000)
				return "X";
			if ($frequency > 24000000000 && $frequency < 24250000000)
				return "K";
			if ($frequency > 47000000000 && $frequency < 47200000000)
				return "R";

			return "";
		}

		/**
		 * Get CAT radios statuses for given user ID
		 *
		 * @param int|string $user_id
		 * @param int|null   $operator Optional operator (clubmode) scope.
		 * @return object
		 */
		/**
		 * Radios of an owner, optionally narrowed to a single operator.
		 *
		 * $operator is the session-free counterpart to the clubaccess_check(9)
		 * branch in status(): a clubstation member below officer level only ever
		 * sees the radios it registered itself.
		 */
		function status_for_user_id($user_id, $operator = null) {
			$this->db->where('user_id', $user_id);
			if ($operator !== null) {
				$this->db->where('operator', $operator);
			}
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

		/**
		 * Fetch a single radio by id, scoped to an explicit owner. Session-free
		 * counterpart to radio_status() for the REST API.
		 *
		 * @param int|string $id      cat row id.
		 * @param int        $user_id Owner.
		 * @param int|null   $operator Optional operator (clubmode) scope.
		 * @return object|null The cat row, or null when not found/owned.
		 */
		function radio_for_user($id, $user_id, $operator = null) {
			$this->db->where('id', $this->security->xss_clean($id));
			$this->db->where('user_id', $user_id);
			if ($operator !== null) {
				$this->db->where('operator', $operator);
			}
			return $this->db->get('cat')->row();
		}

		/**
		 * Look up a radio by its name within an operator/owner scope. Used by the
		 * REST API to resolve the row after the name-keyed upsert in update().
		 *
		 * @param string $radio    Radio name.
		 * @param int    $operator Operator id (clubmode) or owner.
		 * @param int    $user_id  Owner.
		 * @return object|null
		 */
		function radio_by_name($radio, $operator, $user_id) {
			$this->db->where('radio', $radio);
			$this->db->where('operator', $operator);
			$this->db->where('user_id', $user_id);
			return $this->db->get('cat')->row();
		}

		/**
		 * Delete a radio by id, scoped to an explicit owner. Session-free
		 * counterpart to delete() for the REST API.
		 *
		 * @param int|string $id      cat row id.
		 * @param int        $user_id Owner.
		 * @param int|null   $operator Optional operator (clubmode) scope.
		 * @return int Number of affected rows.
		 */
		function delete_for_user($id, $user_id, $operator = null) {
			$this->db->where('id', $this->security->xss_clean($id));
			$this->db->where('user_id', $user_id);
			if ($operator !== null) {
				$this->db->where('operator', $operator);
			}
			$this->db->delete('cat');
			return $this->db->affected_rows();
		}
	}
?>
