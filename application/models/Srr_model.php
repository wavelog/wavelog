<?php

class Srr_model extends CI_Model {

	private $api_url = 'https://award.srr.ru/api/v1';

	// Number of QSOs sent in the first request of an upload
	private $probe_size = 10;

	// Number of requests sent at the same time, 0 = all at once
	private $max_parallel = 0;

	// Minutes after the upload until QSOs in the SRR inbox are verified
	private $verify_after = 15;

	/**
	 * Returns the stored SRR API key of a user
	 *
	 * @param int $user_id User id
	 * @return string The key, empty if none is stored
	 */
	function srr_key($user_id) {
		$this->load->model('user_options_model');
		$row = $this->user_options_model->get_options('srr', array('option_name'=>'srr_key','option_key'=>'key'), $user_id)->row();
		return $row->option_value ?? '';
	}

	/**
	 * Returns all users which have stored an SRR API key
	 *
	 * @return array Rows with user_id and option_value (the key)
	 */
	function srr_users() {
		$sql = "SELECT user_id, option_value FROM user_options WHERE option_type = 'srr' AND option_name = 'srr_key' AND option_key = 'key' AND option_value <> ''";
		return $this->db->query($sql)->result();
	}

	/**
	 * Stores the SRR API key for the logged in user
	 *
	 * @param string $key SRR API key
	 * @return void
	 */
	function store_key($key) {
		$this->load->model('user_options_model');
		$this->user_options_model->set_option('srr', 'srr_key', array('key'=>$key));
	}

	/**
	 * Deletes the SRR API key of the logged in user
	 *
	 * @return void
	 */
	function delete_key() {
		$this->load->model('user_options_model');
		$this->user_options_model->del_option('srr', 'srr_key',array('option_key' => 'key'));
	}

	/**
	 * Checks the key against SRR (/me)
	 *
	 * @param string $key SRR API key
	 * @return object|false The SRR user incl. callsigns, false if the key is invalid
	 */
	function get_srr_me($key) {
		if (($key ?? '') == '') {
			return false;
		}
		$result = $this->srr_request('GET', '/me', $key);
		if (($result['httpcode'] == 200) && (($result['data']->result ?? '') == 'OK')) {
			return $result['data']->data;
		}
		return false;
	}

	/**
	 * Returns the list of RDA codes from the SRR info endpoint.
	 * The list is cached as long as SRR allows it (cache_ttl).
	 *
	 * @param string $key SRR API key
	 * @return array|false Uppercase RDA codes, false if SRR could not be reached
	 */
	function get_srr_rda($key) {
		$this->load->is_loaded('cache') ?: $this->load->driver('cache', [
			'adapter' => $this->config->item('cache_adapter') ?? 'file',
			'backup' => $this->config->item('cache_backup') ?? 'file',
			'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
		]);

		$rda = $this->cache->get('srr_rda');
		if (is_array($rda) && count($rda) > 0) {
			return $rda;
		}

		$result = $this->srr_request('GET', '/info', $key);
		if (($result['httpcode'] != 200) || (($result['data']->result ?? '') != 'OK')) {
			return false;
		}

		$rda = array();
		foreach (($result['data']->data->rda ?? array()) as $item) {
			$rda[] = strtoupper($item->code);
		}
		if (count($rda) > 0) {
			$this->cache->save('srr_rda', $rda, (int)($result['data']->data->cache_ttl ?? 86400));
		}
		return $rda;
	}

	/**
	 * Checks if an RDA code has the format XX-00 and is known by SRR
	 *
	 * @param string $rda RDA code
	 * @param array $rda_list RDA codes from get_srr_rda()
	 * @return bool
	 */
	function rda_valid($rda, $rda_list) {
		return (preg_match('/^[A-Z]{2}-\d{2}$/', $rda) === 1) && in_array($rda, $rda_list, true);
	}

	/**
	 * Returns the station profiles of a user with the counts of modified,
	 * not uploaded and uploaded QSOs
	 *
	 * @param int|null $user_id User id, the logged in user if null
	 * @return CI_DB_result
	 */
	function stations_with_srr($user_id = null) {
		if ($user_id == null) {
			$user_id = $this->session->userdata('user_id');
		}
		$bindings=[];
		$sql = "SELECT station_profile.station_id, station_profile.station_profile_name, station_profile.station_callsign, modc.modcount, notc.notcount, totc.totcount
			FROM station_profile
			LEFT OUTER JOIN (
				SELECT count(*) modcount, station_id
				FROM ". $this->config->item('table_name') .
				" WHERE COL_SRR_QSL_SENT = 'M'
				group by station_id
		) as modc on station_profile.station_id = modc.station_id
		LEFT OUTER JOIN (
			SELECT count(*) notcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE (coalesce(COL_SRR_QSL_SENT, '') = ''
			or COL_SRR_QSL_SENT = 'N')
			group by station_id
		) as notc on station_profile.station_id = notc.station_id
		LEFT OUTER JOIN (
			SELECT count(*) totcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE COL_SRR_QSL_SENT = 'Y'
			group by station_id
		) as totc on station_profile.station_id = totc.station_id
		WHERE station_profile.user_id = ?
		ORDER BY station_profile.station_callsign, station_profile.station_profile_name";
		$bindings[]=$user_id;
		$query = $this->db->query($sql, $bindings);

		return $query;
	}

	/**
	 * Uploads all not yet uploaded QSOs of a station profile to SRR.
	 *
	 * A first request with a few QSOs checks that SRR accepts them, then all other
	 * QSOs are sent in requests of 100 QSOs at the same time. QSOs which SRR did not
	 * process yet (inbox) are stored and verified later by verify_pending().
	 *
	 * QSOs on 2m and above need a PROP_MODE. If there are QSOs without one,
	 * nothing is uploaded and the QSOs are returned (status "propmode") so the
	 * user can decide. The RDA district of each QSO (MY_CNTY) is checked against
	 * the SRR RDA list. If there are QSOs without a valid RDA, nothing is uploaded
	 * and the QSOs are returned (status "rda") so the user can decide.
	 * Without user interaction (cron) these QSOs are skipped.
	 *
	 * @param object $station_profile Station profile row
	 * @param string $key SRR API key
	 * @param bool $propmode_los Send QSOs on 2m and above without PROP_MODE with PROP_MODE LOS
	 * @param bool $interactive FALSE (cron): skip QSOs instead of returning them for a dialog
	 * @param bool $without_rda Send QSOs without a valid RDA without MY_CNTY
	 * @return array status, infomessage, errormessages, the number of uploaded QSOs (uploaded) and the QSOs for the dialogs (qsos, rda_qsos)
	 */
	function upload_station($station_profile, $key, $propmode_los = false, $interactive = true, $without_rda = false) {
		$result = array('status' => 'OK', 'infomessage' => '', 'errormessages' => array(), 'qsos' => array());
		$station_text = $station_profile->station_callsign." (".$station_profile->station_profile_name."): ";

		$rda_list = $this->get_srr_rda($key);
		if ($rda_list === false) {
			$result['status'] = 'Error';
			$result['errormessages'][] = $station_text.__("Could not load the RDA list from SRR.");
			return $result;
		}

		$this->load->model('Logbook_model');
		if (!$this->load->is_loaded('AdifHelper')) {
			$this->load->library('AdifHelper');
		}
		if (!$this->load->is_loaded('Frequency')) {
			$this->load->library('Frequency');
		}
		if (!$this->load->is_loaded('adif_parser')) {
			$this->load->library('adif_parser');
		}

		$qsos = $this->Logbook_model->get_srr_qsos_to_upload($station_profile->station_id)->result();

		// Nothing to upload
		if (empty($qsos)) {
			$result['infomessage'] = $station_text.__("No QSOs to upload.");
			return $result;
		}

		// QSOs on 2m and above need a PROP_MODE
		$upload_qsos = array();
		$los_ids = array();
		$skipped = 0;
		foreach ($qsos as $qso) {
			if ((($qso->COL_PROP_MODE ?? '') == '') && $this->vhf_or_above($qso)) {
				if ($propmode_los) {
					$qso->COL_PROP_MODE = 'LOS';
					$los_ids[] = $qso->COL_PRIMARY_KEY;
				} elseif ($interactive) {
					$result['qsos'][] = array(
						'call' => $qso->COL_CALL,
						'date' => date('Y-m-d H:i', strtotime($qso->COL_TIME_ON)),
						'band' => $qso->COL_BAND,
						'mode' => ($qso->COL_SUBMODE ?? '') != '' ? $qso->COL_SUBMODE : $qso->COL_MODE,
					);
					continue;
				} else {
					$skipped++;
					continue;
				}
			}
			$upload_qsos[] = $qso;
		}
		$qsos = '';

		if (count($result['qsos']) > 0) {
			$result['status'] = 'propmode';
			return $result;
		}

		// The user chose LOS for these QSOs, so it is stored in the QSOs too
		$this->Logbook_model->srr_update_prop_mode_los($los_ids);

		if ($skipped > 0) {
			$result['errormessages'][] = $station_text.sprintf(__("%d QSO(s) on 2m and above have no propagation mode and were not uploaded."), $skipped);
		}

		// The RDA district of the QSO is sent as MY_CNTY
		$result['rda_qsos'] = array();
		$rda_qsos = $upload_qsos;
		$upload_qsos = array();
		$skipped = 0;
		foreach ($rda_qsos as $qso) {
			$qso->srr_rda = strtoupper(trim($qso->COL_MY_CNTY ?? ''));
			if (!$this->rda_valid($qso->srr_rda, $rda_list)) {
				if ($without_rda) {
					$qso->srr_rda = '';
				} elseif ($interactive) {
					$result['rda_qsos'][] = array(
						'call' => $qso->COL_CALL,
						'date' => date('Y-m-d H:i', strtotime($qso->COL_TIME_ON)),
						'band' => $qso->COL_BAND,
						'mode' => ($qso->COL_SUBMODE ?? '') != '' ? $qso->COL_SUBMODE : $qso->COL_MODE,
						'rda' => $qso->COL_MY_CNTY ?? '',
					);
					continue;
				} else {
					$skipped++;
					continue;
				}
			}
			$upload_qsos[] = $qso;
		}
		$rda_qsos = '';

		if (count($result['rda_qsos']) > 0) {
			$result['status'] = 'rda';
			return $result;
		}

		if ($skipped > 0) {
			$result['errormessages'][] = $station_text.sprintf(__("%d QSO(s) have no valid RDA district and were not uploaded."), $skipped);
		}

		$uploaded = 0;
		$failed = 0;
		$result['uploaded'] = 0;

		// A first small request checks that SRR accepts our QSOs before all others are sent
		$probe = array_slice($upload_qsos, 0, $this->probe_size);
		$chunks = array_chunk(array_slice($upload_qsos, $this->probe_size), 100);
		$upload_qsos = '';

		$response = $this->post_qsos($this->build_records($probe), $key);
		if ($response['error'] != '') {
			$result['status'] = 'Error';
			$result['errormessages'][] = $station_text.__("Upload Failed")." - ".$response['error'];
			return $result;
		}

		$marked = $this->mark_chunk($probe, $response['data'], $station_text);
		$uploaded += $marked['uploaded'];
		$failed += $marked['failed'];
		$result['errormessages'] = array_merge($result['errormessages'], $marked['errormessages']);

		if (($marked['uploaded'] == 0) && (count($chunks) > 0)) {
			$result['status'] = 'Error';
			$result['errormessages'][] = $station_text.__("All test QSOs were rejected by SRR, the remaining QSOs were not uploaded.");
			return $result;
		}

		// All other QSOs are sent in requests of 100 QSOs at the same time
		$mh = curl_multi_init();
		$active_handles = array();
		$queue_index = 0;
		$queue_count = count($chunks);
		$max_parallel = ($this->max_parallel > 0) ? $this->max_parallel : $queue_count;

		while ($queue_index < $queue_count && count($active_handles) < $max_parallel) {
			$ch = $this->srr_handle('POST', '/qso', $key, $this->qso_payload($this->build_records($chunks[$queue_index])));
			curl_multi_add_handle($mh, $ch);
			$active_handles[(int)$ch] = array('ch' => $ch, 'chunk' => $queue_index);
			$queue_index++;
		}

		while (count($active_handles) > 0) {
			curl_multi_exec($mh, $running);

			while ($info = curl_multi_info_read($mh)) {
				$ch = $info['handle'];
				$chunk = $chunks[$active_handles[(int)$ch]['chunk']];
				unset($active_handles[(int)$ch]);
				curl_multi_remove_handle($mh, $ch);

				$response = $this->post_result($this->srr_response($ch, curl_multi_getcontent($ch), $info['result']));
				if ($response['error'] != '') {
					// The QSOs of this request stay not uploaded and are sent next time
					$result['status'] = 'Error';
					$result['errormessages'][] = $station_text.__("Upload Failed")." - ".$response['error'];
				} else {
					$marked = $this->mark_chunk($chunk, $response['data'], $station_text);
					$uploaded += $marked['uploaded'];
					$failed += $marked['failed'];
					$result['errormessages'] = array_merge($result['errormessages'], $marked['errormessages']);
				}

				if ($queue_index < $queue_count) {
					$new_ch = $this->srr_handle('POST', '/qso', $key, $this->qso_payload($this->build_records($chunks[$queue_index])));
					curl_multi_add_handle($mh, $new_ch);
					$active_handles[(int)$new_ch] = array('ch' => $new_ch, 'chunk' => $queue_index);
					$queue_index++;
				}
			}

			if (count($active_handles) > 0) {
				curl_multi_select($mh, 1.0);
			}
		}

		curl_multi_close($mh);

		$result['uploaded'] = $uploaded;
		$result['infomessage'] = $station_text.__("Upload Successful")." ".$uploaded." QSOs";
		if ($failed > 0) {
			$result['infomessage'] .= ", ".sprintf(__("%d QSO(s) rejected by SRR and marked as invalid"), $failed);
		}
		return $result;
	}

	/**
	 * Downloads the confirmations of a user from SRR and marks the
	 * matching QSOs as received. The RDA district of the worked station (CNTY)
	 * is stored in the QSO.
	 *
	 * @param int $user_id User id
	 * @param string $key SRR API key
	 * @param string|null $from Date to download from, since the last received SRR confirmation if null
	 * @return string Result message with a table of the updated QSOs
	 */
	function download_user($user_id, $key, $from = null) {
		$me = $this->get_srr_me($key);
		if ($me === false) {
			return __("The stored SRR key is not valid. Please request a new key.");
		}

		$this->load->model('Logbook_model');
		$this->load->model('Stations');
		if (!$this->load->is_loaded('adif_parser')) {
			$this->load->library('adif_parser');
		}

		$station_ids = $this->Stations->all_station_ids_of_user($user_id);
		if ($station_ids == '') {
			return __("No Station Profiles found");
		}

		if (($from ?? '') != '') {
			$qslsince = date('Ymd', strtotime($from));
		} else {
			$qslsince = $this->srr_last_qsl_rcvd_date($user_id);
		}

		// QSOs which SRR did not process during the upload are verified first
		$verified = $this->verify_pending($user_id, $key, $me);
		if ($verified != '') {
			$verified .= "<br>";
		}

		$callsigns = $this->srr_callsigns($me);

		$updated = 0;
		$known = 0;
		$not_found = 0;
		$table = '';
		foreach ($callsigns as $callsign) {
			$result = $this->srr_request('GET', '/qso/export?callsign='.urlencode($callsign).'&source=all&qslsince='.$qslsince, $key);
			if ($result['error'] != '' || $result['httpcode'] != 200) {
				return $verified.$callsign.": ".__("Download failed")." - ".($result['error'] != '' ? $result['error'] : __("HTTP Code").": ".$result['httpcode']);
			}

			$parser = new ADIF_Parser();
			$parser->feed($result['raw']);
			while ($record = $parser->get_record()) {
				if (($record['qsl_rcvd'] ?? '') != 'Y') {
					continue;
				}

				$time_on = date('Y-m-d', strtotime($record['qso_date'])) ." ".date('H:i', strtotime($record['time_on']));
				$status = $this->Logbook_model->import_check($time_on, $record['call'], strtolower($record['band']), $record['mode'], $record['prop_mode'] ?? '', $record['sat_name'] ?? '', $record['station_callsign'], $station_ids);

				if ($status[0] != "Found") {
					$not_found++;
					continue;
				}

				$cnty = strtoupper(trim($record['cnty'] ?? ''));
				$qso = $this->Logbook_model->get_qso($status[1], true)->row();
				if (($qso->COL_SRR_QSL_RCVD ?? '') == 'Y') {
					if (($cnty != '') && ($cnty != ($qso->COL_CNTY ?? ''))) {
						$this->Logbook_model->srr_update_cnty($status[1], $cnty);
					}
					$known++;
					continue;
				}

				$qsl_date = (($record['qslrdate'] ?? '') != '') ? date('Y-m-d', strtotime($record['qslrdate'])) : date('Y-m-d');
				$this->Logbook_model->srr_update($status[1], $qsl_date, $cnty);
				$updated++;
				$table .= "<tr><td>".$record['station_callsign']."</td><td>".$time_on."</td><td>".$record['call']."</td><td>".$record['band']."</td><td>".$record['mode']."</td><td>".$cnty."</td><td>".$qsl_date."</td></tr>";
			}
		}

		$r = $verified.sprintf(__("%d confirmation(s) downloaded from SRR, %d already known, %d QSO(s) not found in the logbook."), $updated, $known, $not_found);
		if ($table != '') {
			$r .= '<table class="table table-sm table-striped mt-2"><thead><tr><th>'.__("Station callsign").'</th><th>'.__("Date").'</th><th>'.__("Callsign").'</th><th>'.__("Band").'</th><th>'.__("Mode").'</th><th>'.__("RDA").'</th><th>'.__("QSL Date").'</th></tr></thead><tbody>'.$table.'</tbody></table>';
		}
		return $r;
	}

	/**
	 * Returns the date of the last received SRR confirmation of a user
	 *
	 * @param int $user_id User id
	 * @return string Date as Ymd, 19000101 if there is none
	 */
	function srr_last_qsl_rcvd_date($user_id) {
		$sql = "SELECT date_format(MAX(COALESCE(COL_SRR_QSLRDATE, str_to_date('1900-01-01','%Y-%m-%d'))),'%Y%m%d') MAXDATE
			FROM " . $this->config->item('table_name') . " INNER JOIN station_profile ON (" . $this->config->item('table_name') . ".station_id = station_profile.station_id)
			WHERE station_profile.user_id = ?";
		$query = $this->db->query($sql, array($user_id));
		return $query->row()->MAXDATE ?? '19000101';
	}

	/**
	 * Stores uploaded QSOs which SRR accepted but did not process yet (inbox)
	 *
	 * @param array $qso_ids QSO primary keys
	 * @return void
	 */
	function add_pending($qso_ids) {
		if (empty($qso_ids)) {
			return;
		}
		$bindings = array();
		$upload_date = date("Y-m-d H:i:s");
		foreach ($qso_ids as $qso_id) {
			$bindings[] = $qso_id;
			$bindings[] = $upload_date;
		}
		$sql = "INSERT INTO srr_pending (qso_id, upload_date) VALUES ".implode(',', array_fill(0, count($qso_ids), '(?, ?)'))."
			ON DUPLICATE KEY UPDATE upload_date = VALUES(upload_date)";
		$this->db->query($sql, $bindings);
	}

	/**
	 * Verifies the uploaded QSOs of a user which SRR did not process during the upload (inbox).
	 * QSOs found in the SRR export stay uploaded, QSOs not found are marked as invalid.
	 * Only QSOs uploaded more than verify_after minutes ago are checked.
	 *
	 * @param int $user_id User id
	 * @param string $key SRR API key
	 * @param object $me SRR user from get_srr_me()
	 * @return string Result message, empty if there was nothing to verify
	 */
	function verify_pending($user_id, $key, $me) {
		$this->load->model('Logbook_model');
		$this->load->model('Stations');
		if (!$this->load->is_loaded('adif_parser')) {
			$this->load->library('adif_parser');
		}

		// QSOs which were deleted or set to another SRR state in the meantime need no verification
		$sql = "DELETE srr_pending FROM srr_pending
			LEFT OUTER JOIN " . $this->config->item('table_name') . " qsos ON qsos.COL_PRIMARY_KEY = srr_pending.qso_id
			WHERE qsos.COL_PRIMARY_KEY IS NULL OR coalesce(qsos.COL_SRR_QSL_SENT, '') <> 'Y'";
		$this->db->query($sql);

		$sql = "SELECT srr_pending.qso_id, qsos.COL_CALL, qsos.COL_TIME_ON, qsos.COL_BAND
			FROM srr_pending
			INNER JOIN " . $this->config->item('table_name') . " qsos ON qsos.COL_PRIMARY_KEY = srr_pending.qso_id
			INNER JOIN station_profile ON qsos.station_id = station_profile.station_id
			WHERE station_profile.user_id = ? AND srr_pending.upload_date <= ?
			ORDER BY qsos.COL_TIME_ON";
		$rows = $this->db->query($sql, array($user_id, date("Y-m-d H:i:s", time() - $this->verify_after * 60)))->result();

		$callsigns = $this->srr_callsigns($me);
		if (empty($rows) || empty($callsigns)) {
			return '';
		}

		$pending = array();
		foreach ($rows as $row) {
			$pending[$row->qso_id] = $row;
		}
		$from = date('Ymd', strtotime($rows[0]->COL_TIME_ON));
		$to = date('Ymd', strtotime($rows[count($rows) - 1]->COL_TIME_ON));
		$station_ids = $this->Stations->all_station_ids_of_user($user_id);

		$verified_ids = array();
		foreach ($callsigns as $callsign) {
			$result = $this->srr_request('GET', '/qso/export?callsign='.urlencode($callsign).'&source=all&from='.$from.'&to='.$to, $key);
			if ($result['error'] != '' || $result['httpcode'] != 200) {
				return $callsign.": ".__("Verification of the uploaded QSOs failed")." - ".($result['error'] != '' ? $result['error'] : __("HTTP Code").": ".$result['httpcode']);
			}

			$parser = new ADIF_Parser();
			$parser->feed($result['raw']);
			while ($record = $parser->get_record()) {
				$time_on = date('Y-m-d', strtotime($record['qso_date'])) ." ".date('H:i', strtotime($record['time_on']));
				$status = $this->Logbook_model->import_check($time_on, $record['call'], strtolower($record['band']), $record['mode'], $record['prop_mode'] ?? '', $record['sat_name'] ?? '', $record['station_callsign'], $station_ids);
				if (($status[0] == "Found") && isset($pending[$status[1]])) {
					$verified_ids[] = $status[1];
					unset($pending[$status[1]]);
				}
			}
		}

		$r = sprintf(__("%d uploaded QSO(s) verified on SRR, %d not found and marked as invalid."), count($verified_ids), count($pending));
		foreach ($pending as $qso_id => $qso) {
			$this->Logbook_model->mark_srr_sent($qso_id, 'I');
			$r .= "<br>".$qso->COL_CALL." ".date('Y-m-d H:i', strtotime($qso->COL_TIME_ON))." ".$qso->COL_BAND." - ".__("not found on SRR after processing. Please check the API requests in your SRR panel.");
		}

		$this->db->where_in('qso_id', array_merge($verified_ids, array_keys($pending)));
		$this->db->delete('srr_pending');

		return $r;
	}

	/**
	 * Marks the QSOs of one request by the SRR response. QSOs with an error are
	 * marked as invalid, all others as uploaded. If SRR did not process all
	 * accepted QSOs yet (inbox), the uploaded QSOs are stored for a later verification.
	 *
	 * @param array $chunk QSO rows of the request
	 * @param object $data Decoded SRR response
	 * @param string $station_text Prefix for the error messages
	 * @return array uploaded, failed and errormessages
	 */
	private function mark_chunk($chunk, $data, $station_text) {
		$result = array('uploaded' => 0, 'failed' => 0, 'errormessages' => array());

		$qso_errors = array();
		foreach (($data->validation_errors ?? array()) as $validation_error) {
			$qso_errors[$validation_error->index] = $validation_error->message;
		}

		foreach (($data->process->errors ?? array()) as $process_error) {
			// A duplicate means the QSO is already known by SRR
			if (strpos($process_error->message ?? '', 'Duplicate entry') !== 0) {
				$qso_errors[$process_error->index] = $process_error->message;
			}
		}

		$uploaded_ids = array();
		foreach ($chunk as $index => $qso) {
			if (isset($qso_errors[$index])) {
				$this->Logbook_model->mark_srr_sent($qso->COL_PRIMARY_KEY, 'I');
				$result['errormessages'][] = $station_text.$qso->COL_CALL." ".date('Y-m-d H:i', strtotime($qso->COL_TIME_ON))." ".$qso->COL_BAND." - ".$qso_errors[$index];
				$result['failed']++;
			} else {
				$this->Logbook_model->mark_srr_sent($qso->COL_PRIMARY_KEY);
				$uploaded_ids[] = $qso->COL_PRIMARY_KEY;
				$result['uploaded']++;
			}
		}

		if ((int)($data->process->processed ?? 0) < (int)($data->accepted ?? count($chunk))) {
			$this->add_pending($uploaded_ids);
		}

		return $result;
	}

	/**
	 * Returns the own and the delegated callsigns of the SRR account.
	 * The SRR export of a callsign contains all QSOs of the SRR account,
	 * so one request for the own and one for each delegated callsign is enough.
	 *
	 * @param object $me SRR user from get_srr_me()
	 * @return array Callsigns
	 */
	private function srr_callsigns($me) {
		$callsigns = array();
		if (isset($me->callsigns[0]->callsign)) {
			$callsigns[] = $me->callsigns[0]->callsign;
		}
		foreach (($me->delegated_callsigns ?? array()) as $delegated) {
			if (($delegated->callsign ?? '') != '') {
				$callsigns[] = $delegated->callsign;
			}
		}
		return $callsigns;
	}

	/**
	 * Builds the QSO array for the SRR API out of the ADIF lines of the QSOs.
	 * The RDA of the QSO is sent as MY_CNTY. Without RDA no MY_CNTY is sent.
	 *
	 * @param array $qsos QSO rows with srr_rda set
	 * @return array One array of uppercase ADIF fields per QSO
	 */
	private function build_records($qsos) {
		$adif = '';
		foreach ($qsos as $qso) {
			$adif .= $this->adifhelper->getAdifLine($qso);
		}

		$parser = new ADIF_Parser();
		$parser->feed($adif);

		$records = array();
		foreach ($qsos as $qso) {
			$record = array();
			foreach ($parser->get_record() as $field => $value) {
				$record[strtoupper($field)] = $value;
			}
			if ($qso->srr_rda != '') {
				$record['MY_CNTY'] = $qso->srr_rda;
			} else {
				unset($record['MY_CNTY']);
			}
			$records[] = $record;
		}
		return $records;
	}

	/**
	 * Sends QSOs to SRR (POST /qso)
	 *
	 * @param array $records QSOs from build_records()
	 * @param string $key SRR API key
	 * @return array error (empty on success) and the decoded response (data)
	 */
	private function post_qsos($records, $key) {
		return $this->post_result($this->srr_request('POST', '/qso', $key, $this->qso_payload($records)));
	}

	/**
	 * Builds the request body for POST /qso
	 *
	 * @param array $records QSOs from build_records()
	 * @return array
	 */
	private function qso_payload($records) {
		return array(
			'logger' => array('name' => 'Wavelog', 'version' => $this->optionslib->get_option('version')),
			'qsos' => $records,
		);
	}

	/**
	 * Checks the result of a POST /qso request
	 *
	 * @param array $result Result from srr_request() or srr_response()
	 * @return array error (empty on success) and the decoded response (data)
	 */
	private function post_result($result) {
		$error = '';
		if ($result['error'] != '') {
			$error = $result['error'];
		} elseif (($result['httpcode'] != 200) || !in_array(($result['data']->result ?? ''), array('OK', 'PARTIAL'))) {
			$error = ($result['data']->error->message ?? '')." (".__("HTTP Code").": ".$result['httpcode'].")";
		}
		return array('error' => $error, 'data' => $result['data']);
	}

	/**
	 * Checks if the QSO was made on 2m or above
	 *
	 * @param object $qso QSO row
	 * @return bool
	 */
	private function vhf_or_above($qso) {
		$freq = (float)($qso->COL_FREQ ?? 0);
		if ($freq <= 0) {
			$freq = (float)($this->frequency->defaultFrequencies[strtolower($qso->COL_BAND ?? '')]['SSB'] ?? 0);
		}
		return $freq >= 144000000;
	}

	/**
	 * Sends a request to the SRR API
	 *
	 * @param string $method GET or POST
	 * @param string $path API path incl. query string
	 * @param string $key SRR API key
	 * @param array|null $payload Data sent as JSON on POST
	 * @return array httpcode, error (curl error), raw (response body) and data (decoded JSON)
	 */
	private function srr_request($method, $path, $key, $payload = null) {
		$ch = $this->srr_handle($method, $path, $key, $payload);
		$response = curl_exec($ch);
		return $this->srr_response($ch, $response, curl_errno($ch));
	}

	/**
	 * Prepares a request to the SRR API
	 *
	 * @param string $method GET or POST
	 * @param string $path API path incl. query string
	 * @param string $key SRR API key
	 * @param array|null $payload Data sent as JSON on POST
	 * @return CurlHandle
	 */
	private function srr_handle($method, $path, $key, $payload = null) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->api_url.$path);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog/'.$this->optionslib->get_option('version'));
		$headers = [
			'Authorization: Bearer '.$key,
			'Accept: application/json'
		];
		if ($method == 'POST') {
			$headers[] = 'Content-Type: application/json';
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		return $ch;
	}

	/**
	 * Evaluates a finished request to the SRR API
	 *
	 * @param CurlHandle $ch The handle of the request
	 * @param string|false $response Response body
	 * @param int $errno curl error number
	 * @return array httpcode, error (curl error), raw (response body) and data (decoded JSON)
	 */
	private function srr_response($ch, $response, $errno) {
		$error = '';
		if ($errno) {
			$error = curl_strerror($errno)." (".$errno.")";
		}
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		return array('httpcode' => $httpcode, 'error' => $error, 'raw' => $response, 'data' => json_decode($response ?? ''));
	}
}
