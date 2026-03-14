<?php
class Contesting_model extends CI_Model {

	/**
	 * Retrieves the contests associated with the current user.
	 *
	 * @return array List of contests with their details.
	 */
	function get_user_contests() {
		$user_id = $this->session->userdata('user_id');

		$binding = [];
		$sql = "SELECT 
					cs.id AS contest_session_id,
					cs.time_start,
					cs.time_end,
					cs.comment,
					sp.station_callsign AS station,
					c.name AS contestname,
					COUNT(cq.id) AS qso_count
				FROM contest_session cs
				JOIN contest c ON c.id = cs.contest_adif_id
				JOIN station_profile sp ON sp.station_id = cs.station_id
				LEFT JOIN contest_qsos cq ON cq.contest_session_id = cs.id
				WHERE cs.user_id = ?
				GROUP BY cs.id
				ORDER BY cs.time_start DESC";
		$binding[] = $user_id;

		$query = $this->db->query($sql, $binding);
		return $query->result_array();
	}

	/**
	 * Retrieves information about a specific contest session.
	 *
	 * @param int $contest_session_id The ID of the contest session.
	 * @return array|null The contest session information or null if not found.
	 */
	function get_session_info($contest_session_id) {
		$user_id = $this->session->userdata('user_id');

		$binding = [];
		$sql = "SELECT cs.id AS contest_session_id,
				cs.time_start AS time_start,
				cs.time_end AS time_end,
				cs.comment AS comment,
				c.name AS contest_name,
				c.id AS contest_id,
				c.adifname AS contest_adifname,
				sp.station_id AS station_id
			FROM contest_session cs
			JOIN contest c ON c.id = cs.contest_adif_id
			JOIN station_profile sp ON sp.station_id = cs.station_id
			WHERE cs.id = ? AND cs.user_id = ?
			LIMIT 1";
		$binding[] = $contest_session_id;
		$binding[] = $user_id;

		$query = $this->db->query($sql, $binding);
		return $query->row_array();
	}

	/**
	 * Creates a new contest session for the current user.
	 *
	 * @param int $contest_adif_id The id of the contest (contest table)
	 * @param string $session_start The start time of the session.
	 * @param string $session_end The end time of the session.
	 * @param int $station_location The station location (station_id).
	 * @param string $session_notes Notes for the session.
	 * @return bool True on success, false on failure. If $return_id is true, returns the inserted session ID instead.
	 */
	function create_contest_session($contest_adif_id, $session_start, $session_end, $station_location, $session_notes, $return_id = false) {
		$user_id = $this->session->userdata('user_id');

		$sql = "INSERT INTO contest_session (user_id, contest_adif_id, time_start, time_end, station_id, comment)
				VALUES (?, ?, ?, ?, ?, ?)";

		$bindings = [
			$user_id,
			$contest_adif_id, // TODO: Modify database to use contest_id instead of contest_adif_id
			$session_start,
			$session_end,
			$station_location,
			$session_notes
		];

		if ($return_id) {
			$this->db->query($sql, $bindings);
			return $this->db->insert_id();
		} else {
			return $this->db->query($sql, $bindings) ? true : false;
		}
	}

	/**
	 * Updates an existing contest session for the current user.
	 * 
	 * @param int $contest_session_id The ID of the contest session to update.
	 * @param int $contest_id The id of the contest (contest table)
	 * @param string $time_start The start time of the session.
	 * @param string $time_end The end time of the session.
	 * @param int $station_id The station location (station_id).
	 * @param string $notes Notes for the session.
	 * @return bool True on success, false on failure.
	 */
	function update_contest_session($contest_session_id, $contest_id, $time_start, $time_end, $station_id, $notes) {
		$user_id = $this->session->userdata('user_id');

		$sql = "UPDATE contest_session
				SET contest_adif_id = ?, time_start = ?, time_end = ?, station_id = ?, comment = ?
				WHERE id = ? AND user_id = ?";

		$bindings = [
			$contest_id,
			$time_start,
			$time_end,
			$station_id,
			$notes,
			$contest_session_id,
			$user_id
		];

		$this->db->query($sql, $bindings);
		return true;
	}

	/**
	 * Deletes a contest session and its associated QSOs for the current user.
	 *
	 * @param int $contest_session_id The ID of the contest session to delete.
	 * @return bool True on success, false on failure.
	 */
	function delete_contest_session($contest_session_id) {
		$user_id = $this->session->userdata('user_id');

		// First, delete associated QSOs (this does not delete the QSOs themselves from the main logbook)
		$sql_delete_qsos = "DELETE FROM contest_qsos WHERE contest_session_id = ?";

		$bindings_qsos = [$contest_session_id];
		$this->db->query($sql_delete_qsos, $bindings_qsos);

		// Then, delete the contest session
		$sql_delete_session = "DELETE FROM contest_session WHERE id = ? AND user_id = ?";

		$bindings_session = [
			$contest_session_id,
			$user_id
		];

		$this->db->query($sql_delete_session, $bindings_session);
		return true;
	}

	/**
	 * Retrieves all QSOs associated with a specific contest session.
	 *
	 * @param int $contest_session_id The ID of the contest session.
	 * @return array List of QSOs in the session.
	 */
	function get_session_qsos($contest_session_id) {
		$bindings = [$contest_session_id];
		$sql = "SELECT 
					lb.COL_PRIMARY_KEY AS qso_id,
					lb.COL_CALL AS callsign,
					lb.COL_TIME_ON AS time_on,
					lb.COL_BAND AS band,
					lb.COL_FREQ AS frequency,
					lb.COL_MODE AS mode,
					lb.COL_SUBMODE AS submode,
					lb.COL_RST_SENT AS rst_sent,
					lb.COL_RST_RCVD AS rst_recv,
					lb.COL_STX AS serial_sent,
					lb.COL_SRX AS serial_recv,
					lb.COL_STX_STRING AS exch_sent,
					lb.COL_SRX_STRING AS exch_recv,
					lb.COL_GRIDSQUARE AS locator
				FROM contest_qsos cq
				JOIN contest_session cs ON cs.id = cq.contest_session_id
				JOIN " . $this->config->item('table_name') . " lb ON lb.COL_PRIMARY_KEY = cq.qso_id
				WHERE cq.contest_session_id = ?
				ORDER BY cq.id ASC";

		$query = $this->db->query($sql, $bindings);
		return $query->result_array();
	}

	/**
	 * Links a QSO to a contest session.
	 *
	 * @param int $qso_id The ID of the QSO.
	 * @param int $contest_session_id The ID of the contest session.
	 * @return bool True on success.
	 */
	function link_qso($qso_id, $contest_session_id) {
		$sql = "INSERT INTO contest_qsos (contest_session_id, qso_id)
				VALUES (?, ?)";

		$bindings = [
			$contest_session_id,
			$qso_id
		];

		$this->db->query($sql, $bindings);
		return true;
	}

	/**
	 * Retrieves the total QSO count for a contest session.
	 *
	 * @param int $contest_session_id The ID of the contest session.
	 * @return int The total number of QSOs in the session.
	 */
	function get_session_qso_count($contest_session_id) {
		$binding = [];
		$sql = "SELECT COUNT(*) AS qso_count
				FROM contest_qsos
				WHERE contest_session_id = ?";
		$binding[] = $contest_session_id;

		$query = $this->db->query($sql, $binding);
		$result = $query->row_array();
		return (int)$result['qso_count'];
	}
}
