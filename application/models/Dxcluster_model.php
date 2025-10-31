<?php

use Wavelog\Dxcc\Dxcc;

class Dxcluster_model extends CI_Model {

 	protected $bandedges = [];

	public function __construct() {
		$this->load->Model('Modes');
		$this->db->where('bandedges.userid', $this->session->userdata('user_id'));
		$query = $this->db->get('bandedges');
		$result = $query->result_array();

		if ($result) {
			$this->bandedges = $result;
		} else {
			// Load bandedges into a class property
			$this->db->where('userid', -1);
			$query = $this->db->get('bandedges');
			$this->bandedges = $query->result_array();
		}
	}

	public function dxc_spotlist($band = '20m', $maxage = 60, $de = '', $mode = 'All') {
		$this->load->helper(array('psr4_autoloader'));

		if($this->session->userdata('user_date_format')) {
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$dxcache_url = ($this->optionslib->get_option('dxcache_url') == '' ? 'https://dxc.jo30.de/dxcache' : $this->optionslib->get_option('dxcache_url'));

		if ($band == "All") {
			$dxcache_url = $dxcache_url . '/spots/';
		} else {
			$dxcache_url = $dxcache_url . '/spots/'.$band;
		}
		// $this->load->model('logbooks_model');  lives in the autoloader
		$this->load->model('logbook_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
		if (!$jsonraw = $this->cache->get('dxcache'.$band)) {
			// CURL Functions
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $dxcache_url);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog '.$this->optionslib->get_option('version').' DXLookup');
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$jsonraw = curl_exec($ch);
			curl_close($ch);
			$this->cache->save('dxcache'.$band, $jsonraw, 59);	// Cache DXClusterCache Instancewide for 59seconds
		}
		$json = json_decode($jsonraw);
		$date = date('Ymd', time());

		$dxccObj = new DXCC($date);

		// Create JSON object
		if (strlen($jsonraw)>20) {
			$spotsout=[];

			// Cache current time outside loop (avoid creating DateTime on every iteration)
			$currentTimestamp = time();

			// Normalize continent filter once
			$de_lower = strtolower($de);
			$filter_continent = ($de != '' && $de != 'Any');

			foreach($json as $singlespot){
				// Early filtering - skip invalid spots immediately
				if (!is_numeric($singlespot->frequency)) {
					continue;
				}

				$spotband = $this->frequency->GetBand($singlespot->frequency*1000);

				// Apply band filter early (before expensive operations)
				if (($band != 'All') && ($band != $spotband)) {
					continue;
				}

				$singlespot->band = $spotband;
				$singlespot->mode = $this->get_mode($singlespot);

				// Apply mode filter early
				if (($mode != 'All') && ($mode != $this->modefilter($singlespot, $mode))) {
					continue;
				}

				// Faster age calculation using timestamps instead of DateTime objects
				$spotTimestamp = strtotime($singlespot->when);
				$minutes = (int)(($currentTimestamp - $spotTimestamp) / 60);

				// Apply age filter early (before DXCC lookups)
				if ($minutes > $maxage) {
					continue;
				}

				$singlespot->age = $minutes;
				$singlespot->when_pretty = date($custom_date_format . " H:i", $spotTimestamp);

				// DXCC lookups (only for spots that passed all filters)
				if (!(property_exists($singlespot,'dxcc_spotted'))) {
					$dxcc = $dxccObj->dxcc_lookup($singlespot->spotted, $date);
					$singlespot->dxcc_spotted = (object)[
						'dxcc_id' => $dxcc['adif'],
						'cont' => $dxcc['cont'],
						'flag' => '',
						'entity' => $dxcc['entity']
					];
				}
				if (!(property_exists($singlespot,'dxcc_spotter'))) {
					$dxcc = $dxccObj->dxcc_lookup($singlespot->spotter, $date);
					$singlespot->dxcc_spotter = (object)[
						'dxcc_id' => $dxcc['adif'],
						'cont' => $dxcc['cont'],
						'flag' => '',
						'entity' => $dxcc['entity']
					];
				}

				// Apply continent filter early
				if ($filter_continent && (!property_exists($singlespot->dxcc_spotter, 'cont') ||
					$de_lower != strtolower($singlespot->dxcc_spotter->cont ?? ''))) {
					continue;
				}

				// Database queries only for spots that passed all filters
				$singlespot->worked_dxcc = ($this->logbook_model->check_if_dxcc_worked_in_logbook($singlespot->dxcc_spotted->dxcc_id, $logbooks_locations_array, $singlespot->band, $singlespot->mode) >= 1);
				$singlespot->worked_call = ($this->logbook_model->check_if_callsign_worked_in_logbook($singlespot->spotted, $logbooks_locations_array, $singlespot->band, $singlespot->mode) >= 1);
				$singlespot->cnfmd_dxcc = ($this->logbook_model->check_if_dxcc_cnfmd_in_logbook($singlespot->dxcc_spotted->dxcc_id, $logbooks_locations_array, $singlespot->band, $singlespot->mode) >= 1);
				$singlespot->cnfmd_call = ($this->logbook_model->check_if_callsign_cnfmd_in_logbook($singlespot->spotted, $logbooks_locations_array, $singlespot->band, $singlespot->mode) >= 1);
				$singlespot->cnfmd_continent = ($this->check_if_continent_cnfmd_in_logbook($singlespot->dxcc_spotted->cont, $logbooks_locations_array, $singlespot->band, $singlespot->mode) >= 1);
				$singlespot->worked_continent = ($this->check_if_continent_worked_in_logbook($singlespot->dxcc_spotted->cont, $logbooks_locations_array, $singlespot->band, $singlespot->mode) >= 1);

				if ($singlespot->worked_call) {
					$singlespot->last_wked = $this->logbook_model->last_worked_callsign_in_logbook($singlespot->spotted, $logbooks_locations_array, $singlespot->band)[0];
					$singlespot->last_wked->LAST_QSO = date($custom_date_format, strtotime($singlespot->last_wked->LAST_QSO));
				}

				$spotsout[] = $singlespot; // Direct array append is faster than array_push
			}
			return ($spotsout);
		} else {
			return '';
		}

	}

	// We need to build functions that check the frequency limit
	// Right now this is just a proof of concept to determine mode
	function get_mode($spot) {
		if ($this->Frequency2Mode($spot->frequency) != '') {
			return $this->Frequency2Mode($spot->frequency);
		}

		// Fallbacks using message keywords
		if (isset($spot->message)) {
			$message = strtolower($spot->message);
			if (strpos($message, 'cw') !== false) {
				return 'cw';;
			}
			if ((strpos($message, 'ft8') !== false || strpos($message, 'rtty') !== false || strpos($message, 'sstv') !== false)) {
				return 'digi';;
			}
		}

		return '';
	}

	function modefilter($spot, $mode) {
		$mode = strtolower($mode); // Normalize case

		if ($this->isFrequencyInMode($spot->frequency, $mode)) {
			return true;
		}

		// Fallbacks using message keywords
		if (isset($spot->message)) {
			$message = strtolower($spot->message);
			if ($mode === 'cw' && strpos($message, 'cw') !== false) {
				return true;
			}
			if ($mode === 'digi' && (strpos($message, 'ft8') !== false || strpos($message, 'rtty') !== false || strpos($message, 'sstv') !== false)) {
				return true;
			}
		}

		return false;
	}

	public function Frequency2Mode($frequency) {
		// Ensure frequency is in Hz if input is in kHz
		if ($frequency < 1_000_000) {
			$frequency *= 1000;
		}

		foreach ($this->bandedges as $band) {
			if ($frequency >= $band['frequencyfrom'] && $frequency < $band['frequencyto']) {
				return $band['mode'];
			}
		}
		return '';
	}

	public function isFrequencyInMode($frequency, $mode) {
		// Ensure frequency is in Hz if input is in kHz
		if ($frequency < 1_000_000) {
			$frequency *= 1000;
		}

		foreach ($this->bandedges as $band) {
			if (strtolower($band['mode']) === strtolower($mode)) {
				if ($frequency >= $band['frequencyfrom'] && $frequency < $band['frequencyto']) {
					return true;
				}
			}
		}

		return false;
	}

    public function dxc_qrg_lookup($qrg, $maxage = 120) {
		$this->load->helper(array('psr4_autoloader'));
	    if (is_numeric($qrg)) {

			$dxcache_url = ($this->optionslib->get_option('dxcache_url') == '' ? 'https://dxc.jo30.de/dxcache' : $this->optionslib->get_option('dxcache_url'));

		    $dxcache_url = $dxcache_url .'/spot/'.$qrg;

		    // CURL Functions
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $dxcache_url);
		    curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog '.$this->optionslib->get_option('version').' DXLookup by QRG');
		    curl_setopt($ch, CURLOPT_HEADER, false);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    $jsonraw = curl_exec($ch);
		    curl_close($ch);
		    $json = json_decode($jsonraw);

			$date = date('Ymd', time());

			$dxccObj = new DXCC($date);

		    // Create JSON object
			if (strlen($jsonraw)>20) {
			    $datetimecurrent = new DateTime("now", new DateTimeZone('UTC')); // Today's Date/Time
			    $datetimespot = new DateTime($json->when, new DateTimeZone('UTC'));
			    $spotage = $datetimecurrent->diff($datetimespot);
			    $minutes = $spotage->days * 24 * 60;
			    $minutes += $spotage->h * 60;
			    $minutes += $spotage->i;
			    $json->age=$minutes;
			    if ($minutes<=$maxage) {
				    $dxcc=$dxccObj->dxcc_lookup($json->spotter,date('Ymd', time()));
				    $json->dxcc_spotter=$dxcc;
				    return ($json);
			    } else {
				    return '';
			    }
		    } else {
			    return '';
		    }
	    }
    }

	function check_if_continent_worked_in_logbook($cont, $StationLocationsArray = null, $band = null, $mode = null) {

		if ($StationLocationsArray == null) {
			$this->load->model('logbooks_model');
			$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		} else {
			$logbooks_locations_array = $StationLocationsArray;
		}

		$this->db->select('COL_CONT');
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->where('COL_CONT', $cont);

		if (isset($mode)) {
			$this->db->where(" COL_MODE in ".$this->Modes->get_modes_from_qrgmode($mode,true));
		}

		$band = ($band == 'All') ? null : $band;
		if ($band != null && $band != 'SAT') {
			$this->db->where('COL_BAND', $band);
		} else if ($band == 'SAT') {
			// Where col_sat_name is not empty
			$this->db->where('COL_SAT_NAME !=', '');
		}
		$this->db->limit('2');

		$query = $this->db->get($this->config->item('table_name'));
		return $query->num_rows();
	}

	function check_if_continent_cnfmd_in_logbook($cont, $StationLocationsArray = null, $band = null, $mode = null) {

		if ($StationLocationsArray == null) {
			$this->load->model('logbooks_model');
			$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		} else {
			$logbooks_locations_array = $StationLocationsArray;
		}

		$user_default_confirmation = $this->session->userdata('user_default_confirmation');
		$extrawhere = '';
		if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
			$extrawhere = "COL_QSL_RCVD='Y'";
		}
		if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
			if ($extrawhere != '') {
				$extrawhere .= " OR";
			}
			$extrawhere .= " COL_LOTW_QSL_RCVD='Y'";
		}
		if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
			if ($extrawhere != '') {
				$extrawhere .= " OR";
			}
			$extrawhere .= " COL_EQSL_QSL_RCVD='Y'";
		}

		if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
			if ($extrawhere != '') {
				$extrawhere .= " OR";
			}
			$extrawhere .= " COL_QRZCOM_QSO_DOWNLOAD_STATUS='Y'";
		}


		$this->db->select('COL_CONT');
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->where('COL_CONT', $cont);

		if (isset($mode)) {
			$this->db->where(" COL_MODE in ".$this->Modes->get_modes_from_qrgmode($mode,true));
		}

		$band = ($band == 'All') ? null : $band;
		if ($band != null && $band != 'SAT') {
			$this->db->where('COL_BAND', $band);
		} else if ($band == 'SAT') {
			// Where col_sat_name is not empty
			$this->db->where('COL_SAT_NAME !=', '');
		}
		if ($extrawhere != '') {
			$this->db->where('(' . $extrawhere . ')');
		} else {
			$this->db->where("1=0");
		}
		$this->db->limit('2');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->num_rows();
	}
}
