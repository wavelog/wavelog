<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("SOTA Awards"); ?>";
            var lang_award_info_ln2 = "<?= __("SOTA (Summits On The Air) is an award scheme for radio amateurs that encourages portable operation in mountainous areas."); ?>";
            var lang_award_info_ln3 = "<?= __("It is fully operational in nearly a hundred countries worldwide. Each country has its own Association that defines the recognized SOTA summits within that Association. Each summit earns the activators and chasers a score related to the height of the summit. Certificates are available for various scores, leading to the prestigious 'Mountain Goat' and 'Shack Sloth' trophies. An Honor Roll for Activators and Chasers is maintained in the SOTA online database."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://www.sota.org.uk/' target='_blank'>https://www.sota.org.uk/</a>"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->
	<?php
		if ($sota_all) {
			if($this->session->userdata('user_date_format')) {
				// If Logged in and session exists
				$custom_date_format = $this->session->userdata('user_date_format');
			} else {
			// Get Default date format from /config/wavelog.php
				$custom_date_format = $this->config->item('qso_date_format');
			}
	?>

	<table style="width: 100%" id="sotatable" class="potatable table table-sm table-striped table-hover">
	<thead>
	<tr>
		<th><?= __("Reference"); ?></th>
		<th><?= __("Date"); ?></th>
		<th><?= __("Time"); ?></th>
		<th><?= __("Callsign"); ?></th>
		<th><?= __("Band"); ?></th>
		<th><?= __("RST Sent"); ?></th>
		<th><?= __("RST Received"); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php
		if ($sota_all->num_rows() > 0) {
			foreach ($sota_all->result() as $row) {
	?>
	<tr>
		<td><a target="_blank" href="https://www.sotadata.org.uk/en/summit/<?php echo $row->COL_SOTA_REF; ?>"><?php echo $row->COL_SOTA_REF; ?></a></td>
		<td style="text-align: center"><?php $timestamp = strtotime($row->COL_TIME_ON); echo date($custom_date_format, $timestamp); ?></td>
		<td style="text-align: center"><?php $timestamp = strtotime($row->COL_TIME_ON); echo date('H:i', $timestamp); ?></td>
		<td><?php echo $row->COL_CALL; ?></td>
		<td><?php echo $row->COL_BAND; ?></td>
		<td><?php echo $row->COL_RST_SENT; ?></td>
		<td><?php echo $row->COL_RST_RCVD; ?></td>
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
