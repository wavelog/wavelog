<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Logbooks_model extends CI_Model {

	function show_all($user_id = null) {
		$this->db->where('user_id', $user_id ?? $this->session->userdata('user_id'));	// Fallback to session-uid, if userid is omitted
		return $this->db->get('station_logbooks');
	}

	function add($logbook_name = '', $user_id = null) {
		$user_id = $user_id ?? $this->session->userdata('user_id');	// Fallback to session-uid, if userid is omitted

		// Create data array with field values
		if (($logbook_name ?? '') !== '') {
			$data = array(
				'user_id' => $user_id,
				'logbook_name' =>  $logbook_name,
			);

			// Insert Records
			$this->db->insert('station_logbooks', $data);
			$logbook_id = $this->db->insert_id();

			// check if user has no active logbook yet
			if (empty($this->find_active_station_logbook_from_userid($user_id))) {
				// set logbook active
				$this->set_logbook_active($logbook_id, $user_id);

				// update user session data (only a web request has one)
				if ($this->session->userdata('user_id') == $user_id) {
					$this->user_model->update_session($user_id);
				}
			}
			return $logbook_id;
		} else {
			return -1;
		}
	}

	function rename($logbook_id, $logbook_name, $user_id = null) {
		$user_id = $user_id ?? $this->session->userdata('user_id');	// Fallback to session-uid, if userid is omitted

		$this->db->where('user_id', $user_id);
		$this->db->where('logbook_id', $logbook_id);
		$this->db->update('station_logbooks', array('logbook_name' => $logbook_name));
	}

	function delete($clean_id, $user_id = null) {
		// Clean ID
		$user_id = $user_id ?? $this->session->userdata('user_id');	// Fallback to session-uid, if userid is omitted

		// be sure that logbook belongs to user, the deletes below span two tables
		if (!$this->check_logbook_is_accessible($clean_id, $user_id)) {
			return;
		}

		// do not delete active logbook
		if ($this->find_active_station_logbook_from_userid($user_id) == $clean_id) {
			return;
		}

		// Also clean up static map images first
		if (!$this->load->is_loaded('staticmap_model')) {
			$this->load->model('staticmap_model');
		}
		$this->staticmap_model->remove_static_map_image(null, $clean_id);

		// Drop the station location links, they are meaningless without the logbook
		$this->db->where('station_logbook_id', $clean_id);
		$this->db->delete('station_logbooks_relationship');

		// Delete logbook
		$this->db->where('user_id', $user_id);
		$this->db->where('logbook_id', $clean_id);
		$this->db->delete('station_logbooks');
	}

	function edit() {
		$data = array(
			'logbook_name' => $this->input->post('station_logbook_name', true),
		);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $this->input->post('logbook_id', true));
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
		if (!$this->check_logbook_is_accessible($cleanId, $user_id)) {
			return;
		}

		$data = array(
			'active_station_logbook' => $cleanId,
		);

		$this->db->where('user_id', $user_id);
		$this->db->update('users', $data);
	}

	function logbook($id, $user_id = null) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->where('user_id', $user_id ?? $this->session->userdata('user_id'));	// Fallback to session-uid, if userid is omitted
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
	function create_logbook_location_link($logbook_id, $location_id, $user_id = null) {
		// Clean ID
		$clean_logbook_id = $this->security->xss_clean($logbook_id);
		$clean_location_id = $this->security->xss_clean($location_id);
		$user_id = $user_id ?? $this->session->userdata('user_id');	// Fallback to session-uid, if userid is omitted

		// be sure that logbook belongs to user
		if (!$this->check_logbook_is_accessible($clean_logbook_id, $user_id)) {
			return;
		}

		// be sure that station belongs to user
		$this->load->model('Stations');
		if (!$this->Stations->check_station_against_user($clean_location_id, $user_id)) {
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

	// Removes the relationship between a logbook and a station location
	function remove_logbook_location_link($logbook_id, $location_id, $user_id = null) {
		// Clean ID
		$clean_logbook_id = $this->security->xss_clean($logbook_id);
		$clean_location_id = $this->security->xss_clean($location_id);
		$user_id = $user_id ?? $this->session->userdata('user_id');	// Fallback to session-uid, if userid is omitted

		// be sure that logbook belongs to user
		if (!$this->check_logbook_is_accessible($clean_logbook_id, $user_id)) {
			return;
		}

		$this->db->where('station_logbook_id', $clean_logbook_id);
		$this->db->where('station_location_id', $clean_location_id);
		$this->db->delete('station_logbooks_relationship');
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

	public function check_logbook_is_accessible($id, $user_id = null) {
		// check if logbook belongs to user
		$this->db->select('logbook_id');
		$this->db->where('user_id', $user_id ?? $this->session->userdata('user_id'));	// Fallback to session-uid, if userid is omitted
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

	/*
	 * The station_location_ids linked to a logbook, as a plain int array.
	 * Unlike list_logbook_relationships() this returns an empty array when
	 * nothing is linked; that one's [-1] sentinel is relied on by its callers.
	 */
	function get_linked_station_ids($logbook_id) {
		$rows = $this->db->select('station_location_id')
			->where('station_logbook_id', (int) $logbook_id)
			->get('station_logbooks_relationship')
			->result();
		return array_map(fn($r) => (int) $r->station_location_id, $rows);
	}

	/*
	 * Whether $user_id already has a logbook of that name. The name is compared
	 * exactly as it will be stored, so the caller has to clean it first.
	 */
	function logbook_name_exists($logbook_name, $user_id = null) {
		$this->db->where('user_id', $user_id ?? $this->session->userdata('user_id'));	// Fallback to session-uid, if userid is omitted
		$this->db->where('logbook_name', $logbook_name);
		return $this->db->get('station_logbooks')->num_rows() > 0;
	}
}
?>
