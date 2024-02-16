<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dxcalendar extends CI_Controller {

	public function index()	{
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }

		$data['page_title'] = "DX Calendar";

		$url = 'http://www.ng3k.com/adxo.xml';
		$data['rss'] = simplexml_load_file($url, null, LIBXML_NOCDATA);

		$footerData['scripts'] = [
			'assets/js/sections/dxcalendar.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/dxcalendar.js"))
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('dxcalendar/index');
		$this->load->view('interface_assets/footer', $footerData);

	}


}
