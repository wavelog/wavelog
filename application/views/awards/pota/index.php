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

<?php
// Renders the POTA award progress block for one track (Hunter or Activator):
// a headline count, the current tier reached, a progress bar to the next tier
// and a collapsible list of every tier with the achieved ones highlighted.
$render_pota_progress = function ($count, $idPrefix, $verbText) use ($pota_award_tiers) {
	$flat = array_merge($pota_award_tiers['standard'], $pota_award_tiers['advanced']);
	$currentTier = null;
	$nextTier = null;
	foreach ($flat as $t) {
		if ($count >= $t['threshold']) {
			$currentTier = $t;
		} elseif ($nextTier === null) {
			$nextTier = $t;
		}
	}

	$pct = 100;
	$remaining = 0;
	if ($nextTier !== null) {
		$prevThreshold = $currentTier ? $currentTier['threshold'] : 0;
		$remaining = max(0, $nextTier['threshold'] - (int) $count);
		$span = $nextTier['threshold'] - $prevThreshold;
		$pct = $span > 0 ? max(0, min(100, round(((int) $count - $prevThreshold) / $span * 100))) : 100;
	}
	?>
	<div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
		<div>
			<span class="display-6 fw-bold"><?php echo (int) $count; ?></span>
			<span class="text-muted"><?php echo $verbText; ?></span>
		</div>
		<div>
			<?php if ($currentTier !== null): ?>
				<span class="badge bg-success fs-6"><?php echo htmlspecialchars($currentTier['name']); ?></span>
			<?php else: ?>
				<span class="badge bg-secondary fs-6"><?php echo __("No award yet"); ?></span>
			<?php endif; ?>
		</div>
	</div>

	<?php if ($nextTier !== null): ?>
		<div class="mb-1 d-flex justify-content-between">
			<span><?php echo sprintf(__("Progress to %s"), '<strong>' . htmlspecialchars($nextTier['name']) . '</strong>'); ?></span>
			<span><?php echo (int) $count; ?> / <?php echo (int) $nextTier['threshold']; ?></span>
		</div>
		<div class="progress" style="height: 10px;">
			<div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $pct; ?>%" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
		</div>
		<small class="text-muted"><?php echo sprintf(__("%d more different park(s) for the next award"), $remaining); ?></small>
	<?php else: ?>
		<div class="alert alert-success mb-0"><?php echo __("You have reached the highest POTA award tier!"); ?></div>
	<?php endif; ?>

	<div class="text-center mt-3">
		<button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $idPrefix; ?>_ladder" aria-expanded="false" aria-controls="<?php echo $idPrefix; ?>_ladder">
			<i class="fas fa-list"></i> <?php echo __("Show all tiers"); ?>
		</button>
	</div>
	<div class="collapse mt-2" id="<?php echo $idPrefix; ?>_ladder">
		<?php foreach (['standard' => __("Standard Awards"), 'advanced' => __("Advanced Awards")] as $key => $label): ?>
			<h6 class="mt-3 mb-2 text-muted"><?php echo $label; ?></h6>
			<div class="row g-1">
				<?php foreach ($pota_award_tiers[$key] as $t):
					$achieved = $count >= $t['threshold'];
				?>
					<div class="col-12 col-md-6 col-xl-4">
						<div class="d-flex justify-content-between align-items-center px-2 py-1 rounded <?php echo $achieved ? 'border border-success' : 'border border-secondary-subtle text-muted'; ?>">
							<span class="text-truncate">
								<?php if ($achieved): ?><i class="fas fa-check-circle text-success me-1"></i><?php endif; ?>
								<?php echo htmlspecialchars($t['name']); ?>
							</span>
							<small class="flex-shrink-0 ms-2"><?php echo (int) $t['threshold']; ?></small>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
};
?>

<div class="card mb-3">
	<div class="card-header">
		<?= __("POTA Hunter progress"); ?>
	</div>
	<div class="card-body">
		<?php $render_pota_progress($pota_hunted_count, 'pota_hunter', __("different parks hunted")); ?>
	</div>
</div>

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
						<div class="d-flex justify-content-between align-items-center mb-1">
							<h5><i class="fas fa-filter me-1"></i> <?= __("Filters"); ?></h5>
							<span><?= __("Press 'Apply' to update the table"); ?></span>
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
						<button type="button" onclick="applyPotaFilters();" class="btn btn-primary"><i class="fas fa-check me-1"></i> <?= __("Apply") ?></button>
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
					if (count($pota_all) > 0) {
						foreach ($pota_all as $row) {
							$references = explode(',', $row->COL_POTA_REF);
								foreach ($references as $reference) {
				?>
				<tr>
					<td style="text-align: center"><a target="_blank" href="https://pota.app/#/park/<?php echo html_escape($reference); ?>"><?php echo html_escape($reference); ?></a></td>
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