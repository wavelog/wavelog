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

	/**
	 * Shows the SRR page with the key management and, if a key is stored which SRR
	 * did not reject, the upload statistics of all station profiles of the user.
	 * These are built from local data, so they are shown while SRR is unavailable too.
	 *
	 * @return void
	 */
	public function index() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		// The page waits for SRR, so the session is released to keep other pages usable
		session_write_close();

		// Load required models for page generation
		$this->load->model('Srr_model');

		$srr_key = $this->Srr_model->srr_key($this->session->userdata('user_id'));
		if ($srr_key != '') {
			$data['srr_me'] = $this->Srr_model->get_srr_me($srr_key);
			if ($data['srr_me'] === false) {
				$data['error'] = __("The stored SRR key is not valid. Please request a new key.");
			} elseif ($data['srr_me'] === null) {
				$data['error'] = __("SRR could not be reached. Please try again later.");
				$data['srr_unavailable'] = true;
			}
			$data['station_profile'] = $this->Srr_model->stations_with_srr();
		}
		$data['srr_key'] = $srr_key;

		// Set Page Title
		$data['page_title'] = __("SRR");

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

	/**
	 * Validates the posted SRR key against SRR and stores it for the user.
	 * The key is not stored if SRR rejects it or could not be reached.
	 *
	 * @return void Redirects to the SRR page
	 */
	public function store_key() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('Srr_model');

		$srr_key = trim($this->input->post('srr_key', true) ?? '');
		$srr_me = $this->Srr_model->get_srr_me($srr_key);
		if (is_object($srr_me)) {
			$this->Srr_model->store_key($srr_key);
			$this->session->set_flashdata('success', __("Key stored."));
		} elseif ($srr_me === null) {
			$this->session->set_flashdata('error', __("SRR could not be reached. Please try again later."));
		} else {
			$this->session->set_flashdata('error', __("Received an invalid SRR key. Please check the key, and try again."));
		}
		redirect('srr');
	}

	/**
	 * Deletes the stored SRR key of the user.
	 *
	 * @return void Redirects to the SRR page
	 */
	public function delete_key() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		$this->load->model('Srr_model');
		$this->Srr_model->delete_key();
		$this->session->set_flashdata('success', __("Key(s) Deleted."));
		redirect('srr');
	}

	/**
	 * Uploads the QSOs of the posted station profile to SRR. The POST flags
	 * propmode_los and without_rda are set after the user confirmed a dialog.
	 *
	 * @return void Echoes the upload result and the updated statistics as JSON
	 */
	public function upload_station() {
		$this->upload(false);
	}

	/**
	 * Uploads the QSOs which remain after upload_station(). The browser starts it
	 * right after upload_station() and the user does not need to wait for it.
	 *
	 * @return void Echoes the upload result and the updated statistics as JSON
	 */
	public function upload_remaining() {
		$this->upload(true);
	}

	/**
	 * Runs the first or the second part of an upload for the posted station profile
	 *
	 * @param bool $remaining TRUE: the QSOs which remain after the first part
	 * @return void Echoes the upload result and the updated statistics as JSON
	 */
	private function upload($remaining) {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
		ini_set('memory_limit', '-1');

		// The upload waits for SRR, so the session is released to keep other pages usable
		session_write_close();

		$this->load->model('Srr_model');
		$this->load->model('Stations');

		$station_id = $this->security->xss_clean($this->input->post('station_id'));
		$propmode_los = ($this->input->post('propmode_los') == '1');
		$without_rda = ($this->input->post('without_rda') == '1');
		$srr_key = $this->Srr_model->srr_key($this->session->userdata('user_id'));

		if ($srr_key == '') {
			$data['status'] = 'Error';
			$data['errormessages'] = array(__("You need to store an SRR key to use this function."));
		} elseif (!$this->Stations->check_station_is_accessible($station_id)) {
			$data['status'] = 'Error';
			$data['errormessages'] = array(__("You're not allowed to do that!"));
		} else {
			$station_profile = $this->Stations->profile($station_id)->row();
			// The second part follows the first one directly, so only the first part asks SRR
			$upload_error = $remaining ? '' : $this->upload_error($this->Srr_model->get_srr_me($srr_key), $station_profile);
			if ($upload_error != '') {
				$data['status'] = 'Error';
				$data['errormessages'] = array($upload_error);
			} elseif ($remaining) {
				$data = $this->Srr_model->upload_remaining($station_profile, $srr_key, $propmode_los, $without_rda, (int)$this->input->post('uploaded'), (int)$this->input->post('failed'));
			} else {
				$data = $this->Srr_model->upload_station($station_profile, $srr_key, $propmode_los, true, $without_rda);
			}
		}

		$data['info'] = $this->Srr_model->stations_with_srr()->result();

		header('Content-type: application/json');
		echo json_encode($data);
	}

	/**
	 * Returns why the QSOs of a station profile can not be uploaded to SRR. A callsign
	 * which is not on the SRR account would get all QSOs of the upload marked as invalid.
	 *
	 * @param object|false|null $srr_me Result of get_srr_me()
	 * @param object $station_profile Station profile row
	 * @return string Error message, empty if the upload can start
	 */
	private function upload_error($srr_me, $station_profile) {
		if ($srr_me === false) {
			return __("The stored SRR key is not valid. Please request a new key.");
		}
		if ($srr_me === null) {
			return __("SRR could not be reached. Please try again later.");
		}
		if (!$this->Srr_model->callsign_on_account($station_profile->station_callsign, $srr_me)) {
			return $station_profile->station_callsign." (".$station_profile->station_profile_name."): ".__("This callsign is not on your SRR account, so nothing was uploaded.");
		}
		return '';
	}

	/**
	 * Downloads the SRR confirmations of the user, optionally since the posted date.
	 *
	 * @return void Echoes the download result as JSON
	 */
	public function download() {
		if (!$this->user_model->authorize(2) || !clubaccess_check(9)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		// The download waits for SRR, so the session is released to keep other pages usable
		session_write_close();

		$this->load->model('Srr_model');

		$srr_key = $this->Srr_model->srr_key($this->session->userdata('user_id'));
		if ($srr_key == '') {
			$r = __("You need to store an SRR key to use this function.");
		} else {
			$r = $this->Srr_model->download_user($this->session->userdata('user_id'), $srr_key, $this->input->post('date', true));
		}

		header('Content-type: application/json');
		echo json_encode($r);
	}

	/**
	 * Called by cron. Uploads the QSOs of all users with an SRR key
	 * and downloads their confirmations. Users whose key SRR rejects and stations
	 * whose callsign is not on the SRR account are skipped. The run stops if SRR is unavailable.
	 *
	 * @return void Echoes the result per station profile and user
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
			echo __("No user has configured SRR.");
			return;
		}

		foreach ($users as $user) {
			$srr_me = $this->Srr_model->get_srr_me($user->option_value);
			if ($srr_me === false) {
				log_message('error', 'SRR rejected the key of user '.$user->user_id.'. Skipping this user.');
				echo sprintf(__("User %d: the stored SRR key is not valid."), $user->user_id)."<br>";
				continue;
			}
			if ($srr_me === null) {	// Stop the run immediately, because the next users would have to wait for SRR too
				log_message('error', 'SRR could not be reached. Stopping the SRR sync!');
				echo __("SRR could not be reached. Please try again later.")."<br>";
				break;
			}

			$station_profiles = $this->Stations->all_of_user($user->user_id);
			foreach ($station_profiles->result() as $station_profile) {
				$upload_error = $this->upload_error($srr_me, $station_profile);
				if ($upload_error != '') {
					echo $upload_error."<br>";
					continue;
				}
				$result = $this->Srr_model->upload_station($station_profile, $user->option_value, false, false);
				if ($result['infomessage'] != '') {
					echo $result['infomessage']."<br>";
				}
				foreach ($result['errormessages'] as $errormessage) {
					echo $errormessage."<br>";
				}
			}
			echo $this->Srr_model->download_user($user->user_id, $user->option_value, null, $srr_me)."<br>";
		}
	}

} // end class
