<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class API extends CI_Controller {

	// Do absolutely nothing
	function index()
	{
		echo "nothing to see";
	}

	function help()
	{
		$this->load->model('user_model');

		// Check if users logged in

		if($this->user_model->validate_session() == 0) {
			// user is not logged in
			redirect('user/login');
		}

		$this->load->model('api_model');

		$data['api_keys'] = $this->api_model->keys();

		$data['page_title'] = __("API");

		$this->load->view('interface_assets/header', $data);
		$this->load->view('api/help');
		$this->load->view('interface_assets/footer');
	}


	function edit($key) {
		$this->load->model('user_model');

		// Check if users logged in

		if($this->user_model->validate_session() == 0) {
			// user is not logged in
			redirect('user/login');
		}

		$this->load->model('api_model');

		$this->load->helper(array('form', 'url'));

        $this->load->library('form_validation');

        $this->form_validation->set_rules('api_desc', __("API Description"), 'required');
        $this->form_validation->set_rules('api_key', __("API Key is required. Do not change this field"), 'required');

        $data['api_info'] = $this->api_model->key_description($key);

        if ($this->form_validation->run() == FALSE)
        {
  	      	$data['page_title'] = __("Edit API Description");

			$this->load->view('interface_assets/header', $data);
			$this->load->view('api/description');
			$this->load->view('interface_assets/footer');
		}
		else
		{
			// Success!

			$this->api_model->update_key_description($this->input->post('api_key'), $this->input->post('api_desc'));

			$this->session->set_flashdata('notice', sprintf(__("API Key %s description has been updated."), "<b>".$this->input->post('api_key')."</b>"));

			redirect('api/help');
		}

	}

	function generate($rights) {
		$this->load->model('user_model');

		// Check if users logged in

		if($this->user_model->validate_session() == 0) {
			// user is not logged in
			redirect('user/login');
		}


		$this->load->model('api_model');

		$data['api_keys'] = $this->api_model->generate_key($rights);

		redirect('api/help');
	}

	function delete($key) {
		$this->load->model('user_model');

		// Check if users logged in

		if($this->user_model->validate_session() == 0) {
			// user is not logged in
			redirect('user/login');
		}


		$this->load->model('api_model');

		$this->api_model->delete_key($key);

		$this->session->set_flashdata('notice', sprintf(__("API Key %s has been deleted"), "<b>".$key."</b>" ));

		redirect('api/help');
	}

	// Example of authing
	function auth($key) {
		$this->load->model('api_model');
			header("Content-type: text/xml");
		if($this->api_model->access($key) == "No Key Found" || $this->api_model->access($key) == "Key Disabled") {
			echo "<auth>";
			echo "<message>Key Invalid - either not found or disabled</message>";
			echo "</auth>";
		} else {
			echo "<auth>";
			echo "<status>Valid</status>";
			echo "<rights>".$this->api_model->access($key)."</rights>";
			echo "</auth>";
			$this->api_model->update_last_used($key);
		}
	}

	function station_info($key) {
		$this->load->model('api_model');
		$this->load->model('stations');
		header("Content-type: application/json");
		if(substr($this->api_model->access($key),0,1) == 'r') { /* Check permission for reading */
			$this->api_model->update_last_used($key);
			$userid = $this->api_model->key_userid($key);
			$station_ids = array();
			$stations=$this->stations->all_of_user($userid);
			foreach ($stations->result() as $row) {
				$result['station_id']=$row->station_id;
				$result['station_profile_name']=$row->station_profile_name;
				$result['station_gridsquare']=$row->station_gridsquare;
				$result['station_callsign']=$row->station_callsign;;
				$result['station_active']=$row->station_active;
				array_push($station_ids, $result);
			}
			echo json_encode($station_ids);
		} else {
			http_response_code(401);
			echo json_encode(['status' => 'failed', 'reason' => "missing or invalid api key"]);
		}
	}


	/*
	*
	*	Function: QSO
	*	Task: allows passing of ADIF data to Wavelog
	*/
	function qso($dryrun = false) {
		header('Content-type: application/json');
		set_time_limit(0);
		ini_set('memory_limit', '-1');

		session_write_close();
		$this->load->model('api_model');

		$this->load->model('stations');

		$return_msg = array();
		$return_count = 0;

		// Decode JSON and store
		$raw = file_get_contents("php://input");
		$obj = json_decode($raw,true);
		$raw='';
		if ($obj === NULL) {
		    echo json_encode(['status' => 'failed', 'reason' => "wrong JSON"]);
		    die();
		}

		if(!isset($obj['key']) || $this->api_model->authorize($obj['key']) == 0) {
		   http_response_code(401);
		   echo json_encode(['status' => 'failed', 'reason' => "missing api key"]);
		   die();
		}

		$userid = $this->api_model->key_userid($obj['key']);
		$this->api_model->update_last_used(($obj['key']));

		if(!isset($obj['station_profile_id']) || $this->stations->check_station_against_user($obj['station_profile_id'], $userid) == false) {
			http_response_code(401);
			echo json_encode(['status' => 'failed', 'reason' => "station id does not belong to the API key owner."]);
			die();
		}

		if($obj['type'] == "adif" && $obj['string'] != "") {
			// Load the logbook model for adding QSO records
			$this->load->model('logbook_model');

			// Load ADIF Parser
			$this->load->library('adif_parser');

			// Feed in the ADIF string
			$this->adif_parser->feed($obj['string']);
			$obj['string']='';
			$return_msg=[];
			$return_count=0;
			if( !($dryrun) && (isset($obj['station_profile_id']))) {
				$custom_errors = "";
				$alladif=[];
				gc_collect_cycles();
				while($record = $this->adif_parser->get_record()) {
					if(!(isset($record['call'])) || (trim($record['call']) == '')) {
						continue;
					}
					if(count($record) == 0) {
						break;
					};
					array_push($alladif,$record);
					$return_count++;
				};
				$record='';	// free memory
				gc_collect_cycles();
				$custom_errors = $this->logbook_model->import_bulk($alladif, $obj['station_profile_id'], false, false, false, false, false, false, true, false, true, false);
				$alladif=[];
				$return_msg[]='';
			} else {
				$return_msg[]='Dryrun works';
			}

			http_response_code(201);
			echo json_encode(['status' => 'created', 'type' => $obj['type'], 'string' => $obj['string'], 'imported_count' => $return_count, 'messages' => $return_msg ]);

		}

	}

	/*
	*
	*	Function: get_contacts_adif
	*	Task: allows third party software to pull ADIF QSO data from wavelog after a baseline of the last fetched QSO id
	*/
	function get_contacts_adif() {

		//set header
		header('Content-type: application/json');

		//load API model
		$this->load->model('api_model');

		// Decode JSON and store
		$obj = json_decode(file_get_contents("php://input"), true);
		if ($obj === NULL) {
		    http_response_code(400);
			echo json_encode(['status' => 'failed', 'reason' => "wrong JSON"]);
			return;
		}

		//do authorization
		if(!isset($obj['key']) || $this->api_model->authorize($obj['key']) == 0) {
		   http_response_code(401);
		   echo json_encode(['status' => 'failed', 'reason' => "missing api key"]);
			return;
		}

		//check for relevant fields in JSON input
		if(!isset($obj['station_id']) or !isset($obj['fetchfromid']))
		{
			http_response_code(400);
			echo json_encode(['status' => 'failed', 'reason' => "Not all required fields were present in input JSON"]);
			return;
		}

		//extract relevant data to variables
		$key = $obj['key'];
		$station_id = $obj['station_id'];
		$fetchfromid = $obj['fetchfromid'];

		//check if goalpost is numeric as an additional layer of SQL injection prevention
		if(!is_numeric($fetchfromid))
		{
			http_response_code(400);
			echo json_encode(['status' => 'failed', 'reason' => "Invalid fetchfromid."]);
			return;
		}

		//make sure the goalpost is an integer
		$fetchfromid = (int)$fetchfromid;

		//load stations API
		$this->load->model('stations');

		//get all stations of user to check if station_id should be readable
		$userid = $this->api_model->key_userid($key);
		$station_ids = array();
		$stations=$this->stations->all_of_user($userid);

		//extract to array
		foreach ($stations->result() as $row) {
			array_push($station_ids, $row->station_id);
		}

		//return error if station not accessible for the API key
		if(!in_array($station_id, $station_ids))
		{
			http_response_code(401);
	 	   	echo json_encode(['status' => 'failed', 'reason' => "Station ID not accessible for this API key"]);
			return;
		}

		//load adif data module
		$this->load->model('adif_data');

		//get qso data
		$data['qsos'] = $this->adif_data->export_past_id($station_id, $fetchfromid);
		
		//set internalonly attribute for adif creation
		$data['internalrender'] = true;
		
		//if no new QSOs are ready, return that
		$qso_count = count($data['qsos']->result()); 
		if($qso_count <= 0)
		{
			http_response_code(200);
			echo json_encode(['status' => 'successfull', 'message' => 'No new QSOs available.', 'lastfetchedid' => $fetchfromid, 'exported_qsos' => 0, 'adif' => null]);
			return;
		}

		//convert data to ADIF
		$adif_content = $this->load->view('adif/data/exportall', $data, TRUE);

		//get new goalpost
		$lastfetchedid = 0;
		foreach ($data['qsos']->result() as $row) {
			$lastfetchedid = max($lastfetchedid, $row->COL_PRIMARY_KEY);
		}		

		//return API result
		http_response_code(200);
		echo json_encode(['status' => 'successfull', 'message' => 'Export successfull', 'lastfetchedid' => $lastfetchedid, 'exported_qsos' => $qso_count, 'adif' => $adif_content]);
	}


	// API function to check if a callsign is in the logbook already
	function logbook_check_callsign() {
		header('Content-type: application/json');

		$this->load->model('api_model');

		// Decode JSON and store
		$obj = json_decode(file_get_contents("php://input"), true);
		if ($obj === NULL) {
		    echo json_encode(['status' => 'failed', 'reason' => "wrong JSON"]);
			return;
		}

		if(!isset($obj['key']) || $this->api_model->authorize($obj['key']) == 0) {
		   http_response_code(401);
		   echo json_encode(['status' => 'failed', 'reason' => "missing api key"]);
			return;
		}

		if(!isset($obj['logbook_public_slug']) || !isset($obj['callsign'])) {
		   http_response_code(401);
		   echo json_encode(['status' => 'failed', 'reason' => "missing fields"]);
			return;
		}

		if($obj['logbook_public_slug'] != "" && $obj['callsign'] != "") {

			$logbook_slug = $obj['logbook_public_slug'];
			$callsign = $obj['callsign'];

			// If $obj['band'] exists
			if(isset($obj['band'])) {
				$band = $obj['band'];
			} else {
				$band = null;
			}

			$this->load->model('logbooks_model');

			if($this->logbooks_model->public_slug_exists($logbook_slug)) {
				$logbook_id = $this->logbooks_model->public_slug_exists_logbook_id($logbook_slug);
				if($logbook_id != false)
				{
					// Get associated station locations for mysql queries
					$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($logbook_id);

					if (!$logbooks_locations_array) {
						// Logbook not found
						http_response_code(404);
						echo json_encode(['status' => 'failed', 'reason' => "Empty Logbook"]);
						die();
					}
				} else {
					// Logbook not found
					http_response_code(404);
					echo json_encode(['status' => 'failed', 'reason' => $logbook_slug." has no associated station locations"]);
					die();
				}
				// Search Logbook for callsign
				$this->load->model('logbook_model');

				$result = $this->logbook_model->check_if_callsign_worked_in_logbook($callsign, $logbooks_locations_array, $band);

				http_response_code(201);
				if($result > 0)
				{
					echo json_encode(['callsign' => $callsign, 'result' => 'Found']);
				} else {
					echo json_encode(['callsign' => $callsign, 'result' => 'Not Found']);
				}
			} else {
				// Logbook not found
				http_response_code(404);
				echo json_encode(['status' => 'failed', 'reason' => "logbook not found"]);
				die();
			}

		}

	}

	// API function to check if a grid is in the logbook already
	function logbook_check_grid() {
		header('Content-type: application/json');

		$this->load->model('api_model');

		// Decode JSON and store
		$obj = json_decode(file_get_contents("php://input"), true);
		if ($obj === NULL) {
		    echo json_encode(['status' => 'failed', 'reason' => "wrong JSON"]);
		}

		if(!isset($obj['key']) || $this->api_model->authorize($obj['key']) == 0) {
		   http_response_code(401);
		   echo json_encode(['status' => 'failed', 'reason' => "missing api key"]);
		}

		if(!isset($obj['logbook_public_slug']) || !isset($obj['grid'])) {
		   http_response_code(401);
		   echo json_encode(['status' => 'failed', 'reason' => "missing fields"]);
			return;
		}

		if($obj['logbook_public_slug'] != "" && $obj['grid'] != "") {

			$logbook_slug = $obj['logbook_public_slug'];
			$grid = $obj['grid'];

			// If $obj['band'] exists
			if(isset($obj['band'])) {
				$band = $obj['band'];
			} else {
				$band = null;
			}

			// If $obj['cnfm'] exists
			if(isset($obj['cnfm'])) {
				$cnfm = $obj['cnfm'];
			} else {
				$cnfm = null;
			}

			$this->load->model('logbooks_model');

			if($this->logbooks_model->public_slug_exists($logbook_slug)) {
				$logbook_id = $this->logbooks_model->public_slug_exists_logbook_id($logbook_slug);
				if($logbook_id != false)
				{
					// Get associated station locations for mysql queries
					$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($logbook_id);

					if (!$logbooks_locations_array) {
						// Logbook not found
						http_response_code(404);
						echo json_encode(['status' => 'failed', 'reason' => "Empty Logbook"]);
						die();
					}
				} else {
					// Logbook not found
					http_response_code(404);
					echo json_encode(['status' => 'failed', 'reason' => $logbook_slug." has no associated station locations"]);
					die();
				}
				// Search Logbook for callsign
				$this->load->model('logbook_model');

				$query = $this->logbook_model->check_if_grid_worked_in_logbook($grid, $logbooks_locations_array, $band, $cnfm);
				http_response_code(201);
				if ($query->num_rows() == 0) {
					echo json_encode(['gridsquare' => strtoupper($grid), 'result' => 'Not Found']);
				} else if ($cnfm == null) {
					echo json_encode(['gridsquare' => strtoupper($grid), 'result' => 'Found']);
				} else {
					$arr = [];
					foreach($query->result() as $line) {
						$arr[] = $line->gridorcnfm;
					}
					if (in_array('Y', $arr)) {
						echo json_encode(['gridsquare' => strtoupper($grid), 'result' => 'Confirmed']);
					} else {
						echo json_encode(['gridsquare' => strtoupper($grid), 'result' => 'Worked']);
					}
				}

			} else {
				// Logbook not found
				http_response_code(404);
				echo json_encode(['status' => 'failed', 'reason' => "logbook not found"]);
				die();
			}

		}

	}

	/* ENDPOINT for Rig Control */

	function radio() {
		session_write_close();
		header('Content-type: application/json');

		$this->load->model('api_model');

		//$json = '{"radio":"FT-950","frequency":14075,"mode":"SSB","timestamp":"2012/04/07 16:47"}';

		$this->load->model('cat');

		//var_dump(file_get_contents("php://input"), true);

		// Decode JSON and store
		$obj = json_decode(file_get_contents("php://input"), true);

		if(!isset($obj['key']) || $this->api_model->authorize($obj['key']) == 0) {
			http_response_code(401);
			echo json_encode(['status' => 'failed', 'reason' => "missing api key"]);
			die();
		}

		if(!isset($obj['radio'])) {
			http_response_code(404);
			echo json_encode(['status' => 'failed', 'reason' => "missing radio element in payload"]);
			die();
		}

		$this->api_model->update_last_used($obj['key']);

		$user_id = $this->api_model->key_userid($obj['key']);

		// Special Case: Yaesu Radio's use CW-U and CW-L which aren't official ADIF Modes. We override this here to CW
		if (isset($obj['mode']) && (strtoupper($obj['mode']) == 'CW-U' || strtoupper($obj['mode']) == 'CW-L')) {
			$obj['mode'] = 'CW';
		}

		// Store Result to Database
		$this->cat->update($obj, $user_id);

		// Return Message

		$arr = array('status' => 'success');

		echo json_encode($arr);

	}

	/*
	*
	*	Stats API function calls
	*
	*/

	function statistics($key = null) {
		header('Content-type: application/json');
		$this->load->model('logbook_model');

		$data['todays_qsos'] = $this->logbook_model->todays_qsos(null, $key);
		$data['total_qsos'] = $this->logbook_model->total_qsos(null, $key);
		$data['month_qsos'] = $this->logbook_model->month_qsos(null, $key);
		$data['year_qsos'] = $this->logbook_model->year_qsos(null, $key);

		http_response_code(201);
		echo json_encode(['Today' => $data['todays_qsos'], 'total_qsos' => $data['total_qsos'], 'month_qsos' => $data['month_qsos'], 'year_qsos' => $data['year_qsos']]);

	}

	function lookup() {
		/*
		 *
		 *	Callsign lookup function for Wavelogs logging page or thirdparty systems
		 *	which want to show previous QSO data on their system.
		 *
		 */



		// Make sure users logged in
		$raw_input = json_decode(file_get_contents("php://input"), true);
		$this->load->model('user_model');
		if (!( $this->user_model->authorize($this->config->item('auth_mode') ))) {				// User not authorized?
			$no_auth=true;
			$this->load->model('api_model');
			if (!( ((isset($raw_input['key'])) && ($this->api_model->authorize($raw_input['key']) > 0) ))) {			// Key invalid?
				$no_auth=true;
			} else {
				$no_auth=false;
			}
			if ($no_auth) {
				http_response_code(401);
				echo json_encode(['status' => 'failed', 'reason' => "missing api key or session"]);
				die();
			}
		}

		$lookup_callsign = strtoupper($raw_input['callsign'] ?? '');
		if ($lookup_callsign ?? '' != '') {


			$this->load->model("logbook_model");
			$date = date("Y-m-d");

			// Return Array
			$return = [
				"callsign" => "",
				"dxcc" => false,
				"dxcc_id" => -1,
				"dxcc_lat" => "",
				"dxcc_long" => "",
				"dxcc_cqz" => "",
				"dxcc_flag" => "",
				"cont" => "",
				"name" => "",
				"gridsquare"  => "",
				"location"  => "",
				"iota_ref" => "",
				"state" => "",
				"us_county" => "",
				"qsl_manager" => "",
				"bearing" 		=> "",
				"workedBefore" => false,
				"lotw_member" => false,
				"suffix_slash" => "", // Suffix Slash aka Portable
			];


			/*
			 *
			 *	Handle Callsign field
			 *
			 */
			$return['callsign'] = $lookup_callsign;

			/*
			 *
			 *	Lookup DXCC and Suffix information
			 *
			 */

			$callsign_dxcc_lookup = $this->logbook_model->dxcc_lookup($lookup_callsign, $date);

			$last_slash_pos = strrpos($lookup_callsign, '/');

			if(isset($last_slash_pos) && $last_slash_pos > 4) {
				$suffix_slash = $last_slash_pos === false ? $lookup_callsign : substr($lookup_callsign, $last_slash_pos + 1);
				switch ($suffix_slash) {
				case "P":
					$suffix_slash_item = "Portable";
					break;
				case "M":
					$suffix_slash_item = "Mobile";
				case "MM":
					$suffix_slash_item =  "Maritime Mobile";
					break;
				default:
					// If its not one of the above suffix slashes its likely dxcc
					$ans2 = $this->logbook_model->dxcc_lookup($suffix_slash, $date);
					$suffix_slash_item = null;
				}

				$return['suffix_slash'] = $suffix_slash_item;
			}

			// If the final slash is a DXCC then find it!
			if (isset($ans2['call'])) {
				$return['dxcc_id'] = $ans2['adif'];
				$return['dxcc'] = $ans2['entity'];
				$return['dxcc_lat'] = $ans2['lat'];
				$return['dxcc_long'] = $ans2['long'];
				$return['dxcc_cqz'] = $ans2['cqz'];
				$return['cont'] = $ans2['cont'];
			} else {
				$return['dxcc_id'] = $callsign_dxcc_lookup['adif'] ?? '';
				$return['dxcc'] = $callsign_dxcc_lookup['entity'] ?? '';
				$return['dxcc_lat'] = $callsign_dxcc_lookup['lat'] ?? '';
				$return['dxcc_long'] = $callsign_dxcc_lookup['long'] ?? '';
				$return['dxcc_cqz'] = $callsign_dxcc_lookup['cqz'] ?? '';
				$return['cont'] = $callsign_dxcc_lookup['cont'] ?? '';
			}

			/*
			 *
			 *	Pool any local data we have for a callsign
			 *
			 */
			$call_lookup_results = $this->logbook_model->call_lookup_result($lookup_callsign);

			if($call_lookup_results != null)
			{
				$return['name'] = $call_lookup_results->COL_NAME;
				$return['gridsquare'] = $call_lookup_results->COL_GRIDSQUARE;
				$return['location'] = $call_lookup_results->COL_QTH;
				$return['iota_ref'] = $call_lookup_results->COL_IOTA;
				$return['qsl_manager'] = $call_lookup_results->COL_QSL_VIA;
				$return['state'] = $call_lookup_results->COL_STATE;
				$return['us_county'] = $call_lookup_results->COL_CNTY;
				$return['dxcc_id'] = $call_lookup_results->COL_DXCC;
				$return['cont'] = $call_lookup_results->COL_CONT;

				if ($return['gridsquare'] != "") {
					$return['latlng'] = $this->qralatlng($return['gridsquare']);
				}

			}

			if ($return['dxcc'] ?? '' != '') {
				$this->load->library('DxccFlag');
				$return['dxcc_flag']=$this->dxccflag->get($return['dxcc_id']);
			}

			$lotw_days=$this->logbook_model->check_last_lotw($lookup_callsign);
			if ($lotw_days != null) {
				$return['lotw_member']=$lotw_days;
			} else {
				$lotw_member="";
			}
			/*
			 *
			 *	Output Returned data
			 *
			 */
			echo json_encode($return, JSON_PRETTY_PRINT);
		} else {
			echo '{"error":"callsign to lookup not given"}';
		}
		return;
	}

	function qralatlng($qra) {
		if(!$this->load->is_loaded('Qra')) {
			$this->load->library('Qra');
		}
		$latlng = $this->qra->qra2latlong($qra);
		return $latlng;
	}
}
