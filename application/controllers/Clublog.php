<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller to interact with the Clublog API
*/

class Clublog extends CI_Controller
{

	function __construct()
	{
		parent::__construct();

		if (MAINTENANCE_MODE && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('dashboard');
		}
	}

	// Show frontend if there is one
	public function index()
	{
		// nothing to display
		redirect('dashboard');
	}

	// Upload ADIF to Clublog
	public function upload()
	{
		$this->load->helper('cronauth');
		if (!cronauth_allowed(3)) {
			// return a 403
			$this->output->set_status_header(403);
			exit();
		}

		$this->load->model('clublog_model');

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

		$users = $this->clublog_model->get_clublog_users();

		$r = '';
		if (!empty($users)) {
			foreach ($users as $user) {
				$r = $this->clublog_model->uploadUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password);
			}
		} else {
			$r = __("No user has configured Clublog.");
		}

		echo $r;
	}

	// Download ADIF from Clublog
	public function download()
	{
		$this->load->model('clublog_model');

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

		$users = $this->clublog_model->get_clublog_users();

		$r = '';
		if (!empty($users)) {
			foreach ($users as $user) {
				$r = $this->clublog_model->downloadUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password);
				if ($r == 'Impossible to reach Clublog') { // Stop Download immediatly, because we're blocked
					log_message("Error","We're blocked by Clublog. Stopping Download!");
					break;
				}
				$this->clublog_model->oqrsUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password);
			}
		} else {
			$r = __("No user has configured Clublog.");
		}

		echo $r;
	}

	/*
	 * Used for displaying the uid for manually selecting log for upload to Clublog
	 */
	public function export() {
		if(!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$this->load->model('clublog_model');
		$this->load->model('stations');

		$data['page_title'] = __("Clublog");

		$data['station_profiles'] = $this->stations->all_of_user();

		$data['station_profile'] = $this->clublog_model->stations_with_clublog_enabled();
		$data['callsigns'] = $this->stations->callsigns_of_user($this->session->userdata('user_id'));

		$this->load->model('cron_model');
		$data['next_run_up'] = $this->cron_model->get_next_run("clublog_upload");
		$data['next_run_down'] = $this->cron_model->get_next_run("clublog_download");

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/sections/clublog.js',
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('clublog/export');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function uploadlog() {
		if (!($this->config->item('disable_manual_clublog'))) {
			$this->load->model('clublog_model');

			$clean_station_id = $this->security->xss_clean($this->input->post('station_id'));

			$users = $this->clublog_model->get_clublog_users($this->session->userdata('user_id'));

			if (!empty($users)) {
				foreach ($users as $user) {
					$data['status'] = 'OK';
					$data['infomessage'] = $this->clublog_model->uploadUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password, $clean_station_id);
					$data['errormessages'] = '';
				}
			} else {
				$data['status'] = 'Error';
				$data['errormessages'] = __("No user has configured Clublog.");
			}
			$stationinfo = $this->clublog_model->stations_with_clublog_enabled();
			$info = $stationinfo->result();
			$data['info'] = $info;

			header('Content-type: application/json');
			echo json_encode($data);
		} else {
			redirect('dashboard');
		}
	}

	public function importlog() {
		if (!($this->config->item('disable_manual_clublog'))) {

			if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

			$data['page_title'] = __("Clublog QSL Import");

			$this->load->model('clublog_model');

			$customDate = $this->input->post('date');
			if ($customDate != NULL) {
				$clublog_last_date = date($customDate);
			} else {
				// Query the logbook to determine when the last LoTW confirmation was
				$clublog_last_date = null;
			}
			$users = $this->clublog_model->get_clublog_users($this->session->userdata('user_id'));

			$r = '';
			if (!empty($users)) {
				foreach ($users as $user) {
					$r = $this->clublog_model->downloadUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password, $clublog_last_date);
					$r .= $this->clublog_model->oqrsUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password, $clublog_last_date);
				}
			} else {
				$r = __("No user has configured Clublog.");
			}

			header('Content-type: application/json');
			echo json_encode($r);
		} else {
			redirect('dashboard');
		}
	}

	/**
	 * Test the Club Log credentials of the currently logged in user account.
	 *
	 * Called via AJAX from the user edit page (button "Test Login" in the
	 * Club Log card). Expects a JSON body {clublog_email, clublog_pass}.
	 * If the password arrives as the unaltered placeholder '**********',
	 * the stored credentials are taken from the database instead.
	 * The callsign used for the Club Log auth test is the callsign of the
	 * logged in user account.
	 *
	 * @return void outputs JSON {status: 'OK'|'failed_wrong_creds'|'failed_na'|'failed'|'failed_nocred', details: string}
	 */
	public function check_clublog_credentials() {
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
			exit();
		}
		$ret = [];
		$ret['status'] = '';

		$raw = file_get_contents("php://input");
		try {
			$obj = json_decode($raw, true);
		} catch (Exception $e) {
			$ret['status'] = 'failed_wrongcall';
			log_message("Error", $ret['status']);
		}
		$raw = '';

		$clublog_email = $obj['clublog_email'] ?? '';
		$clublog_pass = $obj['clublog_pass'] ?? '';

		$pw_placeholder = '**********';
		if ($clublog_pass == $pw_placeholder) {	// User comes with unaltered credentials - take them from database
			$query = $this->user_model->get_by_id($this->session->userdata('user_id'));
			$q = $query->row();
			$clublog_email = $q->user_clublog_name ?? '';
			$clublog_pass = $q->user_clublog_password ?? '';
		}

		$clublog_email = trim(xss_clean($clublog_email));
		$clublog_callsign = $this->session->userdata('user_callsign');

		if (($clublog_email != '') && ($clublog_pass != '') && ($ret['status'] != 'failed_wrongcall')) {
			$this->load->model('clublog_model');
			$ret = $this->clublog_model->test_login($clublog_email, $clublog_pass, $clublog_callsign);
		} else {
			if (($ret['status'] ?? '') == '') {
				$ret['status'] = 'failed_nocred';
				$ret['details'] = __("No Club Log credentials provided.");
			}
		}

		header('Content-type: application/json');
		echo json_encode($ret);
	}

}
