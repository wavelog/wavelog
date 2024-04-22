<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Cron extends CI_Controller
{
	function __construct() {
		parent::__construct();

		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('notice', 'You\'re not allowed to do that!');
			redirect('dashboard');
		}

		$this->load->library('Permissions');
	}

	public function index() {
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
}
