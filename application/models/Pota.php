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

		if (empty($logbooks_locations_array) || $logbooks_locations_array[0] === -1) {
			return [];
		}

		$location_list = "'" . implode("','", $logbooks_locations_array) . "'";

		// Default filter mirrors the POTA page's checkbox defaults, so the
		// initial server-rendered table matches what the AJAX endpoints return
		// (awards/pota_table + awards/pota_map) before "Apply Filters" is used.
		$postdata = [
			'qsl'       => 1,
			'lotw'      => 1,
			'eqsl'      => null,
			'qrz'       => null,
			'clublog'   => null,
			'worked'    => 1,
			'confirmed' => 1,
			'band'      => 'All',
			'mode'      => 'All',
			'dateFrom'  => null,
			'dateTo'    => null,
		];

		return $this->get_qso_list($postdata, $location_list);
	}

	function ham_of_note($callsign) {
		$this->db->where('callsign', $callsign);
		$this->db->order_by('description asc');

		return $this->db->get('hams_of_note');
	}

	/*
	 * The full POTA reference directory with coordinates only — no QSO or
	 * worked/confirmed status. Powers the optional clustered overlay on the
	 * Activation Planner map, where it's used to look up where a reference sits.
	 * Rows without coordinates are skipped because they can't be plotted.
	 */
	function get_directory() {
		$sql = "SELECT reference, name, lat, lon, active
				FROM pota_directory
				WHERE lat IS NOT NULL
				AND lon IS NOT NULL
				ORDER BY reference ASC";
		$query = $this->db->query($sql);

		$result = [];
		foreach ($query->result() as $row) {
			$result[] = [
				'reference' => $row->reference,
				'name'      => $row->name,
				'lat'       => (float) $row->lat,
				'lon'       => (float) $row->lon,
				'inactive'  => ((string) $row->active === '0'),
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
			$q = $this->db->select('reference, name, lat, lon, active')
				->from('pota_directory')
				->where_in('reference', $refs)
				->get();
			foreach ($q->result() as $r) {
				if ($r->lat !== null && $r->lon !== null) {
					$dirMap[$r->reference] = [
						'name'     => $r->name,
						'lat'      => (float) $r->lat,
						'lon'      => (float) $r->lon,
						'inactive' => ((string) $r->active === '0'),
					];
				}
			}
		}

		$result = [];
		foreach ($perRef as $reference => $confirmed) {
			$status = $confirmed ? 'C' : 'W';

			if ($status == 'C' && ($postdata['confirmed'] ?? null) != 1) continue;
			if ($status == 'W' && ($postdata['worked'] ?? null) != 1) continue;

			$coords = $dirMap[$reference] ?? ['name' => null, 'lat' => null, 'lon' => null, 'inactive' => false];
			$result[] = [
				'reference' => $reference,
				'name'      => $coords['name'],
				'lat'       => $coords['lat'],
				'lon'       => $coords['lon'],
				'inactive'  => $coords['inactive'],
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

	/*
	 * The POTA award tier ladder. The same thresholds apply to both the
	 * Activator and Hunter award tracks, so this is shared by both.
	 * Source: https://docs.pota.app/docs/awards.html
	 */
	function award_tiers() {
		return [
			'standard' => [
				['name' => 'Bronze', 'threshold' => 10],
				['name' => 'Silver', 'threshold' => 20],
				['name' => 'Gold', 'threshold' => 30],
				['name' => 'Platinum', 'threshold' => 40],
				['name' => 'Diamond', 'threshold' => 50],
				['name' => 'Sapphire', 'threshold' => 75],
			],
			'advanced' => [
				['name' => 'Arizona Agave', 'threshold' => 100],
				['name' => 'Enrubio', 'threshold' => 200],
				['name' => 'Ouachita Mountain Goldenrod', 'threshold' => 300],
				['name' => 'Stenogyne Kanehoana', 'threshold' => 400],
				['name' => "Howell's Spectacular Thelypody", 'threshold' => 500],
				['name' => 'Texas Wild Rice', 'threshold' => 600],
				['name' => "Wiggin's Acalypha", 'threshold' => 700],
				['name' => 'Georgia Aster', 'threshold' => 800],
				['name' => 'Rafflesia Flower', 'threshold' => 900],
				['name' => 'Western Prairie Fringed Orchid', 'threshold' => 1000],
				['name' => 'Echinacea Paradoxa', 'threshold' => 1500],
				['name' => 'Glandularia Tampensis', 'threshold' => 2000],
				['name' => 'Heliconia Angusta', 'threshold' => 2500],
				['name' => 'Argyroxiphium Sandwicense', 'threshold' => 3000],
				['name' => 'Acacia Koaia', 'threshold' => 3500],
				['name' => 'Kokia Drynarioides', 'threshold' => 4000],
				['name' => 'Acampe Longifolia', 'threshold' => 4500],
				['name' => 'Virginia Big Eared Bat', 'threshold' => 5000],
				['name' => 'Tipton Kangaroo Rat', 'threshold' => 5500],
				['name' => 'Sierra Nevada Bighorn Sheep', 'threshold' => 6000],
				['name' => 'Red Wolf', 'threshold' => 6500],
				['name' => 'Pronghorn Antelope', 'threshold' => 7000],
				['name' => 'Ocelot', 'threshold' => 7500],
				['name' => 'African Forest Elephant', 'threshold' => 8000],
				['name' => 'Amur Leopard', 'threshold' => 8500],
				['name' => 'Black Rhino', 'threshold' => 9000],
				['name' => 'Bornean Orangutan', 'threshold' => 9500],
				['name' => 'Cross River Gorilla', 'threshold' => 10000],
				['name' => 'Sand Tiger Shark', 'threshold' => 10500],
				['name' => 'Mountain Tapir', 'threshold' => 11000],
				['name' => 'Ring-tailed Lemur', 'threshold' => 11500],
				['name' => 'Green Sea Turtle', 'threshold' => 12000],
				['name' => 'Northern Rockhopper Penguin', 'threshold' => 12500],
				['name' => 'Ethiopian Wolf', 'threshold' => 13000],
				['name' => 'Harlequin Duck', 'threshold' => 13500],
				['name' => 'Wood Bison', 'threshold' => 14000],
				['name' => 'Mountain Nyala', 'threshold' => 14500],
				['name' => 'Tiger Quoll', 'threshold' => 15000],
				['name' => 'Oriental Stork', 'threshold' => 15500],
				['name' => 'Siberian Tiger', 'threshold' => 16000],
				['name' => "Grandidier's Mongoose", 'threshold' => 16500],
				['name' => 'Crested Ibis', 'threshold' => 17000],
				['name' => 'Nubian Giraffe', 'threshold' => 17500],
				['name' => 'Lau Banded Iguana', 'threshold' => 18000],
				['name' => 'Black-footed Ferret', 'threshold' => 18500],
				['name' => 'Gray Fox', 'threshold' => 19000],
				['name' => 'Chinese Pangolin', 'threshold' => 19500],
				['name' => 'Tasmanian Devil', 'threshold' => 20000],
			],
		];
	}

	/*
	 * Distinct POTA references the user has ACTIVATED (as activator, i.e.
	 * COL_MY_POTA_REF), across the active logbook's station locations.
	 * Multi-park QSOs store "K-1234,K-5678" in one column, so each row's
	 * value is split and counted per-park in PHP (mirrors the explode idiom
	 * in count_unique_references()). Returns [{reference, last, qso_count}].
	 */
	function activated_references() {
		$this->load->model('logbooks_model');
		$locs = $this->logbooks_model->list_logbook_relationships(
			$this->session->userdata('active_station_logbook'));
		if (!$locs || $locs[0] === -1) {
			return [];
		}

		$this->db->select('COL_MY_POTA_REF ref, COL_TIME_ON last');
		$this->db->where_in('station_id', $locs);
		$this->db->where('COL_MY_POTA_REF IS NOT NULL');
		$this->db->where('COL_MY_POTA_REF !=', '');
		$q = $this->db->get($this->config->item('table_name'));

		$out = [];
		foreach ($q->result() as $r) {
			$last = $r->last ?: '';
			foreach (explode(',', (string) $r->ref) as $sub) {
				$sub = trim($sub);
				if ($sub === '') { continue; }
				if (!isset($out[$sub])) {
					$out[$sub] = ['reference' => $sub, 'last' => $last, 'qso_count' => 0];
				}
				$out[$sub]['qso_count']++;
				if ($last && (!$out[$sub]['last'] || strtotime($last) > strtotime($out[$sub]['last']))) {
					$out[$sub]['last'] = $last;
				}
			}
		}
		return array_values($out);
	}

	/*
	 * Count of distinct parks worked in the active logbook. $column selects
	 * the track: COL_POTA_REF = parks hunted, COL_MY_POTA_REF = parks
	 * activated. Comma-separated multi-park references are expanded so each
	 * park counts once. No QSL filter — POTA awards are based on contacts in
	 * the log, not on confirmation.
	 */
	function count_unique_references($column) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array || $logbooks_locations_array[0] === -1) {
			return 0;
		}

		// Whitelist the column name before interpolating it into the query.
		$column = ($column === 'COL_MY_POTA_REF') ? 'COL_MY_POTA_REF' : 'COL_POTA_REF';

		$this->db->distinct();
		$this->db->select($column);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->where($column . ' IS NOT NULL');
		$this->db->where($column . ' !=', '');
		$query = $this->db->get($this->config->item('table_name'));

		$refs = [];
		foreach ($query->result() as $row) {
			foreach (explode(',', (string) $row->{$column}) as $ref) {
				$ref = trim($ref);
				if ($ref !== '') {
					$refs[$ref] = true;
				}
			}
		}

		return count($refs);
	}

}

?>
