<?php

class Jcc_model extends CI_Model {


	private $location_list=null;
	function __construct() {
		$this->load->library('Genfunctions');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->location_list = "'".implode("','",$logbooks_locations_array)."'";
		$this->load_jcc_data_from_json();
		$this->load_ku_data_from_json();
	}

	// The list of JCC cities and KU areas, loaded from JSON files in assets/json/japan_award/
	public $ja_cities = array();
	public $ja_kus = array();

	/**
	 * Load JCC data from JSON file into $this->ja_cities
	 */
	private function load_jcc_data_from_json() {
		$this->ja_cities = json_decode(file_get_contents(FCPATH . 'assets/json/japan_award/jcc_list.json'), true);
	}

	/**
	 * Load KU data from JSON file into $this->ja_kus
	 */
	private function load_ku_data_from_json() {
		$this->ja_kus = json_decode(file_get_contents(FCPATH . 'assets/json/japan_award/ku_list.json'), true);
	}

	/**
	 * Filters out entities(cities or kus) that are marked as deleted
	 * 
	 * @param array $entity_data The list of entities to filter, usually ja_cities, ja_guns or ja_kus
	 * @param array $postdata The postdata containing filter options
	 * @return array The filtered list of entities
	 */
	private function filter_entity_data($entity_data, $postdata) {
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
	 * SAT is treated as a separate "band" in wavelog award system, which is a little strange
	 * 
	 * @return string The SQL expression for key_col
	 */
	private function build_band_key_expr() {
		return "case
			when col_prop_mode = 'SAT' then 'SAT'
			else col_band
		end";
	}

	/**
	 * Build SQL expression for key_col based on mode
	 * 
	 * Based on JARL supported mode endorsements
	 * Note: This function is not been used in current implementation.
	 * 
	 * @return string The SQL expression for key_col
	 */
	private function build_mode_key_expr() {
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
	 * Similar to Genfunctions->addQslToQuery, but without an 'AND' prefix
	 * May be moved to Genfunctions in the future
	 * 
	 * @param array $postdata The postdata containing filter options
	 * @return string The SQL condition expr
	 */
	private function get_qsl_condition_sql($postdata) {
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
		if (($postdata['dcl'] ?? null) == 1) {
			$qsl[] = "COL_DCL_QSL_RCVD = 'Y'";
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
	private function get_qsl_confirmed_expr($postdata) {
		return 'case when (' . $this->get_qsl_condition_sql($postdata) . ') then 1 else 0 end';
	}

	/**
	 * Build SQL for entity(cities, guns or kus) IN (list)
	 * 	id1, id2, id3 ...
	 * 
	 * @param array $entity_data The list of entities to build IN clause for
	 * @return string The SQL string for IN clause
	 */
	private function build_entity_in_list_sql($entity_data) {
		$keys = array_map(function ($key) {
			return $this->db->escape((string) $key);
		}, array_keys($entity_data));

		return implode(',', $keys);
	}

	/**
	 * Build SQL WHERE clause for entity status query based on postdata
	 * 
	 * @param string $entity_in_list_sql The SQL string for entity IN clause
	 * @param array $postdata The postdata containing filter options
	 * @param array $bindings The array to store query bindings for prepared statement
	 * @param bool $confirmed_only Whether to include only confirmed QSOs in the condition
	 * @return string The SQL string for WHERE clause
	 */
	private function build_entity_query_where_sql($entity_in_list_sql, $postdata, &$bindings, $confirmed_only = false) {
		$band = $postdata['band'] ?? 'All';
		$mode = $postdata['mode'] ?? 'All';
		$prop_mode = $postdata['prop_mode'] ?? 'All';

		$where = array(
			"col_dxcc in ('339')",
			"col_cnty in (" . $entity_in_list_sql . ")",
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

		return implode(" and ", $where);
	}

	/**
	 * Build the base SQL query for entity status
	 * The query turns eligible QSOs into rows of (entity, key_col and confirmed) for further group and aggregation
	 * 
	 * @param string $entity_expr The SQL expression for entity, e.g. col_cnty or left(col_cnty, 4)
	 * @param string $entity_in_list_sql The SQL string for entity IN clause
	 * @param string $key_col The column to use as key_col in the result, can be 'band', 'mode' or 'none'
	 * @param array $postdata The postdata containing filter options
	 * @param array $bindings The array to store query bindings for prepared statement
	 * @return string The SQL string for the base query
	 */
	private function build_entity_status_base_query($entity_expr, $entity_in_list_sql, $key_col, $postdata, &$bindings) {
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
			// No additional bindings needed since key_col is a constant in this case
		}
		$select_str = implode(", ", $select);

		$from = $this->config->item('table_name') . " thcv";

		$where_str = $this->build_entity_query_where_sql($entity_in_list_sql, $postdata, $bindings);

		$sql = "select " . $select_str . " from " . $from . " where " . $where_str;

		return $sql;
	}

	/**
	 * Build SQL to group the base query by entity and key_col, and aggregate confirmed
	 * 
	 * @param string $source_sql The SQL string for the source query to group and aggregate
	 * @return string The SQL string for the grouped and aggregated query
	 */
	private function build_entity_status_max_confirmed_group_by_sql($source_sql) {
		return "select entity, key_col, max(confirmed) as confirmed from (" . $source_sql . ") entity_status group by entity, key_col";
	}

	/**
	 * Build SQL to union all two entity status queries
	 * 
	 * @param string $left_sql The SQL string for the left query
	 * @param string $right_sql The SQL string for the right query
	 * @return string The SQL string for the union all query
	 */
	private function build_entity_status_union_all_sql($left_sql, $right_sql) {
		return $left_sql . " union all " . $right_sql;
	}

	/**
	 * Query the entity status based on postdata and key_col
	 * Return rows of (entity, key_col and confirmed)
	 * The row exists if the slot is worked, and confirmed is 1 if the slot is confirmed
	 * 
	 * @param array $postdata The postdata containing filter options
	 * @param string $key_col The column to use as key_col in the result, can be 'band', 'mode' or 'none'
	 * @return array The result set as an array of rows
	 */
	function query_entity_status($postdata, $key_col = "none") {
		$jcc_data = $this->filter_entity_data($this->ja_cities, $postdata);
		$ku_data = $this->filter_entity_data($this->ja_kus, $postdata);
		$jcc_in_list = $this->build_entity_in_list_sql($jcc_data);
		$ku_in_list = $this->build_entity_in_list_sql($ku_data);

		$bindings = array();

		// Query QSOs with any JCCs, group by city and key_col
		$jcc_source_sql = $this->build_entity_status_base_query('col_cnty', $jcc_in_list, $key_col, $postdata, $bindings);
		$jcc_group_sql = $this->build_entity_status_max_confirmed_group_by_sql($jcc_source_sql);

		// Query QSOs with any Kus, classify to cities, group by city and key_col
		$ku_source_sql = $this->build_entity_status_base_query('left(col_cnty, 4)', $ku_in_list, $key_col, $postdata, $bindings);
		$ku_group_sql = $this->build_entity_status_max_confirmed_group_by_sql($ku_source_sql);

		// Union the two queries, then group again
		$union_sql = $this->build_entity_status_union_all_sql($jcc_group_sql, $ku_group_sql);
		$final_sql = $this->build_entity_status_max_confirmed_group_by_sql($union_sql);

		$query = $this->db->query($final_sql, $bindings);
		$rows = $query->result_array();

		return $rows;
	}

	/**
	 * Get the JCC status array for display on the table
	 * 	array[city][band] = 'C' if confirmed, 'W' if worked but not confirmed, '-' if not worked
	 * 
	 * @param array $bands The list of bands to include in the result
	 * @param array $postdata The postdata containing filter options
	 * @param array|null $entity_status The pre-query entity status to use
	 */
	function get_jcc_array($bands, $postdata, $entity_status = null) {
		if ($entity_status === null) {
			$entity_status = $this->query_entity_status($postdata, 'band');
		}

		$jcc_list = $this->filter_entity_data($this->ja_cities, $postdata);

		$cities = array();
		// Initializing the array with all cities and bands
		foreach ($jcc_list as $city => $city_data) {
			$cities[$city]['Number'] = $city;
			$cities[$city]['City'] = $city_data['name'];
			$cities[$city]['count'] = 0;
			foreach ($bands as $band) {
				// Sets all to dash to indicate no result
				$cities[$city][$band] = '-';
			}
		}

		foreach ($entity_status as $row) {
			if ($row['confirmed'] == 1) {
				if ($postdata['confirmed'] != NULL) {
					$cities[$row['entity']][$row['key_col']] = 'C';
					$cities[$row['entity']]['count'] += 1;
				}
			} else {
				if ($postdata['worked'] != NULL) {
					$cities[$row['entity']][$row['key_col']] = 'W';
					$cities[$row['entity']]['count'] += 1;
				}
			}
		}

		if ($postdata['notworked'] == NULL) {
			foreach ($cities as $city => $city_data) {
				if ($city_data['count'] == 0) {
					unset($cities[$city]);
				}
			}
		}

		if (!empty($cities)) {
			return $cities;
		} else {
			return 0;
		}
	}


	/**
	 * Get the JCC summary array for display on the table
	 * 	array['worked'][band] = count of worked cities for the band
	 * 	array['confirmed'][band] = count of confirmed cities for the band
	 * 
	 * @param array $bands The list of bands to include in the result
	 * @param array $postdata The postdata containing filter options
	 * @param array|null $entity_status The pre-query entity status to use
	 */
	function get_jcc_summary($bands, $postdata, $entity_status = null) {
		if ($entity_status === null) {
			$entity_status = $this->query_entity_status($postdata, 'band');
		}

		$summary = array(
			'worked' => array(),
			'confirmed' => array(),
		);

		// $worked_by_band = array();
		// $confirmed_by_band = array();
		foreach ($bands as $band) {
			$summary['worked'][$band] = 0;
			$summary['confirmed'][$band] = 0;
		}

		$worked_total = array();
		$confirmed_total = array();

		foreach ($entity_status as $row) {
			$worked_total[$row['entity']] = true;
			$summary['worked'][$row['key_col']] += 1;
			if ($row['confirmed'] == 1) {
				$confirmed_total[$row['entity']] = true;
				$summary['confirmed'][$row['key_col']] += 1;
			}
		}

		$summary['worked']['Total'] = count($worked_total);
		$summary['confirmed']['Total'] = count($confirmed_total);

		// make sure SAT is after Total
		// I don't know why, but the origin design is such.
		if (isset($summary['worked']['SAT']) && isset($summary['confirmed']['SAT'])) {
			$summary_worked_sat = $summary['worked']['SAT'];
			$summary_confirmed_sat = $summary['confirmed']['SAT'];

			unset($summary['worked']['SAT']);
			unset($summary['confirmed']['SAT']);

			$summary['worked']['SAT'] = $summary_worked_sat;
			$summary['confirmed']['SAT'] = $summary_confirmed_sat;
		}

		return $summary;
	}

	/**
	 * Get the JCC map array for display on the map
	 * 	array[city] = [worked, confirmed]
	 * 
	 * @param array $postdata The postdata containing filter options
	 * @param array|null $entity_status The pre-query entity status to use
	 * @return array The JCC map array for display on the map
	 */
	function get_jcc_map_array($postdata, $entity_status = null) {
		if ($entity_status === null) {
			$entity_status = $this->query_entity_status($postdata, 'none');
		}

		$jccs = array();
		foreach ($entity_status as $row) {
			$entity = $row['entity'];
			if (!isset($jccs[$entity])) {
				$jccs[$entity] = array(1, 0);
			}

			if ($row['confirmed'] == 1) {
				$jccs[$entity][1] = 1;
			}
		}

		ksort($jccs, SORT_STRING);

		return $jccs;
	}

	/**
	 * Build the base SQL query for exporting QSOs for entities(cities or kus)
	 * The query selects eligible QSOs for further processing
	 * 
	 * @param string $entity_expr The SQL expression for entity, e.g. col_cnty or left(col_cnty, 4)
	 * @param string $entity_in_list_sql The SQL string for entity IN clause
	 * @param array $postdata The postdata containing filter options
	 * @param array $bindings The array to store query bindings for prepared statement
	 * @return string The SQL string for the export query
	 */
	private function build_export_entity_source_query($entity_expr, $entity_in_list_sql, $postdata, &$bindings) {
		$select = array(
			$entity_expr . ' as entity',
			'COL_PRIMARY_KEY',
			'COL_CALL',
			'COL_TIME_ON',
			'COL_BAND',
			'COL_MODE',
			'COL_PROP_MODE',
		);

		$select_str = implode(", ", $select);

		$from = $this->config->item('table_name') . " thcv";

		$where_str = $this->build_entity_query_where_sql($entity_in_list_sql, $postdata, $bindings, true);

		return 'select ' . $select_str . ' from ' . $from . ' where ' . $where_str;
	}

	/**
	 * Export QSOs for entities(cities or kus) based on postdata
	 * Return first confirmed QSO for each entity, with QSO details
	 * 
	 * @param array $postdata The postdata containing filter options
	 * @return array The result set as an array of rows with QSO details
	 */
	function query_export_qsos($postdata) {
		$jcc_data = $this->filter_entity_data($this->ja_cities, $postdata);
		$ku_data = $this->filter_entity_data($this->ja_kus, $postdata);
		$jcc_in_list = $this->build_entity_in_list_sql($jcc_data);
		$ku_in_list = $this->build_entity_in_list_sql($ku_data);

		$bindings = array();

		// Query QSOs with any JCCs or Kus, then union the results
		$jcc_source_sql = $this->build_export_entity_source_query('col_cnty', $jcc_in_list, $postdata, $bindings);
		$ku_source_sql = $this->build_export_entity_source_query('left(col_cnty, 4)', $ku_in_list, $postdata, $bindings);
		$source_sql = $this->build_entity_status_union_all_sql($jcc_source_sql, $ku_source_sql);

		// Rank the QSOs for each entity by time and primary key, then select the first one for each entity
		$ranked_sql = 'select source.*, row_number() over (partition by entity order by COL_TIME_ON asc, COL_PRIMARY_KEY asc) as rn from (' . $source_sql . ') source';
		$final_sql = 'select entity, COL_CALL, COL_TIME_ON, COL_BAND, COL_MODE, COL_PROP_MODE from (' . $ranked_sql . ') ranked where rn = 1 order by entity asc';

		$query = $this->db->query($final_sql, $bindings);
		$rows = $query->result_array();

		return $rows;
	}

	/**
	 * Export QSOs for JCC award based on postdata
	 * Return first confirmed QSO for each city, with QSO details and city name
	 * 
	 * @param array $postdata The postdata containing filter options
	 * @return array The result set as an array of rows with QSO details and city name
	 */
	function get_jcc_export($postdata) {
		$rows = $this->query_export_qsos($postdata);

		foreach ($rows as &$row) {
			$row['entity_name'] = $this->ja_cities[$row['entity']]['name'] ?? '';
		}
		unset($row);

		return $rows;
	}

}
?>
