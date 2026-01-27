<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Wavelog\Dxcc\Dxcc;

require_once APPPATH . '../src/Dxcc/Dxcc.php';

/**
 * DXCluster Cache Library
 * Centralizes cache key generation and invalidation for DXCluster features.
 */
class DxclusterCache {

	protected $CI;

	public function __construct() {
		$this->CI =& get_instance();
	}

	// =========================================================================
	// CACHE KEY GENERATION
	// =========================================================================

	/**
	 * Generate RAW spot cache key (instance-wide, shared by all users)
	 */
	public function getRawCacheKey($maxage, $band) {
		return "dxcluster_raw_{$maxage}_{$band}_Any_All";
	}

	/**
	 * Generate logbook IDs key component (user-specific)
	 */
	public function getLogbookKey($user_id, $logbook_ids, $confirmation_prefs) {
		$logbook_ids_str = implode('_', $logbook_ids);
		$confirmation_hash = md5($confirmation_prefs);
		return "{$user_id}_{$logbook_ids_str}_{$confirmation_hash}";
	}

	/**
	 * Generate WORKED callsign cache key
	 */
	public function getWorkedCallKey($logbook_key, $callsign) {
		return "dxcluster_worked_call_{$logbook_key}_{$callsign}";
	}

	/**
	 * Generate WORKED DXCC cache key
	 */
	public function getWorkedDxccKey($logbook_key, $dxcc) {
		return "dxcluster_worked_dxcc_{$logbook_key}_{$dxcc}";
	}

	/**
	 * Generate WORKED continent cache key
	 */
	public function getWorkedContKey($logbook_key, $cont) {
		return "dxcluster_worked_cont_{$logbook_key}_{$cont}";
	}

	// =========================================================================
	// CACHE INVALIDATION
	// =========================================================================

	/**
	 * Invalidate cache after QSO add/edit/delete for current user
	 * @param string $callsign - The worked callsign
	 */
	public function invalidateForCallsign($callsign) {
		// Skip if worked cache is disabled
		if ($this->CI->config->item('enable_dxcluster_file_cache_worked') !== true) return;

		if (empty($callsign)) return;

		// Get current user's logbook key
		$logbook_key = $this->getCurrentUserLogbookKey();
		if (empty($logbook_key)) return;

		// Delete callsign cache
		$this->deleteFile($this->getWorkedCallKey($logbook_key, $callsign));

		// Look up DXCC and continent from callsign
		$dxccobj = new Dxcc(null);
		$dxcc_info = $dxccobj->dxcc_lookup($callsign, date('Y-m-d'));

		if (!empty($dxcc_info['adif'])) {
			$this->deleteFile($this->getWorkedDxccKey($logbook_key, $dxcc_info['adif']));
		}
		if (!empty($dxcc_info['cont'])) {
			$this->deleteFile($this->getWorkedContKey($logbook_key, $dxcc_info['cont']));
		}
	}

	/**
	 * Invalidate all worked cache for current user (bulk operations)
	 */
	public function invalidateAllWorkedForCurrentUser() {
		// Skip if worked cache is disabled
		if ($this->CI->config->item('enable_dxcluster_file_cache_worked') !== true) return;

		$logbook_key = $this->getCurrentUserLogbookKey();
		if (empty($logbook_key)) return;

		$this->invalidateByPrefix("dxcluster_worked_call_{$logbook_key}_");
		$this->invalidateByPrefix("dxcluster_worked_dxcc_{$logbook_key}_");
		$this->invalidateByPrefix("dxcluster_worked_cont_{$logbook_key}_");
	}

	/**
	 * Get current user's logbook key from session
	 */
	protected function getCurrentUserLogbookKey() {
		$user_id = $this->CI->session->userdata('user_id');
		$active_logbook = $this->CI->session->userdata('active_station_logbook');

		if (empty($user_id) || empty($active_logbook)) return null;

		$this->CI->load->model('logbooks_model');

		$logbook_ids = $this->CI->logbooks_model->list_logbook_relationships($active_logbook);
		$confirmation_prefs = $this->CI->session->userdata('user_default_confirmation') ?? '';

		if (empty($logbook_ids)) return null;

		return $this->getLogbookKey($user_id, $logbook_ids, $confirmation_prefs);
	}

	// =========================================================================
	// INTERNAL HELPERS
	// =========================================================================

	protected function deleteFile($cache_key) {
		$cache_path = $this->getCachePath();
		if (!$cache_path) return;
		@unlink($cache_path . $cache_key);
	}

	protected function invalidateByPrefix($prefix) {
		$cache_path = $this->getCachePath();
		if (!$cache_path) return;

		$handle = @opendir($cache_path);
		if (!$handle) return;

		while (($filename = readdir($handle)) !== false) {
			if (strpos($filename, $prefix) === 0) {
				@unlink($cache_path . $filename);
			}
		}
		closedir($handle);
	}

	protected function getCachePath() {
		$cache_path = $this->CI->config->item('cache_path');
		$cache_path = ($cache_path === '' || $cache_path === false) ? APPPATH . 'cache/' : $cache_path;
		$cache_path = rtrim($cache_path, '/\\') . DIRECTORY_SEPARATOR;
		return (is_dir($cache_path) && is_writable($cache_path)) ? $cache_path : false;
	}

	// =========================================================================
	// GARBAGE COLLECTION
	// =========================================================================

	/**
	 * Run garbage collection with probability check (1% chance)
	 * Call this on each request when worked cache is enabled
	 */
	public function maybeRunGc() {
		if (mt_rand(1, 100) === 1) {
			$this->cleanExpiredCache();
		}
	}

	/**
	 * Clean expired dxcluster cache files
	 * Uses file mtime for fast pre-filtering before reading file contents
	 */
	public function cleanExpiredCache() {
		$cache_path = $this->getCachePath();
		if (!$cache_path || !is_readable($cache_path)) return;

		$handle = @opendir($cache_path);
		if (!$handle) return;

		$now = time();
		$deleted = 0;

		// Max TTL for dxcluster files: raw=59s, worked=900s - use 900s + buffer
		$max_ttl = 1000;

		while (($filename = readdir($handle)) !== false) {
			// Only process dxcluster cache files
			if (strpos($filename, 'dxcluster_') !== 0) continue;

			$file = $cache_path . $filename;
			if (!is_file($file)) continue;

			// Fast pre-filter: skip files modified recently (can't be expired yet)
			$mtime = @filemtime($file);
			if ($mtime !== false && ($now - $mtime) < $max_ttl) {
				continue;
			}

			// File is old enough to potentially be expired - read and verify
			$data = @unserialize(@file_get_contents($file));
			if (!is_array($data) || !isset($data['time'], $data['ttl'])) {
				@unlink($file);
				$deleted++;
				continue;
			}

			// Check if expired
			if ($data['ttl'] > 0 && $now > $data['time'] + $data['ttl']) {
				@unlink($file);
				$deleted++;
			}
		}

		closedir($handle);

		if ($deleted > 0) {
			log_message('debug', "DXCluster cache GC: deleted {$deleted} expired files");
		}
	}
}
