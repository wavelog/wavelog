<?php

class Japan_award_model extends CI_Model {

	protected $location_list = null;

	protected $ja_prefectures = null;
	protected $ja_cities = null;
	protected $ja_kus = null;
	protected $ja_guns = null;

	function __construct() {
		$this->load->library('Genfunctions');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->location_list = "'" . implode("','", $logbooks_locations_array) . "'";
	}

	/**
	 * Load Prefecture data from JSON file into $this->ja_cities
	 */
	protected function load_pref_data_from_json() {
		$this->ja_prefectures = json_decode(file_get_contents(FCPATH . 'assets/json/japan_award/pref_list.json'), true);
	}

	/**
	 * Load JCC data from JSON file into $this->ja_cities
	 */
	protected function load_jcc_data_from_json() {
		$this->ja_cities = json_decode(file_get_contents(FCPATH . 'assets/json/japan_award/jcc_list.json'), true);
	}

	/**
	 * Load KU data from JSON file into $this->ja_kus
	 */
	protected function load_ku_data_from_json() {
		$this->ja_kus = json_decode(file_get_contents(FCPATH . 'assets/json/japan_award/ku_list.json'), true);
	}

	/**
	 * Load JCG data from JSON file into $this->ja_guns
	 */
	protected function load_jcg_data_from_json() {
		$this->ja_guns = json_decode(file_get_contents(FCPATH . 'assets/json/japan_award/jcg_list.json'), true);
	}

	/**
	 * Get the display name for a Japanese prefecture code.
	 *
	 * @param string $prefecture_code The 2-digit prefecture code
	 * @return string The prefecture name
	 */
	protected function get_ja_prefecture_name($prefecture_code) {
		return $this->ja_prefectures[$prefecture_code]['name'] ?? $prefecture_code;
	}

	/**
	 * Filters out entities(cities, guns or kus) that are marked as deleted
	 *
	 * @param array $entity_data The list of entities to filter
	 * @param array $postdata The postdata containing filter options
	 * @return array The filtered list of entities
	 */
	protected function filter_entity_data($entity_data, $postdata) {
		if (($postdata['includedeleted'] ?? null) != null) {
			return $entity_data;
		}

		return array_filter($entity_data, function ($entity) {
			return !(isset($entity['deleted']) && $entity['deleted'] == true);
		});
	}

	/**
	 * Build SQL expression for key_col based on band
	 *
	 * SAT is treated as a separate "band" in wavelog award system
	 *
	 * @return string The SQL expression for key_col
	 */
	protected function build_band_key_expr() {
		return "case
			when col_prop_mode = 'SAT' then 'SAT'
			else col_band
		end";
	}

	/**
	 * Build SQL expression for key_col based on mode
	 *
	 * Based on JARL supported mode endorsements
	 *
	 * @return string The SQL expression for key_col
	 */
	protected function build_mode_key_expr() {
		return "case
			when col_submode = 'DSTAR' then 'DSTAR'
			when col_mode in ('AM', 'FM', 'CW', 'SSB', 'ATV', 'FAX', 'SSTV', 'DIGITALVOICE') then col_mode
			else 'DIGITAL'
		end";
	}

	/**
	 * Build SQL condition for confirmation based on postdata
	 *  cond1 OR cond2 OR cond3 ...
	 *
	 * @param array $postdata The postdata containing filter options
	 * @return string The SQL condition expr
	 */
	protected function get_qsl_condition_sql($postdata) {
		$qsl = array();
		if (($postdata['qsl'] ?? null) == 1) {
			$qsl[] = "col_qsl_rcvd = 'Y'";
		}
		if (($postdata['lotw'] ?? null) == 1) {
			$qsl[] = "col_lotw_qsl_rcvd = 'Y'";
		}
		if (($postdata['eqsl'] ?? null) == 1) {
			$qsl[] = "col_eqsl_qsl_rcvd = 'Y'";
		}
		if (($postdata['qrz'] ?? null) == 1) {
			$qsl[] = "COL_QRZCOM_QSO_DOWNLOAD_STATUS = 'Y'";
		}
		if (($postdata['clublog'] ?? null) == 1) {
			$qsl[] = "COL_CLUBLOG_QSO_DOWNLOAD_STATUS = 'Y'";
		}

		return count($qsl) > 0 ? implode(' or ', $qsl) : '1=0';
	}

	/**
	 * Build SQL expression for confirmation based on postdata
	 * 	CASE WHEN (cond1 OR cond2 OR cond3 ...) THEN 1 ELSE 0 END
	 *
	 * @param array $postdata The postdata containing filter options
	 * @return string The SQL expression for confirmed
	 */
	protected function get_qsl_confirmed_expr($postdata) {
		return 'case when (' . $this->get_qsl_condition_sql($postdata) . ') then 1 else 0 end';
	}

	/**
	 * Build SQL for entity(cities, guns or kus) IN (list)
	 * 	id1, id2, id3 ...
	 *
	 * @param array $entity_data The list of entities to build IN clause for
	 * @return string The SQL string for IN clause
	 */
	protected function build_entity_in_list_sql($entity_data) {
		return implode(',', array_keys($entity_data));
	}

	/**
	 * Build SQL WHERE clause for entity query based on postdata
	 *
	 * @param string $entity_cond Full condition SQL for entity matching,
	 *   e.g. "col_dxcc in ('339') and col_cnty in (...)"
	 * @param array $postdata The postdata containing filter options
	 * @param bool $confirmed_only Whether to include only confirmed QSOs
	 * @return array ['sql' => string, 'bindings' => array]
	 */
	protected function build_entity_query_where_sql($entity_cond, $postdata, $confirmed_only = false) {
		$bindings = array();
		$band = $postdata['band'] ?? 'All';
		$mode = $postdata['mode'] ?? 'All';
		$prop_mode = $postdata['prop_mode'] ?? 'All';

		$where = array(
			"(" . $entity_cond . ")",
			"station_id in (" . $this->location_list . ")",
		);
		if ($band != 'All') {
			if ($band === 'SAT') {
				$where[] = "(col_prop_mode = ?)";
				$bindings[] = $band;
			} else {
				$where[] = "(col_band = ?)";
				$bindings[] = $band;
			}
		}
		if ($mode != 'All') {
			$where[] = "(col_mode = ? or col_submode = ?)";
			$bindings[] = $mode;
			$bindings[] = $mode;
		}
		if ($prop_mode != 'All') {
			$where[] = "(col_prop_mode = ?)";
			$bindings[] = $prop_mode;
		}
		if ($confirmed_only) {
			$where[] = '(' . $this->get_qsl_condition_sql($postdata) . ')';
		}

		return [
			'sql' => implode(" and ", $where),
			'bindings' => $bindings,
		];
	}

	/**
	 * Build the base SQL query for entity status
	 * The query turns eligible QSOs into rows of (entity, key_col and confirmed)
	 *
	 * @param string $entity_expr The SQL expression for entity, e.g. col_cnty or left(col_cnty, 4)
	 * @param string $entity_cond Full condition SQL for entity matching
	 * @param string $key_col The column to use as key_col: 'band', 'mode' or 'none'
	 * @param array $postdata The postdata containing filter options
	 * @return array ['sql' => string, 'bindings' => array]
	 */
	protected function build_entity_status_base_query($entity_expr, $entity_cond, $key_col, $postdata) {
		$confirmed_expr = $this->get_qsl_confirmed_expr($postdata);

		$select = array(
			$entity_expr . ' as entity',
			$confirmed_expr . ' as confirmed',
		);
		if ($key_col === 'band') {
			$select[] = $this->build_band_key_expr() . ' as key_col';
		} else if ($key_col === 'mode') {
			$select[] = $this->build_mode_key_expr() . ' as key_col';
		} else {
			$select[] = "'All' as key_col";
		}
		$select_str = implode(", ", $select);

		$from = $this->config->item('table_name') . " thcv";

		$where = $this->build_entity_query_where_sql($entity_cond, $postdata);

		return [
			'sql' => "select " . $select_str . " from " . $from . " where " . $where['sql'],
			'bindings' => $where['bindings'],
		];
	}

	/**
	 * Build SQL to group the base query by entity and key_col, and aggregate confirmed
	 *
	 * @param string $source_sql The SQL string for the source query
	 * @return string The SQL string for the grouped and aggregated query
	 */
	protected function build_entity_status_max_confirmed_group_by_sql($source_sql) {
		return "select entity, key_col, max(confirmed) as confirmed from (" . $source_sql . ") entity_status group by entity, key_col";
	}

	/**
	 * Build UNION ALL of N query pairs
	 *
	 * @param array $queries Array of ['sql' => string, 'bindings' => array]
	 * @return array ['sql' => string, 'bindings' => array]
	 */
	protected function build_union_all_sql($queries) {
		$sqls = array();
		$bindings = array();
		foreach ($queries as $q) {
			$sqls[] = $q['sql'];
			$bindings = array_merge($bindings, $q['bindings']);
		}
		return [
			'sql' => implode(" union all ", $sqls),
			'bindings' => $bindings,
		];
	}

	/**
	 * Query the entity status from query groups
	 * Return rows of (entity, key_col and confirmed)
	 * The row exists if the slot is worked, and confirmed is 1 if the slot is confirmed
	 *
	 * @param array $query_groups Array of ['entity_expr' => string, 'entity_cond' => string]
	 * @param string $key_col The column to use as key_col: 'band', 'mode' or 'none'
	 * @param array $postdata The postdata containing filter options
	 * @return array The result set as an array of rows
	 */
	function query_entity_status($query_groups, $key_col, $postdata) {
		$group_queries = array();
		foreach ($query_groups as $group) {
			$base = $this->build_entity_status_base_query(
				$group['entity_expr'], $group['entity_cond'], $key_col, $postdata
			);
			$group_sql = $this->build_entity_status_max_confirmed_group_by_sql($base['sql']);
			$group_queries[] = ['sql' => $group_sql, 'bindings' => $base['bindings']];
		}

		$union = $this->build_union_all_sql($group_queries);
		$final_sql = $this->build_entity_status_max_confirmed_group_by_sql($union['sql']);

		$query = $this->db->query($final_sql, $union['bindings']);
		return $query->result_array();
	}

	/**
	 * Build the base SQL query for exporting QSOs for entities
	 * The query selects eligible QSOs for further processing
	 *
	 * @param string $entity_expr The SQL expression for entity
	 * @param string $entity_cond Full condition SQL for entity matching
	 * @param string $key_col The column to use as key_col: 'band', 'mode' or 'none'
	 * @param array $postdata The postdata containing filter options
	 * @return array ['sql' => string, 'bindings' => array]
	 */
	protected function build_export_entity_source_query($entity_expr, $entity_cond, $key_col, $postdata) {
		$select = array(
			$entity_expr . ' as entity',
			'COL_PRIMARY_KEY',
			'COL_CALL',
			'COL_TIME_ON',
			'COL_BAND',
			'COL_MODE',
			'COL_PROP_MODE',
		);
		if ($key_col === 'band') {
			$select[] = $this->build_band_key_expr() . ' as key_col';
		} else if ($key_col === 'mode') {
			$select[] = $this->build_mode_key_expr() . ' as key_col';
		} else {
			$select[] = "'All' as key_col";
		}

		$select_str = implode(", ", $select);

		$from = $this->config->item('table_name') . " thcv";

		$where = $this->build_entity_query_where_sql($entity_cond, $postdata, true);

		return [
			'sql' => 'select ' . $select_str . ' from ' . $from . ' where ' . $where['sql'],
			'bindings' => $where['bindings'],
		];
	}

	/**
	 * Query export QSOs from query groups
	 * Return first confirmed QSO for each entity (+ key_col if applicable)
	 *
	 * @param array $query_groups Array of ['entity_expr' => string, 'entity_cond' => string]
	 * @param string $key_col The column to use as key_col: 'band', 'mode' or 'none'
	 * @param array $postdata The postdata containing filter options
	 * @return array The result set as an array of rows with QSO details
	 */
	function query_export_qsos($query_groups, $key_col, $postdata) {
		$source_queries = array();
		foreach ($query_groups as $group) {
			$source_queries[] = $this->build_export_entity_source_query(
				$group['entity_expr'], $group['entity_cond'], $key_col, $postdata
			);
		}

		$source = $this->build_union_all_sql($source_queries);

		$ranked_sql = 'select source.*, row_number() over (partition by entity, key_col order by COL_TIME_ON asc, COL_PRIMARY_KEY asc) as rn from (' . $source['sql'] . ') source';
		$final_sql = 'select entity, key_col, COL_CALL, COL_TIME_ON, COL_BAND, COL_MODE, COL_PROP_MODE from (' . $ranked_sql . ') ranked where rn = 1 order by entity asc, key_col asc';

		$query = $this->db->query($final_sql, $source['bindings']);
		return $query->result_array();
	}

}
?>