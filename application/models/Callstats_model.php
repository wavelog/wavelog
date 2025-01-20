<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Callstats_model extends CI_Model {

	private $logbooks_locations_array;

	public function __construct() {
		$this->load->model('logbooks_model');
		$this->logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
	}

	function get_activators($band, $mode, $propagation, $mincount, $orbit, $sat) {

		if ($mincount == '' || $mincount == 0 || !is_numeric($mincount)) {
			$mincount = 2;
		}

		if (!$this->logbooks_locations_array) {
			return null;
		}

		$location_list = "'" . implode("','", $this->logbooks_locations_array) . "'";
		$binding = array();

		$sql = "select
			`col_call` as `call`,
			COUNT(*) as `count`
				from " . $this->config->item('table_name') . "
				left join satellite on ".$this->config->item('table_name').".COL_SAT_NAME = satellite.name
				where station_id in (" . $location_list . ")";
		if ($band != 'All') {
			if ($band == 'SAT') {
				$sql .= " and col_prop_mode = ? ";
				$binding[] = $band;
				if ($sat != 'All' && $sat != '') {
					$sql .= " and col_sat_name = ?";
					$binding[] = $sat;
				}
			} else {
				if ($propagation == 'None') {
					$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
				} elseif ($propagation == 'NoSAT') {
					$sql .= " and col_prop_mode != 'SAT'";
				} elseif ($propagation != '') {
					$sql .= " and col_prop_mode = ?";
					$binding[] = $propagation;
				}
				$sql .= " and col_band = ?";
				$binding[] = $band;
			}
		} else {
			if ($propagation == 'None') {
				$sql .= " and (trim(col_prop_mode) = '' or col_prop_mode is null)";
			} elseif ($propagation == 'NoSAT') {
				$sql .= " and col_prop_mode != 'SAT'";
			} elseif ($propagation != '') {
				$sql .= " and col_prop_mode = ?";
				$binding[] = $propagation;
			}
		}

		if ($mode != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		if ($orbit != 'All') {
			$sql .= " AND satellite.orbit = ?";
			$binding[] = $orbit;
		}

		$sql .= "
			group by `col_call`
			having `count` >= ?
			order by `count` desc";

		$binding[] = $mincount;

		$query = $this->db->query($sql, $binding);

		return $query->result();

	}

	function get_max_qsos() {

		if (!$this->logbooks_locations_array) {
			return array();
		}

		$location_list = "'" . implode("','", $this->logbooks_locations_array) . "'";

		$sql = "select max(count) as count from (
			select count(*) count, col_call
			from " . $this->config->item('table_name') . "
			where station_id in (" . $location_list . ")
			group by col_call
			order by count(*) desc
			) as x;";

		$data = $this->db->query($sql);
		foreach ($data->result() as $row) {
			$max =  $row->count;
		}

		return ($max ?? 0);
	}

	function get_worked_modes() {
		if (!$this->logbooks_locations_array) {
			return null;
		}

		$modes = array();

		$this->db->select('distinct col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where_in('station_id', $this->logbooks_locations_array);
		$this->db->order_by('col_mode, col_submode', 'ASC');

		$query = $this->db->get($this->config->item('table_name'));

		foreach($query->result() as $mode){
			if ($mode->col_submode == null || $mode->col_submode == "") {
				array_push($modes, $mode->col_mode);
			} else {
				array_push($modes, $mode->col_submode);
			}
		}

		return $modes;
	}

	/*
	 * Used to fetch QSOs from the table
	 */
	public function qso_details($searchphrase, $band, $mode, $sat, $orbit, $propagation) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$this->db->select($this->config->item('table_name').'.*, `station_profile`.*, `dxcc_entities`.*, `lotw_users`.*, `satellite.displayname` AS sat_displayname');
		$this->db->join('station_profile', 'station_profile.station_id = ' . $this->config->item('table_name') . '.station_id');
		$this->db->join('dxcc_entities', 'dxcc_entities.adif = ' . $this->config->item('table_name') . '.COL_DXCC', 'left outer');
		$this->db->join('lotw_users', 'lotw_users.callsign = ' . $this->config->item('table_name') . '.col_call', 'left outer');

		if (isset($sat) || strtoupper($band) == 'ALL' || $band == 'SAT') {
			$this->db->join('satellite', 'satellite.name = ' . $this->config->item('table_name') . '.col_sat_name', 'left outer');
		}

		$this->db->where('COL_CALL', $searchphrase);

		$this->db->where_in($this->config->item('table_name') . '.station_id', $logbooks_locations_array);

		if (strtolower($band) != 'all') {
			if ($band != "SAT") {
				$this->db->where('COL_PROP_MODE !=', 'SAT');
				$this->db->where('COL_BAND', $band);
			} else {
				$this->db->where('COL_PROP_MODE', "SAT");
			}
		}

		if ($orbit != 'All') {
			$this->db->where('orbit', $orbit);
		}

		if ($propagation != '') {
			if ($propagation == 'None') {
				$this->db->where('COL_PROP_MODE', '');
			} else if ($propagation == 'NoSAT') {
				$this->db->where('COL_PROP_MODE !=', 'SAT');
			} else {
				$this->db->where('COL_PROP_MODE', $propagation);
			}
		}

		if ($mode != 'All') {
			$this->db->group_start();
			$this->db->where("COL_MODE", $mode);
			$this->db->or_where("COL_SUBMODE", $mode);
			$this->db->group_end();
		}

		if ($sat != 'All') {
			$this->db->where("COL_SAT_NAME", $sat);
		}

		$this->db->order_by("COL_TIME_ON", "desc");
		$this->db->order_by("COL_PRIMARY_KEY", "desc");

		$this->db->limit(500);

		return $this->db->get($this->config->item('table_name'));
	}
}
