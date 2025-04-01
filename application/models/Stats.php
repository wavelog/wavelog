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

	function unique_sat_callsigns($yr = 'All') {
		$qsoView = array();

		$sats = $this->get_sats($yr);
		$modes = $this->get_sat_modes($yr);

		$satunique = $this->getUniqueSatCallsignsSat($yr);
		$modeunique = $this->getUniqueSatCallsignsModes($yr);

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
		$workedQso = $this->getUniqueSatCallsigns($yr);

		foreach ($workedQso as $line) {
			//if ($line->col_submode == null) {
			if ($line->col_submode == null || $line->col_submode == "") {
				$qsoView [$line->sat] [$line->col_mode] = $line->calls;
			} else {
				$qsoView [$line->sat] [$line->col_submode] = $line->calls;
			}
		}

		$result['qsoView'] = $qsoView;
		$result['satunique'] = $satcalls ?? '';
		$result['modeunique'] = $modecalls ?? '';
		$result['total'] = $this->getUniqueSatCallsignsTotal($yr);

		return $result;
	}


	function unique_callsigns($yr = 'All') {
		$qsoView = array();

		$bands = $this->get_bands($yr);
		$modes = $this->get_modes($yr);

		$bandunique = $this->getUniqueCallsignsBands($yr);
		$modeunique = $this->getUniqueCallsignsModes($yr);

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
		$workedQso = $this->getUniqueCallsigns($yr);

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
		$result['total'] = $this->getUniqueCallsignsTotal($yr);

		return $result;
	}

	function getUniqueSatCallsignsSat($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, upper(col_sat_name) as sat', FALSE);
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where('coalesce(col_sat_name,"") != ""');
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('upper(col_sat_name)');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueSatCallsigns($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, upper(col_sat_name) as sat, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where('coalesce(col_sat_name,"") != ""');
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('upper(col_sat_name), col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueCallsigns($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, lower(col_band) as band, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('lower(col_band), col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueCallsignsModes($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueSatCallsignsModes($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueCallsignsBands($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls, col_band as band', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('col_band');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function getUniqueSatCallsignsTotal($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		$this->db->where_in('station_id', $logbooks_locations_array);

		$query = $this->db->get($this->config->item('table_name'));

		return $query->row();
	}

	function getUniqueCallsignsTotal($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
		  return null;
		}

		$bands = array();

		$this->db->select('count(distinct col_call) as calls', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);

		$query = $this->db->get($this->config->item('table_name'));

		return $query->row();
	}

	function total_sat_qsos($yr = 'All') {
		$qsoView = array();

		$sats = $this->get_sats($yr);
		$modes = $this->get_sat_modes($yr);

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
		$workedQso = $this->modeSatQso($yr);
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

	function total_qsos($yr = 'All') {
		$qsoView = array();

		$bands = $this->get_bands($yr);
		$modes = $this->get_modes($yr);

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
		$workedQso = $this->modeBandQso($yr);
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

	function total_qsls($yr = 'All') {
		$qsoView = array();
		$qsoSatView = array();

		$bands = $this->get_bands($yr);
		$modes = $this->get_modes($yr);

		$sats = $this->get_sats($yr);
		$satmodes = $this->get_sat_modes($yr);

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
		$workedQso = $this->modeBandQsl($yr);
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
		$workedSatQso = $this->modeSatQsl($yr);
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

	function modeBandQsl($yr = 'All') {
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
		if ($yr != 'All') {
			$sql.=" and COL_TIME_ON >= ? and COL_TIME_ON <= ? ";
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$binding[]=$syr;
			$binding[]=$eyr;
		}
		$sql.=" and col_prop_mode <> 'SAT'
		group by lower(col_band), col_mode, coalesce(col_submode, '')";

		$result = $this->db->query($sql,$binding);
		return $result->result();
	}

	function modeSatQsl($yr = 'All') {
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
		if ($yr != 'All') {
			$sql.=" and COL_TIME_ON >= ? and COL_TIME_ON <= ? ";
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$binding[]=$syr;
			$binding[]=$eyr;
		}
		$sql.=" and col_prop_mode = 'SAT'
		and coalesce(col_sat_name, '') <> ''
		group by upper(col_sat_name), col_mode, coalesce(col_submode, '')";

		$result = $this->db->query($sql,$binding);
		return $result->result();
	}

	function modeSatQso($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$bands = array();

		$this->db->select('count(1) as count, upper(col_sat_name) as sat, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		$this->db->where('coalesce(col_sat_name,"") != ""');
		$this->db->where('col_prop_mode', 'SAT');
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('upper(col_sat_name), col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function modeBandQso($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$this->db->select('count(*) as count, lower(col_band) as band, col_mode, coalesce(col_submode, "") col_submode', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
		$this->db->where_in('station_id', $logbooks_locations_array);
		$this->db->group_by('lower(col_band), col_mode, coalesce(col_submode, "")');

		$query = $this->db->get($this->config->item('table_name'));

		return $query->result();
	}

	function get_sats($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$sats = array();

		$this->db->select('distinct col_sat_name as satsort, upper(col_sat_name) as sat', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
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

	function get_bands($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$bands = array();

		$this->db->select('distinct col_band+0 as bandsort, lower(col_band) as band', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
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

	function get_sat_modes($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$modes = array();

		$this->db->select('distinct col_mode, coalesce(col_submode, "") col_submode', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
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

	function get_modes($yr = 'All') {
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$modes = array();

		$this->db->select('distinct col_mode, coalesce(col_submode, "") col_submode', FALSE);
		if ($yr != 'All') {
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$this->db->where('COL_TIME_ON >=', $syr);
			$this->db->where('COL_TIME_ON <=', $eyr);
		}
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

	function elevationdata($sat, $orbit, $yr = 'All') {
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

		if ($orbit !== 'All' && $orbit !== '') {
			$conditions[] = "orbit = ?";
			$binding[] = $orbit;
		}

		$where = trim(implode(" AND ", $conditions));
		if ($where != "") {
			$where = "AND $where";
		}

		$sql = "SELECT count(*) qsos, round(COL_ANT_EL) elevation FROM ".$this->config->item('table_name')."
		LEFT JOIN satellite ON satellite.name = ".$this->config->item('table_name').".COL_SAT_NAME
		where station_id in (" . implode(',',$logbooks_locations_array) . ") and coalesce(col_ant_el, '') <> ''";
		if ($yr != 'All') {
			$sql.=" and COL_TIME_ON >= ? and COL_TIME_ON <= ? ";
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$binding[]=$syr;
			$binding[]=$eyr;
		}
		$sql.=" $where
		group by round(col_ant_el)
		order by elevation asc";

		$result = $this->db->query($sql, $binding);
		return $result->result();
	}

	function azimuthdata($band, $mode, $sat, $orbit, $yr = 'All') {
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

		if ($orbit !== 'All' && $orbit !== '') {
			$conditions[] = "orbit = ?";
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
		if ($yr != 'All') {
			$sql.=" and COL_TIME_ON >= ? and COL_TIME_ON <= ? ";
			$syr = date($yr.'-01-01 00:00:00');
			$eyr = date($yr.'-12-31 23:59:59');
			$binding[]=$syr;
			$binding[]=$eyr;
		}
		$sql.=" $where
		group by round(col_ant_az)
		order by azimuth asc";

		$result = $this->db->query($sql, $binding);
		return $result->result();
	}

	public function sat_qsos($sat,$year,$mode) {
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
		if (($year ?? 'All') != 'All') {
			$this->db->where('COL_TIME_ON >=',date($year.'-01-01 00:00:00'));
			$this->db->where('COL_TIME_ON <=',date($year.'-12-31 23:59:59'));
		}
		$this->db->where_in($this->config->item('table_name').'.station_id', $logbooks_locations_array);
		$this->db->order_by("COL_TIME_ON desc, COL_PRIMARY_KEY desc");
		$this->db->limit(500);

		return $this->db->get($this->config->item('table_name'));
	}
}

?>
