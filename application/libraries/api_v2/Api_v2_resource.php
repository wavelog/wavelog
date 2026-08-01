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
 * falls through to the default here, which throws a 405 method_not_allowed -
 * except show(), which throws a 404 because a resource that has no per-item GET
 * has no addressable items in the first place.
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
	 * Verb handler method => scope suffix it requires. Single source of truth
	 * for the read/write/delete split, used by the registry derivation in
	 * scope_definitions(). A resource "supports" a suffix when it overrides at
	 * least one of the methods mapped to it (un-overridden verbs 405 in the base
	 * class and therefore contribute no scope).
	 *
	 * @var array<string,string>
	 */
	protected const VERB_SCOPE = [
		'index'   => 'read',
		'show'    => 'read',
		'create'  => 'write',
		'replace' => 'write',
		'update'  => 'write',
		'delete'  => 'delete',
	];

	/**
	 * HTTP verb => the handler methods it maps to. A resource supports a verb
	 * when it overrides at least one of them; the counterpart to VERB_SCOPE,
	 * which answers what scope a verb needs rather than whether it exists.
	 *
	 * @var array<string,string[]>
	 */
	protected const VERB_HANDLERS = [
		'GET'    => ['index', 'show'],
		'POST'   => ['create'],
		'PUT'    => ['replace'],
		'PATCH'  => ['update'],
		'DELETE' => ['delete'],
	];

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

	/**
	 * The HTTP verbs this resource actually implements, for the Allow header of
	 * a 405. Derived from the verb methods the concrete class overrides, the same
	 * way scope_definitions() derives the scopes — so the header can never
	 * advertise a verb that only ends up in the base class' 405 stub.
	 *
	 * @return string[] e.g. ['GET', 'POST', 'PATCH', 'DELETE']
	 */
	public function supported_methods() {
		$verbs = [];
		foreach (self::VERB_HANDLERS as $verb => $handlers) {
			foreach ($handlers as $handler) {
				if ((new ReflectionMethod(static::class, $handler))->getDeclaringClass()->getName() !== self::class) {
					$verbs[] = $verb;
					break;
				}
			}
		}
		return $verbs;
	}

	/**
	 * The scope ids this resource contributes to the central registry, as
	 * "scope-id => human-readable label". Derived purely from $scope and the
	 * verb methods this concrete class actually overrides, so the registry can
	 * never drift from what the dispatcher enforces. Public resources
	 * ($scope === null) contribute nothing.
	 *
	 * @return array<string,string>
	 */
	public static function scope_definitions() {
		$scope = (new ReflectionClass(static::class))->getDefaultProperties()['scope'] ?? null;
		if ($scope === null) {
			return [];
		}

		$labels = static::scope_labels();
		$out = [];
		foreach (self::VERB_SCOPE as $method => $suffix) {
			// A verb counts only when the concrete class overrides it; methods
			// still declared on the abstract base are 405 stubs (unsupported).
			if ((new ReflectionMethod(static::class, $method))->getDeclaringClass()->getName() === self::class) {
				continue;
			}
			$id = $scope . ':' . $suffix;
			// Several methods can map to the same suffix (index + show => read);
			// keying by id de-dupes automatically.
			$out[$id] = $labels[$suffix] ?? self::default_label($suffix, $scope);
		}
		return $out;
	}

	/**
	 * Translated labels for this resource's scopes, keyed by suffix
	 * ("read" | "write" | "delete"). Override in each resource; the strings
	 * must be static __() literals so po_gen.sh can extract them. The base
	 * returns none, falling back to default_label().
	 *
	 * @return array<string,string>
	 */
	protected static function scope_labels() {
		return [];
	}

	/**
	 * Generic, untranslated fallback label for a suffix a resource did not
	 * describe. Resources should always provide their own labels.
	 */
	protected static function default_label($suffix, $scope) {
		return ucfirst($suffix) . ' ' . $scope;
	}

	// --- Default verb handlers: 405 until a subclass overrides them --------

	public function index()        { $this->method_not_allowed(); }
	// A resource without show() has no addressable items, so /res/{id} is not a
	// URL that exists - 404 rather than "wrong verb on an existing URL".
	public function show($id)      { throw new Api_v2_exception('not_found', 'Unknown resource item', 404); }
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

	// --- Clubstation permission levels -------------------------------------
	//
	// The API counterpart to clubaccess_check() (helpers/club_helper.php), which
	// reads cd_p_level and operator_callsign from the session and returns true
	// unconditionally without one - useless for a sessionless request. Here the
	// level travels in the auth context instead (see Api_v2::resolve_club_context).
	//
	// Levels, as defined in controllers/Club.php:
	//   3 = Club Member, 6 = Club Member ADIF, 9 = Club Officer.
	// A personal token is not a club token at all and is never restricted.

	/**
	 * Clubstation permission level behind this token, or null for a personal one.
	 */
	protected function club_permission() {
		return $this->auth['club_permission'] ?? null;
	}

	/**
	 * Callsign of the member acting behind a club token (null for personal
	 * tokens). This is what their QSOs are logged under, and what the
	 * per-operator restrictions are matched against.
	 */
	protected function operator_callsign() {
		return $this->auth['operator_callsign'] ?? null;
	}

	/**
	 * Whether this request comes from a club member below officer level, i.e.
	 * one that may only ever touch its own QSOs. The single condition every
	 * per-operator restriction hangs off.
	 */
	protected function is_restricted_club_member() {
		$permission = $this->club_permission();
		return $permission !== null && $permission < 9;
	}

	/**
	 * Guard an operation that requires a minimum clubstation permission level.
	 * A no-op for personal tokens.
	 *
	 * @param int $level Minimum p_level (typically 9 for officer-only actions).
	 * @throws Api_v2_exception 403 when the member's level is too low.
	 */
	protected function require_club_level($level) {
		$permission = $this->club_permission();
		if ($permission === null || $permission >= $level) {
			return;
		}
		throw new Api_v2_exception(
			'insufficient_club_permission',
			'This operation requires clubstation permission level ' . $level,
			403,
			['required_level' => $level, 'granted_level' => $permission]
		);
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
	 * Station location ids owned by the token user. Every resource scopes its
	 * queries to these, so a token can only ever reach its owner's data.
	 *
	 * @return int[]
	 */
	protected function owner_station_ids() {
		$this->CI->load->model('stations');
		$ids = [];
		$query = $this->CI->stations->all_of_user($this->user_id());
		if ($query !== null) {
			foreach ($query->result() as $row) {
				$ids[] = (int) $row->station_id;
			}
		}
		return $ids;
	}

	/**
	 * The station ids a request runs against: all of the owner's stations, or
	 * the ownership-checked subset named in the query parameter.
	 *
	 * Ids the token does not own are rejected with a 403 rather than silently
	 * dropped, so a caller never receives data for a station set other than the
	 * one it asked for.
	 *
	 * @param string $param Query parameter holding the comma-separated ids.
	 * @return int[]
	 * @throws Api_v2_exception 400 on a non-numeric id, 403 on a foreign one.
	 */
	protected function resolve_station_ids($param = 'station_id') {
		$owned = $this->owner_station_ids();
		$requested = $this->param($param);
		if ($requested === null || $requested === '') {
			return $owned;
		}

		$ids = [];
		foreach (explode(',', $requested) as $sid) {
			$sid = trim($sid);
			if (!is_numeric($sid)) {
				throw new Api_v2_exception('validation_error', $param . ' values must be numeric', 400);
			}
			$sid = (int) $sid;
			if (!in_array($sid, $owned, true)) {
				throw new Api_v2_exception('forbidden', $param . ' not accessible for this token', 403);
			}
			$ids[] = $sid;
		}
		return array_values(array_unique($ids));
	}

	/**
	 * Normalise a ?band= filter: '' when absent, 'SAT' uppercased (matches
	 * COL_PROP_MODE), any other band lowercased (matches COL_BAND). Unknown
	 * values pass through and simply match no rows rather than erroring.
	 */
	protected function normalize_band($raw) {
		if ($raw === null || $raw === '') {
			return '';
		}
		$band = strtolower(trim($raw));
		return ($band === 'sat') ? 'SAT' : $band;
	}

	/**
	 * Normalise a ?mode= filter: '' when absent, else uppercased (COL_MODE /
	 * COL_SUBMODE are stored uppercase). Matched against either column, so a
	 * submode like FT8 is found regardless of how it was stored.
	 */
	protected function normalize_mode($raw) {
		if ($raw === null || $raw === '') {
			return '';
		}
		return strtoupper(trim($raw));
	}

	/**
	 * Normalise a ?callsign= filter: '' when absent, else uppercased (COL_CALL is
	 * stored uppercase). Validated with the same loose rule as the QSO import
	 * (letters, digits, / and -), so junk like spaces or commas is rejected with
	 * a 400 instead of silently matching nothing.
	 */
	protected function normalize_callsign($raw) {
		if ($raw === null || $raw === '') {
			return '';
		}
		$this->CI->load->model('logbook_model');
		if (!$this->CI->logbook_model->is_valid_callsign($raw)) {
			throw new Api_v2_exception('validation_error', 'Invalid callsign', 400);
		}
		return strtoupper(trim($raw));
	}

	/**
	 * Parse a comma-separated query parameter into a validated lowercase list,
	 * or null when the parameter is absent. Values outside $allowed produce a
	 * 400 carrying the allowed list, so a client can correct itself.
	 *
	 * @param string   $key     Query parameter name (e.g. "qsl_filter", "type").
	 * @param string[] $allowed Whitelist of accepted values.
	 * @return string[]|null
	 */
	protected function parse_type_list($key, $allowed) {
		$raw = $this->param($key);
		if ($raw === null || $raw === '') {
			return null;
		}
		$values = array_map('strtolower', array_map('trim', explode(',', $raw)));
		$invalid = array_diff($values, $allowed);
		if (!empty($invalid)) {
			throw new Api_v2_exception(
				'validation_error',
				'Invalid ' . $key . ' values: ' . implode(', ', $invalid),
				400,
				['allowed' => $allowed]
			);
		}
		return array_values(array_unique($values));
	}

	/**
	 * Parse a date query parameter in YYYY-MM-DD form, or '' when absent. The
	 * round-trip comparison rejects calendar-invalid dates like 2026-02-31,
	 * which DateTime would otherwise silently roll over into March.
	 *
	 * @return string '' or a valid Y-m-d date.
	 */
	protected function parse_date($key) {
		$raw = $this->param($key);
		if ($raw === null || $raw === '') {
			return '';
		}
		$raw = trim($raw);
		$date = DateTime::createFromFormat('Y-m-d', $raw);
		if ($date === false || $date->format('Y-m-d') !== $raw) {
			throw new Api_v2_exception(
				'validation_error',
				$key . ' must be a valid date',
				400,
				['format' => 'YYYY-MM-DD']
			);
		}
		return $raw;
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
