<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("73 on 73 Award"); ?>";
            var lang_award_info_ln2 = "<?= __("Paul Stoetzer N8HM is sponsoring an award for contacts made via the AO-73 (FUNcube-1) amateur radio satellite."); ?>";
            var lang_award_info_ln3 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://amsat-uk.org/funcube/73-on-73-award/' target='_blank'>https://amsat-uk.org/funcube/73-on-73-award/</a>"); ?>";
            var lang_award_info_ln4 = "";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->
	<?php
		if ($seven3on73_array) {
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
		<th style="text-align: center"><?= __("Number") ?></th>
		<th style="text-align: center"><?= __("Date") ?></th>
		<th style="text-align: center"><?= __("Time") ?></th>
		<th style="text-align: center"><?= __("Callsign") ?></th>
		<th style="text-align: center"><?= __("Mode") ?></th>
		<th style="text-align: center"><?= __("RST (R)") ?></th>
		<th style="text-align: center"><?= __("RST (S)") ?></th>
	</tr>
	</thead>
	
	<tbody>
	<?php
		$i = count($seven3on73_array);
		if ($i > 0) {
			foreach ($seven3on73_array as $row) {
	?>
	
	<tr>
		<td style="text-align: center"><?php echo $i--; ?></td>
		<td style="text-align: center"><?php $timestamp = strtotime($row->time); echo date($custom_date_format, $timestamp); ?></td>
		<td style="text-align: center"><?php $timestamp = strtotime($row->time); echo date('H:i', $timestamp); ?></td>
      <td style="text-align: center"><a href="javascript:displayQso(<?= $row->pkey; ?>)"><?php echo $row->callsign; ?></a></td>
		<td style="text-align: center"><?php echo $row->mode; ?></td>
		<td style="text-align: center"><?php echo $row->rst_r; ?></td>
		<td style="text-align: center"><?php echo $row->rst_s; ?></td>
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
