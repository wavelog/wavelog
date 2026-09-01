<script type="text/javascript">
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');

	let lang_gen_advanced_logbook_info = '<?= __("INFO"); ?>';
	let lang_gen_advanced_logbook_select_at_least_one_row = '<?= __("You need to select a least 1 row!"); ?>';
	let lang_gen_advanced_logbook_show_more = '<?= __("Show more"); ?>';
	let lang_gen_advanced_logbook_show_less = '<?= __("Show less"); ?>';
	let lang_gen_advanced_logbook_confirmedLabel = '<?= __("Gridsquares for"); ?>';
	let lang_gen_advanced_logbook_workedLabel = '<?= __("Non DXCC matching gridsquare"); ?>';
</script>

<div class="container px-3 px-lg-4 mt-3 mb-3">

	<div class="d-flex align-items-center flex-wrap">
		<h2><?php echo $page_title; ?></h2>
		<a href="https://docs.wavelog.org/user-guide/logbook/advanced-logbook/#database-tools-dbtools" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info ld-ext-right ms-3">
			<?= __("Wiki Help") ?></a>
	</div>

	<p class="alert alert-danger mb-3"><?= __("Warning. This tool can be dangerous to your data, and should only be used if you know what you are doing.") ?></p>

	<div class="card">
		<div class="card-header">
			<?= __("Data Repair Tools") ?>
		</div>
		<div class="card-body">
			<div class="d-flex align-items-center flex-wrap mb-3">
				<label class="me-2" for="dbtools_station_id"><?= __("Station Location"); ?></label>
				<select class="form-select form-select-sm w-auto me-2" id="dbtools_station_id" name="station_profile">
					<option value="All"><?= __("All Station Locations"); ?></option>
					<?php foreach ($station_profile->result() as $station) { ?>
						<option value="<?php echo $station->station_id; ?>">
						<?= __("Callsign: ") . " " ?>
						<span class="callsign"><?php echo strtoupper($station->station_callsign); ?></span> (<?php echo $station->station_profile_name; ?>)
						</option>
					<?php } ?>
				</select>
			</div>

			<div class="row g-3 mb-3">
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Check all QSOs in the logbook for incorrect CQ Zones") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Use Wavelog to determine CQ Zone for all QSOs.") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkIncorrectCqZonesBtn" onclick="checkIncorrectCqZones()">
							<?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Check all QSOs in the logbook for incorrect ITU Zones") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Use Wavelog to determine ITU Zone for all QSOs.") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkIncorrectItuZonesBtn" onclick="checkIncorrectItuZones()">
							<?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Check Gridsquares") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Check gridsquares that does not match the DXCC") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkIncorrectGridsquaresBtn" onclick="checkIncorrectGridsquares()">
							<?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Fix Continent") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Update missing or incorrect continent information") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkFixContinentBtn" onclick="checkFixContinent()">
							<?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Fix State") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Update missing state/province information") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkFixStateBtn" onclick="checkFixState()">
							<?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Update Distances") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Calculate and update distance information for QSOs") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkUpdateDistancesBtn" onclick="checkUpdateDistances()">
							<?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Check all QSOs in the logbook for incorrect DXCC") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Use Wavelog to determine DXCC for all QSOs.") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkDxccBtn" onclick="checkDxcc()">
							<?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
				<?php if (($this->config->item('callbook_batch_lookup') ?? true) && $this->config->item('callbook')): ?>
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Lookup QSOs with missing grid in callbook") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Use callbook lookup to set gridsquare") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkGridsBtn" onclick="checkGrids()">
							<?= __("Lookup") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
				<?php endif; ?>
				<div class="col-md-6 col-xl-4 d-flex">
					<div class="border rounded p-2 w-100 d-flex justify-content-between align-items-center gap-2">
						<div>
							<h6 class="mb-1"><?= __("Check IOTA against DXCC") ?></h6>
							<p class="mb-0 small text-muted"><?= __("Use Wavelog to check IOTA against DXCC") ?></p>
						</div>
						<button type="button" class="btn btn-sm btn-success ld-ext-right flex-shrink-0" id="checkIotaBtn" onclick="checkIota()">
							<?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
						</button>
					</div>
				</div>
			</div>

			<div class="result"></div>
		</div>
	</div>

</div>
