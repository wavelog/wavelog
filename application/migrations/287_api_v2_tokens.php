<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * API v2 token table.
 *
 * Stores the new GitHub-style API tokens (prefix "wl2_") used by the REST API
 * v2. Unlike the legacy `api` table, only a SHA-256 hash of the token is
 * stored; the plaintext token is shown to the user exactly once at creation.
 * `scopes` is a comma-separated list of granted scopes (e.g. "qso:read,qso:write").
 * `created_by` mirrors the clubstation pattern of the legacy `api` table.
 */
class Migration_api_v2_tokens extends CI_Migration {

	public function up() {
		if (!$this->db->table_exists('api_token')) {
			$this->db->query("CREATE TABLE `api_token` (
				`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`user_id` bigint(20) NOT NULL,
				`created_by` int(11) NOT NULL,
				`token_name` varchar(100) NOT NULL,
				`token_hash` char(64) NOT NULL,
				`scopes` varchar(255) NOT NULL,
				`status` varchar(10) NOT NULL DEFAULT 'active',
				`expires_at` datetime DEFAULT NULL,
				`last_used` datetime DEFAULT NULL,
				`created_at` timestamp NOT NULL DEFAULT current_timestamp(),
				PRIMARY KEY (`id`),
				UNIQUE KEY `api_token_hash_uq` (`token_hash`),
				KEY `api_token_user_id_idx` (`user_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
		}
	}

	public function down() {
		$this->db->query("DROP TABLE IF EXISTS `api_token`");
	}
}
