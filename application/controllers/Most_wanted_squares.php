<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Most_wanted_squares extends CI_Controller {

	// Radius around the active station location in kilometers
	const RADIUS_KM = 2500;

	/**
	 * Shows the export form with the VHF bands and the active station location
	 *
	 * @return void
	 */
	public function index() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->is_loaded('bands') ?: $this->load->model('bands');

		$data['bands'] = $this->bands->get_vhf_bands();
		$data['station_profile'] = $this->stations->profile($this->stations->find_active())->row();
		$data['radius_km'] = self::RADIUS_KM;

		$data['page_title'] = __("Most Wanted Squares");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('most_wanted_squares/index');
		$this->load->view('interface_assets/footer');
	}

	/**
	 * Sends a text file with all 4-digit gridsquares within RADIUS_KM of the active station location
	 * that have not been worked yet on the posted band
	 *
	 * POST: band (required), exclude_wet (optional, skips squares without land)
	 *
	 * @return void
	 */
	public function export() {
		if (!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->is_loaded('bands') ?: $this->load->model('bands');

		$band = $this->input->post('band', true);
		if (!in_array($band, $this->bands->get_vhf_bands(), true)) { $this->session->set_flashdata('error', __("Invalid band.")); redirect('most_wanted_squares'); }

		$station_id = $this->stations->find_active();
		$homegrid = $this->stations->gridsquare_from_station($station_id);

		if ($homegrid == null) { $this->session->set_flashdata('error', __("The active station location has no gridsquare set.")); redirect('most_wanted_squares'); }

		$this->load->library('qra');

		$home = $this->qra->qra2latlong($homegrid);
		if ($home === false) { $this->session->set_flashdata('error', __("The gridsquare of the active station location is invalid.")); redirect('most_wanted_squares'); }

		// Worked gridsquares of the active station location on the selected band (any QSO, incl. VUCC multi-grids, satellite excluded)
		$this->load->is_loaded('dxatlas_model') ?: $this->load->model('dxatlas_model');

		$worked = array();
		foreach ($this->dxatlas_model->get_grids($station_id, $band, 'All', 'All', 'All', 'All', '', '', 'none', 'single') as $row) {
			$worked[] = $row['gridsquare'];
		}
		foreach ($this->dxatlas_model->get_grids($station_id, $band, 'All', 'All', 'All', 'All', '', '', 'none', 'multi') as $row) {
			foreach (explode(',', $row['col_vucc_grids']) as $grid) {
				$worked[] = strtoupper(substr(trim($grid), 0, 4));
			}
		}
		$worked = array_unique($worked);

		// Collect all 4-digit gridsquares whose center is within the radius
		$in_range = array();
		for ($f1 = ord('A'); $f1 <= ord('R'); $f1++) {
			for ($f2 = ord('A'); $f2 <= ord('R'); $f2++) {
				for ($n1 = 0; $n1 <= 9; $n1++) {
					for ($n2 = 0; $n2 <= 9; $n2++) {
						// Center of the 4-digit square in degrees (Maidenhead: field 20deg lon / 10deg lat, square 2deg lon / 1deg lat)
						$lat = ($f2 - ord('A')) * 10 + $n2 + 0.5 - 90;
						$lng = ($f1 - ord('A')) * 20 + $n1 * 2 + 1 - 180;
						if (calc_distance($home[0], $home[1], $lat, $lng, 'K') <= self::RADIUS_KM) {
							$in_range[] = chr($f1) . chr($f2) . $n1 . $n2;
						}
					}
				}
			}
		}

		// Optionally keep only squares that are listed for a DXCC entity in the vuccgrids table (open water is not listed there)
		if ($this->input->post('exclude_wet', true) !== null) {
			$this->load->is_loaded('gridmap_model') ?: $this->load->model('gridmap_model');
			$vucc_grids = $this->gridmap_model->get_vucc_grid_set();
			if (empty($vucc_grids)) { $this->session->set_flashdata('error', __("VUCC gridsquare data unavailable.")); redirect('most_wanted_squares'); }
			$in_range = array_filter($in_range, function ($grid) use ($vucc_grids) { return isset($vucc_grids[$grid]); });
		}

		$not_worked = array_values(array_diff($in_range, $worked));
		sort($not_worked);

		$filecontent = "[Squares]";
		$filecontent .= implode("\r\n", $not_worked) . "\r\n";
		$filecontent .= "[End of file]";

		header('Content-Type: text/plain');
		header('Content-Length: ' . strlen($filecontent));
		header('Content-Disposition: attachment; filename="most_wanted_squares_' . strtolower($band) . '.txt"');
		echo $filecontent;
	}
}
