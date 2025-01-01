<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dayswithqso extends CI_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }
	}

	public function index() {
		$this->load->model('dayswithqso_model');
		// Render Page
		$data['page_title'] = __("Days with QSOs");

		$data['result'] = $this->dayswithqso_model->getDaysWithQso();
		$data['streaks'] = $this->dayswithqso_model->getLongestStreak();
		$data['currentstreak'] = $this->dayswithqso_model->getCurrentStreak();
		$data['almostcurrentstreak'] = $this->dayswithqso_model->getAlmostCurrentStreak();
		$data['daysofweek'] = $this->dayswithqso_model->getDaysOfWeek();
		$data['years'] = $this->get_years();

		$footerData = [];
		$footerData['scripts'] = ['assets/js/jquery.glanceyear.js'];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('dayswithqso/index');
		$this->load->view('interface_assets/footer',$footerData);
	}

	function get_years() {
		$this->load->model('logbook_model');
		$totals_year = $this->logbook_model->totals_year();
		$years=[];
		if ($totals_year) {
			foreach($totals_year->result() as $years_obj) {
				$years[] = $years_obj->year;
			}
		}
		return $years;
	}

	public function get_punchvals($yr = null) {
		$punchvals=[];
		if (($yr ?? '') != '') {
			$this->load->model('dayswithqso_model');
			$res_punchvals = $this->dayswithqso_model->getPunchvals($this->security->xss_clean($yr));
			if ($res_punchvals) {
				foreach($res_punchvals as $pobj) {
					$col=0;
					switch (true) {
					case ($pobj->qsos == 0):
						$col=0;
						break;
					case ($pobj->qsos <= 3):
						$col=3;
						break;
					case ($pobj->qsos <= 6):
						$col=6;
						break;
					case ($pobj->qsos <= 12):
						$col=12;
						break;
					case ($pobj->qsos <= 24):
						$col=24;
						break;
					case ($pobj->qsos > 24):
						$col=48;
						break;
					}
					$punchvals[] = ['date' => $pobj->date, 'value' => $pobj->qsos, 'col' => $col];
				}
			}
		} else {
			$punchvals=[];
		}
		header('Content-Type: application/json');
		echo json_encode($punchvals);
	}


	public function get_days(){

		//load model
		$this->load->model('dayswithqso_model');

		// get data
		$data = $this->dayswithqso_model->getDaysWithQso();
		header('Content-Type: application/json');
		echo json_encode($data);
	}

	public function get_weekdays() {
		//load model
		$this->load->model('dayswithqso_model');

		// get data
		$data = $this->dayswithqso_model->getDaysOfWeek();
		header('Content-Type: application/json');
		echo json_encode($data);
	}

	public function get_months() {
		//load model
		$this->load->model('dayswithqso_model');

		// get data
		$data = $this->dayswithqso_model->getMonthsOfYear();
		header('Content-Type: application/json');
		echo json_encode($data);
	}

}
