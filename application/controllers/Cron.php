<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once './src/Cron/vendor/autoload.php';

// TODO 
// Add 'add' / 'edit' functions to be able to add/edit the crons in the cron manager view

class cron extends CI_Controller 
{
	function __construct() {

		parent::__construct();

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
			echo "Maintenance Mode is active. Try again later.\n";
			redirect('user/login');
		}
	}

	public function index() {

		$this->load->model('user_model');
		if(!$this->user_model->authorize(99)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }

		$this->load->helper('file');

		$this->load->model('cron_model');

		$footerData = [];
		$footerData['scripts'] = ['assets/js/sections/cron.js'];

		$data['page_title'] = "Cron Manager";
		$data['crons'] = $this->cron_model->get_crons();

		$this->load->view('interface_assets/header', $data);
		$this->load->view('cron/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function run() {

		// This is the main function, which handles all crons, runs them if enabled and writes the 'next run' timestamp to the database

		// TODO Add an API Key to the cronjob to improve security?

		$this->load->model('cron_model');

		$crons = $this->cron_model->get_crons();

		foreach ($crons as $cron) {
			if ($cron->enabled == 1) {

				// calculate the crons expression
				$cronjob = new Poliander\Cron\CronExpression($cron->expression);
				$dt = new \DateTime();
				$isdue = $cronjob->isMatching($dt);
				if ($isdue == true) {
					$isdue_result = 'true';

					// TODO Add log_message level debug here to have logging for the cron manager

					echo "CRON: ".$cron->id." -> is due: ".$isdue_result."\n";  
					echo "CRON: ".$cron->id." -> RUNNING...\n";

					$url = base_url().$cron->function;

					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_HEADER, false);
					curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$crun = curl_exec($ch);
					curl_close($ch);

					if ($crun !== false) {
						echo "CRON: ".$cron->id." -> CURL Result: ".$crun."\n";

						// TODO Proper testing if '!== false' is enough for all functions. 
						// TODO Make Curl Result available in Cron Manager view?

					} else {
						echo "ERROR: Something went wrong with ".$cron->id."\n";

						// TODO Add a flag or some kind of warning for the cron manager view?

					}
				} else {
					$isdue_result = 'false';
					echo "CRON: ".$cron->id." -> is due: ".$isdue_result."\n";
				}

				$next_run = $cronjob->getNext();
				$next_run_date = date('Y-m-d H:i:s', $next_run);
				echo "CRON: " . $cron->id . " -> Next Run: " . $next_run_date . "\n";
				$this->cron_model->set_next_run($cron->id, $next_run_date);

			} else {
				echo 'CRON: '.$cron->id." is disabled.\n";

				// Set the next_run timestamp to null to indicate in the view/database that this cron is disabled
				$this->cron_model->set_next_run($cron->id,null);

			}
		}
	}
}
