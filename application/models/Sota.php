<?php

class Sota extends CI_Model {

	function search_refs($term) {
		$json = [];
		$ref = strtoupper(trim((string) $term));
		if ($ref === '') {
			return $json;
		}

		$this->db->select('reference');
		$this->db->like('reference', $ref, 'after');
		$this->db->order_by('reference', 'asc');
		$this->db->limit(100);
		$q = $this->db->get('sota_directory');

		foreach ($q->result() as $row) {
			$json[] = ['name' => $row->reference];
		}

		return $json;
	}

	function get_all() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if ($logbooks_locations_array[0] === -1) {
			return null;
		}

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('sota');

		if(!$bandslots) return null;

		$this->db->where_in("station_id", $logbooks_locations_array);
		$this->db->where_in("col_band", $bandslots);
		$this->db->order_by("COL_SOTA_REF", "ASC");
		$this->db->where('COL_SOTA_REF !=', '');

		return $this->db->get($this->config->item('table_name'));
	}

	/*
	 * The full SOTA reference directory with coordinates only — no QSO or
	 * worked/confirmed status. Powers the optional clustered overlay on the
	 * Gridsquare Lookup map, where it's used to look up where a reference sits.
	 * Rows without coordinates are skipped because they can't be plotted.
	 */
	function get_directory() {
		$sql = "SELECT reference, name, lat, lon, altitude FROM sota_directory
		WHERE lat IS NOT null
		AND lon IS NOT NULL
		ORDER by reference";

		$query = $this->db->get('sota_directory');

		$result = [];
		foreach ($query->result() as $row) {
			$result[] = [
				'reference' => $row->reference,
				'name'      => $row->name,
				'lat'       => (float) $row->lat,
				'lon'       => (float) $row->lon,
				'altitude'  => $row->altitude
			];
		}

		return $result;
	}
}

?>
