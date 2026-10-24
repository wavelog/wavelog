<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 * Credits:
 *
 * The model is inspired by MINIMUF 3.5 (QST 1982) with D. L. Mills' path
 * losses, similar model was also used by DXSpider's sh/muf command,
 * with some corrections:
 * - Beyond 4000 km, the MUF is determined by two control points 2000 km
 *   from the endpoints, as in ITU-R P.533. This prevents a dark region in
 *   the middle of a long path from closing the entire path and gives the
 *   long path its own MUF.
 * - The F2 layer follows the Sun with a delay of approximately one hour.
 * - Above the MUF, the signal falls gradually (130 * (f/MUF - 1)^2 dB,
 *   ITU-R P.533), rather than disappearing.
 * - D-layer absorption and chordal hops on paths longer than 10000 km
 *   are treated as in VOACAP.
 *
 * For signal levels, the median deviation from VOACAP remains within
 * 3 dB, with a spread of 4-6 dB. This is a monthly median prediction:
 * on good days, signals can be 8-10 dB better. Therefore, a link up to
 * 10 dB below the mode's threshold is considered marginal, not impossible.
 * Sporadic E, TEP openings and current conditions are better indicated by
 * live reception reports, which are therefore always shown alongside
 * the prediction in the reference application.
 */

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
		$this->load->model('stations');
		$station_id = $this->session->userdata('station_profile_id') ?? 0;
		if (!$this->stations->check_station_is_accessible($station_id)) {
			$station_id = $this->stations->find_active();
		}
		$station = $station_id ? $this->stations->profile($station_id)->row() : null;

		$this->load->model('Hamqsl_model');
		$solar = $this->Hamqsl_model->get_solarinformation_array();
		$solar_node = $this->Hamqsl_model->solarData;
		if ($solar_node && isset($solar_node->solardata)) {
			$solar_node = $solar_node->solardata;
		}
		$observed = $solar_node && isset($solar_node->updated) ? strtotime((string)$solar_node->updated) : false;
		$data['page_title'] = __("Propagation");
		$data['homegrid'] = $station ? strtoupper(explode(',', $station->station_gridsquare ?? '')[0]) : '';
		$power = $station->station_power ?? null;
		$data['station_power'] = is_numeric($power) && $power >= 1 && $power <= 5000 ? (float)$power : 100;
		$data['solar'] = $solar;
		$data['solar_observed'] = $observed === false ? null : $observed;
		$data['settings_key'] = 'wavelog.propagation.' . (int)$this->session->userdata('user_id') . '.' . (int)$station_id;
		$data['solar_modified'] = is_file(FCPATH . 'updates/solarxml.xml') ? filemtime(FCPATH . 'updates/solarxml.xml') : null;
		$this->load->view('interface_assets/header', $data);
		$this->load->view('propagation/index', $data);
		$this->load->view('interface_assets/footer', ['scripts' => [
			'assets/js/chart.js',
			'assets/js/sections/propagation_model.js',
			'assets/js/sections/propagation.js',
		]]);
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
