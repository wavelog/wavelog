<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Achievements_model extends CI_Model
{
	/*
	 * Builds the full trophy catalog for the session user's active station
	 * logbook: unlock state, progress and unlock date (derived from the log
	 * itself, nothing is persisted). Returns null when no valid logbook/
	 * station scope exists, so the controller can bail out early.
	 */
	public function get_trophies() {
		$this->load->model('logbooks_model');
		$station_ids = $this->scoped_station_ids();

		if ($station_ids === null) {
			return null;
		}

		$totals = $this->qso_totals($station_ids);
		$streak = $this->streak_stats($station_ids, array(7, 30, 100, 365, 500, 1000, 2500, 5000));
		$modes = $this->mode_stats($station_ids);
		$bands = $this->band_stats($station_ids);
		$lotw_date = $this->lotw_first_confirmed($station_ids);

		$classic = ($modes['CW']['count'] ?? 0) + ($modes['SSB']['count'] ?? 0);
		$ft = ($modes['FT8']['count'] ?? 0) + ($modes['FT4']['count'] ?? 0);
		$ratio = $ft > 0 ? $classic / $ft : ($classic > 0 ? INF : 0);

		$streak_levels = array(
			7 => 'bronze', 30 => 'bronze',
			100 => 'silver', 365 => 'silver',
			500 => 'gold', 1000 => 'gold',
			2500 => 'platinum', 5000 => 'platinum',
		);
		$streak_trophies = array();
		foreach ($streak_levels as $level => $tier) {
			$detail = array(
				array('label' => __('Longest streak'), 'value' => number_format($streak['longest']) . ' ' . __('days')),
				array('label' => __('Current streak'), 'value' => number_format($streak['current']) . ' ' . __('days')),
				array('label' => __('Target'), 'value' => number_format($level) . ' ' . __('days')),
			);
			if (isset($streak['crossings'][$level])) {
				$detail[] = array('label' => __('Reached on'), 'value' => $this->format_day($streak['crossings'][$level]));
			}
			$streak_trophies[] = $this->trophy(
				'streak_' . $tier . '.svg',
				'%d Day Streak', array($level),
				$streak['longest'] >= $level,
				$streak['crossings'][$level] ?? null,
				$streak['longest'], $level,
				number_format($streak['longest']) . ' / ' . number_format($level) . ' ' . __('days'),
				$detail
			);
		}

		$volume_levels = array(
			100 => array('bronze', '100 QSOs'),
			1000 => array('silver', '1,000 QSOs'),
			10000 => array('gold', '10,000 QSOs'),
			25000 => array('platinum', '25,000 QSOs'),
		);
		$volume_trophies = array();
		foreach ($volume_levels as $level => $meta) {
			$unlocked = $totals >= $level;
			$nth = $unlocked ? $this->nth_qso_date($station_ids, $level) : null;
			$detail = array(
				array('label' => __('QSOs in log'), 'value' => number_format($totals)),
				array('label' => __('Target'), 'value' => number_format($level) . ' ' . __('QSOs')),
			);
			if ($nth !== null) {
				$detail[] = array('label' => __('Unlocked on'), 'value' => $this->format_day($nth));
			}
			$volume_trophies[] = $this->trophy(
				'volume_' . $meta[0] . '.svg',
				$meta[1], array(),
				$unlocked,
				$nth,
				$totals, $level,
				number_format($totals) . ' / ' . number_format($level) . ' ' . __('QSOs'),
				$detail
			);
		}

		$all_modes = array('CW', 'SSB', 'FT8');
		$modes_present = 0;
		$mode_first_dates = array();
		foreach ($all_modes as $group) {
			if (!empty($modes[$group]['count'])) {
				$modes_present++;
				$mode_first_dates[$group] = $modes[$group]['first'];
			}
		}
		$missing_modes = array_values(array_diff($all_modes, array_keys($mode_first_dates)));
		$mode_trophies = array(
			$this->trophy('mode_cw.svg', 'First CW QSO', array(), isset($modes['CW']), $modes['CW']['first'] ?? null, 0, 0, '',
				$this->mode_detail('CW', $modes)),
			$this->trophy('mode_ssb.svg', 'First SSB QSO', array(), isset($modes['SSB']), $modes['SSB']['first'] ?? null, 0, 0, '',
				$this->mode_detail('SSB', $modes)),
			$this->trophy('mode_ft8.svg', 'First FT8 QSO', array(), isset($modes['FT8']), $modes['FT8']['first'] ?? null, 0, 0, '',
				$this->mode_detail('FT8', $modes)),
			$this->trophy('mode_triple.svg', 'Triple Threat', array(), $modes_present >= 3, $modes_present >= 3 ? max($mode_first_dates) : null,
				$modes_present, 3, $modes_present . ' / 3 ' . __('modes'),
				array(
					array('label' => __('Worked modes'), 'value' => $mode_first_dates ? implode(', ', array_keys($mode_first_dates)) : '—'),
					array('label' => __('Missing modes'), 'value' => $missing_modes ? implode(', ', $missing_modes) : '—'),
				)),
		);

		$hf_bands = array('160M', '80M', '40M', '30M', '20M', '17M', '15M', '12M', '10M');
		$warc_bands = array('30M', '17M', '12M');
		$band_trophies = array(
			$this->band_trophy($bands, $hf_bands, 'bands_hf.svg', 'All HF Bands', '160m through 10m'),
			$this->band_trophy($bands, $warc_bands, 'bands_warc.svg', 'WARC Bands', '30m, 17m and 12m'),
		);

		$lotw_trophies = array(
			$this->trophy('lotw_first.svg', 'First LoTW Confirmation', array(), $lotw_date !== null, $lotw_date, 0, 0, '',
				$lotw_date !== null ? array(array('label' => __('Confirmed on'), 'value' => $this->format_day($lotw_date))) : array()),
		);

		$ratio_levels = array(
			array('ratio_tomato.svg', 'Rotten Tomato', 0.1),
			array('ratio_bronze.svg', 'Balanced Operator', 1),
			array('ratio_silver.svg', 'Classic Enthusiast', 2),
			array('ratio_gold.svg', 'Classic Champion', 5),
		);
		$ratio_trophies = array();
		$next_ratio = null;
		foreach (array_reverse($ratio_levels) as $meta) {
			if ($ratio < $meta[2]) {
				$next_ratio = $meta[2];
			}
		}
		foreach ($ratio_levels as $meta) {
			$ratio_trophies[] = $this->trophy(
				$meta[0], $meta[1], array(),
				$ratio >= $meta[2], null,
				is_finite($ratio) ? $ratio : $meta[2], $meta[2],
				$this->ratio_label($classic, $ft, $ratio),
				array(
					array('label' => __('CW and SSB QSOs'), 'value' => number_format($classic)),
					array('label' => __('FT8 and FT4 QSOs'), 'value' => number_format($ft)),
					array('label' => __('Current ratio'), 'value' => is_infinite($ratio) ? '∞ : 1' : round($ratio, 2) . ' : 1'),
					array('label' => __('Next threshold'), 'value' => $next_ratio !== null ? $next_ratio . ' : 1' : '—'),
				)
			);
		}

		return array(
			'families' => array(
				array('title' => __('Streak'), 'subtitle' => $this->streak_subtitle($streak), 'trophies' => $streak_trophies),
				array('title' => __('Volume'), 'subtitle' => null, 'trophies' => $volume_trophies),
				array('title' => __('Modes'), 'subtitle' => null, 'trophies' => $mode_trophies),
				array('title' => __('Bands'), 'subtitle' => null, 'trophies' => $band_trophies),
				array('title' => __('LoTW'), 'subtitle' => null, 'trophies' => $lotw_trophies),
				array('title' => __('Classic Mode Ratio'), 'subtitle' => __('CW and SSB versus FT8 and FT4'), 'trophies' => $ratio_trophies),
			),
		);
	}

	/*
	 * One trophy card as consumed by the view. Unlock dates are plain
	 * 'Y-m-d' strings (or null); progress_now/progress_target drive the
	 * progress bar, a target of 0 means "binary" trophy without a bar.
	 * detail is a list of label/value KPI pairs shown in the trophy modal.
	 */
	private function trophy($icon, $title_key, $title_args, $unlocked, $unlock_date, $progress_now, $progress_target, $progress_label, $detail = array()) {
		return array(
			'icon' => $icon,
			'title' => vsprintf(__($title_key), $title_args),
			'unlocked' => $unlocked,
			'unlock_date' => $unlock_date,
			'progress_now' => $progress_now,
			'progress_target' => $progress_target,
			'progress_label' => $progress_label,
			'detail' => $detail,
		);
	}

	/*
	 * KPI pairs for the single-mode trophies: QSO count in that mode and
	 * the date of the first QSO.
	 */
	private function mode_detail($group, $modes) {
		$detail = array(
			array('label' => __('QSOs in this mode'), 'value' => number_format($modes[$group]['count'] ?? 0)),
		);
		if (!empty($modes[$group]['first'])) {
			$detail[] = array('label' => __('First QSO on'), 'value' => $this->format_day($modes[$group]['first']));
		}
		return $detail;
	}

	private function band_trophy($bands, $required, $icon, $title_key, $desc) {
		$missing = array_diff($required, array_keys($bands));
		$present = count($required) - count($missing);
		$unlocked = $missing === array();
		$first = null;
		if ($unlocked) {
			$dates = array_map(fn($b) => $bands[$b]['first'], $required);
			$first = max($dates);
		}
		$detail = array(
			array('label' => __('Bands worked'), 'value' => $present . ' / ' . count($required)),
			array('label' => __('Missing bands'), 'value' => $missing ? implode(', ', array_map('strtolower', $missing)) : '—'),
			array('label' => __('Range'), 'value' => $desc),
		);
		if ($first !== null) {
			$detail[] = array('label' => __('Completed on'), 'value' => $this->format_day($first));
		}
		return $this->trophy($icon, $title_key, array(), $unlocked, $first, $present, count($required),
			$present . ' / ' . count($required) . ' ' . __('bands'), $detail);
	}

	private function streak_subtitle($streak) {
		$parts = array(sprintf(__("Longest streak: %s days"), number_format($streak['longest'])));
		if ($streak['current'] > 0) {
			$parts[] = sprintf(__("Current streak: %s days"), number_format($streak['current']));
		}
		return implode(' · ', $parts);
	}

	private function ratio_label($classic, $ft, $ratio) {
		if ($classic == 0 && $ft == 0) {
			return __('No QSOs yet');
		}
		if (is_infinite($ratio)) {
			return number_format($classic) . ' ' . __('classic') . ' / 0 FT';
		}
		return number_format($classic) . ' ' . __('classic') . ' / ' . number_format($ft) . ' FT = ' . round($ratio, 2) . ' : 1';
	}

	/*
	 * 'Y-m-d' formatted with the user's date format preference, for
	 * display-ready KPI values.
	 */
	private function format_day($ymd) {
		$format = $this->session->userdata('user_date_format');
		if (!$format) {
			$format = $this->config->item('qso_date_format');
		}
		return date($format, strtotime($ymd));
	}

	/*
	 * Longest streak, current streak and the date each threshold was first
	 * reached, computed from the distinct QSO dates in PHP (one query).
	 */
	private function streak_stats($station_ids, $levels) {
		$params = array();
		$sql = 'SELECT DISTINCT CAST(col_time_on AS DATE) AS d FROM ' . $this->config->item('table_name')
			. ' WHERE col_time_on IS NOT NULL AND ' . $this->station_in($station_ids, $params)
			. ' ORDER BY d ASC';
		$rows = $this->db->query($sql, $params)->result();

		$longest = 0;
		$current = 0;
		$crossings = array();
		$run = 0;
		$prev = null;
		foreach ($rows as $row) {
			$day = $row->d;
			$run = ($prev !== null && date_create($prev)->diff(date_create($day))->format('%a') == 1) ? $run + 1 : 1;
			$prev = $day;
			if ($run > $longest) {
				$longest = $run;
			}
			foreach ($levels as $level) {
				if ($run >= $level && !isset($crossings[$level])) {
					$crossings[$level] = $day;
				}
			}
		}
		if ($prev !== null && strtotime($prev) >= strtotime(date('Y-m-d') . ' -1 day')) {
			$current = $run;
		}

		return array('longest' => $longest, 'current' => $current, 'crossings' => $crossings);
	}

	private function qso_totals($station_ids) {
		$params = array();
		$sql = 'SELECT COUNT(*) AS c FROM ' . $this->config->item('table_name')
			. ' WHERE ' . $this->station_in($station_ids, $params);
		return (int) $this->db->query($sql, $params)->row()->c;
	}

	/*
	 * Date of the Nth QSO in chronological order (used as the unlock date
	 * of the volume trophies). Only called for already unlocked levels.
	 */
	private function nth_qso_date($station_ids, $n) {
		$params = array();
		$sql = 'SELECT col_time_on AS t FROM ' . $this->config->item('table_name')
			. ' WHERE col_time_on IS NOT NULL AND ' . $this->station_in($station_ids, $params)
			. ' ORDER BY col_time_on ASC LIMIT 1 OFFSET ' . (int) ($n - 1);
		$row = $this->db->query($sql, $params)->row();
		return $row ? substr($row->t, 0, 10) : null;
	}

	/*
	 * Per canonical mode (submode wins over mode, as elsewhere in the
	 * codebase): QSO count and date of the first QSO. SSB also matches
	 * USB/LSB, FT8 group matches FT8/FT4.
	 */
	private function mode_stats($station_ids) {
		$params = array();
		$mode_expr = "UPPER(COALESCE(NULLIF(col_submode, ''), col_mode))";
		$sql = 'SELECT ' . $mode_expr . ' AS m, COUNT(*) AS c, MIN(col_time_on) AS first FROM ' . $this->config->item('table_name')
			. ' WHERE ' . $this->station_in($station_ids, $params) . ' GROUP BY m';
		$rows = $this->db->query($sql, $params)->result();

		$result = array();
		foreach ($rows as $row) {
			if ($row->m == 'USB' || $row->m == 'LSB') {
				$row->m = 'SSB';
			}
			if ($row->m == 'FT4') {
				$row->m = 'FT8';
			}
			if (!isset($result[$row->m]) || $row->first < $result[$row->m]['first']) {
				$result[$row->m] = array('count' => ($result[$row->m]['count'] ?? 0) + $row->c, 'first' => substr($row->first, 0, 10));
			} else {
				$result[$row->m]['count'] += $row->c;
			}
		}
		return $result;
	}

	private function band_stats($station_ids) {
		$params = array();
		$sql = 'SELECT UPPER(col_band) AS b, MIN(col_time_on) AS first FROM ' . $this->config->item('table_name')
			. " WHERE col_band IS NOT NULL AND col_band <> '' AND " . $this->station_in($station_ids, $params)
			. ' GROUP BY b';
		$rows = $this->db->query($sql, $params)->result();

		$result = array();
		foreach ($rows as $row) {
			$result[$row->b] = array('first' => substr($row->first, 0, 10));
		}
		return $result;
	}

	private function lotw_first_confirmed($station_ids) {
		$params = array();
		$sql = 'SELECT MIN(col_lotw_qslrdate) AS d FROM ' . $this->config->item('table_name')
			. " WHERE col_lotw_qsl_rcvd = 'Y' AND " . $this->station_in($station_ids, $params);
		$row = $this->db->query($sql, $params)->row();
		return ($row && $row->d !== null) ? substr($row->d, 0, 10) : null;
	}

	/*
	 * Adds "station_id IN (...)" with bound placeholders, same as
	 * Countqsoby_model::station_in().
	 */
	private function station_in($station_ids, &$params) {
		$placeholders = '';
		foreach ($station_ids as $idx => $station_id) {
			$placeholders .= $idx > 0 ? ',?' : '?';
			$params[] = $station_id;
		}
		return $this->config->item('table_name') . '.station_id IN (' . $placeholders . ')';
	}

	/*
	 * Station location ids of the active logbook, verified for ownership so
	 * that no QSOs of other users can leak into any query of this model:
	 * 1. the active logbook must belong to the session user
	 * 2. only station locations owned by that same user are kept
	 * Returns null when nothing valid is linked.
	 */
	private function scoped_station_ids() {
		$logbook_id = $this->session->userdata('active_station_logbook');

		if (empty($logbook_id) || !$this->logbooks_model->check_logbook_is_accessible($logbook_id)) {
			return null;
		}

		$rows = $this->db->select('station_logbooks_relationship.station_location_id')
			->from('station_logbooks_relationship')
			->join('station_profile', 'station_profile.station_id = station_logbooks_relationship.station_location_id')
			->where('station_logbooks_relationship.station_logbook_id', (int) $logbook_id)
			->where('station_profile.user_id', (int) $this->session->userdata('user_id'))
			->get()->result();

		$ids = array();
		foreach ($rows as $row) {
			$ids[] = (int) $row->station_location_id;
		}

		return $ids ?: null;
	}
}
