<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("WWFF - World Wide Flora and Fauna Award"); ?>";
            var lang_award_info_ln2 = "<?= __("WWFF, World Wide Flora and Fauna in Amateur Radio, encourages licensed ham radio operators to leave their shacks and operate portable in Protected Flora & Fauna areas (PFF) worldwide."); ?>";
            var lang_award_info_ln3 = "<?= __("More than 26,000 Protected Flora & Fauna (PFF) areas worldwide are already registered in the WWFF Directory. Hunters and Activators can apply for colorful awards, both globally and nationally."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://wwff.co/awards/' target='_blank'>https://wwff.co/awards/</a>"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->
	<?php
		if ($wwff_all) {
   if($this->session->userdata('user_date_format')) {
      // If Logged in and session exists
      $custom_date_format = $this->session->userdata('user_date_format');
   } else {
      // Get Default date format from /config/wavelog.php
      $custom_date_format = $this->config->item('qso_date_format');
   }
	?>
	
	<table style="width: 100%" id="wwfftable" class="wwfftable table table-sm table-striped table-hover">
	<thead>
		
	<tr>
		<th style="text-align: center"><?= __("WWFF Reference") ?></th>
		<th style="text-align: center"><?= __("Date") ?></th>
		<th style="text-align: center"><?= __("Time") ?></th>
		<th style="text-align: center"><?= __("Callsign") ?></th>
		<th style="text-align: center"><?= __("Band") ?></th>
		<th style="text-align: center"><?= __("RST (S)") ?></th>
		<th style="text-align: center"><?= __("RST (R)") ?></th>
	</tr>
	</thead>
	
	<tbody>
	<?php
		if ($wwff_all->num_rows() > 0) {
			foreach ($wwff_all->result() as $row) {
	?>
	
	<tr>
		<td style="text-align: center"><?php echo $row->COL_WWFF_REF; ?></td>
		<td style="text-align: center"><?php $timestamp = strtotime($row->COL_TIME_ON); echo date($custom_date_format, $timestamp); ?></td>
		<td style="text-align: center"><?php $timestamp = strtotime($row->COL_TIME_ON); echo date('H:i', $timestamp); ?></td>
		<td style="text-align: center"><?php echo $row->COL_CALL; ?></td>
		<td style="text-align: center"><?php if($row->COL_SAT_NAME != null) { echo $row->COL_SAT_NAME; } else { echo $row->COL_BAND; } ?></td>
		<td style="text-align: center"><?php echo $row->COL_RST_SENT; ?></td>
		<td style="text-align: center"><?php echo $row->COL_RST_RCVD; ?></td>
	</tr>
	<?php
		  }
		}
	?>
	
	</tbody>
	</table>
	<?php } else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }?>
</div>
