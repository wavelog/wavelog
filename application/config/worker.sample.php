<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Wavelog Worker Configuration
| -------------------------------------------------------------------------
|
| Optional WebSocket gateway for real-time updates in the browser. Requires
| the separate wavelog_worker service. Set worker_enabled = true to activate.
| Wavelog falls back to the classic AJAX heartbeat when disabled.
|
*/

/**
 * Enable or disable the Worker integration entirely.
 */
$config['worker_enabled'] = false;

/**
 * Internal URL of the Worker (PHP -> Worker, HTTP).
 * Single instance: the URL of your worker.
 * Cluster: the URL of your load balancer service in front of the workers.
 * The cluster nodes are discovered automatically (Worker Version 0.3.0 or newer).
 * A cluster needs a Redis / Valkey instance, see the
 * wavelog_worker sample config.yaml.
 */
$config['worker_url'] = 'http://127.0.0.1:9001';

/**
 * Deprecated, will be removed in Wavelog 1.0.0: worker_vip and worker_urls.
 * Both are still read when worker_url is empty. Use worker_url instead
 * (worker_vip -> worker_url, or the first worker_urls entry -> worker_url).
 * Listing every node in worker_urls is only needed with Workers older than 0.3.0.
 */
// $config['worker_vip'] = '';
// $config['worker_urls'] = [
//     'http://127.0.0.1:9001',
// ];

/**
 * Shared secret — must match worker_secret in the worker's config.yaml.
 * Generate with: openssl rand -hex 32
 */
$config['worker_secret'] = '';

/**
 * Timeout for publish calls in seconds (float). Keep it short:
 * a slow worker must not block QSO saves.
 */
$config['worker_timeout'] = 1.0;

/**
 * Public WebSocket URL for the browser (Browser -> Worker).
 * May differ from worker_url when behind a reverse proxy or in Docker.
 * Format: ws://host:port or wss://host:port. Empty = no WebSocket in browser.
 */
$config['worker_client_url'] = 'ws://log.example.org:9000';

/**
 * Optional: Override the default HMAC token expiration time (in seconds) for browser connections.
 * Default: 24h (86400 seconds). Can not be 0 or negative.
 * 
 * Important:
 * This only controls the token expiration for the browser. The worker itself has another TTL which
 * is applied for the topics themselves. This is configured in the worker's config.yaml (default 24h). 
 * If you want to change the token expiration for the browser, you may want to also change the topic
 * expiration in the worker's config.yaml to match. Otherwise, the token may be valid but the topic may have expired.
 * Also keep in mind that there is also a PHP session expiration which may be shorter than the token 
 * expiration. If the PHP session expires, the token will be invalidated as well. You can control the
 * PHP session expiration in application/config/config.php
 * 
 * Ah and before I forget: Your reverse proxy may also have a timeout for WebSocket connections.
 * 
 * Just reload your damn browser once a day :-D
 */
// $config['worker_token_expiration'] = 86400;