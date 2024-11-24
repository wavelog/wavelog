<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Logbook extends CI_Controller {

	function index()
	{

		// Check if users logged in
		$this->load->model('user_model');
		if($this->user_model->validate_session() == 0) {
			// user is not logged in
			redirect('user/login');
		}
		$this->load->model('stations');

		$this->load->model('logbook_model');

		$this->load->library('pagination');
		$config['base_url'] = base_url().'index.php/logbook/index/';
		$config['total_rows'] = $this->logbook_model->total_qsos();
		$config['per_page'] = '25';
		$config['num_links'] = 6;
		$config['full_tag_open'] = '';
		$config['full_tag_close'] = '';
		$config['cur_tag_open'] = '<strong class="active"><a href="">';
		$config['cur_tag_close'] = '</a></strong>';

		$this->pagination->initialize($config);

		//load the model and get results
		$data['results'] = $this->logbook_model->get_qsos($config['per_page'],$this->uri->segment(3));

		$data['user_map_custom'] = $this->optionslib->get_map_custom();

		if(!$data['results']) {
			$this->session->set_flashdata('notice', __("No logbooks were found. You need to define a logbook under Station Logbooks! Do it here:") . ' <a href="' . site_url('logbooks') . '" title="Station Logbooks">' . __("Station Logbooks") . '</a>');
		}

		// Calculate Lat/Lng from Locator to use on Maps
		if($this->session->userdata('user_locator')) {
				if(!$this->load->is_loaded('Qra')) {
					$this->load->library('Qra');
				}
				$qra_position = $this->qra->qra2latlong($this->session->userdata('user_locator'));
				if (isset($qra_position[0]) and isset($qra_position[1])) {
					$data['qra'] = "set";
					$data['qra_lat'] = $qra_position[0];
					$data['qra_lng'] = $qra_position[1];
				} else {
					$data['qra'] = "none";
				}
		} else {
				$data['qra'] = "none";
		}



		// load the view
		$data['page_title'] = __("Logbook");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('view_log/index');
		$this->load->view('interface_assets/footer');

	}

	function jsonentity($adif) {
        $this->load->model('user_model');
        if(!$this->user_model->authorize($this->config->item('auth_mode'))) { return; }

        $return['dxcc'] = $this->getentity($adif);
        header('Content-Type: application/json');
        echo json_encode($return, JSON_PRETTY_PRINT);
    }

	function json($tempcallsign, $tempband, $tempmode, $tempstation_id = null, $date = "") {
		session_write_close();
		if (($date ?? '') != '') {
			$date=date("Y-m-d",strtotime($date));
		}
		// Cleaning for security purposes
		$callsign = $this->security->xss_clean($tempcallsign);
		$band = $this->security->xss_clean($tempband);
		$mode = $this->security->xss_clean($tempmode);
		$station_id = $this->security->xss_clean($tempstation_id);
		$date = $this->security->xss_clean($date);

		$this->load->model('user_model');
		if(!$this->user_model->authorize($this->config->item('auth_mode'))) { return; }

		// Convert - in Callsign to / Used for URL processing
		$callsign = str_replace("-","/",$callsign);
		$callsign = str_replace("Ø","0",$callsign);

		// Check if callsign is an LoTW User
		// Check Database for all other data
		$this->load->model('logbook_model');

		$lotw_days=$this->logbook_model->check_last_lotw($callsign);
		if ($lotw_days != null) {
			$lotw_member="active";
		} else {
			$lotw_member="not found";
		}

		$return = [
			"callsign" => strtoupper($callsign),
			"dxcc" => false,
			"callsign_name" => "",
			"callsign_qra"  => "",
			"callsign_distance"  => 0,
			"callsign_qth"  => "",
			"callsign_iota" => "",
			"callsign_state" => "",
			"callsign_us_county" => "",
			"callsign_ituz" => "",
			"callsign_cqz" => "",
			"qsl_manager" => "",
			"bearing" 		=> "",
			"workedBefore" => false,
			"timesWorked" => 0,
			"lotw_member" => $lotw_member,
			"lotw_days" => $lotw_days,
			"image" => "",
		];

		$return['dxcc'] = $this->dxcheck($callsign,$date);

		$lookupcall=$this->logbook_model->get_plaincall($callsign);

		$return['partial'] = $this->partial($lookupcall, $band);

		$callbook = $this->logbook_model->loadCallBook($callsign, $this->config->item('use_fullname'));

		if ($this->session->userdata('user_measurement_base') == NULL) {
			$measurement_base = $this->config->item('measurement_base');
		} else {
			$measurement_base = $this->session->userdata('user_measurement_base');
		}

		$return['callsign_name'] 		= $this->nval($callbook['name'] ?? '', $this->logbook_model->call_name($callsign));
		$return['callsign_qra'] 		= $this->nval($callbook['gridsquare'] ?? '',  $this->logbook_model->call_qra($callsign));
		$return['callsign_distance'] 	= $this->distance($return['callsign_qra'], $station_id);
		$return['callsign_qth'] 		= $this->nval($callbook['city'] ?? '', $this->logbook_model->call_qth($callsign));
		$return['callsign_iota'] 		= $this->nval($callbook['iota'] ?? '', $this->logbook_model->call_iota($callsign));
		$return['callsign_email'] 		= $this->nval($callbook['email'] ?? '', $this->logbook_model->call_email($callsign));
		$return['qsl_manager'] 			= $this->nval($callbook['qslmgr'] ?? '', $this->logbook_model->call_qslvia($callsign));
		$return['callsign_state'] 		= $this->nval($callbook['state'] ?? '', $this->logbook_model->call_state($callsign));
		$return['callsign_us_county'] 	= $this->nval($callbook['us_county'] ?? '', $this->logbook_model->call_us_county($callsign));
		$return['callsign_ituz'] 	= $this->nval($callbook['ituz'] ?? '', $this->logbook_model->call_ituzone($callsign));
		$return['callsign_cqz'] 	= $this->nval($callbook['cqz'] ?? '', $this->logbook_model->call_cqzone($callsign));
		$return['workedBefore'] 		= $this->worked_grid_before($return['callsign_qra'], $band, $mode);
		$return['confirmed'] 		= $this->confirmed_grid_before($return['callsign_qra'], $band, $mode);
		$return['timesWorked'] 		= $this->logbook_model->times_worked($lookupcall);

		if ($this->session->userdata('user_show_profile_image')) {
			if (isset($callbook) && isset($callbook['image'])) {
				if ($callbook['image'] == "") {
					$return['image'] = "n/a";
				} else {
					$return['image'] = $callbook['image'];
				}
			} else {
				$return['image'] = "n/a";
			}
		}

		if ($return['callsign_qra'] != "" || $return['callsign_qra'] != null) {
			$return['latlng'] = $this->qralatlng($return['callsign_qra']);
			$return['bearing'] = $this->bearing($return['callsign_qra'], $measurement_base, $station_id);
		}

		echo json_encode($return, JSON_PRETTY_PRINT);

		return;
	}

	// Returns $val2 first if it has value, even if it is null or empty string, if not return $val1.
	function nval($val1, $val2) {
		return (($val2 ?? "") === "" ? ($val1 ?? "") : ($val2 ?? ""));
	}

	function confirmed_grid_before($gridsquare, $band, $mode) {
		if (strlen($gridsquare) < 4)
			return false;

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$user_default_confirmation = $this->session->userdata('user_default_confirmation');

		if(!empty($logbooks_locations_array)) {
			$extrawhere='';
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
				$extrawhere="COL_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_LOTW_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_EQSL_QSL_RCVD='Y'";
			}

			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_QRZCOM_QSO_DOWNLOAD_STATUS='Y'";
			}

			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'C') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_CLUBLOG_QSO_DOWNLOAD_STATUS='Y'";
			}


			if($band == "SAT") {
				$this->db->where('COL_PROP_MODE', 'SAT');
				if ($extrawhere != '') {
					$this->db->where('('.$extrawhere.')');
				} else {
					$this->db->where("1=0");
				}
			} else {
				$this->load->model('logbook_model');
				$this->db->where('COL_MODE', $this->logbook_model->get_main_mode_from_mode($mode));
				$this->db->where('COL_BAND', $band);
				$this->db->where('COL_PROP_MODE !=','SAT');
				if ($extrawhere != '') {
					$this->db->where('('.$extrawhere.')');
				} else {
					$this->db->where("1=0");
				}
			}

			$this->db->where_in('station_id', $logbooks_locations_array);
			$this->db->like('SUBSTRING(COL_GRIDSQUARE, 1, 4)', substr($gridsquare, 0, 4));
			$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "desc");
			$this->db->limit(1);


			$query = $this->db->get($this->config->item('table_name'));


			foreach ($query->result() as $workedBeforeRow) {
				return true;
			}
		}
		return false;
	}

	function worked_grid_before($gridsquare, $band, $mode)
	{
		if (strlen($gridsquare) < 4)
			return false;

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if(!empty($logbooks_locations_array)) {
			if($band == "SAT") {
				$this->db->where('COL_PROP_MODE', 'SAT');
			} else {
				$this->db->where('COL_MODE', $this->logbook_model->get_main_mode_from_mode($mode));
				$this->db->where('COL_BAND', $band);
				$this->db->where('COL_PROP_MODE !=','SAT');

			}
			$this->db->where_in('station_id', $logbooks_locations_array);
			$this->db->like('SUBSTRING(COL_GRIDSQUARE, 1, 4)', substr($gridsquare, 0, 4));
			$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "desc");
			$this->db->limit(1);


			$query = $this->db->get($this->config->item('table_name'));


			foreach ($query->result() as $workedBeforeRow)
			{
				return true;
			}
		}
		return false;
	}

	/*
	*	Function: jsonlookupgrid
	*
	* 	Usage: Used to look up gridsquares when creating a QSO to check whether its needed or not
	*	the $type variable is only used for satellites, set this to SAT.
	*
	*/
	function jsonlookupgrid($gridsquare, $type, $band, $mode) {
		session_write_close();
		$return = [
			"workedBefore" => false,
			"confirmed" => false,
		];
		$user_default_confirmation = $this->session->userdata('user_default_confirmation');
        $this->load->model('logbooks_model');
        $logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if($type == "SAT") {
			$this->db->where('COL_PROP_MODE', 'SAT');
		} else {
			$this->load->model('logbook_model');
			$this->db->where('COL_MODE', $this->logbook_model->get_main_mode_from_mode($mode));
			$this->db->where('COL_BAND', $band);
			$this->db->where('COL_PROP_MODE !=','SAT');

		}

		$this->db->where_in('station_id', $logbooks_locations_array);

		$this->db->like('SUBSTRING(COL_GRIDSQUARE, 1, 4)', substr($gridsquare, 0, 4));
		$query = $this->db->get($this->config->item('table_name'), 1, 0);
		foreach ($query->result() as $workedBeforeRow)
		{
			$return['workedBefore'] = true;
		}


		$extrawhere='';
		if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
			$extrawhere="COL_QSL_RCVD='Y'";
		}
		if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
			if ($extrawhere!='') {
				$extrawhere.=" OR";
			}
			$extrawhere.=" COL_LOTW_QSL_RCVD='Y'";
		}
		if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
			if ($extrawhere!='') {
				$extrawhere.=" OR";
			}
			$extrawhere.=" COL_EQSL_QSL_RCVD='Y'";
		}

		if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
			if ($extrawhere!='') {
				$extrawhere.=" OR";
			}
			$extrawhere.=" COL_QRZCOM_QSO_DOWNLOAD_STATUS='Y'";
		}

		if($type == "SAT") {
			$this->db->where('COL_PROP_MODE', 'SAT');
			if ($extrawhere != '') {
				$this->db->where('('.$extrawhere.')');
			} else {
				$this->db->where("1=0");
			}
		} else {
			$this->load->model('logbook_model');
			$this->db->where('COL_MODE', $this->logbook_model->get_main_mode_from_mode($mode));
			$this->db->where('COL_BAND', $band);
			$this->db->where('COL_PROP_MODE !=','SAT');
			if ($extrawhere != '') {
				$this->db->where('('.$extrawhere.')');
			} else {
				$this->db->where("1=0");
			}
		}

		$this->db->where_in('station_id', $logbooks_locations_array);

		$this->db->like('SUBSTRING(COL_GRIDSQUARE, 1, 4)', substr($gridsquare, 0, 4));
		$query = $this->db->get($this->config->item('table_name'), 1, 0);
		foreach ($query->result() as $workedBeforeRow) {
			$return['confirmed']=true;
		}

		header('Content-Type: application/json');
		echo json_encode($return, JSON_PRETTY_PRINT);

		return;
	}

	function jsonlookupdxcc($country, $type, $band, $mode) {
		session_write_close();

		$return = [
			"workedBefore" => false,
			"confirmed" => false,
		];

		$user_default_confirmation = $this->session->userdata('user_default_confirmation');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->load->model('logbook_model');

		if(!empty($logbooks_locations_array)) {
			if($type == "SAT") {
				$this->db->where('COL_PROP_MODE', 'SAT');
			} else {
				$this->db->where('COL_MODE', $this->logbook_model->get_main_mode_from_mode($mode));
				$this->db->where('COL_BAND', $band);
				$this->db->where('COL_PROP_MODE !=','SAT');

			}

			$this->db->where_in('station_id', $logbooks_locations_array);
			$this->db->where('COL_COUNTRY', urldecode($country));

			$query = $this->db->get($this->config->item('table_name'), 1, 0);
			foreach ($query->result() as $workedBeforeRow)
			{
				$return['workedBefore'] = true;
			}

			$extrawhere='';
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
				$extrawhere="COL_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_LOTW_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_EQSL_QSL_RCVD='Y'";
			}

			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_QRZCOM_QSO_DOWNLOAD_STATUS='Y'";
			}


			if($type == "SAT") {
				$this->db->where('COL_PROP_MODE', 'SAT');
				if ($extrawhere != '') {
					$this->db->where('('.$extrawhere.')');
				} else {
					$this->db->where("1=0");
				}
			} else {
				$this->load->model('logbook_model');
				$this->db->where('COL_MODE', $this->logbook_model->get_main_mode_from_mode($mode));
				$this->db->where('COL_BAND', $band);
				$this->db->where('COL_PROP_MODE !=','SAT');
				if ($extrawhere != '') {
					$this->db->where('('.$extrawhere.')');
				} else {
					$this->db->where("1=0");
				}
			}

			$this->db->where_in('station_id', $logbooks_locations_array);
			$this->db->where('COL_COUNTRY', urldecode($country));

			$query = $this->db->get($this->config->item('table_name'), 1, 0);
			foreach ($query->result() as $workedBeforeRow) {
				$return['confirmed']=true;
			}


			header('Content-Type: application/json');
			echo json_encode($return, JSON_PRETTY_PRINT);

			return;
		} else {
			$return['workedBefore'] = false;
			$return['confirmed'] = false;

			header('Content-Type: application/json');
			echo json_encode($return, JSON_PRETTY_PRINT);
			return;
		}
	}

	function jsonlookupcallsign($callsign, $type, $band, $mode) {
		session_write_close();

		// Convert - in Callsign to / Used for URL processing
		$callsign = str_replace("-","/",$callsign);

		$return = [
			"workedBefore" => false,
			"confirmed" => false,
		];

		$user_default_confirmation = $this->session->userdata('user_default_confirmation');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->load->model('logbook_model');

		if(!empty($logbooks_locations_array)) {
			if($type == "SAT") {
				$this->db->where('COL_PROP_MODE', 'SAT');
			} else {
				$this->db->where('COL_MODE', $this->logbook_model->get_main_mode_from_mode($mode));
				$this->db->where('COL_BAND', $band);
				$this->db->where('COL_PROP_MODE !=','SAT');

			}

			$this->db->where_in('station_id', $logbooks_locations_array);
			$this->db->where('COL_CALL', strtoupper($callsign));

			$query = $this->db->get($this->config->item('table_name'), 1, 0);
			foreach ($query->result() as $workedBeforeRow)
			{
				$return['workedBefore'] = true;
			}

			$extrawhere='';
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
				$extrawhere="COL_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_LOTW_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_EQSL_QSL_RCVD='Y'";
			}
			if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
				if ($extrawhere!='') {
					$extrawhere.=" OR";
				}
				$extrawhere.=" COL_QRZCOM_QSO_DOWNLOAD_STATUS='Y'";
			}



			if($type == "SAT") {
				$this->db->where('COL_PROP_MODE', 'SAT');
				if ($extrawhere != '') {
					$this->db->where('('.$extrawhere.')');
				} else {
					$this->db->where("1=0");
				}
			} else {
				$this->load->model('logbook_model');
				$this->db->where('COL_MODE', $this->logbook_model->get_main_mode_from_mode($mode));
				$this->db->where('COL_BAND', $band);
				$this->db->where('COL_PROP_MODE !=','SAT');
				if ($extrawhere != '') {
					$this->db->where('('.$extrawhere.')');
				} else {
					$this->db->where("1=0");
				}
			}
			$this->db->where_in('station_id', $logbooks_locations_array);
			$this->db->where('COL_CALL', strtoupper($callsign));

			$query = $this->db->get($this->config->item('table_name'), 1, 0);
			foreach ($query->result() as $workedBeforeRow) {
				$return['confirmed'] = true;
			}

			header('Content-Type: application/json');
			echo json_encode($return, JSON_PRETTY_PRINT);
			return;
		} else {
			$return['workedBefore'] = false;
			$return['confirmed'] = false;
			header('Content-Type: application/json');
			echo json_encode($return, JSON_PRETTY_PRINT);
			return;
		}
	}

	function view($id) {
		$this->load->library('DxccFlag');

		$this->load->model('user_model');
		if(!$this->user_model->authorize($this->config->item('auth_mode'))) { return; }

		if(!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}
		$this->load->library('subdivisions');

		$this->load->model('logbook_model');
		$data['query'] = $this->logbook_model->get_qso($id);
		$data['dxccFlag'] = $this->dxccflag->get($data['query']->result()[0]->COL_DXCC);

		if ($this->session->userdata('user_measurement_base') == NULL) {
			$data['measurement_base'] = $this->config->item('measurement_base');
		}
		else {
			$data['measurement_base'] = $this->session->userdata('user_measurement_base');
		}

		$this->load->model('Qsl_model');
		$data['qslimages'] = $this->Qsl_model->getQslForQsoId($id);
		$data['primary_subdivision'] = $this->subdivisions->get_primary_subdivision_name($data['query']->result()[0]->COL_DXCC);
		$data['secondary_subdivision'] = $this->subdivisions->get_secondary_subdivision_name($data['query']->result()[0]->COL_DXCC);
		$data['max_upload'] = ini_get('upload_max_filesize');
		$this->load->view('interface_assets/mini_header', $data);
		$this->load->view('view_log/qso');
		$this->load->view('interface_assets/footer');
	}

	function partial($id, $band = null) {
		$this->load->model('user_model');
		if(!$this->user_model->authorize($this->config->item('auth_mode'))) { return; }

		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		$html = "";

		if(!empty($logbooks_locations_array)) {
			$this->db->select(''.$this->config->item('table_name').'.COL_CALL, '.$this->config->item('table_name').'.COL_BAND, '.$this->config->item('table_name').'.COL_FREQ, '.$this->config->item('table_name').'.COL_TIME_ON, '.$this->config->item('table_name').'.COL_RST_RCVD, '.$this->config->item('table_name').'.COL_RST_SENT, '.$this->config->item('table_name').'.COL_MODE, '.$this->config->item('table_name').'.COL_SUBMODE, '.$this->config->item('table_name').'.COL_PRIMARY_KEY, '.$this->config->item('table_name').'.COL_SAT_NAME, '.$this->config->item('table_name').'.COL_GRIDSQUARE, '.$this->config->item('table_name').'.COL_QSL_RCVD, '.$this->config->item('table_name').'.COL_EQSL_QSL_RCVD, '.$this->config->item('table_name').'.COL_EQSL_QSL_SENT, '.$this->config->item('table_name').'.COL_QSL_SENT, '.$this->config->item('table_name').'.COL_STX, '.$this->config->item('table_name').'.COL_STX_STRING, '.$this->config->item('table_name').'.COL_SRX, '.$this->config->item('table_name').'.COL_SRX_STRING, '.$this->config->item('table_name').'.COL_LOTW_QSL_SENT, '.$this->config->item('table_name').'.COL_LOTW_QSL_RCVD, '.$this->config->item('table_name').'.COL_VUCC_GRIDS, '.$this->config->item('table_name').'.COL_MY_GRIDSQUARE, '.$this->config->item('table_name').'.COL_CONTEST_ID, '.$this->config->item('table_name').'.COL_STATE, '.$this->config->item('table_name').'.COL_QRZCOM_QSO_UPLOAD_STATUS, '.$this->config->item('table_name').'.COL_QRZCOM_QSO_DOWNLOAD_STATUS, '.$this->config->item('table_name').'.COL_CLUBLOG_QSO_UPLOAD_STATUS, '.$this->config->item('table_name').'.COL_CLUBLOG_QSO_DOWNLOAD_STATUS, '.$this->config->item('table_name').'.COL_POTA_REF, '.$this->config->item('table_name').'.COL_IOTA, '.$this->config->item('table_name').'.COL_SOTA_REF, '.$this->config->item('table_name').'.COL_WWFF_REF, '.$this->config->item('table_name').'.COL_OPERATOR, '.$this->config->item('table_name').'.COL_COUNTRY, station_profile.*');
			$this->db->from($this->config->item('table_name'));

			$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
			$this->db->where_in('station_profile.station_id', $logbooks_locations_array);

			$this->db->group_start();
			$this->db->where($this->config->item('table_name').'.COL_CALL', $id);
			$this->db->or_like($this->config->item('table_name').'.COL_CALL', '/'.$id,'before');
			$this->db->or_like($this->config->item('table_name').'.COL_CALL', $id.'/','after');
			$this->db->or_like($this->config->item('table_name').'.COL_CALL', '/'.$id.'/');
			$this->db->group_end();

			$this->db->order_by($this->config->item('table_name').".COL_TIME_ON", "desc");
			$this->db->limit(5);

			$query = $this->db->get();
		}

		if (!empty($logbooks_locations_array) && $query->num_rows() > 0) {
			$html .= "<div class=\"table-responsive\">";
			$html .= "<table class=\"table table-striped\">";
				$html .= "<tr>";
					$html .= "<th>Date</th>";
					$html .= "<th>Callsign</th>";
					$html .= $this->part_table_header_col($this, $this->session->userdata('user_column1')==""?'Mode':$this->session->userdata('user_column1'));
					$html .= $this->part_table_header_col($this, $this->session->userdata('user_column2')==""?'RSTS':$this->session->userdata('user_column2'));
					$html .= $this->part_table_header_col($this, $this->session->userdata('user_column3')==""?'RSTR':$this->session->userdata('user_column3'));
					$html .= $this->part_table_header_col($this, $this->session->userdata('user_column4')==""?'Band':$this->session->userdata('user_column4'));
					switch($this->session->userdata('user_previous_qsl_type')) {
						case 0:
							$html .= "<th>".__("QSL")."</th>";
							break;
						case 1:
							$html .= "<th>".__("LoTW")."</th>";
							break;
						case 2:
							$html .= "<th>".__("eQSL")."</th>";
							break;
						case 4:
							$html .= "<th>QRZ</th>";
							break;
						case 8:
							$html .= "<th>".__("Clublog") ."</th>";
							break;
						default:
							$html .= "<th>".__("QSL")."</th>";
							break;
					}
					$html .= "<th></th>";
				$html .= "</tr>";

			// Get Date format
			if($this->session->userdata('user_date_format')) {
				// If Logged in and session exists
				$custom_date_format = $this->session->userdata('user_date_format');
			} else {
				// Get Default date format from /config/wavelog.php
				$custom_date_format = $this->config->item('qso_date_format');
			}

			foreach ($query->result() as $row) {
				$timestamp = strtotime($row->COL_TIME_ON ?? '1970-01-01 00:00:00');
				$html .= "<tr>";
					$html .= "<td>".date($custom_date_format, $timestamp). date(' H:i',strtotime($row->COL_TIME_ON ?? '1970-01-01 00:00:00')) . "</td>";
					$html .= "<td><a id='edit_qso' href='javascript:displayQso(" . $row->COL_PRIMARY_KEY . ");'>" . str_replace('0','&Oslash;',strtoupper($row->COL_CALL)) . "</a></td>";
					$html .= $this->part_table_col($row, $this->session->userdata('user_column1')==""?'Mode':$this->session->userdata('user_column1'));
					$html .= $this->part_table_col($row, $this->session->userdata('user_column2')==""?'RSTS':$this->session->userdata('user_column2'));
					$html .= $this->part_table_col($row, $this->session->userdata('user_column3')==""?'RSTR':$this->session->userdata('user_column3'));
					$html .= $this->part_table_col($row, $this->session->userdata('user_column4')==""?'Band':$this->session->userdata('user_column4'));
					if ($this->session->userdata('user_previous_qsl_type') == 1) {
						$html .= "<td class=\"lotw\">";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_LOTW_QSL_SENT) {
							case "Y":
								$html .= "green";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9650;</span>";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_LOTW_QSL_RCVD) {
							case "Y":
								$html .= "green";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9660;</span>";
						$html .= "</td>";
					} else if ($this->session->userdata('user_previous_qsl_type') == 2) {
						$html .= "<td class=\"eqsl\">";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_EQSL_QSL_SENT) {
							case "Y":
								$html .= "green";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9650;</span>";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_EQSL_QSL_RCVD) {
							case "Y":
								$html .= "green";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9660;</span>";
						$html .= "</td>";
					} else if ($this->session->userdata('user_previous_qsl_type') == 4) {
						$html .= "<td class=\"qrz\">";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_QRZCOM_QSO_UPLOAD_STATUS) {
							case "Y":
								$html .= "green";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9650;</span>";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_QRZCOM_QSO_DOWNLOAD_STATUS) {
							case "Y":
								$html .= "green";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9660;</span>";
						$html .= "</td>";
					} else if ($this->session->userdata('user_previous_qsl_type') == 8) {
						$html .= "<td class=\"clublog\">";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_CLUBLOG_QSO_UPLOAD_STATUS) {
							case "Y":
								$html .= "green";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9650;</span>";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_CLUBLOG_QSO_DOWNLOAD_STATUS) {
							case "Y":
								$html .= "green";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9660;</span>";
						$html .= "</td>";
					} else {
						$html .= "<td class=\"qsl\">";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_QSL_SENT) {
							case "Y":
								$html .= "green";
								break;
							case "Q":
								$html .= "yellow";
								break;
							case "R":
								$html .= "yellow";
								break;
							case "I":
								$html .= "grey";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9650;</span>";
						$html .= "<span class=\"qsl-";
						switch ($row->COL_QSL_RCVD) {
							case "Y":
								$html .= "green";
								break;
							case "Q":
								$html .= "yellow";
								break;
							case "R":
								$html .= "yellow";
								break;
							case "I":
								$html .= "grey";
								break;
							default:
								$html .= "red";
						}
						$html .= "\">&#9660;</span>";
						$html .= "</td>";
					}
					$html .= "<td><span class=\"badge bg-info\">".$row->station_callsign."</span></td>";
				$html .= "</tr>";
			}
			$html .= "</table>";
			$html .= "</div>";
			return $html;
		} else {
				if ($this->config->item('callbook') == "qrz" && $this->config->item('qrz_username') != null && $this->config->item('qrz_password') != null) {
					// Lookup using QRZ
					$this->load->library('qrz');

					if(!$this->session->userdata('qrz_session_key')) {
						$qrz_session_key = $this->qrz->session($this->config->item('qrz_username'), $this->config->item('qrz_password'));
						$this->session->set_userdata('qrz_session_key', $qrz_session_key);
					}
					$callsign['callsign'] = $this->qrz->search($id, $this->session->userdata('qrz_session_key'), $this->config->item('use_fullname'));

					if (empty($callsign['callsign']['callsign'])) {
						$qrz_session_key = $this->qrz->session($this->config->item('qrz_username'), $this->config->item('qrz_password'));
						$this->session->set_userdata('qrz_session_key', $qrz_session_key);
						$callsign['callsign'] = $this->qrz->search($id, $this->session->userdata('qrz_session_key'), $this->config->item('use_fullname'));
					}
					if (isset($callsign['callsign']['dxcc'])) {
						$this->load->model('logbook_model');
						$entity = $this->logbook_model->get_entity($callsign['callsign']['dxcc']);
						$callsign['callsign']['dxcc_name'] = $entity['name'];
						$callsign['dxcc_worked'] = $this->logbook_model->check_if_dxcc_worked_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
						$callsign['dxcc_confirmed'] = $this->logbook_model->check_if_dxcc_cnfmd_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
					}
				} else if ($this->config->item('callbook') == "hamqth" && $this->config->item('hamqth_username') != null && $this->config->item('hamqth_password') != null) {
					// Load the HamQTH library
					$this->load->library('hamqth');

					if(!$this->session->userdata('hamqth_session_key')) {
						$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
						$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
					}

					$callsign['callsign'] = $this->hamqth->search($id, $this->session->userdata('hamqth_session_key'));

					// If HamQTH session has expired, start a new session and retry the search.
					if($callsign['callsign']['error'] == "Session does not exist or expired") {
						$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
						$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
						$callsign['callsign'] = $this->hamqth->search($id, $this->session->userdata('hamqth_session_key'));
					}
					if (isset($data['callsign']['gridsquare'])) {
						$this->load->model('logbook_model');
						$callsign['grid_worked'] = $this->logbook_model->check_if_grid_worked_in_logbook(strtoupper(substr($data['callsign']['gridsquare'],0,4)), null, $this->session->userdata('user_default_band'))->num_rows();
					}
					if (isset($callsign['callsign']['dxcc'])) {
						$this->load->model('logbook_model');
						$entity = $this->logbook_model->get_entity($callsign['callsign']['dxcc']);
						$callsign['callsign']['dxcc_name'] = $entity['name'];
						$callsign['dxcc_worked'] = $this->logbook_model->check_if_dxcc_worked_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
						$callsign['dxcc_confirmed'] = $this->logbook_model->check_if_dxcc_cnfmd_in_logbook($callsign['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
					}
					if (isset($callsign['callsign']['error'])) {
						$callsign['error'] = $callsign['callsign']['error'];
					}
				} else {
					$callsign['error'] = 'Lookup not configured. Please review configuration.';
				}

				// There's no hamli integration? Disabled for now.
				/*else {
					// Lookup using hamli
					$this->load->library('hamli');

					$callsign['callsign'] = $this->hamli->callsign($id);
				}*/

				if (isset($callsign['callsign']['gridsquare'])) {
					$this->load->model('logbook_model');
					$callsign['grid_worked'] = $this->logbook_model->check_if_grid_worked_in_logbook(strtoupper(substr($callsign['callsign']['gridsquare'],0,4)), null, $band)->num_rows();
				}
				if (isset($callsign['callsign']['error'])) {
					$callsign['error'] = $callsign['callsign']['error'];
				}

				$callsign['id'] = strtoupper($id);
				$callsign['lotw_lastupload'] = $this->logbook_model->check_last_lotw($id);
				return $this->load->view('search/result', $callsign, true);
		}
	}

	function search_result($id="", $id2="") {
		$this->load->model('user_model');
		$this->load->model('logbook_model');

		if(!$this->user_model->authorize($this->config->item('auth_mode'))) { return; }

		$fixedid = $id;

		if ($id2 != "") {
			if (strlen($id2)>3) {	// Last Element longer than 3 chars? Take that as call
				$fixedid = $id2;
			} else {		// Last Element up to 3 Chars? Take first element as Call
				$fixedid = $id;
			}
		}

		$query = $this->querydb($fixedid);

		if ($query->num_rows() == 0) {
			$query = $this->querydb($id);

			if ($query->num_rows() > 0) {
				$data['results'] = $query;
				$this->load->view('view_log/partial/log_ajax.php', $data);
			} else {
				$this->load->model('search');

				$iota_search = $this->search->callsign_iota($id);

				if ($iota_search->num_rows() > 0) {
					$data['results'] = $iota_search;
					$this->load->view('view_log/partial/log_ajax.php', $data);
				} else {
					if ($this->config->item('callbook') == "qrz" && $this->config->item('qrz_username') != null && $this->config->item('qrz_password') != null) {
						// Lookup using QRZ
						$this->load->library('qrz');

						if(!$this->session->userdata('qrz_session_key')) {
							$qrz_session_key = $this->qrz->session($this->config->item('qrz_username'), $this->config->item('qrz_password'));
							$this->session->set_userdata('qrz_session_key', $qrz_session_key);
						}

						$data['callsign'] = $this->qrz->search($id, $this->session->userdata('qrz_session_key'), $this->config->item('use_fullname'));
						if (isset($data['callsign']['gridsquare'])) {
							$data['grid_worked'] = $this->logbook_model->check_if_grid_worked_in_logbook(strtoupper(substr($data['callsign']['gridsquare'],0,4)), null, $this->session->userdata('user_default_band'))->num_rows();
						}
						if (isset($data['callsign']['dxcc'])) {
							$entity = $this->logbook_model->get_entity($data['callsign']['dxcc']);
							$data['callsign']['dxcc_name'] = $entity['name'];
							$data['dxcc_worked'] = $this->logbook_model->check_if_dxcc_worked_in_logbook($data['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
							$data['dxcc_confirmed'] = $this->logbook_model->check_if_dxcc_cnfmd_in_logbook($data['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
						}
						if (isset($data['callsign']['error'])) {
							$data['error'] = $data['callsign']['error'];
						}
					} else if ($this->config->item('callbook') == "hamqth" && $this->config->item('hamqth_username') != null && $this->config->item('hamqth_password') != null) {
						// Load the HamQTH library
						$this->load->library('hamqth');

						if(!$this->session->userdata('hamqth_session_key')) {
							$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
							$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
						}

						$data['callsign'] = $this->hamqth->search($id, $this->session->userdata('hamqth_session_key'));

						// If HamQTH session has expired, start a new session and retry the search.
						if($data['callsign']['error'] == "Session does not exist or expired") {
							$hamqth_session_key = $this->hamqth->session($this->config->item('hamqth_username'), $this->config->item('hamqth_password'));
							$this->session->set_userdata('hamqth_session_key', $hamqth_session_key);
							$data['callsign'] = $this->hamqth->search($id, $this->session->userdata('hamqth_session_key'));
						}
						if (isset($data['callsign']['gridsquare'])) {
							$data['grid_worked'] = $this->logbook_model->check_if_grid_worked_in_logbook(strtoupper(substr($data['callsign']['gridsquare'],0,4)), null, $this->session->userdata('user_default_band'))->num_rows();
						}
						if (isset($data['callsign']['dxcc'])) {
							$entity = $this->logbook_model->get_entity($data['callsign']['dxcc']);
							$data['callsign']['dxcc_name'] = $entity['name'];
							$data['dxcc_worked'] = $this->logbook_model->check_if_dxcc_worked_in_logbook($data['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
							$data['dxcc_confirmed'] = $this->logbook_model->check_if_dxcc_cnfmd_in_logbook($data['callsign']['dxcc'], null, $this->session->userdata('user_default_band'));
						}
						if (isset($data['callsign']['error'])) {
							$data['error'] = $data['callsign']['error'];
						}
					} else {
						$data['error'] = 'Lookup not configured. Please review configuration.';
					} /*else {
						// Lookup using hamli
						$this->load->library('hamli');

						$data['callsign'] = $this->hamli->callsign($id);
					}*/

					$data['id'] = strtoupper($id);
					$data['lotw_lastupload'] = $this->logbook_model->check_last_lotw($id);

					$this->load->view('search/result', $data);
				}
			}
		} else {
			$data['results'] = $query;
			$this->load->view('view_log/partial/log_ajax.php', $data);
		}
	}

	function querydb($id) {
		$this->db->from($this->config->item('table_name'));
		$this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
		$this->db->join('dxcc_entities', 'dxcc_entities.adif = '.$this->config->item('table_name').'.COL_DXCC', 'left outer');
		$this->db->join('lotw_users', 'lotw_users.callsign = '.$this->config->item('table_name').'.col_call', 'left outer');
		$this->db->group_start();
		$this->db->like(''.$this->config->item('table_name').'.COL_CALL', $id);
		$this->db->or_like(''.$this->config->item('table_name').'.COL_GRIDSQUARE', $id);
		$this->db->or_like(''.$this->config->item('table_name').'.COL_VUCC_GRIDS', $id);
		$this->db->group_end();
		$this->db->where('station_profile.user_id', $this->session->userdata('user_id'));
		$this->db->order_by(''.$this->config->item('table_name').'.COL_TIME_ON', 'desc');
		return $this->db->get();
  }

	function search_lotw_unconfirmed($station_id) {
		$clean_station_id = $this->security->xss_clean($station_id);

		if (!is_numeric($clean_station_id) && $clean_station_id !== 'All') {
			show_404();
		}

		$this->load->model('user_model');

		if(!$this->user_model->authorize($this->config->item('auth_mode'))) { return; }

		$this->load->model('stations');
		$logbooks_locations_array = $this->stations->all_of_user();

		$station_ids = array();

		if ($logbooks_locations_array->num_rows() > 0){
			foreach ($logbooks_locations_array->result() as $row) {
				array_push($station_ids, $row->station_id);
			}
		} else {
			return null;
		}

		$location_list = "'".implode("','",$station_ids)."'";

		$sql = 'select COL_CALL, COL_MODE, COL_SUBMODE, station_callsign, COL_SAT_NAME, COL_BAND, COL_TIME_ON, lotw_users.lastupload from ' . $this->config->item('table_name') .
		' join station_profile on ' . $this->config->item('table_name') . '.station_id = station_profile.station_id
		join lotw_users on ' . $this->config->item('table_name') . '.col_call = lotw_users.callsign
		where ' . $this->config->item('table_name') .'.station_id in ('. $location_list . ')';

		if ($station_id != 'All') {
			$sql .= ' and station_profile.station_id = ' . $station_id;
		}

		$sql .= " and COL_LOTW_QSL_RCVD <> 'Y' and " . $this->config->item('table_name') . ".COL_TIME_ON < lotw_users.lastupload";

		$query = $this->db->query($sql);

		$data['qsos'] = $query;

		$this->load->view('search/lotw_unconfirmed_result.php', $data);

	}

	function search_incorrect_cq_zones($station_id) {
		$clean_station_id = $this->security->xss_clean($station_id);

		if (!is_numeric($clean_station_id) && $clean_station_id !== 'All') {
			show_404();
		}

		$this->load->model('user_model');

		if(!$this->user_model->authorize($this->config->item('auth_mode'))) { return; }

		$this->load->model('stations');
		$logbooks_locations_array = $this->stations->all_of_user();

		$station_ids = array();

		if ($logbooks_locations_array->num_rows() > 0){
			foreach ($logbooks_locations_array->result() as $row) {
				array_push($station_ids, $row->station_id);
			}
		} else {
			return null;
		}

		$location_list = "'".implode("','",$station_ids)."'";

		$sql = 'select *, (select group_concat(distinct cqzone order by cqzone) from dxcc_master where countrycode = thcv.col_dxcc and cqzone <> \'\' order by cqzone asc) as correctcqzone from ' . $this->config->item('table_name') .
		' thcv join station_profile on thcv.station_id = station_profile.station_id where thcv.station_id in ('. $location_list . ')
		and not exists (select 1 from dxcc_master where countrycode = thcv.col_dxcc and cqzone = col_cqz) and col_dxcc > 0
		';

		$params = [];

		if ($clean_station_id != 'All') {
			$sql .= ' and station_profile.station_id = ?';
			$params[] = $clean_station_id;
		}

		$query = $this->db->query($sql, $params);

		$data['qsos'] = $query;

		$this->load->view('search/cqzones_result.php', $data);
	}

	function search_incorrect_itu_zones($station_id) {
		$clean_station_id = $this->security->xss_clean($station_id);

		if (!is_numeric($clean_station_id) && $clean_station_id !== 'All') {
			show_404();
		}

		$this->load->model('user_model');

		if(!$this->user_model->authorize($this->config->item('auth_mode'))) { return; }

		$this->load->model('stations');
		$logbooks_locations_array = $this->stations->all_of_user();

		$station_ids = array();

		if ($logbooks_locations_array->num_rows() > 0){
			foreach ($logbooks_locations_array->result() as $row) {
				array_push($station_ids, $row->station_id);
			}
		} else {
			return null;
		}

		$location_list = "'".implode("','",$station_ids)."'";

		$sql = "select *, (select group_concat(distinct ituzone order by ituzone) from dxcc_master where countrycode = thcv.col_dxcc and ituzone <> '' order by ituzone asc) as correctituzone from " . $this->config->item('table_name') .
		" thcv join station_profile on thcv.station_id = station_profile.station_id where thcv.station_id in (". $location_list . ")
		and not exists (select 1 from dxcc_master where countrycode = thcv.col_dxcc and ituzone = col_ituz) and col_dxcc > 0
		";

		$params = [];

		if ($clean_station_id != 'All') {
			$sql .= ' and station_profile.station_id = ?';
			$params[] = $clean_station_id;
		}

		$sql .= " order by thcv.col_time_on desc
		limit 1000";

		$query = $this->db->query($sql, $params);

		$data['qsos'] = $query;

		$this->load->view('search/ituzones_result.php', $data);
	}

	/*
	 * Provide a dxcc search, returning results json encoded
	 */
	function dxcheck($call = "", $date = "") {
		$this->load->model("logbook_model");
		if ($date == ''){
			$date = date("Y-m-d");
		}
		$ans = $this->logbook_model->dxcc_lookup($call, $date);
		return $ans;
	}

    function getentity($adif) {
        $this->load->model("logbook_model");

        $entity = $this->logbook_model->get_entity($adif);
        return $entity;
    }


	/* return station bearing */
	function searchbearing() {
			$locator = xss_clean($this->input->post('grid'));
			$ant_path = xss_clean($this->input->post('ant_path')) == '' ? NULL : xss_clean($this->input->post('ant_path'));
			$station_id = xss_clean($this->input->post('stationProfile'));
			if(!$this->load->is_loaded('Qra')) {
			    $this->load->library('Qra');
		    }

			if($locator != null) {
				if (isset($station_id)) {
					// be sure that station belongs to user
					$this->load->model('Stations');
					if (!$this->Stations->check_station_is_accessible($station_id)) {
						return "";
					}

					// get station profile
					$station_profile = $this->Stations->profile_clean($station_id);

					// get locator
					$mylocator = $station_profile->station_gridsquare;
				} else if($this->session->userdata('user_locator') != null){
					$mylocator = $this->session->userdata('user_locator');
				} else {
					$mylocator = $this->config->item('locator');
				}

				if ($this->session->userdata('user_measurement_base') == NULL) {
					$measurement_base = $this->config->item('measurement_base');
				}
				else {
					$measurement_base = $this->session->userdata('user_measurement_base');
				}

				$bearing = $this->qra->bearing($mylocator, $locator, $measurement_base, $ant_path);

				echo $bearing;
			}
			return "";
	}

	/* return distance */
	function searchdistance() {
			$locator = xss_clean($this->input->post('grid'));
			$ant_path = xss_clean($this->input->post('ant_path')) == '' ? NULL : xss_clean($this->input->post('ant_path'));
			$station_id = xss_clean($this->input->post('stationProfile'));
			if(!$this->load->is_loaded('Qra')) {
			    $this->load->library('Qra');
		    }

			if($locator != null) {
				if (isset($station_id)) {
					// be sure that station belongs to user
					$this->load->model('Stations');
					if (!$this->Stations->check_station_is_accessible($station_id)) {
						return 0;
					}

					// get station profile
					$station_profile = $this->Stations->profile_clean($station_id);

					// get locator
					$mylocator = $station_profile->station_gridsquare;
				} else if($this->session->userdata('user_locator') != null){
					$mylocator = $this->session->userdata('user_locator');
				} else {
					$mylocator = $this->config->item('locator');
				}

				$distance = $this->qra->distance($mylocator, $locator, 'K', $ant_path);

				echo $distance;
			}
			return 0;
	}

	/* return station bearing */
	function bearing($locator, $unit = 'M', $station_id = null, $ant_path = null) {
		if(!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}

		if($locator != null) {
			if (isset($station_id)) {
				// be sure that station belongs to user
				$this->load->model('Stations');
				if (!$this->Stations->check_station_is_accessible($station_id)) {
					return "";
				}

				// get station profile
				$station_profile = $this->Stations->profile_clean($station_id);

				// get locator
				$mylocator = $station_profile->station_gridsquare;
			} else if($this->session->userdata('user_locator') != null){
				$mylocator = $this->session->userdata('user_locator');
			} else {
				$mylocator = $this->config->item('locator');
			}

			$bearing = $this->qra->bearing($mylocator, $locator, $unit, $ant_path);

			return $bearing;
		}
		return "";
	}

	/* return distance */
	function distance($locator, $station_id = null, $ant_path = null) {
			$distance = 0;
			if(!$this->load->is_loaded('Qra')) {
			    $this->load->library('Qra');
		    }

			if($locator != null) {
				if (isset($station_id)) {
					// be sure that station belongs to user
					$this->load->model('Stations');
					if (!$this->Stations->check_station_is_accessible($station_id)) {
						return 0;
					}

					// get station profile
					$station_profile = $this->Stations->profile_clean($station_id);

					// get locator
					$mylocator = $station_profile->station_gridsquare;
				} else if($this->session->userdata('user_locator') != null){
					$mylocator = $this->session->userdata('user_locator');
				} else {
					$mylocator = $this->config->item('locator');
				}

				$distance = $this->qra->distance($mylocator, $locator, 'K', $ant_path);

			}
			return $distance;
	}

	function qralatlng($qra) {
		if(!$this->load->is_loaded('Qra')) {
			    $this->load->library('Qra');
		    }
		$latlng = $this->qra->qra2latlong($qra);
		return $latlng;
	}

	function qralatlngjson() {
		$qra = xss_clean($this->input->post('qra'));
		if(!$this->load->is_loaded('Qra')) {
			    $this->load->library('Qra');
		    }
		$latlng = $this->qra->qra2latlong($qra);
		print json_encode($latlng);
	}

    function get_qsos($num, $offset) {
        $this->db->select(''.$this->config->item('table_name').'.COL_CALL, '.$this->config->item('table_name').'.COL_BAND, '.$this->config->item('table_name').'.COL_TIME_ON, '.$this->config->item('table_name').'.COL_RST_RCVD, '.$this->config->item('table_name').'.COL_RST_SENT, '.$this->config->item('table_name').'.COL_MODE, '.$this->config->item('table_name').'.COL_SUBMODE, '.$this->config->item('table_name').'.COL_NAME, '.$this->config->item('table_name').'.COL_COUNTRY, '.$this->config->item('table_name').'.COL_PRIMARY_KEY, '.$this->config->item('table_name').'.COL_SAT_NAME, '.$this->config->item('table_name').'.COL_GRIDSQUARE, '.$this->config->item('table_name').'.COL_QSL_RCVD, '.$this->config->item('table_name').'.COL_EQSL_QSL_RCVD, '.$this->config->item('table_name').'.COL_EQSL_QSL_SENT, '.$this->config->item('table_name').'.COL_QSL_SENT, '.$this->config->item('table_name').'.COL_STX, '.$this->config->item('table_name').'.COL_STX_STRING, '.$this->config->item('table_name').'.COL_SRX, '.$this->config->item('table_name').'.COL_SRX_STRING, '.$this->config->item('table_name').'.COL_LOTW_QSL_SENT, '.$this->config->item('table_name').'.COL_LOTW_QSL_RCVD, '.$this->config->item('table_name').'.COL_VUCC_GRIDS, station_profile.*');
        $this->db->from($this->config->item('table_name'));

        $this->db->join('station_profile', 'station_profile.station_id = '.$this->config->item('table_name').'.station_id');
        $this->db->order_by(''.$this->config->item('table_name').'.COL_TIME_ON', "desc");

        $this->db->limit($num);
        $this->db->offset($offset);

        return $this->db->get();
    }

	function part_table_header_col($ctx, $name) {
		$ret='';
		switch($name) {
		case 'Mode': $ret.= '<th>'.__("Mode").'</th>'; break;
		case 'RSTS': $ret.= '<th class="d-none d-sm-table-cell">'.__("RST (S)").'</th>'; break;
		case 'RSTR': $ret.= '<th class="d-none d-sm-table-cell">'.__("RST (R)").'</th>'; break;
		case 'Country': $ret.= '<th>'.__("Country").'</th>'; break;
		case 'IOTA': $ret.= '<th>'.__("IOTA").'</th>'; break;
		case 'SOTA': $ret.= '<th>'.__("SOTA").'</th>'; break;
		case 'WWFF': $ret.= '<th>'.__("WWFF").'</th>'; break;
		case 'POTA': $ret.= '<th>'.__("POTA").'</th>'; break;
		case 'State': $ret.= '<th>'.__("State").'</th>'; break;
		case 'Grid': $ret.= '<th>'.__("Gridsquare").'</th>'; break;
		case 'Distance': $ret.= '<th>'.__("Distance").'</th>'; break;
		case 'Band': $ret.= '<th>'.__("Band").'</th>'; break;
		case 'Frequency': $ret.= '<th>'.__("Frequency").'</th>'; break;
		case 'Operator': $ret.= '<th>'.__("Operator").'</th>'; break;
		}
		return $ret;
	}

	function part_QrbCalcLink($mygrid, $grid, $vucc) {
		$ret='';
		if (!empty($grid)) {
			$ret.= $grid . ' <a href="javascript:spawnQrbCalculator(\'' . $mygrid . '\',\'' . $grid . '\')"><i class="fas fa-globe"></i></a>';
		} else if (!empty($vucc)) {
			$ret.= $vucc .' <a href="javascript:spawnQrbCalculator(\'' . $mygrid . '\',\'' . $vucc . '\')"><i class="fas fa-globe"></i></a>';
		}
		return $ret;
	}

	function part_table_col($row, $name) {
		$ret='';
		switch($name) {
		case 'Mode':    $ret.= '<td>'; $ret.= $row->COL_SUBMODE==null?$row->COL_MODE:$row->COL_SUBMODE . '</td>'; break;
		case 'RSTS':    $ret.= '<td class="d-none d-sm-table-cell">' . $row->COL_RST_SENT; if ($row->COL_STX) { $ret.= ' <span data-bs-toggle="tooltip" title="'.($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">'; $ret.=sprintf("%03d", $row->COL_STX); $ret.= '</span>';} if ($row->COL_STX_STRING) { $ret.= ' <span data-bs-toggle="tooltip" title="'.($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">' . $row->COL_STX_STRING . '</span>';} $ret.= '</td>'; break;
		case 'RSTR':    $ret.= '<td class="d-none d-sm-table-cell">' . $row->COL_RST_RCVD; if ($row->COL_SRX) { $ret.= ' <span data-bs-toggle="tooltip" title="'.($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">'; $ret.=sprintf("%03d", $row->COL_SRX); $ret.= '</span>';} if ($row->COL_SRX_STRING) { $ret.= ' <span data-bs-toggle="tooltip" title="'.($row->COL_CONTEST_ID!=""?$row->COL_CONTEST_ID:"n/a").'" class="badge text-bg-light">' . $row->COL_SRX_STRING . '</span>';} $ret.= '</td>'; break;
		case 'Country': $ret.= '<td>' . ucwords(strtolower(($row->COL_COUNTRY ?? ''))); if ($row->end ?? '' != '') $ret.= ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span>'  . '</td>'; break;
		case 'IOTA':    $ret.= '<td>' . ($row->COL_IOTA) . '</td>'; break;
		case 'SOTA':    $ret.= '<td>' . ($row->COL_SOTA_REF) . '</td>'; break;
		case 'WWFF':    $ret.= '<td>' . ($row->COL_WWFF_REF) . '</td>'; break;
		case 'POTA':    $ret.= '<td>' . ($row->COL_POTA_REF) . '</td>'; break;
		case 'Grid':    $ret.= '<td>' . $this->part_QrbCalcLink($row->COL_MY_GRIDSQUARE, $row->COL_VUCC_GRIDS, $row->COL_GRIDSQUARE) . '</td>'; break;
		case 'Distance':    $ret.= '<td>' . (($row->COL_DISTANCE ?? '' != '') ? $row->COL_DISTANCE . '&nbsp;km' : '') . '</td>'; break;
		case 'Band':    $ret.= '<td>'; if($row->COL_SAT_NAME != null) { $ret.= '<a href="https://db.satnogs.org/search/?q='.$row->COL_SAT_NAME.'" target="_blank">'.$row->COL_SAT_NAME.'</a></td>'; } else { $ret.= strtolower($row->COL_BAND); } $ret.= '</td>'; break;
		case 'Frequency':    $ret.= '<td>'; if($row->COL_SAT_NAME != null) { $ret.= '<a href="https://db.satnogs.org/search/?q='.$row->COL_SAT_NAME.'" target="_blank">'.$row->COL_SAT_NAME.'</a></td>'; } else { if($row->COL_FREQ != null) { $ret.= $this->frequency->qrg_conversion($row->COL_FREQ); } else { $ret.= strtolower($row->COL_BAND); } } $ret.= '</td>'; break;
		case 'State':   $ret.= '<td>' . ($row->COL_STATE) . '</td>'; break;
		case 'Operator': $ret.= '<td>' . ($row->COL_OPERATOR) . '</td>'; break;
		}
		return $ret;
	}
}
