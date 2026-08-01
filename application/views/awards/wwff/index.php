<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
	let lang_award_info_all_bands = "<?= __("Every band (w/o SAT)"); ?>";
	let lang_wwff_map_error = "<?= __("Error loading WWFF map data"); ?>";
	let lang_wwff_no_refs = "<?= __("No WWFF references worked for these filters."); ?>";
	let lang_wwff_dir_empty = "<?= __("WWFF directory not loaded - coordinates missing. Run the update_wwff cron job."); ?>";
	let lang_wwff_without_coordinates = "<?= __("Without coordinates"); ?>";
</script>
<style>
	#wwffmap {
		height: calc(100vh - 300px) !important;
		max-height: 900px !important;
	}
	.dropdown-filters-responsive {
		width: min(850px, 90vw);
		min-width: 600px;
	}
</style>
<div class="container px-3 px-lg-4 mt-3 mb-3">
	<!-- Award Info Box -->
	<div id="awardInfoButton">
		<script>
		let lang_awards_info_button = "<?= __("Award Info"); ?>";
		let lang_award_info_ln1 = "<?= __("WWFF - World Wide Flora and Fauna Award"); ?>";
		let lang_award_info_ln2 = "<?= __("WWFF, World Wide Flora and Fauna in Amateur Radio, encourages licensed ham radio operators to leave their shacks and operate portable in Protected Flora & Fauna areas (PFF) worldwide."); ?>";
		let lang_award_info_ln3 = "<?= __("More than 26,000 Protected Flora & Fauna (PFF) areas worldwide are already registered in the WWFF Directory. Hunters and Activators can apply for colorful awards, both globally and nationally."); ?>";
		let lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://wwff.co/awards/' target='_blank'>https://wwff.co/awards/</a>"); ?>";
		let lang_award_info_ln5 = "<?= __("Fields taken for this Award: WWFF (ADIF: WWFF_REF)"); ?>";
		</script>
		<h2><?php echo $page_title; ?></h2>
		<button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
	</div>
	<!-- End of Award Info Box -->

<div class="card">
	<div class="card-header">
		<ul class="nav nav-tabs card-header-tabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" data-bs-toggle="tab" href="#wwfftabletab" role="tab" aria-selected="true"><i class="fas fa-table"></i> <?= __("Table"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="wwff-map-tab" onclick="load_wwff_map();" data-bs-toggle="tab" href="#wwffmaptab" role="tab" aria-selected="false"><i class="fas fa-map"></i> <?= __("Map"); ?></a>
			</li>
		</ul>
	</div>
	<div class="card-body">
		<div class="mb-3 text-center">
			<div class="dropdown" data-bs-auto-close="outside">
				<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="wwffFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
				<button type="button" onclick="applyWwffFilters();" class="btn btn-sm btn-primary"><?= __("Apply Filters") ?></button>

			<div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="wwffFilterDropdown">
				<div class="card-body filterbody">
					<div class="row mb-3">
						<label class="form-label"><?= __("Date Presets") . ": " ?></label>
						<div class="d-flex gap-1 flex-wrap">
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('today')"><?= __("Today") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('yesterday')"><?= __("Yesterday") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('last7days')"><?= __("Last 7 Days") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('last30days')"><?= __("Last 30 Days") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('thismonth')"><?= __("This Month") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('lastmonth')"><?= __("Last Month") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('thisyear')"><?= __("This Year") ?></button>
							<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('lastyear')"><?= __("Last Year") ?></button>
							<button type="button" class="btn btn-danger btn-sm flex-shrink-0" onclick="resetDates()"><i class="fas fa-times"></i> <?= __("Clear") ?></button>
						</div>
					</div>

					<div class="mb-3 row">
						<div class="col-md-2 control-label"><?= __("Date from"); ?></div>
						<div class="col-md-10"><input name="dateFrom" id="dateFrom" type="date" class="form-control form-control-sm w-auto border border-secondary"></div>
					</div>
					<div class="mb-3 row">
						<div class="col-md-2 control-label"><?= __("Date to"); ?></div>
						<div class="col-md-10"><input name="dateTo" id="dateTo" type="date" class="form-control form-control-sm w-auto border border-secondary"></div>
					</div>

					<div class="mb-3 row">
						<div class="col-md-2"><?= __("Worked / Confirmed"); ?></div>
						<div class="col-md-10">
							<div class="form-check-inline">
								<input class="form-check-input" type="checkbox" id="worked" checked>
								<label class="form-check-label" for="worked"><?= __("Show worked"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="form-check-input" type="checkbox" id="confirmed" checked>
								<label class="form-check-label" for="confirmed"><?= __("Show confirmed"); ?></label>
							</div>
						</div>
					</div>

					<div class="mb-3 row">
						<div class="col-md-2"><?= __("Show QSO with QSL Type"); ?></div>
						<div class="col-md-10">
							<div class="form-check-inline">
								<input class="form-check-input" type="checkbox" id="qsl" checked>
								<label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="form-check-input" type="checkbox" id="lotw" checked>
								<label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="form-check-input" type="checkbox" id="eqsl">
								<label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="form-check-input" type="checkbox" id="qrz">
								<label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="form-check-input" type="checkbox" id="clublog">
								<label class="form-check-label" for="clublog"><?= __("Clublog"); ?></label>
							</div>
						</div>
					</div>

					<div class="mb-3 row">
						<label class="col-md-2 control-label" for="band"><?= __("Band"); ?></label>
						<div class="col-md-4">
							<select id="band" class="form-select form-select-sm">
								<option value="All" selected><?= __("Every band (w/o SAT)"); ?></option>
								<?php if (!empty($worked_bands)) { foreach ($worked_bands as $wb) {
									echo '<option value="' . $wb . '">' . $wb . '</option>' . "\n";
								} } ?>
							</select>
						</div>
					</div>

					<div class="mb-3 row">
						<label class="col-md-2 control-label" for="mode"><?= __("Mode"); ?></label>
						<div class="col-md-4">
							<select id="mode" class="form-select form-select-sm">
								<option value="All" selected><?= __("All"); ?></option>
								<?php if (!empty($modes)) { foreach ($modes->result() as $mode_row) {
									if ($mode_row->submode == null) {
										echo '<option value="' . $mode_row->mode . '">' . $mode_row->mode . '</option>' . "\n";
									} else {
										echo '<option value="' . $mode_row->submode . '">' . $mode_row->submode . '</option>' . "\n";
									}
								} } ?>
							</select>
						</div>
					</div>
				</div>
			</div>
			</div>
		</div>

		<div class="tab-content" id="wwffTabContent">
			<div class="tab-pane fade show active" id="wwfftabletab" role="tabpanel">
				<?php
				if ($this->session->userdata('user_date_format')) {
					$custom_date_format = $this->session->userdata('user_date_format');
				} else {
					$custom_date_format = $this->config->item('qso_date_format');
				}
				?>
				<?php if ($wwff_all) { ?>
				<table style="width: 100%" id="wwfftable" class="wwfftable table table-sm table-striped table-hover">
				<thead>
				<tr>
					<th style="text-align: center"><?= __("WWFF Reference") ?></th>
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
					if ($wwff_all->num_rows() > 0) {
						foreach ($wwff_all->result() as $row) {
				?>
				<tr>
					<td style="text-align: center"><a target="_blank" href="https://www.cqgma.org/zinfo.php?ref=<?php echo $row->COL_WWFF_REF; ?>"><?php echo $row->COL_WWFF_REF; ?></a></td>
					<td style="text-align: center"><?php $timestamp = strtotime($row->COL_TIME_ON); echo date($custom_date_format, $timestamp); ?></td>
					<td style="text-align: center"><?php $timestamp = strtotime($row->COL_TIME_ON); echo date('H:i', $timestamp); ?></td>
					<td style="text-align: center"><a href="javascript:displayQso(<?php echo $row->COL_PRIMARY_KEY; ?>)"><?php echo $row->COL_CALL; ?></a></td>
					<td style="text-align: center"><?php if($row->COL_SAT_NAME != null) { echo $row->COL_SAT_NAME; } else { echo $row->COL_BAND; } ?></td>
					<td style="text-align: center"><?php echo $row->COL_RST_SENT; ?></td>
					<td style="text-align: center"><?php echo $row->COL_RST_RCVD; ?></td>
				</tr>
				<?php
						}
					}
				?>
				</tbody>
				</table>
				<?php } else { ?>
					<div class="alert alert-danger" role="alert"><?= __("Nothing found!") ?></div>
				<?php } ?>
			</div>

			<div class="tab-pane fade" id="wwffmaptab" role="tabpanel">
				<div id="wwffmap_status" class="mb-2"></div>
				<div id="wwffmap" class="map-leaflet"></div>
			</div>
		</div>
	</div>
</div>
</div>
