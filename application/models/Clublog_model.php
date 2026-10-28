<?php

class Clublog_model extends CI_Model
{

	private $clublog_identifier = '608df94896cb9c5421ae748235492b43815610c9';

	function get_clublog_users($userid = null) {
		$this->db->select('user_clublog_name, user_clublog_password, user_id');
		$this->db->where('coalesce(user_clublog_name, "") != ""');
		$this->db->where('coalesce(user_clublog_password, "") != ""');
		if ($userid !== null) {
			$this->db->where('user_id', $userid);
		}
		$query = $this->db->get($this->config->item('auth_table'));
		return $query->result();
	}

	function uploadUser($userid, $username, $password, $station_id = null) {
		$clean_username = $this->security->xss_clean($username);
		$clean_password = $password;	// Take password as it is from Database
		$clean_userid = $this->security->xss_clean($userid);

		$return = "No QSOs to upload";

		$this->config->load('config');

		ini_set('memory_limit', '-1');
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		$this->load->helper('file');
		if (!$this->load->is_loaded('AdifHelper')) {
			$this->load->library('AdifHelper');
		}

		$station_profiles = $this->all_with_count($clean_userid, $station_id);

		if ($station_profiles->num_rows()) {
			foreach ($station_profiles->result() as $station_row) {
				if ($station_row->qso_total > 0) {
					$data['qsos'] = $this->get_clublog_qsos($station_row->station_id);

					if ($data['qsos']->num_rows()) {
						$string = $this->load->view('adif/data/clublog', $data, TRUE);

						if ($data['qsos']->num_rows() == 1) {	// exactly ONE QSO --> use their realtime.api as demanded by clublog
							$singlepush=$this->push_qso_to_clublog($clean_username, $clean_password, $station_row->station_callsign, $string, $station_id);
							if ($singlepush['status'] == 'OK') {
								$this->mark_qsos_sent($station_row->station_id);
								log_message('info', 'Clublog singlepush upload for ' . $station_row->station_callsign . ' successfully sent and marked.');
							} else {
								log_message("Error", "Singlepush for ".$station_row->station_id." / ".$station_row->station_callsign." failed: ".$singlepush['status']);
							}
						} else {
							$ranid = uniqid();
							if (!write_file('uploads/clublog' . $ranid . $station_row->station_id . '.adi', $string)) {
								$return = 'Unable to write the file - Make the folder Upload folder has write permissions.';
							} else {

								// initialise the curl request
								$request = curl_init('https://clublog.org/putlogs.php');
								$filepath = realpath('uploads/clublog' . $ranid . $station_row->station_id . '.adi');

								// Check if the file actually exists
								if (!file_exists($filepath)) {
									$return .=  " Clublog upload for " . $station_row->station_callsign . ' failed. Upload file could not be created.';
									log_message('error', $return);
									return $return . "\n";
								}

								if (function_exists('curl_file_create')) { // php 5.5+
									$cFile = curl_file_create($filepath);
								} else {
									$cFile = '@' . $filepath;
								}
								$cFile->setPostFilename(basename($filepath));

								// send a file
								curl_setopt($request, CURLOPT_POST, true);
								curl_setopt($request, CURLOPT_TIMEOUT, 10);
								curl_setopt(
									$request,
									CURLOPT_POSTFIELDS,
									array(
										'email' => $clean_username,
										'password' => $clean_password,
										'callsign' => $station_row->station_callsign,
										'api' => $this->clublog_identifier,
										'file' => $cFile
									)
								);

								// output the response
								curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
								$response = curl_exec($request);
								$info = curl_getinfo($request);
								$httpcode = curl_getinfo($request, CURLINFO_HTTP_CODE);
								if (curl_errno($request)) {
									$return =  curl_error($request);
								}

								// If Clublog Accepts mark the QSOs
								if (($httpcode == 200) || (preg_match('/\baccepted\b/', $response))) {
									$return =  "QSOs uploaded and Logbook QSOs marked as sent to Clublog.";
									$this->mark_qsos_sent($station_row->station_id);
									$return .=  " Clublog upload for " . $station_row->station_callsign . ' successfully sent.';
									log_message('info', 'Clublog upload for ' . $station_row->station_callsign . ' successfully sent and marked.');
								} elseif ($httpcode == 403) { 	// New Message from clublog. HTTP 403 response check.
									$log = "Clublog returned HTML error page for " . $station_row->station_callsign . " (access denied)";
									log_message('Error', $log);
									$return .= $log."<br>";
									$this->disable_sync4call($station_row->station_callsign, $station_row->station_id);
								} else if (preg_match('/too many uploads already queued/', $response)) {	// New Error, Clublog has Backlog, skip for NOW
									$return = 'Clublog upload for ' . $station_row->station_callsign . ' failed, clublog tells backlog there. Skipping whole account for this cycle. Detailled reason ' . $response.' // HTTP:'.$httpcode.' / '.$return;
									log_message('Error', 'Clublog upload for ' . $station_row->station_callsign . ' has become a victim of clublog-Backlog. Skipping full User for this cycle.');
									unlink('uploads/clublog' . $ranid . $station_row->station_id . '.adi');
									break;
								} else if (preg_match('/No QSOs to upload/', $response)) {	// Means: Already uploaded (but not marked) - perhaps different logtool, who knows.
									$this->mark_qsos_sent($station_row->station_id);
									$return =  " Clublog upload for " . $station_row->station_callsign . ' successfully sent.';
									log_message('info', 'Clublog No QSOs to upload for ' . $station_row->station_callsign . '. preventive marked.');
								} else if (preg_match('/checksum duplicate/', $response)) {	// Be safe, if Michael rolls back to 403 on duplicate
									$return =  "QSOs uploaded (as duplicate!) and Logbook QSOs marked as sent to Clublog";
									$this->mark_qsos_sent($station_row->station_id);
									$return .=  " Clublog upload for " . $station_row->station_callsign . ' successfully sent.';
									log_message('info', 'Clublog DUPLICATE upload for ' . $station_row->station_callsign . ' successfully sent and marked.');
								} else {
									$return = 'Clublog upload for ' . $station_row->station_callsign . ' failed reason ' . $response.' // HTTP:'.$httpcode.' / '.$return;
									log_message('error', $return);
									if (substr($response,0,13) == 'Upload denied') {	// Deactivate Upload for Station if Clublog rejects it due to non-configured Call (prevent being blacklisted at Clublog)
										log_message('Error', 'Deactivated upload for station ' . $station_row->station_callsign . ' due to non-configured Call (prevent being blacklisted at Clublog.');
										$this->disable_sync4call($station_row->station_callsign, $station_row->station_id);
									} else if (substr($response,0,14) == 'Login rejected') {	// Deactivate Upload for Station if Clublog rejects it due to wrong credentials (prevent being blacklisted at Clublog)
										log_message('Error', 'Deactivated upload for station ' . $station_row->station_callsign . ' due to wrong credentials (prevent being blacklisted at Clublog.');
										$this->disable_sync4call($station_row->station_callsign, $station_row->station_id);
									} else if ($httpcode == 403) {
										log_message('Error', 'Deactivated upload for station ' . $station_row->station_callsign . ' due to 403 (prevent being blacklisted at Clublog.');
										$this->disable_sync4call($station_row->station_callsign, $station_row->station_id);
									} else {
										log_message('error', 'Some uncaught exception for station ' . $station_row->station_callsign);
									}
								}

								// Delete the ADIF file used for clublog
								unlink('uploads/clublog' . $ranid . $station_row->station_id . '.adi');
							}
						}
					} else {
						$return =  "Nothing awaiting upload to clublog for " . $station_row->station_callsign;
						log_message('info', 'Nothing awaiting upload to clublog for ' . $station_row->station_callsign);
					}
				}
			}
		}
		log_message('info', $return);
		return $return . "\n";
	}

	function downloadUser($userid, $username, $password, $clublog_last_date = null) {
		$clean_username = $this->security->xss_clean($username);
		$clean_password = $password;	// Take pw as it comes from DB
		$clean_userid = $this->security->xss_clean($userid);

		$return = '';

		$this->config->load('config');

		ini_set('memory_limit', '-1');
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		$this->load->helper('file');
		$this->load->model('logbook_model');

		$station_profiles = $this->all_enabled($clean_userid);	// Fetch unique Calls per User with aggregated station_ids

		if ($station_profiles->num_rows()) {
			foreach ($station_profiles->result() as $station_row) {
				$lastrec = $clublog_last_date ?? $this->clublog_last_qsl_rcvd_date($station_row->station_callsign);
				$lastrec = str_replace('-', '', $lastrec);
				$url_params=['api' => $this->clublog_identifier,
					'email' => $clean_username,
					'password' => $clean_password,
					'callsign' => trim($station_row->station_callsign),
					'startyear' => substr($lastrec, 0, 4),
					'startmonth' => substr($lastrec, 4, 2),
					'startday' => substr($lastrec, 6, 2)];
                                $url = 'https://clublog.org/getmatches.php?' . http_build_query($url_params);
				$request = curl_init($url);

				// recieve a file
				curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($request, CURLOPT_TIMEOUT, 10);
				$response = curl_exec($request);
				$info = curl_getinfo($request);
				$httpcode = curl_getinfo($request, CURLINFO_HTTP_CODE);
				$c_err=curl_errno($request);
				$c_errstring=curl_error($request);

				if ($c_err) {
					$log = $c_errstring."<br>";
					log_message("Error",$c_errstring."/".$c_err);
					if ($c_err == 7) { // We're victim of the Clublog Firewall
                                                return 'Impossible to reach Clublog';
                                        }
				} elseif (preg_match_all('/Login rejected/', $response)) {
					$this->disable_sync4call($station_row->station_callsign, $station_row->station_ids);
					$log = "Wrong Clublog username and password for Callsign: '" . $station_row->station_callsign . "'. 'LOGIN REJECTED'.";
					log_message('debug', $log);
					$return .= $log."<br>";
				} elseif (preg_match_all('/Invalid callsign/', $response)) {	// We're trying to download calls for a station we're not granted. Disable Clublog-Transfer for that station(s)
					$this->disable_sync4call($station_row->station_callsign, $station_row->station_ids);
					$log = "The callsign '" . $station_row->station_callsign . "' does not match the user account at Clublog. 'INVALID CALLSIGN'.";
					log_message('debug', $log);
					$return .= $log."<br>";
				} elseif ($httpcode == 403) {
					$log = "Clublog returned HTML error page for " . $station_row->station_callsign . " (possibly access denied)";
					log_message('debug', $log);
					$return .= $log."<br>";
					$this->disable_sync4call($station_row->station_callsign, $station_row->station_ids);
				} else {
					try {
						$cl_qsls = json_decode($response);
						foreach ($cl_qsls as $oneqsl) {
							$this->logbook_model->clublog_update($oneqsl[2], $oneqsl[0], $oneqsl[3], 'Y', $station_row->station_callsign, $station_row->station_ids);
						}
					} catch (Exception $e) {
						$log = "Something gone wrong while trying to Download for station(s) " . $station_row->station_ids . " / Call: " . $station_row->station_callsign." / Response was: ".$response;
						log_message("error", $log);
						$return .= $log."<br>";
					}

					$log = "QSOs for Callsign: '" . $station_row->station_callsign . "' were successfully downloaded";
					log_message('info', $log);
					$return .= $log."<br>";
				}
			}
		} else {
			$return = "Nothing to download";
		}

		return $return . "\n";
	}

	function mark_qsos_sent($station_id) {
		$data = array(
			'COL_CLUBLOG_QSO_UPLOAD_DATE' => date('Y-m-d H:i:s'),
			'COL_CLUBLOG_QSO_UPLOAD_STATUS' => "Y",
		);

		$this->db->where("station_id", $station_id);
		$this->db->group_start();
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", null);
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "N");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "M");
		$this->db->group_end();
		$this->db->update($this->config->item('table_name'), $data);
	}

	function get_clublog_qsos($station_id) {
		$this->db->select('*, dxcc_entities.name as station_country');
		$this->db->join('station_profile', 'station_profile.station_id = ' . $this->config->item('table_name') . '.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');
		$this->db->where($this->config->item('table_name') . '.station_id', $station_id);
		$this->db->where('station_profile.clublogignore', 0);
		$this->db->group_start();
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", null);
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "M");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "N");
		$this->db->group_end();

		$query = $this->db->get($this->config->item('table_name'));

		return $query;
	}

	function clublog_last_qsl_rcvd_date($callsign) {
		$qso_table_name = $this->config->item('table_name');
		$this->db->from($qso_table_name);

		$this->db->join('station_profile', 'station_profile.station_id = ' . $qso_table_name . '.station_id');
		$this->db->where('station_profile.station_callsign', $callsign);

		$this->db->select("DATE_FORMAT(COL_CLUBLOG_QSO_DOWNLOAD_DATE,'%Y%m%d') AS COL_CLUBLOG_QSO_DOWNLOAD_DATE", FALSE);
		$this->db->where('COL_CLUBLOG_QSO_DOWNLOAD_DATE IS NOT NULL');
		$this->db->where('station_profile.clublogignore', 0);
		$this->db->order_by("COL_CLUBLOG_QSO_DOWNLOAD_DATE", "desc");
		$this->db->limit(1);

		$query = $this->db->get();
		$row = $query->row();

		if (isset($row->COL_CLUBLOG_QSO_DOWNLOAD_DATE)) {
			return $row->COL_CLUBLOG_QSO_DOWNLOAD_DATE;
		} else {
			// No previous date (first time import has run?), so choose UNIX EPOCH!
			// Note: date is yyyy/mm/dd format
			return '19700101';
		}
	}

	function disable_sync4call($call, $stations) {
		if (empty($stations) || trim($stations) === '') {
			return;
		}

		$station_ids = array_filter(explode(",", $stations), function($id) {
			return trim($id) !== '';
		});

		if (empty($station_ids)) {
			return;
		}

		$placeholders = implode(',', array_fill(0, count($station_ids), '?'));
		$sql = "UPDATE station_profile SET clublogignore=1 WHERE station_callsign=? AND station_id IN (" . $placeholders . ")";
		$bindings = array_merge([$call], $station_ids);
		$query = $this->db->query($sql, $bindings);
	}

	function all_enabled($userid) {
		$sql = "select sp.station_callsign, group_concat(sp.station_id) as station_ids from station_profile sp
			inner join users u on (u.user_id=sp.user_id)
			where u.user_clublog_name is not null and u.user_clublog_password is not null
			and trim(u.user_clublog_name) != '' and trim(u.user_clublog_password) != ''
			and sp.clublogignore=0 and u.user_id=?
			group by sp.station_callsign";
		$query = $this->db->query($sql, $userid);
		return $query;
	}

	function all_with_count($userid, $station_id) {
		$this->db->select('station_profile.station_id, station_profile.station_callsign, count(' . $this->config->item('table_name') . '.station_id) as qso_total');
		$this->db->from('station_profile');
		$this->db->join($this->config->item('table_name'), 'station_profile.station_id = ' . $this->config->item('table_name') . '.station_id', 'left');
		$this->db->group_by('station_profile.station_id');
		if ($station_id !== null) {
			$this->db->where('station_profile.station_id', $station_id);
		}
		$this->db->where('station_profile.user_id', $userid);
		$this->db->where('station_profile.clublogignore', 0);
		$this->db->group_start();
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", null);
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "M");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "N");
		$this->db->group_end();

		return $this->db->get();
	}

	function stations_with_clublog_enabled() {
		$bindings=[];
		$sql = "SELECT station_profile.station_id, station_profile.station_profile_name, station_profile.station_callsign, modc.modcount, notc.notcount, totc.totcount
			FROM station_profile
			LEFT OUTER JOIN (
				SELECT count(*) modcount, station_id
				FROM ". $this->config->item('table_name') .
				" WHERE COL_CLUBLOG_QSO_UPLOAD_STATUS = 'M'
				group by station_id
		) as modc on station_profile.station_id = modc.station_id
		LEFT OUTER JOIN (
			SELECT count(*) notcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE (coalesce(COL_CLUBLOG_QSO_UPLOAD_STATUS, '') = ''
			or COL_CLUBLOG_QSO_UPLOAD_STATUS = 'N')
			group by station_id
		) as notc on station_profile.station_id = notc.station_id
		LEFT OUTER JOIN (
			SELECT count(*) totcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE COL_CLUBLOG_QSO_UPLOAD_STATUS = 'Y'
			group by station_id
		) as totc on station_profile.station_id = totc.station_id
		WHERE coalesce(station_profile.clublogignore, 1) = 0
		AND station_profile.user_id = ?";
		$bindings[]=$this->session->userdata('user_id');
		$query = $this->db->query($sql, $bindings);

		return $query;
    }

	function push_qso_to_clublog($cl_username, $cl_password, $station_callsign, $adif, $station_id) {

		// initialise the curl request
		$returner = [];
		$request = curl_init('https://clublog.org/realtime.php');

		curl_setopt($request, CURLOPT_POST, true);
		curl_setopt($request, CURLOPT_TIMEOUT, 10);
		curl_setopt(
			$request,
			CURLOPT_POSTFIELDS,
			array(
				'email' => $cl_username,
				'password' => $cl_password,
				'callsign' => $station_callsign,
				'adif' => $adif,
				'api' => $this->clublog_identifier,
			)
		);

		// output the response
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($request);
		$httpcode = curl_getinfo($request, CURLINFO_HTTP_CODE);

		if (preg_match('/\bOK\b/', $response)) {
			$returner['status'] = 'OK';
		} elseif (preg_match('/\bDupe\b/', $response)) {
			$returner['status'] = 'OK';
		} elseif (preg_match('/\bUpdated QSO\b/', $response)) {
			$returner['status'] = 'OK';
		} elseif ($httpcode == 403) { 	// New Message from clublog. HTTP 403 response check.
			log_message('Error',"Clublog returned HTML error page for " . $station_callsign . " (access denied)");
			$this->disable_sync4call($station_callsign, $station_id);
			$returner['status'] = $response;
		} elseif (substr($response,0,14) == 'Login rejected') {	// Deactivate Upload for Station if Clublog rejects it due to wrong credentials (prevent being blacklisted at Clublog)
			log_message("Error","Clublog deactivated for ".$cl_username." because of wrong creds at Realtime-Pusher");
			$this->disable_sync4call($station_callsign, $station_id);
			$returner['status'] = $response;
		} else {
			log_message("Error","Uncaught exception at ClubLog-RT for ".$cl_username." / Details: ".$httpcode." : ".$response);
			$returner['status'] = $response;
		}
		return ($returner);
	}

	/**
	 * Fetch OQRS requests from ClubLog and mark matching QSOs as requested.
	 *
	 * Gated per user via user_options (clublog/oqrs_sync/enabled). Fetch runs per
	 * station callsign (ClubLog getadif.php, type=dxqsl) using the stored 'lastrun'
	 * date as start filter unless $since_date is given; lastrun is only advanced
	 * when every callsign fetch succeeded, so failed windows are retried on the
	 * next run. The first run has no date filter and backfills the full history.
	 *
	 * @param int    $userid     users.user_id
	 * @param string $username   ClubLog account email
	 * @param string $password   ClubLog account password
	 * @param string $since_date optional manual start date (Y-m-d) overriding lastrun
	 * @return string summary with list of marked QSOs (HTML), empty if disabled or nothing fetched
	 */
	function oqrsUser($userid, $username, $password, $since_date = null) {
		$this->load->model('user_options_model');
		$oqrs_options = array();
		foreach ($this->user_options_model->get_options('clublog', array('option_name' => 'oqrs_sync'), $userid)->result() as $opt) {
			$oqrs_options[$opt->option_key] = $opt->option_value;
		}
		if (($oqrs_options['enabled'] ?? '0') != '1') {
			return '';
		}

		$station_profiles = $this->all_enabled($userid);
		if (!$station_profiles->num_rows()) {
			return '';
		}

		$lastrun = $oqrs_options['lastrun'] ?? '';
		if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$since_date)) {
			$lastrun = $since_date;
		}
		$this->load->library('adif_parser');

		$ok = true;
		$fetched = 0;
		$marked = array();
		$skipped = 0;
		$errors = array();
		foreach ($station_profiles->result() as $station_row) {
			$station_ids = array_filter(array_map('trim', explode(',', $station_row->station_ids)));
			if (empty($station_ids) || $this->db->where_in('station_id', $station_ids)->count_all_results($this->config->item('table_name')) == 0) {
				continue;	// no QSOs for this callsign -> nothing to match, skip fetch
			}

			$post = array('email' => $username, 'password' => $password, 'call' => trim($station_row->station_callsign), 'type' => 'dxqsl');
			if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $lastrun)) {
				$post['startyear'] = substr($lastrun, 0, 4);
				$post['startmonth'] = substr($lastrun, 5, 2);
				$post['startday'] = substr($lastrun, 8, 2);
			}

			$request = curl_init('https://clublog.org/getadif.php');
			curl_setopt($request, CURLOPT_POST, true);
			curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($post));
			curl_setopt($request, CURLOPT_TIMEOUT, 30);
			curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($request);
			$httpcode = curl_getinfo($request, CURLINFO_HTTP_CODE);
			curl_close($request);

			$u = strtoupper((string)$response);
			if ($response === false || $httpcode != 200 || (!str_contains($u, '<EOH>') && !str_contains($u, '<EOR>'))) {
				log_message('error', 'Clublog OQRS fetch failed for ' . $station_row->station_callsign . ' (HTTP ' . $httpcode . '): ' . substr((string)$response, 0, 200));
				$errors[] = $station_row->station_callsign;
				$ok = false;
				continue;
			}

			$parser = new ADIF_Parser();
			$parser->load_from_string($response);
			$parser->initialize();
			while ($record = $parser->get_record()) {
				if (isset($record['call'], $record['qso_date'])) {
					$fetched++;
					$line = $this->oqrs_mark_requested($record, $station_row->station_callsign, $station_ids);
					if ($line !== null) {
						$marked[] = $line;
					} else {
						$skipped++;
					}
				}
			}
			log_message('info', 'Clublog OQRS requests fetched for ' . $station_row->station_callsign);
		}

		if ($ok) {
			$this->user_options_model->set_option('clublog', 'oqrs_sync', array('lastrun' => date('Y-m-d')), $userid);
		}

		$return = '';
		if ($fetched > 0 || !empty($errors)) {
			$return = 'Clublog OQRS: ' . $fetched . ' requests fetched, ' . count($marked) . ' QSOs marked requested, ' . $skipped . ' skipped (already sent/requested or no match)';
			if (!empty($errors)) {
				$return .= '<br>Fetch failed for: ' . html_escape(implode(', ', $errors));
			}
			if (!empty($marked)) {
				$return .= '<br>' . implode('<br>', $marked);
			}
		}
		return $return;
	}

	/**
	 * Mark a QSO referenced by a ClubLog OQRS request as requested ('R') so it
	 * enters the QSL print queue workflow.
	 *
	 * Matching mirrors logbook_model::clublog_update() (call + datetime + band with
	 * unit stripped + station callsign, scoped to the station ids of that callsign).
	 * QSOs already marked sent/requested (COL_QSL_SENT 'Y'/'R') are never touched,
	 * so manual marks survive; such collisions are only logged.
	 *
	 * @param array  $record           parsed ADIF record (lowercase keys) from the ClubLog OQRS export
	 * @param string $station_callsign station callsign the OQRS request belongs to
	 * @param array  $station_ids      station_profile ids sharing that callsign
	 * @return string|null escaped one-line description of the marked QSO, null if nothing was marked
	 */
	function oqrs_mark_requested($record, $station_callsign, $station_ids) {
		if (empty($station_ids)) {
			return null;
		}

		$record['call'] = str_replace('Ø', '0', $record['call'] ?? '');
		$date = date('Y-m-d', strtotime($record['qso_date'] ?? ''));
		$time = trim($record['time_on'] ?? '');
		if ($date == '1970-01-01' || $time == '' || $record['call'] == '' || !isset($record['band'])) {
			return null;
		}
		if (!preg_match('/^(\d{2})(\d{2})/', $time, $m)) {
			return null;
		}
		$time = $m[1] . ':' . $m[2];
		$band = preg_replace('/(cm|m)$/i', '', trim($record['band']));

		$via_raw = strtolower(($record['qsl_via'] ?? '') . ' ' . ($record['app_clublog_qsl_via'] ?? ''));
		$via = str_contains($via_raw, 'direct') ? 'D' : 'B';

		$this->db->set('COL_QSL_SENT', 'R');
		$this->db->set('COL_QSLSDATE', date('Y-m-d H:i:s'));
		$this->db->set('COL_QSL_SENT_VIA', $via);
		$this->db->where('COL_CALL', $record['call']);
		$this->db->where("date_format(COL_TIME_ON, '%Y-%m-%d %H:%i') = ", $date . ' ' . $time);
		$this->db->where("replace(replace(COL_BAND,'cm',''),'m','')", $band);
		$this->db->where('COL_STATION_CALLSIGN', $station_callsign);
		$this->db->where_in('station_id', $station_ids);
		$this->db->where("coalesce(COL_QSL_SENT,'') not in ('Y','R')");
		$this->db->update($this->config->item('table_name'));

		if (!$this->db->affected_rows()) {
			log_message('info', 'Clublog OQRS request for ' . $record['call'] . ' at ' . $date . ' ' . $time . ' ' . $record['band'] . ' matched no QSO or the QSO was already sent/requested.');
			return null;
		}
		return html_escape($record['call'] . ', ' . $date . ' ' . $time . ', ' . $record['band'] . ' (' . ($via == 'D' ? 'direct' : 'bureau') . ')');
	}
}
