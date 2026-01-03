<?php

class adif_data extends CI_Model {

	function export_all_chunked($api_key = null, $from = null, $to = null, $exportLotw = false, $onlyop = null, $offset = 0, $limit = 5000) {
		$this->load->model('logbooks_model');
		if ($api_key != null) {
			$this->load->model('api_model');
			if (strpos($this->api_model->access($api_key), 'r') !== false) {
				$this->api_model->update_last_used($api_key);
				$user_id = $this->api_model->key_userid($api_key);
				$logbooks_locations_array = $this->list_station_locations($user_id);
			}
		} else {
			$this->load->model('stations');
			$logbooks_locations_array = $this->list_station_locations($this->session->userdata('user_id'));
		}

		$this->db->select($this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
		$this->db->order_by("COL_TIME_ON", "ASC");
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		if ($from) {
			$this->db->where("date(".$this->config->item('table_name').".COL_TIME_ON) >= ", $from);
		}
		if ($to) {
			$this->db->where("date(".$this->config->item('table_name').".COL_TIME_ON) <= ",$to);
		}
		if ($onlyop) {
			$this->db->where("upper(".$this->config->item('table_name').".col_operator)",$onlyop);
		}
		if ($exportLotw) {
			$this->db->group_start();
			$this->db->where($this->config->item('table_name').".COL_LOTW_QSL_SENT != 'Y'");
			$this->db->or_where($this->config->item('table_name').".COL_LOTW_QSL_SENT", NULL);
			$this->db->group_end();
		}
		$this->db->where_in('station_profile.station_id', $logbooks_locations_array);
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif');

		// Add chunking
		$this->db->limit($limit, $offset);

		$query = $this->db->get($this->config->item('table_name'));
		return $query;
	}	

	function export_all($api_key = null,$from = null, $to = null, $exportLotw = false, $onlyop = null) {
		$this->load->model('logbooks_model');
		if ($api_key != null) {
			$this->load->model('api_model');
			if (strpos($this->api_model->access($api_key), 'r') !== false) {
				$this->api_model->update_last_used($api_key);
				$user_id = $this->api_model->key_userid($api_key);
				$logbooks_locations_array = $this->list_station_locations($user_id);
			}
		} else {
			$this->load->model('stations');
			$logbooks_locations_array = $this->list_station_locations($this->session->userdata('user_id'));
		}

		$this->db->select($this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
		$this->db->order_by("COL_TIME_ON", "ASC");
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		if ($from) {
			$this->db->where("date(".$this->config->item('table_name').".COL_TIME_ON) >= ", $from);
		}
		if ($to) {
			$this->db->where("date(".$this->config->item('table_name').".COL_TIME_ON) <= ",$to);
		}
		if ($onlyop) {
			$this->db->where("upper(".$this->config->item('table_name').".col_operator)",$onlyop);
		}
		if ($exportLotw) {
			$this->db->group_start();
			$this->db->where($this->config->item('table_name').".COL_LOTW_QSL_SENT != 'Y'");
			$this->db->or_where($this->config->item('table_name').".COL_LOTW_QSL_SENT", NULL);
			$this->db->group_end();
		}
		$this->db->where_in('station_profile.station_id', $logbooks_locations_array);
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif');
		$query = $this->db->get($this->config->item('table_name'));
		return $query;
	}

	function list_station_locations($user_id) {
		$this->db->where('user_id', $user_id);
		$query = $this->db->get('station_profile');

		if ($query->num_rows() == 0) {
			return array();
		}

		$locations_array = array();
		foreach ($query->result() as $row) {
			array_push($locations_array, $row->station_id);
		}

		return $locations_array;
	}

	function export_printrequested($station_id = NULL) {
		$this->load->model('stations');
		$active_station_id = $this->stations->find_active();

		$this->db->select($this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');

		if ($station_id == NULL) {
			$this->db->where($this->config->item('table_name').'.station_id', $active_station_id);
		} else if ($station_id != 'All') {
			$this->db->where($this->config->item('table_name').'.station_id', $station_id);
		}

		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif');
		// always filter user. this ensures that even if the station_id is from another user no inaccesible QSOs will be returned
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$this->db->where_in('COL_QSL_SENT', array('R', 'Q'));
		$this->db->order_by("COL_TIME_ON", "ASC");
		$query = $this->db->get($this->config->item('table_name'));

		return $query;
	}

	function sat_all($onlyop = null) {
		$this->load->model('stations');
		$active_station_id = $this->stations->find_active();

		$this->db->select(''.$this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
		$this->db->from($this->config->item('table_name'));
		$this->db->where($this->config->item('table_name').'.station_id', $active_station_id);
		$this->db->where($this->config->item('table_name').'.COL_PROP_MODE', 'SAT');

		if ($onlyop) {
			$this->db->where("upper(".$this->config->item('table_name').".col_operator)",$onlyop);
		}

		$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "ASC");

		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');

		return $this->db->get();
	}

	function satellte_lotw($onlyop = null) {
		$this->load->model('stations');
		$active_station_id = $this->stations->find_active();

		$this->db->select(''.$this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
		$this->db->from($this->config->item('table_name'));
		$this->db->where($this->config->item('table_name').'.station_id', $active_station_id);
		$this->db->where($this->config->item('table_name').'.COL_PROP_MODE', 'SAT');

		if ($onlyop) {
			$this->db->where("upper(".$this->config->item('table_name').".col_operator)",$onlyop);
		}

		$where = $this->config->item('table_name').".COL_LOTW_QSLRDATE IS NOT NULL";
		$this->db->where($where);

		$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "ASC");


		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');

		return $this->db->get();
	}

	function export_custom_chunked($from, $to, $station_id, $exportLotw = false, $onlyop = null, $offset = 0, $limit = 5000) {
		// Copy export_custom logic but add chunking for station_id > 0
		$this->load->model('Stations');
		if ($station_id == 0) {
			// Use existing chunked export_all for all stations
			return $this->export_all_chunked(null, $from, $to, $exportLotw, $onlyop, $offset, $limit);
		}

		// Check station access
		if (!$this->Stations->check_station_is_accessible($station_id)) {
			return;
		}

		// Build query identical to export_custom but add LIMIT/OFFSET
		$this->db->select(''.$this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
		$this->db->from($this->config->item('table_name'));
		$this->db->where($this->config->item('table_name').'.station_id', $station_id);

		// Apply same filters as export_custom
		if ($from) {
			$this->db->where("date(".$this->config->item('table_name').".COL_TIME_ON) >= ", $from);
		}
		if ($to) {
			$this->db->where("date(".$this->config->item('table_name').".COL_TIME_ON) <= ",$to);
		}
		if ($onlyop) {
			$this->db->where("upper(".$this->config->item('table_name').".col_operator)",$onlyop);
		}
		if ($exportLotw) {
			$this->db->group_start();
			$this->db->where($this->config->item('table_name').".COL_LOTW_QSL_SENT != 'Y'");
			$this->db->or_where($this->config->item('table_name').".COL_LOTW_QSL_SENT", NULL);
			$this->db->group_end();
		}

		$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "ASC");
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');

		// Add chunking
		$this->db->limit($limit, $offset);

		return $this->db->get();
	}


	function export_custom($from, $to, $station_id, $exportLotw = false, $onlyop = null) {
		// be sure that station belongs to user
		$this->load->model('Stations');
		if ($station_id == 0) {
			return $this->export_all($api_key = null,$from, $to, $exportLotw);
		} else {
			if (!$this->Stations->check_station_is_accessible($station_id)) {
				return;
			}

			$this->db->select(''.$this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
			$this->db->from($this->config->item('table_name'));
			$this->db->where($this->config->item('table_name').'.station_id', $station_id);

			// If date is set, we format the date and add it to the where-statement
			if ($from) {
				$this->db->where("date(".$this->config->item('table_name').".COL_TIME_ON) >= ", $from);
			}
			if ($to) {
				$this->db->where("date(".$this->config->item('table_name').".COL_TIME_ON) <= ",$to);
			}
			if ($onlyop) {
				$this->db->where("upper(".$this->config->item('table_name').".col_operator)",$onlyop);
			}
			if ($exportLotw) {
				$this->db->group_start();
				$this->db->where($this->config->item('table_name').".COL_LOTW_QSL_SENT != 'Y'");
				$this->db->or_where($this->config->item('table_name').".COL_LOTW_QSL_SENT", NULL);
				$this->db->group_end();
			}

			$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "ASC");

			$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
			$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');

			return $this->db->get();
		}
	}

	function export_past_id_chunked($station_id, $fetchfromid, $limit, $onlyop = null, $offset = 0, $chunk_size = 5000) {
		// Copy export_past_id logic but add chunking support
		$this->db->select(''.$this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
		$this->db->from($this->config->item('table_name'));
		$this->db->where($this->config->item('table_name').'.station_id', $station_id);
		$this->db->where($this->config->item('table_name').".COL_PRIMARY_KEY > ", $fetchfromid);

		if ($onlyop) {
			$this->db->where("upper(".$this->config->item('table_name').".col_operator)",$onlyop);
		}

		// Add chunking
		$this->db->limit($chunk_size, $offset);

		$this->db->order_by("COL_PRIMARY_KEY", "ASC");
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');

		return $this->db->get();
	}

	function export_lotw($onlyop = null) {
		$this->load->model('stations');
		$active_station_id = $this->stations->find_active();


		$this->db->select(''.$this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
		$this->db->from($this->config->item('table_name'));
		$this->db->where($this->config->item('table_name').'.station_id', $active_station_id);
		if ($onlyop) {
			$this->db->where("upper(".$this->config->item('table_name').".col_operator)",$onlyop);
		}
		$this->db->group_start();
		$this->db->where($this->config->item('table_name').".COL_LOTW_QSL_SENT != 'Y'");
		$this->db->or_where($this->config->item('table_name').".COL_LOTW_QSL_SENT", NULL);
		$this->db->group_end();

		$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "ASC");

		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');

		return $this->db->get();
	}

	function sig_all($type) {
		$CI =& get_instance();
		$CI->load->model('logbooks_model');
		$logbooks_locations_array = $CI->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$this->db->select(''.$this->config->item('table_name').'.*, station_profile.*, dxcc_entities.name as station_country');
		$this->db->from($this->config->item('table_name'));
		$this->db->where_in($this->config->item('table_name').'.station_id', $logbooks_locations_array);
		$this->db->where($this->config->item('table_name').'.COL_SIG', $type);

		$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "ASC");

		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');

		return $this->db->get();
	}
}

?>
