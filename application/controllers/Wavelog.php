<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*	Controller to interact with the Wavelog API */
class Wavelog extends CI_Controller {

	/*
	 * Upload QSO to Wavelog
	 * When called from the url wavelog/upload, the function loops through all station_id's with a Wavelog
	 * api key defined.
	 * All QSOs not previously uploaded, will then be uploaded, one at a time
	 */
	public function upload() {
		$this->setOptions();

		$this->load->model('logbook_model');

		$station_ids = $this->logbook_model->get_station_id_with_wavelog_api();

		if ($station_ids) {
			foreach ($station_ids as $station) {
				$wavelog_apikey = $station->wavelog_apikey;
				$wavelog_apiurl = $station->wavelog_apiurl;
				$wavelog_profileid = $station->wavelog_profileid;
				if ($this->mass_upload_qsos($station->station_id, $wavelog_apikey, $wavelog_apiurl, $wavelog_profileid, true)) {	// When called via cron it is trusted
					echo "QSOs have been uploaded to upstream Wavelog.";
					log_message('info', 'QSOs have been uploaded to upstream Wavelog.');
				} else {
					echo "No QSOs found for upload.";
					log_message('info', 'No QSOs found for upload.');
				}
			}
		} else {
			echo "No station profiles with a Wavelog API Key found.";
			log_message('error', "No station profiles with a Wavelog API Key found.");
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
	 * Function gets all QSOs from given station_id, that are not previously uploaded to Wavelog consumer.
	 * Adif is build for each qso, and then uploaded, one at a time
	 */
	function mass_upload_qsos($station_id, $wavelog_apikey, $wavelog_apiurl, $wavelog_profileid, $trusted = false) {
		$i = 0;
		$data['qsos'] = $this->logbook_model->get_wavelog_qsos($station_id, null, null, $trusted);
		$errormessages=array();

		if (!$this->load->is_loaded('AdifHelper')) {
			$this->load->library('AdifHelper');
		}

		if ($data['qsos']) {
			foreach ($data['qsos']->result() as $qso) {
				$adif = $this->adifhelper->getAdifLine($qso);
				$result = $this->logbook_model->push_qso_to_wavelog($wavelog_apiurl, $wavelog_apikey, $wavelog_profileid, $adif);

				if ($result) {
					$this->logbook_model->mark_wavelog_qsos_sent([$qso->COL_PRIMARY_KEY]);
					$i++;
				} else {
					$errorMessage = 'Wavelog upload failed for qso: Call: ' . $qso->COL_CALL . ' Band: ' . $qso->COL_BAND . ' Mode: ' . $qso->COL_MODE . ' Time: ' . $qso->COL_TIME_ON;
					log_message('error', $errorMessage);
					$errormessages[] = $errorMessage;
				}
			}
			$result=[];
			$result['status'] = 'OK';
			$result['count'] = $i;
			$result['errormessages'] = $errormessages;
			return $result;
		} else {
			$result=[];
			$result['status'] = 'Error';
			$result['count'] = $i;
			$result['errormessages'] = $errormessages;
			return $result;
		}
	}

	/*
	 * Used for displaying the uid for manually selecting log for upload to Wavelog consumer
	 */
	public function export() {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('stations');

		$data['page_title'] = __("Wavelog Upload");

		$data['station_profiles'] = $this->stations->stations_with_wavelog_api_key();
		$data['station_profile'] = $this->stations->stations_with_wavelog_api_key();

		$this->load->view('interface_assets/header', $data);
		$this->load->view('wavelog/export');
		$this->load->view('interface_assets/footer');
	}

	/*
	 * Used for ajax-function when selecting log for upload to Wavelog consumer
	 */
	public function upload_station() {
		$this->setOptions();
		$postData = $this->input->post();
		$this->load->model('stations');
		if (!$this->stations->check_station_is_accessible($postData['station_id'])) {
			return;
		}

		$this->load->model('logbook_model');
		$result = $this->logbook_model->exists_wavelog_api_key($postData['station_id']);
		$wavelog_apikey = $result->wavelog_apikey;
		$wavelog_apiurl = $result->wavelog_apiurl;
		$wavelog_profileid = $result->wavelog_profileid;
		header('Content-type: application/json');
		$result = $this->mass_upload_qsos($postData['station_id'], $wavelog_apikey, $wavelog_apiurl, $wavelog_profileid);
		if ($result['status'] == 'OK') {
			$stationinfo = $this->stations->stations_with_wavelog_api_key();
			$info = $stationinfo->result();

			$data['status'] = 'OK';
			$data['info'] = $info;
			$data['infomessage'] = $result['count'] . " QSOs are now uploaded to upstream Wavelog";
			$data['errormessages'] = $result['errormessages'];
			echo json_encode($data);
		} else {
			$data['status'] = 'Error';
			$data['info'] = 'Error: No QSOs found to upload.';
			$data['errormessages'] = $result['errormessages'];
			echo json_encode($data);
		}
	}

	public function mark_wavelog() {
		// Set memory limit to unlimited to allow heavy usage
		ini_set('memory_limit', '-1');
		$data['page_title'] = __("Upstream Wavelog Upload");

		$station_id = $this->security->xss_clean($this->input->post('station_profile'));
		$from = $this->security->xss_clean($this->input->post('from'));
		$to = $this->security->xss_clean($this->input->post('to'));

		$this->load->model('logbook_model');

		$data['qsos'] = $this->logbook_model->get_wavelog_qsos(
			$station_id,
			$from,
			$to
		);

		if ($data['qsos']!==null) {
			$qsoIDs=[];
			foreach ($data['qsos']->result() as $qso) {
				$qsoIDs[]=$qso->COL_PRIMARY_KEY;
			}
			$batchSize = 500;
			while ($qsoIDs !== []) {
				$slice = array_slice($qsoIDs, 0, $batchSize);
				$qsoIDs = array_slice($qsoIDs, $batchSize);
				$this->logbook_model->mark_wavelog_qsos_sent($slice);
			}
		}

		$this->load->view('interface_assets/header', $data);
		$this->load->view('wavelog/mark_wavelog', $data);
		$this->load->view('interface_assets/footer');
	}
}
