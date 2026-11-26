<script type="text/javascript">
	let lang_county_check_error = <?= js_escape(__("An error occurred while processing the request.")); ?>;
	let lang_county_update_title = <?= js_escape(__("Update counties")); ?>;
	let lang_county_update_none = <?= js_escape(__("No rows selected.")); ?>;
	let lang_county_update_confirm = <?= js_escape(__("Write the geojson county into the log for %s QSO(s)? This overwrites the current state and county of those QSOs.")); ?>;
	let lang_county_update_button = <?= js_escape(__("Update")); ?>;
	let lang_county_update_failed = <?= js_escape(__("Update failed.")); ?>;
	let lang_county_updated = <?= js_escape(__("Updated the county of %s QSO(s).")); ?>;
	let lang_county_map_notfound = <?= js_escape(__("No county found for this gridsquare.")); ?>;
	let lang_county_map_error = <?= js_escape(__("Error loading map data.")); ?>;
	let lang_county_close = <?= js_escape(__("Close")); ?>;
	let lang_county_label_geojson = <?= js_escape(__("GeoJSON")); ?>;
	let lang_county_label_log = <?= js_escape(__("Log")); ?>;
	let lang_county_label_possible = <?= js_escape(__("Possible")); ?>;
</script>

<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Gridsquare County identification"); ?>
		</div>
		<div class="card-body">
			<div class="d-flex align-items-center flex-wrap mb-3">
				<label class="me-2" for="de"><?= __("Station Location"); ?></label>
				<select class="form-select form-select-sm w-auto me-2 callsign" id="de" name="de">
					<option value="all"><?= __("All"); ?></option>
					<?php foreach ($station_profile->result() as $station) { ?>
						<option value="<?php echo $station->station_id; ?>"<?php if ($station->station_active) { echo " selected"; } ?>>
						<?= __("Callsign: ") . " " ?>
						<span class="callsign"><?php echo strtoupper($station->station_callsign); ?></span> (<?php echo $station->station_profile_name; ?>)
						</option>
					<?php } ?>
				</select>
				<button id="startCountyCheck" class="btn btn-primary btn-sm ld-ext-right ms-1"><?= __("Start County Check"); ?><div class="ld ld-ring ld-spin"></div></button>
			</div>
			<div class='result'>
			</div>
		</div>
	</div>

</div>
