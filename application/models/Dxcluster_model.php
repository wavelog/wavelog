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

		// Load cache driver once
		$this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));

		// Check cache first for processed spot list
		$user_id = $this->session->userdata('user_id');
		$logbook_id = $this->session->userdata('active_station_logbook');
		$cache_key = "spotlist_{$band}_{$maxage}_{$de}_{$mode}_{$user_id}_{$logbook_id}";

		// Try to get cached processed results (59 second cache)
		if ($cached_spots = $this->cache->get($cache_key)) {
			return $cached_spots;
		}

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

		if (!$jsonraw = $this->cache->get('dxcache'.$band)) {
			// CURL Functions
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $dxcache_url);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog '.$this->optionslib->get_option('version').' DXLookup');
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$jsonraw = curl_exec($ch);
			$curl_error = curl_error($ch);
			curl_close($ch);

			// Check for curl errors
			if ($curl_error || $jsonraw === false) {
				log_message('error', 'DXCluster: Failed to fetch spots from ' . $dxcache_url . ': ' . $curl_error);
				return [];
			}

			$this->cache->save('dxcache'.$band, $jsonraw, 59);	// Cache DXClusterCache Instancewide for 59seconds
		}

		// Validate JSON before decoding
		if (empty($jsonraw) || strlen($jsonraw) <= 20) {
			return [];
		}

		$json = json_decode($jsonraw);

		// Check for JSON decode errors
		if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
			log_message('error', 'DXCluster: Invalid JSON received: ' . json_last_error_msg());
			return [];
		}
		$date = date('Ymd', time());

		$dxccObj = new DXCC($date);

		// DXCC lookup cache to avoid duplicate lookups
		$dxcc_cache = [];

		$spotsout=[];

		// Cache current time outside loop (avoid creating DateTime on every iteration)
		$currentTimestamp = time();

		// Normalize continent filter once
		$de_lower = strtolower($de);
		$filter_continent = ($de != '' && $de != 'Any');

		foreach($json as $singlespot){
			// Early filtering - skip invalid spots immediately
			if (!is_object($singlespot) || !isset($singlespot->frequency) || !is_numeric($singlespot->frequency)) {
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

			// DXCC lookups with memoization to avoid duplicate lookups
			if (!(property_exists($singlespot,'dxcc_spotted'))) {
				$spotted_call = $singlespot->spotted ?? '';
				if (empty($spotted_call)) {
					continue;
				}
				if (!isset($dxcc_cache[$spotted_call])) {
					$dxcc_cache[$spotted_call] = $dxccObj->dxcc_lookup($spotted_call, $date);
				}
				$dxcc = $dxcc_cache[$spotted_call];
				$singlespot->dxcc_spotted = (object)[
					'dxcc_id' => $dxcc['adif'] ?? 0,
					'cont' => $dxcc['cont'] ?? '',
					'flag' => '',
					'entity' => $dxcc['entity'] ?? 'Unknown'
				];
			}
			if (!(property_exists($singlespot,'dxcc_spotter'))) {
				$spotter_call = $singlespot->spotter ?? '';
				if (empty($spotter_call)) {
					continue;
				}
				if (!isset($dxcc_cache[$spotter_call])) {
					$dxcc_cache[$spotter_call] = $dxccObj->dxcc_lookup($spotter_call, $date);
				}
				$dxcc = $dxcc_cache[$spotter_call];
				$singlespot->dxcc_spotter = (object)[
					'dxcc_id' => $dxcc['adif'] ?? 0,
					'cont' => $dxcc['cont'] ?? '',
					'flag' => '',
					'entity' => $dxcc['entity'] ?? 'Unknown'
				];
			}				// Apply continent filter early
			if ($filter_continent && (!property_exists($singlespot->dxcc_spotter, 'cont') ||
				$de_lower != strtolower($singlespot->dxcc_spotter->cont ?? ''))) {
				continue;
			}

			// Extract park references from message
			$singlespot = $this->enrich_spot_metadata($singlespot);

			// Collect spots for batch processing
			$spotsout[] = $singlespot;
		}


		// Batch process all spot statuses in a single optimized database query
		if (!empty($spotsout)) {
			$batch_statuses = $this->logbook_model->get_batch_spot_statuses(
				$spotsout,
				$logbooks_locations_array,
					$band,
					$mode
				);

				// Collect callsigns that need last_worked info (only those that are worked)
				$worked_callsigns = [];
				foreach ($spotsout as $spot) {
					$callsign = $spot->spotted;
					if (isset($batch_statuses[$callsign]) && $batch_statuses[$callsign]['worked_call']) {
						$worked_callsigns[] = $callsign;
					}
				}

				// Batch fetch last_worked info for all worked callsigns
				$last_worked_batch = [];
				if (!empty($worked_callsigns)) {
					$last_worked_batch = $this->logbook_model->get_batch_last_worked(
						$worked_callsigns,
						$logbooks_locations_array,
						$band
					);
				}

				// Map batch results back to spots
				foreach ($spotsout as $index => $spot) {
					$callsign = $spot->spotted;
					if (isset($batch_statuses[$callsign])) {
						$status = $batch_statuses[$callsign];
						$spot->worked_dxcc = $status['worked_dxcc'];
						$spot->worked_call = $status['worked_call'];
						$spot->cnfmd_dxcc = $status['cnfmd_dxcc'];
						$spot->cnfmd_call = $status['cnfmd_call'];
						$spot->cnfmd_continent = $status['cnfmd_continent'];
						$spot->worked_continent = $status['worked_continent'];

						// Use batch last_worked data
						if ($spot->worked_call && isset($last_worked_batch[$callsign])) {
							$spot->last_wked = $last_worked_batch[$callsign];
							$spot->last_wked->LAST_QSO = date($custom_date_format, strtotime($spot->last_wked->LAST_QSO));
						}
					} else {
						// Fallback for spots without status
						$spot->worked_dxcc = false;
						$spot->worked_call = false;
						$spot->cnfmd_dxcc = false;
						$spot->cnfmd_call = false;
						$spot->cnfmd_continent = false;
						$spot->worked_continent = false;
					}

				$spotsout[$index] = $spot;
			}
		}

		// Cache the processed results for 59 seconds (matches DXCache server TTL)
		if (!empty($spotsout)) {
			$this->cache->save($cache_key, $spotsout, 59);
		}

		return $spotsout;	}

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

	/**
	 * Enrich spot metadata with park references and contest detection
	 * Extracts SOTA/POTA/IOTA/WWFF references and detects contest spots
	 * @param object $spot - Spot object with message and dxcc_spotted properties
	 * @return object - Spot object with added sotaRef, potaRef, iotaRef, wwffRef, isContest properties
	 */
	function enrich_spot_metadata($spot) {
		// Initialize references
		$spot->sotaRef = '';
		$spot->potaRef = '';
		$spot->iotaRef = '';
		$spot->wwffRef = '';
		$spot->isContest = false;

		// First check if references are provided directly in dxcc_spotted
		if (property_exists($spot, 'dxcc_spotted') && is_object($spot->dxcc_spotted)) {
			if (property_exists($spot->dxcc_spotted, 'sota_ref')) {
				$spot->sotaRef = $spot->dxcc_spotted->sota_ref ?? '';
			}
			if (property_exists($spot->dxcc_spotted, 'pota_ref')) {
				$spot->potaRef = $spot->dxcc_spotted->pota_ref ?? '';
			}
			if (property_exists($spot->dxcc_spotted, 'iota_ref')) {
				$spot->iotaRef = $spot->dxcc_spotted->iota_ref ?? '';
			}
			if (property_exists($spot->dxcc_spotted, 'wwff_ref')) {
				$spot->wwffRef = $spot->dxcc_spotted->wwff_ref ?? '';
			}
		}

		// Process message if available
		$message = $spot->message ?? '';
		if (!empty($message)) {
			$upperMessage = strtoupper($message);

			// Extract park references if any are missing
			if (empty($spot->sotaRef) || empty($spot->potaRef) || empty($spot->iotaRef) || empty($spot->wwffRef)) {

				// SOTA format: XX/YY-### or XX/YY-#### (e.g., "G/LD-001", "W4G/NG-001")
				if (empty($spot->sotaRef)) {
					if (preg_match('/\b([A-Z0-9]{1,3}\/[A-Z]{2}-\d{3})\b/', $upperMessage, $sotaMatch)) {
						$spot->sotaRef = $sotaMatch[1];
					}
				}

				// POTA format: XX-#### (e.g., "US-4306", "K-1234")
				// Must not match WWFF patterns (ending in FF)
				if (empty($spot->potaRef)) {
					if (preg_match('/\b([A-Z0-9]{1,5}-\d{4,5})\b/', $upperMessage, $potaMatch)) {
						// Exclude WWFF patterns (contain FF-)
						if (strpos($potaMatch[1], 'FF-') === false) {
							$spot->potaRef = $potaMatch[1];
						}
					}
				}

				// IOTA format: XX-### (e.g., "EU-005", "NA-001", "OC-123")
				if (empty($spot->iotaRef)) {
					if (preg_match('/\b((?:AF|AN|AS|EU|NA|OC|SA)-\d{3})\b/', $upperMessage, $iotaMatch)) {
						$spot->iotaRef = $iotaMatch[1];
					}
				}

				// WWFF format: XXFF-#### (e.g., "GIFF-0001", "K1FF-0123", "ON4FF-0050")
				if (empty($spot->wwffRef)) {
					if (preg_match('/\b([A-Z0-9]{2,4}FF-\d{4})\b/', $upperMessage, $wwffMatch)) {
						$spot->wwffRef = $wwffMatch[1];
					}
				}
			}

			// Detect contest spots
			// Common contest indicators in spot comments
			$contestIndicators = [
				'CONTEST',      // Generic contest mention
				'CQ WW',        // CQ World Wide
				'CQ WPX',       // CQ WPX
				'ARRL',         // ARRL contests
				'IARU',         // IARU HF Championship
				'CQWW',         // CQ WW (no space)
				'CQWPX',        // CQ WPX (no space)
				'SWEEPSTAKES',  // ARRL Sweepstakes
				'FIELD DAY',    // ARRL Field Day
				'DX CONTEST',   // Generic DX contest
				'SSB CONTEST',  // SSB contest
				'CW CONTEST',   // CW contest
				'RTTY CONTEST', // RTTY contest
				'VHF CONTEST',  // VHF contest
				'SPRINT',       // Various sprints
				'DXCC',         // DXCC operations
				'WAE',          // Worked All Europe
				'IOTA CONTEST', // IOTA contest
				'NAQP',         // North American QSO Party
				'BARTG',        // BARTG contests
				'RSGB',         // RSGB contests
				'RUNDSPRUCH',   // German contests
				'JARTS',        // JARTS contests
				'CW OPEN',      // CW Open
				'SSB OPEN',     // SSB Open
				'EU CONTEST',   // European contests
				'NA CONTEST',   // North American contests
				'KING OF SPAIN', // King of Spain contest
				'ALL ASIAN',    // All Asian contest
			];

			// Check if message contains any contest indicators
			foreach ($contestIndicators as $indicator) {
				if (strpos($upperMessage, $indicator) !== false) {
					$spot->isContest = true;
					break;
				}
			}

			// Additional heuristic: Check for typical contest exchange patterns
			// Example: "599 025" or "59 123" or "5NN K" (common contest exchanges)
			if (!$spot->isContest) {
				// Match RST + serial number patterns
				if (preg_match('/\b(599|59|5NN)\s+[0-9A-Z]{2,4}\b/', $upperMessage)) {
					$spot->isContest = true;
				}
				// Match zone/state exchanges like "CQ 14" or "CQ K"
				if (preg_match('/\bCQ\s+[0-9A-Z]{1,3}\b/', $upperMessage)) {
					$spot->isContest = true;
				}
			}
		}

		return $spot;
	}
}
