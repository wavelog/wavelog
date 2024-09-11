<?php

class Clublog_model extends CI_Model
{

	function get_clublog_users() {
		$this->db->select('user_clublog_name, user_clublog_password, user_id');
		$this->db->where('coalesce(user_clublog_name, "") != ""');
		$this->db->where('coalesce(user_clublog_password, "") != ""');
		$query = $this->db->get($this->config->item('auth_table'));
		return $query->result();
	}

	function uploadUser($userid, $username, $password) {
		$clean_username = $this->security->xss_clean($username);
		$clean_passord = $this->security->xss_clean($password);
		$clean_userid = $this->security->xss_clean($userid);

		$return = "No QSO's to upload";

		$this->config->load('config');

		ini_set('memory_limit', '-1');
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		$this->load->helper('file');
		if (!$this->load->is_loaded('AdifHelper')) {
			$this->load->library('AdifHelper');
		}

		$station_profiles = $this->all_with_count($clean_userid);

		if ($station_profiles->num_rows()) {
			foreach ($station_profiles->result() as $station_row) {
				if ($station_row->qso_total > 0) {
					$data['qsos'] = $this->get_clublog_qsos($station_row->station_id);

					if ($data['qsos']->num_rows()) {
						$string = $this->load->view('adif/data/clublog', $data, TRUE);

						$ranid = uniqid();

						if (!write_file('uploads/clublog' . $ranid . $station_row->station_id . '.adi', $string)) {
							$return = 'Unable to write the file - Make the folder Upload folder has write permissions.';
						} else {

							$file_info = get_file_info('uploads/clublog' . $ranid . $station_row->station_id . '.adi');

							// initialise the curl request
							$request = curl_init('https://clublog.org/putlogs.php');

							if ($this->config->item('directory') != "") {
								$filepath = $_SERVER['DOCUMENT_ROOT'] . "/" . $this->config->item('directory') . "/" . $file_info['server_path'];
							} else {
								$filepath = $_SERVER['DOCUMENT_ROOT'] . "/" . $file_info['server_path'];
							}

							if (function_exists('curl_file_create')) { // php 5.5+
								$cFile = curl_file_create($filepath);
							} else { //
								$cFile = '@' . realpath($filepath);
							}

							// send a file
							curl_setopt($request, CURLOPT_POST, true);
							curl_setopt(
								$request,
								CURLOPT_POSTFIELDS,
								array(
									'email' => $clean_username,
									'password' => $clean_passord,
									'callsign' => $station_row->station_callsign,
									'api' => "608df94896cb9c5421ae748235492b43815610c9",
									'file' => $cFile
								)
							);

							// output the response
							curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
							$response = curl_exec($request);
							$info = curl_getinfo($request);

							if (curl_errno($request)) {
								$return =  curl_error($request);
							}
							curl_close($request);


							// If Clublog Accepts mark the QSOs
							if (preg_match('/\baccepted\b/', $response)) {
								$return =  "QSOs uploaded and Logbook QSOs marked as sent to Clublog";
								$this->mark_qsos_sent($station_row->station_id);
								$return =  "Clublog upload for " . $station_row->station_callsign;
								log_message('info', 'Clublog upload for ' . $station_row->station_callsign . ' successfully sent.');
							} else if (preg_match('/checksum duplicate/', $response)) {
								$return =  "QSOs uploaded (asduplicate!) and Logbook QSOs marked as sent to Clublog";
								$this->mark_qsos_sent($station_row->station_id);
								$return =  "Clublog upload for " . $station_row->station_callsign;
								log_message('info', 'Clublog DUPLICATE upload for ' . $station_row->station_callsign . ' successfully sent.');
							} else {
								$return =  "Error " . $response;
								log_message('error', 'Clublog upload for ' . $station_row->station_callsign . ' failed reason ' . $response);
								if (substr($response,0,13) == 'Upload denied') {	// Deactivate Upload for Station if Clublog rejects it due to non-configured Call (prevent being blacklisted at Clublog)
        								$sql = 'update station_profile set clublogignore = 1 where station_id = ?';
        								$this->db->query($sql,$station_row->station_id);
								}
								if (substr($response,0,14) == 'Login rejected') {	// Deactivate Upload for Station if Clublog rejects it due to wrong credentials (prevent being blacklisted at Clublog)
        								$sql = 'update station_profile set clublogignore = 1 where station_id = ?';
        								$this->db->query($sql,$station_row->station_id);
								}
							}

							// Delete the ADIF file used for clublog
							unlink('uploads/clublog' . $ranid . $station_row->station_id . '.adi');
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

	function downloadUser($userid, $username, $password) {
		$clean_username = $this->security->xss_clean($username);
		$clean_password = $this->security->xss_clean($password);
		$clean_userid = $this->security->xss_clean($userid);

		$return = "Nothing to download";

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
				$lastrec = $this->clublog_last_qsl_rcvd_date($station_row->station_callsign);
				$url = 'https://clublog.org/getmatches.php?api=608df94896cb9c5421ae748235492b43815610c9&email=' . $clean_username . '&password=' . $clean_password . '&callsign=' . $station_row->station_callsign . '&startyear=' . substr($lastrec, 0, 4) . '&startmonth=' . substr($lastrec, 4, 2) . '&startday=' . substr($lastrec, 6, 2);
				$request = curl_init($url);

				// recieve a file
				curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($request);
				$info = curl_getinfo($request);
				curl_close($request);

				if (curl_errno($request)) {
					$return = curl_error($request);
				} elseif (preg_match_all('/Login rejected/', $response)) {
					$this->disable_sync4call($station_row->station_callsign, $station_row->station_ids);
					$return = "Wrong Clublog username and password for Callsign: '" . $station_row->station_callsign . "'. 'LOGIN REJECTED'.";
					log_message('debug', $return);
				} elseif (preg_match_all('/Invalid callsign/', $response)) {	// We're trying to download calls for a station we're not granted. Disable Clublog-Transfer for that station(s)
					$this->disable_sync4call($station_row->station_callsign, $station_row->station_ids);
					$return = "The callsign '" . $station_row->station_callsign . "' does not match the user account at Clublog. 'INVALID CALLSIGN'.";
					log_message('debug', $return);
				} else {
					try {
						$cl_qsls = json_decode($response);
						foreach ($cl_qsls as $oneqsl) {
							$this->logbook_model->clublog_update($oneqsl[2], $oneqsl[0], $oneqsl[3], 'Y', $station_row->station_callsign, $station_row->station_ids);
						}
					} catch (Exception $e) {
						$return = "Something gone wrong while trying to Download for station(s) " . $station_row->station_ids . " / Call: " . $station_row->station_callsign;
						log_message("error", $return);
					}

					$return = "QSO's for Callsign: '" . $station_row->station_callsign . "' were successfully downloaded";
					log_message('info', $return);
				}
			}
		}

		return $return . "\n";
	}

	function mark_qsos_sent($station_id) {
		$data = array(
			'COL_CLUBLOG_QSO_UPLOAD_DATE' => date('Y-m-d'),
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

	function mark_qso_sent($qso_id) {
		$data = array(
			'COL_CLUBLOG_QSO_UPLOAD_DATE' => date('Y-m-d'),
			'COL_CLUBLOG_QSO_UPLOAD_STATUS' => "Y",
		);

		$this->db->where("COL_PRIMARY_KEY", $qso_id);
		$this->db->update($this->config->item('table_name'), $data);
	}

	function get_last_five($station_id) {
		$this->db->where('station_id', $station_id);
		$this->db->group_start();
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", null);
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "");
		$this->db->or_where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "N");
		$this->db->group_end();
		$this->db->limit(5);
		$query = $this->db->get($this->config->item('table_name'));

		return $query;
	}

	function mark_all_qsos_notsent($station_id) {
		$data = array(
			'COL_CLUBLOG_QSO_UPLOAD_DATE' => null,
			'COL_CLUBLOG_QSO_UPLOAD_STATUS' => "M",
			'COL_CLUBLOG_QSO_UPLOAD_STATUS' => "N",
		);

		$this->db->where("station_id", $station_id);
		$this->db->where("COL_CLUBLOG_QSO_UPLOAD_STATUS", "Y");
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
		$sql = "update station_profile set clublogignore=1 where station_callsign=? and station_id in (" . $stations . ")";
		$query = $this->db->query($sql, $call);
	}

	function all_enabled($userid) {
		$sql = "select sp.station_callsign, group_concat(sp.station_id) as station_ids from station_profile sp 
			inner join users u on (u.user_id=sp.user_id)
			where u.user_clublog_name is not null and u.user_clublog_password is not null and sp.clublogignore=0 and u.user_id=?
			group by sp.station_callsign";
		$query = $this->db->query($sql, $userid);
		return $query;
	}

	function all_with_count($userid) {
		$this->db->select('station_profile.station_id, station_profile.station_callsign, count(' . $this->config->item('table_name') . '.station_id) as qso_total');
		$this->db->from('station_profile');
		$this->db->join($this->config->item('table_name'), 'station_profile.station_id = ' . $this->config->item('table_name') . '.station_id', 'left');
		$this->db->group_by('station_profile.station_id');
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
}
