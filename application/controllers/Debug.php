<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Debug extends CI_Controller
{
	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('notice', 'You\'re not allowed to do that!');
			redirect('dashboard');
		}

		$this->load->library('Permissions');
	}

	/* User Facing Links to Backup URLs */
	public function index() {
		$this->load->helper('file');

		$this->load->model('Logbook_model');
		$this->load->model('Debug_model');
		$this->load->model('Stations');
		$this->load->model('cron_model');

		$footerData = [];
		$footerData['scripts'] = ['assets/js/sections/debug.js'];

		$data['stations'] = $this->Stations->all();

		$data['qso_total'] = $this->Debug_model->count_all_qso();

		$data['qsos_with_no_station_id'] = $this->Logbook_model->check_for_station_id();
		if ($data['qsos_with_no_station_id']) {
			$data['calls_wo_sid'] = $this->Debug_model->calls_without_station_id();
		}

		$data['migration_version'] = $this->Debug_model->getMigrationVersion();

		// Test writing to backup folder
		$backup_folder = $this->permissions->is_really_writable('backup');
		$data['backup_folder'] = $backup_folder;

		// Test writing to updates folder
		$updates_folder = $this->permissions->is_really_writable('updates');
		$data['updates_folder'] = $updates_folder;

		// Test writing to uploads folder
		$uploads_folder = $this->permissions->is_really_writable('uploads');
		$data['uploads_folder'] = $uploads_folder;

		// Check if userdata config is enabled
		$userdata_enabled = $this->config->item('userdata');
		$data['userdata_enabled'] = $userdata_enabled;

		if (isset($userdata_enabled)) {
			// Test writing to userdata folder if option is enabled
			$userdata_folder = $this->permissions->is_really_writable('userdata');
			$data['userdata_folder'] = $userdata_folder;

			// run the status check and return the array to the view
			$userdata_status = $this->check_userdata_status($userdata_folder);
			$data['userdata_status'] = $userdata_status;
		}

		$data['dxcc_update'] = $this->cron_model->cron('update_dxcc')->row();
		$data['dok_update'] = $this->cron_model->cron('update_update_dok')->row();
		$data['lotw_user_update'] = $this->cron_model->cron('update_lotw_users')->row();
		$data['pota_update'] = $this->cron_model->cron('update_update_pota')->row();
		$data['scp_update'] = $this->cron_model->cron('update_update_clublog_scp')->row();
		$data['sota_update'] = $this->cron_model->cron('update_update_sota')->row();
		$data['wwff_update'] = $this->cron_model->cron('update_update_wwff')->row();

		$data['page_title'] = "Debug";

		$this->load->view('interface_assets/header', $data);
		$this->load->view('debug/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	function check_userdata_status($userdata_folder) {
		$this->load->model('debug_model');

		$status = array();

		// Check if the folder is writable
		if ($userdata_folder === true) {

			// Check if the qsl and eqsl folders are accessible and if there is any data the user could migrate
			$qsl_dir = $this->permissions->is_really_writable('assets/qslcard');
			$eqsl_dir = $this->permissions->is_really_writable('images/eqsl_card_images');

			$flag_file = $this->debug_model->check_migrated_flag();

			if ($qsl_dir && $eqsl_dir) {

				// Check for content of the qsl card folder other than *.html files
				$qsl_files = glob('assets/qslcard/*');
				$qsl_files_filtered = array_filter($qsl_files, function ($file) {
					return !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'html';
				});

				// Check for content of the eqsl card folder other than *.html files
				$eqsl_files = glob('images/eqsl_card_images/*');
				$eqsl_files_filtered = array_filter($eqsl_files, function ($file) {
					return !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'html';
				});

				// Set the status info
				if (!empty($qsl_files_filtered) || !empty($eqsl_files_filtered)) {
					if (!$flag_file) {
						$status['btn_class'] = '';
						$status['btn_text'] = 'Migrate data now';
					} else {
						$status['btn_class'] = '';
						$status['btn_text'] = 'Migration already done. Run again?';
					}
				} else {
					$status['btn_class'] = 'disabled';
					$status['btn_text'] = 'No data to migrate';
				}
			} else {
				$status['btn_class'] = 'disabled';
				$status['btn_text'] = 'No migration possible';
			}
		} else {
			// If the folder is not writable, we don't need to continue
			$status['btn_class'] = 'disabled';
			$status['btn_text'] = 'No migration possible';
		}

		return $status;
	}

	public function reassign() {
		$this->load->model('Logbook_model');
		$this->load->model('Stations');

		$call = xss_clean(($this->input->post('call')));
		$qsoids = xss_clean(($this->input->post('qsoids')));
		$station_profile_id = xss_clean(($this->input->post('station_id')));

		log_message('debug', 'station_profile_id:', $station_profile_id);
		// Check if target-station-id exists
		$allowed = false;
		$status = false;
		$stations = $this->Stations->all();
		foreach ($stations->result() as $station) {
			if ($station->station_id == $station_profile_id) {
				$allowed = true;
			}
		}
		if ($allowed) {
			$status = $this->Logbook_model->update_station_ids($station_profile_id, $call, $qsoids);
		} else {
			$status = false;
		}

		header('Content-Type: application/json');
		echo json_encode(array('status' => $status));
		return;
	}

	public function selfupdate() {
		if (file_exists('.git')) {
			try {
				$st=exec('touch '.realpath(APPPATH.'../').'/.maintenance');
				$st=exec('git stash push --include-untracked');
				$st=exec('git fetch');
				$st=exec('git pull');
				$st=exec('git stash pop');
				$st=exec('rm '.realpath(APPPATH.'../').'/.maintenance');
               		} catch (\Throwable $th) {
				log_message("Error","Error at selfupdating");
			}
		}
		redirect('debug');
	}

	public function wavelog_fetch() {
		$a_versions=[];
		try {
			$st=exec('git fetch');	// Fetch latest things from Repo. ONLY Fetch. Doesn't hurt since it isn't a pull!
                        $versions['branch'] = trim(exec('git rev-parse --abbrev-ref HEAD')); // Get ONLY Name of the Branch we're on
			$versions['latest_commit_hash']=substr(trim(exec('git log --pretty="%H" -n1 origin'.'/'.$versions['branch'])),0,8);	// fetch latest commit-hash from repo
		}  catch (Exception $e) {
			$versions['latest_commit_hash']='';
			$versions['branch']='';
		}
		header('Content-Type: application/json');
		echo json_encode($versions);
	}

	public function wavelog_version() {
		$commit_hash=substr(trim(exec('git log --pretty="%H" -n1 HEAD')),0,8);	// Get latest LOCAL Hash
		header('Content-Type: application/json');
		echo json_encode($commit_hash);
	}

	public function migrate_userdata() {
		// Check if users logged in
		$this->load->model('user_model');
		if ($this->user_model->validate_session() == 0) {
			// user is not logged in
			redirect('user/login');
		} else {
			$this->load->model('debug_model');
			$migrate = $this->debug_model->migrate_userdata();

			if ($migrate == true) {
				$this->session->set_flashdata('success', 'File Migration was successfull, but please check also manually. If everything seems right you can delete the folders "assets/qslcard" and "images/eqsl_card_images".');
				redirect('debug');
			} else {
				$this->session->set_flashdata('error', 'File Migration failed. Please check the Error Log.');
				redirect('debug');
			}
		}
	}
}
