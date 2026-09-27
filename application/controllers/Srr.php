<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Srr extends CI_Controller {

	/* Controls who can access the controller and its functions */
	function __construct() {
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		if (!($this->config->item('enable_srr_interface') ?? false)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); exit; }
		if (MAINTENANCE_MODE && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}
	}

	public function index() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		// Load required models for page generation
		$this->load->model('Srr_model');

		$srr_key = $this->Srr_model->srr_key($this->session->userdata('user_id'));
		if ($srr_key != '') {
			$data['srr_me'] = $this->Srr_model->get_srr_me($srr_key);
			if ($data['srr_me'] === false) {
				$data['error'] = __("The stored award.srr key is not valid. Please request a new key.");
			}
			$data['station_profile'] = $this->Srr_model->stations_with_srr();
		}
		$data['srr_key'] = $srr_key;

		// Set Page Title
		$data['page_title'] = __("award.srr");

		$this->load->model('cron_model');
		$data['next_run'] = $this->cron_model->get_next_run("sync_srr");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/srr.js',
		];

		// Load Views
		$this->load->view('interface_assets/header', $data);
		$this->load->view('srr/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function store_key() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('Srr_model');

		$srr_key = trim($this->input->post('srr_key', true) ?? '');
		if ($this->Srr_model->get_srr_me($srr_key) !== false) {
			$this->Srr_model->store_key($srr_key);
			$this->session->set_flashdata('success', __("Key stored."));
		} else {
			$this->session->set_flashdata('error', __("Received an invalid award.srr key. Please check the key, and try again."));
		}
		redirect('srr');
	}

	public function delete_key() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('Srr_model');
		$this->Srr_model->delete_key();
		$this->session->set_flashdata('success', __("Key(s) Deleted."));
		redirect('srr');
	}

	public function upload_station() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		ini_set('memory_limit', '-1');

		$this->load->model('Srr_model');
		$this->load->model('Stations');

		$station_id = $this->security->xss_clean($this->input->post('station_id'));
		$propmode_los = ($this->input->post('propmode_los') == '1');
		$without_rda = ($this->input->post('without_rda') == '1');
		$srr_key = $this->Srr_model->srr_key($this->session->userdata('user_id'));

		if ($srr_key == '') {
			$data['status'] = 'Error';
			$data['errormessages'] = array(__("You need to store an award.srr key to use this function."));
		} elseif (!$this->Stations->check_station_is_accessible($station_id)) {
			$data['status'] = 'Error';
			$data['errormessages'] = array(__("You're not allowed to do that!"));
		} else {
			$station_profile = $this->Stations->profile($station_id)->row();
			$data = $this->Srr_model->upload_station($station_profile, $srr_key, $propmode_los, true, $without_rda);
		}

		$data['info'] = $this->Srr_model->stations_with_srr()->result();

		header('Content-type: application/json');
		echo json_encode($data);
	}

	public function download() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('Srr_model');

		$srr_key = $this->Srr_model->srr_key($this->session->userdata('user_id'));
		if ($srr_key == '') {
			$r = __("You need to store an award.srr key to use this function.");
		} else {
			$r = $this->Srr_model->download_user($this->session->userdata('user_id'), $srr_key, $this->input->post('date', true));
		}

		header('Content-type: application/json');
		echo json_encode($r);
	}

	/*
	|--------------------------------------------------------------------------
	| Function: srr_sync
	|--------------------------------------------------------------------------
	|
	|	Called by cron. Uploads the QSOs of all users with an award.srr key
	|	and downloads their confirmations.
	|
	 */
	public function srr_sync() {
		$this->load->helper('cronauth');
		if (!cronauth_allowed(3)) {
			// return a 403
			$this->output->set_status_header(403);
			exit();
		}
		ini_set('memory_limit', '-1');

		$this->load->model('Srr_model');
		$this->load->model('Stations');

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run('sync_srr');

		$users = $this->Srr_model->srr_users();
		if (empty($users)) {
			echo __("No user has configured award.srr.");
			return;
		}

		foreach ($users as $user) {
			$station_profiles = $this->Stations->all_of_user($user->user_id);
			foreach ($station_profiles->result() as $station_profile) {
				$result = $this->Srr_model->upload_station($station_profile, $user->option_value, false, false);
				if ($result['infomessage'] != '') {
					echo $result['infomessage']."<br>";
				}
				foreach ($result['errormessages'] as $errormessage) {
					echo $errormessage."<br>";
				}
			}
			echo $this->Srr_model->download_user($user->user_id, $user->option_value)."<br>";
		}
	}

} // end class
