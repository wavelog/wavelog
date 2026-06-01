<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Worker
 *
 * Handles communication between the wavelog_worker Go service and Wavelog.
 * All endpoints except debug_status are protected by X-Worker-Secret header.
 */
class Worker extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->config->load('worker', TRUE, TRUE);
	}

	/**
	 * Returns a simple status summary for the Debug page (no secret required,
	 * but only accessible to logged-in admin users via AJAX).
	 * GET /worker/debug_status
	 */
	public function debug_status() {
		header('Content-Type: application/json');

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			http_response_code(403);
			echo json_encode(['success' => false]);
			return;
		}

		if (!$this->config->item('worker_enabled', 'worker')) {
			echo json_encode(['success' => true, 'disabled' => true, 'workers' => []]);
			return;
		}

		$secret = (string) $this->config->item('worker_secret', 'worker');

		$urls_cfg    = $this->config->item('worker_urls', 'worker');
		$worker_urls = is_array($urls_cfg) ? array_map(fn($u) => rtrim($u, '/'), $urls_cfg) : [];

		if (empty($worker_urls) || $secret === '') {
			echo json_encode(['success' => true, 'disabled' => true, 'workers' => []]);
			return;
		}

		$vip_url = rtrim((string) $this->config->item('worker_vip', 'worker'), '/');
		$vip     = null;
		if ($vip_url !== '') {
			$ch = curl_init($vip_url . '/internal/status');
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER    => true,
				CURLOPT_CONNECTTIMEOUT_MS => 300,
				CURLOPT_TIMEOUT_MS        => 800,
				CURLOPT_HTTPHEADER        => ['X-Worker-Secret: ' . $secret],
			]);
			curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			$vip = ['url' => $vip_url, 'alive' => $http_code === 200];
		}

		$workers = [];
		foreach ($worker_urls as $url) {
			$ch = curl_init($url . '/internal/status');
			curl_setopt_array($ch, [
				CURLOPT_RETURNTRANSFER    => true,
				CURLOPT_CONNECTTIMEOUT_MS => 300,
				CURLOPT_TIMEOUT_MS        => 800,
				CURLOPT_HTTPHEADER        => ['X-Worker-Secret: ' . $secret],
			]);
			$raw       = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			$stats = ($http_code === 200 && $raw) ? json_decode($raw, true) : null;
			$workers[] = [
				'public_url'        => $url,
				'alive'             => $http_code === 200,
				'version'           => $stats['version']           ?? null,
				'active_topics'     => $stats['active_topics']     ?? null,
				'connected_clients' => $stats['connected_clients'] ?? null,
				'worker_uptime'     => $stats['uptime']            ?? null,
				'cluster_nodes'     => $stats['cluster_nodes']     ?? null,
			];
		}

		echo json_encode(['success' => true, 'vip' => $vip, 'workers' => $workers]);
	}

}
