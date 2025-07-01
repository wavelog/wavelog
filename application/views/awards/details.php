<h5><?= __("Filtering on"); ?> <?php echo $filter; ?></h5>

<?php
$data['ispopup'] = $ispopup ?? '';
$this->load->view('view_log/partial/log_ajax', $data);
?>
