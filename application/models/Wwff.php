<?php

class Wwff extends CI_Model {

	function get_all() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if ($logbooks_locations_array[0] === -1) {
			return null;
		}

		$this->load->model('bands');

		$bandslots = $this->bands->get_worked_bands('wwff');

		if(!$bandslots) return null;

		$this->db->where_in("station_id", $logbooks_locations_array);
		$this->db->where_in("col_band", $bandslots);
		$this->db->order_by("COL_WWFF_REF", "ASC");
		$this->db->where('COL_WWFF_REF !=', '');

		return $this->db->get($this->config->item('table_name'));
	}

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
		$q = $this->db->get('wwff_directory');

		foreach ($q->result() as $row) {
			$json[] = ['name' => $row->reference];
		}

		return $json;
	}

	/*
	 * The full WWFF reference directory with coordinates.
	 * Powers the optional clustered overlay on the Activation Planner map. Rows without coordinates are
	 * skipped because they can't be plotted.
	 */
	function get_directory() {
		$sql = "SELECT reference, name, lat, lon
			FROM wwff_directory
			WHERE lat IS NOT NULL AND lon IS NOT NULL
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

	function get_map_data($postdata, $location_list) {
		$bindings = [];

		$sql = "SELECT thcv.COL_WWFF_REF AS reference,
				MAX(wd.lat) AS lat, MAX(wd.lon) AS lon, MAX(wd.name) AS name,
				MAX(CASE WHEN thcv.COL_QSL_RCVD = 'Y' THEN 1 ELSE 0 END) AS qsl,
				MAX(CASE WHEN thcv.COL_LOTW_QSL_RCVD = 'Y' THEN 1 ELSE 0 END) AS lotw,
				MAX(CASE WHEN thcv.COL_EQSL_QSL_RCVD = 'Y' THEN 1 ELSE 0 END) AS eqsl,
				MAX(CASE WHEN thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y' THEN 1 ELSE 0 END) AS qrz,
				MAX(CASE WHEN thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' THEN 1 ELSE 0 END) AS clublog
			FROM " . $this->config->item('table_name') . " thcv
			LEFT JOIN wwff_directory wd ON wd.reference = thcv.COL_WWFF_REF
			WHERE thcv.station_id IN (" . $location_list . ")
				AND thcv.COL_WWFF_REF IS NOT NULL
				AND thcv.COL_WWFF_REF <> ''";

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

		$sql .= " GROUP BY thcv.COL_WWFF_REF";
		$sql .= " ORDER BY thcv.COL_WWFF_REF ASC";

		$query = $this->db->query($sql, $bindings);

		$result = [];
		foreach ($query->result() as $row) {
			$isConfirmed = false;
			if (($postdata['qsl'] ?? 0) == 1 && $row->qsl) $isConfirmed = true;
			elseif (($postdata['lotw'] ?? 0) == 1 && $row->lotw) $isConfirmed = true;
			elseif (($postdata['eqsl'] ?? 0) == 1 && $row->eqsl) $isConfirmed = true;
			elseif (($postdata['qrz'] ?? 0) == 1 && $row->qrz) $isConfirmed = true;
			elseif (($postdata['clublog'] ?? 0) == 1 && $row->clublog) $isConfirmed = true;

			$status = $isConfirmed ? 'C' : 'W';

			if ($status == 'C' && ($postdata['confirmed'] ?? null) != 1) continue;
			if ($status == 'W' && ($postdata['worked'] ?? null) != 1) continue;

			$result[] = [
				'reference' => $row->reference,
				'name'      => $row->name,
				'lat'       => $row->lat !== null ? (float) $row->lat : null,
				'lon'       => $row->lon !== null ? (float) $row->lon : null,
				'status'    => $status,
			];
		}

		return $result;
	}

	function get_qso_list($postdata, $location_list) {
		$bindings = [];

		$sql = "SELECT thcv.COL_WWFF_REF, thcv.COL_TIME_ON, thcv.COL_CALL, thcv.COL_BAND,
				thcv.COL_SAT_NAME, thcv.COL_RST_SENT, thcv.COL_RST_RCVD, thcv.COL_PRIMARY_KEY,
				CASE WHEN thcv.COL_QSL_RCVD = 'Y' THEN 1 ELSE 0 END AS qsl,
				CASE WHEN thcv.COL_LOTW_QSL_RCVD = 'Y' THEN 1 ELSE 0 END AS lotw,
				CASE WHEN thcv.COL_EQSL_QSL_RCVD = 'Y' THEN 1 ELSE 0 END AS eqsl,
				CASE WHEN thcv.COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y' THEN 1 ELSE 0 END AS qrz,
				CASE WHEN thcv.COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y' THEN 1 ELSE 0 END AS clublog
			FROM " . $this->config->item('table_name') . " thcv
			WHERE thcv.station_id IN (" . $location_list . ")
				AND thcv.COL_WWFF_REF IS NOT NULL
				AND thcv.COL_WWFF_REF <> ''";

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

		$sql .= " ORDER BY thcv.COL_WWFF_REF ASC, thcv.COL_TIME_ON ASC";

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
