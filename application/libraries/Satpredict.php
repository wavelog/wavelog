<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Satpredict {

	protected $CI;

	public function __construct() {
		$this->CI = &get_instance();
		require_once "./src/predict/Predict.php";
		require_once "./src/predict/Predict/Sat.php";
		require_once "./src/predict/Predict/QTH.php";
		require_once "./src/predict/Predict/Time.php";
		require_once "./src/predict/Predict/TLE.php";
	}

	public function build_tle($sat_tle) {
		$raw = isset($sat_tle->tle) ? trim($sat_tle->tle) : '';

		$name = (isset($sat_tle->satellite) && $sat_tle->satellite)
			? $sat_tle->satellite
			: (isset($sat_tle->displayname) ? $sat_tle->displayname : '');

		if (Predict_TLE::isOmmJson($raw)) {
			return Predict_TLE::fromOmmJson($raw, $name);
		}

		$temp = preg_split('/\n/', $sat_tle->tle);
		return new Predict_TLE($name, $temp[0], $temp[1]);
	}

	public static function daynum_from_date($date) {
		require_once "./src/predict/Predict/Time.php";
		$timestamp = strtotime($date);
		if ($timestamp === false) {
			throw new Exception("Invalid date format. Expected Y-m-d.");
		}
		return Predict_Time::unix2daynum($timestamp, 0);
	}

	protected function qth_from_grid($yourgrid) {
		$strQRA = $yourgrid;

		if ((strlen($strQRA) % 2 == 0) && (strlen($strQRA) <= 10)) {
			$strQRA = strtoupper($strQRA);
			if (strlen($strQRA) == 4)  $strQRA .= "LL";
			if (strlen($strQRA) == 6)  $strQRA .= "55";
			if (strlen($strQRA) == 8)  $strQRA .= "LL";

			if (!preg_match('/^[A-R]{2}[0-9]{2}[A-X]{2}[0-9]{2}[A-X]{2}$/', $strQRA)) {
				return false;
			}
		}

		if (!$this->CI->load->is_loaded('Qra')) {
			$this->CI->load->library('Qra');
		}
		$homecoordinates = $this->CI->qra->qra2latlong($yourgrid);
		if (!is_array($homecoordinates) || count($homecoordinates) < 2) {
			return false;
		}

		$qth = new Predict_QTH();
		$qth->alt = 100;
		$qth->lat = $homecoordinates[0];
		$qth->lon = $homecoordinates[1];
		return $qth;
	}

	protected function date_format_string() {
		if ($this->CI->session->userdata('user_date_format')) {
			$custom_date_format = $this->CI->session->userdata('user_date_format');
		} else {
			$custom_date_format = $this->CI->config->item('qso_date_format');
		}
		return $custom_date_format . ' H:i:s';
	}

	public function calcPasses($sat_tles, $yourgrid, $date, $mintime, $minelevation, $timezone = 'UTC') {
		$predict = new Predict();
		$qth = $this->qth_from_grid($yourgrid);
		if ($qth === false) {
			return false;
		}

		$filtered = [];
		foreach ($sat_tles as $sat_tle) {
			if ($sat_tle->tle == null) {
				continue;
			}
			try {
				$tle = $this->build_tle($sat_tle);
				$sat = new Predict_Sat($tle);

				$now = self::daynum_from_date($date) + ($mintime / 24);

				$predict->minEle     = intval($minelevation);
				$predict->timeRes    = 1;
				$predict->numEntries = 20;

				$results  = $predict->get_passes($sat, $qth, $now, 1);
				$all_of_sat = $predict->filterVisiblePasses($results);
				array_push($filtered, ...$all_of_sat);
			} catch (\Throwable $th) {
				log_message("Error", "Exception while calculating passes for SAT ".($sat_tle->satellite ?? ''));
			}
		}
		$sortKey = array_column($filtered, 'aos');
		array_multisort($sortKey, SORT_ASC, $filtered);

		$data['format'] = $this->date_format_string();
		$data['filtered'] = $filtered;
		$data['zone'] = $timezone;
		return $data;
	}

	public function calcPass($sat_tle, $yourgrid, $date, $mintime, $minelevation, $timezone = 'UTC') {
		$predict = new Predict();
		$qth = $this->qth_from_grid($yourgrid);
		if ($qth === false) {
			return false;
		}

		if ($sat_tle->tle == null) {
			return ['format' => $this->date_format_string(), 'filtered' => [], 'zone' => $timezone];
		}

		try {
			$tle = $this->build_tle($sat_tle);
			$sat = new Predict_Sat($tle);

			$now = self::daynum_from_date($date) + ($mintime / 24);

			$predict->minEle     = intval($minelevation);
			$predict->timeRes    = 1;
			$predict->numEntries = 20;

			$results  = $predict->get_passes($sat, $qth, $now, 1);
			$filtered = $predict->filterVisiblePasses($results);
		} catch (\Throwable $th) {
			log_message("Error", "Exception while calculating pass for SAT ".($sat_tle->satellite ?? ''));
			$filtered = [];
		}

		$data['format'] = $this->date_format_string();
		$data['filtered'] = $filtered;
		$data['zone'] = $timezone;
		return $data;
	}

	public function next_pass($sat_tle, $yourgrid, $minelevation = 0, $horizon_days = 1, $timeRes = 60, $numEntries = 8) {
		$qth = $this->qth_from_grid($yourgrid);
		if ($qth === false) {
			return null;
		}
		if (!isset($sat_tle->tle) || $sat_tle->tle == null || trim($sat_tle->tle) === '') {
			return null;
		}
		try {
			$tle = $this->build_tle($sat_tle);
			$sat = new Predict_Sat($tle);

			$predict = new Predict();
			$predict->minEle     = intval($minelevation);
			$predict->timeRes    = $timeRes;
			$predict->numEntries = $numEntries;

			$now = Predict_Time::unix2daynum(time(), 0);
			$pass = $predict->get_pass($sat, $qth, $now, $horizon_days);
			return ($pass && $pass->aos > 0.0) ? $pass : null;
		} catch (\Throwable $th) {
			log_message("Error", "Exception while calculating next pass for SAT ".($sat_tle->satellite ?? ''));
			return null;
		}
	}
}
