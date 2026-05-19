<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller to upload QSOs to a QRZCALL.EU logbook.

	QRZCALL.EU exposes a QRZ-compatible logbook endpoint, so this mirrors the
	QRZ.com upload controller (Qrz.php) — upload paths only. QRZCALL.EU has no
	QSL download, so there is no download/mark functionality here.

	Note: the class is named Qrzcallupload (not Qrzcall) so it does not clash
	with the existing callbook library application/libraries/Qrzcall.php.
 */

class Qrzcallupload extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
	}

	// Show frontend if there is one
	public function index() {
		$this->config->load('config');
	}

	/*
	 * API Token Status Test — POSTs ACTION=STATUS to the QRZCALL.EU endpoint
	 */
	public function qrzcall_apitest() {
		$apikey = xss_clean($this->input->post('APIKEY'));
		$url = 'https://api.qrzcall.eu/v1/pub/logbook_api.php';

		$post_data['KEY'] = $apikey;
		$post_data['ACTION'] = 'STATUS';

		$ch = curl_init( $url );
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 20);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Wavelog/'.$this->optionslib->get_option('version'));

		$content = curl_exec($ch);

		if ($content){
			if (stristr($content,'RESULT=OK')) {
				$result['status'] = 'OK';
				$result['message'] = $content;
			}
			else {
				$result['status'] = 'Failed';
				$result['message'] = $content;
			}
		}
		if(curl_errno($ch)){
			$result['status'] = 'error';
			$result['message'] = 'Curl error: '. curl_errno($ch);
		}
		header('Content-Type: application/json');
		echo json_encode($result);
	}

	/*
	 * Upload QSOs to QRZCALL.EU.
	 * When called from the url wavelog/qrzcallupload/upload (cron), the
	 * function loops through every station_id with a QRZCALL.EU token defined.
	 * All QSOs not previously uploaded are then uploaded, one at a time.
	 */
	public function upload() {
		$this->setOptions();

		// set the last run in cron table for the qrzcall_upload cron
		$this->load->model('cron_model');
		$this->cron_model->set_last_run('qrzcall_upload');

		$this->load->model('logbook_model');

		$station_ids = $this->logbook_model->get_station_id_with_qrzcall_api();

		if ($station_ids) {
			foreach ($station_ids as $station) {
				$qrzcall_api_key = $station->qrzcallapikey;
				if ($station->qrzcallrealtime>=0) {
					if($this->mass_upload_qsos($station->station_id, $qrzcall_api_key, true)) {
						echo "QSOs have been uploaded to QRZCALL.EU for station_id ".$station->station_id;
					} else{
						echo "No QSOs found for upload and station_id ".$station->station_id;
					}
				} else {
					echo "Station ".$station->station_id." disabled for upload to QRZCALL.EU.";
				}
			}
		} else {
			echo "No station profiles with a QRZCALL.EU API Token found.";
			log_message('error', "No station profiles with a QRZCALL.EU API Token found.");
		}
	}

	function setOptions() {
		$this->config->load('config');
		ini_set('memory_limit', '-1');
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		error_reporting(E_ALL);
	}

	/*
	 * Gets all QSOs from the given station_id that have not been uploaded to
	 * QRZCALL.EU. An ADIF line is built for each QSO and uploaded one at a time.
	 */
	function mass_upload_qsos($station_id, $qrzcall_api_key, $trusted = false) {
		$i = 0;
		$data['qsos'] = $this->logbook_model->get_qrzcall_qsos($station_id, $trusted);
		$errormessages=array();

		if (!$this->load->is_loaded('AdifHelper')) {
			$this->load->library('AdifHelper');
		}

		if ($data['qsos']) {
			foreach ($data['qsos']->result() as $qso) {
				$adif = $this->adifhelper->getAdifLine($qso);

				// A QSO previously uploaded then edited is marked 'M' — re-upload with REPLACE.
				if ($qso->COL_QRZCALL_QSO_UPLOAD_STATUS == 'M') {
					$result = $this->logbook_model->push_qso_to_qrzcall($qrzcall_api_key, $adif, true);
				} else {
					$result = $this->logbook_model->push_qso_to_qrzcall($qrzcall_api_key, $adif);
				}

				if ( ($result['status'] == 'OK') || ( ($result['status'] == 'error') && (stristr($result['message'],'RESULT=FAIL') && stristr($result['message'],'duplicate'))) ) {
					// Uploaded, or already present (duplicate) — both count as done.
					$this->markqso($qso->COL_PRIMARY_KEY);
					$i++;
					$result['status'] = 'OK';
				} elseif ( ($result['status']=='error') && (stristr($result['message'],'RESULT=AUTH')) ) {
					// Bad/expired token — stop and disable upload for this station.
					log_message('error', 'QRZCALL.EU upload failed (auth) for Station_ID '.$station_id.' // Message: '.$result['message']);
					$errormessages[] = $result['message'] . ' Call: ' . $qso->COL_CALL . ' Band: ' . $qso->COL_BAND . ' Mode: ' . $qso->COL_MODE . ' Time: ' . $qso->COL_TIME_ON;
					$result['status'] = 'Error';
					$this->db->query('update station_profile set qrzcallrealtime = -1 where station_id = ?', $station_id);
					break;
				} else {
					log_message('error', 'QRZCALL.EU upload failed for qso: Call: ' . $qso->COL_CALL . ' Band: ' . $qso->COL_BAND . ' Mode: ' . $qso->COL_MODE . ' Time: ' . $qso->COL_TIME_ON);
					log_message('error', 'QRZCALL.EU upload failed with the following message: ' .$result['message']);
					$errormessages[] = $result['message'] . ' Call: ' . $qso->COL_CALL . ' Band: ' . $qso->COL_BAND . ' Mode: ' . $qso->COL_MODE . ' Time: ' . $qso->COL_TIME_ON;
					$result['status'] = 'Error';
				}
			}
			if ($i == 0) {
				$result['status']='OK';
			}
			$result['count'] = $i;
			$result['errormessages'] = $errormessages;
			return $result;
		} else {
			$result['status'] = 'Error';
			$result['count'] = $i;
			$result['errormessages'] = $errormessages;
			return $result;
		}
	}

	/*
	 * Marks the QSO with the given primarykey as uploaded to QRZCALL.EU
	 */
	function markqso($primarykey,$state = 'Y') {
		$this->logbook_model->mark_qrzcall_qsos_sent($primarykey, $state);
	}

	/*
	 * Renders the QRZCALL.EU Logbook upload page
	 */
	public function export() {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('stations');

		$data['page_title'] = __("QRZCALL.EU Logbook");

		$data['station_profile'] = $this->stations->stations_with_qrzcall_api_key();
		$this->load->model('cron_model');
		$data['next_run_up'] = $this->cron_model->get_next_run("qrzcall_upload");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('qrzcall/export');
		$this->load->view('interface_assets/footer');
	}

	/*
	 * AJAX handler — manual "Upload" button for a single station profile
	 */
	public function upload_station() {
		$this->setOptions();
		$this->load->model('stations');

		$postData = $this->input->post();

		$this->load->model('logbook_model');
		$result = $this->logbook_model->exists_qrzcall_api_key($postData['station_id']);
		$qrzcall_api_key = $result->qrzcallapikey;
		$qrzcall_enabled = $result->qrzcallrealtime;
		header('Content-type: application/json');
		if ($qrzcall_enabled>=0) {
			$result = $this->mass_upload_qsos($postData['station_id'], $qrzcall_api_key);
			if ($result['status'] == 'OK') {
				$stationinfo = $this->stations->stations_with_qrzcall_api_key();
				$info = $stationinfo->result();

				$data['status'] = 'OK';
				$data['info'] = $info;
				$data['infomessage'] = $result['count'] . " QSOs are now uploaded to QRZCALL.EU";
				$data['errormessages'] = $result['errormessages'];
				echo json_encode($data);
			} else {
				$data['status'] = 'Error';
				$data['info'] = 'Error: No QSOs found to upload.';
				$data['errormessages'] = $result['errormessages'];
				echo json_encode($data);
			}
		} else {
			$profile = $this->stations->profile($this->security->xss_clean($postData['station_id']))->row()->station_profile_name;
			$data['status']='Error';
			$data['info']='QRZCALL.EU upload disabled for station profile:'.' '.$profile;
			echo json_encode($data);
		}
	}

}
