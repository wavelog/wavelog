<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class cron extends CI_Controller {

	private $min_php_version;

	function __construct() {

		parent::__construct();

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
			echo __("Maintenance Mode is active. Try again later.")."\n";
			redirect('user/login');
		}

		$this->load->model('cron_model');

		// Minimum PHP Version for the Cron Manager
		$this->min_php_version = '8.1.0';
	}

	public function index() {

		$this->load->model('user_model');
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$this->load->helper('file');

		$footerData = [];
		$footerData['scripts'] = [
			'assets/js/cronstrue.min.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/cronstrue.min.js")),
			'assets/js/sections/cron.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/cron.js"))
		];

		$data['page_title'] = __("Cron Manager");
		$data['crons'] = $this->cron_model->get_crons();

		$mastercron = array();
		$mastercron = $this->get_mastercron_status();
		$data['mastercron'] = $mastercron;
		$data['min_php_version'] = $this->min_php_version;

		$this->load->view('interface_assets/header', $data);
		$this->load->view('cron/index');
		$this->load->view('interface_assets/footer', $footerData);
	}

	public function run() {

		// This is the main function, which handles all crons, runs them if enabled and writes the 'next run' timestamp to the database

		// check for min. PHP version
		if (version_compare(PHP_VERSION, $this->min_php_version) >= 0) {

			// TODO Add an API Key to the cronjob to improve security?

			$crons = $this->cron_model->get_crons();

			$status = 'pending';

			foreach ($crons as $cron) {
				if ($cron->enabled == 1) {

					// calculate the crons expression
					$data = array(
						'expression' => $cron->expression,
						'timeZone' => null
					);
					$this->load->library('CronExpression', $data);

					$cronjob = $this->cronexpression;
					$dt = new DateTime();
					$isdue = $cronjob->isMatching($dt);

					$next_run = $cronjob->getNext();
					$next_run_date = date('Y-m-d H:i:s', $next_run);
					$this->cron_model->set_next_run($cron->id, $next_run_date);

					if ($isdue == true) {
						$isdue_result = 'true';

						// TODO Add log_message level debug here to have logging for the cron manager

						echo "CRON: " . $cron->id . " -> is due: " . $isdue_result . "\n";
						echo "CRON: " . $cron->id . " -> RUNNING...\n";

						$url = base_url() . $cron->function;

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $url);
						curl_setopt($ch, CURLOPT_HEADER, false);
						curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						$crun = curl_exec($ch);
						curl_close($ch);

						if ($crun !== false) {
							echo "CRON: " . $cron->id . " -> CURL Result: " . $crun . "\n";
							$status = 'healthy';
						} else {
							echo "ERROR: Something went wrong with " . $cron->id . "\n";
							$status = 'failed';
						}
					} else {
						$isdue_result = 'false';
						echo "CRON: " . $cron->id . " -> is due: " . $isdue_result . " -> Next Run: " . $next_run_date . "\n";
						$status = 'healthy';
					}
				} else {
					echo 'CRON: ' . $cron->id . " is disabled. skipped..\n";
					$status = 'disabled';

					// Set the next_run timestamp to null to indicate in the view/database that this cron is disabled
					$this->cron_model->set_next_run($cron->id, null);
				}
				$this->cron_model->set_status($cron->id, $status);
				$this->cronexpression = null;
			}

			$datetime = new DateTime("now", new DateTimeZone('UTC'));
			$datetime = $datetime->format('Ymd H:i:s');
			$this->optionslib->update('mastercron_last_run', $datetime , 'no');
		} else {
			log_message('error', 'CRON: PHP Version '. PHP_VERSION . ' not supported. Minimum Version is: ' . $this->min_php_version);
			echo 'CRON: PHP Version '. PHP_VERSION . ' not supported. Minimum Version is: ' . $this->min_php_version . "\n";
		}
	}

	public function editDialog() {

		$cron_query = $this->cron_model->cron(xss_clean($this->input->post('id', true)));

		$data['cron'] = $cron_query->row();
		$data['page_title'] = __("Edit Cronjob");

		$this->load->view('cron/edit', $data);
	}

	public function edit() {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(99)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$id = xss_clean($this->input->post('cron_id', true));
		$description = xss_clean($this->input->post('cron_description', true));
		$expression = xss_clean($this->input->post('cron_expression', true));
		$enabled = xss_clean($this->input->post('cron_enabled', true));

		$data = array(
			'expression' => $expression,
			'timeZone' => null
		);
		$this->load->library('CronExpression', $data);
		$cron = $this->cronexpression;

		if ($cron->isValid()) {
			$this->cron_model->edit_cron($id, $description, $expression, $enabled);
			$this->cronexpression = null;

			header("Content-type: application/json");
			echo json_encode(['success' => true, 'messagecategory' => 'success', 'message' => 'Changes saved for Cronjob "' . $id . '"']);
		} else {
			$this->session->set_flashdata('error', 'The Cron Expression you entered is not valid');
			$this->cronexpression = null;

			header("Content-type: application/json");
			echo json_encode(['success' => false, 'messagecategory' => 'error', 'message' => 'The expression "' . $expression . '" is not valid. Please try again.']);
		}
	}

	public function toogleEnableCronSwitch() {

		$id = xss_clean($this->input->post('id', true));
		$cron_enabled = xss_clean($this->input->post('checked', true));

		if ($id ?? '' != '') {
			$this->cron_model->set_cron_enabled($id, $cron_enabled);
			$data['success'] = 1;
		} else {
			$data['success'] = 0;
			$data['flashdata'] = 'Not allowed';
		}
		echo json_encode($data);
	}

	public function fetchCrons() {
		$hres = [];
		$result = $this->cron_model->get_crons();

		foreach ($result as $cron) {
			$single = (object) [];
			$single->cron_id = $cron->id;
			$single->cron_description = $cron->description;
			$single->cron_status = $this->cronStatus2html($cron->enabled, $cron->status);
			$single->cron_expression = $this->cronExpression2html($cron->expression);
			$single->cron_last_run = $cron->last_run ?? 'never';
			$single->cron_next_run = ($cron->enabled == '1') ? ($cron->next_run ?? 'calculating..') : 'never';
			$single->cron_edit = $this->cronEdit2html($cron->id);
			$single->cron_enabled = $this->cronEnabled2html($cron->id, $cron->enabled);
			array_push($hres, $single);
		}
		echo json_encode($hres);
	}

	private function cronStatus2html($enabled, $status) {
		if ($enabled == '1') {
			if ($status == 'healthy') {
				$htmlret = '<span class="badge text-bg-success">healthy</span>';
			} else {
				$htmlret = '<span class="badge text-bg-warning">' . $status . '</span>';
			}
		} else {
			$htmlret = '<span class="badge text-bg-secondary">disabled</span>';
		}
		return $htmlret;
	}

	private function cronExpression2html($expression) {
		$htmlret = '<code id="humanreadable_tooltip" data-bs-toggle="tooltip">' . $expression . '</code>';
		return $htmlret;
	}

	private function cronEdit2html($id) {
		$htmlret = '<button id="' . $id . '" class="editCron btn btn-outline-primary btn-sm"><i class="fas fa-edit"></i></button>';
		return $htmlret;
	}

	private function cronEnabled2html($id, $enabled) {
		if ($enabled == '1') {
			$checked = 'checked';
		} else {
			$checked = '';
		}
		$htmlret = '<div class="form-check form-switch"><input name="cron_enable_switch" class="form-check-input enableCronSwitch" type="checkbox" role="switch" id="' . $id . '" ' . $checked . '></div>';
		return $htmlret;
	}

	private function get_mastercron_status() {
		$warning_timelimit_seconds = 120; 	// yellow - warning please check
		$error_timelimit_seconds = 600; 	// red - "not running"
	
		$result = array();
	
		$last_run = $this->optionslib->get_option('mastercron_last_run') ?? null;
	
		if ($last_run != null) {
			$timestamp_last_run = DateTime::createFromFormat('Ymd H:i:s', $last_run, new DateTimeZone('UTC'));
			$now = new DateTime(); 
			$diff = $now->getTimestamp() - $timestamp_last_run->getTimestamp(); 

			if ($diff >= 0 && $diff <= $warning_timelimit_seconds) {
				$result['status'] = 'OK';
				$result['status_class'] = 'success';
			} else {
				if ($diff <= $error_timelimit_seconds) {
					$result['status'] = 'Last run occurred more than ' . $warning_timelimit_seconds . ' seconds ago.<br>Please check your master cron! It should run every minute (* * * * *).';
					$result['status_class'] = 'warning';
				} else {
					$result['status'] = 'Last run occurred more than ' . ($error_timelimit_seconds / 60) . ' minutes ago.<br>Seems like your Mastercron isn\'t running!<br>It should run every minute (* * * * *).';
					$result['status_class'] = 'danger';
				}
			}
		} else {
			$result['status'] = 'Not running';
			$result['status_class'] = 'danger';
		}
	
		return $result;
	}
		
}
