<?php
class Contesting_import_model extends CI_Model {

	/**
	 * Returns historical contest QSO groups not yet linked to any contest session,
	 * scoped to the current user's stations. Groups by (COL_CONTEST_ID, station_id)
	 * and splits into separate sessions wherever consecutive QSOs are at least
	 * 72 hours apart and not within the same ISO calendar week (see
	 * _segment_qsos). QSOs without a valid COL_TIME_ON are excluded.
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
	 * @param int    $segment_start Unix timestamp of the segment's first QSO
	 * @return int Number of QSOs linked, 0 on ownership failure or unknown segment
	 */
	function import_legacy_contest_group($adif_name, $station_id, $segment_start) {
		$user_id = $this->session->userdata('user_id');
		return $this->_do_import_legacy_group($adif_name, $station_id, $segment_start, $user_id);
	}

	/**
	 * Imports a single legacy contest group for an explicitly specified user.
	 * Used by the all-users admin import.
	 *
	 * @param string $adif_name
	 * @param int    $station_id
	 * @param int    $segment_start Unix timestamp of the segment's first QSO
	 * @param int    $user_id
	 * @return int Number of QSOs linked, 0 on ownership failure or unknown segment
	 */
	function import_legacy_contest_group_as_user($adif_name, $station_id, $segment_start, $user_id) {
		return $this->_do_import_legacy_group($adif_name, $station_id, $segment_start, (int)$user_id);
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
					t.COL_TIME_ON,
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
					AND t.COL_TIME_ON IS NOT NULL
					AND cq.id IS NULL
					{$user_filter}
				ORDER BY t.COL_CONTEST_ID, t.station_id, t.COL_TIME_ON";

		$bindings = empty($user_ids) ? [] : $user_ids;
		$qsos = $this->db->query($sql, $bindings)->result_array();

		// Group QSOs by (adif_name, station_id), then split each group into
		// time segments so that preview rows match the sessions created on import
		$grouped = [];
		foreach ($qsos as $qso) {
			$grouped[$qso['adif_name'] . '|' . $qso['station_id']][] = $qso;
		}

		$result = [];
		foreach ($grouped as $group_qsos) {
			foreach ($this->_segment_qsos($group_qsos) as $segment) {
				$first = $segment[0];
				$last  = end($segment);
				$result[] = [
					'adif_name'        => $first['adif_name'],
					'station_id'       => $first['station_id'],
					'segment_start'    => strtotime($first['COL_TIME_ON']),
					'contest_year'     => (int)date('Y', strtotime($first['COL_TIME_ON'])),
					'time_start'       => $first['COL_TIME_ON'],
					'time_end'         => $last['COL_TIME_ON'],
					'qso_count'        => count($segment),
					'contest_table_id' => $first['contest_table_id'],
					'contest_name'     => $first['contest_name'],
					'station_callsign' => $first['station_callsign'],
					'owner_user_id'    => $first['owner_user_id'],
				];
			}
		}

		usort($result, function ($a, $b) {
			return $b['segment_start'] <=> $a['segment_start'];
		});
		return $result;
	}

	/**
	 * Splits a time-ordered list of QSOs into segments. A new segment starts
	 * whenever two consecutive QSOs are at least 72h apart — unless both QSOs
	 * fall into the same ISO calendar week (Mon-Sun). This keeps week-long
	 * activity events with sparse QSOs (e.g. one QSO on Monday, one on Friday)
	 * in a single session, while weekly series (same weekday every week) always
	 * cross a week boundary and are still split.
	 *
	 * @param array $qsos QSO rows ordered by COL_TIME_ON ascending
	 * @return array Array of segments, each an array of QSO rows
	 */
	private function _segment_qsos(array $qsos) {
		$segments = [];
		$current  = [];
		$prev_ts  = null;

		foreach ($qsos as $qso) {
			$ts = strtotime($qso['COL_TIME_ON']);
			if ($prev_ts !== null && ($ts - $prev_ts) >= 72 * 3600 && date('oW', $ts) !== date('oW', $prev_ts)) {
				$segments[] = $current;
				$current = [];
			}
			$current[] = $qso;
			$prev_ts = $ts;
		}
		if (!empty($current)) {
			$segments[] = $current;
		}
		return $segments;
	}

	private function _do_import_legacy_group($adif_name, $station_id, $segment_start, $user_id) {
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

		// Fetch all unlinked QSOs for this contest/station combination
		$all_qsos = $this->db->query(
			"SELECT t.COL_PRIMARY_KEY, t.COL_TIME_ON
			FROM {$table} t
			LEFT JOIN contest_qsos cq ON cq.qso_id = t.COL_PRIMARY_KEY
			WHERE t.COL_CONTEST_ID = ?
				AND t.station_id = ?
				AND t.COL_TIME_ON IS NOT NULL
				AND cq.id IS NULL
			ORDER BY t.COL_TIME_ON ASC",
			[$adif_name, $station_id]
		)->result_array();

		if (empty($all_qsos)) {
			return 0;
		}

		// Re-derive the segments and pick the one starting at the requested time
		$qsos = null;
		foreach ($this->_segment_qsos($all_qsos) as $segment) {
			if (strtotime($segment[0]['COL_TIME_ON']) === (int)$segment_start) {
				$qsos = $segment;
				break;
			}
		}
		if ($qsos === null) {
			return 0;
		}

		$year       = (int)date('Y', strtotime($qsos[0]['COL_TIME_ON']));
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
