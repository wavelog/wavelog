<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AMSAT Rover Award Model
 *
 * Handles data retrieval and point calculation for the AMSAT Rover Award
 *
 * @package     Wavelog
 * @subpackage  Models
 * @category    Awards
 */

class Amsat_rover extends CI_Model {

	/**
	 * Get active station profile info for the current user
	 */
	function get_station_info() {
		$this->load->model('stations');
		$home_grid = $this->stations->find_gridsquare() ?? '';
		$home_dxcc = '';
		$station_callsign = '';

		$query = $this->db->query(
			'SELECT station_dxcc, station_callsign FROM station_profile WHERE user_id = ? AND station_active = 1',
			[$this->session->userdata('user_id')]
		);
		if ($query && $query->num_rows() > 0) {
			$row = $query->row();
			$home_dxcc = $row->station_dxcc ?? '';
			$station_callsign = $row->station_callsign ?? '';
		}

		return compact('home_grid', 'home_dxcc', 'station_callsign');
	}

	/**
	 * Build confirmation filters from POST data
	 */
	function get_filters() {
		$confirmations = [];
		foreach (['lotw', 'qsl'] as $type) {
			if ($this->input->post($type)) {
				$confirmations[] = $type;
			}
		}
		if (empty($confirmations)) {
			$confirmations = ['lotw', 'qsl'];
		}
		return ['confirmations' => $confirmations];
	}

	/**
	 * Get confirmed satellite QSOs with station profile data
	 *
	 * @param array $location_ids Station location IDs from logbooks_model
	 * @param array $filters Filters including confirmation types
	 * @return array QSO data
	 */
	function get_satellite_qsos($location_ids, $filters = []) {
		if (empty($location_ids) || ($location_ids[0] ?? null) === -1) {
			return [];
		}

		$safe_ids = array_map('intval', $location_ids);
		$location_list = implode(',', $safe_ids);

		$this->load->library('Genfunctions');
		$postdata = [];
		foreach ($filters['confirmations'] ?? ['lotw', 'qsl'] as $type) {
			$postdata[$type] = '1';
		}
		$confirmation_sql = $this->genfunctions->addQslToQuery($postdata);
		$bindings = ['SAT', '2018-01-01'];

		$table_name = $this->config->item('table_name');

		$sql = "SELECT
				qso.COL_SAT_NAME,
				qso.COL_MODE,
				qso.COL_SUBMODE,
				qso.COL_LOTW_QSL_RCVD,
				qso.COL_QSL_RCVD,
				qso.COL_TIME_ON,
				qso.COL_CALL,
				qso.COL_DXCC,
				qso.COL_STATE,
				sp.station_gridsquare AS home_grid,
				sp.station_dxcc AS home_dxcc,
				sp.station_callsign AS station_callsign
			FROM {$table_name} qso
			JOIN station_profile sp ON qso.station_id = sp.station_id
			WHERE qso.station_id IN ({$location_list})
				AND qso.COL_PROP_MODE = ?
				AND qso.COL_TIME_ON >= ?
				{$confirmation_sql}
			ORDER BY qso.COL_TIME_ON DESC";

		try {
			$query = $this->db->query($sql, $bindings);
			if ($query && $query->num_rows() > 0) {
				return $query->result();
			}
		} catch (Exception $e) {
			log_message('error', 'AMSAT Rover Model Error: ' . $e->getMessage());
		}

		return [];
	}

	/**
	 * Fetch QSOs, calculate activations and summary
	 */
	function get_activations($filters, $station_info) {
		$this->load->model('logbooks_model');
		$locations = $this->logbooks_model->list_logbook_relationships(
			$this->session->userdata('active_station_logbook')
		);

		$qsos = $this->get_satellite_qsos($locations, $filters);
		$activations = $this->calculate_points(
			$qsos, $station_info['home_grid'], $station_info['home_dxcc']
		);

		return [
			'activations' => $activations,
			'summary'     => $this->get_summary($activations),
		];
	}

	/**
	 * Calculate bonus points from POST data
	 */
	function get_bonus() {
		$bonus = 0;
		$details = [];
		$items = [
			'bonus_social'  => ['Social Media Promotion', 5],
			'bonus_photos'  => ['Photo Posting', 5],
			'bonus_mm'      => ['Maritime Mobile', 10],
			'bonus_journal' => ['AMSAT Journal Article', 15],
		];
		foreach ($items as $field => [$label, $pts]) {
			if ($this->input->post($field)) {
				$bonus += $pts;
				$details[] = "  + {$label} ({$pts})";
			}
		}
		return compact('bonus', 'details');
	}

	/**
	 * Calculate points from QSO data
	 *
	 * Only ONE QSO per station_profile grid per mode category counts.
	 */
	function calculate_points($qsos, $home_grid, $home_dxcc) {
		$results = [];
		$seen = [];

		if (empty($qsos)) {
			return $results;
		}

		foreach ($qsos as $qso) {
			$grid = !empty($qso->home_grid) ? strtoupper(trim($qso->home_grid)) : '';
			$grid_4 = substr($grid, 0, 4);
			if (empty($grid_4)) {
				continue;
			}

			$cat = $this->get_mode_category($qso->COL_MODE, $qso->COL_SUBMODE);
			$key = $grid_4 . '_' . $cat;
			if (isset($seen[$key])) {
				continue;
			}
			$seen[$key] = true;

			$points = $this->get_mode_points($qso->COL_MODE, $qso->COL_SUBMODE);
			if (!empty($home_dxcc) && !empty($qso->COL_DXCC) && $qso->COL_DXCC != $home_dxcc) {
				$points += 1;
			}

			$confirmation = 'Unknown';
			if ($qso->COL_LOTW_QSL_RCVD == 'Y') {
				$confirmation = 'LoTW';
			} elseif ($qso->COL_QSL_RCVD == 'Y') {
				$confirmation = 'QSL';
			}

			$results[] = [
				'my_grid'          => $grid_4,
				'full_my_grid'     => $qso->home_grid,
				'call_worked'      => $qso->COL_CALL,
				'mode'             => strtoupper($qso->COL_MODE ?? ''),
				'submode'          => strtoupper($qso->COL_SUBMODE ?? ''),
				'mode_category'    => $cat,
				'points'           => $points,
				'confirmation'     => $confirmation,
				'date'             => $qso->COL_TIME_ON,
				'satellite'        => $qso->COL_SAT_NAME,
				'dxcc'             => $qso->COL_DXCC,
				'state'            => $qso->COL_STATE,
				'station_callsign' => $qso->station_callsign ?? '',
			];
		}

		usort($results, function($a, $b) {
			return strcmp($a['my_grid'], $b['my_grid'])
				?: strcmp($a['mode_category'], $b['mode_category'])
				?: strcmp($a['satellite'] ?? '', $b['satellite'] ?? '');
		});

		return $results;
	}

	function get_mode_category($mode, $submode) {
		$mode = strtoupper($mode ?? '');
		$submode = strtoupper($submode ?? '');

		if ($mode == 'FM') {
			return 'FM';
		}

		$digital_modes = ['PSK', 'PKT', 'FSK', 'MFSK', 'GT', 'TOR', 'JT'];
		$digital_submodes = ['PSK31', 'PSK63', 'FT4', 'FT8', 'JT65', 'JT9', 'QRA64', 'MSK144'];
		if (in_array($mode, $digital_modes) || in_array($submode, $digital_submodes)) {
			return 'Digital';
		}

		return 'Linear';
	}

	function get_mode_points($mode, $submode) {
		return match ($this->get_mode_category($mode, $submode)) {
			'FM'      => 1,
			'Digital' => 3,
			default   => 2,
		};
	}

	function get_summary($activations) {
		$total = 0;
		$counts = ['FM' => 0, 'Digital' => 0, 'Linear' => 0];

		foreach ($activations as $act) {
			$total += $act['points'];
			$counts[$act['mode_category']] = ($counts[$act['mode_category']] ?? 0) + 1;
		}

		return [
			'total_points'  => $total,
			'grid_count'    => count($activations),
			'fm_count'      => $counts['FM'],
			'linear_count'  => $counts['Linear'],
			'digital_count' => $counts['Digital'],
			'target'        => 25,
			'complete'      => $total >= 25,
		];
	}
}
