<?php

class Jcg_model extends CI_Model {

	// Station locations of the active logbook as a SQL IN list ("'1','2'")
	protected $location_list = '';

	// Japan reference data loaded from assets/json/japan_award/
	protected $ja_prefectures = [];   // "01" => ["name" => "Hokkaido", ...]
	protected $ja_guns = [];          // "29011" => ["name" => "Yoshida", ...]

	function __construct() {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->location_list = "'" . implode("','", $logbooks_locations_array) . "'";

		$this->ja_prefectures = $this->load_json('pref_list.json');
		$this->ja_guns = $this->load_json('jcg_list.json');
	}

	private function load_json($filename) {
		return json_decode(file_get_contents(FCPATH . 'assets/json/japan_award/' . $filename), true) ?? [];
	}

	/**
	 * The guns to show: all of them, minus the deleted ones unless
	 * "include deleted" is checked.
	 */
	private function jcg_guns($postdata) {
		if (($postdata['includedeleted'] ?? null) != null) {
			return $this->ja_guns;
		}
		return array_filter($this->ja_guns, function ($gun) {
			return empty($gun['deleted']);
		});
	}

	private function prefecture_name($prefecture_code) {
		return $this->ja_prefectures[$prefecture_code]['name'] ?? $prefecture_code;
	}

	/**
	 * SQL condition selecting QSOs confirmed by any of the checked
	 * confirmation methods, e.g. "col_qsl_rcvd = 'Y' or col_lotw_qsl_rcvd = 'Y'".
	 * Returns "1=0" when nothing is checked (so nothing counts as confirmed).
	 */
	private function qsl_condition($postdata) {
		$conditions = [];
		if (($postdata['qsl'] ?? null) == 1) {
			$conditions[] = "col_qsl_rcvd = 'Y'";
		}
		if (($postdata['lotw'] ?? null) == 1) {
			$conditions[] = "col_lotw_qsl_rcvd = 'Y'";
		}
		if (($postdata['eqsl'] ?? null) == 1) {
			$conditions[] = "col_eqsl_qsl_rcvd = 'Y'";
		}
		if (($postdata['qrz'] ?? null) == 1) {
			$conditions[] = "COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y'";
		}
		if (($postdata['clublog'] ?? null) == 1) {
			$conditions[] = "COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y'";
		}
		return $conditions ? implode(' or ', $conditions) : '1=0';
	}

	/**
	 * SQL IN list of quoted reference numbers: "'29009','29011',...".
	 * COL_CNTY is a varchar column, so quoted values compare as strings -
	 * unquoted numbers would force a numeric cast on every row and could
	 * false-match values like '29011A'.
	 */
	private function quoted_in_list($keys) {
		return "'" . implode("','", array_map('strval', $keys)) . "'";
	}

	/**
	 * WHERE clause shared by the status and export queries: Japanese QSOs
	 * (DXCC 339) whose COL_CNTY holds a known gun/county number, in the
	 * active logbook, honoring the band/mode/propagation filters.
	 *
	 * Unlike JCC, JCG numbers have no "ward of a designated city" variant to
	 * fold in, so COL_CNTY is matched directly (see Jcc_model::entity_expr()
	 * for the equivalent JCC case).
	 */
	private function matching_where($postdata, &$bindings) {
		$in_list = $this->quoted_in_list(array_keys($this->jcg_guns($postdata)));

		$where = "col_dxcc in ('339')
			and col_cnty in (" . $in_list . ")
			and station_id in (" . $this->location_list . ")";

		$band = $postdata['band'] ?? 'All';
		if ($band != 'All') {
			if ($band == 'SAT') {
				$where .= " and col_prop_mode = ?";
			} else {
				$where .= " and col_band = ?";
			}
			$bindings[] = $band;
		}

		$mode = $postdata['mode'] ?? 'All';
		if ($mode != 'All') {
			$where .= " and (col_mode = ? or col_submode = ?)";
			$bindings[] = $mode;
			$bindings[] = $mode;
		}

		$prop_mode = $postdata['prop_mode'] ?? 'All';
		if ($prop_mode != 'All') {
			$where .= " and col_prop_mode = ?";
			$bindings[] = $prop_mode;
		}

		return $where;
	}

	/**
	 * Worked/confirmed status per gun. Returns rows of
	 * ['entity' => gun number, 'confirmed' => 0|1] - one row per worked
	 * gun, confirmed is 1 when at least one matching QSO meets the
	 * confirmation filter.
	 */
	function query_jcg_entity_status($postdata) {
		$bindings = [];
		$sql = "select col_cnty as entity, max(confirmed) as confirmed
			from (
				select col_cnty,
					case when (" . $this->qsl_condition($postdata) . ") then 1 else 0 end as confirmed
				from " . $this->config->item('table_name') . " thcv
				where " . $this->matching_where($postdata, $bindings) . "
			) slots
			group by col_cnty";
		return $this->db->query($sql, $bindings)->result_array();
	}

	/**
	 * The grouped slot data for the results grid: prefectures with their
	 * guns rendered as status pills (see render_slot()). Guns come sorted
	 * by number, so prefectures and slots end up in order.
	 */
	function get_jcg_grouped_slot($postdata, $entity_status = null) {
		$entity_status = $entity_status ?? $this->query_jcg_entity_status($postdata);
		$guns = $this->jcg_guns($postdata);
		ksort($guns, SORT_STRING);

		$status = [];
		foreach ($guns as $number => $gun) {
			$status[$number] = '-';
		}
		foreach ($entity_status as $row) {
			$status[$row['entity']] = $row['confirmed'] ? 'C' : 'W';
		}

		$groups = [];
		foreach ($guns as $number => $gun) {
			$prefecture_code = substr((string) $number, 0, 2);
			if (!isset($groups[$prefecture_code])) {
				$groups[$prefecture_code] = [
					'prefecture_code' => $prefecture_code,
					'prefecture_name' => $this->prefecture_name($prefecture_code),
					'slots' => [],
				];
			}
			$groups[$prefecture_code]['slots'][] = $this->render_slot($number, $gun, $status[$number] ?? '-', $postdata);
		}

		return $groups;
	}

	/**
	 * Render one gun as a status pill for the results grid: worked and
	 * confirmed guns link to the matching QSO list, the rest are plain
	 * spans. Deleted guns get a hatch overlay.
	 */
	private function render_slot($number, $gun, $status, $postdata) {
		$classes = 'award-grid-slot btn border d-inline-flex align-items-center justify-content-center ';
		if ($status === 'C') {
			$classes .= 'btn-success';
		} elseif ($status === 'W') {
			$classes .= 'btn-danger';
		} else {
			$classes .= 'btn-secondary';
		}
		if (!empty($gun['deleted'])) {
			$classes .= ' award-grid-slot-deleted';
		}

		// Gun pills show the number's last three digits (the gun's sequence
		// number within its two-digit prefecture code)
		$label = html_escape(substr((string) $number, 2));

		// Tooltip shows the full number, the gun name and "Deleted"
		$tooltip = '<strong>' . html_escape($number) . '</strong>';
		$title = (string) $number;
		if (!empty($gun['name'])) {
			$tooltip .= '<br>' . html_escape($gun['name']);
			$title .= ' - ' . $gun['name'];
		}
		if (!empty($gun['deleted'])) {
			$tooltip .= '<br>' . html_escape(__("Deleted"));
			$title .= ' - ' . __("Deleted");
		}
		$attributes = ' data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true"'
			. ' data-bs-title="' . html_escape($tooltip) . '"'
			. ' title="' . html_escape($title) . '"'
			. ' aria-label="' . html_escape($title) . '"';

		if ($status === 'W' || $status === 'C') {
			$href = $this->display_contacts_href(
				$number,
				$postdata['band'] ?? 'All',
				$postdata['mode'] ?? 'All',
				'JCG',
				$status === 'C' ? $this->qsl_string($postdata) : ''
			);
			return '<a class="' . $classes . '" href="' . html_escape($href) . '"' . $attributes . '>' . $label . '</a>';
		}

		return '<span class="' . $classes . '"' . $attributes . '>' . $label . '</span>';
	}

	/**
	 * The QSL types behind a confirmed slot as a string of letters (Q, L,
	 * E, C, Z) - see displayContacts() in views/interface_assets/footer.php
	 * and qso_details_ajax() in controllers/Awards.php.
	 */
	private function qsl_string($postdata) {
		$qsl = '';
		if (($postdata['qsl'] ?? null) == 1) {
			$qsl .= 'Q';
		}
		if (($postdata['lotw'] ?? null) == 1) {
			$qsl .= 'L';
		}
		if (($postdata['eqsl'] ?? null) == 1) {
			$qsl .= 'E';
		}
		if (($postdata['clublog'] ?? null) == 1) {
			$qsl .= 'C';
		}
		if (($postdata['qrz'] ?? null) == 1) {
			$qsl .= 'Z';
		}
		return $qsl;
	}

	/**
	 * javascript: URL that opens the QSO list behind a slot via
	 * displayContacts() in views/interface_assets/footer.php.
	 */
	private function display_contacts_href($searchphrase, $band, $mode, $type, $qsl = '') {
		$args = [
			(string) $searchphrase,
			(string) $band,
			'All',   // satellite
			'All',   // orbit
			(string) $mode,
			(string) $type,
			(string) $qsl,
			'',      // date from
			'',      // date to
		];
		return 'javascript:displayContacts(' . implode(',', array_map('json_encode', $args)) . ')';
	}

	/**
	 * Summary numbers for the progress bar: worked / confirmed / deleted
	 * guns and their percentages.
	 */
	function get_jcg_summary($postdata, $entity_status = null) {
		$entity_status = $entity_status ?? $this->query_jcg_entity_status($postdata);
		$guns = $this->jcg_guns($postdata);

		$confirmed = 0;
		foreach ($entity_status as $row) {
			if ($row['confirmed']) {
				$confirmed += 1;
			}
		}

		$deleted = 0;
		foreach ($guns as $gun) {
			if (!empty($gun['deleted'])) {
				$deleted += 1;
			}
		}

		$total = count($guns);
		$worked = count($entity_status);
		$worked_only = max(0, $worked - $confirmed);

		return [
			'deleted' => $deleted,
			'total' => $total,
			'worked' => $worked,
			'confirmed' => $confirmed,
			'worked_only' => $worked_only,
			'worked_percent' => $total > 0 ? round(($worked / $total) * 100, 1) : 0,
			'confirmed_percent' => $total > 0 ? round(($confirmed / $total) * 100, 1) : 0,
			'worked_only_percent' => $total > 0 ? round(($worked_only / $total) * 100, 1) : 0,
		];
	}

	/**
	 * Status per gun for the map: gun number => [worked, confirmed].
	 */
	function get_jcg_map_array($postdata, $entity_status = null) {
		$entity_status = $entity_status ?? $this->query_jcg_entity_status($postdata);

		$jcgs = [];
		foreach ($entity_status as $row) {
			$jcgs[$row['entity']] = [1, $row['confirmed'] ? 1 : 0];
		}
		ksort($jcgs, SORT_STRING);

		return $jcgs;
	}

	/**
	 * QSOs for the award application CSV: the first confirmed QSO per gun,
	 * with the gun name attached.
	 */
	function get_jcg_export($postdata) {
		$bindings = [];
		$sql = "select entity, COL_CALL, COL_TIME_ON, COL_BAND, COL_MODE, COL_PROP_MODE
			from (
				select col_cnty as entity, COL_PRIMARY_KEY, COL_CALL, COL_TIME_ON, COL_BAND, COL_MODE, COL_PROP_MODE,
					row_number() over (partition by col_cnty order by COL_TIME_ON asc, COL_PRIMARY_KEY asc) as rn
				from (
					select COL_PRIMARY_KEY, COL_CALL, COL_TIME_ON, COL_BAND, COL_MODE, COL_PROP_MODE, col_cnty
					from " . $this->config->item('table_name') . " thcv
					where " . $this->matching_where($postdata, $bindings) . "
						and (" . $this->qsl_condition($postdata) . ")
				) confirmed_qsos
			) ranked
			where rn = 1
			order by entity";
		$rows = $this->db->query($sql, $bindings)->result_array();

		foreach ($rows as &$row) {
			$row['entity_name'] = $this->ja_guns[$row['entity']]['name'] ?? '';
		}
		unset($row);

		return $rows;
	}

}
?>
