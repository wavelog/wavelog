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

// Enable or disable the Worker integration entirely.
$config['worker_enabled'] = false;

// Internal URLs of wavelog_worker instances (PHP -> Worker, HTTP).
// Single instance: one entry. Cluster: one entry per node.
// PHP publishes to the first entry; the debug page shows status of all nodes.
$config['worker_urls'] = [
    'http://127.0.0.1:9001',
];

// Shared secret — must match worker_secret in the worker's config.yaml.
// Generate with: openssl rand -hex 32
$config['worker_secret'] = '';

// Timeout for publish calls in seconds (float). Keep it short:
// a slow worker must not block QSO saves.
$config['worker_timeout'] = 1.0;

// Public WebSocket URL for the browser (Browser -> Worker).
// May differ from worker_url when behind a reverse proxy or in Docker.
// Format: ws://host:port or wss://host:port. Empty = no WebSocket in browser.
$config['worker_client_url'] = 'ws://log.example.org:9000';
