<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Countqsoby_model extends CI_Model
{
	/*
	 * Returns distinct QSO counts grouped by grid, dxcc or an award reference,
	 * honouring band/sat/orbit/propagation/mode filters.
	 * QSL checkboxes (qsl/lotw/eqsl/qrz/clublog) select the confirmation sources that
	 * count towards the "confirmed" number; they never hide unconfirmed QSOs.
	 */
	public function get_counts($postdata) {
		$clean = $this->security->xss_clean($postdata);
		$type = $clean['type'] ?? 'grid';

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->scoped_station_ids();

		if ($logbooks_locations_array === null) {
			return array('Error' => 'No QSOs found.');
		}

		$table = $this->config->item('table_name');
		$conf = $this->confirm_condition($clean);

		if ($type == 'pota') {
			$rows = $this->pota_counts($clean, $logbooks_locations_array, $table, $conf);
		} else {
			switch ($type) {
				case 'dxcc':
					$group = 'col_dxcc';
					$where = "col_dxcc IS NOT NULL AND col_dxcc > 0";
					$name_select = ', dxcc_entities.name AS group_name, dxcc_entities.prefix AS group_prefix, dxcc_entities.end AS group_end';
					$name_group = ', dxcc_entities.name, dxcc_entities.prefix, dxcc_entities.end';
					$join = ' LEFT OUTER JOIN dxcc_entities ON dxcc_entities.adif = ' . $table . '.COL_DXCC';
					break;
				case 'sota':
					$group = 'col_sota_ref';
					$where = 'LENGTH(col_sota_ref) > 0';
					$name_select = $name_group = $join = '';
					break;
				case 'iota':
					$group = 'UPPER(col_iota)';
					$where = 'LENGTH(col_iota) > 0';
					$name_select = $name_group = $join = '';
					break;
				case 'wwff':
					$group = 'col_wwff_ref';
					$where = 'LENGTH(col_wwff_ref) > 0';
					$name_select = $name_group = $join = '';
					break;
				case 'itu':
					$group = 'col_ituz';
					$where = 'LENGTH(col_ituz) > 0';
					$name_select = $name_group = $join = '';
					break;
				case 'cq':
					$group = 'col_cqz';
					$where = 'LENGTH(col_cqz) > 0';
					$name_select = $name_group = $join = '';
					break;
				default:
					$type = 'grid';
					$group = 'UPPER(SUBSTRING(col_gridsquare,1,4))';
					$where = 'LENGTH(col_gridsquare) > 0';
					$name_select = $name_group = $join = '';
			}

			$params = array();
			$sql = "SELECT $group AS group_key" . $name_select . ',
					COUNT(*) AS qso_count,
					SUM(CASE WHEN ' . $conf . ' THEN 1 ELSE 0 END) AS confirmed_count
				FROM ' . $table . $join . '
				LEFT OUTER JOIN satellite
					ON ' . $table . ".COL_PROP_MODE = 'SAT'
					AND (" . $table . '.COL_SAT_NAME = satellite.name
						OR (satellite.displayname != \'\' AND ' . $table . '.COL_SAT_NAME = satellite.displayname))
				WHERE ' . $where . ' AND ' . $this->station_in($logbooks_locations_array, $params);

			$this->add_filters($sql, $params, $clean);

			$sql .= " GROUP BY $group" . $name_group . ' ORDER BY qso_count DESC, group_key ASC';

			$rows = $this->db->query($sql, $params)->result_array();
		}

		if ($type == 'dxcc') {
			// Same caption logic as the DXCC award (Awards.php dxcc_map).
			// Rows without a name keep NULL so the frontend can fall back
			// to the ADIF entity id.
			foreach ($rows as &$row) {
				$row['group_deleted'] = !empty($row['group_end']);
				unset($row['group_end']);
				if ($row['group_name'] !== null) {
					$row['group_name'] = ucwords(strtolower($row['group_name']), '- (/');
					if (!empty($row['group_prefix'])) {
						$row['group_name'] .= ' (' . $row['group_prefix'] . ')';
					}
				}
				unset($row['group_prefix']);
			}
			unset($row);
		}

		$confirmed = 0;
		$total_qsos = 0;
		foreach ($rows as $row) {
			if ($row['confirmed_count'] > 0) {
				$confirmed++;
			}
			$total_qsos += $row['qso_count'];
		}

		return array(
			'ok' => 'OK',
			'type' => $type,
			'summary' => array(
				'distinct' => count($rows),
				'confirmed' => $confirmed,
				'qsos' => $total_qsos,
			),
			'groups' => $rows,
		);
	}

	/*
	 * POTA refs can be comma-separated multi-park values, so they are
	 * exploded and aggregated in PHP instead of a plain GROUP BY.
	 */
	private function pota_counts($clean, $logbooks_locations_array, $table, $conf) {
		$params = array();
		$sql = 'SELECT COL_POTA_REF, (CASE WHEN ' . $conf . " THEN 1 ELSE 0 END) AS is_confirmed
			FROM $table
			LEFT OUTER JOIN satellite
				ON $table.COL_PROP_MODE = 'SAT'
				AND ($table.COL_SAT_NAME = satellite.name
					OR (satellite.displayname != '' AND $table.COL_SAT_NAME = satellite.displayname))
			WHERE LENGTH(COL_POTA_REF) > 0 AND " . $this->station_in($logbooks_locations_array, $params);

		$this->add_filters($sql, $params, $clean);

		$result = array();
		foreach ($this->db->query($sql, $params)->result_array() as $row) {
			foreach (explode(',', $row['COL_POTA_REF']) as $ref) {
				$ref = strtoupper(trim($ref));
				if ($ref === '') {
					continue;
				}
				$result[$ref]['group_key'] = $ref;
				$result[$ref]['group_name'] = null;
				$result[$ref]['qso_count'] = ($result[$ref]['qso_count'] ?? 0) + 1;
				$result[$ref]['confirmed_count'] = ($result[$ref]['confirmed_count'] ?? 0) + $row['is_confirmed'];
			}
		}

		usort($result, function ($a, $b) {
			return $b['qso_count'] <=> $a['qso_count'] ?: $a['group_key'] <=> $b['group_key'];
		});

		return $result;
	}

	/*
	 * Builds the SQL condition deciding which QSOs count as confirmed.
	 * Mirrors the checkbox set used by gridmap: qsl, lotw, eqsl, qrz, clublog
	 * (sent as "true"/"false" strings by the JS).
	 */
	private function confirm_condition($clean) {
		$sql = '';
		if (($clean['qsl'] ?? '') === 'true') {
			$sql .= " or col_qsl_rcvd = 'Y'";
		}
		if (($clean['lotw'] ?? '') === 'true') {
			$sql .= " or col_lotw_qsl_rcvd = 'Y'";
		}
		if (($clean['eqsl'] ?? '') === 'true') {
			$sql .= " or col_eqsl_qsl_rcvd = 'Y'";
		}
		if (($clean['qrz'] ?? '') === 'true') {
			$sql .= " or col_qrzcom_qso_download_status = 'Y'";
		}
		if (($clean['clublog'] ?? '') === 'true') {
			$sql .= " or col_clublog_qso_download_status = 'Y'";
		}
		return $sql === '' ? '1=0' : '(' . substr($sql, 4) . ')';
	}

	/*
	 * Appends band/sat/orbit/propagation/mode/date filters with bound
	 * parameters, using the same band semantics as the DXCC award:
	 * band=SAT -> satellite segment (col_prop_mode='SAT', optional sat/orbit),
	 * band=All -> everything except SAT, regular band -> non-SAT + col_band.
	 */
	private function add_filters(&$sql, &$params, $clean) {
		$band = strtoupper($clean['band'] ?? 'All');

		if ($band == 'SAT') {
			$sql .= " AND col_prop_mode = 'SAT'";
			if (($clean['sat'] ?? 'All') != 'All') {
				$sql .= ' AND col_sat_name = ?';
				$params[] = $clean['sat'];
			}
		} elseif ($band != 'ALL') {
			$sql .= " AND (col_prop_mode != 'SAT' OR col_prop_mode IS NULL)";
			$sql .= ' AND col_band = ?';
			$params[] = $clean['band'];
		} else {
			$sql .= " AND (col_prop_mode != 'SAT' OR col_prop_mode IS NULL)";
		}

		if (($clean['orbit'] ?? 'All') != 'All') {
			$sql .= ' AND satellite.orbit = ?';
			$params[] = $clean['orbit'];
		}

		$propagation = $clean['propagation'] ?? 'All';
		if ($propagation == 'None') {			// Empty Propmode
			$sql .= " AND (TRIM(col_prop_mode) = '' OR col_prop_mode IS NULL)";
		} elseif ($propagation != 'All') {		// Propmode set, take care of it
			$sql .= ' AND col_prop_mode = ?';
			$params[] = $propagation;
		}

		if (!empty($clean['mode']) && $clean['mode'] != 'All') {
			$sql .= ' AND (col_mode = ? OR col_submode = ?)';
			$params[] = $clean['mode'];
			$params[] = $clean['mode'];
		}

		if (!empty($clean['dateFrom'])) {
			$sql .= ' AND col_time_on >= ?';
			$params[] = $clean['dateFrom'] . ' 00:00:00';
		}

		if (!empty($clean['dateTo'])) {
			$sql .= ' AND col_time_on <= ?';
			$params[] = $clean['dateTo'] . ' 23:59:59';
		}
	}

	/*
	 * Adds "station_id IN (...)" with bound placeholders. The column is
	 * prefixed with the QSO table name because qso_details() joins
	 * station_profile, which has a station_id column of its own.
	 */
	private function station_in($logbooks_locations_array, &$params) {
		$placeholders = '';
		foreach ($logbooks_locations_array as $idx => $station_id) {
			$placeholders .= $idx > 0 ? ',?' : '?';
			$params[] = $station_id;
		}
		return $this->config->item('table_name') . '.station_id IN (' . $placeholders . ')';
	}

	/*
	 * Station location ids of the active logbook, verified for ownership so
	 * that no QSOs of other users can leak into any query of this model:
	 * 1. the active logbook must belong to the session user
	 * 2. only station locations owned by that same user are kept
	 * Returns null when nothing valid is linked.
	 */
	private function scoped_station_ids() {
		$logbook_id = $this->session->userdata('active_station_logbook');

		if (empty($logbook_id) || !$this->logbooks_model->check_logbook_is_accessible($logbook_id)) {
			return null;
		}

		$rows = $this->db->select('station_logbooks_relationship.station_location_id')
			->from('station_logbooks_relationship')
			->join('station_profile', 'station_profile.station_id = station_logbooks_relationship.station_location_id')
			->where('station_logbooks_relationship.station_logbook_id', (int) $logbook_id)
			->where('station_profile.user_id', (int) $this->session->userdata('user_id'))
			->get()->result();

		$ids = array();
		foreach ($rows as $row) {
			$ids[] = (int) $row->station_location_id;
		}

		return $ids ?: null;
	}

	/*
	 * QSO drill-down for one group value. Same column list and joins as
	 * Distances_model::qso_details so the awards/details partial works.
	 */
	public function qso_details($type, $group, $band, $sat, $propagation, $mode = 'All', $orbit = 'All', $dateFrom = null, $dateTo = null, $postdata = array()) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->scoped_station_ids();

		if ($logbooks_locations_array === null) {
			return $this->db->query('SELECT 1 FROM DUAL WHERE 1=0');
		}

		$table = $this->config->item('table_name');
		$sql = "
			SELECT dxcc_entities.adif, lotw_users.callsign, COL_BAND, COL_CALL, COL_CLUBLOG_QSO_DOWNLOAD_DATE,
				COL_CLUBLOG_QSO_DOWNLOAD_STATUS, COL_CLUBLOG_QSO_UPLOAD_DATE, COL_CLUBLOG_QSO_UPLOAD_STATUS, COL_CONTEST_ID, COL_DISTANCE,
				COL_EQSL_QSL_RCVD, COL_EQSL_QSLRDATE, COL_EQSL_QSLSDATE, COL_EQSL_QSL_SENT, COL_FREQ, COL_GRIDSQUARE, COL_IOTA, COL_LOTW_QSL_RCVD,
				COL_LOTW_QSLRDATE, COL_LOTW_QSLSDATE, COL_LOTW_QSL_SENT, COL_MODE, COL_NAME, COL_OPERATOR, COL_POTA_REF, COL_PRIMARY_KEY,
				COL_QRZCOM_QSO_DOWNLOAD_DATE, COL_QRZCOM_QSO_DOWNLOAD_STATUS, COL_QRZCOM_QSO_UPLOAD_DATE, COL_QRZCOM_QSO_UPLOAD_STATUS,
				COL_QSL_RCVD, COL_QSL_RCVD_VIA, COL_QSLRDATE, COL_QSLSDATE, COL_QSL_SENT, COL_QSL_SENT_VIA, COL_QSL_VIA, COL_RST_RCVD,
				COL_RST_SENT, COL_SAT_NAME, COL_SOTA_REF, COL_SRX, COL_SRX_STRING, COL_STATE, COL_STX, COL_STX_STRING, COL_SUBMODE, COL_TIME_ON,
				COL_VUCC_GRIDS, COL_WWFF_REF, COL_PROP_MODE, COL_DCL_QSLRDATE, COL_DCL_QSLSDATE, COL_DCL_QSL_SENT, COL_DCL_QSL_RCVD,
				dxcc_entities.end, lotw_users.lastupload, dxcc_entities.name, satellite.displayname AS sat_displayname,
				station_profile.station_callsign, station_profile.station_gridsquare, station_profile.station_profile_name
			FROM $table
			INNER JOIN station_profile ON station_profile.station_id = $table.station_id
			LEFT OUTER JOIN dxcc_entities ON dxcc_entities.adif = $table.COL_DXCC
			LEFT OUTER JOIN lotw_users ON lotw_users.callsign = $table.col_call
			LEFT OUTER JOIN satellite
				ON $table.COL_PROP_MODE = 'SAT'
				AND ($table.COL_SAT_NAME = satellite.name
					OR (satellite.displayname != '' AND $table.COL_SAT_NAME = satellite.displayname))
			WHERE 1=1
		";

		$params = array();
		$sql .= ' AND ' . $this->station_in($logbooks_locations_array, $params);

		// Defense in depth: besides the already user-scoped station ids,
		// the joined station_profile rows must belong to the session user.
		$sql .= ' AND station_profile.user_id = ?';
		$params[] = (int) $this->session->userdata('user_id');

		switch ($type) {
			case 'dxcc':
				$sql .= ' AND col_dxcc = ?';
				$params[] = $group;
				break;
			case 'sota':
				$sql .= ' AND col_sota_ref = ?';
				$params[] = $group;
				break;
			case 'iota':
				$sql .= ' AND UPPER(col_iota) = ?';
				$params[] = $group;
				break;
			case 'wwff':
				$sql .= ' AND col_wwff_ref = ?';
				$params[] = $group;
				break;
			case 'itu':
				$sql .= ' AND col_ituz = ?';
				$params[] = $group;
				break;
			case 'cq':
				$sql .= ' AND col_cqz = ?';
				$params[] = $group;
				break;
			case 'pota':
				$sql .= ' AND FIND_IN_SET(?, REPLACE(COL_POTA_REF, \' \', \'\')) > 0';
				$params[] = $group;
				break;
			default:
				$sql .= ' AND UPPER(SUBSTRING(col_gridsquare,1,4)) = ?';
				$params[] = $group;
		}

		$clean = array('band' => $band, 'sat' => $sat, 'orbit' => $orbit, 'propagation' => $propagation, 'mode' => $mode, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo);
		$this->add_filters($sql, $params, $clean);

		// When the drill-down was opened from the "confirmed" column, only
		// QSOs confirmed via one of the checked sources are shown.
		if (($postdata['confirmed'] ?? '') === 'true') {
			$sql .= ' AND ' . $this->confirm_condition($this->security->xss_clean($postdata));
		}

		$sql .= ' ORDER BY COL_TIME_ON DESC';

		return $this->db->query($sql, $params);
	}
}
