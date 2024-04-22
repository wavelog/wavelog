<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once './src/Cron/vendor/autoload.php';

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

		$this->load->model('cron_model');

		$crons = $this->cron_model->get_crons();

		foreach ($crons as $cron) {
			if ($cron->enabled == 1) {

				// calculate the crons expression
				$cronjob = new Cron\CronExpression($cron->expression);

				
				$isdue = $cronjob->isDue();
				if ($isdue == true) {
					$isdue_result = 'true';
					echo "CRON: ".$cron->id." -> is due: ".$isdue_result."\n";
					echo "CRON: ".$cron->id." -> RUNNING...\n";
					flush();

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
					} else {
						echo "ERROR: Something went wrong with ".$cron->id."\n";
					}
					flush();
				} else {
					$isdue_result = 'false';
					echo "CRON: ".$cron->id." -> is due: ".$isdue_result."\n";
					flush();
				}
				


				$next_run = $cronjob->getNextRunDate(date('Y-m-d H:i:s'))->format('Y-m-d H:i:s');
				echo "CRON: ".$cron->id." -> Next Run: ".$next_run."\n";
				flush();
				$this->cron_model->set_next_run($cron->id,$next_run);




				

			} else {
				echo 'CRON: '.$cron->id." is disabled.\n";
				flush();

				// set the next_run timestamp to null to indicate that this cron is disabled
				$this->cron_model->set_next_run($cron->id,null);

			}
		}
    }
}
