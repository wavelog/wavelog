<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Lotw extends CI_Controller {
 /*
	|--------------------------------------------------------------------------
	| Controller: Lotw
	|--------------------------------------------------------------------------
	|
	| This Controller handles all things LoTW, upload and download.
	|
	|
	|	Note:
	|	If you plan on using any of the code within this class please credit
	| 	Peter, 2M0SQL. A lot of hard work went into building the
	|	signing of files.
	|
	|	Big Thanks to Rodrigo PY2RAF for all the help and information about OpenSSL
	|
	*/

	/* Controls who can access the controller and its functions */
	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Function: index
	|--------------------------------------------------------------------------
	|
	| Default function for the controller which loads when doing /lotw
	| this shows all the uploaded lotw p12 certificates the user has uploaded
	|
	*/
	public function index() {
		$this->load->library('Permissions');
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		// Load required models for page generation
		$this->load->model('Lotw_model');

		// Check for superseded certificates
		$certcheck = $this->Lotw_model->lotw_certs($this->session->userdata('user_id'));
		foreach ($certcheck->result() as $row) {
			if ($row->serial != null) {
				$status = $this->lotw_cert_status($row->serial);
				if ($status != 99 && $status != $row->status) {
					$this->Lotw_model->update_cert_status($row->lotw_cert_id, $status);
				}
			}
		}

		// Get Array of the logged in users LoTW certs.
		$data['lotw_cert_results'] = $this->Lotw_model->lotw_certs($this->session->userdata('user_id'));

		// Set Page Title
		$data['page_title'] = __("Logbook of the World");

		// Check folder permissions
		$uploads_folder = $this->permissions->is_really_writable('uploads');
		$data['uploads_folder'] = $uploads_folder;

		$this->load->model('cron_model');
		$data['next_run'] = $this->cron_model->get_next_run("lotw_lotw_upload");

		// Load Views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('lotw_views/index');
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
	| Function: do_cert_upload
	|--------------------------------------------------------------------------
	|
	| do_cert_upload is called from cert_upload form submit and handles uploading
	| and processing of p12 files and storing the data into mysql
	|
	*/
	public function do_cert_upload() {
		$this->load->model('user_model');
		$this->load->model('dxcc');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		// create folder to store certs while processing
    	if (!file_exists('./uploads/lotw/certs')) {
		    mkdir('./uploads/lotw/certs', 0755, true);
		}

		$config['upload_path']          = './uploads/lotw/certs';
    	$config['allowed_types']        = 'p12';

		$this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('userfile')) {
        	// Upload of P12 Failed
            $error = array('error' => $this->upload->display_errors());

			// Load DXCC Countrys List
			$data['dxcc_list'] = $this->dxcc->list();

			// Set Page Title
			$data['page_title'] = __("Logbook of the World");

			// Load Views
			$this->load->view('interface_assets/header', $data);
			$this->load->view('lotw_views/upload_cert', $error);
			$this->load->view('interface_assets/footer');
        } else {
        	// Load database queries
        	$this->load->model('Lotw_model');

        	//Upload of P12 successful
        	$data = array('upload_data' => $this->upload->data());

        	$info = $this->decrypt_key($data['upload_data']['full_path']);

			// Check to see if certificate is already in the system
			$new_certificate = $this->Lotw_model->find_cert($info['issued_callsign'], $info['dxcc-id'], $this->session->userdata('user_id'));

        	if($new_certificate == 0) {
        		// New Certificate Store in Database

        		// Store Certificate Data into MySQL
            $this->Lotw_model->store_certificate($this->session->userdata('user_id'), $info['issued_callsign'], $info['dxcc-id'], $info['validFrom'], $info['validTo_Date'], $info['qso-first-date'], $info['qso-end-date'], $info['pem_key'], $info['general_cert'], $info['serialNumber']);

        		// Cert success flash message
        		$this->session->set_flashdata('success', $info['issued_callsign'] . ' ' . __("Certificate Imported."));
        	} else {
        		// Certificate is in the system time to update

				$this->Lotw_model->update_certificate($this->session->userdata('user_id'), $info['issued_callsign'], $info['dxcc-id'], $info['validFrom'], $info['validTo_Date'], $info['qso-first-date'], $info['qso-end-date'], $info['pem_key'], $info['general_cert'], $info['serialNumber']);

        		// Cert success flash message
        		$this->session->set_flashdata('success', $info['issued_callsign'] . ' ' . __("Certificate Updated."));

        	}

        	// p12 certificate processed time to delete the file
        	unlink($data['upload_data']['full_path']);

	        redirect('lotw');
        }
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

				$cert_query = $this->Lotw_model->lotw_cert_details($station_profile->station_callsign, $station_profile->user_id);
				if ($cert_query->num_rows() > 1) {
					echo $station_profile->station_callsign.": Multiple matching LoTW certificates found. Skipping.<br>";
					continue;
				}

				// If Station Profile has no LoTW Cert continue on.
				if ($cert_query->num_rows() == 0) {
					echo $station_profile->station_callsign.": No LoTW certificate for station callsign found.<br>";
					continue;
				}

				$data['lotw_cert_info'] = $cert_query->row();
				// Check if station profile DXCC matches cert DXCC
				if ($data['lotw_cert_info']->cert_dxcc_id != $station_profile->station_dxcc) {
					echo $station_profile->station_callsign.": DXCC of station profile does not match DXCC of LoTW certificate.<br>";
					continue;
				}

				// Check LoTW cert against CRL
				if ($data['lotw_cert_info']->status != 0) {
					if ($data['lotw_cert_info']->status == 1) {
						echo $station_profile->station_callsign.": LoTW certificate superseded.<br>";
						continue;
					}
				}

				// Check if LoTW certificate itself is valid
				// Validty of QSO dates will be checked later
				$current_date = date('Y-m-d H:i:s');
				if ($current_date < $data['lotw_cert_info']->qso_start_date) {
					echo $data['lotw_cert_info']->callsign.": QSO start date of LoTW certificate not reached yet!<br>";
					continue;
				}
				if ($current_date > $data['lotw_cert_info']->qso_end_date) {
					echo $data['lotw_cert_info']->callsign.": QSO end date of LoTW certificate exceeded!<br>";
					continue;
				}
				if ($current_date < $data['lotw_cert_info']->date_created) {
					echo $data['lotw_cert_info']->callsign.": LoTW certificate not valid yet!<br>";
					continue;
				}
				if ($current_date > $data['lotw_cert_info']->date_expires) {
					echo $data['lotw_cert_info']->callsign.": LoTW certificate expired!<br>";
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
						echo str_replace("0", "&Oslash;", $station_profile->station_callsign)." (".$station_profile->station_profile_name."): No QSOs to upload.<br>";
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
					echo "Signing failed.<br>";
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

				if (!preg_match('/<!-- \.UPL\.\s*accepted -->/', $result)) {
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

	/*
	|--------------------------------------------------------------------------
	| Function: delete_cert
	|--------------------------------------------------------------------------
	|
	| Deletes LoTW certificate from the MySQL table
	|
	*/
    public function delete_cert($cert_id) {
    	$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

    	$this->load->model('Lotw_model');

    	$this->Lotw_model->delete_certificate($this->session->userdata('user_id'), $cert_id);

    	$this->session->set_flashdata('success', __("Certificate Deleted."));

    	redirect('lotw');
    }


	/*
	|--------------------------------------------------------------------------
	| Function: decrypt_key
	|--------------------------------------------------------------------------
	|
	| Accepts p12 file and optional password and encrypts the file returning
	| the required fields for LoTW and the PEM Key
	|
	*/
	public function decrypt_key($file, $password = "") {
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$results = array();
		$filename = file_get_contents('file://'.$file);
		$worked = openssl_pkcs12_read($filename, $results, $password);
		$openssl_error_pkcs12_read = openssl_error_string();
		if (!$worked || $openssl_error_pkcs12_read) {
			log_message('error', 'OpenSSL reading LoTW cert file resulted in error: '.$openssl_error_pkcs12_read);
			unlink($file);
			// OpenSSL error:11800071:PKCS12 routines::mac verify failure is most likely an (unknown) password set on the exported certificate
			if (str_contains($openssl_error_pkcs12_read, 'mac verify failure')) {
				$this->session->set_flashdata('warning', sprintf(__("The certificate found in file %s contains a password and cannot be processed. %sPlease make sure you export the LoTW certificate from tqsl application without password!%s For further information please visit the %sLoTW FAQ page%s in the Wavelog Wiki."), basename($file), '<b>', '</b>', '<a target="_blank" href="https://github.com/wavelog/wavelog/wiki/Logbook-of-The-World-(LoTW)">', '</a>'));
			} else {
				$this->session->set_flashdata('warning', sprintf(__("Generic error extracting the certificate from file %s. If the filename contains 'key-only' this is typically a certificate request which has not been processed by LoTW yet."), basename($file)));
			}
			redirect('lotw');
		} else {
			if (!array_key_exists('cert', $results)) {
				log_message('error', 'Generic error processing the certificate from file '.$file);
				unlink($file);
				$this->session->set_flashdata('warning', sprintf(__("Generic error processing the certificate in file %s."), basename($file)));
				redirect('lotw');
			} else {
				$data['general_cert'] = $results['cert'];

				// Reading p12 successful
				$new_password = "wavelog"; // set default password
				$result = null;
				$worked = openssl_pkey_export($results['pkey'], $result, $new_password);
				$openssl_error_pkey_export = openssl_error_string();
				if (!$worked || $openssl_error_pkey_export) {
					log_message('error', 'OpenSSL reading LoTW private key resulted in error: '.$openssl_error_pkey_export);
					$this->session->set_flashdata('warning', sprintf(__("Generic error extracting the private key from certificate in file %s."), basename($file)));
					unlink($file);
					redirect('lotw');
				} else {
					// Store PEM Key in Array
					$data['pem_key'] = $result;

					// Read Cert Data
					$certdata= openssl_x509_parse($results['cert'],0);

					// Store Variables
					$data['serialNumber'] = $certdata['serialNumber'];
					$data['issued_callsign'] = $certdata['subject']['undefined'];
					$data['issued_name'] = $certdata['subject']['commonName'];
					$data['validFrom'] = date('Y-m-d H:i:s', $certdata['validFrom_time_t']);
					$data['validTo_Date'] = date('Y-m-d H:i:s', $certdata['validTo_time_t']);
					// https://oidref.com/1.3.6.1.4.1.12348.1
					$data['qso-first-date'] = $certdata['extensions']['1.3.6.1.4.1.12348.1.2'];
					$data['qso-end-date'] = $certdata['extensions']['1.3.6.1.4.1.12348.1.3'];
					$data['dxcc-id'] = $certdata['extensions']['1.3.6.1.4.1.12348.1.4'];

					return $data;
				}
			}
		}
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

				$call = str_replace("0", "&Oslash;", $record['call']);

				if (isset($record['state'])) {
					$state = $record['state'];
				} else {
					$state = "";
				}
				// Present only if the QSLing station specified a single valid grid square value in its station location uploaded to LoTW.
				$qsl_gridsquare = "";
				if (isset($record['gridsquare'])) {
					if (strlen($record['gridsquare']) >= strlen($status[2] ?? '') || substr(strtoupper($status[2] ?? ''), 0, 4) != substr(strtoupper($record['gridsquare']), 0, 4)) {
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
				$table .= "<td><a id=\"view_lotw_qso\" href=\"javascript:displayQso(".$status[1].")\">".$call."</a></td>";
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

				$lotw_last_qsl_date = date('Y-m-d', strtotime($this->logbook_model->lotw_last_qsl_date($user->user_id)));

				// Build URL for LoTW report file
				$lotw_url = $lotw_base_url."?";
				$lotw_url .= "login=" . urlencode($user->user_lotw_name);
				$lotw_url .= "&password=" . urlencode($user->user_lotw_password);
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
					$result = "LoTW download failed for user ".$user->user_lotw_name.": ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).").";
					if (curl_errno($ch) == 28) {  // break on timeout
						$result .= "<br>Timeout reached. Stopping subsequent downloads.";
						break;
					}
					continue;
				} else if(str_contains(substr($content,0 , 2000),"Username/password incorrect</I>")) {
					$result = "LoTW download failed for user ".$user->user_lotw_name.": Username/password incorrect";
					log_message('error', 'LoTW download failed for user '.$user->user_name.': Username/password incorrect');
					if ($this->Lotw_model->remove_lotw_credentials($user->user_id)) {
						log_message('error', 'LoTW credentials deleted for user '.$user->user_name);
					} else {
						log_message('error', 'Deleting LoTW credentials for user '.$user->user_name.' failed');
					}
					continue;
				} else if (str_contains(substr($content, 0, 2000),"Page Request Limit!</B>")) {
					$result = "LoTW download hit a rate limit for user ".$user->user_lotw_name;
					log_message('error', 'LoTW download hit a rate limit for user '.$user->user_name);
					continue;
				}
				file_put_contents($file, $content);
				if (file_get_contents($file, false, null, 0, 39) != "ARRL Logbook of the World Status Report") {
					$result = "Downloaded LoTW report for user ".$user->user_lotw_name." is invalid. Check your credentials.";
					log_message('error', 'Downloaded LoTW report is invalid for user '.$user->user_name);
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
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 20);
			$content = curl_exec($ch);
			if(curl_errno($ch)) {
				$ret['status']='failed';
				$ret['details'] = __("Connection to LoTW failed.");
				log_message('debug', "LoTW error: Connection to LoTW failed: ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).")");
			} else {
				if (str_contains($content,"Username/password incorrect</I>")) {
					$ret['status']='failed_wrong_creds';
					$ret['details']= sprintf(__("LoTW login failed for user %s: %s."), $data['user_lotw_name'], __("Username/password incorrect"));
				} elseif (!$content) {
					$ret['status']='failed_na';
					$ret['details']= __("LoTW currently not available. Try again later.");
					log_message('debug', "LoTW error: Connecting LoTW gave an empty result");
				} else {
					$ret['status']='OK';
					$ret['details']= __("LoTW login OK!");
				}
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
	|	Function: lotw_ru_oblast_map
	|	Requires: russian oblast map $ru_oblast
	*/
	function lotw_ru_oblast_map($ru_oblast) {
		switch ($ru_oblast):
			case "YR":
				return "JA";
				break;
			case "YN":
				return "JN";
				break;
			default:
				return $ru_oblast;
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

	function lotw_cert_status ($serial) {
		if (($serial ?? '') != '' && is_numeric($serial)) {
			$url = 'https://lotw.arrl.org/lotw/crl?serial='.$serial;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			$result = curl_exec($ch);
			if(curl_errno($ch)){
				log_message('error', 'Error fetch LoTW CRL results: '.curl_strerror(curl_errno($ch)));
				return 99;
			}
			$xml = new SimpleXMLElement($result);
			if (!isset($xml->Status)) {
				log_message('error', 'Error parsing LoTW CRL result: '.$result);
				return 98;
			}
			switch ((string)$xml->Status) {
			case 'Superceded':
				return 1;
			case 'Unrevoked':
				return 0;
			default:
				log_message('error', 'Unknown LotW CRL status: '.(string)$xml->Status);
				return 97;
			}
		}
		return 99;
	}

} // end class
