<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Catalog resource (read-only)
 *
 * Instance-wide reference data: the value lists a client needs to fill a
 * dropdown or to validate its input before sending a write request. Unlike
 * every other resource this one exposes no user data at all, so it is public
 * in the scope sense ($scope = null) - any valid token may read it, exactly
 * like the Token (whoami) resource, and it contributes no scope to the
 * registry.
 *
 * The ?topic= parameter selects a list; without it the response enumerates the
 * available topics, so a client can discover them instead of hard-coding the
 * names. Adding a topic (bands, modes, DXCC entities, ...) means adding one
 * entry to the map in index() - no new route and no new scope.
 *
 * Topics:
 *   contest - the active contest catalog. Its "contest" values are the ADIF
 *             names the Contest resource expects in POST/PATCH bodies.
 *
 * Routes:
 *   GET /api/v2/catalog                 available topics
 *   GET /api/v2/catalog?topic=contest   the contest catalog
 *
 * Scope:  none (any valid token)
 */
class Catalog_resource extends Api_v2_resource {

	/** No scope: catalog data is instance-wide and carries no user data. */
	protected $scope = null;

	/**
	 * GET /api/v2/catalog[?topic=...]
	 *
	 * Topic key => builder, the single place a topic is registered.
	 */
	public function index() {
		$topics = [
			'contest' => function () {
				return $this->contest_topic();
			},
		];

		$topic = $this->param('topic');
		if ($topic === null) {
			// No topic asked for: report what there is rather than guessing a
			// default, so the endpoint documents itself.
			$this->CI->api_v2_response->respond(['topics' => array_keys($topics)]);
			return;
		}

		$topic = strtolower(trim((string) $topic));
		if (!isset($topics[$topic])) {
			throw new Api_v2_exception(
				'validation_error',
				'Unknown topic "' . $topic . '". Allowed: ' . implode(', ', array_keys($topics)),
				400,
				['allowed' => array_keys($topics)]
			);
		}

		$this->CI->api_v2_response->respond($topics[$topic](), 200, ['topic' => $topic]);
	}

	/**
	 * The contest catalog, as offered by the Contesting module. Only active
	 * contests are listed: the Contest resource rejects an inactive one on
	 * write, so a client has no use for it.
	 */
	protected function contest_topic() {
		$this->CI->load->model('contest_admin_model');

		$contests = [];
		foreach ($this->CI->contest_admin_model->getActiveContests() as $row) {
			$contests[] = [
				'id'      => (int) $row['id'],
				'contest' => $row['adifname'],
				'name'    => $row['name'],
			];
		}
		return $contests;
	}
}
