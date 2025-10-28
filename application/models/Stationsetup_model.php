<?php

class Stationsetup_model extends CI_Model {

	function getContainer($id, $session = true) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		if ($session) {
			$this->db->where('user_id', $this->session->userdata('user_id'));
		}
		$this->db->where('logbook_id', $clean_id);
		return $this->db->get('station_logbooks');
	}

	function saveContainer() {
		$data = array(
			'logbook_name' => xss_clean($this->input->post('name', true)),
		);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', xss_clean($this->input->post('id', true)));
		$this->db->update('station_logbooks', $data);
	}

	function remove_public_slug($logbook_id) {
		// Also clean up static map images first
		if (!$this->load->is_loaded('staticmap_model')) {
			$this->load->model('staticmap_model');
		}
		$this->staticmap_model->remove_static_map_image(null, $logbook_id);

		$this->db->set('public_slug', null);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', xss_clean($logbook_id));
		$this->db->update('station_logbooks');
	}

	function saveVisitorLink($id, $name) {
		$this->db->set('public_slug', $name);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $id);
		$this->db->update('station_logbooks');
	}

	function togglePublicSearch($id, $publicSearch) {
		$data = array(
			'public_search' => ($publicSearch === 'true' ? 1 : 0)
		);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('logbook_id', $id);
		$this->db->update('station_logbooks', $data);
	}

	function unLinkLocations($logbook_id, $station_id) {

		// be sure that logbook belongs to user
		if (!$this->check_logbook_is_accessible($logbook_id)) {
			return;
		}

		// be sure that station belongs to user
		$this->load->model('Stations');
		if (!$this->Stations->check_station_is_accessible($station_id)) {
			return;
		}

		// Delete relationship
		$this->db->where('station_logbook_id', $logbook_id);
		$this->db->where('station_location_id', $station_id);
		$this->db->delete('station_logbooks_relationship');
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

	// Creates relationship between a logbook and a station location
	function create_logbook_location_link($logbook_id, $location_id) {
		// be sure that logbook belongs to user
		if (!$this->check_logbook_is_accessible($logbook_id)) {
			return;
		}

		// be sure that station belongs to user
		$this->load->model('Stations');
		if (!$this->Stations->check_station_is_accessible($location_id)) {
			return;
		}

		// Create data array with field values
		$data = array(
			'station_logbook_id' => $logbook_id,
			'station_location_id' =>  $location_id,
		);

		// Insert Record
		$this->db->insert('station_logbooks_relationship', $data);
	}

	function relationship_exists($logbook_id, $location_id) {
		$this->db->where('station_logbook_id', $logbook_id);
		$this->db->where('station_location_id', $location_id);
		$query = $this->db->get('station_logbooks_relationship');

		if ($query->num_rows() > 0){
			return true;
		} else {
			return false;
		}
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

		if ($query->num_rows() == 1){
			return $query->row()->logbook_id;
		} elseif ($query->num_rows() > 1) {
			log_message('error', 'Multiple logbooks with same public_slug found!');
			return false;
		} else {
			return false;
		}

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

	// Get public slug for a logbook
	function get_slug($logbook_id) {
		$this->db->where('logbook_id', $logbook_id);
		$this->db->where('public_slug !=', null);
		$query = $this->db->get('station_logbooks');

		if ($query->num_rows() == 1){
			return $query->row()->public_slug;
		} else {
			return false;
		}
	}

	function locationInfo($id) {
		$userid = $this->session->userdata('user_id'); // Fallback to session-uid, if userid is omitted
		$this->db->select('station_profile.station_profile_name, station_profile.station_callsign, dxcc_entities.name as station_country, dxcc_entities.end as dxcc_end');
		$this->db->where('user_id', $userid);
		$this->db->where('station_id', $id);
		$this->db->join('dxcc_entities','station_profile.station_dxcc = dxcc_entities.adif','left outer');
		return $this->db->get('station_profile');
	}

	function get_container_relations($id, $reverse = false) {

		if ($reverse == false) {
			$searchIn = 'station_logbook_id';
		} else {
			$searchIn = 'station_location_id';
		}

		$relationships_array = array();

		$this->db->where($searchIn, $id);
		$query = $this->db->get('station_logbooks_relationship');

		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				if ($reverse == false) {
					array_push($relationships_array, $row->station_location_id);
				} else {
					array_push($relationships_array, $row->station_logbook_id);
				}
			}

			return $relationships_array;
		} else {
			return array(-1);	// Put some default-Value here, if no relation found
		}
	}

	function public_slug_exists_userid($slug) {
		$this->db->where('public_slug', $this->security->xss_clean($slug));
		$query = $this->db->get('station_logbooks');

		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				return $row->user_id;
			}
		} else {
			return -1;
		}
	}

	function get_all_locations() {
		$this->db->select('station_profile.*, dxcc_entities.name as station_country, dxcc_entities.end as dxcc_end, count('.$this->config->item('table_name').'.station_id) as qso_total, max(col_time_on) as lastqsodate, exists(select 1 from station_logbooks_relationship where station_location_id = station_profile.station_id and station_logbook_id = '.($this->session->userdata('active_station_logbook') ?? 0).') as linked');
		$this->db->from('station_profile');
		$this->db->join($this->config->item('table_name'),'station_profile.station_id = '.$this->config->item('table_name').'.station_id','left');
		$this->db->join('dxcc_entities','station_profile.station_dxcc = dxcc_entities.adif','left outer');
		$this->db->group_by('station_profile.station_id');
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$this->db->or_where('station_profile.user_id =', NULL);

		return $this->db->get();
	}

	function list_all_locations() {
		$sql = "select station_profile_name, station_gridsquare, station_city, station_iota, station_sota, station_callsign, station_power, station_dxcc, dxcc_entities.name as dxccname, dxcc_entities.prefix as dxccprefix, station_cnty, station_cq, station_itu, station_active, eqslqthnickname, state, county, station_sig, station_sig_info, qrzrealtime, station_wwff, station_pota, oqrs, oqrs_text, oqrs_email, webadifrealtime, clublogrealtime, clublogignore, hrdlogrealtime, creation_date, last_modified
	 from station_profile
	 join dxcc_entities on station_profile.station_dxcc = dxcc_entities.adif
	 where user_id = ?";

		$query = $this->db->query($sql, array($this->session->userdata('user_id')));
		return $query;
	}
}

?>
