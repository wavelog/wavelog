<?php

use Wavelog\Dxcc\Dxcc;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dbtools_model extends CI_Model {
	public function check_missing_continent($stationid) {
		// get all records with no COL_CONT
		$this->db->trans_start();
		$sql = "UPDATE " . $this->config->item('table_name') . "
			JOIN dxcc_entities ON " . $this->config->item('table_name') . ".col_dxcc = dxcc_entities.adif
			JOIN station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
			SET col_cont = dxcc_entities.cont
			WHERE (COALESCE(" . $this->config->item('table_name') . ".col_cont, '') = ''  or " . $this->config->item('table_name') . ".col_cont not in ('AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'))
			AND station_profile.user_id = ?
			AND col_dxcc != 0";

		$bindings[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " AND " . $this->config->item('table_name') . ".station_id = ?";
			$bindings[] = $stationid;
		}

		$query = $this->db->query($sql, $bindings);
		$result = $this->db->affected_rows();
		$this->db->trans_complete();

		return $result;
	}

	public function update_distances_batch($stationid) {
		ini_set('memory_limit', '-1');

		$sql = "SELECT COL_ANT_PATH, COL_DISTANCE, COL_PRIMARY_KEY, station_profile.station_gridsquare, COL_GRIDSQUARE, COL_VUCC_GRIDS FROM " . $this->config->item('table_name') . "
			JOIN station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
			WHERE COL_GRIDSQUARE is NOT NULL
			AND COL_GRIDSQUARE != ''
			AND station_profile.user_id = ?
			AND (COL_DISTANCE = '' or COL_DISTANCE is NULL)
			and COL_GRIDSQUARE != station_gridsquare";

		$bindings[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " AND " . $this->config->item('table_name') . ".station_id = ?";
			$bindings[] = $stationid;
		}

		$query = $this->db->query($sql, $bindings);

		$recordcount = $query->num_rows();

		$count = 0;

		if ($recordcount > 0) {
			$this->load->library('Qra');

			$updates = [];
			foreach ($query->result() as $row) {
				$distance = $this->qra->distance(
					$row->station_gridsquare,
					$row->COL_GRIDSQUARE,
					'K',
					$row->COL_ANT_PATH ?? null
				);

				if ($distance != 0) {
					$updates[] = [
						'COL_PRIMARY_KEY' => $row->COL_PRIMARY_KEY,
						'COL_DISTANCE' => $distance,
					];
					$count++;
				}

			}

			if (!empty($updates)) {
				$this->db->update_batch($this->config->item('table_name'), $updates, 'COL_PRIMARY_KEY');
			}
		}

		return $count;
	}

	public function runCheckDb($type, $stationid = null) {
		switch ($type) {
			case 'checkdistance':
				return $this->check_missing_distance($stationid);
			case 'checkcontinent':
				return $this->check_qsos_missing_continent($stationid);
			case 'checkdxcc':
				return $this->check_dxcc($stationid);
			case 'checkstate':
				return $this->check_missing_state($stationid);
			case 'checkincorrectgridsquares':
				return $this->getIncorrectGridsquares($stationid);
			case 'checkincorrectcqzones':
				return $this->getIncorrectCqZones($stationid);
			case 'checkincorrectituzones':
				return $this->getIncorrectItuZones($stationid);
			case 'checkiota':
				return $this->checkIota($stationid);
			default:
				return null;
		}
	}
	/*
	 * Get list of QSOs with gridsquares that do not match the gridsquares listed for the DXCC.
	 * The data comes from the TQSL published Gridsquare list for DXCCs.
	 */
	public function getIncorrectGridsquares($stationid) {
		$sqlcheck = "select count(*) as count from vuccgrids";;
		$querycheck = $this->db->query($sqlcheck);
		$rowcheck = $querycheck->row();
		if ($rowcheck->count == 0) {
			return ['status' => 'error', 'message' => __("VuccGrids table is empty. Please import the VUCC grids data first.")];
		}

		$sql = "select col_primary_key, col_sat_name, col_time_on, col_call, col_band, col_gridsquare, col_dxcc, col_country, station_profile_name, col_lotw_qsl_rcvd, col_mode, col_submode,
			(
			select group_concat(distinct gridsquare order by gridsquare separator ', ')
			from vuccgrids
			where adif = thcv.col_dxcc
				order by gridsquare asc
			) as correctgridsquare
		from " . $this->config->item('table_name') . " thcv
		join station_profile on thcv.station_id = station_profile.station_id
		join dxcc_entities on dxcc_entities.adif = thcv.COL_DXCC
		where station_profile.user_id = ?
		and thcv.col_dxcc > 0
		and not exists (
			select 1
			from vuccgrids
			where adif = thcv.col_dxcc
			and gridsquare = substr(thcv.col_gridsquare, 1, 4)
		)
		and exists (select 1 from vuccgrids where adif = thcv.col_dxcc)
		and thcv.col_dxcc > 0
		and thcv.col_gridsquare is not null
		and thcv.col_gridsquare <> ''";

		$bindings[] = [$this->session->userdata('user_id')];

		if ($stationid != 'All') {
			$sql .= " and thcv.station_id = ?";
			$bindings[] = $stationid;
		}

		$sql .= " order by station_profile_name, col_time_on desc";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	public function check_qsos_missing_continent($stationid) {
		$sql = "select count(*) as count from " . $this->config->item('table_name') . " thcv
			join station_profile on thcv.station_id = station_profile.station_id
			where station_profile.user_id = ?
			and (coalesce(thcv.col_cont, '') = '' or thcv.col_cont not in ('AF', 'AN', 'AS', 'EU', 'NA', 'OC', 'SA'))
			and thcv.col_dxcc != 0";

		$bindings[] = [$this->session->userdata('user_id')];

		if ($stationid != 'All') {
			$sql .= " and thcv.station_id = ?";
			$bindings[] = $stationid;
		}

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	public function check_missing_distance($stationid) {
		$sql = "select count(*) as count from " . $this->config->item('table_name') . " thcv
		join station_profile on thcv.station_id = station_profile.station_id
		where station_profile.user_id = ?
		AND (thcv.COL_DISTANCE = '' or thcv.COL_DISTANCE is NULL)
		and thcv.COL_GRIDSQUARE != station_profile.station_gridsquare
		and thcv.COL_GRIDSQUARE is NOT NULL
		and thcv.COL_GRIDSQUARE != ''";

		$bindings[] = [$this->session->userdata('user_id')];

		if ($stationid != 'All') {
			$sql .= " and thcv.station_id = ?";
			$bindings[] = $stationid;
		}


		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	public function check_missing_state($stationid) {
		$this->load->library('Geojson');
		$supported_dxcc_list = $this->geojson->getSupportedDxccs();
		$supported_dxcc_array = array_keys($supported_dxcc_list);

		$sql = "select count(*) as count, col_dxcc, dxcc_entities.name as dxcc_name, dxcc_entities.prefix from " . $this->config->item('table_name') . " thcv
		join station_profile on thcv.station_id = station_profile.station_id
		join dxcc_entities on thcv.col_dxcc = dxcc_entities.adif
		where station_profile.user_id = ? and coalesce(thcv.col_state, '') = ''
		and thcv.col_dxcc in (" . implode(',', array_map('intval', $supported_dxcc_array)) . ")
		and length(thcv.col_gridsquare) >= 6";

		$bindings[] = [$this->session->userdata('user_id')];

		if ($stationid != 'All') {
			$sql .= " and thcv.station_id = ?";
			$bindings[] = $stationid;
		}

		$sql .= " group by col_dxcc, dxcc_entities.name, dxcc_entities.prefix
		order by dxcc_entities.prefix";


		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	/**
	 * Fix state for a batch of QSOs using GeoJSON lookup
	 *
	 * @param int $dxcc DXCC entity number for which to fix states
	 * @return array Result array with success, dxcc_name, dxcc_number, state_code, skipped
	 */
	function fixStateBatch($dxcc, $stationid) {
		$this->load->library('Geojson', $dxcc);

		// Get QSO data
		$sql = "SELECT COL_PRIMARY_KEY, COL_CALL, COL_GRIDSQUARE, COL_DXCC, COL_STATE, d.name as dxcc_name, station_profile.station_profile_name
				FROM " . $this->config->item('table_name') . " qsos
				JOIN station_profile ON qsos.station_id = station_profile.station_id
				LEFT JOIN dxcc_entities d ON qsos.COL_DXCC = d.adif
				WHERE qsos.COL_DXCC = ?
				AND station_profile.user_id = ?
				AND (qsos.COL_STATE IS NULL OR qsos.COL_STATE = '')
				AND LENGTH(COALESCE(qsos.COL_GRIDSQUARE, '')) >= 6";

		$bindings[] = $dxcc;
		$bindings[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " and qsos.station_id = ?";
			$bindings[] = $stationid;
		}

		$query = $this->db->query($sql, $bindings);

		if ($query->num_rows() === 0) {
			return [
				'success' => false,
				'skipped' => true,
				'count' => 0,
				'reason' => 'QSOs not found'
			];
		}

		$results = [];
		$batch_updates = [];

		foreach ($query->result() as $qso) {
			$result = $this->fixStateDxcc($qso);

			if ($result['success']) {
				// Prepare data for batch update
				$batch_updates[] = [
					'COL_PRIMARY_KEY' => $qso->COL_PRIMARY_KEY,
					'COL_STATE' => $result['state_code']
				];
			} else {
				$result['station_profile_name'] = $qso->station_profile_name;
				$result['id'] = $qso->COL_PRIMARY_KEY;
				$result['gridsquare'] = $qso->COL_GRIDSQUARE;
				$results[] = $result;
			}
		}

		// Perform batch update if there are any updates
		$count = 0;
		if (!empty($batch_updates)) {
			$this->db->update_batch($this->config->item('table_name'), $batch_updates, 'COL_PRIMARY_KEY');
			$count = count($batch_updates);
		}

		$results['count'] = $count;

		return $results;
	}

	/**
	 * Fix state for every DXCC that has QSOs eligible for a state fix.
	 *
	 * Reuses check_missing_state() to determine the candidate DXCCs (so the
	 * set of DXCCs fixed matches exactly what was shown in the check result),
	 * then runs fixStateBatch() for each one, aggregating the outcome.
	 *
	 * @param string $stationid Station id, or 'All'
	 * @return array Aggregated result: total_updated, dxccs_processed,
	 *               dxcc_counts[] (per-DXCC name + count) and failures[]
	 */
	function fixStateAll($stationid) {
		$aggregated = [
			'total_updated' => 0,
			'dxccs_processed' => 0,
			'dxcc_counts' => [],
			'failures' => [],
		];

		$candidates = $this->check_missing_state($stationid);

		foreach ($candidates as $candidate) {
			$dxcc = $candidate->col_dxcc;
			$dxcc_name = isset($candidate->dxcc_name) ? ucwords(strtolower($candidate->dxcc_name), "- (/") : '';

			$result = $this->fixStateBatch($dxcc, $stationid);

			$aggregated['dxccs_processed']++;

			$count = isset($result['count']) ? (int)$result['count'] : 0;
			$aggregated['total_updated'] += $count;
			$aggregated['dxcc_counts'][] = [
				'name' => $dxcc_name,
				'count' => $count,
			];

			// fixStateBatch() returns numeric entries for per-QSO failures
			// (each an array containing 'id') plus a 'count' scalar.
			if (is_array($result)) {
				foreach ($result as $value) {
					if (is_array($value) && isset($value['id'])) {
						$aggregated['failures'][] = $value;
					}
				}
			}
		}

		return $aggregated;
	}

	/**
	 * Fix state for a batch of QSOs, based on the DXCC
	 * Note: This now only validates and prepares data
	 *
	 * @param object $qso QSO object
	 * @return array Result array with success, dxcc_name, dxcc_number, state_code, skipped
	 */
	function fixStateDxcc($qso) {
		$callsign = $qso->COL_CALL ?? 'Unknown';
		$dxcc = (int)$qso->COL_DXCC;
		$gridsquare = $qso->COL_GRIDSQUARE;
		$state = $qso->COL_STATE ?? '';
		$dxcc_name = $qso->dxcc_name ?? 'Unknown';

		// Find state from gridsquare
		$state = $this->geojson->findStateFromGridsquare($gridsquare, $dxcc);

		if ($state === null || !isset($state['code'])) {
			return [
				'success' => false,
				'skipped' => false,
				'callsign' => $callsign,
				'dxcc_number' => $dxcc,
				'dxcc_name' => $dxcc_name,
				'gridsquare' => $gridsquare,
				'reason' => 'State not found in GeoJSON'
			];
		}

		// Return success with state info
		return [
			'success' => true,
			'skipped' => false,
			'callsign' => $callsign,
			'dxcc_number' => $dxcc,
			'dxcc_name' => $dxcc_name,
			'state_code' => $state['code'],
			'state_name' => $state['name'] ?? null
		];
	}

	function getStateListQsos($dxcc, $stationid) {
		$sql = "SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_state, col_gridsquare, d.name as dxcc_name, station_profile.station_profile_name FROM " . $this->config->item('table_name') . " qsos
				JOIN station_profile ON qsos.station_id = station_profile.station_id
				LEFT JOIN dxcc_entities d ON qsos.COL_DXCC = d.adif
				WHERE qsos.COL_DXCC = ? AND station_profile.user_id = ?
				AND (qsos.COL_STATE IS NULL OR qsos.COL_STATE = '')
				AND LENGTH(COALESCE(qsos.COL_GRIDSQUARE, '')) >= 6";

		$bindings[] = $dxcc;
		$bindings[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " and qsos.station_id = ?";
			$bindings[] = $stationid;
		}

		$sql .= " ORDER BY COL_TIME_ON DESC";

		$query = $this->db->query($sql, $bindings);

		return $query->result();
	}

	/*
		Another function moved from update to the advanced logbook, to be used in the dbtools section.
		It did not have filter on user or location.
		The callbook lookup for QSOs with missing grid square is triggered from the dbtools UI one QSO
		at a time (like the callbook lookup in the advanced logbook), so progress can be shown and the
		run can be cancelled by the user.
	*/
	public function lookup_missing_grid($qsoID) {
		$sql = "SELECT qsos.col_primary_key, qsos.col_call FROM " . $this->config->item('table_name') . " qsos
				JOIN station_profile ON qsos.station_id = station_profile.station_id
				WHERE qsos.col_primary_key = ? AND station_profile.user_id = ?
				AND (qsos.COL_GRIDSQUARE IS NULL OR qsos.COL_GRIDSQUARE = '')
				AND (qsos.COL_VUCC_GRIDS IS NULL OR qsos.COL_VUCC_GRIDS = '')";

		$bindings[] = $qsoID;
		$bindings[] = $this->session->userdata('user_id');

		$query = $this->db->query($sql, $bindings);
		$row = $query->row();

		// QSO does not exist, belongs to another user, or is no longer missing a gridsquare
		if ($row === null) {
			return ['status' => 'skipped'];
		}

		if (!$this->load->is_loaded('callbook')) {
			$this->load->library('callbook');
		}

		$callbook = $this->callbook->getCallbookData($row->col_call);

		if (isset($callbook)) {
			if (isset($callbook['error'])) {
				log_message('error', "Error: " . $callbook['error']);
				return ['status' => 'error', 'message' => $callbook['error']];
			}

			if (isset($callbook['gridsquare']) && $callbook['gridsquare'] != '') {
				$this->db->set('COL_GRIDSQUARE', $callbook['gridsquare']);
				$this->db->where('COL_PRIMARY_KEY', $qsoID);
				$this->db->update($this->config->item('table_name'));

				return ['status' => 'updated', 'gridsquare' => $callbook['gridsquare']];
			}
		}

		return ['status' => 'notfound'];
	}

	public function getMissingGridQsos($stationid) {
		$sql = "SELECT col_primary_key, col_call, col_time_on, col_mode, col_submode, col_band, col_state, col_gridsquare, col_qsl_rcvd, col_lotw_qsl_rcvd, col_eqsl_qsl_rcvd, station_profile.station_profile_name FROM " . $this->config->item('table_name') . " qsos
				JOIN station_profile ON qsos.station_id = station_profile.station_id
				WHERE station_profile.user_id = ?
				AND (qsos.COL_GRIDSQUARE IS NULL OR qsos.COL_GRIDSQUARE = '')
				AND (qsos.COL_VUCC_GRIDS IS NULL OR qsos.COL_VUCC_GRIDS = '')";

		$params[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " and qsos.station_id = ?";
			$params[] = $stationid;
		}

		$sql .= " ORDER BY COL_TIME_ON DESC";

		$query = $this->db->query($sql, $params);

		return $query->result();
	}

	/*
		Check all QSOs DXCC against current DXCC database
	*/
	public function check_dxcc($stationid) {
		ini_set('memory_limit', '-1');

		$i = 0;
		$result = array();

		$callarray = $this->getQsos($stationid);

		// Starting clock time in seconds
		$start_time = microtime(true);
		$dxccobj = new Dxcc();

		foreach ($callarray->result() as $call) {

            $i++;
			$dxcc = $dxccobj->dxcc_lookup($call->col_call, $call->date);

            $dxcc['adif'] = (isset($dxcc['adif'])) ? $dxcc['adif'] : 0;
            $dxcc['entity'] = (isset($dxcc['entity'])) ? $dxcc['entity'] : 'None';

            if (($call->col_dxcc ?? 'Unset') != $dxcc['adif']) {
                $result[] = array(
                                'callsign'          => $call->col_call,
								'qso_date'          => $call->date,
								'mode'              => isset($call->col_mode) ? $call->col_mode : '',
								'submode'           => isset($call->col_submode) ? $call->col_submode : '',
								'band'              => isset($call->col_band) ? $call->col_band : '',
								'sat_name'          => isset($call->col_sat_name) ? $call->col_sat_name : '',
								'lotw_qsl_rcvd'     => isset($call->col_lotw_qsl_rcvd) ? $call->col_lotw_qsl_rcvd : '',
								'station_profile'   => $call->station_profile_name,
                                'existing_dxcc'     => $call->col_country,
                                'existing_adif'     => $call->col_dxcc,
                                'result_country'    => ucwords(strtolower($dxcc['entity']), "- (/"),
                                'result_adif'       => $dxcc['adif'],
								'id' 			    => $call->col_primary_key,
                            );
            }
        }

        // End clock time in seconds
        $end_time = microtime(true);

        // Calculate script execution time
        $execution_time = ($end_time - $start_time);

        $data['execution_time'] = $execution_time;
        $data['calls_tested'] = $i;
		$data['result'] = $result;

		return $data;
	}

	function getQsos($stationid) {
		$sql = 'select distinct col_country, col_sat_name, col_call, col_dxcc, date(col_time_on) date, col_mode, col_submode, col_band, col_lotw_qsl_rcvd, station_profile.station_profile_name, col_primary_key
			from ' . $this->config->item('table_name') . '
			join station_profile on ' . $this->config->item('table_name') . '.station_id = station_profile.station_id
			where station_profile.user_id = ?';

		$params[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " and " . $this->config->item('table_name') . ".station_id = ?";
			$params[] = $stationid;
		}

		$sql .= ' order by station_profile.station_profile_name asc, date desc';

        $query = $this->db->query($sql, $params);

		return $query;
	}

	function fixDxccSelected($ids) {
		$sql = "select COL_PRIMARY_KEY, COL_CALL, COL_TIME_ON, COL_TIME_OFF, station_profile.station_profile_name from " . $this->config->item('table_name') .
		" join station_profile on " . $this->config->item('table_name') . ".station_id = station_profile.station_id
		where station_profile.user_id = ? and " . $this->config->item('table_name') . ".col_primary_key in ?";

		$r = $this->db->query($sql, array($this->session->userdata('user_id'), json_decode($ids, true)));

		$count = 0;
		$dxccobj = new Dxcc();

		if ($r->num_rows() > 0) { //query dxcc_prefixes
			$sql = "update " . $this->config->item('table_name') . " set COL_COUNTRY = ?, COL_DXCC = ? where COL_PRIMARY_KEY = ?";
			$q = $this->db->conn_id->prepare($sql);
			foreach ($r->result_array() as $row) {
				$qso_date = $row['COL_TIME_OFF'] == '' ? $row['COL_TIME_ON'] : $row['COL_TIME_OFF'];
				$qso_date = date("Y-m-d", strtotime($qso_date));
				$dxcc = $dxccobj->dxcc_lookup($row['COL_CALL'], $qso_date);
				$dxcc['adif'] = (isset($dxcc['adif'])) ? $dxcc['adif'] : 0;
				$dxcc['entity'] = (isset($dxcc['entity'])) ? $dxcc['entity'] : 'None';
				if ($dxcc['adif'] != 'Not Found') {
					$q->execute(array(addslashes(ucwords(strtolower($dxcc['entity']), "- (/")), $dxcc['adif'], $row['COL_PRIMARY_KEY']));
					$count++;
				}
			}
		}

		$result['count'] = $count;
		return $result;
	}

	function getIncorrectCqZones($stationid) {
		if(!clubaccess_check(9)) return;

		$sql = "select *, (select group_concat(distinct cqzone order by cqzone separator ', ') from dxcc_master where countrycode = thcv.col_dxcc and cqzone <> '' order by cqzone asc) as correctcqzone
		from " . $this->config->item('table_name') . " thcv
		join station_profile on thcv.station_id = station_profile.station_id
		where station_profile.user_id = ?
		and not exists (select 1 from dxcc_master where countrycode = thcv.col_dxcc and cqzone = col_cqz) and col_dxcc > 0
		";

		$params[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " and thcv.station_id = ?";
			$params[] = $stationid;
		}

		$sql .= " order by station_profile.station_profile_name, thcv.col_time_on desc
		limit 5000";

		$query = $this->db->query($sql, $params);

		return $query->result();
	}

	function getIncorrectItuZones($stationid) {
		if(!clubaccess_check(9)) return;

		$sql = "select *, (select group_concat(distinct ituzone order by ituzone separator ', ') from dxcc_master where countrycode = thcv.col_dxcc and ituzone <> '' order by ituzone asc) as correctituzone
		from " . $this->config->item('table_name') . " thcv
		join station_profile on thcv.station_id = station_profile.station_id
		where station_profile.user_id = ?
		and not exists (select 1 from dxcc_master where countrycode = thcv.col_dxcc and ituzone = col_ituz) and col_dxcc > 0
		";

		$params[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " and thcv.station_id = ?";
			$params[] = $stationid;
		}

		$sql .= " order by station_profile.station_profile_name, thcv.col_time_on desc
		limit 5000";

		$query = $this->db->query($sql, $params);

		return $query->result();
	}

	public function checkIota($stationid) {
		$result1 = $this->checkSingleIota($stationid);
		$result2 = $this->checkMultiDxccIota($stationid);

		$merged = array_merge($result1, $result2);

		// Sort merged results by station_profile_name, then col_time_on DESC
		usort($merged, function($a, $b) {
			$stationCompare = strcmp($a->station_profile_name, $b->station_profile_name);
			if ($stationCompare !== 0) {
				return $stationCompare;
			}
			// If same station, sort by time_on descending (newest first)
			return strtotime($b->col_time_on) - strtotime($a->col_time_on);
		});

		return $merged;
	}

	/*
	 * Get list of QSOs with IOTA that do not match the IOTAs listed for the DXCC.
	 * Some islands are excluded as they can be in multiple DXCCs.
	 *
	 * These are excluded by not having a dxccid or dxccid = 0
	 *
	 */
	public function checkSingleIota($stationid) {
		$sql = "select col_primary_key, col_time_on, col_call, col_sat_name, col_band, col_gridsquare, col_dxcc, col_country, station_profile_name, col_lotw_qsl_rcvd, col_mode, col_submode, col_iota, iotadxcc.name as correctdxcc
		FROM  " . $this->config->item('table_name') . "  thcv
		JOIN station_profile on thcv.station_id = station_profile.station_id
		JOIN dxcc_entities on dxcc_entities.adif = thcv.COL_DXCC
		JOIN iota on thcv.col_iota = iota.tag
		JOIN dxcc_entities iotadxcc on iota.dxccid = iotadxcc.adif
		WHERE station_profile.user_id = ?
		AND thcv.col_dxcc > 0
		AND thcv.col_dxcc <> iota.dxccid
		AND iota.dxccid > 0";

		$bindings[] = $this->session->userdata('user_id');

		if ($stationid != 'All') {
			$sql .= " AND thcv.station_id = ?";
			$bindings[] = $stationid;
		}

		$sql .= " order by station_profile_name, col_time_on desc";

		$query = $this->db->query($sql, $bindings);
		return $query->result();
	}

	/*
	 * Get list of QSOs with multi-DXCC IOTA tags where the DXCC prefix doesn't match
	 * any of the valid prefixes for that IOTA.
	 */
	public function checkMultiDxccIota($stationid) {
		// Define IOTA tags that span multiple DXCCs with their valid prefixes
		$multiDxccIotas = [
			'AS-004' => [215, 283], // 5B4, ZC4
			'EU-053' => [167, 284], // OJ0, SM
			'EU-115' => [245, 265], // EI, GI
			'EU-117' => [151, 224], // R1M, OH
			'EU-129' => [230, 269], // DL, SP
			'EU-191' => [275, 288], // YO, UR
			'EU-192' => [284, 224], // SM, OH
			'NA-015' => [70, 105], // CO, KG4
			'NA-096' => [72, 78], // HH, HI
			'NA-105' => [213, 518], // FS, PJ7
			'OC-034' => [163, 327], // P2, YB
			'OC-088' => [46, 327, 345], // 9M6, V8, YB
			'OC-148' => [327, 511], // YB, 4W
			'SA-008' => [100, 112] // LU, CE
		];

		$allResults = [];

		foreach ($multiDxccIotas as $iotaTag => $adifList) {
			$bindings = []; // Reset bindings for each iteration

			// Build IN clause for SQL
			$adifListStr = implode(',', $adifList);

			$sql = "SELECT thcv.col_primary_key, thcv.col_sat_name, thcv.col_time_on, thcv.col_call, thcv.col_band, thcv.col_gridsquare,
					thcv.col_dxcc, thcv.col_country, station_profile.station_profile_name, thcv.col_lotw_qsl_rcvd,
					thcv.col_mode, thcv.col_submode, thcv.col_iota,
					(
						SELECT GROUP_CONCAT(DISTINCT d.name ORDER BY d.name SEPARATOR ', ')
						FROM dxcc_entities d
						WHERE d.adif IN ($adifListStr)
					) as correctdxcc
					FROM " . $this->config->item('table_name') . " thcv
					JOIN station_profile ON thcv.station_id = station_profile.station_id
					JOIN dxcc_entities ON dxcc_entities.adif = thcv.COL_DXCC
					JOIN iota ON thcv.col_iota = iota.tag
					WHERE station_profile.user_id = ?
					AND thcv.col_iota = ?
					AND dxcc_entities.adif NOT IN ($adifListStr)";

			$bindings[] = $this->session->userdata('user_id');
			$bindings[] = $iotaTag;

			if ($stationid != 'All') {
				$sql .= " AND thcv.station_id = ?";
				$bindings[] = $stationid;
			}

			$sql .= " ORDER BY station_profile_name, col_time_on DESC";

			$query = $this->db->query($sql, $bindings);
			$results = $query->result();

			if (!empty($results)) {
				$allResults = array_merge($allResults, $results);
			}
		}

		// Sort the merged results by station_profile_name, then col_time_on DESC
		usort($allResults, function($a, $b) {
			$stationCompare = strcmp($a->station_profile_name, $b->station_profile_name);
			if ($stationCompare !== 0) {
				return $stationCompare;
			}
			// If same station, sort by time_on descending (newest first)
			return strtotime($b->col_time_on) - strtotime($a->col_time_on);
		});

		return $allResults;
	}

	function getGridsForDxcc($dxcc) {
		$sql = "select group_concat(distinct gridsquare order by gridsquare separator ', ') grids
		from vuccgrids
		where adif = ?";

		$query = $this->db->query($sql, array($dxcc));
		$row = $query->row();

		return $row->grids;
	}

}
