<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Debug extends CI_Controller
{
	function __construct() {
		parent::__construct();

		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
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
		$this->load->model('Update_model');

		$footerData = [];
		$footerData['scripts'] = ['assets/js/sections/debug.js'];

		// Get Custom Date format
		if ($this->session->userdata('user_date_format')) {
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			$custom_date_format = $this->config->item('qso_date_format');
		}
		$data['system_time'] = date($custom_date_format . " H:i:s", time());

		$data['running_version'] = $this->optionslib->get_option('version');
		$data['latest_release'] = $this->optionslib->get_option('latest_release');

		$data['newer_version_available'] = false;
		if (function_exists('curl_version')) {
			if (!$this->config->item('disable_version_check') ?? false) {
				$this->Update_model->update_check(true);
				if ($data['latest_release'] && version_compare($data['latest_release'], $data['running_version'], '>')) {
					$data['newer_version_available'] = true;
				}
			}
		}

		$data['stations'] = $this->Stations->all();

		$data['qso_total'] = $this->Debug_model->count_all_qso();
		$data['users_total'] = $this->Debug_model->count_users();
		$data['available_languages'] = $this->config->item('languages');

		$data['qsos_with_no_station_id'] = $this->Logbook_model->check_for_station_id();
		if ($data['qsos_with_no_station_id']) {
			$data['calls_wo_sid'] = $this->Debug_model->calls_without_station_id();
		}

		// get mig version from database
		$data['migration_version'] = $this->Debug_model->getMigrationVersion();

		// get mig version from config file
		$this->load->config('migration');
		$data['migration_config'] = $this->config->item('migration_version');
		$data['migration_lockfile'] = $this->config->item('migration_lockfile');
		$data['miglock_lifetime'] = $this->config->item('migration_lf_maxage');

		// compare mig versions
		if ($data['migration_version'] != $data['migration_config'] && file_exists($data['migration_lockfile'])) {
			$data['migration_is_uptodate'] = false;
		} else {
			$data['migration_is_uptodate'] = true;
		}

		// Test writing to backup folder
		$backup_folder = $this->permissions->is_really_writable('backup');
		$data['backup_folder'] = $backup_folder;

		// Test writing to cache folder
		$cache_folder = $this->permissions->is_really_writable('application/cache');
		$data['cache_folder'] = $cache_folder;

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

		// Cache Info
		$cache_info = $this->Debug_model->get_cache_info();
		$data['cache_available_adapters'] = $cache_info['adapters'];
		$data['cache_path'] = $cache_info['config']['cache_path'] ?: 'application/cache';
		$data['cache_adapter'] = strtolower($cache_info['config']['cache_adapter'] ?? 'file');
		$data['cache_backup'] = strtolower($cache_info['config']['cache_backup'] ?? 'file');
		$data['cache_key_prefix'] = $cache_info['config']['cache_key_prefix'] ?: __("(empty)");
		$data['active_adapter'] = strtolower($cache_info['active']['adapter'] ?? ($cache_info['config']['cache_adapter'] ?? 'file'));
		$data['using_backup'] = !empty($cache_info['active']['using_backup']);
		$data['details_cache_size'] = $cache_info['details']['size'] ?? '0 B';
		$data['details_cache_keys_count'] = $cache_info['details']['keys_count'] ?? 0;
		
		$data['dxcc_update'] = $this->cron_model->cron('update_dxcc')->row();
		$data['dok_update'] = $this->cron_model->cron('update_update_dok')->row();
		$data['lotw_user_update'] = $this->cron_model->cron('update_lotw_users')->row();
		$data['pota_update'] = $this->cron_model->cron('update_update_pota')->row();
		$data['pota_boundaries_update'] = $this->cron_model->cron('update_pota_boundaries')->row();
		$data['scp_update'] = $this->cron_model->cron('update_update_clublog_scp')->row();
		$data['sota_update'] = $this->cron_model->cron('update_update_sota')->row();
		$data['wwff_update'] = $this->cron_model->cron('update_update_wwff')->row();
		$data['tle_update'] = $this->cron_model->cron('update_update_tle')->row();
		$data['hon_update'] = $this->cron_model->cron('update_update_hamsofnote')->row();
		$data['hamqsl_update'] = $this->cron_model->cron('update_update_hamqsl')->row();
		$data['vucc_grids_update'] = $this->cron_model->cron('vucc_grid_file')->row();

		$data['page_title'] = __("Debug");

		$this->load->library('worker');
		$data['worker_status_topic'] = '';
		$data['worker_status_token'] = '';
		$data['worker_enabled'] = $this->worker->is_enabled();
		$urls = $this->config->item('worker_urls', 'worker');
		$data['worker_nodes_total'] = is_array($urls) ? count($urls) : 0;
		if ($data['worker_enabled'] && $this->worker->client_url() !== '') {
			$debug_topic = 'worker.status';
			$data['worker_status_topic'] = $debug_topic;
			$data['worker_status_token'] = $this->worker->create_token($debug_topic);
			$this->worker->register_topic($debug_topic,$data['worker_status_token']);
		}

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
						$status['btn_text'] = __("Migrate data now");
					} else {
						$status['btn_class'] = '';
						$status['btn_text'] = __("Migration already done. Run again?");
					}
				} else {
					$status['btn_class'] = 'disabled';
					$status['btn_text'] = __("No data to migrate");
				}
			} else {
				$status['btn_class'] = 'disabled';
				$status['btn_text'] = __("No migration possible");
			}
		} else {
			// If the folder is not writable, we don't need to continue
			$status['btn_class'] = 'disabled';
			$status['btn_text'] = __("No migration possible");
		}

		return $status;
	}

	public function reassign() {
		$this->load->model('Logbook_model');
		$this->load->model('Stations');

		$call = xss_clean(($this->input->post('call')));
		$qsoids = xss_clean(($this->input->post('qsoids')));
		$station_profile_id = xss_clean(($this->input->post('station_id') ?? 0));

		log_message('debug', 'station_profile_id: '. $station_profile_id);
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

		$stashfile = realpath(APPPATH.'../').'/.updater';
		$maintenancefile = realpath(APPPATH.'../').'/.maintenance';

		if (function_usable('exec')) {
			if (file_exists('.git')) {
				try {
					// enter maintenance mode
					exec('touch '.$maintenancefile);
					log_message('debug', 'Updater: Entered Maintenance mode by creating .maintenance file');

					// we need atleast one file which gets stashed. this file should NOT be in .gitignore
					exec('touch '.$stashfile);
					log_message('debug', 'Updater: Created stashfile');

					// stash everything else
					exec('git stash push --include-untracked');
					log_message('debug', 'Updater: Stash everything');

					// perform the pull
					exec('git fetch');
					exec('git pull');
					log_message('debug', 'Updater: git fetch and git pull');

					// we can now pop all other changes
					exec('git stash pop');
					log_message('debug', 'Updater: Pop stashed changes');

					// Show success message
					$this->session->set_flashdata('success', __("Wavelog was updated successfully!"));

					} catch (\Throwable $th) {
					log_message("Error","Error at selfupdating");
				}
			}
			// delete the stash file
			if(file_exists($stashfile)) {
				exec('rm '.$stashfile);
				log_message('debug', 'Updater: Delete stashfile');
			}
			// exit maintenance mode
			if(file_exists($maintenancefile)) {
				exec('rm '.$maintenancefile);
				log_message('debug', 'Updater: Delete .maintenance file to exit Maintenance Mode');
			}
		} else {
			log_message('error', 'function exec() not available. Debug Controller selfupdate()');
			$this->session->set_flashdata('error', __("Selfupdate() not available. Check the Error Log."));
		}
		redirect('debug');
	}

	private function git_usable() {
		if (!function_usable('exec')) {
			return false;
		}
		if (!is_dir(FCPATH.'.git')) {
			return false;
		}
		exec('command -v git 2>/dev/null', $out, $ret);
		return $ret === 0 && !empty($out);
	}

	public function wavelog_fetch() {
		$versions=[];
		if ($this->git_usable()) {
			try {
				$st=exec('git fetch 2>/dev/null');	// Fetch latest things from Repo. ONLY Fetch. Doesn't hurt since it isn't a pull!
							$versions['branch'] = trim(exec('git rev-parse --abbrev-ref HEAD 2>/dev/null')); // Get ONLY Name of the Branch we're on
				$versions['latest_commit_hash']=substr(trim(exec('git log --pretty="%H" -n1 origin'.'/'.$versions['branch'].' 2>/dev/null')),0,8);	// fetch latest commit-hash from repo
			}  catch (Exception $e) {
				$versions['latest_commit_hash']='';
				$versions['branch']='';
			}
		} else {
			log_message('debug', 'wavelog_fetch() skipped: git not usable (no git binary or not a checkout).');
		}
		header('Content-Type: application/json');
		echo json_encode($versions);
	}

	public function wavelog_version() {
		$commit_hash='';
		if ($this->git_usable()) {
			$commit_hash=substr(trim(exec('git log --pretty="%H" -n1 HEAD 2>/dev/null')),0,8);	// Get latest LOCAL Hash
		} else {
			log_message('debug', 'wavelog_version() skipped: git not usable (no git binary or not a checkout).');
		}
		header('Content-Type: application/json');
		echo json_encode($commit_hash);
	}

	public function clear_cache() {
		$this->load->model('Debug_model');
		$status = $this->Debug_model->clear_cache();

		header('Content-Type: application/json');
		echo json_encode(['status' => (bool) $status]);
		return;
	}

	public function migrate_userdata() {
		$this->load->model('debug_model');
		$migrate = $this->debug_model->migrate_userdata();

		if ($migrate == true) {
			$this->session->set_flashdata('success', __("File Migration was successful, but please check also manually. If everything seems right you can delete the folders 'assets/qslcard' and 'images/eqsl_card_images'."));
			redirect('debug');
		} else {
			$this->session->set_flashdata('error', __("File Migration failed. Please check the Error Log."));
			redirect('debug');
		}
	}

	/**
	 * Returns a simple status summary for the Debug page (no secret required,
	 * but only accessible to logged-in admin users via AJAX).
	 */
	public function worker_status() {
		header('Content-Type: application/json');

		if (!$this->user_model->authorize(2)) {
			http_response_code(403);
			echo json_encode(['success' => false]);
			return;
		}

		$this->load->library('worker');
		$status = $this->worker->status();

		if (!$status['enabled']) {
			echo json_encode(['success' => true, 'disabled' => true, 'workers' => []]);
			return;
		}

		// Map the shared status shape to this endpoint's historic JSON contract.
		$workers = array_map(function ($node) {
			return [
				'public_url'        => $node['url'],
				'alive'             => $node['alive'],
				'version'           => $node['version'],
				'active_topics'     => $node['active_topics'],
				'connected_clients' => $node['connected_clients'],
				'worker_uptime'     => $node['uptime'],
			];
		}, $status['nodes']);

		$vip = $status['vip'] !== null ? ['url' => $status['vip']] : null;

		echo json_encode(['success' => true, 'vip' => $vip, 'workers' => $workers]);
	}

}
