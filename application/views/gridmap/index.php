<style>
	/* Force left alignment for Bootstrap Multiselect button */
	.multiselect.dropdown-toggle {
		text-align: left !important;
	}

	.dropdown-filters-responsive {
		width: 900px;
	}

	@media (max-width: 992px) {
		.dropdown-filters-responsive {
			width: 90vw;
			max-width: none;
		}
	}
</style>
<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>
<div class="container gridsquare_map_form px-3 px-lg-4 mt-3 mb-3">

    <h2><?php echo $page_title; ?></h2>

	 <div class="card">
		<div class="card-header">
			<?= __("View a map of worked / confirmed gridsquares"); ?>
		</div>
		<div class="card-body">

    <?php if ($visitor == false) { ?>
        <form class="form">
            <div class="text-center">
                <div class="dropdown" data-bs-auto-close="outside">
                    <button class="btn btn-sm btn-primary dropdown-toggle me-1 mb-1" type = "button" id = "filterDropdown" data-bs-toggle = "dropdown" aria-expanded = "false"><?= __("Filters") ?></button>
                    <button id="plot" type="button" name="plot" class="btn btn-sm btn-primary me-1 mb-1 ld-ext-right ld-ext-right-plot" onclick="gridPlot(this.form,<?php echo $visitor == true ? 'true' : 'false'; ?>)"><?= __("Plot"); ?><div class="ld ld-ring ld-spin"></div></button>
                    <button id="clear" type="button" name="clear" class="btn btn-sm btn-primary me-1 mb-1 ld-ext-right ld-ext-right-clear" onclick="clearMarkers()"><?= __("Clear Markers"); ?><div class="ld ld-ring ld-spin"></div></button>

                    <!-- Dropdown Menu with Filter Content -->
                    <div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="filterDropdown" style="max-width: 900px;">
						<div class="card-body filterbody">

							<div class="mb-3 row">
								<div class="col-md-2 control-label" for="checkboxes"><?= __("Date Presets"); ?></div>
								<div class="col-sm-10">
									<div class="d-flex flex-wrap gap-1">
										<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('today')"><?= __("Today") ?></button>
										<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('yesterday')"><?= __("Yesterday") ?></button>
										<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('last7days')"><?= __("Last 7 Days") ?></button>
										<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('last30days')"><?= __("Last 30 Days") ?></button>
										<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('thismonth')"><?= __("This Month") ?></button>
										<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('lastmonth')"><?= __("Last Month") ?></button>
										<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="applyPreset('thisyear')"><?= __("This Year") ?></button>
										<button type="button" class="btn btn-danger btn-sm flex-shrink-0" onclick="resetDates()"><i class="fas fa-times"></i> <?= __("Clear") ?></button>
									</div>
								</div>
							</div>

							<div class="mb-2 row align-items-center">
								<div class="col-md-2 control-label" for="checkboxes"><?= __("Date from"); ?></div>
									<div class="col-sm-3">
										<input name="dateFrom" id="dateFrom" type="date" class="form-select form-select-sm" <?php if ($this->input->post('dateFrom')) echo 'value="' . $this->input->post('dateFrom') . '"'; ?>>
									</div>
									<div class="col-md-2 control-label" for="checkboxes"><?= __("Date to"); ?></div>
									<div class="col-sm-3">
										<input name="dateTo" id="dateTo" type="date" class="form-select form-select-sm" <?php if ($this->input->post('dateTo')) echo 'value="' . $this->input->post('dateTo') . '"'; ?>>
									</div>
								</div>
								<!-- Row 1 -->
								<div class="row mb-2">
									<label class="form-label col-md-2" for="call"><?= __("Callsign"); ?></label>
									<div class="col-sm-3">
										<input onclick="this.select()" type="text" name="call" id="call" class="form-control form-control-sm uppercase" value="*" placeholder="<?= __("Empty"); ?>">
									</div>
								</div>
								<!-- Row 2 -->
								<div class="row mb-2">
									<label class="col-md-2" for="band"><?= __("Band"); ?></label>
									<div class="col-sm-3">
										<select class="form-select form-select-sm" id="band">
											<option value="All"><?= __("All") ?></option>
											<?php foreach ($bands as $band) {
												echo '<option value="' . $band . '"';
												if ($user_default_band == $band) {
													echo ' selected="selected"';
												}
												echo '>' . $band . '</option>' . "\n";
											} ?>
										</select>
									</div>
									<label class="col-md-2" for="propagation"><?= __("Propagation"); ?></label>
									<div class="col-sm-3">
										<select class="form-select form-select-sm w-100" id="propagation" <?php if ($user_default_band == 'SAT') {echo 'disabled';} ?>>
											<option value=""><?= __("All"); ?></option>
											<option value="None"><?= __("None/Empty"); ?></option>
											<option value="NoSAT"><?= __("All except SAT") ?></option>
											<?php foreach ($adif_propmodes as $mode => $desc) {
												echo "<option value=\"$mode\"" . (($user_default_band == $mode) ? "selected=\"selected\"" : "").">".htmlspecialchars_decode($desc)."</option>\n";
											} ?>
										</select>
									</div>
								</div>
								<div class="row mb-2">
									<?php if (count($sats_available) != 0) { ?>
										<label class="col-md-2" id="satslabel" for="distplot_sats"
												<?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
											<?= __("Satellite"); ?>
										</label>
										<div class="col-sm-3" id="sats_div"
												<?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
											<select class="form-select form-select-sm" id="sat">
												<option value="All"><?= __("All") ?></option>
												<?php foreach ($sats_available as $sat) {
													echo '<option value="' . $sat . '">' . $sat . '</option>' . "\n";
												} ?>
											</select>
										</div>
									<?php } else { ?>
										<input id="sats" type="hidden" value="All">
									<?php } ?>
									<label class="col-md-2" id="orbitslabel" for="orbits"
										<?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?= __("Orbit"); ?>
									</label>
									<div class="col-sm-3" id="orbits_div"
										<?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
										<select class="form-select form-select-sm" id="orbits">
											<option value="All"><?= __("All") ?></option>
											<?php
											foreach ($orbits as $orbit) {
												echo '<option value="' . $orbit . '">' . strtoupper($orbit) . '</option>' . "\n";
											}
											?>
										</select>
									</div>
								</div>
								<!-- Row 3 -->
								<div class="row mb-2">
									<label class="col-md-2" for="mode"><?= __("Mode"); ?></label>
									<div class="col-sm-3">
										<select class="form-select form-select-sm" id="mode">
											<option value="All"><?= __("All") ?></option>
											<?php
											foreach ($modes as $mode) {
												if ($mode->submode ?? '' == '') {
													echo '<option value="' . $mode . '">' . strtoupper($mode) . '</option>' . "\n";
												}
											}
											?>
										</select>
									</div>
									<?php if(count($countries->result()) > 0) { ?>
										<label class="col-md-2" for="country">DXCC</label>
										<div class="col-sm-3">
											<select class="form-select form-select-sm" id="dxcc">
												<option value="All"><?= __("All") ?></option>
												<?php foreach($countries->result() as $country_option) {
													echo '<option value="' . $country_option->adif . '"';
													echo '>' . $country_option->prefix . ' - ' . ucwords(strtolower($country_option->name)). '</option>' . "\n";
												} ?>
											</select>
										</div>
									<?php } ?>
								</div>
								<div class="row mb-2">
									<label class="col-md-2"><?= __("Confirmation"); ?></label>
									<div class="col-sm-8">
										<div>
											<div class="form-check-inline">
												<?php echo '<input class="form-check-input" type="checkbox" name="qsl" id="qsl"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
											</div>
											<div class="form-check-inline">
												<?php echo '<input class="form-check-input" type="checkbox" name="lotw" id="lotw"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
											</div>
											<div class="form-check-inline">
												<?php echo '<input class="form-check-input" type="checkbox" name="eqsl" id="eqsl"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
											</div>
											<div class="form-check-inline">
												<?php echo '<input class="form-check-input" type="checkbox" name="qrz" id="qrz"';
												if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
													echo ' checked';
												}
												echo '>'; ?>
												<label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
											</div>
										</div>
									</div>
								</div> <!-- /row -->
                        </div> <!-- /card-body.filterbody -->
                    </div> <!-- /dropdown-menu -->
                </div> <!-- /dropdown -->
            </div> <!-- /mb-4.text-center -->
		</div>
        </form>
    <?php } ?>

    <?php if ($this->session->flashdata('message')) { ?>
        <div class="alert-message error">
            <p><?php echo $this->session->flashdata('message'); ?></p>
        </div>
    <?php } ?>

	<div id="gridmapcontainer">
		<div id="gridsquare_map" class="map-leaflet" style="width: 100%;"></div>
	</div>

	<div class="coordinates" style="position: static;">
    <div class="cohidden coord-pair"><span><?= __("Latitude") ?>:&nbsp;</span><span class="text-success fw-bold" id="latDeg"></span></div>
    <div class="cohidden coord-pair"><span><?= __("Longitude") ?>:&nbsp;</span><span class="text-success fw-bold" id="lngDeg"></span></div>
    <div class="cohidden coord-pair"><span><?= __("Gridsquare") ?>:&nbsp;</span><span class="text-success fw-bold" id="locator"></span></div>
    <div class="cohidden coord-pair"><span><?= __("Distance") ?>:&nbsp;</span><span class="text-success fw-bold" id="distance"></span></div>
    <div class="cohidden coord-pair"><span><?= __("Bearing") ?>:&nbsp;</span><span class="text-success fw-bold" id="bearing"></span></div>
	<div class="cohidden coord-pair"><span><?= __("CQ Zone") ?>:&nbsp;</span><span class="text-success fw-bold" id="cqzonedisplay"></span></div>
	<div class="cohidden coord-pair"><span><?= __("ITU Zone") ?>:&nbsp;</span><span class="text-success fw-bold" id="ituzonedisplay"></span></div>
	</div>

<script>
    var gridsquaremap = true;
    var type = "worked";
    <?php if ($visitor == true) { ?>
        var visitor = true;
    <?php } else { ?>
        var visitor = false;
    <?php } ?>
    <?php
    echo "var jslayer = \"" . $layer . "\";\n";
    echo "var jsattribution ='" . $attribution . "';";
    if ($visitor == false) {
        echo "var homegrid = \"" . strtoupper($homegrid[0]) . "\";\n";
    }

    echo "var gridsquares_gridsquares = \"" . $gridsquares_gridsquares . "\";\n";
    echo "var gridsquares_gridsquares_confirmed = \"" . $gridsquares_gridsquares_confirmed . "\";\n";
    echo "var gridsquares_gridsquares_not_confirmed = \"" . $gridsquares_gridsquares_not_confirmed . "\";\n";
    echo "var gridsquares_gridsquares_total_worked = \"" . $gridsquares_gridsquares_total_worked . "\";\n";
    echo "var gridsquares_fields = \"" . $gridsquares_fields . "\";\n";
    echo "var gridsquares_fields_confirmed = \"" . $gridsquares_fields_confirmed . "\";\n";
    echo "var gridsquares_fields_not_confirmed = \"" . $gridsquares_fields_not_confirmed . "\";\n";
    echo "var gridsquares_fields_total_worked = \"" . $gridsquares_fields_total_worked . "\";\n";
    ?>
</script>
		</div> <!-- /card-body -->
	</div> <!-- /card -->
</div> <!-- /container -->

