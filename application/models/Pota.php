<?php

class Pota extends CI_Model {

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
		$q = $this->db->get('pota_directory');

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

		$bandslots = $this->bands->get_worked_bands('pota');

		if(!$bandslots) return null;

		$this->db->where_in("station_id", $logbooks_locations_array);
		$this->db->where_in("col_band", $bandslots);
		$this->db->order_by("COL_POTA_REF", "ASC");
		$this->db->where('COL_POTA_REF !=', '');

		return $this->db->get($this->config->item('table_name'));
	}

	function ham_of_note($callsign) {
		$this->db->where('callsign', $callsign);
		$this->db->order_by('description asc');

		return $this->db->get('hams_of_note');
	}

	/*
	 * The full POTA reference directory with coordinates only — no QSO or
	 * worked/confirmed status. Powers the optional clustered overlay on the
	 * Gridsquare Lookup map, where it's used to look up where a reference sits.
	 * Rows without coordinates are skipped because they can't be plotted.
	 */
	function get_directory() {
		$sql = "SELECT reference, name, lat, lon
				FROM pota_directory
				WHERE lat IS NOT NULL
				AND lon IS NOT NULL
				AND active = 1
				ORDER BY reference ASC";
		$query = $this->db->query($sql);

		$result = [];
		foreach ($query->result() as $row) {
			$result[] = [
				'reference' => $row->reference,
				'name'      => $row->name,
				'lat'       => (float) $row->lat,
				'lon'       => (float) $row->lon,
			];
		}

		return $result;
	}

}

?>
