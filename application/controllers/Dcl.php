<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dcl extends CI_Controller {

	/* Controls who can access the controller and its functions */
	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		if (!($this->config->item('enable_dcl_interface') ?? false)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); exit; }
		$this->load->model('user_model');
		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
	}

	public function save_key() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('Dcl_model');
		$this->Dcl_model->store_key($call);
	}
	public function key_import() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->library('Permissions');
		$this->load->model('dcl_model');
		$data['date_format']=$this->session->userdata('user_date_format') ?? $this->config->item('qso_date_format');

		$sig=($this->input->get('sig',true) ?? '');
		$token=($this->input->get('token',true) ?? '');
		if ( ($sig != '') && ($token != '')) {
			$data['is_valid']=$this->dcl_model->check_dcl_sig($token,$sig);
			$data['page_title'] = __("DCL Key Import");
			$data['token'] = $token;
			if ($data['is_valid']) {
				$data['dcl_info']=$this->dcl_model->get_dcl_info($token);
				$this->dcl_model->store_key(json_encode($data['dcl_info'] ?? ''));
			} else {
				$data['dcl_info']='';
			}
			$this->load->view('interface_assets/header', $data);
			$this->load->view('dcl_views/key_import',$data);
			$this->load->view('interface_assets/footer');
		} else {
			redirect('https://api.dcl.darc.de/api/v1/get-token?wohin='.site_url().'/dcl/key_import');
		}
	}

	public function index() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->library('Permissions');
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		// Load required models for page generation
		$this->load->model('Dcl_model');
		$data['date_format']=$this->session->userdata('user_date_format') ?? $this->config->item('qso_date_format');

		// Get Array of the logged in users LoTW certs.
		$dclkeys=($this->Dcl_model->dcl_keys($this->session->userdata('user_id')) ?? '');
		$i=0;
		foreach ($dclkeys as $dclkey) {
			$data['dcl_keys'][$i] = json_decode($dclkey->option_value ?? '');
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

	public function dcl_sync() {
		$this->dcl_upload();
	}

	public function dcl_upload() {
		// Called as User: Upload for User (if manual sync isn't disabled
		// Called from cron / without Session: iterate through stations, check for DCL-Key and upload
		ini_set('memory_limit', '-1');

		$this->load->model('user_model');
		$this->load->model('Dcl_model');

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class.'_'.$this->router->method);

		// Get Station Profile Data
		$this->load->model('Stations');

		if (!$this->load->is_loaded('AdifHelper')) {
			$this->load->library('AdifHelper');
		}

		if ($this->user_model->authorize(2)) {
			if (!($this->config->item('disable_manual_dcl'))) {
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

		if ($station_profiles->num_rows() >= 1) {

			foreach ($station_profiles->result() as $station_profile) {

				// Get Certificate Data
				$data['station_profile'] = $station_profile;
				$key_info = $this->Dcl_model->find_key($station_profile->station_callsign, $station_profile->user_id);
				// If Station Profile has no DCL Key continue on.
				if (($key_info ?? '') == '') {
					continue;
				}

				$this->load->model('Logbook_model');

				$data['qsos'] = $this->Logbook_model->get_dcl_qsos_to_upload($data['station_profile']->station_id,$key_info['vf'],$key_info['vt']);

				// Nothing to upload
				if(empty($data['qsos']->result())){
					if ($this->user_model->authorize(2)) {	// Only be verbose if we have a session
						echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): ".__("No QSOs to upload.")."<br>";
					}
					continue;
				}

				foreach ($data['qsos']->result() as $temp_qso) {
					array_push($qso_id_array, $temp_qso->COL_PRIMARY_KEY);
				}

				// Build File to save
				$adif_to_post = $this->load->view('adif/data/dcl.php', $data, TRUE);
				$data['qsos']='';
				
				//The URL that accepts the file upload.
				$url = 'https://api.dcl.darc.de/api/v1/adif-import'; // todo: change to final URL b4 release

				//Initiate cURL
				$ch = curl_init();

				//Set the URL
				curl_setopt($ch, CURLOPT_URL, $url);

				//Set the HTTP request to POST
				curl_setopt($ch, CURLOPT_POST, true);

				//Tell cURL to return the output as a string.
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				$headers = [
					'Content-Type: application/json',
					'Accept: application/json'
				];
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				
				$payload=[];
				$payload['key']=$key_info['token'];
				$payload['adif']=$adif_to_post;

				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, true));

				$result = curl_exec($ch);
				$adif_to_post=''; // Clean Mem
				// todo: parse output from DCL (contains a lot of information within $result)
				if(curl_errno($ch)){
					echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): ".__("Upload Failed")." - ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).")<br>";
					if (curl_errno($ch) == 28) {  // break on timeout
						echo __("Timeout reached. Stopping subsequent uploads.")."<br>";
						break;
					} else {
						continue;
					}
				}

				$pos = true;

				if ($pos === false) {
					echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): ".__("Upload Failed")." - ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).")<br>";
					if (curl_errno($ch) == 28) {  // break on timeout
						echo __("Timeout reached. Stopping subsequent uploads.")."<br>";
						break;
					} else {
						continue;
					}
				} else {
					echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): ".__("Upload Successful")." ".count($qso_id_array)." QSOs<br>";
					// Mark QSOs as Sent
					foreach ($qso_id_array as $qso_number) {
						// todo: uncomment when ready
						$this->Logbook_model->mark_dcl_sent($qso_number);
					}
				}
				$qso_id_array=[];
			}
		} else {
			echo __("No Station Profiles found to upload to DCL");
		}

		if ($this->user_model->authorize(2)) {
			echo "<br><br>";
			$sync_user_id=$this->session->userdata('user_id');
		} else {
			$sync_user_id=null;
		}
		// echo $this->dcl_download($sync_user_id);
	}

	public function delete_key() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('Dcl_model');
		$this->Dcl_model->delete_key();
		$this->session->set_flashdata('success', __("Key(s) Deleted."));
		redirect('dcl');
	}


	/*
	|--------------------------------------------------------------------------
	| Function: dcl_download
	|--------------------------------------------------------------------------
	|
	|	Collects users with DCL tokens and runs through them
	|	downloading matching QSOs.
	|
	 */
	function dcl_download($sync_user_id = null) {
		$this->load->model('user_model');
		$this->load->model('logbook_model');
		$this->load->model('Stations');

		// Needs complete refactoring. Pseudocode:
		// Loop through all users with token present (find_key at Dcl_model) or only single_users if sync_user_is has been provided
		// Fetch data for call from DCL (todo: which URL? Where?)
		// Mark as received
		// all on the fly (no tempfiles)
	}

	public function import() {	// Is only called via frontend. Cron uses "upload". within download the download is called
		$this->load->model('user_model');
		$this->load->model('Stations');
		if(!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			exit();
		}
		// Refactoring needed. This function provides manual download (via uploaded file into Wavelog) of DCL-Confirmations as well as triggering dcl_download
	} 

} // end class
