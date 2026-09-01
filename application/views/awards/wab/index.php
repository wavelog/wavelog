<style>
	/* Force left alignment for Bootstrap Multiselect button */
	.multiselect.dropdown-toggle {
		text-align: left !important;
	}
</style>
<script>
	let tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
	let attributionInfo='<?php echo $this->optionslib->get_option('option_map_tile_server_copyright'); ?>';
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
	var homegrid = ''; // onMapMove skips distance/bearing when empty

	var lang_awards_info_button = "<?= __("Award Info"); ?>";
	var lang_award_info_ln1 = "<?= __("WAB - Worked All Britain Award"); ?>";
	var lang_award_info_ln2 = "<?= __("WAB, Worked All Britain squares in Amateur Radio, encourages licensed ham radio operators to work all the squares in Great Britain."); ?>";
	var lang_award_info_ln3 = "<?= __("May be claimed for having contacted an amateur station located in the required amount of squares, described on the page linked below."); ?>";
	var lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://wab.intermip.net/default.php' target='_blank'>https://wab.intermip.net/default.php</a>"); ?>";
	var lang_award_info_ln5 = "<?= __("Fields taken for this Award: SIG (Has to be 'WAB') and SIG_INFO (should contain valid WAB-Grid)"); ?>";
	var lang_wab_total_squares = "<?= __("Total squares"); ?>";
	var lang_wab_total_worked = "<?= __("Total worked"); ?>";
	var lang_wab_squares_by_dxcc = "<?= __("Squares by DXCC"); ?>";
	var lang_wab_goto_square = "<?= __("Go to square"); ?>";
	var lang_wab_square_not_found = "<?= __("WAB square not found"); ?>";
</script>
<div class="container wab_map_form px-3 px-lg-4 mt-3 mb-3">

	<div id="awardInfoButton">
		<h2><?php echo $page_title; ?></h2>
		<button type="button" class="btn btn-sm btn-primary" id="displayAwardInfo"><?= __("Award Info"); ?></button>
	</div>

	<div class="card">
		<div class="card-header">
			<?= __("View a map of worked / confirmed WAB squares"); ?>
		</div>
		<div class="card-body">

			<form class="form" onsubmit="return false;">
				<div class="text-center">
					<div class="dropdown" data-bs-auto-close="outside">
						<button class="btn btn-sm btn-primary dropdown-toggle me-1 mb-1" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
						<div class="input-group input-group-sm d-none mb-1" style="width: 190px;" id="squaresearch">
							<input type="text" class="form-control" id="squareinput" placeholder="<?= __("WAB square"); ?>" aria-label="<?= __("WAB square"); ?>">
							<button class="btn btn-primary" type="button" id="squaregoto" title="<?= __("Go to square"); ?>" aria-label="<?= __("Go to square"); ?>"><i class="fas fa-search"></i></button>
							<button class="btn btn-outline-secondary" type="button" id="squareclear" title="<?= __("Clear"); ?>" aria-label="<?= __("Clear"); ?>"><i class="fas fa-times"></i></button>
						</div>

						<!-- Dropdown Menu with Filter Content -->
						<div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="filterDropdown">
							<div class="card-body filterbody">
								<div class="d-flex justify-content-between align-items-center mb-1">
									<h5><i class="fas fa-filter me-1"></i> <?= __("Filters"); ?></h5>
									<span><?= __("Press 'Apply' to update the table"); ?></span>
								</div>

								<div class="filter-section">
									<div class="mb-3 row">
										<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-tower-broadcast"></i><?= __("Band & Mode"); ?></div>
										<div class="col-sm-6">
											<label class="form-label mb-1" for="band"><?= __("Band"); ?></label>
											<select class="form-select form-select-sm" id="band">
												<option value="All"><?= __("All")?></option>
												<?php foreach($bands as $band) {
													echo '<option value="'.$band.'">'.$band.'</option>'."\n";
												} ?>
											</select>
										</div>
										<div class="col-sm-6">
											<label class="form-label mb-1" for="mode"><?= __("Mode"); ?></label>
											<select class="form-select form-select-sm" id="mode">
												<option value="All"><?= __("All")?></option>
												<?php
												foreach($modes as $mode){
													if ($mode->submode ?? '' == '') {
														echo '<option value="' . $mode . '">' . strtoupper($mode) . '</option>'."\n";
													}
												}
												?>
											</select>
										</div>
									</div>
									<div class="mb-3 row">
										<?php if (count($sats_available) != 0) { ?>
											<div class="col-sm-6">
												<label class="form-label mb-1" id="satslabel" for="sats" style="display: none;"><?= __("Satellite"); ?></label>
												<select class="form-select form-select-sm" id="sats" style="display: none;">
													<option value="All"><?= __("All")?></option>
													<?php foreach($sats_available as $sat) {
														echo '<option value="' . html_escape($sat) . '"' . '>' . html_escape($sat) . '</option>'."\n";
													} ?>
												</select>
											</div>
										<?php } else { ?>
											<input id="sats" type="hidden" value="All"></input>
										<?php } ?>
										<div class="col-sm-6">
											<label class="form-label mb-1" id="orbitslabel" for="orbits" style="display: none;"><?= __("Orbit"); ?></label>
											<select class="form-select form-select-sm" id="orbits" style="display: none;">
												<option value="All"><?= __("All")?></option>
												<?php
												foreach($orbits as $orbit){
													echo '<option value="' . $orbit . '">' . strtoupper($orbit) . '</option>'."\n";
												}
												?>
											</select>
										</div>
									</div>
								</div>

								<div class="filter-section">
									<div class="mb-3 row">
										<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-envelope-open-text"></i><?= __("Confirmation"); ?></div>
										<div class="col-md-10">
											<div class="form-check-inline">
												<?php echo '<input class="btn-check" type="checkbox" name="qsl" id="qsl"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="btn btn-outline-primary btn-sm" for="qsl"><?= __("QSL"); ?></label>
											</div>
											<div class="form-check-inline">
												<?php echo '<input class="btn-check" type="checkbox" name="lotw" id="lotw"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="btn btn-outline-primary btn-sm" for="lotw"><?= __("LoTW"); ?></label>
											</div>
											<div class="form-check-inline">
												<?php echo '<input class="btn-check" type="checkbox" name="eqsl" id="eqsl"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="btn btn-outline-primary btn-sm" for="eqsl"><?= __("eQSL"); ?></label>
											</div>
											<div class="form-check-inline">
												<?php echo '<input class="btn-check" type="checkbox" name="qrz" id="qrz"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="btn btn-outline-primary btn-sm" for="qrz"><?= __("QRZ.com"); ?></label>
											</div>
											<div class="form-check-inline">
												<?php echo '<input class="btn-check" type="checkbox" name="clublog" id="clublog"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'C') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="btn btn-outline-primary btn-sm" for="clublog"><?= __("Clublog"); ?></label>
											</div>
										</div>
									</div>
								</div>

								<div class="d-grid gap-2 mt-3">
									<button type="button" class="btn btn-primary" id="wabApply"><i class="fas fa-check me-1"></i> <?= __("Apply"); ?></button>
								</div>
							</div> <!-- /card-body.filterbody -->
						</div> <!-- /dropdown-menu -->
					</div> <!-- /dropdown -->
				</div> <!-- /text-center -->

				<ul class="nav nav-tabs mt-2" id="wabTabs" role="tablist">
					<li class="nav-item" role="presentation">
						<button class="nav-link active ld-ext-right ld-ext-right-plot" id="wab-map-tab" data-bs-toggle="tab" data-bs-target="#wab-map-pane" type="button" role="tab" aria-controls="wab-map-pane" aria-selected="true"><?= __("Map"); ?><div class="ld ld-ring ld-spin"></div></button>
					</li>
					<li class="nav-item" role="presentation">
						<button class="nav-link ld-ext-right ld-ext-right-list" id="wab-list-tab" data-bs-toggle="tab" data-bs-target="#wab-list-pane" type="button" role="tab" aria-controls="wab-list-pane" aria-selected="false"><?= __("List"); ?><div class="ld ld-ring ld-spin"></div></button>
					</li>
				</ul>
			</form>

		</div> <!-- /card-body -->

		<div class="tab-content">
			<div class="tab-pane fade show active" id="wab-map-pane" role="tabpanel" aria-labelledby="wab-map-tab">
				<div id="mapcontainer">
					<div id="wabmap" class="map-leaflet" style="width: 100%;"></div>
				</div>

				<div class="card-body">
					<div class="coordinates" style="position: static;">
						<div class="cohidden coord-pair"><span><?= __("Latitude") ?>:&nbsp;</span><span class="text-success fw-bold" id="latDeg"></span></div>
						<div class="cohidden coord-pair"><span><?= __("Longitude") ?>:&nbsp;</span><span class="text-success fw-bold" id="lngDeg"></span></div>
						<div class="cohidden coord-pair"><span><?= __("Gridsquare") ?>:&nbsp;</span><span class="text-success fw-bold" id="locator"></span></div>
					</div>
				</div>
			</div>

			<div class="tab-pane fade" id="wab-list-pane" role="tabpanel" aria-labelledby="wab-list-tab"></div>
		</div> <!-- /tab-content -->

	</div> <!-- /card -->
</div> <!-- /container -->
