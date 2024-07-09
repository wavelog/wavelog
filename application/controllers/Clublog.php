<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
	Controller to interact with the Clublog API
*/

class Clublog extends CI_Controller
{

	function __construct()
	{
		parent::__construct();

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
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

		$this->load->model('clublog_model');

		// set the last run in cron table for the correct cron id
		$this->load->model('cron_model');
		$this->cron_model->set_last_run($this->router->class . '_' . $this->router->method);

		$users = $this->clublog_model->get_clublog_users();

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

		if (!empty($users)) {
			foreach ($users as $user) {
				$r = $this->clublog_model->downloadUser($user->user_id, $user->user_clublog_name, $user->user_clublog_password);
			}
		} else {
			$r = __("No user has configured Clublog.");
		}

		echo $r;
	}

	function markqso($station_id)
	{
		$clean_station_id = $this->security->xss_clean($station_id);
		$this->load->model('clublog_model');
		$this->clublog_model->mark_qsos_sent($clean_station_id);
	}

	// Find DXCC
	function find_dxcc($callsign)
	{
		$clean_callsign = $this->security->xss_clean($callsign);
		// Live lookup against Clublogs API
		$url = "https://clublog.org/dxcc?call=" . $clean_callsign . "&api=608df94896cb9c5421ae748235492b43815610c9&full=1";

		$json = file_get_contents($url);
		$data = json_decode($json, TRUE);

		// echo ucfirst(strtolower($data['Name']));
		return $data;
	}
}
