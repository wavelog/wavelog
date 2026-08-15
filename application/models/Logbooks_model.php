<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logbooks_model extends CI_Model {

	function show_all() {
		$this->db->where('user_id', $this->session->userdata('user_id'));
		return $this->db->get('station_logbooks');
	}

	function add($logbook_name = '') {
		// Create data array with field values
		if ($logbook_name ?? '' != '') {
			$data = array(
				'user_id' => $this->session->userdata('user_id'),
				'logbook_name' =>  $logbook_name,
			);

			// Insert Records
			$this->db->insert('station_logbooks', $data);
			$logbook_id = $this->db->insert_id();

			// check if user has no active logbook yet
			if ($this->session->userdata('active_station_logbook') === null) {
				// set logbook active
				$this->set_logbook_active($logbook_id);

				// update user session data
				$this->user_model->update_session($this->session->userdata('user_id'));
			}
			return $logbook_id;
		} else {
			return -1;
		}
	}

	function delete($clean_id) {
		// Clean ID

		// do not delete active logbook
		if ($this->session->userdata('active_station_logbook') === $clean_id) {
			return;
		}

		// Also clean up static map images first
		if (!$this->load->is_loaded('staticmap_model')) {
			$this->load->model('staticmap_model');
		}
		$this->staticmap_model->remove_static_map_image(null, $clean_id);

		// Delete logbook
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $clean_id);
		$this->db->delete('station_logbooks');
	}

	function edit() {
		$data = array(
			'logbook_name' => xss_clean($this->input->post('station_logbook_name', true)),
		);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', xss_clean($this->input->post('logbook_id', true)));
		$this->db->update('station_logbooks', $data);
	}

	function set_logbook_active($id, $user_id = null) {
		// Clean input
		$cleanId = xss_clean($id);

		// check if user_id is set
		if ($user_id === null) {
			$user_id = $this->session->userdata('user_id');
		} else {
			$user_id = xss_clean($user_id);
		}

		// be sure that logbook belongs to user
		if (!$this->check_logbook_is_accessible($cleanId)) {
			return;
		}

		$data = array(
			'active_station_logbook' => $cleanId,
		);

		$this->db->where('user_id', $user_id);
		$this->db->update('users', $data);
	}

	function logbook($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $clean_id);
		return $this->db->get('station_logbooks');
	}

	function find_name($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $clean_id);
		$query = $this->db->get('station_logbooks');
		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				return $row->logbook_name;
			}
		} else {
			return "n/a";
		}
	}

	// Creates relationship between a logbook and a station location
	function create_logbook_location_link($logbook_id, $location_id) {
		// Clean ID
		$clean_logbook_id = $this->security->xss_clean($logbook_id);
		$clean_location_id = $this->security->xss_clean($location_id);

		// be sure that logbook belongs to user
		if (!$this->check_logbook_is_accessible($clean_logbook_id)) {
			return;
		}

		// be sure that station belongs to user
		$this->load->model('Stations');
		if (!$this->Stations->check_station_is_accessible($clean_location_id)) {
			return;
		}

		// Create data array with field values
		$data = array(
			'station_logbook_id' => $clean_logbook_id,
			'station_location_id' =>  $clean_location_id,
		);

		// Insert Record
		$this->db->insert('station_logbooks_relationship', $data);
	}



	function public_slug_exists($slug) {
		$this->db->where('public_slug', $this->security->xss_clean($slug));
		$query = $this->db->get('station_logbooks');

		if ($query->num_rows() > 0){
			return true;
		} else {
			return false;
		}
	}

	function public_slug_exists_logbook_id($slug) {
		$this->db->where('public_slug', $this->security->xss_clean($slug));
		$query = $this->db->get('station_logbooks');
		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				return $row->logbook_id;
			}
		} else {
			return false;
		}
	}

	function exists_logbook_id($logbook_id) {
		$this->db->where('logbook_id', $this->security->xss_clean($logbook_id));
		$query = $this->db->get('station_logbooks');

		if ($query->num_rows() > 0){
			return true;
		} else {
			return false;
		}
	}

	function logbook_id_belongs_to_user($logbook_id, $user_id) {
		$this->db->where('logbook_id', $this->security->xss_clean($logbook_id));
		$this->db->where('user_id', $user_id);
		$query = $this->db->get('station_logbooks');
		return $query->num_rows() > 0;
	}

	function is_public_slug_available($slug) {
		// Clean public_slug
		$clean_slug = $this->security->xss_clean($slug);
		$this->db->where('public_slug', $clean_slug);
		$query = $this->db->get('station_logbooks');

		if ($query->num_rows() > 0){
			return false;
		} else {
			return true;
		}
	}

	function remove_public_slug($logbook_id) {

		$this->db->set('public_slug', null);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', xss_clean($logbook_id));
		$this->db->update('station_logbooks');
	}

	function list_logbook_relationships($logbook_id) {

		$relationships_array = array();

		$this->db->where('station_logbook_id', $logbook_id);
		$query = $this->db->get('station_logbooks_relationship');

		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				array_push($relationships_array, $row->station_location_id);
			}

			return $relationships_array;
		} else {
			return array(-1);	// Put some default-Value here, if no relation found
		}
	}

	function list_logbooks_linked($logbook_id) {

		$relationships_array = array();

		$this->db->where('station_logbook_id', $logbook_id);
		$query = $this->db->get('station_logbooks_relationship');


		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				array_push($relationships_array, $row->station_location_id);
			}

			$this->db->select('station_profile.*, dxcc_entities.name as station_country, dxcc_entities.end as end');
			$this->db->where_in('station_id', $relationships_array);
			$this->db->join('dxcc_entities','station_profile.station_dxcc = dxcc_entities.adif','left outer');
			$this->db->order_by('station_profile.station_callsign asc, station_profile.station_profile_name asc');
			$query = $this->db->get('station_profile');

			return $query;
		} else {
			return false;
		}
	}

	public function check_logbook_is_accessible($id) {
		// check if logbook belongs to user
		$this->db->select('logbook_id');
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $id);
		$query = $this->db->get('station_logbooks');
		if ($query->num_rows() == 1) {
			return true;
		}
		return false;
	}

	public function find_active_station_logbook_from_userid($userid) {
		$this->db->select('active_station_logbook');
		$this->db->where('user_id', $userid);
		$query = $this->db->get('users');
		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				return $row->active_station_logbook;
			}
		} else {
			return 0;
		}
	}

	function public_search_enabled($logbook_id) {
		$this->db->select('public_search');
		$this->db->where('logbook_id', $logbook_id);

		$query = $this->db->get('station_logbooks');

		return $query->result_array()[0]['public_search'];
	}

	// --- API v2 helpers (user_id-parameterised, no session dependency) ------

	/*
	 * All logbooks for a given user, ordered by id.
	 */
	function list_for_user($user_id) {
		return $this->db->where('user_id', (int) $user_id)
			->order_by('logbook_id', 'ASC')
			->get('station_logbooks')
			->result();
	}

	/*
	 * Single logbook by id, ownership-checked. Returns the row or null.
	 */
	function get_by_id_for_user($logbook_id, $user_id) {
		$q = $this->db->where('user_id', (int) $user_id)
			->where('logbook_id', (int) $logbook_id)
			->get('station_logbooks');
		return $q->num_rows() > 0 ? $q->row() : null;
	}

	/*
	 * Create a logbook for $user_id. Returns the new logbook_id, or -1 if name
	 * is empty or already exists for this user.
	 */
	function create_for_user($name, $user_id) {
		$name = trim($name);
		if ($name === '') {
			return -1;
		}
		$dupe = $this->db->where('user_id', (int) $user_id)
			->where('logbook_name', $name)
			->get('station_logbooks');
		if ($dupe->num_rows() > 0) {
			return -1;
		}
		$this->db->insert('station_logbooks', [
			'user_id'      => (int) $user_id,
			'logbook_name' => xss_clean($name),
		]);
		return $this->db->insert_id();
	}

	/*
	 * Rename a logbook. Returns false when the logbook does not belong to the user.
	 */
	function rename_for_user($logbook_id, $name, $user_id) {
		if ($this->get_by_id_for_user($logbook_id, $user_id) === null) {
			return false;
		}
		$this->db->where('user_id', (int) $user_id)
			->where('logbook_id', (int) $logbook_id)
			->update('station_logbooks', ['logbook_name' => xss_clean(trim($name))]);
		return true;
	}

	/*
	 * Delete a logbook (and clean up static map images). Returns false when the
	 * logbook does not belong to the user.
	 */
	function delete_for_user($logbook_id, $user_id) {
		if ($this->get_by_id_for_user($logbook_id, $user_id) === null) {
			return false;
		}
		if (!$this->load->is_loaded('staticmap_model')) {
			$this->load->model('staticmap_model');
		}
		$this->staticmap_model->remove_static_map_image(null, (int) $logbook_id);
		$this->db->where('user_id', (int) $user_id)
			->where('logbook_id', (int) $logbook_id)
			->delete('station_logbooks');
		return true;
	}

	/*
	 * Returns the active logbook id for a user, or null when none is set.
	 */
	function get_active_id_for_user($user_id) {
		$q = $this->db->select('active_station_logbook')
			->where('user_id', (int) $user_id)
			->get('users');
		if ($q->num_rows() === 0) {
			return null;
		}
		$v = $q->row()->active_station_logbook;
		return ($v !== null && $v > 0) ? (int) $v : null;
	}

	/*
	 * Makes $logbook_id the active logbook for $user_id. Ownership is not
	 * re-checked here — callers must verify before calling.
	 */
	function set_active_for_user($logbook_id, $user_id) {
		$this->db->where('user_id', (int) $user_id)
			->update('users', ['active_station_logbook' => (int) $logbook_id]);
	}

	/*
	 * Returns the station_location_ids linked to a logbook as an int array.
	 */
	function get_linked_station_ids($logbook_id) {
		$rows = $this->db->select('station_location_id')
			->where('station_logbook_id', (int) $logbook_id)
			->get('station_logbooks_relationship')
			->result();
		return array_map(fn($r) => (int) $r->station_location_id, $rows);
	}

	/*
	 * Links a station location to a logbook. Silently skips when the link
	 * already exists (idempotent).
	 */
	function link_station($logbook_id, $station_id) {
		if (!$this->station_link_exists($logbook_id, $station_id)) {
			$this->db->insert('station_logbooks_relationship', [
				'station_logbook_id'  => (int) $logbook_id,
				'station_location_id' => (int) $station_id,
			]);
		}
	}

	/*
	 * Removes the link between a logbook and a station location.
	 */
	function unlink_station($logbook_id, $station_id) {
		$this->db->where('station_logbook_id', (int) $logbook_id)
			->where('station_location_id', (int) $station_id)
			->delete('station_logbooks_relationship');
	}

	/*
	 * Returns true when the logbook↔station link already exists.
	 */
	function station_link_exists($logbook_id, $station_id) {
		$q = $this->db->where('station_logbook_id', (int) $logbook_id)
			->where('station_location_id', (int) $station_id)
			->get('station_logbooks_relationship');
		return $q->num_rows() > 0;
	}
}
?>
