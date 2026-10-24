<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Propagation extends CI_Controller {

	public function __construct() {
		parent::__construct();
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}
		$this->load->library('qra');
	}

	public function index() {
		$this->load->library('Propagation_data');
		$data['reference_ready'] = $this->propagation_data->is_ready();
		$data['reference_revision'] = $this->propagation_data->revision();
		$this->load->model('stations');
		$station_id = $this->session->userdata('station_profile_id') ?? 0;
		if (!$this->stations->check_station_is_accessible($station_id)) {
			$station_id = $this->stations->find_active();
		}
		$station = $station_id ? $this->stations->profile($station_id)->row() : null;

		$data['page_title'] = __("Propagation");
		$data['homegrid'] = $station ? strtoupper(explode(',', $station->station_gridsquare ?? '')[0]) : '';
		$power = $station->station_power ?? null;
		$data['station_power'] = is_numeric($power) && $power >= 1 && $power <= 5000 ? (float)$power : 100;
		$data['settings_key'] = 'wavelog.propagation.p533.' . (int)$this->session->userdata('user_id') . '.' . (int)$station_id;
		$this->load->view('interface_assets/header', $data);
		$this->load->view('propagation/index', $data);
		$this->load->view('interface_assets/footer', ['scripts' => [
			'assets/js/chart.js',
			'assets/js/sections/propagation_model.js',
			'assets/js/sections/propagation.js',
		]]);
	}

	public function data() {
		$this->load->library('Propagation_data');
		$revision = $this->input->get('revision', false);
		if ($revision !== null && $revision !== $this->propagation_data->revision()) {
			return $this->json_error(__("Propagation reference version changed. Reload this page."), 400);
		}
		try {
			$path = $this->propagation_data->verified_path($this->input->get('file', false));
		} catch (InvalidArgumentException $error) {
			log_message('error', $error->getMessage());
			return $this->json_error(__("Unknown propagation reference file."), 400);
		} catch (RuntimeException $error) {
			log_message('error', $error->getMessage());
			return $this->json_error(__("Propagation reference data is not installed or is damaged. Ask an administrator to run the propagation reference data job in Cron Manager."), 503);
		}
		session_write_close();
		header('Content-Type: application/octet-stream');
		header('Content-Length: ' . filesize($path));
		header('Cache-Control: private, max-age=86400');
		header('X-Content-Type-Options: nosniff');
		if (readfile($path) === false) {
			log_message('error', 'Cannot read propagation reference file: ' . $path);
		}
	}

	public function solar() {
		$this->load->library('Propagation_solar');
		try {
			return $this->output->set_content_type('application/json')
				->set_output(json_encode($this->propagation_solar->snapshot(), JSON_THROW_ON_ERROR));
		} catch (RuntimeException $error) {
			log_message('error', 'Propagation sunspot data unavailable: ' . $error->getMessage());
			return $this->json_error(__("Smoothed sunspot data is unavailable. Ask an administrator to run 'Update propagation smoothed sunspot numbers (NOAA)' in Cron Manager, or select Manual and enter a value."), 503);
		}
	}

	public function lookup() {
		$input = $this->input->get('target', true);
		if (!is_string($input) || strlen($input) > 32 || trim($input) === '') {
			return $this->json_error(__("Enter a callsign or a Maidenhead locator."), 400);
		}
		$target = strtoupper(trim($input));
		if ($this->valid_locator($target)) {
			return $this->location($target, $target, __("Locator"));
		}
		if (!preg_match('/^[A-Z0-9]+(?:\/[A-Z0-9]+)*$/D', $target) || !preg_match('/[0-9]/', $target)) {
			return $this->json_error(__("Enter a valid callsign or a Maidenhead locator."), 400);
		}

		$this->load->model('logbook_model');
		$local = $this->logbook_model->get_callsign_all_info($target);
		if ($this->valid_locator($local['qra'] ?? '')) {
			return $this->location($target, $local['qra'], __("Logbook"));
		}

		session_write_close();
		$this->load->library('callbook');
		$remote = $this->callbook->getCallbookData($target);
		if ($this->valid_locator($remote['gridsquare'] ?? '')) {
			return $this->location($target, $remote['gridsquare'], $remote['source'] ?? __("Callbook"));
		}
		$errors = [];
		foreach ($remote as $key => $value) {
			if (preg_match('/^error(?:_|$)/', $key) && substr($key, -5) !== '_name' && is_string($value) && $value !== '') {
				$errors[] = $value;
			}
		}
		return $this->json_error(
			__("No locator was found. Enter the DX locator manually.") . ($errors ? ' ' . implode(' ', array_unique($errors)) : ''),
			404
		);
	}

	private function valid_locator($grid) {
		return is_string($grid) && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2}(?:[0-9]{2}(?:[A-X]{2})?)?)?$/iD', $grid)
			&& $this->qra->validate_grid($grid, 'grid');
	}

	private function location($target, $grid, $source) {
		$coordinates = $this->qra->qra2latlong($grid);
		if ($coordinates === false) {
			return $this->json_error(__("Unable to convert the locator to coordinates."), 422);
		}
		return $this->output->set_content_type('application/json')->set_output(json_encode([
			'target' => $target, 'grid' => strtoupper($grid), 'source' => $source,
			'lat' => (float)$coordinates[0], 'lng' => (float)$coordinates[1],
		]));
	}

	private function json_error($message, $status) {
		return $this->output->set_status_header($status)->set_content_type('application/json')
			->set_output(json_encode(['error' => $message]));
	}
}
