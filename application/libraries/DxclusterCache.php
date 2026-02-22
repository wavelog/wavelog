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
	public function get_raw_cache_key($maxage, $band) {
		return "dxcluster_raw_{$maxage}_{$band}_Any_All";
	}

	/**
	 * Generate logbook IDs key component (user-specific)
	 */
	public function get_logbook_key($user_id, $logbook_ids, $confirmation_prefs) {
		$logbook_ids_str = implode('_', $logbook_ids);
		$confirmation_hash = md5($confirmation_prefs);
		return "{$user_id}_{$logbook_ids_str}_{$confirmation_hash}";
	}

	/**
	 * Generate WORKED callsign cache key
	 */
	public function get_worked_call_key($logbook_key, $callsign) {
		return "dxcluster_worked_call_{$logbook_key}_{$callsign}";
	}

	/**
	 * Generate WORKED DXCC cache key
	 */
	public function get_worked_dxcc_key($logbook_key, $dxcc) {
		return "dxcluster_worked_dxcc_{$logbook_key}_{$dxcc}";
	}

	/**
	 * Generate WORKED continent cache key
	 */
	public function get_worked_cont_key($logbook_key, $cont) {
		return "dxcluster_worked_cont_{$logbook_key}_{$cont}";
	}

	// =========================================================================
	// CACHE INVALIDATION
	// =========================================================================

	/**
	 * Invalidate cache after QSO add/edit/delete for current user
	 * @param string $callsign - The worked callsign
	 */
	public function invalidate_for_callsign($callsign) {
		// Skip if worked cache is disabled
		if ($this->CI->config->item('enable_dxcluster_file_cache_worked') !== true) return;

		if (empty($callsign)) return;

		// Get current user's logbook key
		$logbook_key = $this->_get_current_user_logbook_key();
		if (empty($logbook_key)) return;

		// Delete callsign cache
		$this->_delete_from_cache($this->get_worked_call_key($logbook_key, $callsign));

		// Look up DXCC and continent from callsign
		$dxccobj = new Dxcc();
		$dxcc_info = $dxccobj->dxcc_lookup($callsign, date('Y-m-d'));

		if (!empty($dxcc_info['adif'])) {
			$this->_delete_from_cache($this->get_worked_dxcc_key($logbook_key, $dxcc_info['adif']));
		}
		if (!empty($dxcc_info['cont'])) {
			$this->_delete_from_cache($this->get_worked_cont_key($logbook_key, $dxcc_info['cont']));
		}
	}

	/**
	 * Get current user's logbook key from session
	 */
	private function _get_current_user_logbook_key() {
		$user_id = $this->CI->session->userdata('user_id');
		$active_logbook = $this->CI->session->userdata('active_station_logbook');

		if (empty($user_id) || empty($active_logbook)) return null;

		$this->CI->load->model('logbooks_model');

		$logbook_ids = $this->CI->logbooks_model->list_logbook_relationships($active_logbook);
		$confirmation_prefs = $this->CI->session->userdata('user_default_confirmation') ?? '';

		if (empty($logbook_ids)) return null;

		return $this->get_logbook_key($user_id, $logbook_ids, $confirmation_prefs);
	}

	// =========================================================================
	// INTERNAL HELPERS
	// =========================================================================

	private function _delete_from_cache($cache_key) {
		$this->CI->load->driver('cache', [
			'adapter' => $this->CI->config->item('cache_adapter') ?? 'file', 
			'backup' => $this->CI->config->item('cache_backup') ?? 'file',
			'key_prefix' => $this->CI->config->item('cache_key_prefix') ?? ''
		]);
		$this->CI->cache->delete($cache_key);
	}
}
