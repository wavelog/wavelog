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
	 * Single source of truth for the UI checkboxes and the dispatcher's
	 * enforcement. Scope ids follow the "<resource>:<read|write>" convention;
	 * the dispatcher derives the required scope from the resource name and
	 * the HTTP verb. Only list scopes that are actually enforceable.
	 */
	public static function scope_registry() {
		return [
			'qso:read'     		=> __('Read QSOs'),
			'qso:write'    		=> __('Create and update QSOs'),
			'qso:delete'   		=> __('Delete QSOs'),
			'station:read' 		=> __('Read station locations'),
			'statistics:read' 	=> __('Read Wavelog statistics'),
		];
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
	 * Track token usage: token last_used plus the owner's last_seen.
	 *
	 * @param int $token_id Token primary key.
	 * @param int $user_id  Token owner (known to the caller from authenticate_token()).
	 */
	function update_last_used($token_id, $user_id) {
		$this->db->set('last_used', 'NOW()', FALSE);
		$this->db->where('id', (int) $token_id);
		$this->db->update('api_token');

		$this->load->model('user_model');
		$this->user_model->set_last_seen($user_id);
	}
}
