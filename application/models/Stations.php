<?php

class Stations extends CI_Model {

	public function __construct() {
	}

	// Returns ALL station profiles regardless of user logged in
	// This is also used by LoTW sync so must not be changed.
	function all() {
		$this->db->select('station_profile.*, dxcc_entities.name as station_country');
		$this->db->from('station_profile');
		$this->db->join('dxcc_entities','station_profile.station_dxcc = dxcc_entities.adif','left outer');
		return $this->db->get();
	}

	function all_of_user($userid = null) {
		if ($userid == null) {
			$userid=$this->session->userdata('user_id'); // Fallback to session-uid, if userid is omitted
		}
		$this->db->select('station_profile.*, dxcc_entities.name as station_country, dxcc_entities.end as dxcc_end');
		$this->db->where('user_id', $userid);
		$this->db->join('dxcc_entities','station_profile.station_dxcc = dxcc_entities.adif','left outer');
		$this->db->order_by('station_profile.station_callsign asc, station_profile.station_profile_name asc');
		return $this->db->get('station_profile');
	}

	function all_station_ids_of_user($userid = null) {
		if ($userid == null) {
			$userid=$this->session->userdata('user_id'); // Fallback to session-uid, if userid is omitted
		}
		$this->db->select('station_profile.station_id');
		$this->db->where('user_id', $userid);
		$query=$this->db->get('station_profile');
		$a_station_ids = array();
		if ($query->num_rows() > 0){
			foreach ($query->result() as $row) {
				array_push($a_station_ids, $row->station_id);
			}
			$station_ids=implode(', ', $a_station_ids);
			return $station_ids;
		} else {
			return '';
		}
	}

	function callsigns_of_user($userid = null) {
		if ($userid == null) {
			$userid=$this->session->userdata('user_id'); // Fallback to session-uid, if userid is omitted
		}
		$this->db->select('distinct(station_profile.station_callsign) as callsign');
		$this->db->where('user_id', $userid);
		return $this->db->get('station_profile');
	}

	function profile($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);
		$this->db->where('station_id', $clean_id);
		return $this->db->get('station_profile');
	}

	function profile_full($id) {
		// Should refactor all calls for profile to profile_full, since it contains also those eqsl-default-msg
		$clean_id = $this->security->xss_clean($id);
		$this->db->where('station_id', $clean_id);
		$row=$this->db->get('station_profile')->row();

		$options_object = $this->user_options_model->get_options('eqsl_default_qslmsg', array('option_name' => 'key_station_id', 'option_key' => $id))->result();
		$row->eqsl_default_qslmsg = $options_object[0]->option_value ?? '';
		return $row;
	}

	function profile_clean($id) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);


		$this->db->where('station_id', $clean_id);
		$query = $this->db->get('station_profile');

		$row = $query->row();

		return $row;
	}

	/*
	*	Function: add
	*	Adds post material into the station profile table.
	*/
	function add() {
		// check if user has no active station profile yet
		$station_active = null;
		if ($this->find_active() === '0') {
			$station_active = 1;
		}

		// Check if the state is Canada and get the correct state
		if ($this->input->post('dxcc') == 1 && $this->input->post('station_ca_state') !="") {
			$state = xss_clean($this->input->post('station_ca_state', true));
		} else {
			$state = xss_clean($this->input->post('station_state', true));
		}

		// Check if DXCC is USA, Alaska or Hawaii, RU, UR, and others with subareas.
		// If not true, we clear the County field due to complex adif specs
		switch ($this->input->post('dxcc')) {
			case 6:
			case 110:
			case 291:
			case 15:
			case 54:
			case 61:
			case 126:
			case 151:
			case 288:
			case 339:
			case 170:
			case 21:
			case 29:
			case 32:
			case 281:
				$county = xss_clean($this->input->post('station_cnty', true));
				break;
			default:
				$county = '';
		}

		// Create data array with field values
		$data = array(
			'user_id' => $this->session->userdata('user_id'),
			'station_active' => $station_active,
			'station_profile_name' => xss_clean($this->input->post('station_profile_name', true)),
			'station_gridsquare' =>  xss_clean(strtoupper($this->input->post('gridsquare', true))),
			'station_city' =>  xss_clean($this->input->post('city', true)),
			'station_iota' =>  xss_clean(strtoupper($this->input->post('iota', true))),
			'station_sota' =>  xss_clean(strtoupper($this->input->post('sota', true))),
			'station_wwff' =>  xss_clean(strtoupper($this->input->post('wwff', true))),
			'station_pota' =>  xss_clean(strtoupper($this->input->post('pota', true))),
			'station_sig' =>  xss_clean(strtoupper($this->input->post('sig', true))),
			'station_sig_info' =>  xss_clean(strtoupper($this->input->post('sig_info', true))),
			'station_callsign' =>  trim(xss_clean(strtoupper($this->input->post('station_callsign', true)))),
			'station_power' => is_numeric(xss_clean($this->input->post('station_power', true))) ? xss_clean($this->input->post('station_power', true)) : NULL,
			'station_dxcc' =>  xss_clean($this->input->post('dxcc', true)),
			'station_cnty' =>  $county,
			'station_cq' =>  xss_clean($this->input->post('station_cq', true)),
			'station_itu' =>  xss_clean($this->input->post('station_itu', true)),
			'state' =>  $state,
			'eqslqthnickname' => xss_clean($this->input->post('eqslnickname', true)),
			'hrdlog_username' => xss_clean($this->input->post('hrdlog_username', true)),
			'hrdlog_code' => xss_clean($this->input->post('hrdlog_code', true)),
			'hrdlogrealtime' => xss_clean($this->input->post('hrdlogrealtime', true)),
			'clublogignore' => xss_clean($this->input->post('clublogignore', true)),
			'clublogrealtime' => xss_clean($this->input->post('clublogrealtime', true)),
			'qrzapikey' => xss_clean($this->input->post('qrzapikey', true)),
			'qrzrealtime' => xss_clean($this->input->post('qrzrealtime', true)),
			'oqrs' => xss_clean($this->input->post('oqrs', true) ?? '0'),
			'oqrs_email' => xss_clean($this->input->post('oqrsemail', true) ?? '0'),
			'oqrs_text' => xss_clean($this->input->post('oqrstext', true)),
			'webadifapikey' => xss_clean($this->input->post('webadifapikey', true)),
			'webadifapiurl' => 'https://qo100dx.club/api',
			'webadifrealtime' => xss_clean($this->input->post('webadifrealtime', true)),
		);

		// Insert Records & return insert id //
		if ($this->db->insert('station_profile', $data)) {
			$new_station_id = $this->db->insert_id();
			$eqsl_default_qslmsg = xss_clean($this->input->post('eqsl_default_qslmsg', true));
			if (!empty(trim($eqsl_default_qslmsg))) {
				$this->user_options_model->set_option('eqsl_default_qslmsg', 'key_station_id', array($new_station_id => $eqsl_default_qslmsg));
			}
			return $new_station_id;
		} else {
			return 0;
		}
	}

	function edit() {

		// Check if the state is Canada and get the correct state
		if ($this->input->post('dxcc') == 1 && $this->input->post('station_ca_state') !="") {
			$state = xss_clean($this->input->post('station_ca_state', true));
		} else {
			$state = xss_clean($this->input->post('station_state', true));
		}

		// Check if DXCC is USA, Alaska or Hawaii, RU, UR, and others with subareas.
		// If not true, we clear the County field due to complex adif specs
		switch ($this->input->post('dxcc')) {
			case 6:
			case 110:
			case 291:
			case 15:
			case 54:
			case 61:
			case 126:
			case 151:
			case 288:
			case 339:
			case 170:
			case 21:
			case 29:
			case 32:
			case 281:
				$county = xss_clean($this->input->post('station_cnty', true));
				break;
			default:
				$county = '';
		}

		$data = array(
			'station_profile_name' => xss_clean($this->input->post('station_profile_name', true)),
			'station_gridsquare' => xss_clean(strtoupper($this->input->post('gridsquare', true))),
			'station_city' => xss_clean($this->input->post('city', true)),
			'station_iota' => xss_clean(strtoupper($this->input->post('iota', true))),
			'station_sota' => xss_clean(strtoupper($this->input->post('sota', true))),
			'station_wwff' => xss_clean(strtoupper($this->input->post('wwff', true))),
			'station_pota' => xss_clean(strtoupper($this->input->post('pota', true))),
			'station_sig' => xss_clean(strtoupper($this->input->post('sig', true))),
			'station_sig_info' => xss_clean(strtoupper($this->input->post('sig_info', true))),
			'station_callsign' => trim(xss_clean(strtoupper($this->input->post('station_callsign', true)))),
			'station_power' => is_numeric(xss_clean($this->input->post('station_power', true))) ? xss_clean($this->input->post('station_power', true)) : NULL,
			'station_dxcc' => xss_clean($this->input->post('dxcc', true)),
			'station_cnty' =>  $county,
			'station_cq' => xss_clean($this->input->post('station_cq', true)),
			'station_itu' => xss_clean($this->input->post('station_itu', true)),
			'state' => $state,
			'eqslqthnickname' => xss_clean($this->input->post('eqslnickname', true)),
			'hrdlog_username' => xss_clean($this->input->post('hrdlog_username', true)),
			'hrdlog_code' => xss_clean($this->input->post('hrdlog_code', true)),
			'hrdlogrealtime' => xss_clean($this->input->post('hrdlogrealtime', true)),
			'clublogignore' => xss_clean($this->input->post('clublogignore', true)),
			'clublogrealtime' => xss_clean($this->input->post('clublogrealtime', true)),
			'qrzapikey' => xss_clean($this->input->post('qrzapikey', true)),
			'qrzrealtime' => xss_clean($this->input->post('qrzrealtime', true)),
			'oqrs' => xss_clean($this->input->post('oqrs', true) ?? '0'),
			'oqrs_email' => xss_clean($this->input->post('oqrsemail', true) ?? '0'),
			'oqrs_text' => xss_clean($this->input->post('oqrstext', true)),
			'webadifapikey' => xss_clean($this->input->post('webadifapikey', true)),
			'webadifapiurl' => 'https://qo100dx.club/api',
			'webadifrealtime' => xss_clean($this->input->post('webadifrealtime', true)),
		);

		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('station_id', xss_clean($this->input->post('station_id', true)));
		$this->db->update('station_profile', $data);

		$eqsl_default_qslmsg = xss_clean($this->input->post('eqsl_default_qslmsg', true) ?? '');
		$this->user_options_model->set_option('eqsl_default_qslmsg', 'key_station_id', array(xss_clean($this->input->post('station_id', true)) => $eqsl_default_qslmsg));
	}

	function delete($id,$force = false, $user_id = null) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		// do not delete active station
		if ((!($force)) && ($clean_id === $this->find_active())) {
			return;
		}

		# Del options for that station_id
		$this->user_options_model->del_option('eqsl_default_qslmsg', 'key_station_id', array('option_key' => $id));

		// Delete Contents of log for that station_id
		$this->deletelog($clean_id, $user_id);

		// Delete Station Profile, links, contests and oqrs-requests
		$this->db->query("DELETE c FROM contest_session c WHERE c.station_id =?",$clean_id);
		$this->db->query("DELETE FROM oqrs WHERE station_id = ?",$clean_id);
		$this->db->query("DELETE FROM station_logbooks_relationship WHERE station_location_id = ?",$clean_id);
		$this->db->delete('station_profile', array('station_id' => $clean_id));
	}

	function deletelog($id, $user_id = null) {
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->load->model('qsl_model');
		$this->load->model('eqsl_images');

		$qsos=$this->db->query("select COL_PRIMARY_KEY as id from ".$this->config->item('table_name')." where station_id=?",$clean_id);
		foreach ($qsos->result() as $qso) {
			$this->qsl_model->del_image_for_qso($qso->id, $user_id);
			$this->eqsl_images->del_image($qso->id, $user_id);
		}
		// Delete QSOs
		$this->db->query("DELETE FROM ".$this->config->item('table_name')." WHERE station_id = ?",$clean_id);

		// Also clean up static map images
		if (!$this->load->is_loaded('staticmap_model')) {
			$this->load->model('staticmap_model');
		}
		$this->staticmap_model->remove_static_map_image($clean_id);
	}

	function set_active($current, $new) {
		// Clean inputs
		$clean_current = $this->security->xss_clean($current);
		$clean_new = $this->security->xss_clean($new);

		// be sure that stations belong to user
		if ($clean_current != 0) {
			if (!$this->check_station_is_accessible($clean_current)) {
				return;
			}
		}
		if (!$this->check_station_is_accessible($clean_new)) {
			return;
		}

		// Deselect current default
		$current_default = array(
			'station_active' => null,
		);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->update('station_profile', $current_default);

		// Deselect current default
		$newdefault = array(
			'station_active' => 1,
		);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('station_id', $clean_new);
		$this->db->update('station_profile', $newdefault);
	}

	function edit_favourite($id) {
		$cleanid = $this->security->xss_clean($id);

		$is_favorite = $this->user_options_model->get_options('station_location', array('option_name'=>'is_favorite', 'option_key'=>$cleanid))->row()->option_value ?? 'false';
		if ($is_favorite == 'true') {
			$this->user_options_model->del_option('station_location', 'is_favorite', array('option_key'=>$cleanid));
		} else if ($is_favorite == 'false') {
			$this->user_options_model->set_option('station_location', 'is_favorite', array($cleanid=>'true'));
		}
	}

	public function find_active() {
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('station_active', 1);
		$query = $this->db->get('station_profile');

		if($query->num_rows() >= 1) {
			foreach ($query->result() as $row)
			{
				return $row->station_id;
			}
		} else {
			return "0";
		}
	}

	public function gridsquare_from_station($station_id) {
		$this->db->where('station_id', $station_id);
		$query = $this->db->get('station_profile');

		if($query->num_rows() >= 1) {
			foreach ($query->result() as $row) {
				return $row->station_gridsquare;
			}
		} else {
			return null;
		}
	}

	public function find_gridsquare() {
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('station_active', 1);
		$query = $this->db->get('station_profile');

		if($query->num_rows() >= 1) {
			foreach ($query->result() as $row)
			{
				return $row->station_gridsquare;
			}
		} else {
			return "0";
		}
	}

	public function find_name() {
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('station_active', 1);
		$query = $this->db->get('station_profile');

		if($query->num_rows() >= 1) {
			foreach ($query->result() as $row)
			{
				return $row->station_profile_name;
			}
		} else {
			return "0";
		}
	}

    public function reassign($id) {
		// DANGEROUS Function. Do not let any User without Admin call this one!!
		// Clean ID
		$clean_id = $this->security->xss_clean($id);

		$this->db->select('station_profile.*, dxcc_entities.name as station_country');
		$this->db->where('station_id', $clean_id);
		$this->db->join('dxcc_entities', 'station_profile.station_dxcc = dxcc_entities.adif', 'left outer');
		$query = $this->db->get('station_profile');

		$row = $query->row();

		//print_r($row);

		$data = array(
		        'station_id' => $id,
		);

		$this->db->where('COL_STATION_CALLSIGN', $row->station_callsign);

		if($row->station_iota != "") {
			$this->db->where('COL_MY_IOTA', $row->station_iota);
		}

		if($row->station_sota != "") {
			$this->db->where('COL_MY_SOTA_REF', $row->station_sota);
		}

		if($row->station_wwff != "") {
			$this->db->where('COL_MY_WWFF_REF', $row->station_wwff);
		}

		if($row->station_pota != "") {
			$this->db->where('COL_MY_POTA_REF', $row->station_pota);
		}

		if($row->station_sig != "") {
			$this->db->where('COL_MY_SIG', $row->station_sig);
		}

		if($row->station_sig_info != "") {
			$this->db->where('COL_MY_SIG_INFO', $row->station_sig_info);
		}

		$this->db->where('COL_MY_COUNTRY', $row->station_country);

		if( strpos($row->station_gridsquare, ',') !== false ) {
		     $this->db->where('COL_MY_VUCC_GRIDS', $row->station_gridsquare);
		} else {
			$this->db->where('COL_MY_GRIDSQUARE', $row->station_gridsquare);
		}

		$this->db->update($this->config->item('table_name'), $data);

		$str = $this->db->last_query();

    }

    function profile_exists() {
	    $query = $this->db->get('station_profile');
		if($query->num_rows() >= 1) {
	    	return 1;
	    } else {
	    	return 0;
	    }
    }

    function stations_with_hrdlog_code() {
       $sql = "SELECT station_profile.station_id, station_profile.station_profile_name, station_profile.station_callsign, modc.modcount, notc.notcount, totc.totcount
                FROM station_profile
                LEFT OUTER JOIN (
                            SELECT count(*) modcount, station_id
                    FROM ". $this->config->item('table_name') .
                    " WHERE COL_HRDLOG_QSO_UPLOAD_STATUS = 'M'
                    group by station_id
                ) as modc on station_profile.station_id = modc.station_id
                LEFT OUTER JOIN (
                            SELECT count(*) notcount, station_id
                    FROM " . $this->config->item('table_name') .
                    " WHERE (coalesce(COL_HRDLOG_QSO_UPLOAD_STATUS, '') = ''
                    or COL_HRDLOG_QSO_UPLOAD_STATUS = 'N')
                    group by station_id
                ) as notc on station_profile.station_id = notc.station_id
                LEFT OUTER JOIN (
                    SELECT count(*) totcount, station_id
                    FROM " . $this->config->item('table_name') .
                    " WHERE COL_HRDLOG_QSO_UPLOAD_STATUS = 'Y'
                    group by station_id
                ) as totc on station_profile.station_id = totc.station_id
                WHERE coalesce(station_profile.hrdlog_code, '') <> '' AND coalesce(station_profile.hrdlog_username, '') <> ''
				 AND station_profile.user_id = " . $this->session->userdata('user_id');
        $query = $this->db->query($sql);

        return $query;
    }

    function stations_with_qrz_api_key() {
	    $bindings=[];
	    $sql = "SELECT station_profile.station_id, station_profile.station_profile_name, station_profile.station_callsign, modc.modcount, notc.notcount, totc.totcount
		    FROM station_profile
		    LEFT OUTER JOIN (
			    SELECT count(*) modcount, station_id
			    FROM ". $this->config->item('table_name') .
			    " WHERE COL_QRZCOM_QSO_UPLOAD_STATUS = 'M'
			    group by station_id
		) as modc on station_profile.station_id = modc.station_id
		LEFT OUTER JOIN (
			SELECT count(*) notcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE (coalesce(COL_QRZCOM_QSO_UPLOAD_STATUS, '') = ''
			or COL_QRZCOM_QSO_UPLOAD_STATUS = 'N')
			group by station_id
		) as notc on station_profile.station_id = notc.station_id
		LEFT OUTER JOIN (
			SELECT count(*) totcount, station_id
			FROM " . $this->config->item('table_name') .
			" WHERE COL_QRZCOM_QSO_UPLOAD_STATUS = 'Y'
			group by station_id
		) as totc on station_profile.station_id = totc.station_id
		WHERE coalesce(station_profile.qrzapikey, '') <> ''
		AND station_profile.user_id = ?";
	    $bindings[]=$this->session->userdata('user_id');
	    $query = $this->db->query($sql, $bindings);

	    return $query;
    }

	function stations_with_webadif_api_key() {
		$bindings=[];
		$sql="
			SELECT station_profile.station_id, station_profile.station_profile_name, station_profile.station_callsign, notc.c notcount, totc.c totcount
			FROM station_profile
			LEFT OUTER JOIN (
				SELECT qsos.station_id, COUNT(qsos.COL_PRIMARY_KEY) c
				FROM %s qsos
				LEFT JOIN webadif ON qsos.COL_PRIMARY_KEY = webadif.qso_id
				WHERE webadif.qso_id IS NULL AND qsos.COL_SAT_NAME = 'QO-100'
				GROUP BY qsos.station_id
			) notc ON station_profile.station_id = notc.station_id
			LEFT JOIN (
				SELECT qsos.station_id, COUNT(qsos.COL_PRIMARY_KEY) c
				FROM %s qsos
				WHERE qsos.COL_SAT_NAME = 'QO-100'
				GROUP BY qsos.station_id
			) totc ON station_profile.station_id = totc.station_id
			WHERE COALESCE(station_profile.webadifapikey, '') <> ''
			AND COALESCE(station_profile.webadifapiurl, '') <> ''
			AND station_profile.user_id = ?
		";
		$bindings[]=$this->session->userdata('user_id');
		$sql=sprintf(
			$sql,
			$this->config->item('table_name'),
			$this->config->item('table_name')
		);
		return $this->db->query($sql,$bindings);
	}

    /*
	*	Function: are_eqsl_nicks_defined
	*	Description: Returns number of station profiles with eqslnicknames
    */
    function are_eqsl_nicks_defined() {
    	$this->db->select('eqslqthnickname');
    	$this->db->where('eqslqthnickname IS NOT NULL');
    	$this->db->where('eqslqthnickname !=', '');
		$this->db->from('station_profile');
		$query = $this->db->get();

		return $query->num_rows();
    }

	public function check_station_is_accessible($id) {
		// check if station belongs to user
		$this->db->select('station_id');
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('station_id', $id);
		$query = $this->db->get('station_profile');
		if ($query->num_rows() == 1) {
			return true;
		}
		return false;
	}

	public function get_station_power($id) {
		$this->db->select('station_power');
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$this->db->where('station_id', $id);
		$query = $this->db->get('station_profile');
		if($query->num_rows() >= 1) {
			foreach ($query->result() as $row)
			{
				return $row->station_power;
			}
		} else {
			return null;
		}
	}

	public function get_user_from_station($stationid) {
		if (($stationid ?? '') != '') {
			$sql="select u.* from users u inner join station_profile sp on (u.user_id=sp.user_id) where sp.station_id = ?";
			$query = $this->db->query($sql, $stationid);
			return $query->row();
		} else {
			return false;
		}
	}

	public function check_station_against_user($stationid, $userid) {
		$this->db->select('station_id');
		$this->db->where('user_id', $userid);
		$this->db->where('station_id', $stationid);
		$query = $this->db->get('station_profile');
		if ($query->num_rows() == 1) {
			return true;
		}
		return false;
	}

	public function check_station_against_callsign($stationid, $callsign) {
		$this->db->select('station_id');
		$this->db->where('station_callsign', $callsign);
		$this->db->where('station_id', $stationid);
		$query = $this->db->get('station_profile');
		if ($query->num_rows() == 1) {
			return true;
		}
		return false;
	}

	// [MAP Custom] get array for json structure (for map) about info's station  //
	public function get_station_array_for_map() {
		$_jsonresult = array();
		list($station_lat, $station_lng) = array(0,0);
		$station_active = $this->profile($this->find_active())->row();
		if (!empty($station_active)) { list($station_lat, $station_lng) = $this->qra->qra2latlong($station_active->station_gridsquare); }
		if (($station_lat!=0)&&($station_lng!=0)) { $_jsonresult = array('lat'=>$station_lat,'lng'=>$station_lng,'html'=>$station_active->station_gridsquare,'label'=>$station_active->station_profile_name,'icon'=>'stationIcon'); }
		return (count($_jsonresult)>0)?(array('station'=>$_jsonresult)):array();
	}

	public function lookupProfileCoords($stationid) {
		$sql = "SELECT station_gridsquare FROM station_profile WHERE station_id = ?;";
		$query = $this->db->query($sql, $stationid);
		if ($query->num_rows() == 1) {
			$row = $query->row();
			if ($row->station_gridsquare != '') {
				if (!$this->load->is_loaded('Qra')) {
					$this->load->library('Qra');
				}
				return $this->qra->qra2latlong($row->station_gridsquare);
			}
		}
		return false;
	}
}

?>
