<?php

class Award_pl_polska extends CI_Model {

	// Award constants
	private $DXCC_POLAND = '269';
	private $AWARD_START_DATE = '1999-01-01';
	private $VALID_BANDS = array('160M','80M','40M','30M','20M','17M','15M','12M','10M','6M','2M');
	private $MODE_CATEGORIES = array('MIXED', 'PHONE', 'CW', 'DIGI');

	// Voivodeship codes and names
	private $voivodeship_names = array(
		'D' => 'Dolnośląskie',
		'P' => 'Kujawsko-Pomorskie',
		'B' => 'Lubuskie',
		'L' => 'Lubelskie',
		'C' => 'Łódzkie',
		'M' => 'Małopolskie',
		'R' => 'Mazowieckie',
		'U' => 'Opolskie',
		'K' => 'Podkarpackie',
		'O' => 'Podlaskie',
		'F' => 'Pomorskie',
		'G' => 'Śląskie',
		'S' => 'Świętokrzyskie',
		'J' => 'Warmińsko-Mazurskie',
		'W' => 'Wielkopolskie',
		'Z' => 'Zachodniopomorskie'
	);

	function __construct() {
		$this->load->library('Genfunctions');
	}

	/**
	 * Get voivodeship codes
	 */
	function getVoivodeshipCodes() {
		return array_keys($this->voivodeship_names);
	}

	/**
	 * Get voivodeship name from code
	 */
	function getVoivodeshipName($code) {
		return isset($this->voivodeship_names[$code]) ? $this->voivodeship_names[$code] : $code;
	}

	/**
	 * Build base SQL query
	 */
	private function buildBaseQuery($location_list, $withCount = false) {
		$select = $withCount
			? "SELECT UPPER(COL_STATE) as COL_STATE, COUNT(*) as qso_count"
			: "SELECT DISTINCT UPPER(COL_STATE) as COL_STATE";

		$sql = $select . "
			FROM " . $this->config->item('table_name') . " thcv
			WHERE station_id IN (" . $location_list . ")
			AND COL_DXCC = '" . $this->DXCC_POLAND . "'
			AND COL_TIME_ON >= '" . $this->AWARD_START_DATE . "'
			AND (COL_PROP_MODE != 'SAT' OR COL_PROP_MODE IS NULL)
			AND COL_BAND IN ('" . implode("','", $this->VALID_BANDS) . "')
			AND COL_STATE IS NOT NULL AND COL_STATE != ''
			AND UPPER(COL_STATE) IN ('" . implode("','", $this->getVoivodeshipCodes()) . "')";

		return $sql;
	}

	/**
	 * Add band filter to query
	 */
	private function addBandFilter($sql, $band) {
		if ($band != 'All') {
			$sql .= " AND COL_BAND = '" . $band . "'";
		}
		return $sql;
	}

	/**
	 * Add mode category filter to query
	 */
	private function addModeCategoryFilter($sql, $mode_category) {
		if ($mode_category == 'PHONE') {
			$sql .= " AND (UPPER(COL_MODE) IN ('SSB','USB','LSB','AM','FM','SSTV') OR UPPER(COL_SUBMODE) IN ('SSB','USB','LSB','AM','FM','SSTV'))";
		} elseif ($mode_category == 'CW') {
			$sql .= " AND (UPPER(COL_MODE) = 'CW' OR UPPER(COL_SUBMODE) = 'CW')";
		} elseif ($mode_category == 'DIGI') {
			$digi_modes = "'RTTY','PSK','PSK31','PSK63','PSK125','PSKR','FSK','FSK441','FT4','FT8','JS8','JT4','JT6M','JT9','JT65','MFSK','OLIVIA','OPERA','PAX','PAX2','PKT','Q15','QRA64','ROS','T10','THOR','THRB','TOR','VARA','WSPR'";
			$sql .= " AND (UPPER(COL_MODE) IN (" . $digi_modes . ") OR UPPER(COL_SUBMODE) IN (" . $digi_modes . "))";
		}
		return $sql;
	}

	/**
	 * Finalize query with GROUP BY
	 */
	private function finalizeQuery($sql, $withCount = false) {
		if ($withCount) {
			$sql .= " GROUP BY UPPER(COL_STATE)";
		}
		return $sql;
	}

	/**
	 * Execute voivodeship query
	 */
	private function queryVoivodeships($location_list, $options = array()) {
		$band = isset($options['band']) ? $options['band'] : 'All';
		$mode_category = isset($options['mode_category']) ? $options['mode_category'] : null;
		$confirmed = isset($options['confirmed']) ? $options['confirmed'] : false;
		$withCount = isset($options['withCount']) ? $options['withCount'] : false;
		$postdata = isset($options['postdata']) ? $options['postdata'] : array();

		$sql = $this->buildBaseQuery($location_list, $withCount);
		$sql = $this->addBandFilter($sql, $band);

		if ($mode_category) {
			$sql = $this->addModeCategoryFilter($sql, $mode_category);
		}

		if ($confirmed && !empty($postdata)) {
			$sql .= $this->genfunctions->addQslToQuery($postdata);
		}

		$sql = $this->finalizeQuery($sql, $withCount);

		$query = $this->db->query($sql);
		return $query->result();
	}

	/**
	 * Calculate award class based on minimum QSOs per voivodeship
	 */
	private function calculateAwardClass($counts) {
		$voiv_codes = $this->getVoivodeshipCodes();
		$min_count = PHP_INT_MAX;

		foreach ($voiv_codes as $code) {
			if (!isset($counts[$code]) || $counts[$code] == 0) {
				return null;
			}
			$min_count = min($min_count, $counts[$code]);
		}

		if ($min_count >= 12) return 'gold';
		if ($min_count >= 7) return 'silver';
		if ($min_count >= 3) return 'bronze';
		if ($min_count >= 1) return 'basic';

		return null;
	}

	/**
	 * Get worked (not confirmed) QSO counts by mode categories
	 */
	function get_polska_worked_by_modes($location_list) {
		$result = array();

		foreach ($this->voivodeship_names as $code => $name) {
			$result[$code] = array_fill_keys($this->MODE_CATEGORIES, 0);
		}

		foreach ($this->MODE_CATEGORIES as $category) {
			$voivData = $this->queryVoivodeships($location_list, array(
				'mode_category' => $category,
				'confirmed' => false,
				'withCount' => true
			));

			foreach ($voivData as $line) {
				if (isset($result[$line->COL_STATE])) {
					$result[$line->COL_STATE][$category] = (int)$line->qso_count;
				}
			}
		}

		return $result;
	}

	/**
	 * Get confirmed QSO counts by mode categories
	 */
	function get_polska_simple_by_modes($postdata, $location_list) {
		$result = array();

		foreach ($this->voivodeship_names as $code => $name) {
			$result[$code] = array_fill_keys($this->MODE_CATEGORIES, 0);
		}

		foreach ($this->MODE_CATEGORIES as $category) {
			$voivData = $this->queryVoivodeships($location_list, array(
				'mode_category' => $category,
				'confirmed' => true,
				'withCount' => true,
				'postdata' => $postdata
			));

			foreach ($voivData as $line) {
				if (isset($result[$line->COL_STATE])) {
					$result[$line->COL_STATE][$category] = (int)$line->qso_count;
				}
			}
		}

		return $result;
	}

	/**
	 * Get worked (not confirmed) QSO counts by bands
	 */
	function get_polska_worked_by_bands($bands, $location_list) {
		$result = array();

		foreach ($this->voivodeship_names as $code => $name) {
			$result[$code] = array_fill_keys($bands, 0);
		}

		foreach ($bands as $band) {
			$voivData = $this->queryVoivodeships($location_list, array(
				'band' => $band,
				'confirmed' => false,
				'withCount' => true
			));

			foreach ($voivData as $line) {
				if (isset($result[$line->COL_STATE])) {
					$result[$line->COL_STATE][$band] = (int)$line->qso_count;
				}
			}
		}

		return $result;
	}

	/**
	 * Get confirmed QSO counts by bands
	 */
	function get_polska_simple_by_bands($bands, $postdata, $location_list) {
		$result = array();

		foreach ($this->voivodeship_names as $code => $name) {
			$result[$code] = array_fill_keys($bands, 0);
		}

		foreach ($bands as $band) {
			$voivData = $this->queryVoivodeships($location_list, array(
				'band' => $band,
				'confirmed' => true,
				'withCount' => true,
				'postdata' => $postdata
			));

			foreach ($voivData as $line) {
				if (isset($result[$line->COL_STATE])) {
					$result[$line->COL_STATE][$band] = (int)$line->qso_count;
				}
			}
		}

		return $result;
	}

	/**
	 * Get total confirmed voivodeship counts by mode categories
	 */
	function get_polska_totals_by_modes($postdata, $location_list) {
		$totals = array();

		foreach ($this->MODE_CATEGORIES as $category) {
			$voivData = $this->queryVoivodeships($location_list, array(
				'mode_category' => $category,
				'confirmed' => true,
				'postdata' => $postdata
			));
			$totals[$category] = count($voivData);
		}

		return $totals;
	}

	/**
	 * Get total confirmed voivodeship counts by bands
	 */
	function get_polska_totals_by_bands($bands, $postdata, $location_list) {
		$totals = array();

		foreach ($bands as $band) {
			$voivData = $this->queryVoivodeships($location_list, array(
				'band' => $band,
				'confirmed' => true,
				'postdata' => $postdata
			));
			$totals[$band] = count($voivData);
		}

		return $totals;
	}

	/**
	 * Get award class by mode category
	 */
	function getPolskaClassByCategory($location_list, $mode_category, $postdata, $confirmed = false) {
		$voivData = $this->queryVoivodeships($location_list, array(
			'mode_category' => $mode_category,
			'confirmed' => $confirmed,
			'withCount' => true,
			'postdata' => $postdata
		));

		$counts = array();
		foreach ($voivData as $row) {
			$counts[$row->COL_STATE] = $row->qso_count;
		}

		return $this->calculateAwardClass($counts);
	}

	/**
	 * Get award class by band
	 */
	function getPolskaClassByBand($location_list, $band, $postdata, $confirmed = false) {
		$voivData = $this->queryVoivodeships($location_list, array(
			'band' => $band,
			'confirmed' => $confirmed,
			'withCount' => true,
			'postdata' => $postdata
		));

		$counts = array();
		foreach ($voivData as $row) {
			$counts[$row->COL_STATE] = $row->qso_count;
		}

		return $this->calculateAwardClass($counts);
	}

	/**
	 * Get map status (W=worked, C=confirmed, -=not worked)
	 */
	function get_polska_map_status($category, $postdata, $location_list) {
		$result = array();

		foreach ($this->voivodeship_names as $code => $name) {
			$result[$code] = '-';
		}

		$options = array('withCount' => true);

		if (in_array($category, $this->MODE_CATEGORIES)) {
			$options['mode_category'] = $category;
		} elseif (in_array($category, $this->VALID_BANDS)) {
			$options['band'] = $category;
		}

		// Get worked voivodeships
		$workedData = $this->queryVoivodeships($location_list, $options);
		$workedVoivs = array();
		foreach ($workedData as $line) {
			$workedVoivs[$line->COL_STATE] = true;
		}

		// Get confirmed voivodeships
		$options['confirmed'] = true;
		$options['postdata'] = $postdata;
		$confirmedData = $this->queryVoivodeships($location_list, $options);
		$confirmedVoivs = array();
		foreach ($confirmedData as $line) {
			$confirmedVoivs[$line->COL_STATE] = true;
		}

		// Build result
		foreach ($this->voivodeship_names as $code => $name) {
			if (isset($confirmedVoivs[$code])) {
				$result[$code] = 'C';
			} elseif (isset($workedVoivs[$code])) {
				$result[$code] = 'W';
			}
		}

		return $result;
	}
}
