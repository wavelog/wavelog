<?php

class Hrdlog_model extends CI_Model {

	function upload() {
		$this->setOptions();

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

		$this->load->model('logbook_model');

		$station_ids = $this->logbook_model->get_station_id_with_hrdlog_code();

		if ($station_ids) {
			foreach ($station_ids as $station) {
				$hrdlog_username = $station->hrdlog_username;
				$hrdlog_code = $station->hrdlog_code;
				$u_result = $this->mass_upload_qsos($station->station_id, $hrdlog_username, $hrdlog_code);
				if ($u_result['count'] > 0) {
					$msg = __("HRDlog: QSOs have been uploaded to hrdlog.net for the station callsign: ").$station->station_callsign." (ID: ".$station->station_id.")";
					echo $msg;
				} else {
					$msg = __("HRDlog: No QSOs found to upload for the station callsign: ").$station->station_callsign." (ID: ".$station->station_id.")";
					echo $msg;
				}
				log_message('debug', $msg);
			}
		} else {
			$msg = __("HRDlog: No station profiles with HRDlog Credentials found.");
			echo $msg;
			log_message('debug', $msg);
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
	 * Function gets all QSOs from given station_id, that are not previously uploaded to hrdlog.
	 * Adif is build for each qso, and then uploaded, one at a time
	 */
	function mass_upload_qsos($station_id, $hrdlog_username, $hrdlog_code) {
		$i = 0;
		$data['qsos'] = $this->logbook_model->get_hrdlog_qsos($station_id);
		$errormessages = array();

		if (!$this->load->is_loaded('AdifHelper')) {
			$this->load->library('AdifHelper');
		}

		if ($data['qsos']) {
			foreach ($data['qsos']->result() as $qso) {
				$adif = $this->adifhelper->getAdifLine($qso);

				if ($qso->COL_HRDLOG_QSO_UPLOAD_STATUS == 'M') {
					$result = $this->logbook_model->push_qso_to_hrdlog($hrdlog_username, $hrdlog_code, $adif, true);
				} else {
					$result = $this->logbook_model->push_qso_to_hrdlog($hrdlog_username, $hrdlog_code, $adif);
				}

				if (($result['status'] == 'OK') || (($result['status'] == 'error') || ($result['status'] == 'duplicate'))) {
					$this->markqso($qso->COL_PRIMARY_KEY);
					$i++;
					$result['status'] = 'OK';
				} elseif ((substr($result['status'], 0, 11)  == 'auth_error')) {
					log_message('error', 'hrdlog upload failed for qso: Call: ' . $qso->COL_CALL . ' Band: ' . $qso->COL_BAND . ' Mode: ' . $qso->COL_MODE . ' Time: ' . $qso->COL_TIME_ON);
					log_message('error', 'hrdlog upload failed with the following message: ' . $result['message']);
					log_message('error', 'hrdlog upload stopped and disabled for Station_ID: ' . $station_id);
					$errormessages[] = $result['message'] . 'Invalid HRDLog-Code, stopped at Call: ' . $qso->COL_CALL . ' Band: ' . $qso->COL_BAND . ' Mode: ' . $qso->COL_MODE . ' Time: ' . $qso->COL_TIME_ON;
					$this->disable_hrdlog_station($station_id);
					$result['status'] = 'Error';
					break; /* If key is invalid, immediate stop syncing for more QSOs of this station */
				} else {
					log_message('error', 'hrdlog upload failed for qso: Call: ' . $qso->COL_CALL . ' Band: ' . $qso->COL_BAND . ' Mode: ' . $qso->COL_MODE . ' Time: ' . $qso->COL_TIME_ON);
					log_message('error', 'hrdlog upload failed with the following message: ' . $result['message']);
					$result['status'] = 'Error';
					$errormessages[] = $result['message'] . ' Call: ' . $qso->COL_CALL . ' Band: ' . $qso->COL_BAND . ' Mode: ' . $qso->COL_MODE . ' Time: ' . $qso->COL_TIME_ON;
				}
			}
			if ($i == 0) {
				$result['status'] = 'Error';
			}
			$result['count'] = $i;
			$result['errormessages'] = $errormessages;
		} else {
			$result['status'] = 'Error';
			$result['count'] = $i;
			$result['errormessages'] = $errormessages;
		}
		return $result;
	}

	function mass_mark_hrdlog_sent($station_id, $from, $till) {
		// Set memory limit to unlimited to allow heavy usage
		ini_set('memory_limit', '-1');

		$this->load->model('adif_data');
		$this->load->model('logbook_model');

		$qsos = $this->adif_data->export_custom($from, $till, $station_id);

		if (isset($qsos)) {
			foreach ($qsos->result() as $qso) {
				$mark_them[]=$qso->COL_PRIMARY_KEY;
			}
			$sql="update ".$this->config->item('table_name')." set COL_HRDLOG_QSO_UPLOAD_DATE='".date("Y-m-d H:i:s", strtotime("now"))."', COL_HRDLOG_QSO_UPLOAD_STATUS='Y'  where COL_HRDLOG_QSO_UPLOAD_STATUS != 'Y' and col_primary_key in (".implode(',', array_values($mark_them)).") and station_id=".$station_id;
			$query = $this->db->query($sql);
			return $this->db->affected_rows();
		}
		return 0;
	}

	function disable_hrdlog_station($station_id) {
		$sql='update station_profile set hrdlogrealtime=-1 where station_id=?';
		$bindings=[$station_id];
		$this->db->query($sql,$bindings);
		return;
	}

	/*
	 * Function marks QSO with given primarykey as uploaded to hrdlog
	 */
	function markqso($primarykey) {
		$this->logbook_model->mark_hrdlog_qsos_sent($primarykey);
	}
}
