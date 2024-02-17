<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dxcalendar extends CI_Controller {

	public function index()	{
		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }

		$data['page_title'] = "DX Calendar";

		$url = 'http://www.ng3k.com/adxo.xml';
		$rssdata = simplexml_load_file($url, null, LIBXML_NOCDATA);

		// Get Date format
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from /config/cloudlog.php
			$custom_date_format = $this->config->item('qso_date_format');
		}

		foreach ($rssdata->channel->item as $item) {
			$dxped=(object)[];
			$title = explode('--', $item->title);
			$tempinfo = explode(':', $title[0]);
			$dxped->dxcc = $tempinfo[0];
			$date = $tempinfo[1] ?? '';

			$dxped->dates = $this->extractDates($date, $custom_date_format);

			$dxped->description = $item->description;

			$descsplit = explode("\n", $item->description);

			$call = (string) $descsplit[3];
			$dxped->call = str_replace('--', '', $call);
			$qslinfo = (string) $descsplit[4];
			$qslinfo = str_replace('--', '', $qslinfo);
			$dxped->qslinfo = str_replace('QSL: ', '', $qslinfo);
			$source = (string) $descsplit[5];
			$source = str_replace('--', '', $source);
			$dxped->source = str_replace('Source: ', '', $source);
			$dxped->info = (string) $descsplit[6];
			$dxped->link = (string) $item->link;
			$dxpeds[]=$dxped;
		}
		$data['rss']=$dxpeds;




		$footerData['scripts'] = [
			'assets/js/sections/dxcalendar.js?' . filemtime(realpath(__DIR__ . "/../../assets/js/sections/dxcalendar.js"))
		];

		$this->load->view('interface_assets/header', $data);
		$this->load->view('dxcalendar/index');
		$this->load->view('interface_assets/footer', $footerData);

	}

	function extractDates($dateRange, $custom_date_format) {
		// Split the date range into two parts: month-day and year
		$dateParts = explode(",", $dateRange);
		if (count($dateParts) != 2) {
			return false; // Invalid date range format
		}

		$monthDayPart = explode("-", trim($dateParts[0]));
		$yearPart = trim($dateParts[1]);

		// Extract the year from the year part
		$year = substr($yearPart, -4);

		$startDate = $monthDayPart[0] . ", " . $year;

		if (strlen($monthDayPart[1]) < 3) {
			$tempdate = explode(" ", $monthDayPart[0]);
			$endDate = $tempdate[0] . " " . $monthDayPart[1] . ", " . $year;
		} else {
			$endDate = $monthDayPart[1] . ", " . $year;
		}

		// Parse the start date
		$startDateTime = date_create_from_format("M j, Y", $startDate);

		// Parse the end date
		$endDateTime = date_create_from_format("M j, Y", $endDate);

		// Check if parsing was successful
		if ($startDateTime !== false && $endDateTime !== false) {
			return array($startDateTime->format($custom_date_format), $endDateTime->format($custom_date_format));
		} else {
			return false; // Failed to parse dates
		}
	}

}
