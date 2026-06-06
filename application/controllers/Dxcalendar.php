<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dxcalendar extends CI_Controller {

	public function index()	{
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['page_title'] = __("DX Calendar");

		$this->load->model('Calendar_model');
		$data['rss'] = $this->Calendar_model->get_all_dxpeditions();

		$footerData['scripts'] = [
			'assets/js/sections/dxcalendar.js'
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('dxcalendar/index');
		$this->load->view('interface_assets/footer', $footerData);
	}
}
