<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once __DIR__ . '/Api_v2_exception.php';

/**
 * API v2 - Abstract resource handler
 *
 * Base class every REST resource extends. The dispatcher (Api_v2 controller)
 * instantiates the matching "<Resource>_resource" class, hands it the
 * authentication context plus the parsed request body, and then calls one of
 * the verb methods below.
 *
 * Subclasses override only the verbs they support. Any verb left unimplemented
 * falls through to the default here, which throws a 405 method_not_allowed.
 *
 * Verb -> method mapping (performed by the dispatcher):
 *   GET    /res        -> index()
 *   GET    /res/{id}   -> show($id)
 *   POST   /res        -> create()
 *   PUT    /res/{id}   -> replace($id)   full replacement (idempotent)
 *   PATCH  /res/{id}   -> update($id)    partial update of given fields
 *   DELETE /res/{id}   -> delete($id)
 *
 * PUT and PATCH are intentionally distinct: PUT replaces the whole resource
 * (omitted fields are reset), PATCH only touches the fields present in the body.
 */
abstract class Api_v2_resource {

	/** @var CI_Controller The CodeIgniter super-object. */
	protected $CI;

	/** @var array Authentication context filled by the dispatcher. */
	protected $auth;

	/** @var array|null Decoded JSON request body (null if none/invalid). */
	protected $request_body;

	/**
	 * Scope name of this resource (e.g. "qso", "station"). Combined with the
	 * HTTP verb to the required token scope: GET needs "<scope>:read", DELETE
	 * "<scope>:delete", every other verb "<scope>:write". null marks a public
	 * resource (no scope check).
	 *
	 * @var string|null
	 */
	protected $scope = null;

	/**
	 * @param array      $auth { id, user_id, created_by, scopes (string[]) }
	 * @param array|null $body Parsed JSON body, or null for verbs without one.
	 */
	public function __construct($auth, $body = null) {
		$this->CI =& get_instance();
		$this->auth = $auth;
		$this->request_body = $body;
	}

	/**
	 * The token scope required to call this resource with the given verb, or
	 * null when the resource is public. Evaluated by the dispatcher before
	 * the verb handler runs.
	 *
	 * @param string $method HTTP verb (uppercase).
	 * @return string|null
	 */
	public function required_scope($method) {
		if ($this->scope === null) {
			return null;
		}
		switch ($method) {
			case 'GET':
				return $this->scope . ':read';
			case 'DELETE':
				return $this->scope . ':delete';
			default:
				return $this->scope . ':write';
		}
	}

	// --- Default verb handlers: 405 until a subclass overrides them --------

	public function index()        { $this->method_not_allowed(); }
	public function show($id)      { $this->method_not_allowed(); }
	public function create()       { $this->method_not_allowed(); }
	public function replace($id)   { $this->method_not_allowed(); } // PUT: full replace
	public function update($id)    { $this->method_not_allowed(); } // PATCH: partial update
	public function delete($id)    { $this->method_not_allowed(); }

	// --- Helpers for subclasses -------------------------------------------

	/**
	 * Return the full decoded request body, or an empty array if none.
	 */
	protected function body() {
		return is_array($this->request_body) ? $this->request_body : [];
	}

	/**
	 * Read a single query-string parameter with XSS cleaning.
	 *
	 * @param string $key     Query parameter name.
	 * @param mixed  $default Returned when the parameter is absent.
	 */
	protected function param($key, $default = null) {
		$value = $this->CI->input->get($key, true);
		return ($value === null || $value === false || $value === '') ? $default : $value;
	}

	/**
	 * The user_id that owns the data this key may touch.
	 */
	protected function user_id() {
		return $this->auth['user_id'];
	}

	/**
	 * Guard a write operation: throw 403 unless the token carries this
	 * resource's write scope. The dispatcher already enforces scopes per verb;
	 * this is an extra safety net for handlers that mutate data internally.
	 */
	protected function require_write() {
		$this->require_scope_suffix('write');
	}

	/**
	 * Guard a delete operation, analogous to require_write().
	 */
	protected function require_delete() {
		$this->require_scope_suffix('delete');
	}

	/**
	 * Throw 403 unless the token carries "<scope>:<suffix>".
	 */
	protected function require_scope_suffix($suffix) {
		$required = $this->scope . ':' . $suffix;
		if (!in_array($required, $this->auth['scopes'] ?? [], true)) {
			throw new Api_v2_exception('insufficient_scope', 'Token is missing the required scope: ' . $required, 403);
		}
	}

	/**
	 * Resolve pagination from the query string with sane defaults and caps.
	 *
	 * @param int $default_per_page Default page size.
	 * @param int $max_per_page     Hard upper bound on page size.
	 * @return array { page, per_page, offset }
	 */
	protected function pagination($default_per_page = 50, $max_per_page = 500) {
		$page = (int) $this->param('page', 1);
		if ($page < 1) {
			$page = 1;
		}

		$per_page = (int) $this->param('per_page', $default_per_page);
		if ($per_page < 1) {
			$per_page = $default_per_page;
		}
		if ($per_page > $max_per_page) {
			$per_page = $max_per_page;
		}

		return [
			'page'     => $page,
			'per_page' => $per_page,
			'offset'   => ($page - 1) * $per_page,
		];
	}

	/**
	 * Guard against nested JSON: every body value must be a scalar or null.
	 * Arrays/objects would otherwise blow up deep in the data layer (500);
	 * fail early with a clear 400 instead.
	 *
	 * @param array $body Decoded request body.
	 * @throws Api_v2_exception 400 when a non-scalar value is found.
	 */
	protected function require_scalar_fields($body) {
		foreach ($body as $key => $value) {
			if ($value !== null && !is_scalar($value)) {
				throw new Api_v2_exception(
					'validation_error',
					'Field "' . $key . '" must be a scalar value',
					400,
					['field' => $key]
				);
			}
		}
	}

	/**
	 * Throw a 405 with the standard error code.
	 */
	protected function method_not_allowed() {
		throw new Api_v2_exception('method_not_allowed', 'HTTP method not allowed for this resource', 405);
	}
}
