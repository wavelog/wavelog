<?php

class Srr_model extends CI_Model {

	private $api_url = 'https://award.srr.ru/api/v1';

	/*
	|--------------------------------------------------------------------------
	| Function: srr_key
	|--------------------------------------------------------------------------
	|
	| Returns the award.srr API key of the user via the $user_id parameter
	|
	*/
	function srr_key($user_id) {
		$this->load->model('user_options_model');
		$row = $this->user_options_model->get_options('srr', array('option_name'=>'srr_key','option_key'=>'key'), $user_id)->row();
		return $row->option_value ?? '';
	}

	/*
	|--------------------------------------------------------------------------
	| Function: srr_users
	|--------------------------------------------------------------------------
	|
	| Returns all users which have stored an award.srr API key
	|
	*/
	function srr_users() {
		$sql = "SELECT user_id, option_value FROM user_options WHERE option_type = 'srr' AND option_name = 'srr_key' AND option_key = 'key' AND option_value <> ''";
		return $this->db->query($sql)->result();
	}

	function store_key($key) {
		$this->load->model('user_options_model');
		$this->user_options_model->set_option('srr', 'srr_key', array('key'=>$key));
	}

	function delete_key() {
		$this->load->model('user_options_model');
		$this->user_options_model->del_option('srr', 'srr_key',array('option_key' => 'key'));
	}

	/*
	|--------------------------------------------------------------------------
	| Function: get_srr_me
	|--------------------------------------------------------------------------
	|
	| Checks the key against award.srr and returns the user with his callsigns
	| or false if the key is invalid
	|
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

	/*
	|--------------------------------------------------------------------------
	| Function: get_srr_rda
	|--------------------------------------------------------------------------
	|
	| Returns the list of RDA codes from the award.srr info endpoint.
	| The list is cached as long as award.srr allows it (cache_ttl).
	|
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

	function rda_valid($rda, $rda_list) {
		return (preg_match('/^[A-Z]{2}-\d{2}$/', $rda) === 1) && in_array($rda, $rda_list, true);
	}

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

	/*
	|--------------------------------------------------------------------------
	| Function: upload_station
	|--------------------------------------------------------------------------
	|
	| Uploads all not yet uploaded QSOs of a station profile to award.srr.
	| QSOs on 2m and above need a PROP_MODE. If there are QSOs without one,
	| nothing is uploaded and the QSOs are returned (status "propmode") so the
	| user can decide. With $propmode_los these QSOs are sent with PROP_MODE LOS.
	| The RDA district of each QSO (MY_CNTY) is checked against the award.srr
	| RDA list. If there are QSOs without a valid RDA, nothing is uploaded and
	| the QSOs are returned (status "rda") so the user can decide. With
	| $without_rda these QSOs are sent without MY_CNTY.
	| Without user interaction (cron) these QSOs are skipped.
	|
	*/
	function upload_station($station_profile, $key, $propmode_los = false, $interactive = true, $without_rda = false) {
		$result = array('status' => 'OK', 'infomessage' => '', 'errormessages' => array(), 'qsos' => array());
		$station_text = $station_profile->station_callsign." (".$station_profile->station_profile_name."): ";

		$rda_list = $this->get_srr_rda($key);
		if ($rda_list === false) {
			$result['status'] = 'Error';
			$result['errormessages'][] = $station_text.__("Could not load the RDA list from award.srr.");
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
		$skipped = 0;
		foreach ($qsos as $qso) {
			if ((($qso->COL_PROP_MODE ?? '') == '') && $this->vhf_or_above($qso)) {
				if ($propmode_los) {
					$qso->COL_PROP_MODE = 'LOS';
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
		foreach (array_chunk($upload_qsos, 100) as $chunk) {
			$records = $this->build_records($chunk);

			$response = $this->post_qsos($records, $key);
			if ($response['error'] != '') {
				$result['status'] = 'Error';
				$result['errormessages'][] = $station_text.__("Upload Failed")." - ".$response['error'];
				break;
			}

			$qso_errors = array();
			foreach (($response['data']->validation_errors ?? array()) as $validation_error) {
				$qso_errors[$validation_error->index] = $validation_error->message;
			}

			if (count($response['data']->process->errors ?? array()) > 0) {
				// The process errors can not be assigned to the QSOs, so the QSOs are sent again one by one
				foreach ($chunk as $index => $qso) {
					if (isset($qso_errors[$index])) {
						continue;
					}
					$single = $this->post_qsos(array($records[$index]), $key);
					if ($single['error'] != '') {
						$qso_errors[$index] = $single['error'];
					} elseif (count($single['data']->validation_errors ?? array()) > 0) {
						$qso_errors[$index] = $single['data']->validation_errors[0]->message;
					} elseif (count($single['data']->process->errors ?? array()) > 0) {
						// A duplicate means the QSO is already known by award.srr
						if (strpos($single['data']->process->errors[0]->message ?? '', 'Duplicate entry') !== 0) {
							$qso_errors[$index] = $single['data']->process->errors[0]->message;
						}
					}
				}
			}

			foreach ($chunk as $index => $qso) {
				if (isset($qso_errors[$index])) {
					$this->Logbook_model->mark_srr_sent($qso->COL_PRIMARY_KEY, 'I');
					$result['errormessages'][] = $station_text.$qso->COL_CALL." ".date('Y-m-d H:i', strtotime($qso->COL_TIME_ON))." ".$qso->COL_BAND." - ".$qso_errors[$index];
					$failed++;
				} else {
					$this->Logbook_model->mark_srr_sent($qso->COL_PRIMARY_KEY);
					$uploaded++;
				}
			}
		}

		$result['infomessage'] = $station_text.__("Upload Successful")." ".$uploaded." QSOs";
		if ($failed > 0) {
			$result['infomessage'] .= ", ".sprintf(__("%d QSO(s) rejected by award.srr and marked as invalid"), $failed);
		}
		return $result;
	}

	/*
	|--------------------------------------------------------------------------
	| Function: download_user
	|--------------------------------------------------------------------------
	|
	| Downloads the confirmations of a user from award.srr and marks the
	| matching QSOs as received. The RDA district of the worked station (CNTY)
	| is stored in the QSO. Without $from the confirmations since the last
	| received award.srr confirmation are downloaded.
	|
	*/
	function download_user($user_id, $key, $from = null) {
		$me = $this->get_srr_me($key);
		if ($me === false) {
			return __("The stored award.srr key is not valid. Please request a new key.");
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

		// The export contains all QSOs of the award.srr account, so one request for the own and one for each delegated callsign is enough
		$callsigns = array();
		if (isset($me->callsigns[0]->callsign)) {
			$callsigns[] = $me->callsigns[0]->callsign;
		}
		foreach (($me->delegated_callsigns ?? array()) as $delegated) {
			if (($delegated->callsign ?? '') != '') {
				$callsigns[] = $delegated->callsign;
			}
		}

		$updated = 0;
		$known = 0;
		$not_found = 0;
		$table = '';
		foreach ($callsigns as $callsign) {
			$result = $this->srr_request('GET', '/qso/export?callsign='.urlencode($callsign).'&source=all&qslsince='.$qslsince, $key);
			if ($result['error'] != '' || $result['httpcode'] != 200) {
				return $callsign.": ".__("Download failed")." - ".($result['error'] != '' ? $result['error'] : __("HTTP Code").": ".$result['httpcode']);
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
				$qso = $this->Logbook_model->get_qso($status[1])->row();
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

		$r = sprintf(__("%d confirmation(s) downloaded from award.srr, %d already known, %d QSO(s) not found in the logbook."), $updated, $known, $not_found);
		if ($table != '') {
			$r .= '<table class="table table-sm table-striped mt-2"><thead><tr><th>'.__("Station callsign").'</th><th>'.__("Date").'</th><th>'.__("Callsign").'</th><th>'.__("Band").'</th><th>'.__("Mode").'</th><th>'.__("RDA").'</th><th>'.__("QSL Date").'</th></tr></thead><tbody>'.$table.'</tbody></table>';
		}
		return $r;
	}

	/*
	 * Returns the date of the last received award.srr confirmation of a user
	 */
	function srr_last_qsl_rcvd_date($user_id) {
		$sql = "SELECT date_format(MAX(COALESCE(COL_SRR_QSLRDATE, str_to_date('1900-01-01','%Y-%m-%d'))),'%Y%m%d') MAXDATE
			FROM " . $this->config->item('table_name') . " INNER JOIN station_profile ON (" . $this->config->item('table_name') . ".station_id = station_profile.station_id)
			WHERE station_profile.user_id = ?";
		$query = $this->db->query($sql, array($user_id));
		return $query->row()->MAXDATE ?? '19000101';
	}

	/*
	 * Builds the QSO array for the award.srr API out of the ADIF lines of the QSOs.
	 * The RDA of the QSO is sent as MY_CNTY. Without RDA no MY_CNTY is sent.
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

	private function post_qsos($records, $key) {
		$payload = array(
			'logger' => array('name' => 'Wavelog', 'version' => $this->optionslib->get_option('version')),
			'qsos' => $records,
		);
		$result = $this->srr_request('POST', '/qso', $key, $payload);

		$error = '';
		if ($result['error'] != '') {
			$error = $result['error'];
		} elseif (($result['httpcode'] != 200) || !in_array(($result['data']->result ?? ''), array('OK', 'PARTIAL'))) {
			$error = ($result['data']->error->message ?? '')." (".__("HTTP Code").": ".$result['httpcode'].")";
		}
		return array('error' => $error, 'data' => $result['data']);
	}

	/*
	 * Checks if the QSO was made on 2m or above
	 */
	private function vhf_or_above($qso) {
		$freq = (float)($qso->COL_FREQ ?? 0);
		if ($freq <= 0) {
			$freq = (float)($this->frequency->defaultFrequencies[strtolower($qso->COL_BAND ?? '')]['SSB'] ?? 0);
		}
		return $freq >= 144000000;
	}

	private function srr_request($method, $path, $key, $payload = null) {
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

		$response = curl_exec($ch);
		$error = '';
		if (curl_errno($ch)) {
			$error = curl_strerror(curl_errno($ch))." (".curl_errno($ch).")";
		}
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		return array('httpcode' => $httpcode, 'error' => $error, 'raw' => $response, 'data' => json_decode($response ?? ''));
	}
}
