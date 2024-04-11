<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Update existing POTA references as per their renaming
// of parks
// See https://docs.pota.app/docs/changes.html

class Migration_pota_migrations extends CI_Migration
{

	var $map = array(
		// Changes on 2024-02-20
		'4O' => 'ME',
		// Changes on 2024-02-22
		'ES' => 'EE',
		'S0' => 'EH',
		'9J2' => 'ZM',
		'7O' => 'YE',
		// Changes on 2024-02-24
		'TF' => 'IS',
		'Z2' => 'ZW',
		'8R' => 'GY',
		'V2' => 'AG',
		'UI' => 'UZ',
		'HV' => 'VA',
		// Changes on 2024-02-25
		'3DA' => 'SZ',
		'9N' => 'NP',
		'3B8' => 'MU',
		'ZA' => 'AL',
		'4J' => 'AZ',
		'3C' => 'GQ',
		'E3' => 'ER',
		'HH' => 'HT',
		'YN' => 'NI',
		'3X' => 'GN',
		'C2' => 'NR',
		'8Q6' => 'MV',
		'H44' => 'SB',
		'5U' => 'NE',
		'E6' => 'NU',
		'EZ' => 'TM',
		'5X' => 'UG',
		'A9' => 'BH',
		'S2' => 'BD',
		'A5' => 'BT',
		'4S7' => 'LK',
		'EP' => 'IR',
		'P5' => 'KP',
		'9K2' => 'KW',
		'OD5' => 'LB',
		'JT' => 'MN',
		'AP' => 'PK',
		'A7' => 'QA',
		'HZ' => 'SA',
		'S79' => 'SC',
		'J2' => 'DJ',
		'7X' => 'DZ',
		'D2' => 'AO',
		'9U5' => 'BI',
		//'' => 'CM',
		'D6' => 'KM',
		//'' => 'CG',
		'5Z4' => 'KE',
		'7P8' => 'LS',
		'EL' => 'LR',
		//'5A' => '',
		'5R' => 'MG',
		'7Q7' => 'MW',
		//'' => 'ML',
		'5T' => 'MR',
		'CN' => 'MA',
		'5N' => 'NG',
		'9X5' => 'RW',
		'6W' => 'SN',
		'9L1' => 'SL',
		'OM' => 'SK',
		'ZC6' => 'PS',
		'PJ7' => 'SX',
		//'5H3' => '',
		//'' => 'BQ',
		//'' => 'CD',
		//'3V8' => '',
		//'S9' => '',
		//'EY' => '',
		// Changes on 2024-02-26
		'EK' => 'AM',
		'OX' => 'GL',
		'OY' => 'FO',
		'P4' => 'AW',
		'V4' => 'KN',
		'HC' => 'EC',
		'V5' => 'NA',
		'CP' => 'BO',
		'PZ' => 'SR',
		'VP8' => 'FK',
		'YA' => 'AF',
		'VP2E' => 'AI',
		'T8' => 'PW',
		'T31' => 'KI',
		'4L' => 'GE',
		'VP5' => 'TC',
		'J7' => 'DM',
		'VP2M' => 'MS',
		'J8' => 'VC',
		'UL' => 'KZ',
		'UM' => 'KG',
		'XX9' => 'MO',
		'P29' => 'PG',
		'UO' => 'MD',
		'C3' => 'AD',
		'T5' => 'SO',
		'9H' => 'MT',
		'T2' => 'TV',
		'XZ2' => 'MM',
		'XU' => 'KH',
		'YI' => 'IQ',
		'V85' => 'BN',
		'YK' => 'SY',
		'A6' => 'AE',
		'TL' => 'CF',
		'TT' => 'TD',
		'TY' => 'BJ',
		'TR' => 'GA',
		'TU' => 'CI',
		//'' => 'SD',
		'SU' => 'EG',
		'XT' => 'BF',
		//'T33' => '',
		'ST0' => 'SS',
		//'Z6' => '',
		'CE9' => 'AQ',
		'GJ' => 'JE',
		'GU' => 'GG',
		// Changes on 2024-02-28
		'OU' => 'DK',
		//'' => 'PM'
		// Changes on 2024-02-29
		'HI' => 'DO',
		//'' => 'PM'
		// Changes on 2024-03-01
		'9M' => 'MY',
		'9V' => 'SG',
		'HL' => 'KR',
		'HS' => 'TH',
		'5B' => 'CY',
		//'' => 'CL',
		//'' => 'TW',
		'OE' => 'AT',
		'HB' => 'CH',
		'LX' => 'LU',
		'HR' => 'HN',
		'8P' => 'BB',
		'DU' => 'PH',
		'6Y' => 'JM',
		'A2' => 'BW',
		'C9' => 'MZ',
		'CV' => 'UY',
		//'9Y' => '',
		'C6' => 'BS',
		'J6' => 'LC',
		'A3' => 'TO',
		'HA' => 'HU',
		'HB0' => 'LI',
		//'4U1UN' => '',
		'4X' => 'IL',
		'JY' => 'JO',
		//'A4' => '',
		'9G1' => 'GH',
		'OK' => 'CZ',
		//'4W' => '',
		'GD' => 'IM',
		// Changes on 2024-03-03
		'PA' => 'NL',
		'DA' => 'DE',
		'CT' => 'PT',
		'JA' => 'JP',
		//'' => 'NO',
		//'' => 'IN',
		//'' => 'SE',
		//'' => 'WF',
		//'' => 'VI',
		//'' => 'TK',
		//'' => 'SJ',
		//'' => 'RE',
		//'' => 'PR',
		//'' => 'PN',
		//'' => 'NF',
		//'' => 'NC',
		//'' => 'MP',
		//'' => 'MF',
		//'' => 'IO',
		//'' => 'HM',
		//'' => 'GS',
		//'' => 'GP',
		//'' => 'GF',
		//'' => 'CX',
		//'' => 'CK',
		//'' => 'CC',
		//'' => 'BL',
		//'' => 'AX',
		//'' => 'AS',
		//'EA' => '',
		//'YV' => '',
		//'EV' => '',
		//'SM' => '',
		//'XW' => '',
		//'YJ8' => '',
		//'KP' => '',
		// Changes on 2024-03-04
		'VP9' => 'BM',
		// CHanges on 2024-03-14
		'OA' => 'PE',
		'OH' => 'FI',
		'TG' => 'GT',
		//'T7' => ''
		//'TA' => ''
		// Changes on 2024-03-15
		'F' => 'FR',
		'XE' => 'MX',
		//'ZR' => ''
		//'CN' => ''
		//'' => 'MQ'
		// Changes on 2024-03-18
		'I' => 'IT',
		'ZL' => 'NZ',
		'SP' => 'PL',
		'V3' => 'BZ',
		'ZF' => 'KY',
		//'YS' => '',
		'YO' => 'RO',
		'PJ2' => 'CW',
	);

	public function up()
	{
		foreach ($this->map as $key => $value) {
			$this->update_db($key, $value);
		}
	}

	public function down()
	{
	}

	function update_db($from, $to) {
		$sql= "UPDATE ".$this->config->item('table_name')." SET COL_POTA_REF = REPLACE(COL_POTA_REF, '".$from."-', '".$to."-') WHERE SUBSTRING(COL_POTA_REF,1,3) = '".$from."-';";
		$this->db->query($sql);
	}

}
