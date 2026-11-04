<?php

class Srr_model extends CI_Model {

	private $api_url = 'https://award.srr.ru/api/v1';

	// Number of QSOs sent in the first request of an upload
	private $probe_size = 10;

	// Number of requests sent at the same time
	private $max_parallel = 5;

	// Minimum minutes shown to the user until QSOs in the SRR inbox are processed
	private $verify_min = 15;

	// QSOs per minute which SRR processes from its inbox (measured), used for the shown time and the deadline
	private $srr_rate = 50;

	// Minimum minutes after the upload until QSOs which SRR did not process are uploaded again
	private $pending_deadline_min = 1440;

	// Seconds until a GET request to SRR is aborted
	private $timeout_get = 30;

	// Seconds until a GET /qso/export request to SRR is aborted
	private $timeout_export = 120;

	// Seconds until a POST request to SRR is aborted, SRR keeps it open up to wait.limit_seconds (120, measured up to 130)
	private $timeout_post = 180;

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
	 * @return object|false|null The SRR user incl. callsigns, false if the key is empty or SRR rejected it (HTTP 401 or 403), null if SRR is unavailable (curl error, any other HTTP code or an unexpected body)
	 */
	function get_srr_me($key) {
		if (($key ?? '') == '') {
			return false;
		}
		$result = $this->srr_request('GET', '/me', $key);
		if ($result['error'] != '') {
			return null;
		}
		if (in_array($result['httpcode'], array(401, 403))) {
			return false;
		}
		if (($result['httpcode'] == 200) && (($result['data']->result ?? '') == 'OK') && is_object($result['data']->data ?? null)) {
			return $result['data']->data;
		}
		return null;
	}

	/**
	 * Checks if a station callsign is one of the own or delegated callsigns of the
	 * SRR account. The plain callsigns are compared, so UB1AVZ/P matches UB1AVZ.
	 *
	 * @param string $station_callsign Callsign of the station profile
	 * @param object $me SRR user from get_srr_me()
	 * @return bool
	 */
	function callsign_on_account($station_callsign, $me) {
		if (!$this->load->is_loaded('callbook')) {
			$this->load->library('callbook');
		}

		$account_calls = array();
		foreach (($me->callsigns ?? array()) as $srr_call) {
			$account_calls[] = strtoupper($this->callbook->get_plaincall($srr_call->callsign ?? ''));
		}
		foreach (($me->delegated_callsigns ?? array()) as $srr_call) {
			$account_calls[] = strtoupper($this->callbook->get_plaincall($srr_call->callsign ?? ''));
		}

		$plaincall = strtoupper($this->callbook->get_plaincall($station_callsign));
		return ($plaincall != '') && in_array($plaincall, $account_calls, true);
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
	 * not uploaded, queued (in the SRR inbox), uploaded and rejected QSOs
	 *
	 * @param int|null $user_id User id, the logged in user if null
	 * @return CI_DB_result
	 */
	function stations_with_srr($user_id = null) {
		if ($user_id == null) {
			$user_id = $this->session->userdata('user_id');
		}
		$bindings=[];
		$sql = "SELECT station_profile.station_id, station_profile.station_profile_name, station_profile.station_callsign, modc.modcount, notc.notcount, quec.queuedcount, totc.totcount, rejc.rejcount
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
			SELECT count(*) queuedcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE COL_SRR_QSL_SENT = 'Q'
			group by station_id
		) as quec on station_profile.station_id = quec.station_id
		LEFT OUTER JOIN (
			SELECT count(*) totcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE COL_SRR_QSL_SENT = 'Y'
			group by station_id
		) as totc on station_profile.station_id = totc.station_id
		LEFT OUTER JOIN (
			SELECT count(*) rejcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE COL_SRR_QSL_SENT = 'I'
			group by station_id
		) as rejc on station_profile.station_id = rejc.station_id
		WHERE station_profile.user_id = ?
		ORDER BY station_profile.station_callsign, station_profile.station_profile_name";
		$bindings[]=$user_id;
		$query = $this->db->query($sql, $bindings);

		return $query;
	}

	/**
	 * Returns the QSOs of a station profile which can be uploaded to SRR now.
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
	 * @param bool $propmode_los Send QSOs on 2m and above without PROP_MODE with PROP_MODE LOS and store it in the QSOs
	 * @param bool $interactive FALSE (cron): skip QSOs instead of returning them for a dialog
	 * @param bool $without_rda Send QSOs without a valid RDA without MY_CNTY
	 * @param array $result Upload result, gets the status, the messages and the QSOs for the dialogs
	 * @return array QSOs to upload, empty if there is nothing to upload now
	 */
	private function prepare_upload($station_profile, $key, $propmode_los, $interactive, $without_rda, &$result) {
		$station_text = html_escape($station_profile->station_callsign." (".$station_profile->station_profile_name."): ");

		$rda_list = $this->get_srr_rda($key);
		if ($rda_list === false) {
			$result['status'] = 'Error';
			$result['errormessages'][] = $station_text.__("Could not load the RDA list from SRR.");
			return array();
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
			return array();
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
			return array();
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
			return array();
		}

		if ($skipped > 0) {
			$result['errormessages'][] = $station_text.sprintf(__("%d QSO(s) have no valid RDA district and were not uploaded."), $skipped);
		}

		return $upload_qsos;
	}

	/**
	 * Uploads the not yet uploaded QSOs of a station profile to SRR.
	 *
	 * A first request with a few QSOs checks that SRR accepts them. The user does not
	 * wait for all other QSOs: the result is returned with the number of the remaining
	 * QSOs (remaining) and the browser starts upload_remaining() right away.
	 * Without user interaction (cron) all QSOs are sent here.
	 *
	 * @param object $station_profile Station profile row
	 * @param string $key SRR API key
	 * @param bool $propmode_los Send QSOs on 2m and above without PROP_MODE with PROP_MODE LOS and store it in the QSOs
	 * @param bool $interactive FALSE (cron): skip QSOs instead of returning them for a dialog and send all QSOs
	 * @param bool $without_rda Send QSOs without a valid RDA without MY_CNTY
	 * @return array status, infomessage, errormessages, the numbers of uploaded and rejected QSOs (uploaded, failed), the number of QSOs for upload_remaining() (remaining), the minutes until SRR should have processed them (verify_minutes) and the QSOs for the dialogs (qsos, rda_qsos)
	 */
	function upload_station($station_profile, $key, $propmode_los = false, $interactive = true, $without_rda = false) {
		$result = array('status' => 'OK', 'infomessage' => '', 'errormessages' => array(), 'qsos' => array());
		$station_text = html_escape($station_profile->station_callsign." (".$station_profile->station_profile_name."): ");

		$upload_qsos = $this->prepare_upload($station_profile, $key, $propmode_los, $interactive, $without_rda, $result);
		if (empty($upload_qsos)) {
			return $result;
		}

		$result['uploaded'] = 0;

		// A first small request checks that SRR accepts our QSOs before all others are sent
		$probe = array_slice($upload_qsos, 0, $this->probe_size);
		$remaining = array_slice($upload_qsos, $this->probe_size);
		$upload_qsos = '';

		$response = $this->post_qsos($this->build_records($probe), $key);
		if ($response['error'] != '') {
			$result['status'] = 'Error';
			$result['errormessages'][] = $station_text.__("Upload Failed")." - ".$response['error'];
			return $result;
		}

		$marked = $this->mark_chunk($probe, $response['data'], $station_text);
		$result['errormessages'] = array_merge($result['errormessages'], $marked['errormessages']);

		if (($marked['uploaded'] == 0) && (count($remaining) > 0)) {
			$result['status'] = 'Error';
			$result['errormessages'][] = $station_text.sprintf(__("SRR rejected all %d QSOs of the first request, so the upload was stopped and %d QSOs were not sent. The rejected QSOs are marked as invalid, the next upload continues with the other QSOs."), count($probe), count($remaining));
			return $result;
		}

		// SRR processes about srr_rate QSOs per minute from its inbox, which may still hold an earlier upload.
		// This time is shown to the user, rounded up to 5 minutes, but not less than verify_min minutes.
		// QSOs of the first request which SRR did not process are already marked as queued.
		$inbox_minutes = (count($remaining) + $this->count_queued($station_profile->user_id)) / $this->srr_rate;
		$result['verify_minutes'] = max($this->verify_min, (int)ceil($inbox_minutes / 5) * 5);

		$result['uploaded'] = $marked['uploaded'];
		$result['failed'] = $marked['failed'];

		if ($interactive && (count($remaining) > 0)) {
			$result['remaining'] = count($remaining);
			$result['infomessage'] = $station_text.sprintf(__("%d QSOs queued for upload to SRR"), $marked['uploaded'] + count($remaining));
			return $result;
		}

		$sent = $this->send_remaining($remaining, $station_profile, $key, $result);
		$result['uploaded'] += $sent['uploaded'];
		$result['failed'] += $sent['failed'];
		$result['infomessage'] = $this->upload_message($station_text, $result['uploaded'], $result['failed']);
		return $result;
	}

	/**
	 * Uploads the QSOs which remain after the first request of upload_station().
	 * The browser starts it right after upload_station() and does not need to wait for it.
	 *
	 * @param object $station_profile Station profile row
	 * @param string $key SRR API key
	 * @param bool $propmode_los Send QSOs on 2m and above without PROP_MODE with PROP_MODE LOS and store it in the QSOs
	 * @param bool $without_rda Send QSOs without a valid RDA without MY_CNTY
	 * @param int $probe_uploaded Number of QSOs uploaded by upload_station(), for the message
	 * @param int $probe_failed Number of QSOs rejected in upload_station(), for the message
	 * @return array status, infomessage, errormessages and the numbers of uploaded and rejected QSOs (uploaded, failed)
	 */
	function upload_remaining($station_profile, $key, $propmode_los = false, $without_rda = false, $probe_uploaded = 0, $probe_failed = 0) {
		$result = array('status' => 'OK', 'infomessage' => '', 'errormessages' => array(), 'qsos' => array(), 'uploaded' => 0, 'failed' => 0);
		$station_text = html_escape($station_profile->station_callsign." (".$station_profile->station_profile_name."): ");

		$upload_qsos = $this->prepare_upload($station_profile, $key, $propmode_los, false, $without_rda, $result);
		if (empty($upload_qsos)) {
			return $result;
		}

		$sent = $this->send_remaining($upload_qsos, $station_profile, $key, $result);
		$result['uploaded'] = $sent['uploaded'];
		$result['failed'] = $sent['failed'];
		$result['infomessage'] = $this->upload_message($station_text, $probe_uploaded + $sent['uploaded'], $probe_failed + $sent['failed']);
		return $result;
	}

	/**
	 * Returns the result message of an upload
	 *
	 * @param string $station_text Prefix of the message, escaped for HTML
	 * @param int $uploaded Number of uploaded QSOs
	 * @param int $failed Number of rejected QSOs
	 * @return string Message
	 */
	private function upload_message($station_text, $uploaded, $failed) {
		$message = $station_text.__("Upload Successful")." ".$uploaded." QSOs";
		if ($failed > 0) {
			$message .= ", ".sprintf(__("%d QSO(s) rejected by SRR and marked as invalid"), $failed);
		}
		return $message;
	}

	/**
	 * Returns the minutes until SRR must have processed queued QSOs. QSOs which
	 * it still has not processed after this time are uploaded again, see verify_pending().
	 *
	 * @param float $inbox_minutes Estimated minutes until SRR has processed its inbox
	 * @return int Minutes
	 */
	private function pending_deadline($inbox_minutes) {
		return max($this->pending_deadline_min, (int)ceil(2 * $inbox_minutes));
	}

	/**
	 * Marks QSOs as queued and sends them in requests of 100 QSOs, max_parallel at the same time.
	 * QSOs which SRR processes during the request are marked by its response, the
	 * others stay queued and are verified later by verify_pending(). The QSOs of a
	 * failed request are set to not uploaded and are sent next time. If SRR does not
	 * respond, no further requests are started and the QSOs not sent are set to not uploaded too.
	 *
	 * @param array $qsos QSOs to upload
	 * @param object $station_profile Station profile row
	 * @param string $key SRR API key
	 * @param array $result Upload result, gets the status and the error messages
	 * @return array Numbers of uploaded and rejected QSOs (uploaded, failed)
	 */
	private function send_remaining($qsos, $station_profile, $key, &$result) {
		$sent = array('uploaded' => 0, 'failed' => 0);
		if (empty($qsos)) {
			return $sent;
		}

		$station_text = html_escape($station_profile->station_callsign." (".$station_profile->station_profile_name."): ");

		$this->Logbook_model->srr_mark_queued(array_column($qsos, 'COL_PRIMARY_KEY'));

		$chunks = array_chunk($qsos, 100);
		$qsos = '';

		$mh = curl_multi_init();
		$active_handles = array();
		$queue_index = 0;
		$queue_count = count($chunks);
		$aborted = false;

		while ($queue_index < $queue_count && count($active_handles) < $this->max_parallel) {
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
					// The QSOs of this request are not uploaded and are sent next time
					$this->Logbook_model->srr_update_verified(array_column($chunk, 'COL_PRIMARY_KEY'), 'N');
					$result['status'] = 'Error';
					$result['errormessages'][] = $station_text.__("Upload Failed")." - ".$response['error'];
					if ($info['result'] != CURLE_OK) {
						$aborted = true;
					}
				} else {
					$marked = $this->mark_chunk($chunk, $response['data'], $station_text, true);
					$sent['uploaded'] += $marked['uploaded'];
					$sent['failed'] += $marked['failed'];
					$result['errormessages'] = array_merge($result['errormessages'], $marked['errormessages']);
				}

				if (!$aborted && $queue_index < $queue_count) {
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

		if ($queue_index < $queue_count) {
			$unsent_ids = array_column(array_merge(...array_slice($chunks, $queue_index)), 'COL_PRIMARY_KEY');
			$this->Logbook_model->srr_update_verified($unsent_ids, 'N');
			$result['errormessages'][] = $station_text.sprintf(__("SRR did not respond, so the upload was stopped and %d QSOs were not sent. They will be sent with the next upload."), count($unsent_ids));
		}

		return $sent;
	}

	/**
	 * Returns why SRR does not need to be asked for a user
	 *
	 * @param int $user_id User id
	 * @return string Message, empty if the user has QSOs
	 */
	function skip_reason($user_id) {
		$this->load->model('Logbook_model');
		$this->load->model('Stations');

		$station_ids = $this->Stations->all_station_ids_of_user($user_id);
		if ($station_ids == '') {
			return __("No Station Profiles found");
		}

		// No QSOs in log, so there is nothing a download could match
		if ($this->Logbook_model->total_qsos(explode(', ', $station_ids)) == 0) {
			return __("No QSOs in log. SRR download skipped.");
		}

		return '';
	}

	/**
	 * Downloads the confirmations of a user from SRR and marks the
	 * matching QSOs as received. The RDA district of the worked station (CNTY)
	 * is stored in the QSO.
	 *
	 * @param int $user_id User id
	 * @param string $key SRR API key
	 * @param string|null $from Date to download from, since the last received SRR confirmation if null
	 * @param object|false|null $me Result of get_srr_me() if the caller already has it, null to request it here
	 * @return string Result message with a table of the updated QSOs
	 */
	function download_user($user_id, $key, $from = null, $me = null) {
		$skip_reason = $this->skip_reason($user_id);
		if ($skip_reason != '') {
			return $skip_reason;
		}

		$station_ids = $this->Stations->all_station_ids_of_user($user_id);

		if ($me === null) {
			$me = $this->get_srr_me($key);
		}
		if ($me === false) {
			return __("The stored SRR key is not valid. Please request a new key.");
		}
		if ($me === null) {
			return __("SRR could not be reached. Please try again later.");
		}

		if (!$this->load->is_loaded('adif_parser')) {
			$this->load->library('adif_parser');
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

		// The date of the next download is the same for all callsigns and moves with the first marked QSO.
		// So nothing is marked until all exports are here, or the next download skips those of a failed export.
		$exports = array();
		foreach ($callsigns as $callsign) {
			$result = $this->srr_request('GET', '/qso/export?callsign='.urlencode($callsign).'&source=all&qslsince='.$qslsince, $key);
			if ($result['error'] != '' || $result['httpcode'] != 200) {
				return $verified.html_escape($callsign).": ".__("Download failed")." - ".($result['error'] != '' ? $result['error'] : __("HTTP Code").": ".$result['httpcode']);
			}
			$exports[] = $result['raw'];
		}

		$updated = 0;
		$known = 0;
		$not_found = 0;
		$table = '';
		$handled = array();
		foreach ($exports as $export) {
			$parser = new ADIF_Parser();
			$parser->feed($export);
			while ($record = $parser->get_record()) {
				if (($record['qsl_rcvd'] ?? '') != 'Y') {
					continue;
				}

				$time_on = date('Y-m-d', strtotime($record['qso_date'])) ." ".date('H:i', strtotime($record['time_on']));

				$record_key = $this->record_key($record, $time_on);
				if (isset($handled[$record_key])) {
					continue;
				}
				$handled[$record_key] = true;

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
				$table .= "<tr><td>".html_escape($record['station_callsign'])."</td><td>".$time_on."</td><td>".html_escape($record['call'])."</td><td>".html_escape($record['band'])."</td><td>".html_escape($record['mode'])."</td><td>".html_escape($cnty)."</td><td>".$qsl_date."</td></tr>";
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
			WHERE station_profile.user_id = ? AND COL_SRR_QSL_RCVD = 'Y'";
		$query = $this->db->query($sql, array($user_id));
		return $query->row()->MAXDATE ?? '19000101';
	}

	/**
	 * Returns the number of QSOs of a user which are queued in the SRR inbox
	 *
	 * @param int $user_id User id
	 * @return int Number of QSOs
	 */
	private function count_queued($user_id) {
		$sql = "SELECT count(*) AS queued
			FROM " . $this->config->item('table_name') . " qsos
			INNER JOIN station_profile ON qsos.station_id = station_profile.station_id
			WHERE station_profile.user_id = ? AND qsos.COL_SRR_QSL_SENT = 'Q'";
		return (int)$this->db->query($sql, array($user_id))->row()->queued;
	}

	/**
	 * Verifies the queued QSOs of a user which SRR did not process during the upload (inbox).
	 * QSOs found in the SRR export are marked as uploaded. QSOs not found are probably still in
	 * the inbox and stay queued. After their deadline (see pending_deadline()), counted from their
	 * upload date, they are marked as not uploaded, so the next upload sends them again.
	 * A QSO without an upload date has no deadline to wait for. If the export of a callsign
	 * fails, the other callsigns are verified and the QSOs of this one stay queued without a deadline.
	 *
	 * @param int $user_id User id
	 * @param string $key SRR API key
	 * @param object $me SRR user from get_srr_me()
	 * @return string Result message, empty if there was nothing to verify
	 */
	function verify_pending($user_id, $key, $me) {
		$this->load->model('Logbook_model');
		if (!$this->load->is_loaded('adif_parser')) {
			$this->load->library('adif_parser');
		}
		if (!$this->load->is_loaded('callbook')) {
			$this->load->library('callbook');
		}

		$sql = "SELECT qsos.COL_PRIMARY_KEY, qsos.COL_SRR_QSLSDATE, station_profile.station_callsign, qsos.COL_CALL, qsos.COL_TIME_ON, qsos.COL_BAND, qsos.COL_MODE, qsos.COL_SUBMODE
			FROM " . $this->config->item('table_name') . " qsos
			INNER JOIN station_profile ON qsos.station_id = station_profile.station_id
			WHERE station_profile.user_id = ? AND qsos.COL_SRR_QSL_SENT = 'Q'
			ORDER BY qsos.COL_TIME_ON";
		$rows = $this->db->query($sql, array($user_id))->result();

		$callsigns = $this->srr_callsigns($me);
		if (empty($rows) || empty($callsigns)) {
			return '';
		}

		$pending = array();
		$by_minute = array();
		foreach ($rows as $row) {
			$pending[$row->COL_PRIMARY_KEY] = $row;
			// The upload sends the callsign of the station profile, see AdifHelper::getAdifLine()
			$by_minute[$this->minute_key($row->station_callsign, $row->COL_CALL, $row->COL_BAND, date('Y-m-d H:i', strtotime($row->COL_TIME_ON)))][] = $row;
		}
		$from = date('Ymd', strtotime($rows[0]->COL_TIME_ON));
		$to = date('Ymd', strtotime($rows[count($rows) - 1]->COL_TIME_ON));

		$verified_ids = array();
		$handled = array();
		$failed_calls = array();
		$failed_text = '';
		foreach ($callsigns as $callsign) {
			$result = $this->srr_request('GET', '/qso/export?callsign='.urlencode($callsign).'&source=all&from='.$from.'&to='.$to, $key);
			if ($result['error'] != '' || $result['httpcode'] != 200) {
				$failed_calls[] = strtoupper($this->callbook->get_plaincall($callsign));
				$failed_text .= html_escape($callsign).": ".__("Verification of the uploaded QSOs failed")." - ".($result['error'] != '' ? $result['error'] : __("HTTP Code").": ".$result['httpcode'])."<br>";
				continue;
			}

			$parser = new ADIF_Parser();
			$parser->feed($result['raw']);
			while ($record = $parser->get_record()) {
				$time_on = date('Y-m-d', strtotime($record['qso_date'])) ." ".date('H:i', strtotime($record['time_on']));

				$record_key = $this->record_key($record, $time_on);
				if (isset($handled[$record_key])) {
					continue;
				}
				$handled[$record_key] = true;

				// SRR identifies a QSO to the minute, so import_check() with its 15 minutes would mix up QSOs
				$minute_key = $this->minute_key($record['station_callsign'], $record['call'], $record['band'], $time_on);
				if (!empty($by_minute[$minute_key])) {
					foreach ($this->take_pending($by_minute[$minute_key], $record['mode']) as $qso) {
						$verified_ids[] = $qso->COL_PRIMARY_KEY;
						unset($pending[$qso->COL_PRIMARY_KEY]);
					}
				}
			}
		}

		// COL_SRR_QSLSDATE is written with PHP date(), so it is compared with the PHP time
		$deadline = $this->pending_deadline(count($rows) / $this->srr_rate) * 60;
		$now = time();
		$expired_ids = array();
		$expired_text = '';
		foreach ($pending as $qso_id => $qso) {
			// The export of this callsign failed, so the QSO may be on SRR
			if (in_array(strtoupper($this->callbook->get_plaincall($qso->station_callsign)), $failed_calls, true)) {
				continue;
			}
			if ((($qso->COL_SRR_QSLSDATE ?? '') == '') || ((strtotime($qso->COL_SRR_QSLSDATE) + $deadline) <= $now)) {
				$expired_ids[] = $qso_id;
				$expired_text .= "<br>".html_escape($qso->COL_CALL)." ".date('Y-m-d H:i', strtotime($qso->COL_TIME_ON))." ".html_escape($qso->COL_BAND)." - ".__("not found on SRR, it will be sent again with the next upload. Please check the API requests in your SRR panel.");
			}
		}

		$this->Logbook_model->srr_update_verified($verified_ids, 'Y');
		$this->Logbook_model->srr_update_verified($expired_ids, 'N');

		$r = sprintf(__("%d uploaded QSO(s) verified on SRR, %d still being processed by SRR, %d not found and set to not uploaded."), count($verified_ids), count($pending) - count($expired_ids), count($expired_ids));

		return $failed_text.$r.$expired_text;
	}

	/**
	 * Takes the QSOs for a record of the SRR export off a list of queued QSOs of the same minute.
	 * All QSOs with the mode of the record are taken, because they are one QSO for SRR.
	 * If there is none, the first QSO of the list is taken.
	 *
	 * @param array $qsos Queued QSOs of the same minute, not empty
	 * @param string $mode Mode of the record
	 * @return array QSO rows
	 */
	private function take_pending(&$qsos, $mode) {
		$taken = array();
		foreach ($qsos as $index => $qso) {
			if ((strcasecmp($qso->COL_MODE ?? '', $mode) == 0) || (strcasecmp($qso->COL_SUBMODE ?? '', $mode) == 0)) {
				$taken[] = $qso;
				unset($qsos[$index]);
			}
		}
		return !empty($taken) ? $taken : array(array_shift($qsos));
	}

	/**
	 * Marks the QSOs of one request by the SRR response. QSOs with an error are
	 * marked as invalid, all others as uploaded. If SRR did not process all accepted QSOs
	 * yet (inbox), they are marked as queued for a later verification by verify_pending().
	 *
	 * @param array $chunk QSO rows of the request
	 * @param object $data Decoded SRR response
	 * @param string $station_text Prefix for the error messages, escaped for HTML
	 * @param bool $marked_queued TRUE if the QSOs are already marked as queued
	 * @return array uploaded, failed and errormessages
	 */
	private function mark_chunk($chunk, $data, $station_text, $marked_queued = false) {
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

		$queued = (int)($data->process->processed ?? 0) < (int)($data->accepted ?? count($chunk));

		foreach ($chunk as $index => $qso) {
			if (isset($qso_errors[$index])) {
				$this->Logbook_model->mark_srr_sent($qso->COL_PRIMARY_KEY, 'I');
				$result['errormessages'][] = $station_text.html_escape($qso->COL_CALL)." ".date('Y-m-d H:i', strtotime($qso->COL_TIME_ON))." ".html_escape($qso->COL_BAND)." - ".html_escape($qso_errors[$index]);
				$result['failed']++;
			} else {
				// A download may already have verified a QSO which is marked as queued, so it is not marked again
				if (!$queued || !$marked_queued) {
					$this->Logbook_model->mark_srr_sent($qso->COL_PRIMARY_KEY, $queued ? 'Q' : 'Y');
				}
				$result['uploaded']++;
			}
		}

		return $result;
	}

	/**
	 * Returns the own and the delegated callsigns of the SRR account, each of them once
	 *
	 * @param object $me SRR user from get_srr_me()
	 * @return array Callsigns
	 */
	private function srr_callsigns($me) {
		$callsigns = array();
		foreach (array_merge($me->callsigns ?? array(), $me->delegated_callsigns ?? array()) as $item) {
			$callsign = $item->callsign ?? '';
			if (($callsign != '') && !isset($callsigns[strtoupper($callsign)])) {
				$callsigns[strtoupper($callsign)] = $callsign;
			}
		}
		return array_values($callsigns);
	}

	/**
	 * Returns the key of a QSO to the minute
	 *
	 * @param string $station_callsign Station callsign
	 * @param string $call Callsign of the worked station
	 * @param string $band Band
	 * @param string $time_on Date and time of the QSO as Y-m-d H:i
	 * @return string
	 */
	private function minute_key($station_callsign, $call, $band, $time_on) {
		return strtoupper($station_callsign).'|'.strtoupper($call).'|'.strtolower($band).'|'.$time_on;
	}

	/**
	 * Returns the key of a record of the SRR export. The same QSO may be in
	 * the export of several callsigns, the key is used to handle it only once.
	 *
	 * @param array $record ADIF record of the SRR export
	 * @param string $time_on Date and time of the QSO as Y-m-d H:i
	 * @return string
	 */
	private function record_key($record, $time_on) {
		return $this->minute_key($record['station_callsign'], $record['call'], $record['band'], $time_on).'|'.strtoupper($record['mode']);
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
	 * @return array error (empty on success, safe for HTML) and the decoded response (data)
	 */
	private function post_result($result) {
		$error = '';
		if ($result['error'] != '') {
			$error = $result['error'];
		} elseif (($result['httpcode'] != 200) || !in_array(($result['data']->result ?? ''), array('OK', 'PARTIAL'))) {
			$error = html_escape($result['data']->error->message ?? '')." (".__("HTTP Code").": ".$result['httpcode'].")";
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
		$timeout = (strpos($path, '/qso/export') === 0) ? $this->timeout_export : $this->timeout_get;
		$headers = [
			'Authorization: Bearer '.$key,
			'Accept: application/json'
		];
		if ($method == 'POST') {
			$timeout = $this->timeout_post;
			$headers[] = 'Content-Type: application/json';
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
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
