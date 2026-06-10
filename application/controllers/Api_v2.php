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
 *   - Routing:   /api/v2/<resource>[/<id>]   (resource names are plural, lowercase)
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
	 * collect them all via a variadic parameter (e.g. route('qsos', '42')).
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
			// Public meta endpoints: GET /api/v2 and GET /api/v2/status.
			if ($resource === '' || $resource === 'status') {
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
			$auth = $this->authenticate();

			// Per-resource rate limiting keyed by the token id (the plaintext
			// token is never kept around after authentication).
			$this->enforce_rate_limit('api_v2_' . $resource, 'api_token_' . $auth['id']);

			$handler = $this->load_resource($resource, $auth, $method);

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

		$this->api_v2_model->update_last_used($auth['id'], $auth['user_id']);

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
		// Resource names are plural lowercase; the class is "<Resource>_resource".
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

		$body = in_array($method, ['POST', 'PUT', 'PATCH'], true) ? $this->read_json_body() : null;

		return new $class($auth, $body);
	}

	/**
	 * Map the HTTP verb to a handler method and invoke it.
	 *
	 * @throws Api_v2_exception 405 for unsupported verbs.
	 */
	protected function dispatch($handler, $method, $id) {
		switch ($method) {
			case 'GET':
				($id === null) ? $handler->index() : $handler->show($id);
				break;
			case 'POST':
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
