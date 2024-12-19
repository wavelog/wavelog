<?php
	//only set these values if internalrender is not present or false
	$internalrender = isset($internalrender) ? $internalrender : false;
	if(!$internalrender) {
		header('Content-Type: text/plain; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$this->session->userdata('user_callsign').'-'.date('Ymd-Hi').'.adi"');
	}
?>
Wavelog ADIF export
<ADIF_VER:5>3.1.5
<PROGRAMID:<?php echo strlen($this->config->item('app_name')); ?>><?php echo $this->config->item('app_name')."\r\n"; ?>
<PROGRAMVERSION:<?php echo strlen($this->optionslib->get_option('version')); ?>><?php echo $this->optionslib->get_option('version')."\r\n"; ?>
<EOH>

<?php
$CI =& get_instance();
if (!$CI->load->is_loaded('AdifHelper')) {
	$CI->load->library('AdifHelper');
}

foreach ($qsos->result() as $qso) {
    echo $CI->adifhelper->getAdifLine($qso);
}
