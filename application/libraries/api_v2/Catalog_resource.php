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
 *   contest      - the active contest catalog. Its "contest" values are the
 *                  ADIF names the Contest resource expects in POST/PATCH
 *                  bodies.
 *   dxcc         - the currently valid DXCC entities. Optional ?order=name|prefix.
 *   subdivisions - the primary administrative subdivisions (ADIF STATE) of one
 *                  entity. Requires ?dxcc=<entity number>.
 *
 * Routes:
 *   GET /api/v2/catalog                 				available topics
 *   GET /api/v2/catalog?topic=contest   				the contest catalog
 *   GET /api/v2/catalog?topic=dxcc      				the DXCC entity catalog
 *   GET /api/v2/catalog?topic=subdivisions&dxcc=123 	the DXCC subdivision catalog
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
			'dxcc' => function () {
				return $this->dxcc_topic();
			},
			'subdivisions' => function () {
				return $this->subdivisions_topic();
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
				'Unknown topic: ' . $topic,
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

	/**
	 * DXCC entities, as offered by the DXCC module.
	 * Please note that this is publicly available data and Wavelog just provides it for convenience.
	 * If possible please use the official dxcc sources. 
	 * 
	 * Optional parameters:
	 * - order: name|prefix (default: name)
	 */
	protected function dxcc_topic() {
		$this->CI->load->model('dxcc');

		$order  = $this->param('order', 'name') ?? 'name';
		$allowed = ['name', 'prefix'];
		if (!in_array($order, $allowed, true)) {
			throw new Api_v2_exception(
				'validation_error',
				'Unknown order parameter: ' . $order,
				400,
				['allowed' => $allowed]
			);
		}

		$entities = [];
		foreach ($this->CI->dxcc->list_current($order)->result() as $row) {
			$entities[] = [
				'adif'      => (int) $row->adif,
				'name'      => $row->name,
				'prefix'    => $row->prefix,
				'continent' => $row->cont,
				'cqz'       => isset($row->cqz) ? (int) $row->cqz : null,
				'ituz'      => isset($row->ituz) ? (int) $row->ituz : null,
				'lat'       => isset($row->lat) ? (float) $row->lat : null,
				'long'      => isset($row->long) ? (float) $row->long : null,
			];
		}

		return $entities;
	}

	/**
	 * The primary administrative subdivisions (ADIF STATE) of one DXCC entity,
	 * as offered by the Logbook model. The "state" values are what the
	 * QSO resource expects in a STATE field.
	 * Please note that this is publicly available data and Wavelog just provides
	 * it for convenience. If possible please use the official ADIF sources.
	 *
	 * Required parameters:
	 * - dxcc: the DXCC entity number the subdivisions belong to
	 */
	protected function subdivisions_topic() {
		$this->CI->load->model('logbook_model');

		$dxcc = $this->param('dxcc');
		if ($dxcc === null) {
			throw new Api_v2_exception(
				'validation_error',
				'Missing dxcc parameter',
				400
			);
		}

		$dxcc = (int) $dxcc;
		if ($dxcc <= 0) {
			throw new Api_v2_exception(
				'validation_error',
				'Invalid dxcc parameter: ' . $this->param('dxcc'),
				400
			);
		}

		$subdivisions = [];
		// Deprecated subdivisions are left out: they exist so an old QSO can
		// still show the state it was logged with, but they are not valid
		// choices for anything a client creates.
		foreach ($this->CI->logbook_model->get_states_by_dxcc($dxcc, true)->result() as $row) {
			$subdivisions[] = [
				'state' => $row->state,
				'name'  => $row->subdivision,
			];
		}

		return $subdivisions;
	}
}
