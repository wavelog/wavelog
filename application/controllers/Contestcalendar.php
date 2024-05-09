<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Contestcalendar extends CI_Controller {

	public function index()	{
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }

		$data['page_title'] = "Contest Calendar";

		$rssRawData = $this->getRssData();
		$data['rss'] = $this->parseRSS($rssRawData);

		$footerData['scripts'] = [
			'assets/js/sections/dxcalendar.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/dxcalendar.js"))
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('contestcalendar/index');
		$this->load->view('interface_assets/footer', $footerData);

	}

	private function parseRSS($rssRawData) {
		$rssData = array();
	
		$raw = simplexml_load_string($rssRawData, null, LIBXML_NOCDATA);
	
		foreach ($raw->channel->item as $item) {

			// create an array to hold the contest information
			$contest = array();
	
			// write the contest_title to the array
			$contest['title'] = (string) $item->title;
	
			// write the start time to the array. the whole time range is in the 'description' tag of the rssRawData
			$description = (string) $item->description;
			$timeRange = $this->parseTimeRange($description);
			$contest['start'] = $timeRange['start']->format('Y-m-d H:i:s');
			$contest['end'] = $timeRange['end']->format('Y-m-d H:i:s');
	
			// and write the link to the array
			$contest['link'] = (string) $item->link;
	
			// append the contest array to the $rssData array
			$rssData[] = $contest;
		}
	
		return $rssData;
	}
	

	private function parseTimeRange($string) {
		$timeData = array();
	
		// if the timeRange is over midnight the string contains 'to'
		if (strpos($string, 'to')) {
	
			// split in start and end time
			$parts = explode('to', $string);
			$start = trim($parts[0]);
			$end = trim($parts[1]);
	
			// create proper dateTime 
			$timeData['start'] = DateTime::createFromFormat('Hi\Z, M d', $start);
			$timeData['end'] = DateTime::createFromFormat('Hi\Z, M d', $end);
		} else {
	
			// split in start and end time
			$parts = explode('-', $string);
			$start = trim($parts[0]);
			$end = trim($parts[1]);
	
			// extract the date. we need to add this to the start time
			$date = substr($parts[1], strpos($parts[1], ',') + 2);
	
			// create proper dateTime 
			$timeData['start'] = DateTime::createFromFormat('Hi\Z, M d', $start . ', ' . $date);
			$timeData['end'] = DateTime::createFromFormat('Hi\Z, M d', $end);
		}
	
		return $timeData;
	}

	private function getRssData() {

		$this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
	
		if (!$rssRawData = $this->cache->get('RssRawContestCal')) {
		
			$rssUrl = 'https://www.contestcalendar.com/calendar.rss';
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $rssUrl);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$rssRawData = curl_exec($ch);
			curl_close($ch);

			if ($rssRawData === FALSE) {
				$msg = "Something went wrong with fetching the Contest Data";
				log_message('error', $msg);
				return;
			}
	
			$this->cache->save('RssRawContestCal', $rssRawData, (60*60*12)); // 12 hours cache time
	
			curl_close($ch);
		}
	
		return $rssRawData;
	}
		
}
