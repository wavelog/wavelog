<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class adif extends CI_Controller {

	/* Controls ADIF Import/Export Functions */

	private $allowed_tabs = [];

	private $tab_method_mapping = [
		'import' => ['import'],
		'export' => ['export_custom', 'exportall', 'exportsat', 'exportsatlotw'],
		'lotw' => ['lotw', 'export_lotw'],
		'dcl' => ['dcl'],
		'pota' => ['pota'],
		'cbr' => []
	];

	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2) || (!clubaccess_check(6) && !clubaccess_check(9))) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->determine_allowed_tabs();
		$this->check_tab_access();
	}

	private function determine_allowed_tabs() {
		if (clubaccess_check(6) && !clubaccess_check(9)) {
			// Only ClubMember ADIF and NOT ClubOfficer
			$this->allowed_tabs = ['import', 'export'];
		} else {
			// Default: show all tabs (backward compatible)
			$this->allowed_tabs = ['import', 'export', 'lotw', 'dcl', 'pota', 'cbr'];
		}

	}

	private function check_tab_access() {
		// Get current method using CI's built-in method
		$current_method = $this->router->method;

		// Skip access check for some common methods
		$skip_methods = ['index', '__construct', 'determine_allowed_tabs', 'check_tab_access', 'get_allowed_tabs', 'test'];
		if (in_array($current_method, $skip_methods)) {
			return;
		}

		// Find which tab(s) this method belongs to
		$required_tabs = [];
		foreach ($this->tab_method_mapping as $tab => $methods) {
			if (in_array($current_method, $methods)) {
				$required_tabs[] = $tab;
			}
		}

		// Check if user has access to required tabs
		foreach ($required_tabs as $tab) {
			if (!in_array($tab, $this->allowed_tabs)) {
				$this->session->set_flashdata('error', __("You're not allowed to access this functionality!"));
				redirect('adif');
			}
		}
	}

	private function get_allowed_tabs() {
		return $this->allowed_tabs;
	}

	private function require_tab_access($required_tab) {
		if (!in_array($required_tab, $this->allowed_tabs)) {
			$this->session->set_flashdata('error', __("You're not allowed to access this functionality!"));
			redirect('adif');
		}
	}

	public function test() {
		if(validateADIFDate('20120228') == true){
			echo __("valid date");
		} else {
			echo __("date incorrect");
		}


	}

	// Export all QSO Data in ASC Order of Date - use chunks to avoid memory exhaustion
	public function exportall() {
		$this->require_tab_access('export');

		ini_set('memory_limit', '-1');
		set_time_limit(300);

		$this->load->model('adif_data');
		$this->load->library('AdifHelper');

		$from = $this->input->post('from', true);
		$to = $this->input->post('to', true);

		$filename = $this->session->userdata('user_callsign').'-'.date('Ymd-Hi').'.adi';
		header('Content-Type: text/plain; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');

		// Output ADIF header // No chance to use exportall-view any longer, because of chunking logic
		echo $this->adifhelper->getAdifHeader($this->config->item('app_name'),$this->optionslib->get_option('version'));

		// Stream QSOs in 5K chunks
		$offset = 0;
		$chunk_size = 5000;

		do {
			$qsos = $this->adif_data->export_all_chunked(null, $from, $to, false, null, $offset, $chunk_size);

			if ($qsos->num_rows() > 0) {
				foreach ($qsos->result() as $qso) {
					echo $this->adifhelper->getAdifLine($qso);
				}

				// Free memory
				$qsos->free_result();
			}

			$offset += $chunk_size;
		} while ($qsos->num_rows() > 0);

		exit;
	}


	// Export all QSO Data in ASC Order of Date.
	public function exportsat() {
		// Set memory limit to unlimited to allow heavy usage
		$this->require_tab_access('export');
		ini_set('memory_limit', '-1');

		$this->load->model('adif_data');

		if (clubaccess_check(6) && !clubaccess_check(9)) {
			$onlyop=($this->session->userdata('operator_callsign') ?? '');
		} else {
			$onlyop=null;
		}

		$data['qsos'] = $this->adif_data->sat_all($onlyop);

		$this->load->view('adif/data/exportsat', $data);
	}

	// Export all QSO Data in ASC Order of Date.
	public function exportsatlotw() {
		// Set memory limit to unlimited to allow heavy usage
		$this->require_tab_access('export');
		ini_set('memory_limit', '-1');

		if (clubaccess_check(6) && !clubaccess_check(9)) {
			$onlyop=($this->session->userdata('operator_callsign') ?? '');
		} else {
			$onlyop=null;
		}

		$this->load->model('adif_data');

		$data['qsos'] = $this->adif_data->satellte_lotw($onlyop);

		$this->load->view('adif/data/exportsat', $data);
	}

	public function export_custom() {
		// Check if user has access to export tab
		$this->require_tab_access('export');

		// Set memory limit to unlimited to allow heavy usage
		ini_set('memory_limit', '-1');
		set_time_limit(300);

		$this->load->model('adif_data');
		$this->load->library('AdifHelper');
		$this->load->model('logbook_model');

		// Get parameters
		$station_id = $this->security->xss_clean($this->input->post('station_profile'));
		$from = $this->input->post('from');
		$to = $this->input->post('to');

		// Used for exporting QSOs not previously exported to LoTW
		if ($this->input->post('exportLotw') == 1) {
			$exportLotw = true;
		} else {
			$exportLotw = false;
		}

		if (clubaccess_check(6) && !clubaccess_check(9)) {
			$onlyop=($this->session->userdata('operator_callsign') ?? '');
		} else {
			$onlyop=null;
		}

		// Set headers for direct download
		$filename = $this->session->userdata('user_callsign').'-'.date('Ymd-Hi').'.adi';
		header('Content-Type: text/plain; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');

		echo $this->adifhelper->getAdifHeader($this->config->item('app_name'),$this->optionslib->get_option('version'));

		// Collect QSO IDs for LoTW marking (since we can't access all at once)
		$qso_ids_for_lotw = [];

		// Stream QSOs in 5K chunks
		$offset = 0;
		$chunk_size = 5000;

		do {
			$qsos = $this->adif_data->export_custom_chunked($from, $to, $station_id, $exportLotw, $onlyop, $offset, $chunk_size);

			if ($qsos && $qsos->num_rows() > 0) {
				foreach ($qsos->result() as $qso) {
					echo $this->adifhelper->getAdifLine($qso);
					// Collect IDs for LoTW marking
					$qso_ids_for_lotw[] = $qso->COL_PRIMARY_KEY;
				}
				// Free memory
				$qsos->free_result();
			}

			$offset += $chunk_size;
		} while ($qsos && $qsos->num_rows() > 0);

		// Handle LoTW marking after export
		if ((clubaccess_check(9)) && ($this->input->post('markLotw') == 1) && !empty($qso_ids_for_lotw)) {
			foreach ($qso_ids_for_lotw as $qso_id) {
				$this->logbook_model->mark_lotw_sent($qso_id);
			}
		}

		// Stop execution
		exit;
	}

	public function export_lotw() {
		// Check if user has access to lotw tab
		$this->require_tab_access('lotw');

		// Set memory limit to unlimited to allow heavy usage
		ini_set('memory_limit', '-1');

		$this->load->model('adif_data');
		$this->load->model('logbook_model');

		$data['qsos'] = $this->adif_data->export_lotw();

		$this->load->view('adif/data/exportall', $data);

		foreach ($data['qsos']->result() as $qso) {
			$this->logbook_model->mark_lotw_sent($qso->COL_PRIMARY_KEY);
		}
	}

	public function index() {
		$this->load->model('contesting_model');
		$data['contests']=$this->contesting_model->getActivecontests();

		$this->load->model('stations');

		$data['page_title'] = __("ADIF Import / Export");
		$data['max_upload'] = ini_get('upload_max_filesize');
		$data['cd_p_level'] = ($this->session->userdata('cd_p_level') ?? 0);

		if ($this->config->item('special_callsign') && clubaccess_check(9) && $this->session->userdata('clubstation') == 1) {
			$this->load->model('club_model');
			$data['club_operators'] = $this->club_model->get_club_members($this->session->userdata('user_id'));
		} else {
			$data['club_operators'] = false;
		}

		$data['station_profile'] = $this->stations->all_of_user();
		$active_station_id = $this->stations->find_active();
		$station_profile = $this->stations->profile($active_station_id);

		$data['active_station_info'] = $station_profile->row();
		$data['active_station_id'] = $active_station_id;

		// Pass allowed tabs to view
		$data['allowed_tabs'] = $this->get_allowed_tabs();

		$this->load->view('interface_assets/header', $data);
		$this->load->view('adif/import', $data);
		$this->load->view('interface_assets/footer');
	}

	public function import() {
		// Check if user has access to import tab
		$this->require_tab_access('import');

		$this->load->model('stations');
		$data['station_profile'] = $this->stations->all_of_user();

		$active_station_id = $this->stations->find_active();
		$station_profile = $this->stations->profile($active_station_id);

		$data['active_station_info'] = $station_profile->row();

		$data['page_title'] = __("ADIF Import");
		$data['tab'] = "adif";

		// Pass allowed tabs to view
		$data['allowed_tabs'] = $this->get_allowed_tabs();

		$config['upload_path'] = './uploads/';
		$config['allowed_types'] = 'adi|ADI|adif|ADIF|zip';

		log_message("Error","ADIF Start");
		session_write_close();
		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload()) {
			$data['error'] = $this->upload->display_errors();
			$data['max_upload'] = ini_get('upload_max_filesize');

			$this->load->view('interface_assets/header', $data);
			$this->load->view('adif/import', $data);
			$this->load->view('interface_assets/footer');
		} else {
			if ($this->stations->check_station_is_accessible($this->input->post('station_profile', TRUE))) {
				$contest=$this->input->post('contest', true) ?? '';
				$club_operator=$this->input->post('club_operator', true) ?? '';
				$stopnow=false;
				$fdata = array('upload_data' => $this->upload->data());
				ini_set('memory_limit', '-1');
				set_time_limit(0);

				$this->load->model('logbook_model');

				$f_elements=explode(".",$fdata['upload_data']['file_name']);
				if (strtolower($f_elements[count($f_elements)-1])=='zip') {
					$f_adif = preg_replace('/\\.zip$/', '', $fdata['upload_data']['file_name']);
					$p_adif = hash('sha256', $this->session->userdata('user_callsign') ).'.adif';
					if (preg_match("/.*\.adi.?$/",strtolower($p_adif))) {	// Check if adi? inside zip
						$zip = new ZipArchive;
						if ($zip->open('./uploads/'.$fdata['upload_data']['file_name'])) {
							$zip->extractTo("./uploads/",array($p_adif));
							$zip->close();
						}
						unlink('./uploads/'.$fdata['upload_data']['file_name']);
					} else {
						unlink('./uploads/'.$fdata['upload_data']['file_name']);
						$data['error'] = __("Unsupported Filetype");
						$stopnow=true;
					}
				} else {
					$p_adif=$fdata['upload_data']['file_name'];
				}
				if (!($stopnow)) {

					if (!$this->load->is_loaded('adif_parser')) {
						$this->load->library('adif_parser');
					}

					$this->adif_parser->load_from_file('./uploads/'.$p_adif);
					unlink('./uploads/'.$p_adif);
					$fdata['upload_data']='';	// free memory

					$this->adif_parser->initialize();
					$custom_errors['errormessage'] = "";
					$alladif = [];
					$contest_qso_infos = [];
					while($record = $this->adif_parser->get_record()) {

						// Handle slashed zeros
						if (isset($record['call'])) {
							$record['call'] = str_replace('Ø', "0", $record['call']);
						}
						if (($record['operator'] ?? '') != '') {
							$record['operator'] = str_replace('Ø', "0", $record['operator']);
						}
						if (($record['station_callsign'] ?? '') != '') {
							$record['station_callsign'] = str_replace('Ø', "0", $record['station_callsign']);
						}
						if (($record['owner_callsign'] ?? '') != '') {
							$record['owner_callsign'] = str_replace('Ø', "0", $record['owner_callsign']);
						}

						//overwrite the contest id if user chose a contest in UI
						if ($contest != '') {
							$record['contest_id'] = $contest;
						}

						//handle club operator based on permission level
						$user_permission_level = $this->session->userdata('cd_p_level');
						if ($user_permission_level >= 9) {
							// Club Officer: Allow operator override
							if ($club_operator != '') {
								$record['operator'] = strtoupper($club_operator);
							}
						} elseif ($user_permission_level == 6) {
							// ClubMemberADIF: Force operator to current user, ignore input
							$record['operator'] = strtoupper($this->session->userdata('operator_callsign'));
						}
						// Note: Regular Club Member (Level 3) should not reach here due to constructor permission check

						//check if contest_id exists in record and extract all found contest_ids
						if(array_key_exists('contest_id', $record)){
							$contest_id = $record['contest_id'];
							if($contest_id != ''){
								if(array_key_exists($contest_id, $contest_qso_infos)){
									$contest_qso_infos[$contest_id] += 1;
								}else{
									$contest_qso_infos[$contest_id] = 1;
								}
							}
						}

						if(count($record) == 0) {
							break;
						};
						array_push($alladif, $record);
					};
					$record='';	// free memory
					try {
						if (($this->input->post('skipDuplicate',true) ?? '') == '1') {	// Reverse Logic. View states: "Import Dupes", while Flag is called skipDuplicates
							$skipDups=false;	// Box ticked? Means: Import Dupes
						} else {
							$skipDups=true;		// Box not ticked? Means: Skip Dupes, don't import them
						}
						$custom_errors = $this->logbook_model->import_bulk($alladif, $this->input->post('station_profile', TRUE), $skipDups, $this->input->post('markClublog'),$this->input->post('markLotw'), $this->input->post('dxccAdif'), $this->input->post('markQrz'), $this->input->post('markEqsl'), $this->input->post('markHrd'), $this->input->post('markDcl'), true, $this->input->post('operatorName') ?? false, false, $this->input->post('skipStationCheck'));
					} catch (Exception $e) {
						log_message('error', 'Import error: '.$e->getMessage());
						$data['page_title'] = __("ADIF Import failed!");
						$this->load->view('interface_assets/header', $data);
						$this->load->view('adif/import_failed');
						$this->load->view('interface_assets/footer');
						return;
					}
				} else {	// Failure, if no ADIF inside ZIP
					$data['max_upload'] = ini_get('upload_max_filesize');
					$this->load->view('interface_assets/header', $data);
					$this->load->view('adif/import', $data);
					$this->load->view('interface_assets/footer');
					return;
				}
			} else {
				$custom_errors['errormessage'] = __("Station Profile not valid for User");
			}

			log_message("Error","ADIF End");
			$data['adif_errors'] = $custom_errors['errormessage'];
			$data['qsocount'] = $custom_errors['qsocount'] ?? 0;
			$data['skip_dupes'] = $this->input->post('skipDuplicate');
			$data['imported_contests'] = $contest_qso_infos;

			$data['page_title'] = __("ADIF Imported");
			$this->load->view('interface_assets/header', $data);
			$this->load->view('adif/import_success');
			$this->load->view('interface_assets/footer');
		}
	}

	public function dcl() {
		// Check if user has access to dcl tab
		$this->require_tab_access('dcl');

		$this->load->model('stations');
		$data['station_profile'] = $this->stations->all_of_user();

		$data['page_title'] = __("DCL Import");
		$data['tab'] = "dcl";

		// Pass allowed tabs to view
		$data['allowed_tabs'] = $this->get_allowed_tabs();

		$config['upload_path'] = './uploads/';
		$config['allowed_types'] = 'adi|ADI|adif|ADIF';

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload()) {
			$data['error'] = $this->upload->display_errors();

			$data['max_upload'] = ini_get('upload_max_filesize');

			$this->load->view('interface_assets/header', $data);
			$this->load->view('adif/import', $data);
			$this->load->view('interface_assets/footer');
		} else {
			$data = array('upload_data' => $this->upload->data());

			ini_set('memory_limit', '-1');
			set_time_limit(0);

			$this->load->model('logbook_model');

			if (!$this->load->is_loaded('adif_parser')) {
				$this->load->library('adif_parser');
			}

			$this->adif_parser->load_from_file('./uploads/'.$data['upload_data']['file_name']);

			$this->adif_parser->initialize();
			$error_count = array(0, 0, 0);
			$custom_errors = "";
			while($record = $this->adif_parser->get_record())
			{
				if(count($record) == 0) {
					break;
				};

				$dok_result = $this->logbook_model->update_dok($record, $this->input->post('ignoreAmbiguous'), $this->input->post('onlyConfirmed'), $this->input->post('overwriteDok'));
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
			};
			unlink('./uploads/'.$data['upload_data']['file_name']);
			$data['dcl_error_count'] = $error_count;
			$data['dcl_errors'] = $custom_errors;
			$data['page_title'] = __("DCL Data Imported");
			$this->load->view('interface_assets/header', $data);
			$this->load->view('adif/dcl_success');
			$this->load->view('interface_assets/footer');
		}
	}

	public function pota() {
		// Check if user has access to pota tab
		$this->require_tab_access('pota');

		$this->load->model('stations');
		$data['station_profile'] = $this->stations->all_of_user();

		$data['page_title'] = __("POTA Import");
		$data['tab'] = "potab";

		// Pass allowed tabs to view
		$data['allowed_tabs'] = $this->get_allowed_tabs();

		$config['upload_path'] = './uploads/';
		$config['allowed_types'] = 'adi|ADI|adif|ADIF';

		$this->load->library('upload', $config);

		if ( ! $this->upload->do_upload()) {
			$data['error'] = $this->upload->display_errors();

			$data['max_upload'] = ini_get('upload_max_filesize');

			$this->load->view('interface_assets/header', $data);
			$this->load->view('adif/import', $data);
			$this->load->view('interface_assets/footer');
		} else {
			$data = array('upload_data' => $this->upload->data());

			ini_set('memory_limit', '-1');
			set_time_limit(0);

			$this->load->model('logbook_model');

			if (!$this->load->is_loaded('adif_parser')) {
				$this->load->library('adif_parser');
			}

			$this->adif_parser->load_from_file('./uploads/'.$data['upload_data']['file_name']);

			$this->adif_parser->initialize();
			$error_count = array(0, 0, 0);
			$custom_errors = "";
			while($record = $this->adif_parser->get_record())
			{
				if(count($record) == 0) {
					break;
				};

				$pota_result = $this->logbook_model->update_pota($record);
				if (!empty($pota_result)) {
					switch ($pota_result[0]) {
					case 0:
						$error_count[0]++;
						break;
					case 1:
						$error_count[1]++;
						break;
					case 2:
						$custom_errors .= $pota_result[1];
						$error_count[2]++;
					}
				}
			};
			unlink('./uploads/'.$data['upload_data']['file_name']);
			$data['pota_error_count'] = $error_count;
			$data['pota_errors'] = $custom_errors;
			$data['page_title'] = __("POTA Data Imported");
			$this->load->view('interface_assets/header', $data);
			$this->load->view('adif/pota_success');
			$this->load->view('interface_assets/footer');
		}
	}
}

/* End of file adif.php */
