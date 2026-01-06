<?php

class Publicsearch extends CI_Model {

	function search($slug, $callsign) {
		if ($this->public_search_enabled($slug)) {
			$userid = $this->get_userid_for_slug($slug);

			$sql = "SELECT * FROM ".$this->config->item('table_name')."
					JOIN station_profile ON station_profile.station_id = ".$this->config->item('table_name').".station_id
					JOIN station_logbooks_relationship ON station_logbooks_relationship.station_location_id = station_profile.station_id
					JOIN station_logbooks ON station_logbooks.logbook_id = station_logbooks_relationship.station_logbook_id
					LEFT OUTER JOIN lotw_users ON lotw_users.callsign = ".$this->config->item('table_name').".col_call
					WHERE station_logbooks.public_search = 1
					AND station_profile.user_id = ?
					AND station_logbooks.public_slug = ?
					AND ".$this->config->item('table_name').".COL_CALL LIKE ?";
			$query = $this->db->query($sql, array($userid, $slug, '%'.$callsign.'%'));
			return $query;
		}
		return false;
	}

	function get_userid_for_slug($slug) {
		$sql = "SELECT user_id FROM station_logbooks WHERE public_slug = ?";
		$query = $this->db->query($sql, array($slug));
		return $query->result_array()[0]['user_id'];
	}

	function public_search_enabled($slug) {
		if ($slug) {
			$sql = "SELECT public_search FROM station_logbooks WHERE public_slug = ?";
			$query = $this->db->query($sql, array($slug));

			if ($query->result_array()[0]['public_search'] == 1) {
				return true;
			}
		}
		return false;
	}

}

?>
