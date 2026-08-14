<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Worker
 *
 * Manages communication with the wavelog_worker Go service.
 *
 * Usage:
 *     $this->load->library('Worker');
 *     $this->worker->register_topic('contest_session.abc123');
 *     $this->worker->publish('contest_session.abc123', ['event' => 'qso_updated', ...]);
 *     $this->worker->unregister_topic('contest_session.abc123');
 *
 * If not configured or disabled, all methods are no-ops. Errors are intentionally
 * swallowed — the worker is optional and must never cause a QSO save to appear failed.
 */
class Worker {

	private string $url;
	private string $secret;
	private int    $timeout_ms;
	private bool   $enabled;
	private int    $token_expiration;

	public function __construct() {
		$CI =& get_instance();
		$CI->config->load('worker', TRUE, TRUE);

		$this->secret     = (string) $CI->config->item('worker_secret', 'worker');
		$timeout_seconds  = (float)  $CI->config->item('worker_timeout', 'worker');
		$this->timeout_ms = (int) max(100, $timeout_seconds * 1000);

		$vip_cfg   = (string) $CI->config->item('worker_vip', 'worker');
		$urls_cfg  = $CI->config->item('worker_urls', 'worker');
		if ($vip_cfg !== '') {
			$this->url = rtrim($vip_cfg, '/');
		} elseif (is_array($urls_cfg) && !empty($urls_cfg)) {
			$this->url = rtrim($urls_cfg[0], '/');
		} else {
			$this->url = '';
		}

		$this->enabled    = (bool) $CI->config->item('worker_enabled', 'worker')
		                    && $this->url !== ''
		                    && $this->secret !== '';
		$this->token_expiration = (int) ($CI->config->item('worker_token_expiration', 'worker') ?? 86400); // Default 24h
		// if token_expiration is set to 0 or negative, we throw an exception to prevent misconfiguration
		if ($this->token_expiration <= 0) {
			throw new InvalidArgumentException('worker_token_expiration must be positive');
		}
	}

	/**
	 * Returns true if the Worker is configured and enabled.
	 */
	public function is_enabled(): bool {
		return $this->enabled;
	}

	/**
	 * Registers a topic with the Worker so browsers can connect to it.
	 * Idempotent — safe to call on every page load.
	 *
	 * @param string $topic          e.g. "contest_session.abc123"
	 * @param bool   $require_token  Whether browsers must present a valid HMAC token.
	 */
	public function register_topic(string $topic, bool $require_token = true): void {
		$this->_internal_post('/internal/register', [
			'topic' => $topic,
			'meta'  => ['require_token' => $require_token],
		]);
	}

	/**
	 * Unregisters a topic. Call when the session is deleted.
	 * Idempotent — safe even if the Worker does not know the topic.
	 */
	public function unregister_topic(string $topic): void {
		$this->_internal_post('/internal/unregister', ['topic' => $topic]);
	}

	/**
	 * Broadcasts a payload to all clients subscribed to topic.
	 * If the Worker returns 404 (unknown topic after a restart), re-registers and retries once.
	 *
	 * @param string $topic
	 * @param array  $payload  Forwarded as-is as the push envelope payload.
	 */
	public function publish(string $topic, array $payload): void {
		if (!$this->enabled) {
			return;
		}

		$body = json_encode(['topic' => $topic, 'payload' => $payload]);
		if ($body === false) {
			return;
		}

		$ch = curl_init($this->url . '/internal/publish');
		curl_setopt_array($ch, [
			CURLOPT_POST              => true,
			CURLOPT_POSTFIELDS        => $body,
			CURLOPT_RETURNTRANSFER    => true,
			CURLOPT_CONNECTTIMEOUT_MS => 500,
			CURLOPT_TIMEOUT_MS        => $this->timeout_ms,
			CURLOPT_HTTPHEADER        => [
				'Content-Type: application/json',
				'X-Worker-Secret: ' . $this->secret,
			],
		]);
		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_err  = curl_error($ch);

		if ($curl_err !== '') {
			log_message('error', 'Worker: publish(' . $topic . ') failed: ' . $curl_err);
			return;
		}

		// 404 means the Worker restarted and lost the registry. Re-register and retry once.
		if ($http_code === 404) {
			$this->register_topic($topic);
			$this->_internal_post('/internal/publish', ['topic' => $topic, 'payload' => $payload]);
			return;
		}

		if ($http_code !== 200) {
			log_message('error', 'Worker: publish(' . $topic . ') returned HTTP ' . $http_code);
		}
	}

	/**
	 * Returns the public WebSocket URL for the browser (worker_client_url from config).
	 * Empty string if not configured.
	 */
	public function client_url(): string {
		$CI =& get_instance();
		return (string) $CI->config->item('worker_client_url', 'worker');
	}

	/**
	 * Generates a signed HMAC token for browser WebSocket authentication.
	 * The Go worker verifies this locally — no PHP callback needed.
	 * Returns empty string if the worker secret is not configured.
	 *
	 * The token is bound to a single topic. The worker compares the topic claim
	 * against the topic the browser tries to subscribe to, so a token for one
	 * topic cannot be used to join another.
	 *
	 * @param string $topic        e.g. "contest_session.42" or "radio.5"
	 * @param int    $ttl_seconds  Default 24h
	 */
	public function create_token(string $topic, $ttl_seconds = null): string {
		if ($this->secret === '') {
			return '';
		}

		if ($ttl_seconds === null) {
			$ttl_seconds = $this->token_expiration;
		}

		$CI =& get_instance();
		$user_id = intval($CI->session->userdata('source_uid') ?: $CI->session->userdata('user_id'));

		$claims  = [
			'user_id' => $user_id,
			'topic'   => $topic,
			'expires' => time() + $ttl_seconds,
		];

		$encoded = bin2hex(json_encode($claims));
		$sig     = hash_hmac('sha256', $encoded, $this->secret);
		return $encoded . '.' . $sig;
	}

	/**
	 * Live status of the worker cluster, fanned out to every configured node's
	 * /internal/status endpoint. Reused by the debug page and the statistics
	 * API so the fan-out lives in one place.
	 *
	 * @return array {
	 *   enabled: bool,
	 *   vip: string|null,
	 *   nodes_total: int,
	 *   nodes_alive: int,
	 *   active_topics: int|null,       // cluster sum, null when no node answered
	 *   connected_clients: int|null,   // cluster sum, null when no node answered
	 *   nodes: array<int, array{url,alive,version,active_topics,connected_clients,uptime}>
	 * }
	 */
	public function status(): array {
		$CI =& get_instance();
		$vip_url = rtrim((string) $CI->config->item('worker_vip', 'worker'), '/');

		$result = [
			'enabled'           => $this->enabled,
			'vip'               => $vip_url !== '' ? $vip_url : null,
			'nodes_total'       => 0,
			'nodes_alive'       => 0,
			'active_topics'     => null,
			'connected_clients' => null,
			'nodes'             => [],
		];

		if (!$this->enabled) {
			return $result;
		}

		$urls_cfg = $CI->config->item('worker_urls', 'worker');
		$urls = is_array($urls_cfg) ? array_map(fn($u) => rtrim($u, '/'), $urls_cfg) : [];
		if (empty($urls) && $this->url !== '') {
			$urls = [$this->url];
		}
		$result['nodes_total'] = count($urls);

		$topics = 0;
		$clients = 0;
		$have_metrics = false;
		foreach ($urls as $url) {
			$node = $this->fetch_node_status($url);
			if ($node['alive']) {
				$result['nodes_alive']++;
			}
			if ($node['active_topics'] !== null) {
				$topics += (int) $node['active_topics'];
				$have_metrics = true;
			}
			if ($node['connected_clients'] !== null) {
				$clients += (int) $node['connected_clients'];
				$have_metrics = true;
			}
			$result['nodes'][] = $node;
		}
		if ($have_metrics) {
			$result['active_topics']     = $topics;
			$result['connected_clients'] = $clients;
		}

		return $result;
	}

	/**
	 * Query a single worker node's /internal/status. Never throws: an
	 * unreachable node is reported as alive=false with null metrics.
	 */
	private function fetch_node_status(string $url): array {
		$ch = curl_init($url . '/internal/status');
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER    => true,
			CURLOPT_CONNECTTIMEOUT_MS => 300,
			CURLOPT_TIMEOUT_MS        => 800,
			CURLOPT_HTTPHEADER        => ['X-Worker-Secret: ' . $this->secret],
		]);
		$raw       = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$stats = ($http_code === 200 && $raw) ? json_decode($raw, true) : null;

		return [
			'url'               => $url,
			'alive'             => $http_code === 200,
			'version'           => $stats['version']           ?? null,
			'active_topics'     => $stats['active_topics']     ?? null,
			'connected_clients' => $stats['connected_clients'] ?? null,
			'uptime'            => $stats['uptime']            ?? null,
		];
	}

	/**
	 * Shared fire-and-forget POST to a Worker internal API endpoint.
	 */
	private function _internal_post(string $path, array $body): void {
		if (!$this->enabled) {
			return;
		}

		$encoded = json_encode($body);
		if ($encoded === false) {
			return;
		}

		$ch = curl_init($this->url . $path);
		curl_setopt_array($ch, [
			CURLOPT_POST              => true,
			CURLOPT_POSTFIELDS        => $encoded,
			CURLOPT_RETURNTRANSFER    => true,
			CURLOPT_CONNECTTIMEOUT_MS => 500,
			CURLOPT_TIMEOUT_MS        => $this->timeout_ms,
			CURLOPT_HTTPHEADER        => [
				'Content-Type: application/json',
				'X-Worker-Secret: ' . $this->secret,
			],
		]);
		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_err  = curl_error($ch);

		if ($curl_err !== '') {
			log_message('error', 'Worker: POST ' . $path . ' failed: ' . $curl_err);
		} elseif ($http_code !== 200) {
			log_message('error', 'Worker: POST ' . $path . ' returned HTTP ' . $http_code);
		}
	}
}
