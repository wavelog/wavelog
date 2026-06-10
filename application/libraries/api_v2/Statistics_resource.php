<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Statistics resource (read-only)
 *
 * Exposes instance-wide statistics (all users) via the existing Debug_model.
 *
 * Route:  /api/v2/statistics
 * Scope:  statistics:read
 */
class Statistics_resource extends Api_v2_resource {

	/** Token scope of this resource (see Api_v2_resource::required_scope()). */
	protected $scope = 'statistics';

	/**
	 * GET /api/v2/statistics
	 */
	public function index() {
		// Note: CI registers models under their lowercased name.
		$this->CI->load->model('debug_model');

		$this->CI->api_v2_response->respond([
			'qsos_total'  => (int) $this->CI->debug_model->count_all_qso(),
			'users_total' => (int) $this->CI->debug_model->count_users(),
		]);
	}
}
