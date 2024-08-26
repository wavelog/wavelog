<?php
//Load Libraries
$CI = &get_instance();
$CI->load->library('Reg1testformat');

//Set headers
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $callsign . '-' . $contest_id . '-' . date('Ymd-Hi') . '-' . $CI->reg1testformat->reg1testbandstring($band) . '.edi"');

//get header
echo $CI->reg1testformat->header(
	$contest_id,
	$from,
	$to,
	$callsign,
	$gridlocator,
	$contestaddress1,
	$contestaddress2,
	$categoryoperator,
	$band,
	$club,
	$name,
	$responsible_operator,
	$address1,
	$address2,
	$addresspostalcode,
	$addresscity,
	$addresscountry,
	$operatorphone,
	$operators,
	$soapbox,
	$qso_count,
	$sentexchange,
	$txequipment,
	$power,
	$rxequipment,
	$antenna,
	$antennaheight,
	$maxdistanceqso
);

//write QSO details while keeping track of the QSO number
foreach ($qsos->result() as $row) {
	echo $CI->reg1testformat->qso($row);
}

//get footer
echo $CI->reg1testformat->footer();
