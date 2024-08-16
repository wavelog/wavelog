<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Dxcalendar extends CI_Controller {

	public function index()	{
		$this->load->model('user_model');
		$this->load->model('logbook_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('error', __("You're not allowed to do that!")); redirect('dashboard'); }

		$data['page_title'] = __("DX Calendar");

		$this->load->driver('cache', array('adapter' => 'file', 'backup' => 'file'));
		$rssUrl = 'http://www.ng3k.com/adxo.xml';
		if (!$rssRawData = $this->cache->get('RssRawDxCal')) {
			$rssRawData = file_get_contents($rssUrl, true);
			$this->cache->save('RssRawDxCal', $rssRawData, (60*60*12));
		}
		$rssdata = simplexml_load_string($rssRawData, null, LIBXML_NOCDATA);

		// Get Date format
		if($this->session->userdata('user_date_format')) {
			// If Logged in and session exists
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			// Get Default date format from config
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
			$dxped->call = trim(str_replace('--', '', $call));
			$chk_dxcc=$this->logbook_model->dxcc_lookup($dxped->call."X",$dxped->dates[2]->format('Y-m-d')); // X because sometimes only the pref is in XML
			if ($chk_dxcc['adif'] ?? '' != '') {
				$chk_dxcc_val=$chk_dxcc['adif'];
				$dxped->no_dxcc=false;
			} else {
				$chk_dxcc_val=-1;
				$dxped->no_dxcc=true;
			}
			$dxped->call_wked =$this->logbook_model->check_if_callsign_worked_in_logbook($dxped->call);
			$dxped->call_cnfmd =$this->logbook_model->check_if_callsign_cnfmd_in_logbook($dxped->call);
			$dxped->dxcc_wked =$this->logbook_model->check_if_dxcc_worked_in_logbook($chk_dxcc_val);
			$dxped->dxcc_cnfmd =$this->logbook_model->check_if_dxcc_cnfmd_in_logbook($chk_dxcc_val);
			$dxped->dxcc_adif = $chk_dxcc_val;
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
		if (count($dateParts) < 2) {
			return false; // Invalid date range format
		}

		$monthDayPart = explode("-", trim($dateParts[0]));
		$yearPart = trim($dateParts[1]);

		if (count($dateParts) == 3) {
			$yearEndPart = trim($dateParts[2]);
			$EmonthDayPart = explode("-", trim($dateParts[1]));
			$monthDayPart[1]=$EmonthDayPart[1];
			$acrossyears=explode('-',trim($yearPart));
			$year = substr($acrossyears[0], -4);
		} else {
			$year = substr($yearPart, -4);
			$yearEndPart = $yearPart;
		}
		// Extract the year from the year part
		$yearE = substr($yearEndPart, -4);

		$startDate = $monthDayPart[0] . ", " . $year;

		if (strlen($monthDayPart[1]) < 3) {
			$tempdate = explode(" ", $monthDayPart[0]);
			$endDate = $tempdate[0] . " " . $monthDayPart[1] . ", " . $yearE;
		} else {
			$endDate = $monthDayPart[1] . ", " . $yearE;
		}

		// Parse the start date
		$startDateTime = date_create_from_format("M j, Y", $startDate);

		// Parse the end date
		$endDateTime = date_create_from_format("M j, Y", $endDate);

		// Check if parsing was successful
		if ($startDateTime !== false && $endDateTime !== false) {
			return array($startDateTime->format($custom_date_format), $endDateTime->format($custom_date_format), $startDateTime, $endDateTime);
		} else {
			log_message("Error",$startDate.'///'.$endDate);
			return false; // Failed to parse dates
		}
	}

}
