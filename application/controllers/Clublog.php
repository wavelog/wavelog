<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller to interact with the Clublog API
*/

class Clublog extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
            echo "Maintenance Mode is active. Try again later.\n";
			redirect('user/login');
		}
	}

	// Show frontend if there is one
	public function index() {
		$this->config->load('config');
	}

	// Upload ADIF to Clublog
	public function upload() {
		$this->load->model('clublog_model');

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class.'_'.$this->router->method);

		$users = $this->clublog_model->get_clublog_users();

		foreach ($users as $user) {
			$this->uploadUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password);
		}
	}

	// Download ADIF from Clublog
	public function download() {
		$this->load->model('clublog_model');

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class.'_'.$this->router->method);

		$users = $this->clublog_model->get_clublog_users();

		foreach ($users as $user) {
			$this->downloadUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password);
		}
	}

	function downloadUser($userid, $username, $password) {
		$clean_username = $this->security->xss_clean($username);
		$clean_password = $this->security->xss_clean($password);
		$clean_userid = $this->security->xss_clean($userid);

		$this->config->load('config');
		ini_set('memory_limit', '-1');
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		$this->load->helper('file');

		$this->load->model('clublog_model');
		$this->load->model('logbook_model');

		$station_profiles = $this->clublog_model->all_enabled($clean_userid);	// Fetch unique Calls per User with aggregated station_ids
		if ($station_profiles->num_rows()) {
			foreach ($station_profiles->result() as $station_row) {
				$lastrec=$this->clublog_model->clublog_last_qsl_rcvd_date($station_row->station_callsign);
				$url='https://clublog.org/getmatches.php?api=608df94896cb9c5421ae748235492b43815610c9&email='.$clean_username.'&password='.$clean_password.'&callsign='.$station_row->station_callsign.'&startyear='.substr($lastrec,0,4).'&startmonth='.substr($lastrec,4,2).'&startday='.substr($lastrec,6,2);
				$request = curl_init($url);

				// recieve a file
				curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
				$response = curl_exec($request);
				$info = curl_getinfo($request);
				curl_close ($request);
				if(curl_errno($request)) {
					echo curl_error($request);
				} elseif (preg_match_all('/Invalid callsign/',$response)) {	// We're trying to download calls for a station we're not granted. Disable Clublog-Transfer for that station(s)
					$this->clublog_model->disable_sync4call($station_row->station_callsign,$station_row->station_ids);
				} else {
					try {
						$cl_qsls=json_decode($response);
						foreach ($cl_qsls as $oneqsl) {
							$this->logbook_model->clublog_update($oneqsl[2], $oneqsl[0], $oneqsl[3], 'Y', $station_row->station_callsign, $station_row->station_ids);
						}
					} catch (Exception $e) {
						log_message("Error","Something gone wrong while trying to Download for station(s) ".$station_row->station_ids." / Call: ".$station_row->station_callsign);
					}
				}

			}
		}
	}

	function uploadUser($userid, $username, $password) {
		$clean_username = $this->security->xss_clean($username);
		$clean_passord = $this->security->xss_clean($password);
		$clean_userid = $this->security->xss_clean($userid);

		$this->config->load('config');
		ini_set('memory_limit', '-1');
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);

		$this->load->helper('file');

		$this->load->model('clublog_model');

		$station_profiles = $this->clublog_model->all_with_count($clean_userid);

		if($station_profiles->num_rows()){
			foreach ($station_profiles->result() as $station_row)
			{
				if($station_row->qso_total > 0) {
					$data['qsos'] = $this->clublog_model->get_clublog_qsos($station_row->station_id);

					if($data['qsos']->num_rows()){
						$string = $this->load->view('adif/data/clublog', $data, TRUE);

						$ranid = uniqid();

						if ( ! write_file('uploads/clublog'.$ranid.$station_row->station_id.'.adi', $string)) {
						     echo 'Unable to write the file - Make the folder Upload folder has write permissions.';
						}
						else {

							$file_info = get_file_info('uploads/clublog'.$ranid.$station_row->station_id.'.adi');

							// initialise the curl request
							$request = curl_init('https://clublog.org/putlogs.php');

							if($this->config->item('directory') != "") {
								 $filepath = $_SERVER['DOCUMENT_ROOT']."/".$this->config->item('directory')."/".$file_info['server_path'];
							} else {
								 $filepath = $_SERVER['DOCUMENT_ROOT']."/".$file_info['server_path'];
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
							    ));

							// output the response
							curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
							$response = curl_exec($request);
							$info = curl_getinfo($request);

							if(curl_errno($request)) {
							    echo curl_error($request);
							}
							curl_close ($request);


							// If Clublog Accepts mark the QSOs
							if (preg_match('/\baccepted\b/', $response)) {
								echo "QSOs uploaded and Logbook QSOs marked as sent to Clublog"."<br>";
								$this->load->model('clublog_model');
								$this->clublog_model->mark_qsos_sent($station_row->station_id);
								echo "Clublog upload for ".$station_row->station_callsign."<br>";
								log_message('info', 'Clublog upload for '.$station_row->station_callsign.' successfully sent.');
							} else if (preg_match('/checksum duplicate/',$response)) {
								echo "QSOs uploaded (asduplicate!) and Logbook QSOs marked as sent to Clublog"."<br>";
								$this->load->model('clublog_model');
								$this->clublog_model->mark_qsos_sent($station_row->station_id);
								echo "Clublog upload for ".$station_row->station_callsign."<br>";
								log_message('info', 'Clublog DUPLICATE upload for '.$station_row->station_callsign.' successfully sent.');
							} else {
								echo "Error ".$response."<br />";
								log_message('error', 'Clublog upload for '.$station_row->station_callsign.' failed reason '.$response);
							}

							// Delete the ADIF file used for clublog
							unlink('uploads/clublog'.$ranid.$station_row->station_id.'.adi');

						}

					} else {
						echo "Nothing awaiting upload to clublog for ".$station_row->station_callsign."<br>";

						log_message('info', 'Nothing awaiting upload to clublog for '.$station_row->station_callsign);
					}
				}
			}
		}
	}

	function markqso($station_id) {
		$clean_station_id = $this->security->xss_clean($station_id);
		$this->load->model('clublog_model');
		$this->clublog_model->mark_qsos_sent($clean_station_id);
	}

	// Find DXCC
	function find_dxcc($callsign) {
		$clean_callsign = $this->security->xss_clean($callsign);
		// Live lookup against Clublogs API
		$url = "https://clublog.org/dxcc?call=".$clean_callsign."&api=608df94896cb9c5421ae748235492b43815610c9&full=1";

		$json = file_get_contents($url);
		$data = json_decode($json, TRUE);

		// echo ucfirst(strtolower($data['Name']));
		return $data;
	}
}
