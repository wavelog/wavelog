<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'libraries/api_v2/Api_v2_exception.php';

/**
 * Wavelog REST API v2 - central dispatcher
 *
 * A single catch-all controller for every /api/v2/... request. Unlike the v1
 * API (RPC-style, method name in the URL, key in the JSON body), v2 is a real
 * REST API:
 *
 *   - Routing:   /api/v2/<resource>[/<id>]   (resource names are singular, lowercase)
 *   - Verbs:     GET (read), POST (create), PUT/PATCH (update), DELETE (delete)
 *   - Auth:      Authorization: Bearer <token>   (X-API-Key accepted as fallback)
 *                Only the new "wl2_" tokens (table `api_token`, Api_v2_model)
 *                are accepted; legacy v1 keys are rejected. Each token carries
 *                granular scopes ("qso:read", "qso:write", ...) which are
 *                enforced per resource and verb before dispatching.
 *   - Response:  slim envelope { "data": ... } / { "error": { code, message } };
 *                the HTTP status carries the semantics.
 *
 * Adding a new resource requires no change here: drop a
 * `application/libraries/api_v2/<Resource>_resource.php` class extending
 * Api_v2_resource and it is dispatched by convention.
 *
 * @see application/config/routes.php  ($route['api/v2/(:any)'])
 * @see application/libraries/api_v2/Api_v2_resource.php
 */
class Api_v2 extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->library('api_v2_response');
		$this->load->model('api_v2_model');
	}

	/**
	 * Single entry point for all /api/v2/... requests.
	 *
	 * The catch-all route forwards the path after /api/v2/ as individual URI
	 * segments, so CodeIgniter passes them as separate arguments here. We
	 * collect them all via a variadic parameter (e.g. route('qso', '42')).
	 *
	 * @param string ...$segments The path segments after /api/v2/.
	 */
	public function route(...$segments) {
		$method = $this->input->method(true);

		// Answer CORS preflight before doing any work.
		if ($method === 'OPTIONS') {
			$this->handle_preflight();
			return;
		}

		// Drop any empty segments (e.g. from a trailing slash).
		$segments = array_values(array_filter($segments, function ($s) {
			return $s !== '' && $s !== null;
		}));

		$resource = $segments[0] ?? '';
		$id       = $segments[1] ?? null;

		try {
			if (count($segments) > 2) {
				throw new Api_v2_exception('not_found', 'Unknown resource path', 404);
			}

			// Public meta endpoints: GET /api/v2 and GET /api/v2/status.
			// Neither has addressable items, so a trailing id is not a URL either.
			if ($resource === '' || $resource === 'status') {
				if ($id !== null) {
					throw new Api_v2_exception('not_found', 'Unknown resource path', 404);
				}
				if ($method !== 'GET') {
					$this->reject_method(['GET']);
				}
				$this->api_v2_response->respond([
					'name'    => 'Wavelog API',
					'status'  => 'ok',
				]);
				return;
			}

			// Everything past here requires a valid API token.
			try {
				$auth = $this->authenticate();
			} catch (Api_v2_exception $e) {
				// Throttle failed attempts by IP; a rejected token never reaches
				// the per-token limit below. Only failures count, so valid
				// clients sharing a NAT address are spared.
				$this->enforce_rate_limit('api_v2_auth', $this->input->ip_address());
				throw $e;
			}

			// Per-resource rate limiting keyed by the token id (the plaintext
			// token is never kept around after authentication).
			$this->enforce_rate_limit('api_v2_' . $resource, $auth['id']);

			$handler = $this->load_resource($resource, $auth, $method);

			// Verb support first: a verb the resource does not implement is a
			// 405, not a permission problem. Checking it after the scope check
			// would answer "insufficient_scope" for a scope that cannot exist
			// (e.g. POST on a read-only resource needs "<res>:write", which is
			// never in the registry) and hide the real reason from the client.
			$allowed = $handler->supported_methods();
			if (!in_array($method, $allowed, true)) {
				$this->reject_method($allowed);
			}

			// Scope enforcement: the resource declares its scope name, the
			// verb decides between :read and :write. null = public resource.
			$required = $handler->required_scope($method);
			if ($required !== null && !in_array($required, $auth['scopes'], true)) {
				throw new Api_v2_exception(
					'insufficient_scope',
					'Token is missing the required scope: ' . $required,
					403,
					['required_scope' => $required]
				);
			}

			$this->dispatch($handler, $method, $id);

		} catch (Api_v2_exception $e) {
			$this->api_v2_response->error(
				$e->get_error_code(),
				$e->getMessage(),
				$e->get_http_status(),
				$e->get_details()
			);
		} catch (Throwable $e) {
			log_message('error', 'API v2 unhandled: ' . $e->getMessage());
			$this->api_v2_response->error('internal_error', 'Internal server error', 500);
		}
	}

	/**
	 * Validate the Bearer/X-API-Key credential and build the auth context.
	 * Only v2 tokens ("wl2_" prefix) are accepted; scope checks happen later
	 * in route() once the target resource is known.
	 *
	 * @return array { id, user_id, created_by, scopes }
	 * @throws Api_v2_exception 401
	 */
	protected function authenticate() {
		$token = $this->extract_key();
		if ($token === null) {
			throw new Api_v2_exception('unauthorized', 'Missing API token', 401);
		}

		// Fast-fail anything that is not a v2 token (e.g. legacy v1 keys).
		if (strpos($token, Api_v2_model::TOKEN_PREFIX) !== 0) {
			throw new Api_v2_exception(
				'invalid_token',
				'API v2 requires a ' . Api_v2_model::TOKEN_PREFIX . ' token; legacy v1 API keys are not accepted',
				401
			);
		}

		$auth = $this->api_v2_model->authenticate_token($token);
		if ($auth === null) {
			throw new Api_v2_exception('invalid_token', 'Invalid or revoked API token', 401);
		}
		if ($auth['expired']) {
			throw new Api_v2_exception('token_expired', 'API token has expired', 401);
		}

		$auth = $this->resolve_club_context($auth);

		$this->api_v2_model->update_last_used($auth['id'], $auth['user_id']);

		return $auth;
	}

	/**
	 * Re-check the clubstation membership behind a club token and add the club
	 * context the resources need to enforce the permission levels.
	 *
	 * A club token belongs to the clubstation (user_id) but was issued by a
	 * member acting on its behalf (created_by). Two things follow from that:
	 *
	 *  - The membership can be withdrawn at any time and the token carries no
	 *    record of it, so without this check a removed member would keep full
	 *    access to the club logbook until the token happens to expire.
	 *  - What the member may do depends on the permission level behind that
	 *    membership (3 = member, 6 = member ADIF, 9 = officer). The web UI reads
	 *    it from the session via clubaccess_check(), which is useless here, so
	 *    the level travels in the auth context instead.
	 *
	 * Personal tokens (owner == creator) skip both lookups entirely and keep
	 * null for the club fields - they are never restricted by club rules.
	 *
	 * @param array $auth Authentication context from authenticate_token().
	 * @return array The same context plus club_permission and operator_callsign.
	 * @throws Api_v2_exception 403 once the membership is gone.
	 */
	protected function resolve_club_context($auth) {
		$auth['club_permission']   = null;
		$auth['operator_callsign'] = null;

		// Same master switch clubaccess_check() uses: with the clubstation
		// feature off there are no club rules to apply, for the API no more than
		// for the web UI.
		if (!$this->config->item('special_callsign')) {
			return $auth;
		}

		if ($auth['created_by'] === $auth['user_id']) {
			return $auth;
		}

		$this->load->model('club_model');
		// Returns 0 for a user that is no longer a member of the clubstation.
		$permission = (int) $this->club_model->get_permission_noui($auth['user_id'], $auth['created_by']);
		if ($permission < 1) {
			// The token is valid, the permission behind it is not: 403, not 401.
			throw new Api_v2_exception(
				'club_access_revoked',
				'The clubstation membership behind this token has been revoked',
				403
			);
		}

		$auth['club_permission'] = $permission;

		// The acting member's own callsign: what their QSOs are logged under and
		// what the per-operator restrictions are matched against.
		$creator = $this->user_model->get_by_id($auth['created_by']);
		if ($creator !== null && $creator->num_rows() > 0) {
			$auth['operator_callsign'] = $creator->row()->user_callsign;
		}

		return $auth;
	}

	/**
	 * Read the API key from the Authorization: Bearer header, falling back to
	 * the X-API-Key header.
	 *
	 * @return string|null
	 */
	protected function extract_key() {
		$auth_header = $this->input->get_request_header('Authorization', true);
		if ($auth_header && preg_match('/^Bearer\s+(\S+)$/i', trim($auth_header), $m)) {
			return $m[1];
		}

		$api_key_header = $this->input->get_request_header('X-API-Key', true);
		if ($api_key_header) {
			return trim($api_key_header);
		}

		return null;
	}

	/**
	 * Instantiate the resource handler for the given resource name.
	 *
	 * @throws Api_v2_exception 404 when no handler exists for the resource.
	 */
	protected function load_resource($resource, $auth, $method) {
		// Resource names are singular lowercase; the class is "<Resource>_resource".
		if (!preg_match('/^[a-z][a-z0-9_]*$/', $resource)) {
			throw new Api_v2_exception('not_found', 'Unknown resource', 404);
		}

		$class = ucfirst($resource) . '_resource';
		$file  = APPPATH . 'libraries/api_v2/' . $class . '.php';

		if (!is_file($file)) {
			throw new Api_v2_exception('not_found', 'Unknown resource: ' . $resource, 404);
		}

		require_once $file;
		// The class must be a concrete subclass of Api_v2_resource. This also
		// rejects the abstract base class itself (e.g. GET /api/v2/api_v2).
		if (!class_exists($class) || !is_subclass_of($class, 'Api_v2_resource')) {
			throw new Api_v2_exception('not_found', 'Unknown resource: ' . $resource, 404);
		}

		if (!$class::is_available()) {
			throw new Api_v2_exception('not_found', 'Unknown resource: ' . $resource, 404);
		}

		$body = in_array($method, ['POST', 'PUT', 'PATCH'], true) ? $this->read_json_body() : null;

		return new $class($auth, $body);
	}

	/**
	 * Map the HTTP verb to a handler method and invoke it.
	 *
	 * @throws Api_v2_exception 405 for unsupported verbs.
	 */
	protected function dispatch($handler, $method, $id) {
		// route() has already rejected any verb this resource does not implement;
		// the base class' 405 stubs stay in place as a safety net.
		$allowed = $handler->supported_methods();

		switch ($method) {
			case 'GET':
				($id === null) ? $handler->index() : $handler->show($id);
				break;
			case 'POST':
				if ($id !== null) {
					// POST creates, it never addresses an existing item: the
					// remaining verbs are exactly the id-addressable ones.
					$this->reject_method(array_values(array_diff($allowed, ['POST'])));
				}
				$handler->create();
				break;
			case 'PUT':
				// Full replacement of the resource (idempotent).
				if ($id === null) {
					throw new Api_v2_exception('not_found', 'Missing resource id', 404);
				}
				$handler->replace($id);
				break;
			case 'PATCH':
				// Partial update of the supplied fields.
				if ($id === null) {
					throw new Api_v2_exception('not_found', 'Missing resource id', 404);
				}
				$handler->update($id);
				break;
			case 'DELETE':
				if ($id === null) {
					throw new Api_v2_exception('not_found', 'Missing resource id', 404);
				}
				$handler->delete($id);
				break;
			default:
				$this->reject_method(['GET', 'POST', 'PUT', 'PATCH', 'DELETE']);
		}
	}

	/**
	 * Read and decode the JSON request body.
	 *
	 * @throws Api_v2_exception 400 on malformed JSON.
	 */
	protected function read_json_body() {
		$raw = file_get_contents('php://input');
		if ($raw === '' || $raw === false) {
			return [];
		}
		$decoded = json_decode($raw, true);
		if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
			throw new Api_v2_exception('invalid_json', 'Request body is not valid JSON', 400);
		}
		return is_array($decoded) ? $decoded : [];
	}

	/**
	 * Enforce rate limiting for the given endpoint/identifier.
	 * No-op unless `api_rate_limits` is configured.
	 *
	 * @throws Api_v2_exception 429 when the limit is exceeded.
	 */
	protected function enforce_rate_limit($endpoint, $identifier) {
		if (!$this->load->is_loaded('rate_limit')) {
			$this->load->library('rate_limit');
		}
		$result = $this->rate_limit->check($endpoint, $identifier);
		if (!$result['allowed']) {
			$this->output->set_header('Retry-After: ' . $result['retry_after']);
			throw new Api_v2_exception(
				'rate_limited',
				'Rate limit exceeded. Try again in ' . $result['retry_after'] . ' seconds.',
				429,
				['retry_after' => $result['retry_after']]
			);
		}
	}

	/**
	 * Throw a 405 with the appropriate Allow header.
	 */
	protected function reject_method($allowed) {
		$this->output->set_header('Allow: ' . implode(', ', $allowed));
		throw new Api_v2_exception('method_not_allowed', 'HTTP method not allowed', 405);
	}

	/**
	 * Answer a CORS preflight request.
	 */
	protected function handle_preflight() {
		$this->output
			->set_header('Access-Control-Allow-Origin: *')
			->set_header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS')
			->set_header('Access-Control-Allow-Headers: Authorization, Content-Type, X-API-Key')
			->set_header('Access-Control-Max-Age: 86400')
			->set_status_header(204);
	}

}
