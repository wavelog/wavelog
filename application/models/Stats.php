<?php

	class Stats extends CI_Model {

	function result() {
		$this->db->select('COL_CALL, COL_BAND, COL_TIME_ON, COL_RST_RCVD, COL_RST_SENT, COL_MODE, COL_NAME, COL_COUNTRY, COL_PRIMARY_KEY, COL_SAT_NAME');

		$this->db->where('COL_TIME_ON >=', $this->input->post('start_date'));
		$this->db->where('COL_TIME_OFF <=', $this->input->post('end_date'));

		if($this->input->post('band_6m') == "6m") {
			$this->db->where('COL_BAND', $this->input->post('band_6m'));
		}

		if($this->input->post('band_2m') == "2m") {
			$this->db->where('COL_BAND', $this->input->post('band_2m'));
		}

		if($this->input->post('band_70cm') == "70cm") {
			$this->db->where('COL_BAND', $this->input->post('band_70cm'));
		}

		if($this->input->post('band_23cm') == "23cm") {
			$this->db->where('COL_BAND', $this->input->post('band_23cm'));
		}

		if($this->input->post('band_3cm') == "3cm") {
			$this->db->where('COL_BAND', $this->input->post('band_3cm'));
		}

		// Select Voice QSOs
		if($this->input->post('mode_data') == "data") {
			if($this->input->post('mode_ssb') != "ssb") {
				$this->db->where('COL_MODE !=', 'SSB');
				$this->db->where('COL_MODE !=', 'LSB');
				$this->db->where('COL_MODE !=', 'USB');
			}
			if($this->input->post('mode_cw') != "cw") {
				$this->db->where('COL_MODE !=', 'CW');
			}
			if($this->input->post('mode_fm') != "fm") {
				$this->db->where('COL_MODE !=', 'FM');
			}
			if($this->input->post('mode_am') != "am") {
				$this->db->where('COL_MODE !=', 'AM');
			}
		}

		// Select Voice QSOs
		if($this->input->post('mode_ssb') == "ssb") {
			$this->db->where('COL_MODE', $this->input->post('mode_ssb'));
			$this->db->or_where('COL_MODE', 'USB');
			$this->db->or_where('COL_MODE', 'LSB');
		}

		// Select CW QSOs
		if($this->input->post('mode_cw') == "cw") {
			$this->db->where('COL_MODE', $this->input->post('mode_ssb'));
		}

		// Select FM QSOs
		if($this->input->post('mode_fm') == "fm") {
			$this->db->where('COL_MODE', $this->input->post('mode_ssb'));
		}

		// Select AM QSOs
		if($this->input->post('mode_am') == "am") {
			$this->db->where('COL_MODE', $this->input->post('mode_am'));
		}

		return $this->db->get($this->config->item('table_name'));
	}

	// Helper method for date range filtering
	private function filter_date_range($dateFrom, $dateTo) {
		if (!empty($dateFrom)) {
			$this->db->where('DATE(COL_TIME_ON) >=', $dateFrom);
		}
		if (!empty($dateTo)) {
			$this->db->where('DATE(COL_TIME_ON) <=', $dateTo);
		}
	}

	function unique_sat_grids($dateFrom = null, $dateTo = null) {
		$qsoView = array();

		$sats = $this->get_sats($dateFrom, $dateTo);
		$modes = $this->get_sat_modes($dateFrom, $dateTo);

		$satunique = $this->getUniqueSatGridsSat($dateFrom, $dateTo);
		$modeunique = $this->getUniqueSatGridModes($dateFrom, $dateTo);

		// Generating the band/mode table
		foreach ($sats as $sat) {
			$sattotal[$sat] = 0;
			foreach ($modes as $mode) {
				$qsoView [$sat][$mode] = '-';
			}
		}

		foreach ($satunique as $sat) {
			$satgrids[$sat->sat] = $sat->grids;
		}

		foreach ($modeunique as $mode) {
			//if ($mode->col_submode == null) {
			if ($mode->col_submode == null || $mode->col_submode == "") {
				$modegrids[$mode->col_mode] = $mode->grids;
			} else {
				$modegrids[$mode->col_submode] = $mode->grids;
			}
		}

		// Populating array with worked
		$workedQso = $this->getUniqueSatGrids($dateFrom, $dateTo);

		foreach ($workedQso as $line) {
			//if ($line->col_submode == null) {
			if ($line->col_submode == null || $line->col_submode == "") {
				$qsoView [$line->sat] [$line->col_mode] = $line->grids;
			} else {
				$qsoView [$line->sat] [$line->col_submode] = $line->grids;
			}
		}

		$result['qsoView'] = $qsoView;
		$result['satunique'] = $satgrids ?? '';
		$result['modeunique'] = $modegrids ?? '';
		$result['total'] = $this->getUniqueSatGridsTotal($dateFrom, $dateTo);

		return $result;
	}

	function unique_sat_callsigns($dateFrom = null, $dateTo = null) {
		$qsoView = array();

		$sats = $this->get_sats($dateFrom, $dateTo);
		$modes = $this->get_sat_modes($dateFrom, $dateTo);

		$satunique = $this->getUniqueSatCallsignsSat($dateFrom, $dateTo);
		$modeunique = $this->getUniqueSatCallsignsModes($dateFrom, $dateTo);

		// Generating the band/mode table
		foreach ($sats as $sat) {
			$sattotal[$sat] = 0;
			foreach ($modes as $mode) {
				$qsoView [$sat][$mode] = '-';
			}
		}

		foreach ($satunique as $sat) {
			$satcalls[$sat->sat] = $sat->calls;
		}

		foreach ($modeunique as $mode) {
			//if ($mode->col_submode == null) {
			if ($mode->col_submode == null || $mode->col_submode == "") {
				$modecalls[$mode->col_mode] = $mode->calls;
			} else {
				$modecalls[$mode->col_submode] = $mode->calls;
			}
		}

		// Populating array with worked
		$workedQso = $this->getUniqueSatCallsigns($dateFrom, $dateTo);

		foreach ($workedQso as $line) {
			//if ($line->col_submode == null) {
			if ($line->col_submode == null || $line->col_submode == "") {
				$qsoView [$line->sat] [$line->col_mode] = $line->calls;
			} else {
				$qsoView [$line->sat] [$line->col_submode] = $line->calls;
			}
		}

		$result['qsoView'] = $qsoView;
		$result['satunique'] = $satcalls;
		$result['modeunique'] = $modecalls;
		$result['total'] = $this->getUniqueSatCallsignsTotal($dateFrom, $dateTo);

		return $result;
	}

	function unique_callsigns($dateFrom = null, $dateTo = null) {
		$qsoView = array();

		$bands = $this->get_bands($dateFrom, $dateTo);
		$modes = $this->get_modes($dateFrom, $dateTo);

		$bandunique = $this->getUniqueCallsignsBands($dateFrom, $dateTo);
		$modeunique = $this->getUniqueCallsignsModes($dateFrom, $dateTo);

		$modecalls=[];
		$bandcalls=[];

		// Generating the band/mode table
		foreach ($bands as $band) {
			$bandtotal[$band] = 0;
			foreach ($modes as $mode) {
				$qsoView [$mode][$band] = '-';
			}
		}

		foreach ($bandunique as $band) {
			$bandcalls[$band->band] = $band->calls;
		}

		foreach ($modeunique as $mode) {
			//if ($mode->col_submode == null) {
			if ($mode->col_submode == null || $mode->col_submode == "") {
				$modecalls[$mode->col_mode] = $mode->calls;
			} else {
				$modecalls[$mode->col_submode] = $mode->calls;
			}
		}

		// Populating array with worked
		$workedQso = $this->getUniqueCallsigns($dateFrom, $dateTo);

		foreach ($workedQso as $line) {
			//if ($line->col_submode == null) {
			if ($line->col_submode == null || $line->col_submode == "") {
				$qsoView [$line->col_mode]  [$line->band] = $line->calls;
			} else {
				$qsoView [$line->col_submode]  [$line->band] = $line->calls;
			}
		}

		$result['qsoView'] = $qsoView;
		$result['bandunique'] = $bandcalls;
		$result['modeunique'] = $modecalls;
		$result['total'] = $this->getUniqueCallsignsTotal($dateFrom, $dateTo);

		return $result;
	}

	function getUniqueSatGridsSat($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		// Select required columns without aggregation
		$this->db->select('distinct col_gridsquare, col_vucc_grids, upper(col_sat_name) as sat', FALSE);
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where('coalesce(col_sat_name,"") != ""');

		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);

		$query = $this->db->get($this->config->item('table_name'));
		$rows = $query->result();

		// Prepare result array: sat => unique grids set
		$satGrids = [];

		foreach ($rows as $row) {
			$sat = $row->sat;

			if (!isset($satGrids[$sat])) {
				$satGrids[$sat] = [];
			}

			// Process col_gridsquare
			if (!empty($row->col_gridsquare)) {
				$grid = strtoupper(substr(trim($row->col_gridsquare), 0, 4));
				if ($grid !== '') {
					$satGrids[$sat][$grid] = true;
				}
			}

			// Process col_vucc_grids: comma-separated
			if (!empty($row->col_vucc_grids)) {
				$vuccParts = explode(',', $row->col_vucc_grids);
				foreach ($vuccParts as $part) {
					$grid = strtoupper(substr(trim($part), 0, 4));
					if ($grid !== '') {
						$satGrids[$sat][$grid] = true;
					}
				}
			}
		}

		// Now convert to result array like your original query result format
		$result = [];
		foreach ($satGrids as $sat => $grids) {
			$result[] = (object)[
				'sat'   => $sat,
				'grids' => count($grids),
			];
		}

		return $result;
	}

	function getUniqueSatCallsignsSat($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, upper(col_sat_name) as sat', FALSE);
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('upper(col_sat_name)');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}


	function getUniqueSatGrids($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$this->db->distinct();
		$this->db->select([
			'col_gridsquare',
			'col_vucc_grids',
			'upper(col_sat_name) AS sat',
			'col_mode',
			'coalesce(col_submode, "") AS col_submode'
		], FALSE);
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);

		$query = $this->db->get($this->config->item('table_name'));
		$rows = $query->result();

		$comboGrids = [];

		foreach($rows as $row) {
			$key = $row->sat.'|'.$row->col_mode.'|'.$row->col_submode;
			if(!isset($comboGrids[$key])) {
				$comboGrids[$key] = [];
			}

			if(!empty($row->col_gridsquare)) {
				$grid = strtoupper(substr(trim($row->col_gridsquare), 0, 4));
				if($grid) $comboGrids[$key][$grid] = true;
			}

			if(!empty($row->col_vucc_grids)) {
				$grids = explode(',', $row->col_vucc_grids);
				foreach($grids as $vuccgrid) {
					$grid = strtoupper(substr(trim($vuccgrid), 0, 4));
					if($grid) $comboGrids[$key][$grid] = true;
				}
			}
		}

		$result = [];
		foreach($comboGrids as $key => $gridSet) {
			list($sat, $mode, $submode) = explode('|', $key, 3);
			$result[] = (object) [
				'sat'        => $sat,
				'grids'      => count($gridSet),
				'col_mode'   => $mode,
				'col_submode'=> $submode,
			];
		}

		return $result;
	}


	function getUniqueSatCallsigns($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, upper(col_sat_name) as sat, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('upper(col_sat_name), col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueCallsigns($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, lower(col_band) as band, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('lower(col_band), col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueCallsignsModes($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueSatGridModes($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct substr(col_gridsquare,1,4)) as grids, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueSatCallsignsModes($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueCallsignsBands($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, col_band as band', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('col_band');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueSatGridsTotal($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$this->db->select('distinct col_gridsquare, col_vucc_grids', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where_in('station_id', $logbooks_locations_array);

		$query = $this->db->get($this->config->item('table_name'));
		$rows = $query->result();

		$uniqueGrids = [];

		foreach ($rows as $row) {
			if (!empty($row->col_gridsquare)) {
				$grid = strtoupper(substr(trim($row->col_gridsquare), 0, 4));
				if ($grid !== '') {
					$uniqueGrids[$grid] = true;
				}
			}
			if (!empty($row->col_vucc_grids)) {
				$grids = explode(',', $row->col_vucc_grids);
				foreach ($grids as $g) {
					$grid = strtoupper(substr(trim($g), 0, 4));
					if ($grid !== '') {
						$uniqueGrids[$grid] = true;
					}
				}
			}
		}
		return (object) ['grids' => count($uniqueGrids)];
	}


	function getUniqueSatCallsignsTotal($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where_in('station_id', $logbooks_locations_array);

		$query = $this->db->get($this->config->item('table_name'));

		return $query->row();
	}

	function getUniqueCallsignsTotal($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);

		$query = $this->db->get($this->config->item('table_name'));

		return $query->row();
	}

	function total_sat_qsos($dateFrom = null, $dateTo = null) {
		$qsoView = array();

		$sats = $this->get_sats($dateFrom, $dateTo);
		$modes = $this->get_sat_modes($dateFrom, $dateTo);

		$sattotal = array();
		$modetotal = array();
		// Generating the band/mode table
		foreach ($sats as $sat) {
			$sattotal[$sat] = 0;
			foreach ($modes as $mode) {
				$qsoView [$sat][$mode] = '-';
				$modetotal[$mode] = 0;
			}
		}

		// Populating array with worked
		$workedQso = $this->modeSatQso($dateFrom, $dateTo);
		foreach ($workedQso as $line) {
			if ($line->col_submode == null || $line->col_submode == "") {
				$qsoView [$line->sat] [$line->col_mode] = $line->count;
				$modetotal[$line->col_mode] += $line->count;
			} else {
				$qsoView [$line->sat] [$line->col_submode] = $line->count;
				$modetotal[$line->col_submode] += $line->count;
			}
			$sattotal[$line->sat] += $line->count;
		}

		$result['qsoView'] = $qsoView;
		$result['sattotal'] = $sattotal;
		$result['modetotal'] = $modetotal;
		$result['modes'] = $modes;

		return $result;
	}

	function total_qsos($dateFrom = null, $dateTo = null) {
		$qsoView = array();

		$bands = $this->get_bands($dateFrom, $dateTo);
		$modes = $this->get_modes($dateFrom, $dateTo);

		$bandtotal = array();
		$modetotal = array();
		// Generating the band/mode table
		foreach ($bands as $band) {
			$bandtotal[$band] = 0;
			foreach ($modes as $mode) {
				$qsoView [$mode][$band] = '-';
				$modetotal[$mode] = 0;
			}
		}

		// Populating array with worked
		$workedQso = $this->modeBandQso($dateFrom, $dateTo);
		foreach ($workedQso as $line) {
			if ($line->col_submode == null || $line->col_submode == "") {
				$qsoView [$line->col_mode]  [$line->band] = $line->count;
				$modetotal[$line->col_mode] += $line->count;
			} else {
				$qsoView [$line->col_submode]  [$line->band] = $line->count;
				$modetotal[$line->col_submode] += $line->count;
			}
			$bandtotal[$line->band] += $line->count;
		}

		$result['qsoView'] = $qsoView;
		$result['bandtotal'] = $bandtotal;
		$result['modetotal'] = $modetotal;

		return $result;
	}

	function total_qsls($dateFrom = null, $dateTo = null) {
		$qsoView = array();
		$qsoSatView = array();

		$bands = $this->get_bands($dateFrom, $dateTo);
		$modes = $this->get_modes($dateFrom, $dateTo);

		$sats = $this->get_sats($dateFrom, $dateTo);
		$satmodes = $this->get_sat_modes($dateFrom, $dateTo);

		// Generating the band/mode table
		foreach ($bands as $band) {
			foreach ($modes as $mode) {
				$qsoView [$mode][$band]['qso'] = '0';
				$qsoView [$mode][$band]['qsl'] = '0';
				$qsoView [$mode][$band]['lotw'] = '0';
				$qsoView [$mode][$band]['qrz'] = '0';
				$qsoView [$mode][$band]['eqsl'] = '0';
				$qsoView [$mode][$band]['clublog'] = '0';
			}
		}

		// Populating array with numbers
		$workedQso = $this->modeBandQsl($dateFrom, $dateTo);
		foreach ($workedQso as $line) {
			if ($line->col_submode == null || $line->col_submode == "") {
				$qsoView [$line->col_mode]  [$line->band] ['qso'] = $line->qsos;
				$qsoView [$line->col_mode]  [$line->band] ['qsl'] = $line->qsl;
				$qsoView [$line->col_mode]  [$line->band] ['lotw'] = $line->lotw;
				$qsoView [$line->col_mode]  [$line->band] ['qrz'] = $line->qrz;
				$qsoView [$line->col_mode]  [$line->band] ['eqsl'] = $line->eqsl;
				$qsoView [$line->col_mode]  [$line->band] ['clublog'] = $line->clublog;
			} else {
				$qsoView [$line->col_submode]  [$line->band] ['qso'] = $line->qsos;
				$qsoView [$line->col_submode]  [$line->band] ['qsl'] = $line->qsl;
				$qsoView [$line->col_submode]  [$line->band] ['lotw'] = $line->lotw;
				$qsoView [$line->col_submode]  [$line->band] ['qrz'] = $line->qrz;
				$qsoView [$line->col_submode]  [$line->band] ['eqsl'] = $line->eqsl;
				$qsoView [$line->col_submode]  [$line->band] ['clublog'] = $line->clublog;
			}
		}

		// Generating the band/mode table
		foreach ($sats as $sat) {
			foreach ($satmodes as $mode) {
				$qsoSatView [$mode][$sat]['qso'] = '0';
				$qsoSatView [$mode][$sat]['qsl'] = '0';
				$qsoSatView [$mode][$sat]['lotw'] = '0';
				$qsoSatView [$mode][$sat]['qrz'] = '0';
				$qsoSatView [$mode][$sat]['eqsl'] = '0';
				$qsoSatView [$mode][$sat]['clublog'] = '0';
			}
		}

		// Populating array with numbers
		$workedSatQso = $this->modeSatQsl($dateFrom, $dateTo);
		foreach ($workedSatQso as $line) {
			if ($line->col_submode == null || $line->col_submode == "") {
				$qsoSatView [$line->col_mode]  [$line->sat] ['qso'] = $line->qsos;
				$qsoSatView [$line->col_mode]  [$line->sat] ['qsl'] = $line->qsl;
				$qsoSatView [$line->col_mode]  [$line->sat] ['lotw'] = $line->lotw;
				$qsoSatView [$line->col_mode]  [$line->sat] ['qrz'] = $line->qrz;
				$qsoSatView [$line->col_mode]  [$line->sat] ['eqsl'] = $line->eqsl;
				$qsoSatView [$line->col_mode]  [$line->sat] ['clublog'] = $line->clublog;
			} else {
				$qsoSatView [$line->col_submode]  [$line->sat] ['qso'] = $line->qsos;
				$qsoSatView [$line->col_submode]  [$line->sat] ['qsl'] = $line->qsl;
				$qsoSatView [$line->col_submode]  [$line->sat] ['lotw'] = $line->lotw;
				$qsoSatView [$line->col_submode]  [$line->sat] ['qrz'] = $line->qrz;
				$qsoSatView [$line->col_submode]  [$line->sat] ['eqsl'] = $line->eqsl;
				$qsoSatView [$line->col_submode]  [$line->sat] ['clublog'] = $line->clublog;
			}
		}

		$result['qsoView'] = $qsoView;
		$result['qsoSatView'] = $qsoSatView;

		return $result;
	}

	function modeBandQsl($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$binding=[];
		if (!$logbooks_locations_array) {
			return null;
		}

		$sql = "select lower(col_band) as band, col_mode, coalesce(col_submode, '') col_submode,
				count(1) qsos,
				count(case when COL_QSL_RCVD='Y' then 1 end) qsl,
				count(case when COL_EQSL_QSL_RCVD='Y' then 1 end) eqsl,
				count(case when COL_LOTW_QSL_RCVD='Y' then 1 end) lotw,
				count(case when COL_QRZCOM_QSO_DOWNLOAD_STATUS='Y' then 1 end) qrz,
				count(case when COL_CLUBLOG_QSO_DOWNLOAD_STATUS='Y' then 1 end) clublog
		from " . $this->config->item('table_name') . "
		where station_id in (". implode(',', $logbooks_locations_array) .")";
		if (!empty($dateFrom)) {
			$sql.=" and DATE(COL_TIME_ON) >= ? ";
			$binding[]=$dateFrom;
		}
		if (!empty($dateTo)) {
			$sql.=" and DATE(COL_TIME_ON) <= ? ";
			$binding[]=$dateTo;
		}
		$sql.=" and col_prop_mode <> 'SAT'
		group by lower(col_band), col_mode, coalesce(col_submode, '')";

		$result = $this->db->query($sql,$binding);
		return $result->result();
	}

	function modeSatQsl($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$binding=[];
		if (!$logbooks_locations_array) {
			return null;
		}

		$sql = "select upper(col_sat_name) as sat, col_mode, coalesce(col_submode, '') col_submode,
				count(1) qsos,
				count(case when COL_QSL_RCVD='Y' then 1 end) qsl,
				count(case when COL_EQSL_QSL_RCVD='Y' then 1 end) eqsl,
				count(case when COL_LOTW_QSL_RCVD='Y' then 1 end) lotw,
				count(case when COL_QRZCOM_QSO_DOWNLOAD_STATUS='Y' then 1 end) qrz,
				count(case when COL_CLUBLOG_QSO_DOWNLOAD_STATUS='Y' then 1 end) clublog
		from " . $this->config->item('table_name') . "
		where station_id in (". implode(',', $logbooks_locations_array) .")";
		if (!empty($dateFrom)) {
			$sql.=" and DATE(COL_TIME_ON) >= ? ";
			$binding[]=$dateFrom;
		}
		if (!empty($dateTo)) {
			$sql.=" and DATE(COL_TIME_ON) <= ? ";
			$binding[]=$dateTo;
		}
		$sql.=" and col_prop_mode = 'SAT'
		and coalesce(col_sat_name, '') <> ''
		group by upper(col_sat_name), col_mode, coalesce(col_submode, '')";

		$result = $this->db->query($sql,$binding);
		return $result->result();
	}

	function modeSatQso($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$bands = array();

		$this->db->select('count(1) as count, upper(col_sat_name) as sat, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('upper(col_sat_name), col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function modeBandQso($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$this->db->select('count(*) as count, lower(col_band) as band, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('lower(col_band), col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function get_sats($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$sats = array();

		$this->db->select('distinct col_sat_name as satsort, upper(col_sat_name) as sat', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->order_by('satsort', 'asc');

		$query = $this->db->get($this->config->item('table_name'));

		foreach($query->result() as $sat){
			array_push($sats, $sat->sat);
		}
		return $sats;
	}

	function get_bands($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$bands = array();

		$this->db->select('distinct col_band+0 as bandsort, lower(col_band) as band', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->order_by('bandsort', 'desc');

		$query = $this->db->get($this->config->item('table_name'));

		foreach($query->result() as $band){
			array_push($bands, $band->band);
		}

		usort(
			$bands,
			function($b, $a) {
				sscanf($a, '%f%s', $ac, $ar);
				sscanf($b, '%f%s', $bc, $br);
				if ($ar == $br) {
					return ($ac < $bc) ? -1 : 1;
				}
				return ($ar < $br) ? -1 : 1;
			}
		);

		return $bands;
	}

	function get_sat_modes($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$modes = array();

		$this->db->select('distinct col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where_in('station_id', $logbooks_locations_array);
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

	function get_modes($dateFrom = null, $dateTo = null) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$modes = array();

		$this->db->select('distinct col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in('station_id', $logbooks_locations_array);
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

	function elevationdata($sat, $orbit, $dateFrom = null, $dateTo = null) {
		$conditions = [];
		$binding = [];

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$conditions[] = "COL_PROP_MODE = 'SAT'";

		if($sat != "All") {
			$conditions[] = "COL_SAT_NAME = ? ";
			$binding[] = trim($sat);
		}

		if ($orbit !== '') {
			$conditions[] = "orbit in ?";
			$binding[] = $orbit;
		}

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}

		$sql = "SELECT count(*) qsos, round(COL_ANT_EL) elevation FROM ".$this->config->item('table_name')."
		LEFT JOIN satellite ON satellite.name = ".$this->config->item('table_name').".COL_SAT_NAME
		where station_id in (" . implode(',',$logbooks_locations_array) . ") and coalesce(col_ant_el, '') <> ''";
		if (!empty($dateFrom)) {
			$sql.=" and DATE(COL_TIME_ON) >= ? ";
			$binding[]=$dateFrom;
		}
		if (!empty($dateTo)) {
			$sql.=" and DATE(COL_TIME_ON) <= ? ";
			$binding[]=$dateTo;
		}
		$sql.=" $where
		group by round(col_ant_el)
		order by elevation asc";

		$result = $this->db->query($sql, $binding);
		return $result->result();
	}

	function azimuthdata($band, $mode, $sat, $orbit, $dateFrom = null, $dateTo = null) {
		$conditions = [];
		$binding = [];

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		if ($band !== 'All') {
			if($band != "SAT") {
				$conditions[] = "COL_BAND = ? and COL_PROP_MODE != 'SAT'";
				$binding[] = trim($band);
			} else {
				$conditions[] = "COL_PROP_MODE = 'SAT'";
				if ($sat !== 'All') {
					$conditions[] = "COL_SAT_NAME = ?";
					$binding[] = trim($sat);
				}
			}
		}

		if ($mode !== 'All') {
			$conditions[] = "(COL_MODE = ? or COL_SUBMODE = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		if ($orbit !== '') {
			$conditions[] = "orbit in ?";
			$binding[] = $orbit;
		}

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}

		$sql = "SELECT count(*) qsos, round(COL_ANT_AZ) azimuth
		FROM ".$this->config->item('table_name')."
		LEFT JOIN satellite ON satellite.name = ".$this->config->item('table_name').".COL_SAT_NAME
		where station_id in (" . implode(',',$logbooks_locations_array) . ")
		and coalesce(col_ant_az, '') <> ''";
		if (!empty($dateFrom)) {
			$sql.=" and DATE(COL_TIME_ON) >= ? ";
			$binding[]=$dateFrom;
		}
		if (!empty($dateTo)) {
			$sql.=" and DATE(COL_TIME_ON) <= ? ";
			$binding[]=$dateTo;
		}
		$sql.=" $where
		group by round(col_ant_az)
		order by azimuth asc";

		$result = $this->db->query($sql, $binding);
		return $result->result();
	}

	public function sat_qsos($sat,$dateFrom,$dateTo,$mode) {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->db->select('*, satellite.displayname AS sat_displayname');
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('satellite', 'satellite.name = '.$this->config->item('table_name').'.COL_SAT_NAME');
		$this->db->join('dxcc_entities', $this->config->item('table_name') . '.col_dxcc = dxcc_entities.adif', 'left outer');
		$this->db->where('COL_SAT_NAME', $sat);
		if (($mode ?? '') != '') {
			$this->db->group_start();
			$this->db->where('COL_MODE', $mode);
			$this->db->or_where('COL_SUBMODE', $mode);
			$this->db->group_end();
		}
		// Apply date range filter
		$this->filter_date_range($dateFrom, $dateTo);
		$this->db->where_in($this->config->item('table_name').'.station_id', $logbooks_locations_array);
		$this->db->order_by("COL_TIME_ON desc, COL_PRIMARY_KEY desc");
		$this->db->limit(500);

		return $this->db->get($this->config->item('table_name'));
	}

	public function getInitialsFromDb($band, $mode) {
		$binding = [];

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$sql = "select thcv.col_call, thcv.col_time_on, thcv.col_band, thcv.col_mode, thcv.col_submode, thcv.col_primary_key, thcv.col_vucc_grids, thcv.col_gridsquare, thcv.col_distance, thcv.col_state FROM ". $this->config->item('table_name') . " thcv";

		$sql .= " join (SELECT col_call, min(col_time_on) firstworked, col_band, min(col_primary_key) qsoid FROM ".$this->config->item('table_name');

		$sql .= " where station_id in (" . implode(',',$logbooks_locations_array) . ") and col_prop_mode ='EME'";

		if ($mode != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		if ($band != 'All') {
			$sql .= " and col_band = ?";
			$binding[] = $band;
		}

		$sql .= " group by col_call, col_band order by firstworked) x on thcv.col_primary_key = x.qsoid";

		$result = $this->db->query($sql, $binding);

		return $result->result();
	}

		public function getInitialsFromDb2($band, $mode) {
		$binding = [];

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$sql = "SELECT col_call, min(col_time_on) firstworked, col_band, min(col_primary_key) qsoid FROM ".$this->config->item('table_name');

		$sql .= " where station_id in (" . implode(',',$logbooks_locations_array) . ") and col_prop_mode ='EME'";

		if ($mode != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$binding[] = $mode;
			$binding[] = $mode;
		}

		if ($band != 'All') {
			$sql .= " and col_band = ?";
			$binding[] = $band;
		}

		$sql .= " group by col_call, col_band order by firstworked";

		$result = $this->db->query($sql, $binding);

		return $result->result();
	}

	function get_eme_modes() {

		$modes = array();

		$this->db->select('distinct col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$this->db->where($this->config->item('table_name').'.col_prop_mode', 'EME');
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

}

?>
