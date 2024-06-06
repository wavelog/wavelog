<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?php echo __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?php echo __("SOTA Awards"); ?>";
            var lang_award_info_ln2 = "<?php echo __("SOTA (Summits On The Air) is an award scheme for radio amateurs that encourages portable operation in mountainous areas."); ?>";
            var lang_award_info_ln3 = "<?php echo __("It is fully operational in nearly a hundred countries worldwide. Each country has its own Association that defines the recognized SOTA summits within that Association. Each summit earns the activators and chasers a score related to the height of the summit. Certificates are available for various scores, leading to the prestigious 'Mountain Goat' and 'Shack Sloth' trophies. An Honor Roll for Activators and Chasers is maintained in the SOTA online database."); ?>";
            var lang_award_info_ln4 = "<?php echo lang('awards_sota_description_ln4'); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?php echo __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->
	<?php
		if ($sota_all) {
	?>
	
	<table class="table table-sm table-striped table-hover">
		
	<tr>
		<td>Reference</td>
		<td>Date/Time</td>
		<td>Callsign</td>
		<td>Band</td>
		<td>RST Sent</td>
		<td>RST Received</td>
	</tr>
	
	<?php
		if ($sota_all->num_rows() > 0) {
			foreach ($sota_all->result() as $row) {
	?>
	
	<tr>
		<td>	
			<?php echo $row->COL_SOTA_REF; ?>
		</td>
		<td><?php $timestamp = strtotime($row->COL_TIME_ON); echo date('d/m/y', $timestamp); ?> - <?php $timestamp = strtotime($row->COL_TIME_ON); echo date('H:i', $timestamp); ?></td>
		<td><?php echo $row->COL_CALL; ?></td>
		<td><?php echo $row->COL_BAND; ?></td>
		<td><?php echo $row->COL_RST_SENT; ?></td>
		<td><?php echo $row->COL_RST_RCVD; ?></td>
	</tr>
	<?php
		  }
		}
	?>
	
	</table>
	<?php } else {
        echo '<div class="alert alert-danger" role="alert">Nothing found!</div>';
    }?>
</div>
