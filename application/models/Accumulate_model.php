<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Accumulate_model extends CI_Model
{
    function get_accumulated_data($band, $award, $mode, $propmode, $period) {
        $this->load->model('logbooks_model');
        $logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

        if ($logbooks_locations_array[0] === -1) {
            return array();
        }

        $location_list = "'" . implode("','", $logbooks_locations_array) . "'";

        switch ($award) {
            case 'dxcc':
                $result = $this->get_accumulated_dxcc($band, $mode, $propmode, $period, $location_list);
                break;
            case 'was':
                $result = $this->get_accumulated_was($band, $mode, $propmode, $period, $location_list);
                break;
            case 'iota':
                $result = $this->get_accumulated_iota($band, $mode, $propmode, $period, $location_list);
                break;
            case 'waz':
                $result = $this->get_accumulated_waz($band, $mode, $propmode, $period, $location_list);
                break;
            case 'vucc':
                $result = $this->get_accumulated_vucc($band, $mode, $propmode, $period, $location_list);
                break;
            case 'waja':
                $result = $this->get_accumulated_waja($band, $mode, $propmode, $period, $location_list);
                break;
        }

        return $result;
    }

    function get_accumulated_by_column($band, $mode, $propmode, $period, $location_list, $column, $where_condition = '', $where_check = '', $outer_where_condition = '') {
		// Use modern CTE/window function approach for MySQL 8.0+
		$dbversion = $this->db->version();
		$dbversion = explode('.', $dbversion);

		if ($dbversion[0] >= "8") {
			return $this->get_accumulated_by_column_fast($band, $mode, $propmode, $period, $location_list, $column, $where_condition, $outer_where_condition);
		} else {
			return $this->get_accumulated_by_column_slow($band, $mode, $propmode, $period, $location_list, $column, $where_condition, $where_check, $outer_where_condition);
		}
	}

	function get_accumulated_by_column_fast($band, $mode, $propmode, $period, $location_list, $column, $where_condition = '', $outer_where_condition = '') {
		$binding = [];

		// Build the filter conditions used in both CTEs
		$filter_conditions = $this->build_filter_conditions($band, $mode, $propmode);

		// Build WHERE clause for inner queries
		$where_clause = "station_id in (" . $location_list . ")";
		if ($where_condition) {
			$where_clause .= " and " . $where_condition;
		}

		// Build the year/month selection
		if ($period == "year") {
			$year_expr = "YEAR(col_time_on)";
		} else if ($period == "month") {
			$year_expr = "DATE_FORMAT(col_time_on, '%Y-%m')";
		}

		// For DXCC, we need an outer WHERE condition in the all_periods CTE
		$all_periods_where = $where_clause . " " . $filter_conditions['sql'];
		if ($outer_where_condition) {
			$all_periods_where .= " and " . $outer_where_condition;
		}

		$sql = "WITH all_periods AS (
			SELECT DISTINCT " . $year_expr . " AS year
			FROM " . $this->config->item('table_name') . "
			WHERE " . $all_periods_where . "
		),
		new_items_per_period AS (
			SELECT
				year,
				COUNT(*) AS tot
			FROM (
				SELECT
					" . $column . ",
					" . $year_expr . " AS year,
					ROW_NUMBER() OVER (PARTITION BY " . $column . " ORDER BY " . $year_expr . ") AS rn
				FROM " . $this->config->item('table_name') . "
				WHERE " . $where_clause . "
				" . $filter_conditions['sql'] . "
			) ranked
			WHERE rn = 1
			GROUP BY year
		)
		SELECT
			ap.year,
			COALESCE(nipp.tot, 0) AS tot
		FROM all_periods ap
		LEFT JOIN new_items_per_period nipp ON ap.year = nipp.year
		ORDER BY ap.year";

		// Duplicate bindings for both CTEs (same filters used twice)
		$final_bindings = array_merge($filter_conditions['bindings'], $filter_conditions['bindings']);

		$query = $this->db->query($sql, $final_bindings);
		return $this->count_and_add_accumulated_total($query->result());
	}

	function build_filter_conditions($band, $mode, $propmode) {
		$sql = "";
		$binding = [];

		// Normal band/propmode handling
		if ($band == 'SAT') {
			$sql .= "and col_prop_mode = ?";
			$binding[] = $band;
		} else {
			if ($band != 'All') {
				$sql .= "and col_band = ?";
				$binding[] = $band;
			}
			if ($propmode == 'NoSAT') {
				$sql .= "and col_prop_mode !='SAT'";
			} elseif ($propmode == 'None') {
				$sql .= "and (trim(col_prop_mode)='' or col_prop_mode is null)";
			} elseif ($propmode == 'All') {
				// No Prop-Filter
			} else {
				$sql .= "and col_prop_mode = ?";
				$binding[] = $propmode;
			}
		}

		if ($mode != 'All') {
			$sql .= "and (col_mode = ? or col_submode = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		return ['sql' => $sql, 'bindings' => $binding];
	}

	function get_accumulated_by_column_slow($band, $mode, $propmode, $period, $location_list, $column, $where_condition = '', $where_check = '', $outer_where_condition = '') {
		$filter_conditions = $this->build_filter_conditions($band, $mode, $propmode);
		$binding = $filter_conditions['bindings'];
		$filter_sql = $filter_conditions['sql'];

		if ($period == "year") {
			$sql = "select year(thcv.col_time_on) year";
		} else if ($period == "month") {
			$sql = "select date_format(col_time_on, '%Y-%m') year";
		}

		$sql .= ", coalesce(y.tot, 0) tot
			from " . $this->config->item('table_name') . " thcv
			left outer join (
				select count(" . $column . ") as tot, year
				from (select distinct ";

		if ($period == "year") {
			$sql .= "year(col_time_on)";
		} else if ($period == "month") {
			$sql .= "date_format(col_time_on, '%Y-%m')";
		}

		$sql .= " year, " . $column . "
			from " . $this->config->item('table_name') .
			" where station_id in (" . $location_list . ")";

		if ($where_condition) {
			$sql .= " and " . $where_condition;
		}

		$sql .= " " . $filter_sql;

		$sql .= " order by year
	) x
	where not exists (select 1 from " . $this->config->item('table_name') . " where";

		if ($period == "year") {
			$sql .= " year(col_time_on) < year";;
		} else if ($period == "month") {
			$sql .= " date_format(col_time_on, '%Y-%m') < year";;
		}

		$sql .= " and " . $column . " = x." . $column;

		$sql .= " " . $filter_sql;

		if ($where_check) {
			$sql .= " and " . $where_check;
		}

		$sql .= " and station_id in (" . $location_list . "))
			group by year
			order by year";

		if ($period == "year") {
			$sql .= " ) y on year(thcv.col_time_on) = y.year";
		} else if ($period == "month") {
			$sql .= " ) y on date_format(col_time_on, '%Y-%m') = y.year";
		}

		$sql .= " where station_id in (" . $location_list . ")";

		if ($outer_where_condition) {
			$sql .= " and " . $outer_where_condition;
		}

		$sql .= " " . $filter_sql;

		if ($period == "year") {
			$sql .= " group by year(thcv.col_time_on), y.tot
				order by year(thcv.col_time_on)";
		} else if ($period == "month") {
			$sql .= " group by date_format(col_time_on, '%Y-%m'), y.tot
				order by date_format(col_time_on, '%Y-%m')";
		}

		// Triplicate bindings for the three filter locations
		$final_bindings = array_merge($binding, $binding, $binding);

		$query = $this->db->query($sql, $final_bindings);

		return $this->count_and_add_accumulated_total($query->result());
    }

	function count_and_add_accumulated_total($array) {
		$counter = 0;
		for ($i = 0; $i < count($array); $i++) {
			$array[$i]->total = $array[$i]->tot + $counter;
			$counter = $array[$i]->total;
			}
		return $array;
	}

	function get_accumulated_dxcc($band, $mode, $propmode, $period, $location_list) {
		$where_condition = "COL_DXCC > 0";
		$where_check = "COL_DXCC > 0";
		return $this->get_accumulated_by_column($band, $mode, $propmode, $period, $location_list, 'col_dxcc', $where_condition, $where_check, $where_condition, true);
	}

	function get_accumulated_waja($band, $mode, $propmode, $period, $location_list) {
		$where_condition = "COL_DXCC in ('339') and trim(coalesce(col_state,'')) != ''";
		$where_check = "COL_DXCC in ('339')";
		return $this->get_accumulated_by_column($band, $mode, $propmode, $period, $location_list, 'col_state', $where_condition, $where_check);
	}

	function get_accumulated_was($band, $mode, $propmode, $period, $location_list) {
		$where_condition = "COL_DXCC in ('291', '6', '110') and COL_STATE in ('AK','AL','AR','AZ','CA','CO','CT','DE','FL','GA','HI','IA','ID','IL','IN','KS','KY','LA','MA','MD','ME','MI','MN','MO','MS','MT','NC','ND','NE','NH','NJ','NM','NV','NY','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VA','VT','WA','WI','WV','WY')";
		return $this->get_accumulated_by_column($band, $mode, $propmode, $period, $location_list, 'col_state', $where_condition, $where_condition);
	}

	function get_accumulated_iota($band, $mode, $propmode, $period, $location_list) {
		$where_condition = "COL_IOTA > ''";
		return $this->get_accumulated_by_column($band, $mode, $propmode, $period, $location_list, 'col_iota', $where_condition, $where_condition);
	}

	function get_accumulated_waz($band, $mode, $propmode, $period, $location_list) {
		return $this->get_accumulated_by_column($band, $mode, $propmode, $period, $location_list, 'col_cqz', 'col_cqz between 1 and 40', 'col_cqz between 1 and 40');
	}

    function get_accumulated_vucc($band, $mode, $propmode, $period, $location_list) {
		$dbversion = $this->db->version();
		$dbversion = explode('.', $dbversion);

		$sql = "";
		if ($dbversion[0] >= "8") {
			$query = $this->fastVuccQuery($band, $mode, $propmode, $period, $location_list);
			return $query->result();
		} else {
			$query = $this->slowVuccQuery($band, $mode, $propmode, $period, $location_list);
			return $this->count_and_add_accumulated_total($query->result());
		}
    }

    function fastVuccQuery($band, $mode, $propmode, $period, $location_list) {
		$filter_conditions = $this->build_filter_conditions($band, $mode, $propmode);
		$binding = $filter_conditions['bindings'];
		$filter_sql = $filter_conditions['sql'];

		$sql = "WITH firstseen AS (
			SELECT substr(col_gridsquare,1,4) as grid, ";

		if ($period == "year") {
			$sql .= "MIN(year(col_time_on)) year";
		} else if ($period == "month") {
			$sql .= "MIN(date_format(col_time_on, '%Y-%m')) year";
		}

		$sql .= " from " . $this->config->item('table_name') . " thcv
			where coalesce(col_gridsquare, '') <> ''
			and station_id in (" . $location_list . ")
			" . $filter_sql . "
			GROUP BY 1
			union all
			select substr(grid, 1,4) as grid, year
			from (
				select TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(COL_VUCC_GRIDS, ',', x.x), ',',-1)) as grid, ";
		if ($period == "year") {
			$sql .= "MIN(year(col_time_on)) year";
		} else if ($period == "month") {
			$sql .= "MIN(date_format(col_time_on, '%Y-%m')) year";
		}

		$sql .= " from " . $this->config->item('table_name') . " thcv
			cross join (
				select 1 as x
				union all
				select 2
				union all
				select 3
				union all
				select 4) x
				where
				x.x <= length(COL_VUCC_GRIDS)-length(replace(COL_VUCC_GRIDS, ',', ''))+ 1
				and coalesce(COL_VUCC_GRIDS, '') <> ''
				and station_id in (" . $location_list . ")
				" . $filter_sql . "
			GROUP BY 1) as z
		)
		, z as (
			SELECT grid, row_number() OVER (partition by grid ORDER BY grid asc, year asc) as rn, year
			FROM firstseen
		) select DISTINCT COUNT(grid) OVER (ORDER BY year) as total, year from z where rn = 1
	";

		// Duplicate bindings for both parts of the UNION
		$final_bindings = array_merge($binding, $binding);

		$query = $this->db->query($sql, $final_bindings);
		return $query;
	}

	function slowVuccQuery($band, $mode, $propmode, $period, $location_list) {
		$filter_conditions = $this->build_filter_conditions($band, $mode, $propmode);
		$binding = $filter_conditions['bindings'];
		$filter_sql = $filter_conditions['sql'];

		if ($period == "year") {
			$sql = "select year(thcv.col_time_on) year";
		} else if ($period == "month") {
			$sql = "select date_format(col_time_on, '%Y-%m') year";
		}

		$sql .= ", coalesce(y.tot, 0) tot
			from " . $this->config->item('table_name') . " thcv
			left outer join (
				select count(substr(col_gridsquare,1,4)) as tot, year
				from (select distinct ";

		if ($period == "year") {
			$sql .= "year(col_time_on)";
		} else if ($period == "month") {
			$sql .= "date_format(col_time_on, '%Y-%m')";
		}

		$sql .= " year, substr(col_gridsquare,1,4) as col_gridsquare
			from " . $this->config->item('table_name') .
			" where station_id in (" . $location_list . ")"
			. $filter_sql . "
			order by year
	) x
	where not exists (select 1 from " . $this->config->item('table_name') . " where";

		if ($period == "year") {
			$sql .= " year(col_time_on) < year";;
		} else if ($period == "month") {
			$sql .= " date_format(col_time_on, '%Y-%m') < year";;
		}

		$sql .= " and substr(col_gridsquare,1,4) = substr(x.col_gridsquare,1,4)"
			. $filter_sql . "
			and station_id in (" . $location_list . "))
			group by year
			order by year";

		if ($period == "year") {
			$sql .= " ) y on year(thcv.col_time_on) = y.year";
		} else if ($period == "month") {
			$sql .= " ) y on date_format(col_time_on, '%Y-%m') = y.year";
		}

		$sql .= " where station_id in (" . $location_list . ")" . $filter_sql;

		if ($period == "year") {
			$sql .= " group by year(thcv.col_time_on), y.tot
				order by year(thcv.col_time_on)";
		} else if ($period == "month") {
			$sql .= " group by date_format(col_time_on, '%Y-%m'), y.tot
				order by date_format(col_time_on, '%Y-%m')";
		}

		// Triplicate bindings for the three filter locations
		$final_bindings = array_merge($binding, $binding, $binding);

		$query = $this->db->query($sql, $final_bindings);
		return $query;
	}

}
