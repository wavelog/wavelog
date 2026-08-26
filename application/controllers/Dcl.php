<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dcl extends CI_Controller {

	/* Controls who can access the controller and its functions */
	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		if (!($this->config->item('enable_dcl_interface') ?? false)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); exit; }
		if (MAINTENANCE_MODE && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
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
		$data['next_run'] = $this->cron_model->get_next_run("sync_dcl");

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

		$this->load->helper('cronauth');
		if (!cronauth_allowed(2)) {
			// return a 403
			$this->output->set_status_header(403);
			exit();
		}

		ini_set('memory_limit', '-1');

		$this->load->model('Dcl_model');

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run('sync_dcl');

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

				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

				if(curl_errno($ch)){
					echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): ".__("Upload Failed")." - ".curl_strerror(curl_errno($ch))." (".curl_errno($ch).")<br>";
					if (curl_errno($ch) == 28) {  // break on timeout
						echo __("Timeout reached. Stopping subsequent uploads.")."<br>";
						break;
					} else {
						continue;
					}
				}

				$pos = ($httpcode == 200);

				if ($pos === false) {
					echo $station_profile->station_callsign." (".$station_profile->station_profile_name."): ".__("Upload Failed")." - Errorcode: ".$httpcode."<br>";
					continue;
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
		echo $this->dcl_download($sync_user_id);
	}

	public function delete_key() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
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
	|	downloading matching QSOs (DCL confirmations + DOK).
	|
	|	$sync_user_id = null: all users with DCL key (cron)
	|	$sync_user_id set: only this user
	|	$since set (Y-m-d): override "confirmed since" date
	|	$details array passed by reference: filled with per-QSO result data for display
	|
	|	Works entirely in memory, no tempfiles.
	 */
	function dcl_download($sync_user_id = null, $since = null, &$details = null) {
		if ($this->session->userdata('user_id') != '') {	// Called from browser/session: auth-check and never sync foreign users
			if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); exit; }
			if ($sync_user_id == null) {
				$sync_user_id = $this->session->userdata('user_id');
			}
		}

		$this->load->model('logbook_model');
		$this->load->model('Stations');
		$this->load->model('Dcl_model');

		ini_set('memory_limit', '-1');

		if (!$this->load->is_loaded('adif_parser')) {
			$this->load->library('adif_parser');
		}

		if ($sync_user_id != null) {
			$user_ids = array($sync_user_id);
		} else {
			$user_ids = array();
			foreach ($this->Dcl_model->dcl_user_ids() as $dcl_user) {
				$user_ids[] = $dcl_user->user_id;
			}
		}

		$result = '';

		//The URL that returns stored QSOs as ADIF. Untested endpoint - adjust here if DCL API differs.
		$url = 'https://dings.dcl.darc.de/api/adiexport';

		foreach ($user_ids as $user_id) {
			$token = $this->Dcl_model->get_token($user_id);
			if (($token ?? '') == '') { continue; }

			$station_ids = $this->Stations->all_station_ids_of_user($user_id);
			if (($station_ids ?? '') == '') { continue; }

			if (($since ?? '') != '') {
				$qsl_since = date('Y-m-d', strtotime($since));
			} else {
				$qsl_since = date('Y-m-d', strtotime($this->logbook_model->dcl_last_qsl_date($user_id)));
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Accept: application/json'));
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('key' => $token, 'cnf_only' => 'T', 'qsl_since' => $qsl_since, 'limit' => 400000)));

			$content = curl_exec($ch);
			$errno = curl_errno($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($errno) {
				log_message('error', 'DCL download failed for user_id '.$user_id.': '.curl_strerror($errno).' ('.$errno.')');
				if ($sync_user_id != null) { $result .= __("DCL download failed").": ".curl_strerror($errno)." (".$errno.")\n"; }
				continue;
			}
			if ($httpcode !== 200) {
				log_message('error', 'DCL download failed for user_id '.$user_id.': unexpected HTTP status '.$httpcode.($httpcode == 401 ? ' (invalid DCL key?)' : ''));
				if ($sync_user_id != null) { $result .= __("DCL download failed")." - ".__("Errorcode").": ".$httpcode."\n"; }
				continue;
			}

			$json = json_decode($content ?? '', true);
			if (!is_array($json) || (($json['status'] ?? '') != 'ok')) {
				log_message('error', 'DCL download failed for user_id '.$user_id.': invalid response');
				if ($sync_user_id != null) { $result .= __("DCL download failed").": ".__("Invalid response from DCL")."\n"; }
				continue;
			}

			if (trim((string)($json['adif'] ?? '')) == '') {
				if ($sync_user_id != null) { $result .= "DCL: ".__("No QSOs to import").".\n"; }
				continue;
			}

			$this->adif_parser->feed($json['adif']);
			$this->adif_parser->initialize();

			$confirmed = 0;
			$not_matched = 0;

			while ($record = $this->adif_parser->get_record()) {
				if (count($record) == 0) { break; }

				// Only confirmed QSOs carry DCL_QSL_RCVD
				if (($record['dcl_qsl_rcvd'] ?? '') != 'Y') { continue; }
				if (($record['call'] ?? '') == '') { continue; }
				if (($record['station_callsign'] ?? '') == '') { continue; }

				$time_on = date('Y-m-d', strtotime($record['qso_date'])) . " " . date('H:i', strtotime($record['time_on']));

				$qsl_date = (($record['dcl_qslrdate'] ?? '') != '') ? date('Y-m-d H:i', strtotime($record['dcl_qslrdate'])) : date('Y-m-d H:i');

				$status = $this->logbook_model->import_check($time_on, $record['call'], strtolower($record['band'] ?? ''), $record['mode'] ?? '', $record['prop_mode'] ?? null, $record['sat_name'] ?? null, $record['station_callsign'], $station_ids);

				if ($status[0] == "Found") {
					$this->logbook_model->dcl_update($status[1], $qsl_date, $record['darc_dok'] ?? '', $station_ids);
					$confirmed++;
					if ($details !== null) {
						$details[] = array(
							'date' => date('Y-m-d', strtotime($record['qso_date'])),
							'time' => date('H:i', strtotime($record['time_on'])),
							'call' => $record['call'],
							'band' => (strtoupper(trim($record['prop_mode'] ?? '')) == 'SAT' && strtoupper(trim($record['sat_name'] ?? '')) != '') ? strtoupper(trim($record['sat_name'])) : strtoupper($record['band'] ?? ''),
							'mode' => $record['mode'] ?? '',
							'dok' => strtoupper(trim($record['darc_dok'] ?? '')),
							'qsl_date' => date('Y-m-d', strtotime($record['dcl_qslrdate'] ?? 'now')),
							'matched' => true,
						);
					}
				} else {
					$not_matched++;
					if ($details !== null) {
						$details[] = array(
							'date' => date('Y-m-d', strtotime($record['qso_date'])),
							'time' => date('H:i', strtotime($record['time_on'])),
							'call' => $record['call'],
							'band' => (strtoupper(trim($record['prop_mode'] ?? '')) == 'SAT' && strtoupper(trim($record['sat_name'] ?? '')) != '') ? strtoupper(trim($record['sat_name'])) : strtoupper($record['band'] ?? ''),
							'mode' => $record['mode'] ?? '',
							'dok' => strtoupper(trim($record['darc_dok'] ?? '')),
							'qsl_date' => date('Y-m-d', strtotime($record['dcl_qslrdate'] ?? 'now')),
							'matched' => false,
						);
					}
				}
			}

			if ($sync_user_id != null) {
				$result .= "DCL: ".$confirmed." ".__("QSOs confirmed").", ".$not_matched." ".__("not matched")." (".__("since")." ".$qsl_since.")\n";
			} else {
				log_message('debug', 'DCL download for user_id '.$user_id.': '.$confirmed.' confirmed, '.$not_matched.' not matched (since '.$qsl_since.')');
			}
		}

		return $result;
	}

	public function import() {	// Manual import of DCL confirmations: file upload (DOKs) or fetch from DCL. Cron uses "dcl_sync".
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); exit(); }
		$this->load->library('Permissions');

		$data['page_title'] = __("DCL Import");
		$data['date_format'] = $this->session->userdata('user_date_format') ?? $this->config->item('qso_date_format');

		if ($this->input->post('dclimport') == 'upload') {
			// File based import of DCL confirmations (DOK update)
			$config['upload_path'] = './uploads/';
			$config['allowed_types'] = 'adi|ADI|adif|ADIF';

			$this->load->library('upload', $config);

			if (!$this->upload->do_upload()) {
				$data['error'] = $this->upload->display_errors();

				$this->load->view('interface_assets/header', $data);
				$this->load->view('dcl_views/import', $data);
				$this->load->view('interface_assets/footer');
			} else {
				$upload_data = $this->upload->data();

				ini_set('memory_limit', '-1');
				set_time_limit(0);

				$this->load->model('logbook_model');

				if (!$this->load->is_loaded('adif_parser')) {
					$this->load->library('adif_parser');
				}

				$filepath = $upload_data['full_path'];
				$error_count = array(0, 0, 0);
				$custom_errors = "";

				try {
					$this->adif_parser->load_from_file($filepath);
					$this->adif_parser->initialize();

					while ($record = $this->adif_parser->get_record()) {
						if (count($record) == 0) {
							break;
						};

						$dok_result = $this->logbook_model->update_dok($record, xss_clean($this->input->post('ignoreAmbiguous')), xss_clean($this->input->post('onlyConfirmed')), xss_clean($this->input->post('overwriteDok')));
						if (!empty($dok_result)) {
							switch ($dok_result[0]) {
							case 0:
								$error_count[0]++;
								break;
							case 1:
								$custom_errors .= $dok_result[1];
								$error_count[1]++;
								break;
							case 2:
								$custom_errors .= $dok_result[1];
								$error_count[2]++;
							}
						}
					}
				} finally {
					@unlink($filepath);	// Never leave an orphaned upload behind
				}

				$data['dcl_error_count'] = $error_count;
				$data['dcl_errors'] = $custom_errors;
				$data['page_title'] = __("DCL Data Imported");
				$this->load->view('interface_assets/header', $data);
				$this->load->view('dcl_views/dcl_success');
				$this->load->view('interface_assets/footer');
			}
		} else {
			if ($this->input->post('dclimport') == 'fetch' && !($this->config->item('disable_manual_dcl') ?? false)) {
				$data['dcl_details'] = [];
				$data['dcl_result'] = $this->dcl_download($this->session->userdata('user_id'), xss_clean($this->input->post('from')), $data['dcl_details']);
				usort($data['dcl_details'], fn($a, $b) => strcmp($b['qsl_date'], $a['qsl_date']));
			}

			$this->load->view('interface_assets/header', $data);
			$this->load->view('dcl_views/import', $data);
			$this->load->view('interface_assets/footer');
		}
	}

} // end class
