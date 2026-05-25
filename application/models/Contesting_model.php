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
				cs.settings AS settings,
				c.name AS contest_name,
				c.id AS contest_id,
				c.adifname AS contest_adifname,
				sp.station_id AS station_id,
				sp.station_callsign AS station_callsign,
				sp.station_gridsquare AS station_gridsquare
			FROM contest_session cs
			JOIN contest c ON c.id = cs.contest_adif_id
			JOIN station_profile sp ON sp.station_id = cs.station_id
			WHERE cs.id = ? AND cs.user_id = ?
			LIMIT 1";
		$binding[] = $contest_session_id;
		$binding[] = $user_id;

		$query = $this->db->query($sql, $binding);
		$row = $query->row_array();
		if ($row && !empty($row['settings'])) {
			$settings = json_decode($row['settings'], true) ?? [];
			$row['copyexchangeto'] = $settings['copyexchangeto'] ?? '';
			$row['exchangefields'] = $settings['exchangefields'] ?? ['exchange'];
			$row['exchangetype']   = $settings['exchangetype']   ?? 'Exchange';
		} else {
			$row['copyexchangeto'] = '';
			$row['exchangefields'] = ['exchange'];
			$row['exchangetype']   = 'Exchange';
		}
		unset($row['settings']);
		return $row;
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
	function create_contest_session($contest_adif_id, $session_start, $session_end, $station_location, $session_notes, $return_id = false, $exchangetype = 'Exchange', $copyexchangeto = '', $exchangefields = []) {
		$user_id = $this->session->userdata('user_id');

		$settings = json_encode(['exchangetype' => $exchangetype, 'copyexchangeto' => $copyexchangeto, 'exchangefields' => $exchangefields]);

		$sql = "INSERT INTO contest_session (user_id, contest_adif_id, time_start, time_end, station_id, comment, settings)
				VALUES (?, ?, ?, ?, ?, ?, ?)";

		$bindings = [
			$user_id,
			$contest_adif_id, // TODO: Modify database to use contest_id instead of contest_adif_id
			$session_start,
			$session_end,
			$station_location,
			$session_notes,
			$settings
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
	function update_contest_session($contest_session_id, $contest_id, $time_start, $time_end, $station_id, $notes, $exchangetype = 'Exchange', $copyexchangeto = '', $exchangefields = []) {
		if (!clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("Officers must edit contests."));
			redirect('contesting');
		}
		$user_id = $this->session->userdata('user_id');

		$settings = json_encode(['exchangetype' => $exchangetype, 'copyexchangeto' => $copyexchangeto, 'exchangefields' => $exchangefields]);

		$sql = "UPDATE contest_session
				SET contest_adif_id = ?, time_start = ?, time_end = ?, station_id = ?, comment = ?, settings = ?
				WHERE id = ? AND user_id = ?";

		$bindings = [
			$contest_id,
			$time_start,
			$time_end,
			$station_id,
			$notes,
			$settings,
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
		if (!clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("Only clubstation officers can delete."));
			redirect('contesting');
		}
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
					lb.COL_GRIDSQUARE AS locator,
					lb.COL_OPERATOR AS operator
				FROM contest_qsos cq
				JOIN contest_session cs ON cs.id = cq.contest_session_id
				JOIN " . $this->config->item('table_name') . " lb ON lb.COL_PRIMARY_KEY = cq.qso_id
				WHERE cq.contest_session_id = ?
				ORDER BY cq.id ASC";

		$query = $this->db->query($sql, $bindings);
		return $query->result_array();
	}

	/**
	 * Fetches a single QSO, verifying it belongs to the given contest session.
	 * Returns the row (including operator_callsign) or null if not found.
	 *
	 * @param int $qso_id
	 * @param int $contest_session_id
	 * @return array|null
	 */
	function get_contest_qso($qso_id, $contest_session_id) {
		$table = $this->config->item('table_name');
		$sql = "SELECT lb.COL_PRIMARY_KEY AS qso_id, lb.COL_OPERATOR AS operator
				FROM contest_qsos cq
				JOIN {$table} lb ON lb.COL_PRIMARY_KEY = cq.qso_id
				WHERE cq.qso_id = ? AND cq.contest_session_id = ?
				LIMIT 1";
		$query = $this->db->query($sql, [$qso_id, $contest_session_id]);
		return $query->num_rows() > 0 ? $query->row_array() : null;
	}

	/**
	 * Updates a subset of editable fields on a contest QSO.
	 * MySQL's ON UPDATE CURRENT_TIMESTAMP on last_modified handles the timestamp automatically.
	 *
	 * @param int   $qso_id
	 * @param array $fields  Whitelisted column → value pairs
	 * @return bool
	 */
	function update_contest_qso($qso_id, $fields) {
		$table = $this->config->item('table_name');
		$this->db->where('COL_PRIMARY_KEY', $qso_id)->update($table, $fields);
		return $this->db->affected_rows() > 0;
	}

	/**
	 * Returns the maximum last_modified timestamp (in milliseconds) across all QSOs
	 * in the session. Used by check_sync to detect edits across browsers.
	 *
	 * @param int $contest_session_id
	 * @return int Unix timestamp in ms, or 0 if no QSOs exist
	 */
	function get_session_last_update($contest_session_id) {
		$table = $this->config->item('table_name');
		$sql = "SELECT UNIX_TIMESTAMP(MAX(lb.last_modified)) * 1000 AS ts
				FROM contest_qsos cq
				JOIN {$table} lb ON lb.COL_PRIMARY_KEY = cq.qso_id
				WHERE cq.contest_session_id = ?";
		$query = $this->db->query($sql, [$contest_session_id]);
		$row = $query->row_array();
		return (int)($row['ts'] ?? 0);
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

	/**
	 * Checks whether the current user owns a given contest session.
	 *
	 * @param int $contest_session_id
	 * @return bool
	 */
	function userCanAccessSession($contest_session_id) {
		$user_id = $this->session->userdata('user_id');
		$sql = "SELECT id FROM contest_session WHERE id = ? AND user_id = ? LIMIT 1";
		$query = $this->db->query($sql, [$contest_session_id, $user_id]);
		return $query->num_rows() > 0;
	}

	/**
	 * Returns the Cabrillo-specific settings sub-array stored in the session's settings JSON.
	 *
	 * @param int $contest_session_id
	 * @return array
	 */
	function get_cabrillo_settings($contest_session_id) {
		$user_id = $this->session->userdata('user_id');
		$sql = "SELECT settings FROM contest_session WHERE id = ? AND user_id = ? LIMIT 1";
		$query = $this->db->query($sql, [$contest_session_id, $user_id]);
		$row = $query->row_array();
		if ($row && !empty($row['settings'])) {
			$settings = json_decode($row['settings'], true) ?? [];
			return $settings['cabrillo'] ?? [];
		}
		return [];
	}

	/**
	 * Merges Cabrillo settings into the session's settings JSON without overwriting other fields.
	 *
	 * @param int $contest_session_id
	 * @param array $cabrillo_settings
	 * @return bool
	 */
	function save_cabrillo_settings($contest_session_id, $cabrillo_settings) {
		$user_id = $this->session->userdata('user_id');
		$sql_sel = "SELECT settings FROM contest_session WHERE id = ? AND user_id = ? LIMIT 1";
		$query = $this->db->query($sql_sel, [$contest_session_id, $user_id]);
		$row = $query->row_array();

		$settings = [];
		if ($row && !empty($row['settings'])) {
			$settings = json_decode($row['settings'], true) ?? [];
		}
		$settings['cabrillo'] = $cabrillo_settings;

		$sql_upd = "UPDATE contest_session SET settings = ? WHERE id = ? AND user_id = ?";
		$this->db->query($sql_upd, [json_encode($settings), $contest_session_id, $user_id]);
		return true;
	}

	/**
	 * Returns all QSOs of a contest session as a CI DB result object suitable for AdifHelper::getAdifLine().
	 * Includes full logbook row + station profile + DXCC country name.
	 *
	 * @param int $contest_session_id
	 * @return CI_DB_result
	 */
	function get_session_qsos_for_adif($contest_session_id) {
		$user_id = $this->session->userdata('user_id');
		$table = $this->config->item('table_name');

		$sql = "SELECT {$table}.*, station_profile.*, dxcc_entities.name AS station_country
				FROM contest_qsos cq
				JOIN contest_session cs ON cs.id = cq.contest_session_id
				JOIN {$table} ON {$table}.COL_PRIMARY_KEY = cq.qso_id
				JOIN station_profile ON station_profile.station_id = {$table}.station_id
				LEFT JOIN dxcc_entities ON dxcc_entities.adif = station_profile.station_dxcc
				WHERE cq.contest_session_id = ? AND cs.user_id = ?
				ORDER BY {$table}.COL_TIME_ON ASC";

		return $this->db->query($sql, [$contest_session_id, $user_id]);
	}

	/**
	 * Returns a sorted, space-separated string of distinct operators logged in a contest session.
	 * Falls back to COL_STATION_CALLSIGN when COL_OPERATOR is empty.
	 *
	 * @param int $contest_session_id
	 * @return string e.g. "HB9ABC HB9DEF"
	 */
	function get_session_operators($contest_session_id) {
		$user_id = $this->session->userdata('user_id');
		$table   = $this->config->item('table_name');

		$sql = "SELECT DISTINCT UPPER(IFNULL(NULLIF(TRIM({$table}.COL_OPERATOR), ''), {$table}.COL_STATION_CALLSIGN)) AS operator
				FROM contest_qsos cq
				JOIN contest_session cs ON cs.id = cq.contest_session_id
				JOIN {$table} ON {$table}.COL_PRIMARY_KEY = cq.qso_id
				WHERE cq.contest_session_id = ? AND cs.user_id = ?
				ORDER BY operator ASC";

		$query = $this->db->query($sql, [$contest_session_id, $user_id]);
		$ops   = array_column($query->result_array(), 'operator');
		return implode(' ', $ops);
	}

	/**
	 * Returns all QSOs of a contest session as a CI DB result object suitable for Cabrilloformat::qso().
	 * Selects only the columns required for Cabrillo output.
	 *
	 * @param int $contest_session_id
	 * @return CI_DB_result
	 */
	function get_session_qsos_for_cabrillo($contest_session_id) {
		$user_id = $this->session->userdata('user_id');
		$table = $this->config->item('table_name');

		$sql = "SELECT {$table}.COL_FREQ, {$table}.COL_MODE, {$table}.COL_TIME_ON,
					   {$table}.COL_CALL, {$table}.COL_RST_SENT, {$table}.COL_RST_RCVD,
					   {$table}.COL_STX, {$table}.COL_SRX,
					   {$table}.COL_STX_STRING, {$table}.COL_SRX_STRING,
					   {$table}.COL_GRIDSQUARE,
					   station_profile.station_callsign, station_profile.station_gridsquare
				FROM contest_qsos cq
				JOIN contest_session cs ON cs.id = cq.contest_session_id
				JOIN {$table} ON {$table}.COL_PRIMARY_KEY = cq.qso_id
				JOIN station_profile ON station_profile.station_id = {$table}.station_id
				WHERE cq.contest_session_id = ? AND cs.user_id = ?
				ORDER BY {$table}.COL_TIME_ON ASC";

		return $this->db->query($sql, [$contest_session_id, $user_id]);
	}
}
