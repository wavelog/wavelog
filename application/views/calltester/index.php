<div class="container">
	<br />
    <h5><?= __("Callsign DXCC identification"); ?></h5>
	<div class="d-flex align-items-center mb-3">
		<label class="me-2" for="de"><?= __("Station Location"); ?></label>
		<select class="form-select form-select-sm w-auto me-2" id="de" name="de">
			<option value="all">All</option>
			<?php foreach ($station_profile->result() as $station) { ?>
				<option value="<?php echo $station->station_id; ?>">
					<?= __("Callsign: ") . " " ?>
					<?php echo str_replace("0", "&Oslash;", strtoupper($station->station_callsign)); ?> (<?php echo $station->station_profile_name; ?>)
				</option>
			<?php } ?>
		</select>
		<button id="startDxccCheck" class="btn btn-primary btn-sm"><?= __("Start DXCC Check"); ?></button>
	</div>
	<div class='result'>
	</div>
</div>