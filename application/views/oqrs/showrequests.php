<script type="text/javascript">
	let lang_oqrs_status_open_request = '<?= __("Open request"); ?>';
	let lang_oqrs_status_not_in_log_request = '<?= __("Not in log request"); ?>';
	let lang_oqrs_status_request_done = '<?= __("Request done"); ?>';
	let lang_oqrs_status_pending_requests = '<?= __("Pending requests"); ?>';
	let lang_oqrs_status_request_rejected = '<?= __("Request rejected"); ?>';
	let lang_oqrs_qsl_method_bureau = '<?= __("Bureau"); ?>';
	let lang_oqrs_qsl_method_direct = '<?= __("Direct"); ?>';
	let lang_oqrs_qsl_method_electronic = '<?= __("Electronic"); ?>';
	let lang_oqrs_error_request = '<?= __("An error ocurred while making the request"); ?>';
	let lang_oqrs_warning_delete = '<?= __("Warning! Are you sure you want to delete the marked OQRS request(s)?"); ?>';
	let lang_oqrs_warning_reject = '<?= __("Warning! Are you sure you want to reject the marked OQRS request(s)?"); ?>';
	let lang_oqrs_warning_mark = '<?= __("Warning! Are you sure you want to mark the marked OQRS request(s)?"); ?>';
	let lang_oqrs_warning_add_to_queue = '<?= __("Warning! Are you sure you want to add the marked OQRS request(s) to the queue?"); ?>';
	let lang_oqrs_status_message = '<?= __("OQRS Status Information"); ?>';
</script>

<div class="container-fluid oqrs pt-3 ps-4 pe-4">
	<h2><?php echo $page_title; ?></h2>
	<?php if ($this->session->flashdata('message')) { ?>
		<!-- Display Message -->
		<div class="alert-message error">
			<p><?php echo $this->session->flashdata('message'); ?></p>
		</div>
	<?php } ?>
<div class="row">
	<form id="searchForm" name="searchForm" action="<?php echo base_url()."index.php/oqrs/search";?>" method="post">
		<div class="row">
			<div class="form-group w-auto col-lg-2 col-md-2 col-sm-3 col-xl">
				<label class="form-label" for="de"><?= __("Location"); ?></label>
				<select id="de" name="de" class="form-select form-select-sm">
					<option value=""><?= __("All"); ?></option>
					<?php
					foreach($stations->result() as $station){
						?><option value="<?php echo htmlentities($station->station_id);?>"><?php echo htmlspecialchars($station->station_profile_name . ' - ' . $station->station_callsign);?></option><?php
					}
					?>
				</select>
			</div>
			<div class="form-group w-auto col-lg-2 col-md-2 col-sm-3 col-xl">
				<label class="form-label" for="dx"><?= __("Request callsign"); ?></label>
				<input type="text" name="dx" id="dx" class="form-control form-control-sm" value="">
			</div>

			<div class="form-group w-auto col-lg-2 col-md-2 col-sm-3 col-xl">
				<label for="status"><?= __("OQRS Status"); ?> <i class="statusinfo far fa-question-circle" aria-hidden="true"></i></label>
				<select id="status" name="status" class="form-select form-select-sm">
					<option value=""><?= __("All"); ?></option>
					<option value="0"><?= __("Open request"); ?></option>
					<option value="1"><?= __("Not in log request"); ?></option>
					<option value="2"><?= __("Done / sent"); ?></option>
					<option value="3"><?= __("Pending"); ?></option>
					<option value="4"><?= __("Rejected"); ?></option>
				</select>
			</div>
			<div class="form-group w-auto col-lg-2 col-md-2 col-sm-3 col-xl">
				<label for="oqrsResults"><?= __("# Results"); ?></label>
				<select id="oqrsResults" name="oqrsResults" class="form-select form-select-sm">
					<option value="50">50</option>
					<option value="200">200</option>
					<option value="500">500</option>
					<option value="1000">1000</option>
					<option value="All"><?= __("All"); ?></option>
				</select>
			</div>
			<div class="mb-3">
				<label>&nbsp;</label><br>
				<button type="submit" class="btn btn-sm btn-primary" id="searchButton"><?= __("Search"); ?></button>
				<button type="reset" class="btn btn-sm btn-danger" id="resetButton"><?= __("Reset"); ?></button>
				<span class="h6"><?= __("With selected"); ?>:</span>
				<button hidden type="button" class="btn btn-sm btn-success" id="addOqrsToQueue"><?= __("Add to print queue"); ?></button>
				<button type="button" class="btn btn-sm btn-success" id="markOqrs"><?= __("Mark as done"); ?></button>
				<button type="button" class="btn btn-sm btn-warning" id="rejectOqrs"><?= __("Reject"); ?></button>
				<button type="button" class="btn btn-sm btn-danger" id="deleteOqrs"><?= __("Delete"); ?></button>
			</div>
		</div>
	</form>
</div>

<table style="width:100%" class="table-sm oqrstable table table-striped table-bordered table-hover table-condensed text-center" id="qsoList">
	<thead>
		<tr>
			<th><div class="form-check" style="margin-top: -1.5em"><input class="form-check-input" type="checkbox" id="checkBoxAll" /></div></th>
			<th><?= __("Time of request"); ?></th>
			<th><?= __("QSO Date"); ?></th>
			<th><?= __("QSO Time"); ?></th>
			<th><?= __("Band"); ?></th>
			<th><?= __("Mode"); ?></th>
			<th><?= __("Request callsign"); ?></th>
			<th><?= __("Station callsign"); ?></th>
			<th><?= __("E-mail"); ?></th>
			<th><?= __("Note"); ?></th>
			<th><?= __("QSL route"); ?></th>
			<th><?= __("Check log"); ?></th>
			<th><?= __("Status"); ?></th>

		</tr>
	</thead>
	<tbody>

	</tbody>
</table>
