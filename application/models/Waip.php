<?php

class Waip extends CI_Model {

	// Award constants
	private $DXCC_ITALY = '248';
	private $DXCC_SARDINIA = '225';
	private $VALID_BANDS = array('160M','80M','40M','30M','20M','17M','15M','12M','10M');
	private $MODE_CATEGORIES = array('MIXED', 'PHONE', 'CW', 'DIGI');
	private $AWARD_START_DATE = '1948-06-02';

	// Italian provinces mapping (2-letter codes to names)
	private $province_names = array(
		'AG' => 'Agrigento',
		'AL' => 'Alessandria',
		'AN' => 'Ancona',
		'AO' => 'Aosta',
		'AP' => 'Ascoli Piceno',
		'AQ' => 'L\'Aquila',
		'AR' => 'Arezzo',
		'AT' => 'Asti',
		'AV' => 'Avellino',
		'BA' => 'Bari',
		'BG' => 'Bergamo',
		'BI' => 'Biella',
		'BL' => 'Belluno',
		'BN' => 'Benevento',
		'BO' => 'Bologna',
		'BR' => 'Brindisi',
		'BS' => 'Brescia',
		'BT' => 'Barletta-Andria-Trani',
		'BZ' => 'Bolzano/Bozen',
		'CA' => 'Cagliari',
		'CB' => 'Campobasso',
		'CE' => 'Caserta',
		'CH' => 'Chieti',
		'CI' => 'Carbonia-Iglesias',
		'CL' => 'Caltanissetta',
		'CN' => 'Cuneo',
		'CO' => 'Como',
		'CR' => 'Cremona',
		'CS' => 'Cosenza',
		'CT' => 'Catania',
		'CZ' => 'Catanzaro',
		'EN' => 'Enna',
		'FC' => 'Forlì-Cesena',
		'FE' => 'Ferrara',
		'FG' => 'Foggia',
		'FI' => 'Firenze',
		'FM' => 'Fermo',
		'FR' => 'Frosinone',
		'GE' => 'Genova',
		'GO' => 'Gorizia',
		'GR' => 'Grosseto',
		'IM' => 'Imperia',
		'IS' => 'Isernia',
		'KR' => 'Crotone',
		'LC' => 'Lecco',
		'LE' => 'Lecce',
		'LI' => 'Livorno',
		'LO' => 'Lodi',
		'LT' => 'Latina',
		'LU' => 'Lucca',
		'MB' => 'Monza e Brianza',
		'MC' => 'Macerata',
		'ME' => 'Messina',
		'MI' => 'Milano',
		'MN' => 'Mantova',
		'MO' => 'Modena',
		'MS' => 'Massa-Carrara',
		'MT' => 'Matera',
		'NA' => 'Napoli',
		'NO' => 'Novara',
		'NU' => 'Nuoro',
		'OG' => 'Ogliastra',
		'OR' => 'Oristano',
		'OT' => 'Olbia-Tempio',
		'PA' => 'Palermo',
		'PC' => 'Piacenza',
		'PD' => 'Padova',
		'PE' => 'Pescara',
		'PG' => 'Perugia',
		'PI' => 'Pisa',
		'PN' => 'Pordenone',
		'PO' => 'Prato',
		'PR' => 'Parma',
		'PT' => 'Pistoia',
		'PU' => 'Pesaro-Urbino',
		'PV' => 'Pavia',
		'PZ' => 'Potenza',
		'RA' => 'Ravenna',
		'RC' => 'Reggio Calabria',
		'RE' => 'Reggio Emilia',
		'RG' => 'Ragusa',
		'RI' => 'Rieti',
		'RM' => 'Roma',
		'RN' => 'Rimini',
		'RO' => 'Rovigo',
		'SA' => 'Salerno',
		'SI' => 'Siena',
		'SO' => 'Sondrio',
		'SP' => 'La Spezia',
		'SR' => 'Siracusa',
		'SS' => 'Sassari',
		'SV' => 'Savona',
		'TA' => 'Taranto',
		'TE' => 'Teramo',
		'TN' => 'Trento',
		'TO' => 'Torino',
		'TP' => 'Trapani',
		'TR' => 'Terni',
		'TS' => 'Trieste',
		'TV' => 'Treviso',
		'UD' => 'Udine',
		'VA' => 'Varese',
		'VB' => 'Verbano-Cusio-Ossola',
		'VC' => 'Vercelli',
		'VE' => 'Venezia',
		'VI' => 'Vicenza',
		'VR' => 'Verona',
		'VS' => 'Medio Campidano',
		'VT' => 'Viterbo',
		'VV' => 'Vibo Valentia'
	);

	function __construct() {
		$this->load->library('Genfunctions');
	}

	/**
	 * Get province codes
	 */
	function getProvinceCodes() {
		return array_keys($this->province_names);
	}

	/**
	 * Get province name from code
	 */
	function getProvinceName($code) {
		return isset($this->province_names[$code]) ? $this->province_names[$code] : $code;
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
			AND COL_TIME_ON >= '" . $this->AWARD_START_DATE . "'
			AND COL_DXCC IN ('" . $this->DXCC_ITALY . "', '" . $this->DXCC_SARDINIA . "')
			AND (COL_PROP_MODE != 'SAT' OR COL_PROP_MODE IS NULL)
			AND COL_BAND IN ('" . implode("','", $this->VALID_BANDS) . "')
			AND COL_STATE IS NOT NULL AND COL_STATE != ''
			AND UPPER(COL_STATE) IN ('" . implode("','", $this->getProvinceCodes()) . "')";

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
	 * Execute province query
	 */
	private function queryProvinces($location_list, $options = array()) {
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
	 * Get worked (not confirmed) QSO counts by mode categories
	 */
	function get_waip_worked_by_modes($location_list) {
		$result = array();

		foreach ($this->province_names as $code => $name) {
			$result[$code] = array_fill_keys($this->MODE_CATEGORIES, 0);
		}

		foreach ($this->MODE_CATEGORIES as $category) {
			$provData = $this->queryProvinces($location_list, array(
				'mode_category' => $category,
				'confirmed' => false,
				'withCount' => true
			));

			foreach ($provData as $line) {
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
	function get_waip_simple_by_modes($postdata, $location_list) {
		$result = array();

		foreach ($this->province_names as $code => $name) {
			$result[$code] = array_fill_keys($this->MODE_CATEGORIES, 0);
		}

		foreach ($this->MODE_CATEGORIES as $category) {
			$provData = $this->queryProvinces($location_list, array(
				'mode_category' => $category,
				'confirmed' => true,
				'withCount' => true,
				'postdata' => $postdata
			));

			foreach ($provData as $line) {
				if (isset($result[$line->COL_STATE])) {
					$result[$line->COL_STATE][$category] = (int)$line->qso_count;
				}
			}
		}

		return $result;
	}

	/**
	 * Get total confirmed province counts by mode categories
	 */
	function get_waip_totals_by_modes($postdata, $location_list) {
		$totals = array();

		foreach ($this->MODE_CATEGORIES as $category) {
			$provData = $this->queryProvinces($location_list, array(
				'mode_category' => $category,
				'confirmed' => true,
				'postdata' => $postdata
			));
			$totals[$category] = count($provData);
		}

		return $totals;
	}

	/**
	 * Get worked (not confirmed) QSO counts by bands
	 */
	function get_waip_worked_by_bands($location_list) {
		$result = array();

		foreach ($this->province_names as $code => $name) {
			$result[$code] = array_fill_keys($this->VALID_BANDS, 0);
		}

		foreach ($this->VALID_BANDS as $band) {
			$provData = $this->queryProvinces($location_list, array(
				'band' => $band,
				'confirmed' => false,
				'withCount' => true
			));

			foreach ($provData as $line) {
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
	function get_waip_simple_by_bands($postdata, $location_list) {
		$result = array();

		foreach ($this->province_names as $code => $name) {
			$result[$code] = array_fill_keys($this->VALID_BANDS, 0);
		}

		foreach ($this->VALID_BANDS as $band) {
			$provData = $this->queryProvinces($location_list, array(
				'band' => $band,
				'confirmed' => true,
				'withCount' => true,
				'postdata' => $postdata
			));

			foreach ($provData as $line) {
				if (isset($result[$line->COL_STATE])) {
					$result[$line->COL_STATE][$band] = (int)$line->qso_count;
				}
			}
		}

		return $result;
	}

	/**
	 * Get total confirmed province counts by bands
	 */
	function get_waip_totals_by_bands($postdata, $location_list) {
		$totals = array();

		foreach ($this->VALID_BANDS as $band) {
			$provData = $this->queryProvinces($location_list, array(
				'band' => $band,
				'confirmed' => true,
				'postdata' => $postdata
			));
			$totals[$band] = count($provData);
		}

		return $totals;
	}

	/**
	 * Get map status (W=worked, C=confirmed, -=not worked)
	 */
	function get_waip_map_status($category, $postdata, $location_list) {
		$result = array();

		foreach ($this->province_names as $code => $name) {
			$result[$code] = '-';
		}

		$options = array('withCount' => true);

		if (in_array($category, $this->MODE_CATEGORIES)) {
			$options['mode_category'] = $category;
		} elseif (in_array($category, $this->VALID_BANDS)) {
			$options['band'] = $category;
		}

		// Get worked provinces
		$workedData = $this->queryProvinces($location_list, $options);
		$workedProvs = array();
		foreach ($workedData as $line) {
			$workedProvs[$line->COL_STATE] = true;
		}

		// Get confirmed provinces
		$options['confirmed'] = true;
		$options['postdata'] = $postdata;
		$confirmedData = $this->queryProvinces($location_list, $options);
		$confirmedProvs = array();
		foreach ($confirmedData as $line) {
			$confirmedProvs[$line->COL_STATE] = true;
		}

		// Build result
		foreach ($this->province_names as $code => $name) {
			if (isset($confirmedProvs[$code])) {
				$result[$code] = 'C';
			} elseif (isset($workedProvs[$code])) {
				$result[$code] = 'W';
			}
		}

		return $result;
	}
}
?>
