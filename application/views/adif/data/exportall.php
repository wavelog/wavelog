<?php
	//only set these values if internalrender is not present or false
	$internalrender = isset($internalrender) ? $internalrender : false;
	if(!$internalrender) {
		header('Content-Type: text/plain; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$this->session->userdata('user_callsign').'-'.date('Ymd-Hi').'.adi"');
	}
$CI =& get_instance();
if (!$CI->load->is_loaded('AdifHelper')) {
	$CI->load->library('AdifHelper');
}

echo $CI->adifhelper->getAdifHeader($CI->config->item('app_name'),$CI->optionslib->get_option('version'));

foreach ($qsos->result() as $qso) {
    echo $CI->adifhelper->getAdifLine($qso);
}
?>
