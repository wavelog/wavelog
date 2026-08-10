<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_resource.php';

/**
 * API v2 - Token info resource ("whoami")
 *
 * Returns metadata about the token used for the request: its id, name, owner
 * callsign, granted scopes and expiry. This is the v2 equivalent of the v1
 * auth / check_auth endpoints — a cheap way for a client to verify its token
 * and discover what it may do.
 *
 * The resource is public in the scope sense ($scope = null): it requires a
 * valid token (the dispatcher authenticates before the resource runs) but no
 * particular scope. It therefore contributes no scope to the registry.
 *
 * Route:  /api/v2/token
 * Scope:  none (any valid token)
 */
class Token_resource extends Api_v2_resource {

	/** No scope: any authenticated token may read its own metadata. */
	protected $scope = null;

	/**
	 * GET /api/v2/token
	 * Metadata of the current token.
	 */
	public function index() {
		$meta = $this->CI->api_v2_model->get_token_meta($this->auth['id']);

		$owner = $this->CI->user_model->get_by_id($this->user_id());
		$callsign = ($owner !== null && $owner->num_rows() > 0) ? $owner->row()->user_callsign : null;

		$this->CI->api_v2_response->respond([
			'id'         => (int) $this->auth['id'],
			'name'       => $meta['token_name'] ?? null,
			'owner'      => $callsign,
			'user_id'    => $this->user_id(),
			'scopes'     => $this->auth['scopes'],
			'expires_at' => $meta['expires_at'] ?? null,
		]);
	}
}
