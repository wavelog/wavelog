<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Activators_model extends CI_Model
{

	function get_activators($band, $mincount, $leogeo) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if ($mincount == '' || $mincount == 0 || !is_numeric($mincount)) {
			$mincount = 2;
		}

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'" . implode("','", $logbooks_locations_array) . "'";
		$binding = array();

		$sql = "select
			`call`,
			COUNT(distinct(grid)) as `count`,
			GROUP_CONCAT(distinct grid order by `grid` separator ', ') as `grids`
			from (
				select
					distinct COL_CALL as `call`,
					SUBSTR(COL_GRIDSQUARE, 1, 4) grid
				from
					" . $this->config->item('table_name') . "
				where
					station_id in (" . $location_list . ")
					and `COL_GRIDSQUARE` != ''";

		if ($band != 'All') {
			$binding[] = $band;
			if ($band == 'SAT') {
				switch ($leogeo) {
					case 'both':
					$sql .= " and col_prop_mode = ?";
					break;
					case 'leo':
					$sql .= " and col_prop_mode = ?";
					$sql .= " and col_sat_name != 'QO-100'";
					break;
					case 'geo':
					$sql .= " and col_prop_mode = ?";
					$sql .= " and col_sat_name = 'QO-100'";
					break;
					default:
					$sql .= " and col_prop_mode = ?";
					break;
				}
			} else {
				$sql .= " and col_prop_mode != 'SAT'";
				$sql .= " and COL_BAND = ?";
			}
		}

		$sql .= " union
				select distinct COL_CALL as `call`, substr(grid, 1,4) as grid
					from (
						select TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(COL_VUCC_GRIDS, ',', x.x), ',',-1)) as grid, col_call from " . $this->config->item('table_name') . "
					cross join (
						select 1 as x
						union all
						select 2
						union all
						select 3
						union all
						select 4) x
						where x.x <= length(COL_VUCC_GRIDS)-length(replace(COL_VUCC_GRIDS, ',', ''))+ 1
						and coalesce(COL_VUCC_GRIDS, '') <> ''
						and station_id in (" . $location_list . ")";

		if ($band != 'All') {
			$binding[] = $band;
			if ($band == 'SAT') {
				switch ($leogeo) {
					case 'both':
					$sql .= " and col_prop_mode = ?";
					break;
					case 'leo':
					$sql .= " and col_prop_mode = ?";
					$sql .= " and col_sat_name != 'QO-100'";
					break;
					case 'geo':
					$sql .= " and col_prop_mode = ?";
					$sql .= " and col_sat_name = 'QO-100'";
					break;
					default:
					$sql .= " and col_prop_mode = ?";
					break;
				}
			} else {
				$sql .= " and col_prop_mode != 'SAT'";
				$sql .= " and COL_BAND = ?";
			}
		}

		$sql .= " GROUP BY 1, COL_CALL
					) as z
			) as x
			group by `call`
			having `count` >= ?
			order by `count` desc";

		$binding[] = $mincount;

		$query = $this->db->query($sql, $binding);

		return $query->result();

	}

   function get_max_activated_grids()
   {
      $this->load->model('logbooks_model');
      $logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

      if (!$logbooks_locations_array) {
         return array();
      }

      $location_list = "'" . implode("','", $logbooks_locations_array) . "'";

      // Get max no of activated grids of single operator
	  $sql = "
		select count(distinct(grid)) as `count` from (
			select distinct(SUBSTR(COL_GRIDSQUARE, 1, 4)) as grid, col_call
			from " . $this->config->item('table_name') . "
			where station_id in (" . $location_list . ")
				and `COL_GRIDSQUARE` != ''
			union
			select distinct substr(grid, 1,4) as grid, COL_CALL
					from (
						select TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(COL_VUCC_GRIDS, ',', x.x), ',',-1)) as grid, col_call from " . $this->config->item('table_name') . "
					cross join (
						select 1 as x
						union all
						select 2
						union all
						select 3
						union all
						select 4) x
						where x.x <= length(COL_VUCC_GRIDS)-length(replace(COL_VUCC_GRIDS, ',', ''))+ 1
						and coalesce(COL_VUCC_GRIDS, '') <> ''
						and station_id in (" . $location_list . ")
						GROUP BY 1, COL_CALL
					) as z
		) as x
		group by col_call
		order by `count` desc
		limit 1
		";


      $data = $this->db->query($sql);
      foreach ($data->result() as $row) {
         $max =  $row->count;
      }

      return ($max ?? 0);
   }
}
