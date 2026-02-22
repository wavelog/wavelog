<?php

/* api_model.php
 *
 * Provides API functions to the web frontend
 *
 */

class API_Model extends CI_Model {

	// GET API Keys
	function keys() {
		$binding = [];
		$user_id = $this->session->userdata('user_id');
		$clubstation = $this->session->userdata('clubstation');
		$impersonate = $this->session->userdata('impersonate');

		if ($clubstation == 1 && $impersonate == 1) {
			$sql = "SELECT api.*, users.user_callsign
					FROM api
					JOIN users ON api.created_by = users.user_id
					WHERE api.user_id = ?";
			$binding[] = $user_id;

			if (!clubaccess_check(9)) {
				$sql .= " AND api.created_by = ?";
				$binding[] = $this->session->userdata('source_uid');
			}
		} else {
			$sql = "SELECT * FROM api WHERE user_id = ?";
			$binding[] = $user_id;
		}

		return $this->db->query($sql, $binding);
	}

	function key_description($key = '') {
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('key', $key);
		$query = $this->db->get('api');

		return $query->result_array()[0];
	}

	function key_userid($key = '') {
		$this->db->where('key', $key);
		$query = $this->db->get('api');

		return $query->result_array()[0]['user_id'];
	}

	function key_created_by($key = '') {
		$this->db->where('key', $key);
		$query = $this->db->get('api');

		return $query->result_array()[0]['created_by'];
	}

	function update_key_description($key, $description) {

		$data = array(
			'description' => xss_clean($description),
		);

		$this->db->where('key', xss_clean($key));
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->update('api', xss_clean($data));
	}


	function delete_key($key = '') {
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('key', xss_clean($key));
		$this->db->delete('api');
	}

	// Generate API Key
	function generate_key($rights, $creator = NULL) {

		// Generate Unique Key
		$data['key'] = "wl" . substr(md5(uniqid(rand(), true)), 19);
		$data['rights'] = $rights;

		// Set API key to active
		$data['status'] = "active";

		$data['user_id'] = $this->session->userdata('user_id');
		$data['created_by'] = $creator != NULL ? $creator : $this->session->userdata('user_id');


		if ($this->db->insert('api', $data)) {
			return true;
		} else {
			return false;
		}
	}

	function access($key = '') {

		// No key = no access, mate
		if ((!$key) || ($key == '')) {
			return $status = "No Key Found";
		}

		// Check that the key is valid
		$this->db->where('key', $key);
		$query = $this->db->get('api');

		if ($query->num_rows() > 0) {
			foreach ($query->result() as $row) {
				if ($row->status == "active") {
					return $status = $row->rights;
				} else {
					return $status = "Key Disabled";
				}
			}
		} else {
			return $status = "No Key Found";
		}
	}

	function authorize($key = '') {
		$r = $this->access($key);
		if ($r == "rw") {
			return 2;
		} else if ($r == "r") {
			return 1;
		} else {
			return 0;
		}
	}

	function update_last_used($key) {
		$this->db->set('last_used', 'NOW()', FALSE);
		$this->db->where('key', xss_clean($key));
		$this->db->update('api');

		// Also update last_seen in user table
		$user_id = $this->key_userid($key);

		$this->load->model('user_model');
		$this->user_model->set_last_seen($user_id);
	}

	function get_qsos_grouped_by_mode($station_id) {
        $binding[] = $station_id;

        $sql = "
            SELECT count(1) count, col_mode, col_submode
			FROM " . $this->config->item('table_name') . " qsos
			WHERE qsos.station_id =  ?
			group by col_mode, col_submode
		";

		return $this->db->query($sql, $binding);
	}

	function get_qsos_total($station_id) {
		$binding[] = $station_id;

        $sql = "
            SELECT count(1) count
			FROM " . $this->config->item('table_name') . " qsos
			WHERE qsos.station_id =  ?
		";

		return $this->db->query($sql, $binding);
	}

	function get_qsos_this_year($station_id) {
		$binding[] = $station_id;

        $sql = "
            SELECT count(1) count
			FROM " . $this->config->item('table_name') . " qsos
			WHERE qsos.station_id =  ?
			and year(col_time_on) = year(now())
		";

		return $this->db->query($sql, $binding);

	}

	function get_grids_worked_in_logbook($StationLocationsArray = null, $band = null, $cnfm = null) {
		$grid_array = [];
		if ($StationLocationsArray == null) {
			$this->load->model('logbooks_model');
			$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		} else {
			$logbooks_locations_array = $StationLocationsArray;
		}

		$bindings = [];
		$subsql = '';
		$band = ($band == 'All') ? null : $band;
		if ($band != null && $band != 'SAT') {
			$subsql .= ' AND COL_BAND = ? AND COL_PROP_MODE != "SAT"';
			$bindings[] = $band;
		} else if ($band == 'SAT') {
			$subsql .= ' AND COL_SAT_NAME != ""';
		}
		switch ($cnfm) {
			case 'qsl':
				$subsql .= ' AND COL_QSL_RCVD = "Y"';
				break;
			case 'lotw':
				$subsql .= ' AND COL_LOTW_QSL_RCVD = "Y"';
				break;
			case 'eqsl':
				$subsql .= ' AND COL_EQSL_QSL_RCVD = "Y"';
				break;
		}

		$ids = array_map('intval', $logbooks_locations_array);
		$sql = 'SELECT DISTINCT UPPER(SUBSTR(COL_GRIDSQUARE, 1, 4)) AS gridsquare FROM ' . $this->config->item('table_name') . ' thcv ';
		$sql .= ' WHERE COL_GRIDSQUARE <> "" AND CHAR_LENGTH(COL_GRIDSQUARE) >= 4';
		$sql .= ' AND station_id IN (' . implode(',', $ids) . ')';
		$sql .= $subsql;
		$sql .= ' ORDER BY gridsquare ASC;';
		$query = $this->db->query($sql,$bindings);
		foreach($query->result() as $line) {
			$grid_array[] = $line->gridsquare;
		}
		// Get and add VUCC grids
		$sql = 'SELECT DISTINCT UPPER(COL_VUCC_GRIDS) AS vuccgrids FROM ' . $this->config->item('table_name') . ' thcv ';
		$sql .= ' WHERE COL_VUCC_GRIDS <> ""';
		$sql .= ' AND station_id IN (' . implode(',', $ids) . ')';
		$sql .= $subsql;
		$sql .= ' ORDER BY vuccgrids ASC;';
		$query = $this->db->query($sql,$bindings);
		foreach($query->result() as $line) {
			$vucc_grids = explode(',', $line->vuccgrids);
			foreach ($vucc_grids as $vucc_grid) {
				if (strlen($vucc_grid) >= 4) {
					$grid = substr($vucc_grid, 0, 4);
					if (! in_array($grid, $grid_array)) {
						$grid_array[] = $grid;
					}
				}
			}
		}
		sort ($grid_array);
		return $grid_array;
	}
}
