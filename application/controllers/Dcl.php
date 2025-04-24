<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dcl extends CI_Controller {

	/* Controls who can access the controller and its functions */
	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
	}

	public function index() {
		$this->load->library('Permissions');
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		// Load required models for page generation
		$this->load->model('Dcl_model');

		// Get Array of the logged in users LoTW certs.
		$dclkeys=($this->Dcl_model->dcl_keys($this->session->userdata('user_id')) ?? '');
		$i=0;
		foreach ($dclkeys as $dclkey) {
			$data['dcl_keys'][$i] = json_decode($dclkey->option_value ?? '');
			$data['dcl_keys'][$i]->call = $dclkey->option_key ?? '';
			$i++;
		}

		// Set Page Title
		$data['page_title'] = __("DCL");

		$this->load->model('cron_model');
		$data['next_run'] = $this->cron_model->get_next_run("dcl_dcl_upload");

		// Load Views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('dcl_views/index');
		$this->load->view('interface_assets/footer');
	}

	/*
	|--------------------------------------------------------------------------
	| Function: cert_upload
	|--------------------------------------------------------------------------
	|
	| Nothing fancy just shows the cert_upload form for uploading p12 files
	|
	 */
	public function cert_upload() {
		$this->load->model('user_model');
		$this->load->model('dxcc');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		// Load DXCC Countrys List
		$data['dxcc_list'] = $this->dxcc->list();

		// Set Page Title
		$data['page_title'] = __("Logbook of the World");

		// Load Views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('lotw_views/upload_cert', array('error' => ' ' ));
		$this->load->view('interface_assets/footer');
	}


    /*
	|--------------------------------------------------------------------------
	| Function: lotw_upload
	|--------------------------------------------------------------------------
	|
	| This function Uploads to LoTW
	|
     */
	public function lotw_upload() {

		$this->load->model('user_model');
		$this->user_model->authorize(2);

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class.'_'.$this->router->method);

		// Get Station Profile Data
		$this->load->model('Stations');

		if ($this->user_model->authorize(2)) {
			if (!($this->config->item('disable_manual_lotw'))) {
				$station_profiles = $this->Stations->all_of_user($this->session->userdata('user_id'));
				$sync_user_id=$this->session->userdata('user_id');
			} else {
				echo "Manual syncing is disabled by configuration";
				redirect('dashboard');
				exit();
			}
		} else {
			$station_profiles = $this->Stations->all();
			$sync_user_id=null;
		}

		// Array of QSO IDs being Uploaded

		$qso_id_array = array();

		// Build TQ8 Outputs
		if ($station_profiles->num_rows() >= 1) {

			foreach ($station_profiles->result() as $station_profile) {

				// Get Certificate Data
				$this->load->model('Lotw_model');
				$data['station_profile'] = $station_profile;
				$data['lotw_cert_info'] = $this->Lotw_model->lotw_cert_details($station_profile->station_callsign, $station_profile->station_dxcc, $station_profile->user_id);

				// If Station Profile has no LoTW Cert continue on.
				if(!isset($data['lotw_cert_info']->cert_dxcc_id)) {
					echo $station_profile->station_callsign.": No LoTW certificate for station callsign found.<br>";
					continue;
				}

				// Check if LoTW certificate itself is valid
				// Validty of QSO dates will be checked later
				$current_date = date('Y-m-d H:i:s');
				if ($current_date <= $data['lotw_cert_info']->date_created) {
					echo $data['lotw_cert_info']->callsign.": LoTW certificate not valid yet!";
					continue;
				}
				if ($current_date >= $data['lotw_cert_info']->date_expires) {
					echo $data['lotw_cert_info']->callsign.": LoTW certificate expired!";
					continue;
				}

				// Get QSOs

				$this->load->model('Logbook_model');

				// First mark QSOs with unsupported propagation modes as ignore
				$this->Logbook_model->mark_lotw_ignore($data['station_profile']->station_id);

				$data['qsos'] = $this->Logbook_model->get_lotw_qsos_to_upload($data['station_profile']->station_id, $data['lotw_cert_info']->qso_start_date, $data['lotw_cert_info']->qso_end_date);

				// Nothing to upload
				if(empty($data['qsos']->result())){
					if ($this->user_model->authorize(2)) {	// Only be verbose if we have a session
						echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): No QSOs to upload.<br>";
					}
					continue;
				}

				foreach ($data['qsos']->result() as $temp_qso) {
					array_push($qso_id_array, $temp_qso->COL_PRIMARY_KEY);
				}

				// Build File to save
				$adif_to_save = $this->load->view('lotw_views/adif_views/adif_export', $data, TRUE);
				if (strpos($adif_to_save, '<SIGN_LOTW_V2.0:1:6>')) {
					// Signing failed
					echo "Signing failed.";
					continue;
				}

				// create folder to store upload file
				if (!file_exists('./uploads/lotw')) {
					mkdir('./uploads/lotw', 0775, true);
				}

				// Build Filename
				$filename_for_saving = './uploads/lotw/'.preg_replace('/[^a-z0-9]+/', '-', strtolower($data['lotw_cert_info']->callsign))."-".date("Y-m-d-H-i-s")."-wavelog.tq8";

				$gzdata = gzencode($adif_to_save, 9);
				$fp = fopen($filename_for_saving, "w");
				fwrite($fp, $gzdata);
				fclose($fp);

				//The URL that accepts the file upload.
				$url = 'https://lotw.arrl.org/lotw/upload';

				//The name of the field for the uploaded file.
				$uploadFieldName = 'upfile';

				//The full path to the file that you want to upload
				$filePath = realpath($filename_for_saving);

				//Initiate cURL
				$ch = curl_init();

				//Set the URL
				curl_setopt($ch, CURLOPT_URL, $url);

				//Set the HTTP request to POST
				curl_setopt($ch, CURLOPT_POST, true);

				//Tell cURL to return the output as a string.
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				//Use the recommended way, creating a CURLFile object.
				$uploadfile = curl_file_create($filePath);
				$uploadfile->setPostFilename(basename($filePath));

				//Setup our POST fields
				$postFields = array(
					$uploadFieldName => $uploadfile
				);

				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

				//Execute the request
				$result = curl_exec($ch);

				if(curl_errno($ch)){
					echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): Upload Failed - ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).")<br>";
					$this->Lotw_model->last_upload($data['lotw_cert_info']->lotw_cert_id, "Upload failed");
					if (curl_errno($ch) == 28) {  // break on timeout
						echo "Timeout reached. Stopping subsequent uploads.<br>";
						break;
					} else {
						continue;
					}
				}

				$pos = strpos($result, "<!-- .UPL.  accepted -->");

				if ($pos === false) {
					// Upload of TQ8 Failed for unknown reason
					echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): Upload Failed - ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).")<br>";
					$this->Lotw_model->last_upload($data['lotw_cert_info']->lotw_cert_id, "Upload failed");
					if (curl_errno($ch) == 28) {  // break on timeout
						echo "Timeout reached. Stopping subsequent uploads.<br>";
						break;
					} else {
						continue;
					}
				} else {
					// Upload of TQ8 was successfull

					echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): Upload Successful - ".$filename_for_saving."<br>";

					$this->Lotw_model->last_upload($data['lotw_cert_info']->lotw_cert_id, "Success");

					// Mark QSOs as Sent
					foreach ($qso_id_array as $qso_number) {
						$this->Logbook_model->mark_lotw_sent($qso_number);
					}
				}

				// Delete TQ8 File - This is done regardless of whether upload was succcessful
				unlink(realpath($filename_for_saving));
			}
		} else {
			echo "No Station Profiles found to upload to LoTW";
		}

			/*
			|	Download QSO Matches from LoTW
			 */
		if ($this->user_model->authorize(2)) {
			echo "<br><br>";
			$sync_user_id=$this->session->userdata('user_id');
		} else {
			$sync_user_id=null;
		}
		echo $this->lotw_download($sync_user_id);
	}

	public function delete_key($call) {
		$call=str_replace('_','/',xss_clean($call));
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('Dcl_model');
		$this->Dcl_model->delete_key($call);
		$this->session->set_flashdata('success', __("Key Deleted."));
		redirect('dcl');
	}


	/*
	|--------------------------------------------------------------------------
	| Function: loadFromFile
	|--------------------------------------------------------------------------
	|
	|	$filepath is the ADIF file, $display_view is used to hide the output if its internal script
	|
	|	Internal function that takes the LoTW ADIF and imports into the log
	|
	 */
	private function loadFromFile($filepath, $station_ids, $display_view = "TRUE") {

		// Figure out how we should be marking QSLs confirmed via LoTW
		$query = $this->db->query('SELECT lotw_rcvd_mark FROM config');
		$q = $query->row();
		$config['lotw_rcvd_mark'] = $q->lotw_rcvd_mark;

		ini_set('memory_limit', '-1');
		set_time_limit(0);

		if (!$this->load->is_loaded('adif_parser')) {
			$this->load->library('adif_parser');
		}

		$this->adif_parser->load_from_file($filepath);

		$this->adif_parser->initialize();

		$tableheaders = "<table width=\"100%\">";
		$tableheaders .= "<tr class=\"titles\">";
		$tableheaders .= "<td>Station Callsign</td>";
		$tableheaders .= "<td>QSO Date</td>";
		$tableheaders .= "<td>Call</td>";
		$tableheaders .= "<td>Mode</td>";
		$tableheaders .= "<td>LoTW QSL Received</td>";
		$tableheaders .= "<td>Date LoTW Confirmed</td>";
		$tableheaders .= "<td>State</td>";
		$tableheaders .= "<td>Gridsquare</td>";
		$tableheaders .= "<td>IOTA</td>";
		$tableheaders .= "<td>Log Status</td>";
		$tableheaders .= "<td>LoTW Status</td>";
		$tableheaders .= "</tr>";

		$table = "";
		while($record = $this->adif_parser->get_record()) {
			// Check for LoTW confirmation in ADIF record and skip if not existent
			if (!isset($record['app_lotw_rxqsl'])) {
				continue;
			}
			if (($record['call'] ?? '') == '') {	// Failsafe if no call is given
				continue;
			}
			if (($record['station_callsign'] ?? '') == '') {	// Failsafe if no station_callsign is given
				continue;
			}
			$time_on = date('Y-m-d', strtotime($record['qso_date'])) ." ".date('H:i', strtotime($record['time_on']));

			$qsl_date = date('Y-m-d H:i', strtotime($record['app_lotw_rxqsl']));

			if (isset($record['time_off'])) {
				$time_off = date('Y-m-d', strtotime($record['qso_date'])) ." ".date('H:i', strtotime($record['time_off']));
			} else {
				$time_off = date('Y-m-d', strtotime($record['qso_date'])) ." ".date('H:i', strtotime($record['time_on']));
			}

			// If we have a positive match from LoTW, record it in the DB according to the user's preferences
			if ($record['qsl_rcvd'] == "Y")
			{
				$record['qsl_rcvd'] = $config['lotw_rcvd_mark'];
			}

			// SAT-Name not given? Create array-key and fill with null
			if (!(array_key_exists('sat_name', $record))) {
				$record['sat_name']=null;
			}

			// Prop-Mode not given? Create array-key and fill with null
			if (!(array_key_exists('prop_mode', $record))) {
				$record['prop_mode']=null;
			}

			$status = $this->logbook_model->import_check($time_on, $record['call'], $record['band'], $record['mode'], $record['prop_mode'], $record['sat_name'], $record['station_callsign'], $station_ids);

			if($status[0] == "Found") {
				$qso_id4lotw=$status[1];
				if (isset($record['state'])) {
					$state = $record['state'];
				} else {
					$state = "";
				}
				// Present only if the QSLing station specified a single valid grid square value in its station location uploaded to LoTW.
				$qsl_gridsquare = "";
				if (isset($record['gridsquare'])) {
					if (strlen($record['gridsquare']) > strlen($status[2] ?? '') || substr(strtoupper($status[2] ?? ''), 0, 4) != substr(strtoupper($record['gridsquare']), 0, 4)) {
						$qsl_gridsquare = $record['gridsquare'];
					}
				}

				$ant_path = $status[3] ?? '';

				if (isset($record['vucc_grids'])) {
					$qsl_vucc_grids = $record['vucc_grids'];
				} else {
					$qsl_vucc_grids = "";
				}

				if (isset($record['iota'])) {
					$iota = $record['iota'];
				} else {
					$iota = "";
				}

				if (isset($record['cnty'])) {
					$cnty = $record['cnty'];
				} else {
					$cnty = "";
				}

				if (isset($record['cqz'])) {
					$cqz = $record['cqz'];
				} else {
					$cqz = "";
				}

				if (isset($record['ituz'])) {
					$ituz = $record['ituz'];
				} else {
					$ituz = "";
				}

				if (isset($record['dxcc'])) {
					$dxcc = $record['dxcc'];
				} else {
					$dxcc = "";
				}

				if (isset($record['country'])) {
					$country = $record['country'];
				} else {
					$country = "";
				}

				$lotw_status = $this->logbook_model->lotw_update($time_on, $record['call'], $record['band'], $qsl_date, $record['qsl_rcvd'], $state, $qsl_gridsquare, $qsl_vucc_grids, $iota, $cnty, $cqz, $ituz, $record['station_callsign'],$qso_id4lotw, $station_ids, $dxcc, $country, $ant_path);

				$table .= "<tr>";
				$table .= "<td>".$record['station_callsign']."</td>";
				$table .= "<td>".$time_on."</td>";
				$table .= "<td><a id=\"view_lotw_qso\" href=\"javascript:displayQso(".$status[1].")\">".$record['call']."</a></td>";
				$table .= "<td>".$record['mode']."</td>";
				$table .= "<td>".$record['qsl_rcvd']."</td>";
				$table .= "<td>".$qsl_date."</td>";
				$table .= "<td>".$state."</td>";
				$table .= "<td>".($qsl_gridsquare != '' ? $qsl_gridsquare : $qsl_vucc_grids)."</td>";
				$table .= "<td>".$iota."</td>";
				$table .= "<td>QSO Record: ".$status[0]."</td>";
				$table .= "<td>LoTW Record: ".$lotw_status."</td>";
				$table .= "</tr>";
			} else {
				$table .= "<tr>";
				$table .= "<td>".$record['station_callsign']."</td>";
				$table .= "<td>".$time_on."</td>";
				$table .= "<td>".$record['call']."</td>";
				$table .= "<td>".$record['mode']."</td>";
				$table .= "<td>".$record['qsl_rcvd']."</td>";
				$table .= "<td></td>";
				$table .= "<td></td>";
				$table .= "<td></td>";
				$table .= "<td></td>";
				$table .= "<td>QSO Record: ".$status[0]."</td>";
				$table .= "<td></td>";
				$table .= "</tr>";
			}
		}

		if ($table != "") {
			$table .= "</table>";
			$data['lotw_table_headers'] = $tableheaders;
			$data['lotw_table'] = $table;
		}

		unlink($filepath);

		$this->load->model('user_model');
		if ($this->user_model->authorize(2)) {	// Only Output results if authorized User
			if(isset($data['lotw_table_headers'])) {
				if($display_view == TRUE) {
					$data['page_title'] = __("LoTW ADIF Information");
					$this->load->view('interface_assets/header', $data);
					$this->load->view('lotw/analysis');
					$this->load->view('interface_assets/footer');
				} else {
					return $tableheaders.$table;
				}
			} else {
				echo "Downloaded LoTW report contains no matches.";
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Function: lotw_download
	|--------------------------------------------------------------------------
	|
	|	Collects users with LoTW usernames and passwords and runs through them
	|	downloading matching QSOs.
	|
	 */
	function lotw_download($sync_user_id = null) {
		$this->load->model('user_model');
		$this->load->model('logbook_model');
		$this->load->model('Stations');

		$query = $this->user_model->get_all_lotw_users();

		if ($query->num_rows() >= 1) {
			$result = '';

			// Get URL for downloading LoTW
			$url_query = $this->db->query('SELECT lotw_download_url FROM config');
			$q = $url_query->row();
			$lotw_base_url = $q->lotw_download_url;

			foreach ($query->result() as $user) {
				if ( ($sync_user_id != null) && ($sync_user_id != $user->user_id) ) { continue; }
				$station_ids=$this->Stations->all_station_ids_of_user($user->user_id);
				if ($station_ids == '') { continue; } // User has no Station-ID! next one

				// Validate that LoTW credentials are not empty
				// TODO: We don't actually see the error message
				if ($user->user_lotw_password == '') {
					$result = "You have not defined your ARRL LoTW credentials!";
					continue;
				}

				$config['upload_path'] = './uploads/';
				$file = $config['upload_path'] . 'lotwreport_download_'.$user->user_id.'_auto.adi';
				if (file_exists($file) && ! is_writable($file)) {
					$result = "Temporary download file ".$file." is not writable. Aborting!";
					continue;
				}

				// Get credentials for LoTW
				$data['user_lotw_name'] = urlencode($user->user_lotw_name);
				$data['user_lotw_password'] = urlencode($user->user_lotw_password);

				$lotw_last_qsl_date = date('Y-m-d', strtotime($this->logbook_model->lotw_last_qsl_date($user->user_id)));

				// Build URL for LoTW report file
				$lotw_url = $lotw_base_url."?";
				$lotw_url .= "login=" . $data['user_lotw_name'];
				$lotw_url .= "&password=" . $data['user_lotw_password'];
				$lotw_url .= "&qso_query=1&qso_qsl='yes'&qso_qsldetail='yes'&qso_mydetail='yes'";

				$lotw_url .= "&qso_qslsince=";
				$lotw_url .= "$lotw_last_qsl_date";

				if (! is_writable(dirname($file))) {
					$result = "Temporary download directory ".dirname($file)." is not writable. Aborting!";
					continue;
				}
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $lotw_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				$content = curl_exec($ch);
				if(curl_errno($ch)) {
					$result = "LoTW download failed for user ".$data['user_lotw_name'].": ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).").";
					if (curl_errno($ch) == 28) {  // break on timeout
						$result .= "<br>Timeout reached. Stopping subsequent downloads.";
						break;
					}
					continue;
				} else if(str_contains($content,"Username/password incorrect</I>")) {
					$result = "LoTW download failed for user ".$data['user_lotw_name'].": Username/password incorrect";
					continue;
				}
				file_put_contents($file, $content);
				if (file_get_contents($file, false, null, 0, 39) != "ARRL Logbook of the World Status Report") {
					$result = "Downloaded LoTW report for user ".$data['user_lotw_name']." is invalid. Check your credentials.";
					continue;
				}

				ini_set('memory_limit', '-1');
				$result = $this->loadFromFile($file, $station_ids, false);
			}
			return $result;
		} else {
			return "No LoTW User details found to carry out matches.";
		}
	}

	public function check_lotw_credentials () {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			exit();
		}
		$ret=[];
		$ret['status']='';


		$raw = file_get_contents("php://input");
		try {
			$obj = json_decode($raw,true);
		} catch (Exception $e) {
			$ret['status']='failed_wrongcall';
			log_message("Error",$ret['status']);
		} finally {
			$lotw_user=$obj['lotw_user'] ?? '';
			$lotw_pass=$obj['lotw_pass'] ?? '';
		}
		$raw='';

		$pw_placeholder = '**********';
		if ($lotw_pass == $pw_placeholder) {	// User comes with unaltered credentials - take them from database
			$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
			$q = $query->row();
			$data['user_lotw_name'] = urlencode($q->user_lotw_name ?? '');
			$data['user_lotw_password'] = urlencode($q->user_lotw_password ?? '');
		} else {
			$data['user_lotw_name'] = urlencode($lotw_user ?? '');
			$data['user_lotw_password'] = urlencode($lotw_pass ?? '');
		}

		if ((($data['user_lotw_name'] ?? '') != '') && (($data['user_lotw_password'] ?? '') != '') && ($ret['status'] != 'failed_wrongcall')) {

			// Get URL for downloading LoTW
			$query = $query = $this->db->query('SELECT lotw_login_url FROM config');
			$q = $query->row();
			$lotw_url = $q->lotw_login_url;

			// Validate that LoTW credentials are not empty
			// TODO: We don't actually see the error message
			if ($data['user_lotw_name'] == '' || $data['user_lotw_password'] == '') {
				$ret='No Creds set';
			}

			// Build URL for LoTW report file
			$lotw_url .= "?";
			$lotw_url .= "login=" . $data['user_lotw_name'];
			$lotw_url .= "&password=" . $data['user_lotw_password'];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $lotw_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			$content = curl_exec($ch);
			if ($content) {
				if(curl_errno($ch)) {
					$ret['status']='failed';
					$ret['details']== sprintf(__("LoTW login failed for user %s: %s."), $data['user_lotw_name'], curl_strerror(curl_errno($ch))." (".curl_errno($ch).")");
				} else if (str_contains($content,"Username/password incorrect</I>")) {
					$ret['status']='failed_wrong_creds';
					$ret['details']= sprintf(__("LoTW login failed for user %s: %s."), $data['user_lotw_name'], __("Username/password incorrect"));
				} else {
					$ret['status']='OK';
					$ret['details']= __("LoTW login OK!");
				}
			} else {
				$ret['status']='failed_na';
				$ret['details']= __("LoTW currently not available. Try again later.");
			}
		} else {
			if (($ret['status'] ?? '') == '') {
				$ret['status']='failed_nocred';
				$ret['details']= __("No LoTW credentials provided.");
			}
		}
		header("Content-type: application/json");
		echo json_encode($ret);
		return $ret;
	}

	public function import() {	// Is only called via frontend. Cron uses "upload". within download the download is called
		$this->load->model('user_model');
		$this->load->model('Stations');
		if(!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			exit();
		}

		$station_ids=$this->Stations->all_station_ids_of_user($this->session->userdata['user_id']);
		$data['page_title'] = __("LoTW ADIF Import");

		$config['upload_path'] = './uploads/';
		$config['allowed_types'] = 'adi|ADI';

		$this->load->library('upload', $config);

		$this->load->model('logbook_model');

		if (($this->input->post('lotwimport') == 'fetch') && (!($this->config->item('disable_manual_lotw')))) {
			$file = $config['upload_path'] . 'lotwreport_download_'.$this->session->userdata('user_id').'.adi';

			// Get credentials for LoTW
			$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
			$q = $query->row();
			$data['user_lotw_name'] = urlencode($q->user_lotw_name ?? '');
			$data['user_lotw_password'] = urlencode($q->user_lotw_password ?? '');

			// Get URL for downloading LoTW
			$query = $query = $this->db->query('SELECT lotw_download_url FROM config');
			$q = $query->row();
			$lotw_url = $q->lotw_download_url;

			// Validate that LoTW credentials are not empty
			// TODO: We don't actually see the error message
			if ($data['user_lotw_name'] == '' || $data['user_lotw_password'] == '') {
				$this->session->set_flashdata('warning', __("You have not defined your ARRL LoTW credentials!")); redirect('lotw/import');
			}

			$customDate = $this->input->post('from');

			if ($customDate != NULL) {
				$lotw_last_qsl_date = date($customDate);
			} else {
				// Query the logbook to determine when the last LoTW confirmation was
				$lotw_last_qsl_date = date('Y-m-d', strtotime($this->logbook_model->lotw_last_qsl_date($this->session->userdata['user_id'])));
			}

			// Build URL for LoTW report file
			$lotw_url .= "?";
			$lotw_url .= "login=" . $data['user_lotw_name'];
			$lotw_url .= "&password=" . $data['user_lotw_password'];
			$lotw_url .= "&qso_query=1&qso_qsl='yes'&qso_qsldetail='yes'&qso_mydetail='yes'";

			$lotw_url .= "&qso_qslsince=";
			$lotw_url .= "$lotw_last_qsl_date";

			if ($this->input->post('callsign') != '0') {
				$lotw_url .= "&qso_owncall=".$this->input->post('callsign');
			}

			if (is_writable(dirname($file)) && (!file_exists($file) || is_writable($file))) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $lotw_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				$content = curl_exec($ch);
				if(curl_errno($ch)) {
					print "LoTW download failed for user ".$data['user_lotw_name'].": ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).").";
				} else if (str_contains($content,"Username/password incorrect</I>")) {
					print "LoTW download failed for user ".$data['user_lotw_name'].": Username/password incorrect";
				} else {
					file_put_contents($file, $content);
					ini_set('memory_limit', '-1');
					$this->loadFromFile($file, $station_ids);
				}
			} else {
				if (!is_writable(dirname($file))) {
					$data['errormsg'] = 'Directory '.dirname($file).' is not writable!';
				} else if (!is_writable($file)) {
					$data['errormsg'] = 'File '.$file.' is not writable!';
				}
				$this->load->model('Stations');
				$data['callsigns'] = $this->Stations->callsigns_of_user($this->session->userdata('user_id'));

				$this->load->view('interface_assets/header', $data);
				$this->load->view('lotw/import', $data);
				$this->load->view('interface_assets/footer');
			}
		} else {
			if (!$this->upload->do_upload()) {

				$data['error'] = $this->upload->display_errors();
				$this->load->model('Stations');
				$data['callsigns'] = $this->Stations->callsigns_of_user($this->session->userdata('user_id'));

				$this->load->view('interface_assets/header', $data);
				$this->load->view('lotw/import', $data);
				$this->load->view('interface_assets/footer');
			} else {
				$data = array('upload_data' => $this->upload->data());

				$this->loadFromFile('./uploads/'.$data['upload_data']['file_name'], $station_ids);
			}
		}
	} // end function

	public function export() {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['page_title'] = __("LoTW .TQ8 Upload");

		$config['upload_path'] = './uploads/';
		$config['allowed_types'] = 'tq8|TQ8';

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload())
		{
			$data['error'] = $this->upload->display_errors();

			$this->load->view('interface_assets/header', $data);
			$this->load->view('lotw/export');
			$this->load->view('interface_assets/footer');
		}
		else
		{
			$data = array('upload_data' => $this->upload->data());

			// Figure out how we should be marking QSLs confirmed via LoTW
			$query = $query = $this->db->query('SELECT lotw_login_url FROM config');
			$q = $query->row();
			$config['lotw_login_url'] = $q->lotw_login_url;

			// Set some fields that we're going to need for ARRL login
			$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
			$q = $query->row();
			$fields['login'] = $q->user_lotw_name;
			$fields['password'] = $q->user_lotw_password;
			$fields['acct_sel'] = "";

			if ($fields['login'] == '' || $fields['password'] == '')
			{
				$this->session->set_flashdata('warning', __("You have not defined your ARRL LoTW credentials!")); redirect('lotw/status');
			}

			// Curl stuff goes here

			// First we need to get a cookie

			// options
			$cookie_file_path = "./uploads/cookies.txt";
			$agent            = "Mozilla/4.0 (compatible;)";

			// begin script
			$ch = curl_init();

			// extra headers
			$headers[] = "Accept: */*";
			$headers[] = "Connection: Keep-Alive";

			// basic curl options for all requests
			curl_setopt($ch, CURLOPT_HTTPHEADER,  $headers);
			curl_setopt($ch, CURLOPT_HEADER,  0);

			// TODO: These SSL things should probably be set to true :)
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, $agent);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file_path);
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);

			// Set login URL
			curl_setopt($ch, CURLOPT_URL, $config['lotw_login_url']);

			// set postfields using what we extracted from the form
			$POSTFIELDS = http_build_query($fields);

			// set post options
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);

			// perform login
			$result = curl_exec($ch);
			if (stristr($result, "Username/password incorrect"))
			{
				$this->session->set_flashdata('warning', __("Your ARRL username and/or password is incorrect.")); redirect('lotw/status');
			}


			// Now we need to use that cookie and upload the file
			// change URL to upload destination URL
			curl_setopt($ch, CURLOPT_URL, $config['lotw_login_url']);

			// Grab the file
			$postfile = array(
				"upfile"=>"@./uploads/".$data['upload_data']['file_name'],
			);

			//Upload it
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfile);
			$response = curl_exec($ch);
			if (stristr($response, "accepted"))
			{
				$this->session->set_flashdata('lotw_status', 'accepted');
				$data['page_title'] = __("LoTW .TQ8 Sent");
			}
			elseif (stristr($response, "rejected"))
			{
				$this->session->set_flashdata('lotw_status', 'rejected');
				$data['page_title'] = __("LoTW .TQ8 Sent");
			}
			else
			{
				// If we're here, we didn't find what we're looking for in the ARRL response
				// and LoTW is probably down or broken.
				$this->session->set_flashdata('warning', 'Did not receive proper response from LoTW. Try again later.');
				$data['page_title'] = __("LoTW .TQ8 Not Sent");
			}

			// Now we need to clean up
			unlink($cookie_file_path);
			unlink('./uploads/'.$data['upload_data']['file_name']);

			$this->load->view('interface_assets/header', $data);
			$this->load->view('lotw/status');
			$this->load->view('interface_assets/footer');
		}
	}

	/*
		Deprecated. To be back compatible we do the same as update/lotw_users 
		HB9HIL, July 2024
	 */
	public function load_users() {
		$this->load->model('Update_model');
		$result = $this->Update_model->lotw_users();
		echo $result;
	}

	function signlog($sign_key, $string) {

		$qso_string = $string;

		$key = $sign_key;

		$pkeyid = openssl_pkey_get_private($key, 'wavelog');
		if ($pkeyid) {
			//openssl_sign($plaintext, $signature, $pkeyid, OPENSSL_ALGO_SHA1 );
			//openssl_free_key($pkeyid);

			if(openssl_sign($qso_string, $signature, $pkeyid, OPENSSL_ALGO_SHA1)) {
				if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION < 8) {
					openssl_free_key($pkeyid);
				}
				$signature_b64 = base64_encode($signature);
				return $signature_b64."\n";
			} else {
				// in case of deprecation of SHA-1 in some distro
				log_message('error', 'Error signing LoTW log: '.openssl_error_string());
			}
		} else {
			log_message('error', 'Error signing LoTW log.');
			return null;
		}


	}

	/*
	|	Function: lotw_ca_province_map
	|	Requires: candian province map $ca_province
	 */
	function lotw_ca_province_map($ca_prov) {
		switch ($ca_prov):
		case "QC":
			return "PQ";
			break;
		case "NL":
			return "NF";
			break;
		default:
			return $ca_prov;
			endswitch;
	}

	/*
	|	Function: mode_map
	|	Requires: mode as $mode, submode as $submode
	|
	|	This converts ADIF modes to the mode that LoTW expects if its non standard
	 */
	function mode_map($mode, $submode) {
		switch ($mode):
		case "PKT":
			return "PACKET";
			break;
		case "MFSK":
			if ($submode == "FT4") {
				return "FT4";
				break;
			} elseif ($submode == "FST4") {
				return "FST4";
				break;
			} elseif ($submode == "MFSK16") {
				return "MFSK16";
				break;
			} elseif ($submode == "MFSK8") {
				return "MFSK8";
				break;
			} elseif ($submode == "Q65") {
				return "Q65";
				break;
			} else {
				return "DATA";
				break;
			}
		case "PSK":
			if ($submode == "PSK31") {
				return "PSK31";
				break;
			} elseif ($submode == "PSK63") {
				return "PSK63";
				break;
			} elseif ($submode == "BPSK125") {
				return "PSK125";
				break;
			} elseif ($submode == "BPSK31") {
				return "PSK31";
				break;
			} elseif ($submode == "BPSK63") {
				return "PSK63";
				break;
			} elseif ($submode == "FSK31") {
				return "FSK31";
				break;
			} elseif ($submode == "PSK10") {
				return "PSK10";
				break;
			} elseif ($submode == "PSK125") {
				return "PSK125";
				break;
			} elseif ($submode == "PSK500") {
				return "PSK500";
				break;
			} elseif ($submode == "PSK63F") {
				return "PSK63F";
				break;
			} elseif ($submode == "PSKAM10") {
				return "PSKAM";
				break;
			} elseif ($submode == "PSKAM31") {
				return "PSKAM";
				break;
			} elseif ($submode == "PSKAM50") {
				return "PSKAM";
				break;
			} elseif ($submode == "PSKFEC31") {
				return "PSKFEC31";
				break;
			} elseif ($submode == "QPSK125") {
				return "PSK125";
				break;
			} elseif ($submode == "QPSK31") {
				return "PSK31";
				break;
			} elseif ($submode == "QPSK63") {
				return "PSK63";
				break;
			} elseif ($submode == "PSK2K") {
				return "PSK2K";
				break;
			} else {
				return "DATA";
				break;
			}
		default:
			return $mode;
			endswitch;
	}

} // end class
