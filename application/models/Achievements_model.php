<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Achievements_model extends CI_Model
{
	/*
	 * Streak trophy levels: day count => tier. Single source for the
	 * trophy catalog and the streak walk.
	 */
	private static $streak_levels = array(
		7 => 'bronze', 30 => 'bronze',
		100 => 'silver', 365 => 'silver',
		500 => 'gold', 1000 => 'gold',
		2500 => 'platinum', 5000 => 'platinum',
	);

	/*
	 * Volume trophy levels: QSO count => array(tier, title). Single
	 * source for both the trophy catalog and the Nth-QSO query.
	 */
	private static $volume_levels = array(
		100 => 'bronze',
		1000 => 'silver',
		10000 => 'gold',
		25000 => 'platinum',
		50000 => 'platinum',
	);

	public function get_trophies($confirmed_only = false) {
		$this->load->model('logbooks_model');

		$this->load->is_loaded('cache') ?: $this->load->driver('cache', [
			'adapter' => $this->config->item('cache_adapter') ?? 'file',
			'backup' => $this->config->item('cache_backup') ?? 'file',
			'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
		]);

		$station_ids = $this->scoped_station_ids();

		$cache_key = 'achievements_u' . (int) $this->session->userdata('user_id');

		if ($station_ids === null) {
			$this->cache->delete($cache_key);
			return null;
		}

		$variant = $confirmed_only ? 'confirmed' : 'all';

		$fingerprint = $this->fingerprint($station_ids);
		$cached = $this->cache->get($cache_key);
		if ($cached !== false && ($cached['fingerprint'] ?? '') === $fingerprint) {
			return array('families' => $cached['variants'][$variant], 'cached_at' => $cached['generated']);
		}

		$stats = $this->collect_stats($station_ids);
		$variants = array(
			'all' => $this->build_families($stats, 'all'),
			'confirmed' => $this->build_families($stats, 'confirmed'),
		);

		$this->cache->save($cache_key, array('fingerprint' => $fingerprint, 'generated' => time(), 'variants' => $variants), 60 * 60 * 24);

		return array('families' => $variants[$variant], 'cached_at' => null);
	}

	private function collect_stats($station_ids) {
		$table = $this->config->item('table_name');
		$conf = "($table.col_lotw_qsl_rcvd = 'Y' OR $table.col_qsl_rcvd = 'Y')";
		$mode_expr = "UPPER(COALESCE(NULLIF($table.col_submode, ''), $table.col_mode))";

		// Totals
		$params = array();
		$row = $this->db->query('SELECT COUNT(*) AS c, COALESCE(SUM(' . $conf . '), 0) AS cc FROM ' . $table
			. ' WHERE ' . $this->station_in($station_ids, $params), $params)->row();
		$totals = array('all' => (int) $row->c, 'confirmed' => (int) $row->cc);

		// Streak: one row per day with a flag whether any QSO that day is confirmed
		$params = array();
		$rows = $this->db->query('SELECT CAST(col_time_on AS DATE) AS d, MAX(' . $conf . ') AS c FROM ' . $table
			. ' WHERE col_time_on IS NOT NULL AND ' . $this->station_in($station_ids, $params)
			. ' GROUP BY d ORDER BY d ASC', $params)->result();
		$days_all = array();
		$days_conf = array();
		foreach ($rows as $row) {
			$days_all[] = $row->d;
			if ($row->c == 1) {
				$days_conf[] = $row->d;
			}
		}
		$streak = array(
			'all' => $this->walk_streak($days_all, array_keys(self::$streak_levels)),
			'confirmed' => $this->walk_streak($days_conf, array_keys(self::$streak_levels)),
		);

		// Nth QSO dates for the volume levels, both variants in one window query
		$nth = $this->nth_qso_dates($station_ids, array_keys(self::$volume_levels), $conf);

		// Modes: canonical mode (submode wins), USB/LSB folded into SSB, FT4 into FT8
		$params = array();
		$rows = $this->db->query('SELECT ' . $mode_expr . ' AS m, COUNT(*) AS c, COALESCE(SUM(' . $conf . '), 0) AS cc,'
			. ' MIN(col_time_on) AS f, MIN(CASE WHEN ' . $conf . ' THEN col_time_on END) AS fc FROM ' . $table
			. ' WHERE ' . $this->station_in($station_ids, $params) . ' GROUP BY m', $params)->result();
		$modes = array();
		foreach ($rows as $row) {
			$group = $row->m;
			if ($group == 'USB' || $group == 'LSB') {
				$group = 'SSB';
			}
			if ($group == 'FT4') {
				$group = 'FT8';
			}
			foreach (array('all' => array('c' => $row->c, 'f' => $row->f), 'confirmed' => array('c' => $row->cc, 'f' => $row->fc)) as $v => $vals) {
				$existing = $modes[$group][$v] ?? array('count' => 0, 'first' => null);
				$first = $vals['f'] !== null ? substr($vals['f'], 0, 10) : null;
				$modes[$group][$v] = array(
					'count' => $existing['count'] + (int) $vals['c'],
					'first' => $first !== null && ($existing['first'] === null || $first < $existing['first']) ? $first : $existing['first'],
				);
			}
		}

		// Bands
		$params = array();
		$rows = $this->db->query('SELECT UPPER(col_band) AS b, MIN(col_time_on) AS f, MIN(CASE WHEN ' . $conf . ' THEN col_time_on END) AS fc FROM ' . $table
			. " WHERE col_band IS NOT NULL AND col_band <> '' AND " . $this->station_in($station_ids, $params)
			. ' GROUP BY b', $params)->result();
		$bands = array();
		foreach ($rows as $row) {
			$bands[$row->b] = array(
				'all' => $row->f !== null ? substr($row->f, 0, 10) : null,
				'confirmed' => $row->fc !== null ? substr($row->fc, 0, 10) : null,
			);
		}

		// SAT aggregates, FM/Linear/LEO with confirm twins
		$sat = $this->sat_stats($station_ids, $conf);

		// LoTW (definitionally confirmed, same for both variants)
		$params = array();
		$row = $this->db->query('SELECT MIN(col_lotw_qslrdate) AS d FROM ' . $table
			. " WHERE col_lotw_qsl_rcvd = 'Y' AND " . $this->station_in($station_ids, $params), $params)->row();
		$lotw_date = ($row && $row->d !== null) ? substr($row->d, 0, 10) : null;

		return compact('totals', 'streak', 'nth', 'modes', 'bands', 'sat', 'lotw_date');
	}

	/*
	 * Builds one trophy family tree for the requested variant from the
	 * combined stats. Trophy structure and KPIs are identical between
	 * variants, only the underlying numbers differ.
	 */
	private function build_families($stats, $v) {
		$totals = $stats['totals'][$v];
		$streak = $stats['streak'][$v];
		$nth = $stats['nth'][$v];
		$lotw_date = $stats['lotw_date'];
		$sat = $stats['sat'][$v];

		$modes = array();
		foreach ($stats['modes'] as $group => $pair) {
			$modes[$group] = $pair[$v];
		}
		$bands = array();
		foreach ($stats['bands'] as $band => $pair) {
			$bands[$band] = array('first' => $pair[$v]);
		}

		$classic = ($modes['CW']['count'] ?? 0) + ($modes['SSB']['count'] ?? 0);
		$ft = ($modes['FT8']['count'] ?? 0) + ($modes['FT4']['count'] ?? 0);
		$ratio = $ft > 0 ? $classic / $ft : ($classic > 0 ? INF : 0);

		$streak_trophies = array();
		foreach (self::$streak_levels as $level => $tier) {
			$detail = array(
				array('label' => __('Longest streak'), 'value' => sprintf(_ngettext('%s day', '%s days', $streak['longest']), number_format($streak['longest']))),
				array('label' => __('Current streak'), 'value' => sprintf(_ngettext('%s day', '%s days', $streak['current']), number_format($streak['current']))),
				array('label' => __('Target'), 'value' => sprintf(_ngettext('%s day', '%s days', $level), number_format($level))),
			);
			if (isset($streak['crossings'][$level])) {
				$detail[] = array('label' => __('Reached on'), 'value' => $this->format_day($streak['crossings'][$level]));
			}
			$streak_trophies[] = $this->trophy(
				'streak_' . $tier . '.svg',
				__('%d Day Streak'), array($level),
				$streak['longest'] >= $level,
				$streak['crossings'][$level] ?? null,
				$streak['longest'], $level,
				sprintf(_ngettext('%s / %s day', '%s / %s days', $level), number_format($streak['longest']), number_format($level)),
				$detail
			);
		}

		$volume_trophies = array();
		foreach (self::$volume_levels as $level => $tier) {
			$unlocked = $totals >= $level;
			$nth_date = $unlocked ? ($nth[$level] ?? null) : null;
			$detail = array(
				array('label' => __('QSOs in log'), 'value' => number_format($totals)),
				array('label' => __('Target'), 'value' => sprintf(_ngettext('%s QSO', '%s QSOs', $level), number_format($level))),
			);
			if ($nth_date !== null) {
				$detail[] = array('label' => __('Unlocked on'), 'value' => $this->format_day($nth_date));
			}
			$volume_trophies[] = $this->trophy(
				'volume_' . $tier . '.svg',
				_ngettext('%s QSO', '%s QSOs', $level), array(number_format($level)),
				$unlocked,
				$nth_date,
				$totals, $level,
				sprintf(_ngettext('%s / %s QSO', '%s / %s QSOs', $level), number_format($totals), number_format($level)),
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
		$classic_digi_modes = array('RTTY', 'PSK', 'PSK31', 'PSK63', 'PSK125', 'PSKR', 'FSK', 'OLIVIA', 'OPERA', 'PAX', 'PAX2', 'PKT', 'Q15', 'ROS', 'T10', 'THOR', 'THRB', 'TOR', 'VARA');
		$digi_count = 0;
		$digi_first = null;
		$digi_top = null;
		$digi_top_count = 0;
		foreach (array_intersect($classic_digi_modes, array_keys($modes)) as $digi_mode) {
			$digi_count += $modes[$digi_mode]['count'];
			if ($digi_first === null || $modes[$digi_mode]['first'] < $digi_first) {
				$digi_first = $modes[$digi_mode]['first'];
			}
			if ($modes[$digi_mode]['count'] > $digi_top_count) {
				$digi_top = $digi_mode;
				$digi_top_count = $modes[$digi_mode]['count'];
			}
		}
		$digi_detail = array(array('label' => __('QSOs in classic digimodes'), 'value' => number_format($digi_count)));
		if ($digi_first !== null) {
			$digi_detail[] = array('label' => __('First QSO on'), 'value' => $this->format_day($digi_first));
		}
		if ($digi_top !== null) {
			$digi_detail[] = array('label' => __('Top classic mode'), 'value' => $digi_top . ' (' . number_format($digi_top_count) . ')');
		}
		$mode_trophies = array(
			$this->trophy('mode_cw.svg', __('First CW QSO'), array(), isset($modes['CW']), $modes['CW']['first'] ?? null, 0, 0, '',
				$this->mode_detail('CW', $modes)),
			$this->trophy('mode_ssb.svg', __('First SSB QSO'), array(), isset($modes['SSB']), $modes['SSB']['first'] ?? null, 0, 0, '',
				$this->mode_detail('SSB', $modes)),
			$this->trophy('mode_ft8.svg', __('First FT8 QSO'), array(), isset($modes['FT8']), $modes['FT8']['first'] ?? null, 0, 0, '',
				$this->mode_detail('FT8', $modes)),
			$this->trophy('mode_digi.svg', __('First Classic Digimode QSO'), array(), $digi_count > 0, $digi_first, 0, 0, '', $digi_detail),
			$this->trophy('mode_triple.svg', __('Triple Threat'), array(), $modes_present >= 3, $modes_present >= 3 ? max($mode_first_dates) : null,
				$modes_present, 3, sprintf(_ngettext('%s / 3 mode', '%s / 3 modes', $modes_present), $modes_present),
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

		$band_trophies[] = $this->sat_trophy('bands_sat_linear.svg', 'First Linear SAT QSO', $sat['lin_count'], $sat['lin_first']);
		$band_trophies[] = $this->sat_trophy('bands_sat_fm.svg', 'First FM SAT QSO', $sat['fm_count'], $sat['fm_first']);
		$band_trophies[] = $this->sat_trophy('bands_sat_leo.svg', 'First LEO SAT QSO', $sat['leo_count'], $sat['leo_first'], $sat['leo_sats']);

		$lotw_trophies = array(
			$this->trophy('lotw_first.svg', __('First LoTW Confirmation'), array(), $lotw_date !== null, $lotw_date, 0, 0, '',
				$lotw_date !== null ? array(array('label' => __('Confirmed on'), 'value' => $this->format_day($lotw_date))) : array()),
		);

		$ratio_levels = array(
			array('ratio_mouse.svg', 'Mouse-Operator', 0.1),
			array('ratio_bronze.svg', 'Balanced Operator', 1),
			array('ratio_silver.svg', 'Classic Enthusiast', 2),
			array('ratio_gold.svg', 'Classic Champion', 5),
			array('ratio_platinum.svg', 'Classic Legend', 100),
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
				$meta[0], __($meta[1]), array(),
				$ratio >= $meta[2], null,
				is_finite($ratio) ? $ratio : $meta[2], $meta[2],
				$this->ratio_label($classic, $ft, $ratio),
				array(
					array('label' => __('CW and SSB QSOs'), 'value' => number_format($classic)),
					array('label' => __('FT8 and FT4 QSOs'), 'value' => number_format($ft)),
					array('label' => __('Current ratio'), 'value' => is_infinite($ratio) ? '∞ : 1' : round($ratio, 2) . ' : 1'),
					array('label' => __('Needed ratio'), 'value' => $meta[2] . ' : 1'),
					array('label' => __('Next threshold'), 'value' => $next_ratio !== null ? $next_ratio . ' : 1' : '—'),
				)
			);
		}

		$volume_subtitle = sprintf(__('Total: %s QSOs'), number_format($totals));

		$modes_subtitle_parts = array();
		if ($mode_first_dates) {
			$modes_subtitle_parts[] = __('Worked') . ': ' . implode(', ', array_keys($mode_first_dates));
		}
		if ($digi_count > 0) {
			$modes_subtitle_parts[] = __('Classic digimodes') . ': ' . number_format($digi_count);
		}
		$modes_subtitle = $modes_subtitle_parts ? implode(' · ', $modes_subtitle_parts) : null;

		$bands_subtitle = sprintf(__('HF: %s/%s'), $band_trophies[0]['progress_now'], $band_trophies[0]['progress_target'])
			. ' · ' . sprintf(__('WARC: %s/%s'), $band_trophies[1]['progress_now'], $band_trophies[1]['progress_target'])
			. ' · ' . sprintf(__('SAT: %s'), number_format($sat['total']));

		$lotw_subtitle = $lotw_date !== null ? sprintf(__('First confirmation: %s'), $this->format_day($lotw_date)) : __('No LoTW confirmation yet');

		$ratio_subtitle = __('CW and SSB versus FT8 and FT4') . ' — ' . (is_infinite($ratio) ? '∞' : round($ratio, 2)) . ' : 1';

		return array(
			array('title' => __('Streak'), 'subtitle' => $this->streak_subtitle($streak), 'trophies' => $streak_trophies),
			array('title' => __('Volume'), 'subtitle' => $volume_subtitle, 'trophies' => $volume_trophies),
			array('title' => __('Modes'), 'subtitle' => $modes_subtitle, 'trophies' => $mode_trophies),
			array('title' => __('Bands'), 'subtitle' => $bands_subtitle, 'trophies' => $band_trophies),
			array('title' => __('LoTW'), 'subtitle' => $lotw_subtitle, 'trophies' => $lotw_trophies),
			array('title' => __('Classic Mode Ratio'), 'subtitle' => $ratio_subtitle, 'trophies' => $ratio_trophies),
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
			'title' => vsprintf($title_key, $title_args),
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
		return $this->trophy($icon, __($title_key), array(), $unlocked, $first, $present, count($required),
			sprintf(_ngettext('%s / %s band', '%s / %s bands', count($required)), $present, count($required)), $detail);
	}

	/*
	 * Binary satellite trophy: unlock state and date from the first QSO
	 * of that kind, KPIs with QSO count and (for LEO) satellite count.
	 */
	private function sat_trophy($icon, $title_key, $count, $first, $sats = null) {
		$detail = array(array('label' => __('SAT QSOs'), 'value' => number_format($count)));
		if ($first !== null) {
			$detail[] = array('label' => __('First QSO on'), 'value' => $this->format_day($first));
		}
		if ($sats > 0) {
			$detail[] = array('label' => __('Different satellites'), 'value' => number_format($sats));
		}
		return $this->trophy($icon, __($title_key), array(), $count > 0, $first, 0, 0, '', $detail);
	}

	private function streak_subtitle($streak) {
		$parts = array(sprintf(_ngettext('Longest streak: %s day', 'Longest streak: %s days', $streak['longest']), number_format($streak['longest'])));
		if ($streak['current'] > 0) {
			$parts[] = sprintf(_ngettext('Current streak: %s day', 'Current streak: %s days', $streak['current']), number_format($streak['current']));
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
	 * Longest streak, current streak and the date each threshold was
	 * reached, computed in PHP from a list of consecutive-day candidates.
	 */
	private function walk_streak($days, $levels) {
		$longest = 0;
		$current = 0;
		$crossings = array();
		$run = 0;
		$prev = null;
		foreach ($days as $day) {
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
		if ($prev !== null && strtotime($prev) >= strtotime(gmdate('Y-m-d') . ' -1 day')) {
			$current = $run;
		}

		return array('longest' => $longest, 'current' => $current, 'crossings' => $crossings);
	}

	/*
	 * Date of the Nth QSO in chronological order for every requested
	 * level, for both variants, in one window-function query: rn is the
	 * overall rank, cr the running count of confirmed QSOs (the rank
	 * among confirmed QSOs on confirmed rows).
	 */
	private function nth_qso_dates($station_ids, $levels, $conf) {
		$table = $this->config->item('table_name');
		$params = array();

		$columns = array();
		foreach ($levels as $level) {
			$columns[] = "MAX(CASE WHEN rn = $level THEN d END) AS a$level";
			$columns[] = "MAX(CASE WHEN cf = 1 AND cr = $level THEN d END) AS c$level";
		}

		$sql = 'SELECT ' . implode(', ', $columns) . ' FROM ('
			. " SELECT CAST(col_time_on AS DATE) AS d, CASE WHEN $conf THEN 1 ELSE 0 END AS cf,"
			. ' ROW_NUMBER() OVER (ORDER BY col_time_on, COL_PRIMARY_KEY) AS rn,'
			. " COUNT(CASE WHEN $conf THEN 1 END) OVER (ORDER BY col_time_on, COL_PRIMARY_KEY ROWS UNBOUNDED PRECEDING) AS cr"
			. ' FROM ' . $table
			. ' WHERE col_time_on IS NOT NULL AND ' . $this->station_in($station_ids, $params)
			. ') t';

		$row = $this->db->query($sql, $params)->row();

		$result = array('all' => array(), 'confirmed' => array());
		foreach ($levels as $level) {
			$all_col = 'a' . $level;
			$conf_col = 'c' . $level;
			if ($row->$all_col !== null) {
				$result['all'][(int) $level] = $row->$all_col;
			}
			if ($row->$conf_col !== null) {
				$result['confirmed'][(int) $level] = $row->$conf_col;
			}
		}
		return $result;
	}

	/*
	 * SAT QSO aggregates for the satellite trophies, following the AMSAT
	 * mode semantics also used by Amsat_rover: FM is FM, SSB/CW (via a
	 * linear transponder) is Linear; digital modes (e.g. PKT) count for
	 * neither. LEO is decided by the satellite table's orbit column, so
	 * QSOs with unknown satellite names are not LEO. Both confirm
	 * variants in one query.
	 */
	private function sat_stats($station_ids, $conf) {
		$table = $this->config->item('table_name');
		$mode_expr = "UPPER(COALESCE(NULLIF($table.col_submode, ''), $table.col_mode))";
		$fm = "$mode_expr = 'FM'";
		$lin = "$mode_expr IN ('SSB','USB','LSB','CW')";
		$leo = "satellite.orbit = 'LEO'";
		$params = array();

		$sql = "SELECT
				COUNT(*) AS total, COALESCE(SUM($conf), 0) AS total_c,
				SUM(CASE WHEN $fm THEN 1 ELSE 0 END) AS fm_count, MIN(CASE WHEN $fm THEN $table.col_time_on END) AS fm_first,
				SUM(CASE WHEN $fm AND $conf THEN 1 ELSE 0 END) AS fm_count_c, MIN(CASE WHEN $fm AND $conf THEN $table.col_time_on END) AS fm_first_c,
				SUM(CASE WHEN $lin THEN 1 ELSE 0 END) AS lin_count, MIN(CASE WHEN $lin THEN $table.col_time_on END) AS lin_first,
				SUM(CASE WHEN $lin AND $conf THEN 1 ELSE 0 END) AS lin_count_c, MIN(CASE WHEN $lin AND $conf THEN $table.col_time_on END) AS lin_first_c,
				SUM(CASE WHEN $leo THEN 1 ELSE 0 END) AS leo_count, MIN(CASE WHEN $leo THEN $table.col_time_on END) AS leo_first,
				SUM(CASE WHEN $leo AND $conf THEN 1 ELSE 0 END) AS leo_count_c, MIN(CASE WHEN $leo AND $conf THEN $table.col_time_on END) AS leo_first_c,
				COUNT(DISTINCT CASE WHEN $leo THEN $table.col_sat_name END) AS leo_sats,
				COUNT(DISTINCT CASE WHEN $leo AND $conf THEN $table.col_sat_name END) AS leo_sats_c
			FROM $table
			LEFT OUTER JOIN satellite
				ON $table.col_prop_mode = 'SAT'
				AND ($table.col_sat_name = satellite.name
					OR (satellite.displayname != '' AND $table.col_sat_name = satellite.displayname))
			WHERE $table.col_prop_mode = 'SAT' AND " . $this->station_in($station_ids, $params);
		$row = $this->db->query($sql, $params)->row();

		$make = fn($r, $suffix) => array(
			'total' => (int) ($r->{'total' . $suffix} ?? 0),
			'fm_count' => (int) ($r->{'fm_count' . $suffix} ?? 0),
			'fm_first' => $r->{'fm_first' . $suffix} !== null ? substr($r->{'fm_first' . $suffix}, 0, 10) : null,
			'lin_count' => (int) ($r->{'lin_count' . $suffix} ?? 0),
			'lin_first' => $r->{'lin_first' . $suffix} !== null ? substr($r->{'lin_first' . $suffix}, 0, 10) : null,
			'leo_count' => (int) ($r->{'leo_count' . $suffix} ?? 0),
			'leo_first' => $r->{'leo_first' . $suffix} !== null ? substr($r->{'leo_first' . $suffix}, 0, 10) : null,
			'leo_sats' => (int) ($r->{'leo_sats' . $suffix} ?? 0),
		);

		return array('all' => $make($row, ''), 'confirmed' => $make($row, '_c'));
	}

	/*
	 * Cheap change detector for the trophy cache: one indexed aggregate
	 * over the scoped station locations, hashed together with the
	 * station id list so switching logbooks also invalidates. The two
	 * confirmation counters catch LoTW/paper syncs in both directions.
	 */
	private function fingerprint($station_ids) {
		$params = array();
		$sql = 'SELECT COUNT(*) AS c, IFNULL(MAX(COL_PRIMARY_KEY), 0) AS m, IFNULL(SUM(COL_PRIMARY_KEY), 0) AS s,'
			. " COALESCE(SUM(col_lotw_qsl_rcvd = 'Y'), 0) AS lotw, COALESCE(SUM(col_qsl_rcvd = 'Y'), 0) AS qsl FROM " . $this->config->item('table_name')
			. ' WHERE ' . $this->station_in($station_ids, $params);
		$row = $this->db->query($sql, $params)->row();
		return md5(implode(',', $station_ids) . '|' . $row->c . '|' . $row->m . '|' . $row->s . '|' . $row->lotw . '|' . $row->qsl);
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
