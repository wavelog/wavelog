<?php

/* Api_v2_model.php
 *
 * Manages the API v2 tokens (table `api_token`).
 *
 * v2 tokens are GitHub-style: "wl2_" prefix + cryptographically secure random
 * part. Only a SHA-256 hash is stored in the database; the plaintext token is
 * returned exactly once at creation time. Each token carries a set of granular
 * scopes (see scope_registry()) instead of the legacy r/rw rights.
 *
 * The legacy v1 keys (table `api`, Api_model) are untouched by this model.
 */

class Api_v2_model extends CI_Model {

	/** Prefix of every v2 token; also used to fast-reject legacy keys. */
	const TOKEN_PREFIX = 'wl2_';

	/**
	 * Central scope registry: scope id => human-readable label.
	 *
	 * Derived automatically from the resource classes in
	 * application/libraries/api_v2/ rather than hand-maintained: each resource
	 * declares its base scope ($scope) plus per-suffix labels (scope_labels()),
	 * and Api_v2_resource::scope_definitions() expands those into concrete
	 * "<resource>:<read|write|delete>" ids based on the verbs the resource
	 * actually implements. This keeps the UI checkboxes and token validation in
	 * lock-step with what the dispatcher enforces — adding a resource (or a verb)
	 * needs no change here. Result is memoized for the request.
	 */
	public static function scope_registry() {
		static $registry = null;
		if ($registry === null) {
			$registry = self::collect_scopes();
		}

		return $registry;
	}

	/**
	 * The subset of scope_registry() the current session may actually hand out.
	 *
	 * Not memoized: the session can change within a request (impersonation).
	 */
	public static function grantable_scope_registry() {
		return self::collect_scopes(true);
	}

	/**
	 * Walk the resource classes and collect their scope definitions.
	 *
	 * @param bool $grantable Also require is_grantable(), i.e. filter down to
	 *                        what the current session may hand out. Availability
	 *                        is checked either way, which is what keeps the
	 *                        grantable set a subset of the full registry.
	 */
	private static function collect_scopes($grantable = false) {
		$registry = [];

		foreach (glob(APPPATH . 'libraries/api_v2/*_resource.php') as $file) {
			require_once $file;
			// Convention: file "<Resource>_resource.php" holds class
			// "<Resource>_resource" (see Api_v2::load_resource()).
			$class = basename($file, '.php');
			// Skip the abstract base and anything that is not a real resource.
			if (!class_exists($class) || !is_subclass_of($class, 'Api_v2_resource')) {
				continue;
			}
			if (!$class::is_available()) {
				continue;
			}
			if ($grantable && !$class::is_grantable()) {
				continue;
			}
			$registry += $class::scope_definitions();
		}

		return $registry;
	}

	/**
	 * Scope presets offered as one-click buttons in the create-token dialog.
	 *
	 * A preset is nothing but a named selection of scopes: picking one only
	 * ticks the matching checkboxes, the form still submits the individual
	 * scopes and the controller still validates them. Presets exist so users do
	 * not have to know which scopes a well-known third party tool needs.
	 *
	 * Two of them are derived from the registry ("read everything" / "full
	 * access") so they keep covering new resources automatically; the tool
	 * specific ones are hand-maintained and silently drop scopes that do not
	 * exist (yet), which keeps this list safe across versions.
	 *
	 * @return array<string,array{name:string,description:string,icon:string,class:string,scopes:string[]}>
	 */
	public static function preset_registry() {
		$all = array_keys(self::grantable_scope_registry());
		$read_only = array_values(array_filter($all, function ($scope) {
			return substr($scope, -5) === ':read';
		}));

		$presets = [
			'waveloggate' => [
				'name'        => 'WaveLogGate' . __(' (min. 2.1.0)'),
				'description' => __("Radio control and QSO upload from WSJT-X, JTDX and friends. Requires at least version 2.1.0 of WaveLogGate."),
				'icon'        => 'fas fa-satellite-dish',
				'class'       => 'btn-primary',
				'scopes'      => ['station:read', 'qso:write', 'radio:write'],
			],
			'readonly' => [
				'name'        => __("Read-only"),
				'description' => __("Read access to everything, no changes to your data."),
				'icon'        => 'fas fa-book-open',
				'class'       => 'btn-primary',
				'scopes'      => $read_only,
			],
			'statistics' => [
				'name'        => __("Statistics only"),
				'description' => __("Statistics only, no changes to your data."),
				'icon'        => 'fas fa-chart-bar',
				'class'       => 'btn-primary',
				'scopes'      => ['statistic:read'],
			],
		];

		// Drop scopes (and then empty presets) that this instance does not know.
		foreach ($presets as $key => $preset) {
			$presets[$key]['scopes'] = array_values(array_intersect($preset['scopes'], $all));
			if (empty($presets[$key]['scopes'])) {
				unset($presets[$key]);
			}
		}

		return $presets;
	}

	/**
	 * Create a new token and return the plaintext token string (shown once),
	 * or false when the input is invalid.
	 *
	 * @param string      $name       Display name for the token (max 100 chars).
	 * @param array       $scopes     Scope ids; validated against scope_registry().
	 * @param string|null $expires_at Expiry as "Y-m-d H:i:s", or null for never.
	 * @param int|null    $user_id    Token owner; defaults to the session user.
	 * @param int|null    $created_by Creator (clubstation); defaults to the owner.
	 * @return string|false
	 */
	function create_token($name, $scopes, $expires_at = null, $user_id = null, $created_by = null) {
		$name = trim($name);
		if ($name === '' || mb_strlen($name) > 100) {
			return false;
		}

		// Only known scopes, at least one. Scopes are independent: a token can
		// be write-only or delete-only on purpose (e.g. a logger that may push
		// QSOs but must not read the logbook).
		$valid = array_keys(self::scope_registry());
		$scopes = array_values(array_unique(array_intersect((array) $scopes, $valid)));
		if (empty($scopes)) {
			return false;
		}

		$user_id = $user_id ?? $this->session->userdata('user_id');
		$created_by = $created_by ?? $user_id;

		// 4 char prefix + 40 hex chars = 44 chars, cryptographically secure.
		$token = self::TOKEN_PREFIX . bin2hex(random_bytes(20));

		$data = array(
			'user_id'    => $user_id,
			'created_by' => $created_by,
			'token_name' => xss_clean($name),
			'token_hash' => hash('sha256', $token),
			'scopes'     => implode(',', $scopes),
			'status'     => 'active',
			'expires_at' => $expires_at,
		);

		if ($this->db->insert('api_token', $data)) {
			return $token;
		}
		return false;
	}

	/**
	 * Authenticate a plaintext token by its SHA-256 hash.
	 *
	 * Returns null for unknown or disabled tokens. Expiry is reported via the
	 * 'expired' flag so the caller can answer with a distinct error code.
	 *
	 * @param string $token Plaintext token as presented by the client.
	 * @return array|null { id, user_id, created_by, scopes => string[], expired => bool }
	 */
	function authenticate_token($token) {
		$this->db->where('token_hash', hash('sha256', $token));
		$query = $this->db->get('api_token');

		if ($query->num_rows() !== 1) {
			return null;
		}

		$row = $query->row();
		if ($row->status !== 'active') {
			return null;
		}

		return [
			'id'         => (int) $row->id,
			'user_id'    => (int) $row->user_id,
			'created_by' => (int) $row->created_by,
			'scopes'     => explode(',', $row->scopes),
			'expired'    => ($row->expires_at !== null && strtotime($row->expires_at) < time()),
		];
	}

	/**
	 * All tokens of the current session user, for the management UI.
	 * Mirrors the clubstation visibility rules of Api_model::keys().
	 */
	function get_tokens_for_user() {
		$binding = [];
		$user_id = $this->session->userdata('user_id');
		$clubstation = $this->session->userdata('clubstation');
		$impersonate = $this->session->userdata('impersonate');

		if ($clubstation == 1 && $impersonate == 1) {
			$sql = "SELECT api_token.*, users.user_callsign
					FROM api_token
					JOIN users ON api_token.created_by = users.user_id
					WHERE api_token.user_id = ?";
			$binding[] = $user_id;

			if (!clubaccess_check(9)) {
				$sql .= " AND api_token.created_by = ?";
				$binding[] = $this->session->userdata('source_uid');
			}
		} else {
			$sql = "SELECT * FROM api_token WHERE user_id = ?";
			$binding[] = $user_id;
		}

		$sql .= " ORDER BY created_at DESC";

		return $this->db->query($sql, $binding);
	}

	/**
	 * Delete a token owned by the current session user.
	 * Clubstation: non-officers may only delete tokens they created themselves.
	 */
	function revoke_token($id) {
		$this->db->where('id', (int) $id);
		$this->db->where('user_id', $this->session->userdata('user_id'));

		if ($this->session->userdata('clubstation') == 1
			&& $this->session->userdata('impersonate') == 1
			&& !clubaccess_check(9)) {
			$this->db->where('created_by', $this->session->userdata('source_uid'));
		}

		$this->db->delete('api_token');
	}

	/**
	 * Delete the tokens a member issued for a clubstation.
	 *
	 * @param int      $club_id The clubstation.
	 * @param int|null $user_id One member, or null for every member of the club.
	 */
	function revoke_club_tokens($club_id, $user_id = null) {
		if (!is_numeric($club_id) || (int) $club_id < 1) {
			return;
		}

		$this->db->where('user_id', (int) $club_id);
		if ($user_id !== null) {
			$this->db->where('created_by', (int) $user_id);
		} else {
			// A clubstation can hold tokens of its own (club_direct login);
			// those belong to the account, not to a membership.
			$this->db->where('created_by !=', (int) $club_id);
		}
		$this->db->delete('api_token');
	}

	/**
	 * Metadata of a single token, for the whoami endpoint (Token_resource).
	 * Never exposes the hash; the plaintext token is not stored at all.
	 *
	 * @param int $id Token primary key.
	 * @return array|null { token_name, expires_at, created_at, last_used, status }
	 */
	function get_token_meta($id) {
		$this->db->where('id', (int) $id);
		$query = $this->db->get('api_token');
		if ($query->num_rows() !== 1) {
			return null;
		}
		$row = $query->row();
		return [
			'token_name' => $row->token_name,
			'expires_at' => $row->expires_at,
			'created_at' => $row->created_at,
			'last_used'  => $row->last_used,
			'status'     => $row->status,
		];
	}

	/**
	 * Track token usage: token last_used plus the owner's last_seen.
	 *
	 * @param int $token_id Token primary key.
	 * @param int $user_id  Token owner (known to the caller from authenticate_token()).
	 */
	function update_last_used($token_id, $user_id) {
		$this->db->set('last_used', 'NOW()', FALSE);
		$this->db->where('id', (int) $token_id);
		$this->db->update('api_token');

		$this->user_model->set_last_seen($user_id);
	}
}
