<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

use Wavelog\Dxcc\Dxcc;

require_once APPPATH . '../src/Dxcc/Dxcc.php';

class Calendar_model extends CI_Model {

	private $today;

	function __construct() {
		$this->today = date('Y-m-d');

		$this->load->driver('cache', [
			'adapter' => $this->config->item('cache_adapter') ?? 'file',
			'backup' => $this->config->item('cache_backup') ?? 'file',
			'key_prefix' => $this->config->item('cache_key_prefix') ?? ''
		]);
	}

	// ================================================================
	// Contest Calendar Methods
	// ================================================================

	/**
	 * Fetch contest RSS data (cached for 12 hours).
	 * Returns raw XML string or false on error.
	 */
	public function get_contest_rss_data() {
		if (!$rssRawData = $this->cache->get('RssRawContestCal')) {
			$rssUrl = 'https://www.contestcalendar.com/calendar.rss';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $rssUrl);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			$rssRawData = curl_exec($ch);

			if ($rssRawData === FALSE) {
				log_message('error', 'Calendar_model: Failed to fetch contest RSS data');
				return false;
			}

			$this->cache->save('RssRawContestCal', $rssRawData, (60 * 60 * 12));
		}

		return $rssRawData;
	}

	/**
	 * Parse contest RSS XML into structured array.
	 * Returns array of ['title', 'start' (DateTime), 'end' (DateTime), 'link'].
	 */
	public function parse_contest_rss($rssRawData) {
		$rssData = array();

		$raw = simplexml_load_string($rssRawData, null, LIBXML_NOCDATA);
		if ($raw === false) {
			return false;
		}

		foreach ($raw->channel->item as $item) {
			$contest = array();
			$contest['title'] = (string) $item->title;

			$description = (string) $item->description;
			$timeRange = $this->parse_contest_time_range($description);
			$contest['start'] = $timeRange['start'];
			$contest['end'] = $timeRange['end'];
			$contest['link'] = (string) $item->link;

			$rssData[] = $contest;
		}

		return $rssData;
	}

	/**
	 * Parse contest time range string from RSS description.
	 * Handles formats like "1400Z- 1800Z, Dec 31" and "2200Z to 0200Z Jan 1".
	 */
	public function parse_contest_time_range($string) {
		$timeData = array();

		if (strpos($string, 'to')) {
			$parts = explode('to', $string);
			$start = trim($parts[0]);
			$end = trim($parts[1]);

			$timeData['start'] = DateTime::createFromFormat('Hi\Z, M d', $start);
			if (!$timeData['start']) {
				$timeData['start'] = DateTime::createFromFormat('Hi\Z M d', $start);
			}

			$timeData['end'] = DateTime::createFromFormat('Hi\Z, M d', $end);
			if (!$timeData['end']) {
				$timeData['end'] = DateTime::createFromFormat('Hi\Z M d', $end);
			}
		} else {
			$parts = explode('-', $string);
			$start = trim($parts[0]);
			$end = trim($parts[1]);

			$date = substr($parts[1], strpos($parts[1], ',') + 2);

			$timeData['start'] = DateTime::createFromFormat('Hi\Z, M d', $start . ', ' . $date);
			$timeData['end'] = DateTime::createFromFormat('Hi\Z, M d', $end);
		}

		return $timeData;
	}

	/**
	 * Get contests active on today's date.
	 */
	public function get_contests_today() {
		$rssRawData = $this->get_contest_rss_data();
		if ($rssRawData === false) {
			return false;
		}

		$parsed = $this->parse_contest_rss($rssRawData);
		if ($parsed === false) {
			return false;
		}

		$contestsToday = array();
		foreach ($parsed as $contest) {
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

	/**
	 * Get contests for the next weekend.
	 */
	public function get_contests_weekend() {
		$rssRawData = $this->get_contest_rss_data();
		if ($rssRawData === false) {
			return false;
		}

		$parsed = $this->parse_contest_rss($rssRawData);
		if ($parsed === false) {
			return false;
		}

		$contestsNextWeekend = array();
		$currentDayOfWeek = date('N', strtotime($this->today));

		if ($currentDayOfWeek >= 1 && $currentDayOfWeek <= 4) {
			$nextFriday = date('Y-m-d', strtotime('next friday', strtotime($this->today)));
			$nextSunday = date('Y-m-d', strtotime('next sunday', strtotime($this->today)));
		} else {
			$nextFriday = date('Y-m-d', strtotime('friday this week', strtotime($this->today)));
			$nextSunday = date('Y-m-d', strtotime('sunday this week', strtotime($this->today)));
		}

		foreach ($parsed as $contest) {
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

	/**
	 * Get contests for next week (from next Monday onward).
	 */
	public function get_contests_next_week() {
		$rssRawData = $this->get_contest_rss_data();
		if ($rssRawData === false) {
			return false;
		}

		$parsed = $this->parse_contest_rss($rssRawData);
		if ($parsed === false) {
			return false;
		}

		$contestsNextWeek = array();
		$nextMonday = date('Y-m-d', strtotime('next monday', strtotime($this->today)));

		foreach ($parsed as $contest) {
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

	// ================================================================
	// DXpedition Calendar Methods
	// ================================================================

	/**
	 * Fetch DXpedition XML data (cached for 12 hours).
	 * Returns raw XML string or false on error.
	 */
	public function get_dx_rss_data() {
		if (!$rssRawData = $this->cache->get('RssRawDxCal')) {
			$rssUrl = 'https://www.ng3k.com/adxo.xml';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $rssUrl);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Wavelog Updater');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			$rssRawData = curl_exec($ch);

			if ($rssRawData === FALSE) {
				log_message('error', 'Calendar_model: Failed to fetch DX calendar XML data');
				return false;
			}

			$this->cache->save('RssRawDxCal', $rssRawData, (60 * 60 * 12));
		}

		return $rssRawData;
	}

	/**
	 * Extract and parse date range from DXpedition XML.
	 * Returns array [formatted_start, formatted_end, startDateTime, endDateTime] or false.
	 */
	public function extract_dxped_dates($dateRange, $custom_date_format) {
		$dateParts = explode(",", $dateRange);
		if (count($dateParts) < 2) {
			return false;
		}

		$monthDayPart = explode("-", trim($dateParts[0]));
		$yearPart = trim($dateParts[1]);

		if (count($dateParts) == 3) {
			$yearEndPart = trim($dateParts[2]);
			$EmonthDayPart = explode("-", trim($dateParts[1]));
			$monthDayPart[1] = $EmonthDayPart[1];
			$acrossyears = explode('-', trim($yearPart));
			$year = substr($acrossyears[0], -4);
		} else {
			$year = substr($yearPart, -4);
			$yearEndPart = $yearPart;
		}

		$yearE = substr($yearEndPart, -4);

		$startDate = $monthDayPart[0] . ", " . $year;

		if (strlen($monthDayPart[1]) < 3) {
			$tempdate = explode(" ", $monthDayPart[0]);
			$endDate = $tempdate[0] . " " . $monthDayPart[1] . ", " . $yearE;
		} else {
			$endDate = $monthDayPart[1] . ", " . $yearE;
		}

		$startDateTime = date_create_from_format("M j, Y", $startDate);
		$endDateTime = date_create_from_format("M j, Y", $endDate);

		if ($startDateTime !== false && $endDateTime !== false) {
			return array($startDateTime->format($custom_date_format), $endDateTime->format($custom_date_format), $startDateTime, $endDateTime);
		} else {
			log_message("error", "Calendar_model: Failed to parse dates: " . $startDate . '///' . $endDate);
			return false;
		}
	}

	/**
	 * Parse DXpedition XML and return all DXpeditions with worked/confirmed status.
	 * Used by the DX Calendar page.
	 */
	public function get_all_dxpeditions() {
		$this->load->model('logbook_model');

		$rssRawData = $this->get_dx_rss_data();
		if ($rssRawData === false) {
			return array();
		}

		$rssRawData = mb_convert_encoding($rssRawData, 'UTF-8', 'UTF-8');
		$rssdata = simplexml_load_string($rssRawData, null, LIBXML_NOCDATA);
		if ($rssdata === false) {
			return array();
		}

		// Get date format
		if ($this->session->userdata('user_date_format')) {
			$custom_date_format = $this->session->userdata('user_date_format');
		} else {
			$custom_date_format = $this->config->item('qso_date_format');
		}

		$dxccobj = new Dxcc();
		$dxpeds = array();

		foreach ($rssdata->channel->item as $item) {
			$dxped = (object)[];
			$title = explode('--', $item->title);
			$tempinfo = explode(':', $title[0]);
			$dxped->dxcc = $tempinfo[0];
			$date = $tempinfo[1] ?? '';

			$dxped->dates = $this->extract_dxped_dates($date, $custom_date_format);
			if ($dxped->dates === false) {
				continue;
			}

			$dxped->description = $item->description;
			$descsplit = explode("\n", $item->description);

			$call = (string) $descsplit[3];
			$dxped->call = trim(str_replace('--', '', $call));

			$chk_dxcc = $dxccobj->dxcc_lookup($dxped->call . "X", $dxped->dates[2]->format('Y-m-d'));
			if (($chk_dxcc['adif'] ?? '') != '') {
				$chk_dxcc_val = $chk_dxcc['adif'];
				$dxped->no_dxcc = false;
			} else {
				$chk_dxcc_val = -1;
				$dxped->no_dxcc = true;
			}

			$dxped->call_wked = $this->logbook_model->check_if_callsign_worked_in_logbook($dxped->call);
			$dxped->call_cnfmd = $this->logbook_model->check_if_callsign_cnfmd_in_logbook($dxped->call);
			$dxped->dxcc_wked = $this->logbook_model->check_if_dxcc_worked_in_logbook($chk_dxcc_val);
			$dxped->dxcc_cnfmd = $this->logbook_model->check_if_dxcc_cnfmd_in_logbook($chk_dxcc_val);
			$dxped->dxcc_adif = $chk_dxcc_val;

			$qslinfo = (string) $descsplit[4];
			$qslinfo = str_replace('--', '', $qslinfo);
			$dxped->qslinfo = str_replace('QSL: ', '', $qslinfo);

			$source = (string) $descsplit[5];
			$source = str_replace('--', '', $source);
			$dxped->source = str_replace('Source: ', '', $source);

			$dxped->info = (string) ($descsplit[6] ?? '');
			$dxped->link = (string) $item->link;

			$dxpeds[] = $dxped;
		}

		return $dxpeds;
	}

	/**
	 * Get DXpeditions active on today's date with worked/confirmed status.
	 * Used by the Dashboard card.
	 */
	public function get_active_dxpeditions() {
		$all = $this->get_all_dxpeditions();

		$active = array();
		foreach ($all as $dxped) {
			if ($dxped->dates === false) {
				continue;
			}
			$startDate = $dxped->dates[2]->format('Y-m-d');
			$endDate = $dxped->dates[3]->format('Y-m-d');
			if ($startDate <= $this->today && $endDate >= $this->today) {
				$active[] = $dxped;
			}
		}

		return $active;
	}
}
