<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Amsatstatus extends CI_Controller {

	function __construct() {
		parent::__construct();

		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$this->load->driver('cache', [
			'adapter' => $this->config->item('cache_adapter') ?? 'file',
			'backup' => $this->config->item('cache_backup') ?? 'file',
			'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
		]);

		$cache_key = 'amsatstatus_reports_24h';
		$json = $this->cache->get($cache_key);

		if ($json === false) {
			$url = 'https://www.amsat.org/status/api/v1/reports.php?hours=24&limit=500';
			$ctx = stream_context_create(['http' => [
				'method' => 'GET',
				'timeout' => 15,
				'user_agent' => 'Wavelog/'.$this->optionslib->get_option('version')
			]]);
			$fetched = @file_get_contents($url, false, $ctx);
			if ($fetched === false) {
				$this->session->set_flashdata('message', __("The AMSAT status service is currently unavailable. Please try again later."));
				$data['matrix'] = [];
				$data['sat_order'] = [];
				$data['display_names'] = [];
				$data['wl_link'] = [];
				$data['next_pass'] = [];
				$data['now'] = time();
				$data['page_title'] = __("AMSAT Satellite Status");
				$this->load->view('interface_assets/header', $data);
				$this->load->view('/amsatstatus/index', $data);
				$this->load->view('interface_assets/footer', []);
				return;
			}
			$this->cache->save($cache_key, $fetched, 60*15);
			$json = $fetched;
		}

		$decoded = json_decode($json, true);
		$reports = $decoded['data'] ?? [];

		$status_priority = [
			'Heard'          => 1,
			'Crew Active'    => 2,
			'Telemetry Only' => 3,
			'Not Heard'      => 4,
		];

		$now = time();
		$buckets = [];
		$sat_latest = [];
		$display_names = [];

		foreach ($reports as $r) {
			$name = $r['name'] ?? '';
			if ($name === '') continue;
			if (!empty($r['satellite_display_name'])) {
				$display_names[$name] = $r['satellite_display_name'];
			} elseif (!isset($display_names[$name])) {
				$display_names[$name] = $name;
			}

			$epoch = strtotime($r['reported_time'] ?? '');
			if ($epoch === false) continue;
			$age_h = (int)floor(($now - $epoch) / 3600);
			if ($age_h < 0 || $age_h > 23) continue;

			$col = $age_h;
			$status = $r['report'] ?? '';
			$callsign = $r['callsign'] ?? '';
			$grid = $r['grid_square'] ?? '';

			if (!isset($buckets[$name][$col])) {
				$buckets[$name][$col] = [
					'counts' => [],
					'reporters' => [],
					'winning' => $status,
					'winning_priority' => $status_priority[$status] ?? 99,
					'total' => 0,
					'latest_epoch' => $epoch,
					'latest_callsign' => $callsign,
					'latest_grid' => $grid,
				];
			}
			$cell =& $buckets[$name][$col];
			$cell['counts'][$status] = ($cell['counts'][$status] ?? 0) + 1;
			$cell['total']++;
			$cell['reporters'][] = ['epoch' => $epoch, 'callsign' => $callsign, 'grid' => $grid, 'status' => $status];

			$p = $status_priority[$status] ?? 99;
			if ($p < $cell['winning_priority']) {
				$cell['winning'] = $status;
				$cell['winning_priority'] = $p;
			}
			if ($epoch > $cell['latest_epoch']) {
				$cell['latest_epoch'] = $epoch;
				$cell['latest_callsign'] = $callsign;
				$cell['latest_grid'] = $grid;
			}
			unset($cell);

			if (!isset($sat_latest[$name]) || $epoch > $sat_latest[$name]) {
				$sat_latest[$name] = $epoch;
			}
		}

		foreach ($buckets as $name => &$cells) {
			foreach ($cells as &$cell) {
				usort($cell['reporters'], function($a, $b) { return $b['epoch'] - $a['epoch']; });
			}
			unset($cell);
		}
		unset($cells);

		$sat_order = array_keys($sat_latest);
		usort($sat_order, fn($a, $b) => strnatcasecmp($display_names[$a] ?? $a, $display_names[$b] ?? $b));

		$matrix = [];
		foreach ($buckets as $name => $cells) {
			$row = [];
			for ($col = 0; $col < 24; $col++) {
				$row[$col] = $cells[$col] ?? null;
			}
			$matrix[$name] = $row;
		}

		$this->load->model('satellite_model');
		$this->load->model('stations');
		$this->load->library('satpredict');

		$wl_map = [];
		foreach ($this->satellite_model->get_all_satellites_with_tle() as $wl) {
			$base = $wl->satname ?: ($wl->displayname ?? '');
			if ($base === '') continue;
			$key = strtoupper(trim(preg_replace('/[\[_].*/', '', $base)));
			if ($key !== '' && !isset($wl_map[$key])) {
				$wl_map[$key] = $wl;
			}
		}

		$grid = $this->stations->find_gridsquare();
		$have_grid = ($grid && strtoupper((string)$grid) !== '0');

		$alias = [
			'ISS'      => 'ARISS',
			'SONATE-2' => 'SONATE',
		];
		$prefix_alias = [
			'TEVEL2-' => 'TEV2-',
		];

		$wl_link = [];
		$next_pass = [];
		foreach ($sat_order as $name) {
			$key = strtoupper(trim(preg_replace('/[\[_].*/', '', $name)));
			if (isset($alias[$key])) {
				$key = $alias[$key];
			} else {
				foreach ($prefix_alias as $from => $to) {
					if (str_starts_with($key, $from)) {
						$key = $to . substr($key, strlen($from));
						break;
					}
				}
			}
			$wl = $wl_map[$key] ?? null;
			if (!$wl) continue;
			$wl_link[$name] = $wl->satname ?: $wl->displayname;
			if ($have_grid) {
				$pass = $this->satpredict->next_pass($wl, $grid, 0, 1);
				if ($pass) {
					$next_pass[$name] = [
						'time'  => Predict_Time::daynum2readable($pass->aos, 'UTC', 'H:i'),
						'maxel' => isset($pass->max_el) ? (int)round($pass->max_el) : null,
					];
				}
			}
		}

		$data['matrix'] = $matrix;
		$data['sat_order'] = $sat_order;
		$data['display_names'] = $display_names;
		$data['wl_link'] = $wl_link;
		$data['next_pass'] = $next_pass;
		$data['now'] = $now;
		$data['page_title'] = __("AMSAT Satellite Status");

		$footerData = [];
		$footerData['scripts'] = ['assets/js/sections/amsatstatus.js'];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('/amsatstatus/index', $data);
		$this->load->view('interface_assets/footer', $footerData);
	}
}
