<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Worker_publisher
 *
 * Thin HTTP client that manages topics and broadcasts events to the wavelog_worker Go service.
 *
 * Usage:
 *     $this->load->library('Worker_publisher');
 *     $this->worker_publisher->register_topic('session.42');
 *     $this->worker_publisher->publish('session.42', ['event' => 'qso_updated', ...]);
 *     $this->worker_publisher->unregister_topic('session.42');
 *
 * If not configured, all methods are no-ops. Errors are intentionally swallowed — the
 * worker is an optional layer and must never cause a successful QSO save to appear failed.
 */
class Worker_publisher {

	private string $url;
	private string $secret;
	private int    $timeout_ms;
	private bool   $enabled;

	public function __construct() {
		$CI =& get_instance();
		$CI->config->load('worker', TRUE, TRUE);

		$this->secret     = (string) $CI->config->item('worker_secret', 'worker');
		$timeout_seconds  = (float)  $CI->config->item('worker_timeout', 'worker');
		$this->timeout_ms = (int) max(100, $timeout_seconds * 1000);
		$this->url        = rtrim((string) $CI->config->item('worker_url', 'worker'), '/');
		$this->enabled    = (bool) $CI->config->item('worker_enabled', 'worker')
		                    && $this->url !== ''
		                    && $this->secret !== '';
	}

	/**
	 * Registers a topic with the Worker so browsers can connect to it.
	 * Call when a contest session becomes active (e.g. logging_engine page load).
	 * Idempotent — safe to call on every page load.
	 *
	 * @param string $topic        e.g. "session.42"
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
	 *
	 * @param string $topic  e.g. "session.42"
	 */
	public function unregister_topic(string $topic): void {
		$this->_internal_post('/internal/unregister', ['topic' => $topic]);
	}

	/**
	 * Broadcasts a payload to all clients subscribed to topic.
	 * If the Worker returns 404 (unknown topic after a restart), re-registers and retries once.
	 *
	 * @param string $topic   e.g. "session.42"
	 * @param array  $payload Forwarded as-is as the push envelope payload.
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
		curl_close($ch);

		if ($curl_err !== '') {
			log_message('error', 'Worker_publisher: publish(' . $topic . ') failed: ' . $curl_err);
			return;
		}

		// 404 means the Worker restarted and lost the registry. Re-register and retry once.
		if ($http_code === 404) {
			$this->register_topic($topic);
			$this->_internal_post('/internal/publish', ['topic' => $topic, 'payload' => $payload]);
			return;
		}

		if ($http_code !== 200) {
			log_message('error', 'Worker_publisher: publish(' . $topic . ') returned HTTP ' . $http_code);
		}
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
		curl_close($ch);

		if ($curl_err !== '') {
			log_message('error', 'Worker_publisher: POST ' . $path . ' failed: ' . $curl_err);
		} elseif ($http_code !== 200) {
			log_message('error', 'Worker_publisher: POST ' . $path . ' returned HTTP ' . $http_code);
		}
	}
}
