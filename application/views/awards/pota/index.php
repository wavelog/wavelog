<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
	let lang_award_info_all_bands = "<?= __("Every band (w/o SAT)"); ?>";
	let lang_pota_map_error = "<?= __("Error loading POTA map data"); ?>";
	let lang_pota_no_refs = "<?= __("No POTA references worked for these filters."); ?>";
	let lang_pota_dir_empty = "<?= __("POTA directory not loaded - coordinates missing. Run the update_pota cron job."); ?>";
	let lang_pota_without_coordinates = "<?= __("Not shown (No coordinates)"); ?>";
</script>
<style>
	#potamap {
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
		let lang_award_info_ln1 = "<?= __("POTA Awards"); ?>";
		let lang_award_info_ln2 = "<?= __("Parks on the Air® (POTA) started in early 2017 when the ARRL's National Parks on the Air special event ended. A group of volunteers wanted to continue the fun beyond the one-year event, and thus, POTA was born."); ?>";
		let lang_award_info_ln3 = "<?= __("POTA works similarly to SOTA, with Activators and Hunters. For the awards, there are several categories based on the number of parks, geographic areas, and more."); ?>";
		let lang_award_info_ln4 = "<?= sprintf(_pgettext("uses 'the website'", "For more information about the available awards and categories, please visit the %s."), "<a href='https://docs.pota.app/docs/awards.html' target='_blank'>Parks on the Air® website</a>"); ?>";
		let lang_award_info_ln5 = "<?= __("Fields taken for this Award: POTA_REF (must contain Park-Reference)"); ?>";
		</script>
		<h2><?php echo $page_title; ?></h2>
		<button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
	</div>
	<!-- End of Award Info Box -->

<div class="card">
	<div class="card-header">
		<ul class="nav nav-tabs card-header-tabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" data-bs-toggle="tab" href="#potatabletab" role="tab" aria-selected="true"><i class="fas fa-table"></i> <?= __("Table"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="pota-map-tab" onclick="load_pota_map();" data-bs-toggle="tab" href="#potamaptab" role="tab" aria-selected="false"><i class="fas fa-map"></i> <?= __("Map"); ?></a>
			</li>
		</ul>
	</div>
	<div class="card-body">
		<div class="mb-3 text-center">
			<div class="dropdown" data-bs-auto-close="outside">
				<button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="potaFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
				<button type="button" onclick="applyPotaFilters();" class="btn btn-sm btn-primary"><?= __("Apply Filters") ?></button>

			<div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="potaFilterDropdown">
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

		<div class="tab-content" id="potaTabContent">
			<div class="tab-pane fade show active" id="potatabletab" role="tabpanel">
				<?php
				if ($this->session->userdata('user_date_format')) {
					$custom_date_format = $this->session->userdata('user_date_format');
				} else {
					$custom_date_format = $this->config->item('qso_date_format');
				}
				?>
				<?php if ($pota_all) { ?>
				<table style="width: 100%" id="potatable" class="potatable table table-sm table-striped table-hover">
				<thead>
				<tr>
					<th style="text-align: center"><?= __("POTA Reference(s)") ?></th>
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
					if ($pota_all->num_rows() > 0) {
						foreach ($pota_all->result() as $row) {
							$references = explode(',', $row->COL_POTA_REF);
								foreach ($references as $reference) {
				?>
				<tr>
					<td style="text-align: center"><a target="_blank" href="https://pota.app/#/park/<?php echo $reference; ?>"><?php echo $reference; ?></a></td>
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
					}
				?>
				</tbody>
				</table>
				<?php } else { ?>
					<div class="alert alert-danger" role="alert"><?= __("Nothing found!") ?></div>
				<?php } ?>
			</div>

			<div class="tab-pane fade" id="potamaptab" role="tabpanel">
				<div id="potamap_status" class="mb-2"></div>
				<div id="potamap" class="map-leaflet"></div>
			</div>
		</div>
	</div>
</div>
</div>
