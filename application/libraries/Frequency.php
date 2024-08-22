<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Frequency {

	public $defaultFrequencies = array(
		'160m' => array(
			'SSB' => "1900000",
			'DATA' => "1838000",
			'CW' => "1830000",
			'NOMINAL' => "1.8",
			'UNIT' => 'kHz'),
		'80m' => array(
			'SSB' => "3700000",
			'DATA' => "3583000",
			"CW" => "3550000",
			"NOMINAL" => "3.5",
			'UNIT' => 'kHz'),
		'60m' => array(
			'SSB' => "5330000",
			'DATA' => "5330000",
			"CW" => "5260000",
			"NOMINAL" => "5",
			'UNIT' => 'kHz'),
		'40m' => array(
			'SSB' => "7100000",
			'DATA' => "7040000",
			'CW' => "7020000",
			'NOMINAL' => "7",
			'UNIT' => 'kHz'),
		'30m' => array(
			'SSB' => "10120000",
			'DATA' => "10145000",
			'CW' => "10120000",
			'NOMINAL' => "10",
			'UNIT' => 'kHz'),
		'20m' => array(
			'SSB' => "14200000",
			'DATA' => "14080000",
			'CW' => "14020000",
			'NOMINAL' => "14",
			'UNIT' => 'kHz'),
		'17m' => array(
			'SSB' => "18130000",
			'DATA' => "18105000",
			'CW' => "18080000",
			'NOMINAL' => "18",
			'UNIT' => 'kHz'),
		'15m' => array(
			'SSB' => "21300000",
			'DATA' => "21080000",
			'CW' => "21020000",
			'NOMINAL' => "21",
			'UNIT' => 'kHz'),
		'12m' => array(
			'SSB' => "24950000",
			'DATA' => "24925000",
			'CW' => "24900000",
			'NOMINAL' => "24",
			'UNIT' => 'kHz'),
		'10m' => array(
			'SSB' => "28300000",
			'DATA' => "28120000",
			'CW' => "28050000",
			'NOMINAL' => "28",
			'UNIT' => 'kHz'),
		'6m' => array(
			'SSB' => "50150000",
			'DATA' => "50230000",
			'CW' => "50090000",
			'NOMINAL' => "50",
			'UNIT' => 'MHz'),
		'4m' => array(
			'SSB' => "70200000",
			'DATA' => "70200000",
			'CW' => "70200000",
			'NOMINAL' => "70",
			'UNIT' => 'MHz'),
		'2m' => array(
			'SSB' => "144300000",
			'DATA' => "144370000",
			'CW' => "144050000",
			'NOMINAL' => "144",
			'UNIT' => 'MHz'),
		'1.25m' => array(
			'SSB' => "222100000",
			'DATA' => "222100000",
			'CW' => "222100000",
			'NOMINAL' => "222",
			'UNIT' => 'MHz'),
		'70cm' => array(
			'SSB' => "432200000",
			'DATA' => "432088000",
			'CW' => "432050000",
			'NOMINAL' => "433",
			'UNIT' => 'MHz'),
		'33cm' => array(
			'SSB' => "902100000",
			'DATA' => "902100000",
			'CW' => "902100000",
			'NOMINAL' => "902",
			'UNIT' => 'MHz'),
		'23cm' => array(
			'SSB' => "1296000000",
			'DATA' => "1296138000",
			'CW' => "129600000",
			'NOMINAL' => "1296",
			'UNIT' => 'GHz'),
		'13cm' => array(
			'SSB' => "2320800000",
			'DATA' => "2320800000",
			'CW' => "2320800000",
			'NOMINAL' => "2400",
			'UNIT' => 'GHz'),
		'9cm' => array(
			'SSB' => "3410000000",
			'DATA' => "3410000000",
			'CW' => "3400000000",
			'NOMINAL' => "3400",
			'UNIT' => 'GHz'),
		'6cm' => array(
			'SSB' => "5670000000",
			'DATA' => "5670000000",
			'CW' => "5670000000",
			'NOMINAL' => "5670",
			'UNIT' => 'GHz'),
		'3cm' => array(
			'SSB' => "10225000000",
			'DATA' => "10225000000",
			'CW' => "10225000000",
			'NOMINAL' => "10225",
			'UNIT' => 'GHz'),
		'1.25cm' => array(
			'SSB' => "24000000000",
			'DATA' => "24000000000",
			'CW' => "24000000000",
			'NOMINAL' => "24000",
			'UNIT' => 'GHz'),
	);

	/* Class to convert band and mode into a frequency in a format based on the specifications of the database table */
	public function convert_band($band, $mode = 'SSB') {
		// Converting LSB and USB to SSB
		if ($mode == 'LSB' or $mode == 'USB') {
			$mode = "SSB";
		}

		// Use 'DATA' for any of the data modes
		if ($mode != 'CW' and $mode != 'SSB') {
			$mode = "DATA";
		}

		return $this->getDefaultFrequency($band, $mode);
	}

	function getDefaultFrequency($band, $mode) {
		$CI = &get_instance();
		$db = &$CI->db;

		$db->from('bands');
		$db->where('bands.band', $band);

		$result = $db->get()->row();

		$mode = strtolower($mode);

		return $result->$mode;
	}

	public function GetBand($Frequency) {
		$Band = NULL;
		if ($Frequency > 1000000 && $Frequency < 2000000) {
			$Band = "160m";
		} else if ($Frequency > 3000000 && $Frequency < 4000000) {
			$Band = "80m";
		} else if ($Frequency > 6000000 && $Frequency < 8000000) {
			$Band = "40m";
		} else if ($Frequency > 9000000 && $Frequency < 11000000) {
			$Band = "30m";
		} else if ($Frequency > 13000000 && $Frequency < 15000000) {
			$Band = "20m";
		} else if ($Frequency > 17000000 && $Frequency < 19000000) {
			$Band = "17m";
		} else if ($Frequency > 20000000 && $Frequency < 22000000) {
			$Band = "15m";
		} else if ($Frequency > 23000000 && $Frequency < 25000000) {
			$Band = "12m";
		} else if ($Frequency > 27000000 && $Frequency < 30000000) {
			$Band = "10m";
		} else if ($Frequency > 49000000 && $Frequency < 52000000) {
			$Band = "6m";
		} else if ($Frequency > 69000000 && $Frequency < 71000000) {
			$Band = "4m";
		} else if ($Frequency > 140000000 && $Frequency < 150000000) {
			$Band = "2m";
		} else if ($Frequency > 218000000 && $Frequency < 226000000) {
			$Band = "1.25m";
		} else if ($Frequency > 430000000 && $Frequency < 440000000) {
			$Band = "70cm";
		} else if ($Frequency > 900000000 && $Frequency < 930000000) {
			$Band = "33cm";
		} else if ($Frequency > 1200000000 && $Frequency < 1300000000) {
			$Band = "23cm";
		} else if ($Frequency > 2200000000 && $Frequency < 2600000000) {
			$Band = "13cm";
		} else if ($Frequency > 3000000000 && $Frequency < 4000000000) {
			$Band = "9cm";
		} else if ($Frequency > 5000000000 && $Frequency < 6000000000) {
			$Band = "6cm";
		} else if ($Frequency > 9000000000 && $Frequency < 11000000000) {
			$Band = "3cm";
		} else if ($Frequency > 23000000000 && $Frequency < 25000000000) {
			$Band = "1.2cm";
		} else if ($Frequency > 46000000000 && $Frequency < 55000000000) {
			$Band = "6mm";
		} else if ($Frequency > 75000000000 && $Frequency < 82000000000) {
			$Band = "4mm";
		} else if ($Frequency > 120000000000 && $Frequency < 125000000000) {
			$Band = "2.5mm";
		} else if ($Frequency > 133000000000 && $Frequency < 150000000000) {
			$Band = "2mm";
		} else if ($Frequency > 240000000000 && $Frequency < 250000000000) {
			$Band = "1mm";
		} else if ($Frequency >= 250000000000) {
			$Band = "<1mm";
		}
		return $Band;
	}

	/**
	 * Convert a frequency to a specified unit and return it in a specified format.
	 *
	 * @param float  	$frequency		The frequency value to be converted.
	 * 							
	 * @param int    	$r_option		(Optional) The result format option. 
	 *                            		0: Return as a string with just the frequency.
	 *                            		1 (default): Return as a string with frequency and unit.
	 *                            		2: Return as a string with frequency, unit, and band.
	 * 
	 * @param string 	$source_unit 	(Optional) The source unit of the frequency. 
	 *                            		Possible values: 'Hz', 'kHz', 'MHz', 'GHz'. 
	 *                            		Default is 'Hz'. 
	 * 									! If the source unit is not 'Hz', you have to provide the source unit. !
	 * 
	 * @param string 	$target_unit 	(Optional) The target unit for conversion. 
	 *                            		Possible values: 'Hz', 'kHz', 'MHz', 'GHz'. 
	 *                            		If not provided, the unit is determined based on session data (function qrg_unit()).
	 *
	 * @return string 	The converted frequency in the specified format.
	 * 
	 * To change the number of decimals shown per unit, add in your config.php
	 * 
	 * $config['qrg_hz_dec'] = 0;
	 * $config['qrg_khz_dec'] = 0;
	 * $config['qrg_mhz_dec'] = 3;
	 * $config['qrg_ghz_dec'] = 3;
	 * 
	 * and adjust the values to your needs.
	 * 
	 */
	function qrg_conversion($frequency, $r_option = 1, $source_unit = 'Hz', $target_unit = NULL) {

		$CI = &get_instance();
	
		// Get the band
		$band = $this->GetBand($frequency);
	
		// Get the target unit
		if ($target_unit === NULL) {
			$target_unit = $this->qrg_unit($band);
		}
	
		// Convert the frequency to Hz
		switch ($source_unit) {
			case 'Hz':
				break;
			case 'kHz':
				$frequency *= 1000;
				break;
			case 'MHz':
				$frequency *= 1000000; // 1000 * 1000
				break;
			case 'GHz':
				$frequency *= 1000000000; // 1000 * 1000 * 1000
				break;
		}
	
		// Convert the frequency to the target unit
		switch ($target_unit) {
			case 'Hz':
				$decimals = $CI->config->item('qrg_hz_dec') ?? 0;
				break;
			case 'kHz':
				$frequency /= 1000;
				$decimals = $CI->config->item('qrg_khz_dec') ?? 0;
				break;	
			case 'MHz':
				$frequency /= 1000000; // 1000 * 1000
				$decimals = $CI->config->item('qrg_mhz_dec') ?? 3;
				break;
			case 'GHz':
				$frequency /= 1000000000; // 1000 * 1000 * 1000
				$decimals = $CI->config->item('qrg_ghz_dec') ?? 3;
				break;
		}

		// Return
		switch ($r_option) {
			case 0:
				return number_format($frequency, $decimals, '.', '');
				break;
			case 1:
				return number_format($frequency, $decimals, '.', '') . ' ' . $target_unit;
				break;
			case 2:
				return number_format($frequency, $decimals, '.', '') . ' ' . $target_unit . ' (' . $band . ')';
				break;
		}
	}

	function qrg_unit($band) {

		$CI = &get_instance();

		if ($CI->session->userdata('qrgunit_'.$band)) {
			$unit = $CI->session->userdata('qrgunit_'.$band);
		} else {
			if (isset($this->defaultFrequencies[$band]['UNIT'])) {
				$unit = $this->defaultFrequencies[$band]['UNIT'];
			} else {
				$unit = 'kHz'; // we use kHz as fallback unit
			}
		}
		return $unit;
	}
}
/* End of file Frequency.php */
