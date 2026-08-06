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

	/*
	 * Worked POTA references with coordinates, grouped per individual park.
	 *
	 * Unlike WWFF, a single QSO can carry several POTA references in
	 * COL_POTA_REF (comma-separated, e.g. "K-1234,K-5678" for a 2-park
	 * activation), so we can't join the directory on equality. Instead we
	 * aggregate the confirmation flags per distinct ref-string, explode the
	 * strings in PHP and merge the best (confirmed beats worked) status onto
	 * each individual reference, then attach coordinates from pota_directory
	 * for the references that were actually worked.
	 */
	function get_map_data($postdata, $location_list) {
		$bindings = [];

		$sql = "SELECT thcv.COL_POTA_REF AS refs,
				MAX(CASE WHEN thcv.COL_QSL_RCVD = 'Y' THEN 1 ELSE 0 END) AS qsl,
				MAX(CASE WHEN thcv.COL_LOTW_QSL_RCVD = 'Y' THEN 1 ELSE 0 END) AS lotw,
				MAX(CASE WHEN thcv.COL_EQSL_QSL_RCVD = 'Y' THEN 1 ELSE 0 END) AS eqsl,
				MAX(CASE WHEN thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y' THEN 1 ELSE 0 END) AS qrz,
				MAX(CASE WHEN thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' THEN 1 ELSE 0 END) AS clublog
			FROM " . $this->config->item('table_name') . " thcv
			WHERE thcv.station_id IN (" . $location_list . ")
				AND thcv.COL_POTA_REF IS NOT NULL
				AND thcv.COL_POTA_REF <> ''";

		$band = $postdata['band'] ?? 'All';
		if ($band == 'All') {
			$sql .= " and (thcv.col_prop_mode !='SAT' or thcv.col_prop_mode is NULL)";
		}
		else if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$bindings[] = $band;
		} else {
			$sql .= " AND thcv.COL_BAND = ?";
			$sql .= " and (thcv.col_prop_mode !='SAT' or thcv.col_prop_mode is NULL)";
			$bindings[] = $band;
		}

		$mode = $postdata['mode'] ?? 'All';
		if ($mode !== 'All' && $mode !== '') {
			$sql .= " AND (thcv.COL_MODE = ? OR thcv.COL_SUBMODE = ?)";
			$bindings[] = $mode;
			$bindings[] = $mode;
		}

		if (!empty($postdata['dateFrom'])) {
			$sql .= " AND thcv.COL_TIME_ON >= ?";
			$bindings[] = $postdata['dateFrom'] . ' 00:00:00';
		}
		if (!empty($postdata['dateTo'])) {
			$sql .= " AND thcv.COL_TIME_ON <= ?";
			$bindings[] = $postdata['dateTo'] . ' 23:59:59';
		}

		$sql .= " GROUP BY thcv.COL_POTA_REF";
		$query = $this->db->query($sql, $bindings);

		// Accumulate the best status per individual reference across every
		// ref-string it appears in (true = confirmed, false = worked only).
		$perRef = [];
		foreach ($query->result() as $row) {
			$isConfirmed = false;
			if (($postdata['qsl'] ?? 0) == 1 && $row->qsl) $isConfirmed = true;
			elseif (($postdata['lotw'] ?? 0) == 1 && $row->lotw) $isConfirmed = true;
			elseif (($postdata['eqsl'] ?? 0) == 1 && $row->eqsl) $isConfirmed = true;
			elseif (($postdata['qrz'] ?? 0) == 1 && $row->qrz) $isConfirmed = true;
			elseif (($postdata['clublog'] ?? 0) == 1 && $row->clublog) $isConfirmed = true;

			foreach (explode(',', (string) $row->refs) as $reference) {
				$reference = trim($reference);
				if ($reference === '') continue;
				if (!isset($perRef[$reference])) {
					$perRef[$reference] = false;
				}
				if ($isConfirmed) {
					$perRef[$reference] = true;
				}
			}
		}

		// Look up coordinates only for the references that were worked.
		$dirMap = [];
		$refs = array_keys($perRef);
		if ($refs) {
			$q = $this->db->select('reference, name, lat, lon')
				->from('pota_directory')
				->where_in('reference', $refs)
				->get();
			foreach ($q->result() as $r) {
				if ($r->lat !== null && $r->lon !== null) {
					$dirMap[$r->reference] = [
						'name' => $r->name,
						'lat'  => (float) $r->lat,
						'lon'  => (float) $r->lon,
					];
				}
			}
		}

		$result = [];
		foreach ($perRef as $reference => $confirmed) {
			$status = $confirmed ? 'C' : 'W';

			if ($status == 'C' && ($postdata['confirmed'] ?? null) != 1) continue;
			if ($status == 'W' && ($postdata['worked'] ?? null) != 1) continue;

			$coords = $dirMap[$reference] ?? ['name' => null, 'lat' => null, 'lon' => null];
			$result[] = [
				'reference' => $reference,
				'name'      => $coords['name'],
				'lat'       => $coords['lat'],
				'lon'       => $coords['lon'],
				'status'    => $status,
			];
		}

		usort($result, function ($a, $b) {
			return strcmp($a['reference'], $b['reference']);
		});

		return $result;
	}

	function get_qso_list($postdata, $location_list) {
		$bindings = [];

		$sql = "SELECT thcv.COL_POTA_REF, thcv.COL_TIME_ON, thcv.COL_CALL, thcv.COL_BAND,
				thcv.COL_SAT_NAME, thcv.COL_RST_SENT, thcv.COL_RST_RCVD, thcv.COL_PRIMARY_KEY,
				CASE WHEN thcv.COL_QSL_RCVD = 'Y' THEN 1 ELSE 0 END AS qsl,
				CASE WHEN thcv.COL_LOTW_QSL_RCVD = 'Y' THEN 1 ELSE 0 END AS lotw,
				CASE WHEN thcv.COL_EQSL_QSL_RCVD = 'Y' THEN 1 ELSE 0 END AS eqsl,
				CASE WHEN thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y' THEN 1 ELSE 0 END AS qrz,
				CASE WHEN thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' THEN 1 ELSE 0 END AS clublog
			FROM " . $this->config->item('table_name') . " thcv
			WHERE thcv.station_id IN (" . $location_list . ")
				AND thcv.COL_POTA_REF IS NOT NULL
				AND thcv.COL_POTA_REF <> ''";

		$band = $postdata['band'] ?? 'All';
		if ($band == 'All') {
			$sql .= " and (thcv.col_prop_mode !='SAT' or thcv.col_prop_mode is NULL)";
		}
		else if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$bindings[] = $band;
		} else {
			$sql .= " AND thcv.COL_BAND = ?";
			$sql .= " and (thcv.col_prop_mode !='SAT' or thcv.col_prop_mode is NULL)";
			$bindings[] = $band;
		}

		$mode = $postdata['mode'] ?? 'All';
		if ($mode !== 'All' && $mode !== '') {
			$sql .= " AND (thcv.COL_MODE = ? OR thcv.COL_SUBMODE = ?)";
			$bindings[] = $mode;
			$bindings[] = $mode;
		}

		if (!empty($postdata['dateFrom'])) {
			$sql .= " AND thcv.COL_TIME_ON >= ?";
			$bindings[] = $postdata['dateFrom'] . ' 00:00:00';
		}
		if (!empty($postdata['dateTo'])) {
			$sql .= " AND thcv.COL_TIME_ON <= ?";
			$bindings[] = $postdata['dateTo'] . ' 23:59:59';
		}

		$sql .= " ORDER BY thcv.COL_POTA_REF ASC, thcv.COL_TIME_ON ASC";

		$query = $this->db->query($sql, $bindings);

		$result = [];
		foreach ($query->result() as $row) {
			$isConfirmed = false;
			if (($postdata['qsl'] ?? 0) == 1 && $row->qsl) $isConfirmed = true;
			elseif (($postdata['lotw'] ?? 0) == 1 && $row->lotw) $isConfirmed = true;
			elseif (($postdata['eqsl'] ?? 0) == 1 && $row->eqsl) $isConfirmed = true;
			elseif (($postdata['qrz'] ?? 0) == 1 && $row->qrz) $isConfirmed = true;
			elseif (($postdata['clublog'] ?? 0) == 1 && $row->clublog) $isConfirmed = true;

			if ($isConfirmed && ($postdata['confirmed'] ?? null) != 1) continue;
			if (!$isConfirmed && ($postdata['worked'] ?? null) != 1) continue;

			$result[] = $row;
		}

		return $result;
	}

}

?>
