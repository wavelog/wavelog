<div class="container px-3 px-lg-4 mt-3 mb-3">

	<h2><?php echo $page_title; ?></h2>

	<div class="card">
		<div class="card-header">
			<?= __("Callsign DXCC identification"); ?>
		</div>
		<div class="card-body">
			<div class="d-flex align-items-center flex-wrap mb-3">
				<label class="me-2" for="de"><?= __("Station Location"); ?></label>
				<select class="form-select form-select-sm w-auto me-2 callsign" id="de" name="de">
					<option value="all">All</option>
					<?php foreach ($station_profile->result() as $station) { ?>
						<option value="<?php echo $station->station_id; ?>"<?php if ($station->station_active) { echo " selected"; } ?>>
						<?= __("Callsign: ") . " " ?>
						<span class="callsign"><?php echo strtoupper($station->station_callsign); ?></span> (<?php echo $station->station_profile_name; ?>)
						</option>
					<?php } ?>
				</select>
				<button id="startDxccCheck" class="btn btn-primary btn-sm me-2 ld-ext-right"><?= __("Start DXCC Check"); ?><div class="ld ld-ring ld-spin"></div></button>
				<div class="form-check">
					<input type="checkbox" class="form-check-input" id="compareDxccClass" name="compareDxccClass">
					<label class="form-check-label" for="compareDxccClass"><?= __("Compare DXCC class and logbook model"); ?></label>
				</div>
			</div>
			<div class='result'>
			</div>
		</div>
	</div>

</div>
