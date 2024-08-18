<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Contestcalendar extends CI_Controller {

	private $today;

	function __construct() {
		parent::__construct();
		$this->today = date('Y-m-d');
	}

	public function index() {
		$this->load->model('user_model');
		if (!$this->user_model->authorize(2)) {
			$this->session->set_flashdata('error', __("You're not allowed to do that!"));
			redirect('dashboard');
		}

		$data['page_title'] = __("Contest Calendar");

		// get the raw data and parse it
		$rssRawData = $this->getRssData();
		$parsed = $this->parseRSS($rssRawData);

		// and give it to the view
		$data['contestsToday'] = $this->contestsToday($parsed);
		$data['contestsNextWeekend'] = $this->contestsNextWeekend($parsed);
		$data['contestsNextWeek'] = $this->contestsNextWeek($parsed);

		// Get Date format
		if ($this->session->userdata('user_date_format')) {
			$data['custom_date_format'] = $this->session->userdata('user_date_format');
		} else {
			$data['custom_date_format'] = $this->config->item('qso_date_format');
		}

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
			$contest['start'] = $timeRange['start'];
			$contest['end'] = $timeRange['end'];

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

			$this->cache->save('RssRawContestCal', $rssRawData, (60 * 60 * 12)); // 12 hours cache time

			curl_close($ch);
		}

		return $rssRawData;
	}

	private function contestsToday($rss) {
		$contestsToday = array();

		foreach ($rss as $contest) {
			if (!($contest['start'] instanceof DateTime) && !($contest['end'] instanceof DateTime)) {
				log_message('debug', "Invalid Time format for contest: " . $contest['title']);
				continue;
			}

			$start = $contest['start'] == '' ? '' : date('Y-m-d', strtotime($contest['start']->format('Y-m-d')));
			$end = $contest['end'] == '' ? '' : date('Y-m-d', strtotime($contest['end']->format('Y-m-d')));

			if ($start <= $this->today && $end >= $this->today) {
				$contestsToday[] = $contest;
			}
		}

		return $contestsToday;
	}

	private function contestsNextWeekend($rss) {

		$contestsNextWeekend = array();

		$currentDayOfWeek = date('N', strtotime($this->today));

		if ($currentDayOfWeek >= 1 && $currentDayOfWeek <= 4) {
			$nextFriday = date('Y-m-d', strtotime('next friday', strtotime($this->today)));
			$nextSunday = date('Y-m-d', strtotime('next sunday', strtotime($this->today)));
		} else {
			$nextFriday = date('Y-m-d', strtotime('friday this week', strtotime($this->today)));
			$nextSunday = date('Y-m-d', strtotime('sunday this week', strtotime($this->today)));
		}

		foreach ($rss as $contest) {
			if (!($contest['start'] instanceof DateTime) && !($contest['end'] instanceof DateTime)) {
				log_message('debug', "Invalid Time format for contest: " . $contest['title']);
				continue;
			}

			$start = $contest['start'] == '' ? '' : date('Y-m-d', strtotime($contest['start']->format('Y-m-d')));
			$end = $contest['end'] == '' ? '' : date('Y-m-d', strtotime($contest['end']->format('Y-m-d')));

			if ($start >= $nextFriday && $start <= $nextSunday && $start >= $this->today) {
				$contestsNextWeekend[] = $contest;
			}
			if ($start <= $nextSunday && $end >= $nextFriday) {
				$contestExists = false;
				foreach ($contestsNextWeekend as $existingContest) {
					if ($existingContest['title'] === $contest['title']) {
						$contestExists = true;
						break;
					}
				}
				if (!$contestExists) {
					$contestsNextWeekend[] = $contest;
				}
			}

		}

		return $contestsNextWeekend;
	}

	private function contestsNextWeek($rss) {
		$contestsNextWeek = array();

		$nextMonday = date('Y-m-d', strtotime('next monday', strtotime($this->today)));

		foreach ($rss as $contest) {
			if (!($contest['start'] instanceof DateTime)) {
				log_message('debug', "Invalid Time format for contest: " . $contest['title']);
				continue;
			}
			$start = date('Y-m-d', strtotime($contest['start']->format('Y-m-d')));

			if ($start >= $nextMonday) {
				$contestsNextWeek[] = $contest;
			}
		}

		return $contestsNextWeek;
	}
}
