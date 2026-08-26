<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
	let lang_award_info_all_bands = "<?= __("Every band (w/o SAT)"); ?>";
	let lang_wwff_map_error = "<?= __("Error loading WWFF map data"); ?>";
	let lang_wwff_no_refs = "<?= __("No WWFF references worked for these filters."); ?>";
	let lang_wwff_dir_empty = "<?= __("WWFF directory not loaded - coordinates missing. Run the update_wwff cron job."); ?>";
	let lang_wwff_without_coordinates = "<?= __("Not shown (No coordinates)"); ?>";
</script>
<style>
	#wwffmap {
		height: calc(100vh - 300px) !important;
		max-height: 900px !important;
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
<div class="d-flex justify-content-between align-items-center mb-1">
						<h5><i class="fas fa-filter me-1"></i> <?= __("Filters"); ?></h5>
						<span><?= __("Press ''Apply'' to update the table"); ?></span>
					</div>
				<div class="filter-section">
					<div class="row mb-3">
						<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-calendar-days"></i><?= __("Date Range"); ?></div>
						<div class="d-flex flex-wrap gap-1 mb-3">
							<button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="applyPreset('today')"><?= __("Today") ?></button>
							<button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="applyPreset('yesterday')"><?= __("Yesterday") ?></button>
							<button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="applyPreset('last7days')"><?= __("Last 7 Days") ?></button>
							<button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="applyPreset('last30days')"><?= __("Last 30 Days") ?></button>
							<button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="applyPreset('thismonth')"><?= __("This Month") ?></button>
							<button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="applyPreset('lastmonth')"><?= __("Last Month") ?></button>
							<button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="applyPreset('thisyear')"><?= __("This Year") ?></button>
							<button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="applyPreset('lastyear')"><?= __("Last Year") ?></button>
							<button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0" onclick="resetDates()"><i class="fas fa-times"></i> <?= __("Clear") ?></button>
						</div>
					</div>

					<div class="mb-3 row">
					<div class="col-sm-6 mb-2">
						<label class="form-label mb-1" for="dateFrom"><?= __("Date from"); ?></label>
						<input name="dateFrom" id="dateFrom" type="date" class="form-control form-control-sm">
					</div>
					<div class="col-sm-6">
						<label class="form-label mb-1" for="dateTo"><?= __("Date to"); ?></label>
						<input name="dateTo" id="dateTo" type="date" class="form-control form-control-sm">
					</div>
				</div>

				</div>
				<div class="filter-section">
					<div class="mb-3 row">
						<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-circle-check"></i><?= __("Status"); ?></div>
						<div class="col-md-10">
							<div class="form-check-inline">
								<input class="btn-check" type="checkbox" id="worked" checked>
								<label class="btn btn-outline-primary btn-sm" for="worked"><?= __("Show worked"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="btn-check" type="checkbox" id="confirmed" checked>
								<label class="btn btn-outline-primary btn-sm" for="confirmed"><?= __("Show confirmed"); ?></label>
							</div>
						</div>
					</div>

				</div>
				<div class="filter-section">
					<div class="mb-3 row">
						<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-envelope-open-text"></i><?= __("Confirmation"); ?></div>
						<div class="col-md-10">
							<div class="form-check-inline">
								<input class="btn-check" type="checkbox" id="qsl" checked>
								<label class="btn btn-outline-primary btn-sm" for="qsl"><?= __("QSL"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="btn-check" type="checkbox" id="lotw" checked>
								<label class="btn btn-outline-primary btn-sm" for="lotw"><?= __("LoTW"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="btn-check" type="checkbox" id="eqsl">
								<label class="btn btn-outline-primary btn-sm" for="eqsl"><?= __("eQSL"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="btn-check" type="checkbox" id="qrz">
								<label class="btn btn-outline-primary btn-sm" for="qrz"><?= __("QRZ.com"); ?></label>
							</div>
							<div class="form-check-inline">
								<input class="btn-check" type="checkbox" id="clublog">
								<label class="btn btn-outline-primary btn-sm" for="clublog"><?= __("Clublog"); ?></label>
							</div>
						</div>
					</div>

				</div>
				<div class="filter-section">
					<div class="mb-3 row">
					<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-tower-broadcast"></i><?= __("Band & Mode"); ?></div>
					<div class="col-sm-6 mb-2">
						<label class="form-label mb-1" for="band"><?= __("Band"); ?></label>
						<select id="band" class="form-select form-select-sm">
								<option value="All" selected><?= __("Every band (w/o SAT)"); ?></option>
								<?php if (!empty($worked_bands)) { foreach ($worked_bands as $wb) {
									echo '<option value="' . $wb . '">' . $wb . '</option>' . "\n";
								} } ?>
							</select>
					</div>
					<div class="col-sm-6">
						<label class="form-label mb-1" for="mode"><?= __("Mode"); ?></label>
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
					<div class="d-grid gap-2 mt-3">
						<button type="button" onclick="applyWwffFilters();" class="btn btn-primary"><i class="fas fa-check me-1"></i> <?= __("Apply") ?></button>
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
					if (count($wwff_all) > 0) {
						foreach ($wwff_all as $row) {
				?>
				<tr>
					<td style="text-align: center"><a target="_blank" href="https://www.cqgma.org/zinfo.php?ref=<?php echo html_escape($row->COL_WWFF_REF); ?>"><?php echo html_escape($row->COL_WWFF_REF); ?></a></td>
					<td style="text-align: center"><?php $timestamp = strtotime($row->COL_TIME_ON); echo date($custom_date_format, $timestamp); ?></td>
					<td style="text-align: center"><?php $timestamp = strtotime($row->COL_TIME_ON); echo date('H:i', $timestamp); ?></td>
					<td style="text-align: center"><a class="callsign" href="javascript:displayQso(<?php echo (int) $row->COL_PRIMARY_KEY; ?>)"><?php echo html_escape($row->COL_CALL); ?></a></td>
					<td style="text-align: center"><?php if($row->COL_SAT_NAME != null) { echo html_escape($row->COL_SAT_NAME); } else { echo html_escape($row->COL_BAND); } ?></td>
					<td style="text-align: center"><?php echo html_escape($row->COL_RST_SENT); ?></td>
					<td style="text-align: center"><?php echo html_escape($row->COL_RST_RCVD); ?></td>
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
