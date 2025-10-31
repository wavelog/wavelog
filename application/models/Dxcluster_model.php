<?php

use Wavelog\Dxcc\Dxcc;

class Dxcluster_model extends CI_Model {

 	protected $bandedges = [];

	// Contest indicators - moved to class property to avoid recreation on every call
	protected $contestIndicators = [
		'CONTEST', 'CQ WW', 'CQ WPX', 'ARRL', 'IARU', 'CQWW', 'CQWPX',
		'SWEEPSTAKES', 'FIELD DAY', 'DX CONTEST', 'SSB CONTEST', 'CW CONTEST',
		'RTTY CONTEST', 'VHF CONTEST', 'SPRINT', 'DXCC', 'WAE', 'IOTA CONTEST',
		'NAQP', 'BARTG', 'RSGB', 'RUNDSPRUCH', 'JARTS', 'CW OPEN', 'SSB OPEN',
		'EU CONTEST', 'NA CONTEST', 'KING OF SPAIN', 'ALL ASIAN'
	];

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
	 * Only performs regex extraction if references are not already provided by DX cluster
	 * @param object $spot - Spot object with message and dxcc_spotted properties
	 * @return object - Spot object with enriched dxcc_spotted containing references and isContest flag
	 */
	function enrich_spot_metadata($spot) {
		// Ensure dxcc_spotted object exists
		if (!property_exists($spot, 'dxcc_spotted') || !is_object($spot->dxcc_spotted)) {
			$spot->dxcc_spotted = (object)[];
		}

		// Initialize all properties at once using array merge
		$defaults = [
			'sota_ref' => '',
			'pota_ref' => '',
			'iota_ref' => '',
			'wwff_ref' => '',
			'isContest' => false
		];

		foreach ($defaults as $prop => $defaultValue) {
			if (!property_exists($spot->dxcc_spotted, $prop)) {
				$spot->dxcc_spotted->$prop = $defaultValue;
			}
		}

		// Early exit if message is empty
		$message = $spot->message ?? '';
		if (empty($message)) {
			return $spot;
		}

		$upperMessage = strtoupper($message);

		// Check which references are missing to minimize regex executions
		$needsSota = empty($spot->dxcc_spotted->sota_ref);
		$needsPota = empty($spot->dxcc_spotted->pota_ref);
		$needsIota = empty($spot->dxcc_spotted->iota_ref);
		$needsWwff = empty($spot->dxcc_spotted->wwff_ref);

		// Early exit if all references already populated
		if (!$needsSota && !$needsPota && !$needsIota && !$needsWwff && $spot->dxcc_spotted->isContest) {
			return $spot;
		}

		// Combined regex approach - execute all patterns in one pass if any are needed
		if ($needsSota || $needsPota || $needsIota || $needsWwff) {
			// SOTA format: XX/YY-### or XX/YY-#### (e.g., "G/LD-001", "W4G/NG-001", "DL/KW-044")
			if ($needsSota && preg_match('/\b([A-Z0-9]{1,3}\/[A-Z]{2}-\d{3,4})\b/', $upperMessage, $sotaMatch)) {
				$spot->dxcc_spotted->sota_ref = $sotaMatch[1];
			}

			// IOTA format: XX-### (e.g., "EU-005", "NA-001", "OC-123")
			// Check IOTA before POTA as it's more specific
			if ($needsIota && preg_match('/\b((?:AF|AN|AS|EU|NA|OC|SA)-\d{3})\b/', $upperMessage, $iotaMatch)) {
				$spot->dxcc_spotted->iota_ref = $iotaMatch[1];
			}

			// WWFF format: XXFF-#### or KFF-#### (e.g., "GIFF-0001", "K1FF-0123", "ON4FF-0050", "KFF-6731")
			// Check WWFF before POTA to avoid conflicts
			if ($needsWwff && preg_match('/\b((?:[A-Z0-9]{2,4}FF|KFF)-\d{4})\b/', $upperMessage, $wwffMatch)) {
				$spot->dxcc_spotted->wwff_ref = $wwffMatch[1];
			}

			// POTA format: XX-#### (e.g., "US-4306", "K-1234", "DE-0277")
			// Must not match WWFF patterns (ending in FF) - checked last to avoid conflicts
			if ($needsPota && preg_match('/\b([A-Z0-9]{1,5}-\d{4,5})\b/', $upperMessage, $potaMatch)) {
				// Exclude WWFF patterns (contain FF-)
				if (strpos($potaMatch[1], 'FF-') === false) {
					$spot->dxcc_spotted->pota_ref = $potaMatch[1];
				}
			}
		}

		// Contest detection - use class property instead of creating array each time
		if (!$spot->dxcc_spotted->isContest) {
			// Check for contest keywords using optimized strpbrk-like approach
			foreach ($this->contestIndicators as $indicator) {
				if (strpos($upperMessage, $indicator) !== false) {
					$spot->dxcc_spotted->isContest = true;
					return $spot; // Early exit once contest detected
				}
			}

			// Additional heuristic: Check for typical contest exchange patterns
			// Match RST + serial number patterns OR zone/state exchanges in single regex
			if (preg_match('/\b(?:(?:599|59|5NN)\s+[0-9A-Z]{2,4}|CQ\s+[0-9A-Z]{1,3})\b/', $upperMessage)) {
				$spot->dxcc_spotted->isContest = true;
			}
		}

		return $spot;
	}
}
