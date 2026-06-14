<?php
class Contesting_import_model extends CI_Model {

	/**
	 * Returns historical contest QSO groups not yet linked to any contest session,
	 * scoped to the current user's stations. Groups by (COL_CONTEST_ID, station_id, year).
	 * QSOs without a valid COL_QSO_DATE are excluded.
	 *
	 * @return array
	 */
	function get_legacy_contest_groups() {
		$user_id = $this->session->userdata('user_id');
		return $this->_fetch_legacy_groups_for_users([$user_id]);
	}

	/**
	 * Returns historical contest QSO groups for ALL users of this instance,
	 * not yet linked to any contest session.
	 *
	 * @return array
	 */
	function get_all_legacy_contest_groups() {
		return $this->_fetch_legacy_groups_for_users([]);
	}

	/**
	 * Finds a contest entry by ADIF name. Returns 1 ("Other") if none found.
	 *
	 * @param string $adif_name
	 * @return int contest.id
	 */
	function ensure_contest_exists($adif_name) {
		$query = $this->db->query(
			"SELECT id FROM contest WHERE adifname = ? LIMIT 1",
			[$adif_name]
		);
		if ($query->num_rows() > 0) {
			return (int)$query->row()->id;
		}
		return 1; // "Other"
	}

	/**
	 * Imports a single legacy contest group for the current session user.
	 *
	 * @param string $adif_name
	 * @param int    $station_id
	 * @param int    $year
	 * @return int Number of QSOs linked, 0 on ownership failure
	 */
	function import_legacy_contest_group($adif_name, $station_id, $year) {
		$user_id = $this->session->userdata('user_id');
		return $this->_do_import_legacy_group($adif_name, $station_id, $year, $user_id);
	}

	/**
	 * Imports a single legacy contest group for an explicitly specified user.
	 * Used by the all-users admin import.
	 *
	 * @param string $adif_name
	 * @param int    $station_id
	 * @param int    $year
	 * @param int    $user_id
	 * @return int Number of QSOs linked, 0 on ownership failure
	 */
	function import_legacy_contest_group_as_user($adif_name, $station_id, $year, $user_id) {
		return $this->_do_import_legacy_group($adif_name, $station_id, $year, (int)$user_id);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	private function _fetch_legacy_groups_for_users(array $user_ids) {
		$table = $this->config->item('table_name');

		$user_filter = empty($user_ids)
			? ""
			: "AND t.station_id IN (SELECT station_id FROM station_profile WHERE user_id = ?)";

		$sql = "SELECT
					t.COL_CONTEST_ID AS adif_name,
					t.station_id,
					YEAR(t.COL_QSO_DATE) AS contest_year,
					MIN(t.COL_TIME_ON) AS time_start,
					MAX(t.COL_TIME_ON) AS time_end,
					COUNT(*) AS qso_count,
					c.id AS contest_table_id,
					COALESCE(c.name, t.COL_CONTEST_ID) AS contest_name,
					sp.station_callsign,
					sp.user_id AS owner_user_id
				FROM {$table} t
				LEFT JOIN contest c ON c.adifname = t.COL_CONTEST_ID
				LEFT JOIN station_profile sp ON sp.station_id = t.station_id
				LEFT JOIN contest_qsos cq ON cq.qso_id = t.COL_PRIMARY_KEY
				WHERE t.COL_CONTEST_ID IS NOT NULL
					AND t.COL_CONTEST_ID != ''
					AND t.COL_QSO_DATE IS NOT NULL
					AND cq.id IS NULL
					{$user_filter}
				GROUP BY t.COL_CONTEST_ID, t.station_id, YEAR(t.COL_QSO_DATE)
				ORDER BY time_start DESC";

		$bindings = empty($user_ids) ? [] : $user_ids;
		$query = $this->db->query($sql, $bindings);
		return $query->result_array();
	}

	private function _do_import_legacy_group($adif_name, $station_id, $year, $user_id) {
		$table = $this->config->item('table_name');

		// Verify station belongs to the specified user
		$check = $this->db->query(
			"SELECT station_id FROM station_profile WHERE station_id = ? AND user_id = ? LIMIT 1",
			[$station_id, $user_id]
		);
		if ($check->num_rows() === 0) {
			return 0;
		}

		$contest_id = $this->ensure_contest_exists($adif_name);
		$is_other   = ($contest_id === 1 && $adif_name !== 'Other');

		// Fetch unlinked QSOs for this group
		$qsos = $this->db->query(
			"SELECT t.COL_PRIMARY_KEY, t.COL_TIME_ON
			FROM {$table} t
			LEFT JOIN contest_qsos cq ON cq.qso_id = t.COL_PRIMARY_KEY
			WHERE t.COL_CONTEST_ID = ?
				AND t.station_id = ?
				AND YEAR(t.COL_QSO_DATE) = ?
				AND cq.id IS NULL
			ORDER BY t.COL_TIME_ON ASC",
			[$adif_name, $station_id, $year]
		)->result_array();

		if (empty($qsos)) {
			return 0;
		}

		$time_start = date('Y-m-d H:i:s', strtotime($qsos[0]['COL_TIME_ON']) - 3600);
		$time_end   = date('Y-m-d H:i:s', strtotime(end($qsos)['COL_TIME_ON']) + 3600);

		if ($is_other) {
			$comment = sprintf(__("Imported from logbook\n(ADIF: %s, Year: %d) [Original: %s]"), $adif_name, $year, $adif_name);
		} else {
			$comment = sprintf(__("Imported from logbook\n(ADIF: %s, Year: %d)"), $adif_name, $year);
		}

		$settings = json_encode(['exchangetype' => 'Exchange', 'copyexchangeto' => '', 'exchangefields' => ['exchange']]);

		$this->db->query(
			"INSERT INTO contest_session (user_id, contest_adif_id, time_start, time_end, station_id, comment, settings)
			 VALUES (?, ?, ?, ?, ?, ?, ?)",
			[$user_id, $contest_id, $time_start, $time_end, $station_id, $comment, $settings]
		);
		$session_id = (int)$this->db->insert_id();

		$linked = 0;
		foreach ($qsos as $qso) {
			$this->db->query(
				"INSERT INTO contest_qsos (contest_session_id, qso_id) VALUES (?, ?)",
				[$session_id, $qso['COL_PRIMARY_KEY']]
			);
			$linked++;
		}
		return $linked;
	}
}
