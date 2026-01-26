<?php

use Wavelog\Dxcc\Dxcc;

class Dxcluster_model extends CI_Model {

 	protected $bandedges = [];

	// Contest indicators - moved to class property to avoid recreation on every call
	// Contest indicators - ORDER MATTERS! More specific names must come before generic terms
	// to ensure accurate matching (e.g., "HAM SPIRIT" before "CONTEST")
	protected $contestIndicators = [
		'HAM SPIRIT', 'HAMSPIRIT', 'CQ WW', 'CQ WPX', 'ARRL', 'IARU', 'CQWW', 'CQWPX',
		'SWEEPSTAKES', 'FIELD DAY', 'DX CONTEST', 'SSB CONTEST', 'CW CONTEST',
		'RTTY CONTEST', 'VHF CONTEST', 'SPRINT', 'DXCC', 'WAE', 'IOTA CONTEST',
		'NAQP', 'BARTG', 'RSGB', 'RUNDSPRUCH', 'JARTS', 'CW OPEN', 'SSB OPEN',
		'EU CONTEST', 'NA CONTEST', 'KING OF SPAIN', 'ALL ASIAN', 'CONTEST'
	];

	// Digital modes for submode detection
	// Note: Order matters! More specific modes (PSK31, PSK63) must come before generic (PSK)
	// to ensure accurate submode detection via strpos() matching
	protected $digitalModes = [
		'FT8', 'FT4', 'RTTY', 'PSK31', 'PSK63', 'PSK', 'SSTV', 'MFSK',
		'OLIVIA', 'CONTESTIA', 'JT65', 'JT9', 'WSPR', 'HELL', 'THOR',
		'DOMINO', 'MT63', 'PACTOR', 'MSK144', 'Q65', 'JS8', 'FSK441',
		'ISCAT', 'JT6M', 'FST4', 'FST4W', 'FREEDV', 'VARA'
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

	// Main function to get spot list from DXCache and process it
	public function dxc_spotlist($band = '20m', $maxage = 60, $de = '', $mode = 'All') {
		$this->load->helper(array('psr4_autoloader'));

		// Check if file caching is enabled in config
		$cache_band_enabled = $this->config->item('enable_dxcluster_file_cache_band') === true;
		$cache_worked_enabled = $this->config->item('enable_dxcluster_file_cache_worked') === true;

		// Only load cache driver if caching is enabled
		if ($cache_band_enabled || $cache_worked_enabled) {
			$this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));

			// Garbage collection: 1% chance to clean expired cache files
			// Only needed when worked cache is enabled (creates many per-callsign files)
			if ($cache_worked_enabled) {
				$this->load->library('DxclusterCache');
				$this->dxclustercache->maybeRunGc();
			}
		}

		if($this->session->userdata('user_date_format')) {
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$dxcache_url = ($this->optionslib->get_option('dxcache_url') == '' ? 'https://dxc.wavelog.org/dxcache' : $this->optionslib->get_option('dxcache_url'));

		if ($band == "All") {
			$dxcache_url = $dxcache_url . '/spots/';
		} else {
			$dxcache_url = $dxcache_url . '/spots/'.$band;
		}

		$this->load->model('logbook_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		// Cache key for RAW cluster response (instance-wide, no worked status)
		// Use DxclusterCache library for centralized key generation
		$this->load->library('DxclusterCache');
		$raw_cache_key = $this->dxclustercache->getRawCacheKey($maxage, $band);

		// Check cache for raw processed spots (without worked status)
		$spotsout = null;
		if ($cache_band_enabled) {
			$spotsout = $this->cache->get($raw_cache_key);
		}

		if (!$spotsout) {
			// Fetch raw DX cluster data from API
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $dxcache_url);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog '.$this->optionslib->get_option('version').' DXLookup');
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$jsonraw = curl_exec($ch);
			$curl_error = curl_error($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			// Check for curl errors
			if ($curl_error || $jsonraw === false) {
				log_message('error', 'DXCluster: Failed to fetch spots from ' . $dxcache_url . ': ' . $curl_error);
				return [];
			}

			// Check HTTP status code
			if ($http_code !== 200) {
				$sample = substr($jsonraw, 0, 500);
				log_message('error', 'DXCluster: HTTP error ' . $http_code . ' from ' . $dxcache_url . '. Response: ' . $sample);
				return [];
			}

			// Validate JSON before decoding
			if (empty($jsonraw) || strlen($jsonraw) <= 20) {
				return [];
			}

			$json = json_decode($jsonraw);
			$json_error = json_last_error();

			// Check for JSON decode errors or unexpected data type
			if ($json_error !== JSON_ERROR_NONE) {
				// Malformed JSON - log error with sample of received data
				$sample = substr($jsonraw, 0, 500);
				log_message('error', 'DXCluster: Malformed JSON received from ' . $dxcache_url . ' - ' . json_last_error_msg() . '. Data sample: ' . $sample);
				return [];
			}

			if (!is_array($json)) {
				// Valid JSON but not an array - log what we received
				$sample = substr($jsonraw, 0, 500);
				$received_type = is_object($json) ? 'object' : gettype($json);
				log_message('error', 'DXCluster: Expected array but received ' . $received_type . ' from ' . $dxcache_url . '. Data: ' . $sample);
				return [];
			}

			// Check if array is empty
			if (empty($json)) {
				log_message('debug', 'DXCluster: Empty array received from ' . $dxcache_url . ' (no spots available)');
				return [];
			}
			$date = date('Ymd', time());

			$dxccObj = new DXCC($date);

			// DXCC lookup cache to avoid duplicate lookups
			$dxcc_cache = [];

			$spotsout=[];

			// Cache current time outside loop (avoid creating DateTime on every iteration)
			$currentTimestamp = time();

			foreach($json as $singlespot){
				// Early filtering - skip invalid spots immediately
				if (!is_object($singlespot) || !isset($singlespot->frequency) || !is_numeric($singlespot->frequency)) {
					continue;
				}

				// Ensure frequency is always a number (not a string)
				$singlespot->frequency = floatval($singlespot->frequency);

				// Validate against amateur band allocations (skip non-amateur frequencies)
				if (!$this->isFrequencyInAmateurBand($singlespot->frequency)) {
					continue;
				}

				$spotband = $this->frequency->GetBand($singlespot->frequency*1000);			// Apply band filter early (before expensive operations)
			if (($band != 'All') && ($band != $spotband)) {
				continue;
			}

			$singlespot->band = $spotband;

			// Only determine mode if not provided by cluster
			if (!isset($singlespot->mode) || empty($singlespot->mode)) {
				$singlespot->mode = $this->get_mode($singlespot);
			} else {
				// Normalize cluster-provided mode to lowercase
				$singlespot->mode = strtolower($singlespot->mode);
			}

			// Only determine submode if not provided by cluster
			if (!isset($singlespot->submode) || empty($singlespot->submode)) {
				$singlespot->submode = $this->get_submode($singlespot);
			} else {
				// Normalize cluster-provided submode to uppercase
				$singlespot->submode = strtoupper($singlespot->submode);
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

			// Perform DXCC lookups using cached results to prevent redundant database queries
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
					'cqz' => $dxcc['cqz'] ?? '',
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
					'cqz' => $dxcc['cqz'] ?? '',
					'flag' => '',
					'entity' => $dxcc['entity'] ?? 'Unknown'
				];
			}

			// Extract park references from message
			$singlespot = $this->enrich_spot_metadata($singlespot);

				// Collect spots for batch processing
				$spotsout[] = $singlespot;
			}

			// Cache the RAW processed spots (WITHOUT worked status) - instance-wide
			if ($cache_band_enabled && !empty($spotsout)) {
				$this->cache->save($raw_cache_key, $spotsout, 59);
			}
		}

		// Apply user-specific filters AFTER cache retrieval (mode & continent)
		if (!empty($spotsout) && ($mode != 'All' || ($de != '' && $de != 'Any'))) {
			$de_lower = strtolower($de);
			$filter_continent = ($de != '' && $de != 'Any');
			$spotsout = array_filter($spotsout, function($spot) use ($mode, $de_lower, $filter_continent) {
				if ($mode != 'All' && !$this->modefilter($spot, $mode)) return false;
				if ($filter_continent && ($de_lower != strtolower($spot->dxcc_spotter->cont ?? ''))) return false;
				return true;
			});
			$spotsout = array_values($spotsout); // Re-index array
		}

		// NOW add worked status if enabled (user-specific)
		if (!empty($spotsout)) {
			$batch_statuses = $this->logbook_model->get_batch_spot_statuses(
				$spotsout,
				$logbooks_locations_array,
				$band,
				$mode
			);

			// Collect callsigns that need last_worked info (only those that are worked)
			$worked_spots = [];
			foreach ($spotsout as $spot) {
				$callsign = $spot->spotted;
				if (isset($batch_statuses[$callsign]) && $batch_statuses[$callsign]['worked_call']) {
					$worked_spots[] = $spot;
				}
			}

			// Batch fetch last_worked info for all worked spots (with their specific bands)
			$last_worked_batch = [];
			if (!empty($worked_spots)) {
				$last_worked_batch = $this->logbook_model->get_batch_last_worked(
					$worked_spots,
					$logbooks_locations_array
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

					// Validate and convert date safely to prevent epoch date (1970) issues
					if (!empty($spot->last_wked->LAST_QSO)) {
						$timestamp = strtotime($spot->last_wked->LAST_QSO);
						// Check if strtotime succeeded and timestamp is valid (> 0)
						if ($timestamp !== false && $timestamp > 0) {
							$spot->last_wked->LAST_QSO = date($custom_date_format, $timestamp);
						} else {
							// Invalid date - remove last_wked to prevent displaying incorrect date
							unset($spot->last_wked);
						}
					} else {
						// Empty date - remove last_wked
						unset($spot->last_wked);
					}
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
		} else {
			// No worked status check - set all to false
			foreach ($spotsout as $index => $spot) {
				$spot->worked_dxcc = false;
				$spot->worked_call = false;
				$spot->cnfmd_dxcc = false;
				$spot->cnfmd_call = false;
				$spot->cnfmd_continent = false;
				$spot->worked_continent = false;
				$spotsout[$index] = $spot;
			}
		}

		return $spotsout;
	}	// Determine mode with priority: POTA/SOTA mode > message keywords > frequency-based
	function get_mode($spot) {
		// Priority 0: If spot already has a valid mode from cluster, use it
		if (isset($spot->mode) && !empty($spot->mode)) {
			$existingMode = strtolower($spot->mode);
			// Validate it's a known mode category
			if (in_array($existingMode, ['cw', 'phone', 'digi', 'ssb'])) {
				return $this->mapToModeCategory($existingMode);
			}
		}

		// Priority 1: POTA/SOTA mode fields (if present) - check from both dxcc_spotted and direct properties
		$potaMode = $spot->pota_mode ?? $spot->dxcc_spotted->pota_mode ?? null;
		$sotaMode = $spot->sota_mode ?? $spot->dxcc_spotted->sota_mode ?? null;

		if (!empty($potaMode)) {
			return $this->mapToModeCategory($potaMode);
		}
		if (!empty($sotaMode)) {
			return $this->mapToModeCategory($sotaMode);
		}

		// Priority 2: Message keywords (explicit mode in message text)
		if (isset($spot->message)) {
			$message = strtolower($spot->message);

			// Check for CW first (simplest check)
			if (strpos($message, 'cw') !== false) {
				return 'cw';
			}

			// Check for digital modes using class property
			foreach ($this->digitalModes as $digiMode) {
				if (strpos($message, strtolower($digiMode)) !== false) {
					return 'digi';
				}
			}
		}

		// Priority 3: Frequency-based mode (from bandedges table)
		// If frequency falls within a defined band edge, use that mode
		$frequencyMode = $this->Frequency2Mode($spot->frequency);
		if ($frequencyMode != '') {
			return $frequencyMode;
		}

		// Default fallback: phone
		return 'phone';
	}

	// Map specific mode names to mode categories (phone/cw/digi)
	function mapToModeCategory($mode) {
		$modeUpper = strtoupper($mode);

		// CW modes
		if ($modeUpper === 'CW') {
			return 'cw';
		}

		// Digital modes - check against class property
		foreach ($this->digitalModes as $digiMode) {
			if ($modeUpper === $digiMode) {
				return 'digi';
			}
		}

		// Phone modes
		if (in_array($modeUpper, ['SSB', 'LSB', 'USB', 'AM', 'FM', 'PHONE'])) {
			return 'phone';
		}

		// Default to phone if unknown
		return 'phone';
	}

	// Determine submode for more specific mode classification
	function get_submode($spot) {
		// Priority 0: If spot already has a valid submode from cluster, use it
		if (isset($spot->submode) && !empty($spot->submode)) {
			return strtoupper($spot->submode);
		}

		$mode = strtolower($spot->mode ?? '');
		$frequency = floatval($spot->frequency);

		// Check if we have specific mode from POTA/SOTA - use that as submode
		$potaMode = $spot->pota_mode ?? $spot->dxcc_spotted->pota_mode ?? null;
		$sotaMode = $spot->sota_mode ?? $spot->dxcc_spotted->sota_mode ?? null;

		// If POTA/SOTA provides generic "SSB", refine it to LSB/USB based on frequency
		if (!empty($potaMode) && strtoupper($potaMode) !== 'SSB') {
			return strtoupper($potaMode);
		}
		if (!empty($sotaMode) && strtoupper($sotaMode) !== 'SSB') {
			return strtoupper($sotaMode);
		}

		// For phone modes (including generic SSB from POTA/SOTA), determine LSB or USB based on frequency
		if ($mode === 'phone' || $mode === 'ssb') {
			// Below 10 MHz use LSB, above use USB
			return $frequency < 10000 ? 'LSB' : 'USB';
		}

		// For CW, return CW
		if ($mode === 'cw') {
			return 'CW';
		}

		// For digital modes, try to get specific mode from message
		if ($mode === 'digi') {
			if (isset($spot->message)) {
				$message = strtoupper($spot->message);
				// Check for specific digital modes using class property
				foreach ($this->digitalModes as $digiMode) {
					if (strpos($message, $digiMode) !== false) {
						return $digiMode;
					}
				}
			}
			return 'DIGI'; // Generic digital fallback
		}

		// Return uppercase version of mode as submode
		return strtoupper($mode);
	}

	function modefilter($spot, $mode) {
		$mode = strtolower($mode); // Normalize case
		$spotMode = strtolower($spot->mode ?? ''); // Get already-determined mode

		// Since get_mode() already determined the mode using priority logic
		// (frequency > POTA/SOTA > message), we can directly compare
		return $spotMode === $mode;
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

	/**
	 * Check if frequency falls within amateur band allocations
	 * @param float $frequency Frequency in Hz
	 * @return bool True if frequency is in amateur band
	 */
	public function isFrequencyInAmateurBand($frequency) {
		// Ensure frequency is in Hz if input is in kHz
		if ($frequency < 1_000_000) {
			$frequency *= 1000;
		}

		// Check against bandedges table (already loaded in constructor)
		foreach ($this->bandedges as $band) {
			if ($frequency >= $band['frequencyfrom'] && $frequency < $band['frequencyto']) {
				return true;
			}
		}
		return false;
	}

    public function dxc_qrg_lookup($qrg, $maxage = 120) {
		$this->load->helper(array('psr4_autoloader'));
	    if (is_numeric($qrg)) {

			$dxcache_url = ($this->optionslib->get_option('dxcache_url') == '' ? 'https://dxc.wavelog.org/dxcache' : $this->optionslib->get_option('dxcache_url'));

		    $dxcache_url = $dxcache_url .'/spot/'.$qrg;

		    // CURL Functions
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $dxcache_url);
		    curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog '.$this->optionslib->get_option('version').' DXLookup by QRG');
		    curl_setopt($ch, CURLOPT_HEADER, false);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    $jsonraw = curl_exec($ch);
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
			'isContest' => false,
			'contestName' => null
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
		$hasContestData = property_exists($spot->dxcc_spotted, 'isContest');

		// Early exit if all references already populated and contest data exists
		if (!$needsSota && !$needsPota && !$needsIota && !$needsWwff && $hasContestData) {
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
			// More strict contest detection - require clear indicators

			// Method 1: Explicit contest keywords with word boundaries
			foreach ($this->contestIndicators as $indicator) {
			// Use word boundary to avoid matching "CQ DX" in "CQ DX Americas" (which is just a CQ call)
			if (preg_match('/\b' . preg_quote($indicator, '/') . '\b/', $upperMessage)) {
				// Additional check: avoid false positives from generic "CQ" messages
				if ($indicator === 'DX CONTEST' && preg_match('/^CQ\s+DX\s+[A-Z]+$/i', trim($message))) {
				continue; // Skip "CQ DX <region>" patterns
			}
			$spot->dxcc_spotted->isContest = true;
			$spot->dxcc_spotted->contestName = $indicator;
			return $spot;
			}
		}			// Method 2: Contest exchange pattern - must have RST AND serial AND no conversational words
			// Exclude spots with conversational indicators (TU, TNX, 73, GL, etc.)
			$conversational = '/\b(TU|TNX|THANKS|73|GL|HI|FB|CUL|HPE|PSE|DE)\b/';

			if (!preg_match($conversational, $upperMessage)) {
			// Look for typical contest exchange: RST + number (but not just any 599)
			// Must be followed by more structured exchange (not just "ur 599")
			if (preg_match('/\b(?:599|5NN)\s+(?:TU\s+)?[0-9]{2,4}\b/', $upperMessage) &&
				!preg_match('/\bUR\s+599\b/', $upperMessage)) {
				$spot->dxcc_spotted->isContest = true;
				$spot->dxcc_spotted->contestName = '';
				return $spot;
			}
			}
		}

		return $spot;
	}
}
