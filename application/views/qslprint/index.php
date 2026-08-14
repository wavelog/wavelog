<script>
	let lang_export_qslprint_pagination_all = "<?= __("All") ?>";
	let lang_qslprint_info = "<?= __("INFO") ?>";
	let lang_qslprint_select_at_least_one_row = "<?= __("Please select at least one row") ?>";
	let lang_print_label = "<?= __("Print Label") ?>";
	let lang_print_qslcard = "<?= __("Print QSLcard") ?>";
	let lang_qslprint_action_mark = "<?= __("Mark as sent") ?>";
	let lang_qslprint_action_remove = "<?= __("Remove") ?>";
	let lang_qslprint_action_qsolist = "<?= __("QSO List") ?>";
	let lang_qslprint_warning = "<?= __("Warning") ?>";
	let lang_qslprint_are_you_sure = "<?= __("Are you sure you want to mark ALL requested QSLs as sent?") ?>";
</script>

<div class="container">

	<br>

		<?php if($this->session->flashdata('message')) { ?>
			<!-- Display Message -->
			<div class="alert-message error">
				<p><?php echo $this->session->flashdata('message'); ?></p>
			</div>
		<?php } ?>

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
		<?= __("Export Requested QSLs for Printing"); ?>
	</div>
		<div class="card-body">
			<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
				<form class="d-flex align-items-center" action="<?php echo site_url('adif/import'); ?>" method="post" enctype="multipart/form-data">
					<label for="station_profile" class="me-2 text-nowrap"><?= __("Station Location"); ?>:</label>
					<select name="station_profile" class="station_id form-select form-select-sm" style="max-width: 220px;">
						<option value="All"><?= __("All"); ?></option>
						<?php foreach ($station_profile->result() as $station) { ?>
							<option <?php if ($station->station_id == $station_id) { echo "selected "; } ?>value="<?php echo $station->station_id; ?>"><?= __("Callsign"); ?>: <?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name; ?>)</option>
						<?php } ?>
					</select>
				</form>

				<!-- Switch Band or Frequency display -->
				<div class="d-flex align-items-center">
					<label for="frequency_or_band" class="me-2 text-nowrap"><?= __("Show Band or Frequency:"); ?></label>
					<select id="frequency_or_band" class="form-select form-select-sm">
						<option value="band" selected><?= __("Band"); ?></option>
						<option value="frequency"><?= __("Frequency"); ?></option>
						<option value="both"><?= __("Band & Frequency"); ?></option>
					</select>
				</div>

				<!-- Mark -->
				<div class="btn-group">
					<button type="button" class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-check me-1"></i><?= __("Mark"); ?></button>
					<ul class="dropdown-menu">
						<li><button type="button" class="dropdown-item markallprinted" onclick="markSelectedQsos();"><?= __("Mark selected QSOs as sent"); ?></button></li>
						<li><hr class="dropdown-divider"></li>
						<li><button type="button" class="dropdown-item" onclick="requestedQslAction('qsl_printed')"><?= __("Mark ALL requested QSLs as sent"); ?></button></li>
					</ul>
				</div>

				<!-- Export -->
				<div class="btn-group">
					<button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-file-export me-1"></i><?= __("Export"); ?></button>
					<ul class="dropdown-menu">
						<li><button type="button" class="dropdown-item exportselected" onclick="exportSelectedQsos();"><?= __("Export selected QSOs to ADIF-file"); ?></button></li>
						<li><hr class="dropdown-divider"></li>
						<li><button type="button" class="dropdown-item" onclick="requestedQslAction('exportcsv')"><?= __("Export requested QSLs to CSV-file"); ?></button></li>
						<li><hr class="dropdown-divider"></li>
						<li><button type="button" class="dropdown-item" onclick="requestedQslAction('exportadif')"><?= __("Export requested QSLs to ADIF-file"); ?></button></li>
					</ul>
				</div>

				<!-- Print -->
				<div class="btn-group">
					<button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-print me-1"></i><?= __("Print"); ?></button>
					<ul class="dropdown-menu">
						<li><button type="button" onclick="printDialog('qslcard');" class="dropdown-item"><?= __("Print Selected QSO Postcards"); ?></button></li>
						<li><hr class="dropdown-divider"></li>
						<li><button type="button" onclick="printDialog('qslcard', true);" class="dropdown-item"><?= __("Print Postcards for all QSOs"); ?></button></li>
						<li><hr class="dropdown-divider"></li>
						<li><button type="button" onclick="printDialog('label');" class="dropdown-item"><?= __("Print Selected QSL Labels"); ?></button></li>
						<li><hr class="dropdown-divider"></li>
						<li><button type="button" onclick="printDialog('label', true);" class="dropdown-item"><?= __("Print Labels for all QSOs"); ?></button></li>
					</ul>
				</div>

				<!-- Remove -->
				<div class="btn-group">
					<button type="button" class="btn btn-sm btn-danger dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-trash-alt me-1"></i><?= __("Remove"); ?></button>
					<ul class="dropdown-menu">
						<li><button type="button" class="dropdown-item removeall" onclick="removeSelectedQsos();"><?= __("Remove selected QSOs from the queue"); ?></button></li>
					</ul>
				</div>
			</div>

	    <p class="card-text"><?= __("Here you can export requested QSLs as CSV or ADIF files for printing and, optionally, mark them as sent."); ?></p>
	    <p class="card-text">
			<?= __("Requested QSLs are any QSOs with a value of 'Requested' or 'Queued' in their 'QSL Sent' field."); ?><br>
			<?= sprintf(__("The column %s shows how many QSLs have been sent to the same station before on the same band and mode."), "'" . __("Previous QSL") . "'"); ?>
		</p>

		<div class="resulttable">
		<?php
			$data2['qsos'] = $qsos;
			$this->load->view('qslprint/qslprint', $data2);
		?>
			</div>
		</div>
	</div>
</div>
