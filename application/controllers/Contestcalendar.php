<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Contestcalendar extends CI_Controller {

	public function index()	{
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }

		$data['page_title'] = "Contest Calendar";

		$this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
		$rssUrl = 'https://www.contestcalendar.com/calendar.rss';
		if (!$rssRawData = $this->cache->get('RssRawContestCal')) {
			$rssRawData = file_get_contents($rssUrl, true);
			$this->cache->save('RssRawContestCal', $rssRawData, (60*12));
		}
		$data['rss'] = simplexml_load_string($rssRawData, null, LIBXML_NOCDATA);

		$footerData['scripts'] = [
			'assets/js/sections/dxcalendar.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/dxcalendar.js"))
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('contestcalendar/index');
		$this->load->view('interface_assets/footer', $footerData);

	}


}
