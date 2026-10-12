<style>
	/* general.css and the theme overrides center every DataTable header; keep the scan table left aligned */
	#wabtoolTable thead th {
		text-align: left !important;
	}
</style>

<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Batch Assign WAB from Gridsquare"); ?>
		</div>
		<div class="card-body">
			<div class="d-flex align-items-center flex-wrap mb-3">
				<label class="me-2" for="de"><?= __("Station Location"); ?></label>
				<select class="form-select form-select-sm w-auto me-2" id="de" name="de">
					<option value="all"><?= __("All"); ?></option>
					<?php foreach ($station_profile->result() as $station) { ?>
						<option value="<?php echo $station->station_id; ?>"<?php if ($station->station_active) { echo " selected"; } ?>>
						<?= __("Callsign: ") . " " ?>
						<span class="callsign"><?php echo strtoupper($station->station_callsign); ?></span> (<?php echo $station->station_profile_name; ?>)
						</option>
					<?php } ?>
				</select>
				<button id="startScan" class="btn btn-primary btn-sm ld-ext-right"><?= __("Scan Log"); ?><div class="ld ld-ring ld-spin"></div></button>
			</div>
			<button id="applyWab" class="btn btn-success btn-sm ld-ext-right mb-3 d-none"
				data-confirm-title="<?= __("Apply WAB squares"); ?>"
				data-confirm-msg="<?= __("Write the computed WAB square into the selected QSOs?"); ?>">
				<?= __("Apply Selected"); ?><div class="ld ld-ring ld-spin"></div>
			</button>
			<div class="applyresult"></div>
			<div class="scanresult"></div>
		</div>
	</div>

</div>
