Wavelog ADIF export
<ADIF_VER:5>3.1.5
<PROGRAMID:<?php echo strlen($this->config->item('app_name')); ?>><?php echo $this->config->item('app_name')."\r\n"; ?>
<PROGRAMVERSION:<?php echo strlen($this->optionslib->get_option('version')); ?>><?php echo $this->optionslib->get_option('version')."\r\n"; ?>
<EOH>

<?php

foreach ($qsos->result() as $qso) {
    echo $this->adifhelper->getAdifLine($qso);
}
