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
					MAX(cs.time_start) AS time_start,
					MAX(cs.time_end) AS time_end,
					MAX(cs.comment) AS comment,
					MAX(sp.station_callsign) AS station,
					MAX(c.name) AS contestname,
					JSON_UNQUOTE(JSON_EXTRACT(MAX(cs.settings), '$.custom_name')) AS custom_name,
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
	 * Check if contest associated with current user
	 *
	 * @param int $contest_session_id The ID of the contest session.
	 * @return bool If user is associated with contest
	 */
	function check_user_contest($contest_session_id) {
		$user_id = $this->session->userdata('user_id');

		$sql = "SELECT 
					COUNT(*) AS cnt
				FROM contest_session
				WHERE id = ?
				AND user_id = ?";

		$query = $this->db->query($sql, [$contest_session_id, $user_id]);
		$row = $query->row();

		return ((int) $row->cnt === 1);
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
			$row['copyexchangeto']  = $settings['copyexchangeto']  ?? '';
			$row['exchangefields']  = $settings['exchangefields']  ?? ['exchange'];
			$row['exchangetype']    = $settings['exchangetype']    ?? 'Exchange';
			$row['callbook_lookup'] = $settings['callbook_lookup'] ?? true;
			$row['custom_name']     = $settings['custom_name']     ?? '';
			$row['serial_per_band'] = $settings['serial_per_band'] ?? false;
			$row['serial_scope']    = $settings['serial_scope']    ?? 'station';
		} else {
			$row['copyexchangeto']  = '';
			$row['exchangefields']  = ['exchange'];
			$row['exchangetype']    = 'Exchange';
			$row['callbook_lookup'] = true;
			$row['custom_name']     = '';
			$row['serial_per_band'] = false;
			$row['serial_scope']    = 'station';
		}
		unset($row['settings']);
		return $row;
	}

	/**
	 * Merges the given session settings with their defaults and returns the JSON
	 * representation stored in the contest_session.settings column.
	 *
	 * @param array $parameter_array Session settings, missing keys fall back to defaults.
	 * @return string JSON encoded settings.
	 */
	private function build_session_settings($parameter_array = []) {
		return json_encode(array_merge($this->session_settings_defaults(), $parameter_array));
	}

	/**
	 * Public defaults for a contest session's settings - the single source of
	 * truth for build_session_settings(), the API v2 contest resource and any
	 * other caller that needs the effective settings of a sparse JSON.
	 *
	 * @return array
	 */
	function session_settings_defaults() {
		return [
			'exchangetype'    => 'Serial',
			'copyexchangeto'  => '',
			'exchangefields'  => ['serial'],
			'callbook_lookup' => true,
			'custom_name'     => '',
			'serial_per_band' => false,
			'serial_scope'    => 'station',
		];
	}

	/**
	 * Validates contest session settings values. Only the keys present are
	 * checked, so it works for partial (API) and full (form) input alike.
	 * The allowed values mirror what the session form offers.
	 *
	 * @param mixed $settings Settings array to validate.
	 * @return string[] List of error messages, empty when valid.
	 */
	function validate_session_settings($settings) {
		if (!is_array($settings)) {
			return ['settings must be an object'];
		}
		$errors = [];

		$unknown = array_diff(array_keys($settings), array_keys($this->session_settings_defaults()));
		if (!empty($unknown)) {
			$errors[] = 'unknown settings key(s): ' . implode(', ', $unknown);
		}
		if (array_key_exists('exchangefields', $settings)) {
			$allowed = ['serial', 'gridsquare', 'exchange'];
			$fields = $settings['exchangefields'];
			if (!is_array($fields) || empty($fields) || array_diff($fields, $allowed) !== []) {
				$errors[] = 'exchangefields must be a non-empty array of: ' . implode(', ', $allowed);
			}
		}
		if (array_key_exists('serial_scope', $settings)
			&& !in_array($settings['serial_scope'], ['station', 'operator'], true)) {
			$errors[] = "serial_scope must be 'station' or 'operator'";
		}
		if (array_key_exists('copyexchangeto', $settings)
			&& !in_array($settings['copyexchangeto'], ['', 'dok', 'locator', 'qth', 'name', 'age', 'state', 'power'], true)) {
			$errors[] = 'copyexchangeto must be one of: dok, locator, qth, name, age, state, power (or empty)';
		}
		foreach (['callbook_lookup', 'serial_per_band'] as $flag) {
			if (array_key_exists($flag, $settings) && !is_bool($settings[$flag])) {
				$errors[] = $flag . ' must be a boolean';
			}
		}
		foreach (['custom_name', 'exchangetype'] as $string_key) {
			if (array_key_exists($string_key, $settings) && !is_string($settings[$string_key])) {
				$errors[] = $string_key . ' must be a string';
			}
		}
		return $errors;
	}

	/**
	 * Derives the legacy exchangetype value from the exchange fields - the
	 * same mapping the Contesting controller applies to the session form, so
	 * exchangetype and exchangefields can never drift apart.
	 *
	 * @param array $fields exchangefields value.
	 * @return string
	 */
	function exchangetype_for_fields($fields) {
		$s = in_array('serial', $fields, true);
		$g = in_array('gridsquare', $fields, true);
		$e = in_array('exchange', $fields, true);
		if ($s && $g && $e) return 'SerialGridExchange';
		if ($s && $g)       return 'Serialgridsquare';
		if ($s && $e)       return 'Serialexchange';
		if ($e && $g)       return 'Exchangegridsquare';
		if ($s)             return 'Serial';
		return 'Exchange';
	}

	/**
	 * Creates a new contest session for the current user.
	 *
	 * @param int $contest_adif_id The id of the contest (contest table)
	 * @param string $session_start The start time of the session.
	 * @param string $session_end The end time of the session.
	 * @param int $station_location The station location (station_id).
	 * @param string $session_notes Notes for the session.
	 * @param bool $return_id If true, returns the inserted session ID instead of a boolean.
	 * @param array $parameter_array Session settings (exchangetype, copyexchangeto, exchangefields, callbook_lookup, custom_name, serial_per_band, serial_scope). Missing keys fall back to defaults.
	 * @return bool True on success, false on failure. If $return_id is true, returns the inserted session ID instead.
	 */
	function create_contest_session($contest_adif_id, $session_start, $session_end, $station_location, $session_notes, $return_id = false, $parameter_array = [], $user_id = null) {
		$user_id = $user_id ?? $this->session->userdata('user_id');

		$settings = $this->build_session_settings($parameter_array);

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
	 * @param array $parameter_array Session settings (exchangetype, copyexchangeto, exchangefields, callbook_lookup, custom_name, serial_per_band, serial_scope). Missing keys fall back to defaults.
	 * @return bool True on success, false on failure.
	 */
	function update_contest_session($contest_session_id, $contest_id, $time_start, $time_end, $station_id, $notes, $parameter_array = [], $user_id = null) {
		if (!clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("Officers must edit contests."));
			redirect('contesting');
		}
		$user_id = $user_id ?? $this->session->userdata('user_id');

		$settings = $this->build_session_settings($parameter_array);

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
	function delete_contest_session($contest_session_id, $delete_qsos = false, $user_id = null) {
		if (!clubaccess_check(9)) {
			$this->session->set_flashdata('error', __("Only clubstation officers can delete."));
			redirect('contesting');
		}
		$user_id = $user_id ?? $this->session->userdata('user_id');

		if ($delete_qsos) {
			$this->load->is_loaded('logbook_model') ?: $this->load->model('logbook_model');
			$query = $this->db->query("SELECT qso_id FROM contest_qsos WHERE contest_session_id = ?", [$contest_session_id]);
			foreach ($query->result() as $row) {
				// pass the resolved user explicitly - Logbook_model::delete()
				// verifies ownership and would silently no-op in a sessionless
				// (API) context otherwise
				$this->logbook_model->delete($row->qso_id, $user_id);
			}
			// contest_qsos rows are cascade-deleted via FK when logbook rows are removed
		} else {
			$this->db->query("DELETE FROM contest_qsos WHERE contest_session_id = ?", [$contest_session_id]);
		}

		$this->db->query("DELETE FROM contest_session WHERE id = ? AND user_id = ?", [$contest_session_id, $user_id]);
		return true;
	}

	/**
	 * Deletes multiple contest sessions for the current user.
	 * Ownership is verified before any QSO links are touched.
	 *
	 * @param array $contest_session_ids IDs of the contest sessions to delete.
	 * @param bool  $delete_qsos Also delete the linked QSOs from the logbook.
	 * @return int Number of sessions deleted.
	 */
	function batch_delete_sessions(array $contest_session_ids, $delete_qsos = false) {
		$user_id = $this->session->userdata('user_id');

		// Only keep sessions actually owned by the current user
		$placeholders = implode(',', array_fill(0, count($contest_session_ids), '?'));
		$query = $this->db->query(
			"SELECT id FROM contest_session WHERE id IN ({$placeholders}) AND user_id = ?",
			array_merge($contest_session_ids, [$user_id])
		);

		$deleted = 0;
		foreach ($query->result() as $row) {
			$this->delete_contest_session($row->id, $delete_qsos);
			$deleted++;
		}
		return $deleted;
	}

	/**
	 * Delete a QSO from a contest. Does not delete QSO from main logbook.
	 *
	 * @param int $qso_id The ID of the QSO.
	 * @param int $contest_session_id The ID of the contest session to delete.
	 * @return bool True on success, false on failure.
	 */
	function unlink_qso($qso_id, $contest_session_id) {

		// Delete associated QSOs (this does not delete the QSOs themselves from the main logbook)
		// Could just use qso_id, but keep contest_session_id to ensure unlink_qso caller knows which contest is being modified
		$sql_delete_qsos = "DELETE FROM contest_qsos WHERE contest_session_id = ? AND qso_id = ?";

		$bindings_qsos = [$contest_session_id, $qso_id];
		$this->db->query($sql_delete_qsos, $bindings_qsos);
		return true;
	}

	/**
	 * Get the contest that a QSO is linked to
	 *
	 * @param int $qso_id The ID of the QSO.
	 * @return int The ID of the contest, otherwise zero
	 */
	function get_linked_contest($qso_id) {

		$sql_get_qsos = "SELECT contest_session_id FROM contest_qsos WHERE qso_id = ?";

		$bindings_qsos = [$qso_id];
		$query = $this->db->query($sql_get_qsos, $bindings_qsos);

        if ($query->num_rows() > 0) {
            return $query->row()->contest_session_id;
        } else {
            return 0;
        }
	}

	/**
	 * Retrieves all QSOs associated with a specific contest session.
	 *
	 * @param int $contest_session_id The ID of the contest session.
	 * @param string $band A valid band
	 * @return array List of QSOs in the session.
	 */
	function get_session_qsos($contest_session_id, $band = "all") {

		$band_constraint = '';
		$bindings = [$contest_session_id];

		if ($band !== 'all') {
			$band_constraint = " AND lb.COL_BAND = ?";
			$bindings[] = $band;
		}
		
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
					lb.COL_OPERATOR AS operator,
					lb.COL_GRIDSQUARE as gridsquare_recv,
					lb.COL_ANT_PATH as antenna_path,
					lb.COL_DXCC as dxcc
				FROM contest_qsos cq
				JOIN contest_session cs ON cs.id = cq.contest_session_id
				JOIN " . $this->config->item('table_name') . " lb ON lb.COL_PRIMARY_KEY = cq.qso_id
				WHERE cq.contest_session_id = ? {$band_constraint}
				ORDER BY cq.id ASC";

		$query = $this->db->query($sql, $bindings);
		return $query->result_array();
	}

	/**
	 * Retrieves QSOs of a session that changed after the client's watermark (delta sync).
	 * Relies on the main table's last_modified column (ON UPDATE CURRENT_TIMESTAMP),
	 * which is bumped path-independently by any UPDATE that changes a value — so this
	 * catches contest edits as well as edits made through the regular logbook.
	 *
	 * The query is session-bounded: it walks contest_qsos via its session index and
	 * joins the logbook on the primary key, so cost scales with session size, not
	 * total logbook size. There is no index on last_modified, but the filter only
	 * runs over the rows already fetched by primary key, so that is fine.
	 *
	 * The watermark is a (second, qso_id) pair, not just a timestamp, because
	 * last_modified is a TIMESTAMP (1s resolution): a bulk import lands many QSOs in the
	 * same second. A plain >= would re-send all of them on every heartbeat. The pair
	 * compares as: strictly later second, OR same second with a higher qso_id. This still
	 * cannot miss an edit (an edit bumps last_modified into a later second), but within
	 * the boundary second it only returns QSOs the client has not seen yet.
	 *
	 * @param int $contest_session_id
	 * @param int $since_ts Unix timestamp in ms; 0 returns all QSOs (initial load)
	 * @param int $since_id Highest qso_id already seen within the since_ts second
	 * @return array List of QSOs including last_modified_ms
	 */
	function get_session_qsos_since($contest_session_id, $since_ts, $since_id = 0) {
		// Compare on the numeric side (UNIX_TIMESTAMP) rather than FROM_UNIXTIME(?):
		// FROM_UNIXTIME(0) returns NULL under non-UTC server time zones, which would make
		// the initial load (since_ts = 0) match no rows. Floor to whole seconds because
		// last_modified is a TIMESTAMP with 1s resolution.
		$since_sec = (int)($since_ts / 1000);
		$bindings = [$contest_session_id, $since_sec, $since_sec, (int)$since_id];
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
					lb.COL_OPERATOR AS operator,
					UNIX_TIMESTAMP(lb.last_modified) * 1000 AS last_modified_ms,
					de.lat AS dxcc_lat,
					de.long AS dxcc_lon
				FROM contest_qsos cq
				JOIN contest_session cs ON cs.id = cq.contest_session_id
				JOIN " . $this->config->item('table_name') . " lb ON lb.COL_PRIMARY_KEY = cq.qso_id
				LEFT JOIN dxcc_entities de ON de.adif = lb.COL_DXCC
				WHERE cq.contest_session_id = ?
				  AND (
				        UNIX_TIMESTAMP(lb.last_modified) > ?
				        OR (UNIX_TIMESTAMP(lb.last_modified) = ? AND cq.qso_id > ?)
				      )
				ORDER BY lb.last_modified ASC, cq.qso_id ASC";

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
	 * Retrieves contest sessions for the API v2 contest resource: full rows
	 * (including settings JSON and timestamps) with catalog names and QSO
	 * counts, optionally filtered by station and id watermark.
	 *
	 * @param int        $user_id
	 * @param int[]|null $station_ids Restrict to these stations (null = all).
	 * @param int        $since_id    Only sessions with id > $since_id.
	 * @param int|null   $session_id  Single session by id (null = all).
	 * @return array List of session rows.
	 */
	function get_sessions_for_user($user_id, $station_ids = null, $since_id = 0, $session_id = null) {
		$bindings = [$user_id, (int) $since_id];
		$constraints = '';

		if (!empty($station_ids)) {
			$constraints .= ' AND cs.station_id IN (' . implode(',', array_fill(0, count($station_ids), '?')) . ')';
			$bindings = array_merge($bindings, array_map('intval', $station_ids));
		}
		if ($session_id !== null) {
			$constraints .= ' AND cs.id = ?';
			$bindings[] = (int) $session_id;
		}

		$sql = "SELECT
					cs.id,
					cs.time_start,
					cs.time_end,
					cs.station_id,
					cs.contest_adif_id,
					cs.comment,
					cs.settings,
					cs.creation_date,
					cs.last_modified,
					c.name AS contest_name,
					c.adifname AS contest_adifname,
					(SELECT COUNT(*) FROM contest_qsos cq
						WHERE cq.contest_session_id = cs.id) AS qso_count
				FROM contest_session cs
				JOIN contest c ON c.id = cs.contest_adif_id
				WHERE cs.user_id = ?
				AND cs.id > ?
				{$constraints}
				ORDER BY cs.time_start DESC, cs.id DESC";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	/**
	 * Filters a list of QSO ids down to the ones owned by the given user -
	 * the batched counterpart to Logbook_model::check_qso_is_accessible(),
	 * with the same station_profile join semantics. With an operator callsign
	 * the result is further limited to QSOs that operator logged (for club
	 * members below officer level).
	 *
	 * @param int[]       $qso_ids
	 * @param int         $user_id
	 * @param string|null $operator_callsign Restrict to this COL_OPERATOR.
	 * @return int[] The subset of $qso_ids the user (and operator) owns.
	 */
	function filter_owned_qso_ids(array $qso_ids, $user_id, $operator_callsign = null) {
		if (empty($qso_ids)) {
			return [];
		}
		$placeholders = implode(',', array_fill(0, count($qso_ids), '?'));
		$sql = "SELECT lb.COL_PRIMARY_KEY AS qso_id
				FROM " . $this->config->item('table_name') . " lb
				JOIN station_profile sp ON sp.station_id = lb.station_id
				WHERE lb.COL_PRIMARY_KEY IN ({$placeholders})
				AND sp.user_id = ?";
		$bindings = array_merge(array_map('intval', $qso_ids), [$user_id]);

		// Restricted club members (below officer level) may only touch QSOs
		// they logged themselves - same rule as clubaccess_check() with a qso_id.
		if ($operator_callsign !== null && $operator_callsign !== '') {
			$sql .= " AND lb.COL_OPERATOR = ?";
			$bindings[] = $operator_callsign;
		}

		$query = $this->db->query($sql, $bindings);
		$owned = [];
		foreach ($query->result() as $row) {
			$owned[] = (int) $row->qso_id;
		}
		return $owned;
	}

	/**
	 * Returns the contest session each of the given QSOs is linked to - the
	 * batched counterpart to get_linked_contest().
	 *
	 * @param int[] $qso_ids
	 * @return array qso_id => contest_session_id (unlinked QSOs are absent).
	 */
	function get_linked_sessions(array $qso_ids) {
		if (empty($qso_ids)) {
			return [];
		}
		$placeholders = implode(',', array_fill(0, count($qso_ids), '?'));
		$query = $this->db->query(
			"SELECT qso_id, contest_session_id FROM contest_qsos
				WHERE qso_id IN ({$placeholders})",
			array_map('intval', $qso_ids)
		);
		$linked = [];
		foreach ($query->result() as $row) {
			$linked[(int) $row->qso_id] = (int) $row->contest_session_id;
		}
		return $linked;
	}

	/**
	 * Links several QSOs to a contest session in one statement - the batched
	 * counterpart to link_qso(). The caller is responsible for ownership and
	 * duplicate checks (see get_linked_sessions()).
	 *
	 * @param int   $contest_session_id
	 * @param int[] $qso_ids
	 * @return int Number of links created.
	 */
	function link_qsos($contest_session_id, array $qso_ids) {
		if (empty($qso_ids)) {
			return 0;
		}
		$rows = [];
		foreach ($qso_ids as $qso_id) {
			$rows[] = [
				'contest_session_id' => (int) $contest_session_id,
				'qso_id'             => (int) $qso_id,
			];
		}
		$this->db->insert_batch('contest_qsos', $rows);
		return count($rows);
	}

	/**
	 * Unlinks several QSOs from a contest session in one statement - the
	 * batched counterpart to unlink_qso(). The QSOs stay in the logbook.
	 *
	 * @param int   $contest_session_id
	 * @param int[] $qso_ids
	 * @return int Number of links removed.
	 */
	function unlink_qsos($contest_session_id, array $qso_ids) {
		if (empty($qso_ids)) {
			return 0;
		}
		$placeholders = implode(',', array_fill(0, count($qso_ids), '?'));
		$this->db->query(
			"DELETE FROM contest_qsos
				WHERE contest_session_id = ? AND qso_id IN ({$placeholders})",
			array_merge([(int) $contest_session_id], array_map('intval', $qso_ids))
		);
		return $this->db->affected_rows();
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
		$sql = "SELECT COUNT(*) AS qso_count FROM contest_qsos WHERE contest_session_id = ?";
		$query = $this->db->query($sql, [$contest_session_id]);
		return (int)$query->row_array()['qso_count'];
	}

	/**
	 * Returns the Export-Format-specific settings sub-array stored in the session's settings JSON.
	 *
	 * @param int $contest_session_id
	 * @param string $exportformat
	 * @return array
	 */
	function get_exportformat_settings($contest_session_id, $exportformat) {
		$user_id = $this->session->userdata('user_id');
		$sql = "SELECT settings FROM contest_session WHERE id = ? AND user_id = ? LIMIT 1";
		$query = $this->db->query($sql, [$contest_session_id, $user_id]);
		$row = $query->row_array();
		if ($row && !empty($row['settings'])) {
			$settings = json_decode($row['settings'], true) ?? [];
			return $settings[$exportformat] ?? [];
		}
		return [];
	}

	/**
	 * Merges exportformat settings into the session's settings JSON without overwriting other fields.
	 *
	 * @param int $contest_session_id
	 * @param string $exportformat
	 * @param array $exportformat_settings
	 * @return bool
	 */
	function save_exportformat_settings($contest_session_id, $exportformat, $exportformat_settings) {
		$user_id = $this->session->userdata('user_id');
		$sql_sel = "SELECT settings FROM contest_session WHERE id = ? AND user_id = ? LIMIT 1";
		$query = $this->db->query($sql_sel, [$contest_session_id, $user_id]);
		$row = $query->row_array();

		$settings = [];
		if ($row && !empty($row['settings'])) {
			$settings = json_decode($row['settings'], true) ?? [];
		}
		$settings[$exportformat] = $exportformat_settings;

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
	 * Returns a sorted array of bands logged in a contest session.
	 * Returns empty array if no qsos
	 *
	 * @param int $contest_session_id
	 * @return array e.g. ["160m", "80m", "70cm"]
	 */
	function get_session_bands($contest_session_id) {
		$user_id = $this->session->userdata('user_id');
		$table   = $this->config->item('table_name');

		$sql = "SELECT DISTINCT bands.band as band
				FROM contest_qsos cq
				JOIN contest_session cs ON cs.id = cq.contest_session_id
				JOIN {$table} ON {$table}.COL_PRIMARY_KEY = cq.qso_id
				JOIN bands ON bands.band = {$table}.COL_BAND
				WHERE cq.contest_session_id = ? AND cs.user_id = ? and bands.band != ?
				ORDER BY bands.ssb ASC";

		$query = $this->db->query($sql, [$contest_session_id, $user_id, "SAT"]);
		$bands   = array_column($query->result_array(), 'band');
		return $bands;
	}


	/**
	 * Returns all QSOs of a contest session as a CI DB result object suitable for Cabrilloformat::qso().
	 * Selects only the columns required for Cabrillo output.
	 *
	 * @param int $contest_session_id
	 * @param string $band
	 * @return CI_DB_result
	 */
	function get_session_qsos_for_exportformat($contest_session_id, $band = "all") {
		$user_id = $this->session->userdata('user_id');
		$table = $this->config->item('table_name');

		$band_constraint = '';
		$bindings = [$contest_session_id, $user_id];

		if ($band !== 'all') {
			$band_constraint = " AND {$table}.COL_BAND = ?";
			$bindings[] = $band;
		}

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
				WHERE cq.contest_session_id = ? AND cs.user_id = ? {$band_constraint}
				ORDER BY {$table}.COL_TIME_ON ASC";

		return $this->db->query($sql, $bindings);
		
	}

	const SERIAL_COUNTER_TTL = 259200; // 72 hours

	private function _load_cache() {
		$this->load->is_loaded('cache') ?: $this->load->driver('cache', [
			'adapter'    => $this->config->item('cache_adapter')    ?? 'file',
			'backup'     => $this->config->item('cache_backup')     ?? 'file',
			'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
		]);
	}

	/**
	 * Builds the cache key of a serial pool.
	 *
	 * @param int $contest_session_id
	 * @param string|null $band Current band, ignored unless the session counts per band.
	 * @param string|null $operator Current operator callsign, ignored unless the session counts per operator.
	 * @return string
	 */
	private function _serial_cache_key($contest_session_id, $band = null, $operator = null) {
		return 'contest_serial_' . (int) $contest_session_id . '_' . strtolower(trim($band ?? '')) . '_' . strtoupper(trim($operator ?? ''));
	}

	/**
	 * Seeds a serial counter from the QSOs already logged in the session.
	 *
	 * @param string $key Cache key of the pool.
	 * @param int $contest_session_id
	 * @param string|null $band Set to scope the pool to a band.
	 * @param string|null $operator Set to scope the pool to an operator.
	 * @return int The highest serial already used, 0 if the session is empty.
	 */
	private function _serial_init($key, $contest_session_id, $band = null, $operator = null) {
		$filter = '';
		$bindings = [$contest_session_id];

		if (!empty($band)) {
			$filter .= ' AND lb.COL_BAND = ?';
			$bindings[] = $band;
		}
		if (!empty($operator)) {
			$filter .= ' AND lb.COL_OPERATOR = ?';
			$bindings[] = $operator;
		}

		$sql = "SELECT MAX(lb.COL_STX) AS max_serial
				FROM contest_qsos cq
				JOIN " . $this->config->item('table_name') . " lb ON lb.COL_PRIMARY_KEY = cq.qso_id
				WHERE cq.contest_session_id = ? {$filter}";

		$row = $this->db->query($sql, $bindings)->row_array();
		$current = (int) ($row['max_serial'] ?? 0);

		$this->cache->save($key, $current, self::SERIAL_COUNTER_TTL);
		return $current;
	}

	/**
	 * Returns the serial the next claim would most likely get, without
	 * consuming it.
	 *
	 * @param int $contest_session_id
	 * @param string|null $band
	 * @param string|null $operator
	 * @return int
	 */
	function serial_peek($contest_session_id, $band = null, $operator = null) {
		$this->_load_cache();

		$key = $this->_serial_cache_key($contest_session_id, $band, $operator);
		$current = $this->cache->get($key);

		if ($current === FALSE) {
			$current = $this->_serial_init($key, $contest_session_id, $band, $operator);
		}

		return (int) $current + 1;
	}

	/**
	 * Hands out the next serial number and marks it as used.
	 *
	 * @param int $contest_session_id
	 * @param string|null $band
	 * @param string|null $operator
	 * @return int|false The claimed serial, or false if the cache cannot count.
	 */
	function serial_claim($contest_session_id, $band = null, $operator = null) {
		$this->_load_cache();

		$key = $this->_serial_cache_key($contest_session_id, $band, $operator);
		$current = $this->cache->get($key);

		if ($current === FALSE) {
			$current = $this->_serial_init($key, $contest_session_id, $band, $operator);
		}

		$next = (int) $current + 1;

		if (!$this->cache->save($key, $next, self::SERIAL_COUNTER_TTL)) {
			log_message('error', 'Contest serial counter could not be stored for key ' . $key . ' (cache adapter: ' . $this->cache->get_loaded_driver() . ')');
			return false;
		}

		return $next;
	}

	/**
	 * Gives a claimed serial back when it was never logged.
	 *
	 * @param int $contest_session_id
	 * @param string|null $band
	 * @param string|null $operator
	 * @param int $serial The serial being given back.
	 * @return bool True if the counter was rolled back.
	 */
	function serial_release($contest_session_id, $band = null, $operator = null, $serial = 0) {
		$serial = (int) $serial;
		if ($serial < 1) {
			return false;
		}

		$this->_load_cache();

		$key = $this->_serial_cache_key($contest_session_id, $band, $operator);
		$current = $this->cache->get($key);

		if ($current === FALSE || (int) $current !== $serial) {
			return false;
		}

		return (bool) $this->cache->save($key, $serial - 1, self::SERIAL_COUNTER_TTL);
	}

	/**
	 * Raises the counter if a QSO was logged with a serial above it.
	 *
	 * @param int $contest_session_id
	 * @param string|null $band
	 * @param string|null $operator
	 * @param int $serial The serial that was actually logged.
	 * @return void
	 */
	function serial_bump($contest_session_id, $band = null, $operator = null, $serial = 0) {
		$serial = (int) $serial;
		if ($serial < 1) {
			return;
		}

		$this->_load_cache();

		$key = $this->_serial_cache_key($contest_session_id, $band, $operator);
		$current = $this->cache->get($key);

		if ($current !== FALSE && (int) $current >= $serial) {
			return;
		}

		$this->cache->save($key, $serial, self::SERIAL_COUNTER_TTL);
	}
}
