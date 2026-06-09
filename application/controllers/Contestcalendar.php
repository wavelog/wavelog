<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Contestcalendar extends CI_Controller {

	public function index() {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$data['page_title'] = __("Contest Calendar");

		$this->load->model('Calendar_model');

		$data['contestsToday'] = $this->Calendar_model->get_contests_today();
		$data['contestsNextWeekend'] = $this->Calendar_model->get_contests_weekend();
		$data['contestsNextWeek'] = $this->Calendar_model->get_contests_next_week();

		if ($data['contestsToday'] !== false) {
			// Get Date format
			if ($this->session->userdata('user_date_format')) {
				$data['custom_date_format'] = $this->session->userdata('user_date_format');
			} else {
				$data['custom_date_format'] = $this->config->item('qso_date_format');
			}

			$footerData['scripts'] = [
				'assets/js/sections/dxcalendar.js'
			];
		} else {
			$data['contestsToday']='';
			$data['contestsNextWeekend']='';
			$data['contestsNextWeek']='';
			$data['custom_date_format'] = '';
			$footerData['scripts']=[];
			$this->session->set_flashdata('error', __("Contestcalendar not reachable. Try again later"));
		}
		$this->load->view('interface_assets/header', $data);
		$this->load->view('contestcalendar/index');
		$this->load->view('interface_assets/footer', $footerData);
	}
}
