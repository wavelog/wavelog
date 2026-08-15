<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - DXCC entities resource (read-only)
 *
 * Returns the list of current DXCC entities for use in station creation
 * forms and callsign-resolution UI in third-party clients.
 *
 * Routes:
 *   GET /api/v2/dxcc          list all current entities (no deleted/0-adif)
 *   GET /api/v2/dxcc/{id}     single entity by ADIF number
 *
 * Scope: dxcc:read
 */
class Dxcc_resource extends Api_v2_resource {

	protected $scope = 'dxcc';

	protected static function scope_labels() {
		return [
			'read' => __('Read DXCC entity list'),
		];
	}

	/**
	 * GET /api/v2/dxcc
	 * Optional query: ?order=name (default) or ?order=prefix
	 */
	public function index() {
		$this->CI->load->model('dxcc');

		$order  = $this->param('order', 'name');
		$allowed = ['name', 'prefix'];
		if (!in_array($order, $allowed, true)) {
			throw new Api_v2_exception(
				'validation_error',
				'Unknown order "' . $order . '". Allowed: ' . implode(', ', $allowed),
				400,
				['allowed' => $allowed]
			);
		}

		$result = $this->CI->dxcc->list_current($order);
		$out = [];
		foreach ($result->result() as $row) {
			$out[] = $this->format($row);
		}

		$this->CI->api_v2_response->respond($out, 200, ['count' => count($out)]);
	}

	/**
	 * GET /api/v2/dxcc/{id}
	 * {id} is the ADIF number of the entity.
	 * Optional: ?subdivisions=true — include primary subdivisions (states/provinces).
	 */
	public function show($id) {
		if (!is_numeric($id) || (int) $id < 1) {
			throw new Api_v2_exception('not_found', 'DXCC entity not found', 404);
		}
		$this->CI->load->model('dxcc');

		$row = $this->CI->dxcc->get_by_adif((int) $id);
		if ($row === null) {
			throw new Api_v2_exception('not_found', 'DXCC entity not found', 404);
		}

		$data = $this->format($row);

		if ($this->param('subdivisions') === 'true') {
			$data['subdivisions'] = $this->fetch_subdivisions((int) $id);
		}

		$this->CI->api_v2_response->respond($data);
	}

	/**
	 * GET /api/v2/dxcc?subdivisions={adif}
	 * Convenience endpoint: returns just the subdivision list for a given DXCC.
	 * Equivalent to GET /api/v2/dxcc/{id}?subdivisions=true but usable without
	 * knowing the entity's exact ADIF id in advance.
	 *
	 * Also maps the patch endpoint get_state_list: ?dxcc={adif}
	 */
	protected function fetch_subdivisions($adif_id) {
		$rows = $this->CI->dxcc->get_subdivisions($adif_id);
		return array_map(fn($r) => [
			'code' => $r->state,
			'name' => $r->subdivision,
		], $rows);
	}

	// --- Helpers -----------------------------------------------------------

	protected function format($row) {
		return [
			'adif'      => isset($row->adif)      ? (int) $row->adif      : null,
			'name'      => $row->name      ?? null,
			'prefix'    => $row->prefix    ?? null,
			'continent' => $row->cont      ?? null,
			'cqz'       => isset($row->cqz)       ? (int) $row->cqz       : null,
			'ituz'      => isset($row->ituz)      ? (int) $row->ituz      : null,
			'lat'       => isset($row->lat)       ? (float) $row->lat     : null,
			'long'      => isset($row->long)      ? (float) $row->long    : null,
		];
	}
}
