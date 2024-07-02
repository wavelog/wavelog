<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("POTA Awards"); ?>";
            var lang_award_info_ln2 = "<?= __("Parks on the Air® (POTA) started in early 2017 when the ARRL's National Parks on the Air special event ended. A group of volunteers wanted to continue the fun beyond the one-year event, and thus, POTA was born."); ?>";
            var lang_award_info_ln3 = "<?= __("POTA works similarly to SOTA, with Activators and Hunters. For the awards, there are several categories based on the number of parks, geographic areas, and more."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(_pgettext("uses 'the website'", "For more information about the available awards and categories, please visit the %s."), "<a href='https://parksontheair.com/pota-awards/' target='_blank'>Parks on the Air® website</a>"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->
	<?php
		if ($pota_all) {
   if($this->session->userdata('user_date_format')) {
      // If Logged in and session exists
      $custom_date_format = $this->session->userdata('user_date_format');
   } else {
      // Get Default date format from /config/wavelog.php
      $custom_date_format = $this->config->item('qso_date_format');
   }
	?>
	
	<table style="width: 100%" id="potatable" class="potatable table table-sm table-striped table-hover">
	<thead>
		
	<tr>
		<th style="text-align: center"><?= __("POTA Reference(s)") ?></th>
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
		if ($pota_all->num_rows() > 0) {
			foreach ($pota_all->result() as $row) {
				$references = explode(',', $row->COL_POTA_REF);
					foreach ($references as $reference) {
	?>
	
	<tr>
		<td style="text-align: center"><a target="_blank" href="https://pota.app/#/park/<?php echo $reference; ?>"><?php echo $reference; ?></a></td>
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
		}
	?>
	
	</tbody>
	</table>
	<?php } else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }?>
</div>
