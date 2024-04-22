<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

require_once './src/Cron/vendor/autoload.php';

class cron extends CI_Controller
{
	function __construct()
	{
		parent::__construct();

		if (ENVIRONMENT == 'maintenance' && $this->session->userdata('user_id') == '') {
            echo "Maintenance Mode is active. Try again later.\n";
			redirect('user/login');
		}
	}

	public function index() {

        $this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }

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
        echo "works\n";
    }
}
