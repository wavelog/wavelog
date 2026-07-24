<script>
	// Pass supported DXCC list from PHP to JavaScript
	const supportedDxccs = <?php echo json_encode(array_keys($supported_dxccs)); ?>;
	const homegrid = "<?php echo strtoupper($homegrid[0]); ?>";
	let lang_gen_hamradio_cq_zones = '<?= _pgettext("Map Options", "CQ Zones"); ?>';
    let lang_gen_hamradio_itu_zones = '<?= _pgettext("Map Options", "ITU Zones"); ?>';
	let lang_gen_hamradio_gridsquares = '<?= _pgettext("Map Options", "Gridsquares"); ?>';
	let lang_qso_map_loading = '<?= __("Loading QSO data..."); ?>';
	let lang_qso_map_loading_all = '<?= __("Loading QSOs for all countries (this may take a moment)..."); ?>';
	let lang_qso_map_still_loading = '<?= __("Still loading... Processing large dataset, please wait..."); ?>';
	let lang_qso_map_load_failed = '<?= __("Failed to load QSO data. Please try again."); ?>';
	let lang_qso_map_error = '<?= __("Error:"); ?>';
	let lang_qso_map_error_parsing = '<?= __("Error parsing response:"); ?>';
	let lang_qso_map_outside_boundaries = '<?= __("Outside country boundaries"); ?>';
	let lang_qso_map_inside_boundaries = '<?= __("Inside country boundaries"); ?>';
	let lang_qso_map_legend = '<?= __("Legend"); ?>';
	let lang_qso_map_inside_label = '<?= __("Inside boundaries"); ?>';
	let lang_qso_map_outside_label = '<?= __("Outside boundaries"); ?>';
	let lang_qso_map_boundaries = '<?= __("Country/State boundaries"); ?>';
	let lang_qso_map_showing = '<?= __("Showing %s of %s total QSOs"); ?>';
	let lang_qso_map_total_qsos = '<?= __("Total: %s QSOs with 6+ char grids"); ?>';
	let lang_qso_map_region = '<?= __("Region"); ?>';
	let lang_qso_map_hover_region = '<?= __("Hover over a region"); ?>';
	let lang_qso_map_toggle_layers = '<?= __("Toggle layers"); ?>';
	let lang_qso_map_region_confirmed = '<?= __("Confirmed"); ?>';
	let lang_qso_map_region_worked = '<?= __("Worked"); ?>';
	let lang_qso_map_region_not_worked = '<?= __("Not worked"); ?>';
	let lang_qso_map_regions_label = '<?= __("Regions worked:"); ?>';
	let lang_qso_map_show_list = '<?= __("Show QSO list"); ?>';
	let lang_qso_map_th_call = '<?= __("Callsign"); ?>';
	let lang_qso_map_th_date = '<?= __("Date/Time"); ?>';
	let lang_qso_map_th_band = '<?= __("Band"); ?>';
	let lang_qso_map_th_mode = '<?= __("Mode"); ?>';
	let lang_qso_map_th_grid = '<?= __("Gridsquare"); ?>';
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>

<div class="container px-3 px-lg-4 mt-3 mb-3">
    <h2><?= ('GeoJSON QSO Map'); ?></h2>

    <div class="card">
        <div class="card-header">
            <?= ('Map your QSOs for a country against its region boundaries — and see which contacts fall outside them.'); ?>
        </div>
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-auto">
                    <label for="countrySelect" class="form-label"><?= __("Select Country:"); ?></label>
                    <select class="form-select" id="countrySelect" style="min-width: 200px;">
                        <option value=""><?= __("Choose a country...") ?></option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo htmlspecialchars(ucwords(strtolower(($country['dxcc_name'])), "- (/")); ?>"
                                    data-dxcc="<?php echo htmlspecialchars($country['COL_DXCC']); ?>">
                                <?php echo htmlspecialchars($country['prefix']) . ' - ' . htmlspecialchars(ucwords(strtolower(($country['dxcc_name'])), "- (/")); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label for="locationSelect" class="form-label"><?= __("Location:"); ?></label>
                    <select class="form-select" id="locationSelect" style="min-width: 200px;">
                        <option value="all">All</option>
                        <?php foreach ($station_profiles as $profile): ?>
                            <option value="<?php echo htmlspecialchars($profile->station_id); ?>"
                                <?php echo ($profile->station_id == $active_station_id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($profile->station_profile_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <label for="bandSelect" class="form-label"><?= __("Band:"); ?></label>
                    <select class="form-select" id="bandSelect" style="min-width: 120px;">
                        <option value="all"><?= __("All") ?></option>
                        <?php foreach ($bands as $band): ?>
                            <option value="<?php echo htmlspecialchars($band); ?>"><?php echo htmlspecialchars(strtoupper($band)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button id="loadMapBtn" class="btn btn-primary" disabled><?= __("Load Map") ?></button>
                </div>
                <div class="col-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="showOnlyOutside" disabled checked>
                        <label class="form-check-label" for="showOnlyOutside">
                            <?= ('Show only QSOs outside boundaries') ?>
                        </label>
                    </div>
                </div>
                <div class="col-auto d-flex align-items-center">
                    <div id="loadingSpinner" class="spinner-border text-primary d-none" role="status">
                        <span class="visually-hidden"><?= ('Loading...') ?></span>
                    </div>
                    <div id="loadingText" class="ms-2 text-muted d-none"></div>
                </div>
            </div>
		</div>
		<div id="mapContainer" style="display: none;">
			<div class="ms-3 mb-2">
				<small class="text-muted">
					<i class="fas fa-info-circle"></i>
					<?= ('Map shows QSOs with 6+ character gridsquares. The confirmed / worked / unworked regions, are only based on QSOs with gridsquares.') ?>
				</small>
			</div>
			<div id="mapgeojson"></div>
			<div class="card-body">
				<div class="coordinates" style="position: static;">
					<div class="cohidden coord-pair"><span><?= __("Latitude") ?>:&nbsp;</span><span class="text-success fw-bold" id="latDeg"></span></div>
					<div class="cohidden coord-pair"><span><?= __("Longitude") ?>:&nbsp;</span><span class="text-success fw-bold" id="lngDeg"></span></div>
					<div class="cohidden coord-pair"><span><?= __("Gridsquare") ?>:&nbsp;</span><span class="text-success fw-bold" id="locator"></span></div>
					<div class="cohidden coord-pair"><span><?= __("Distance") ?>:&nbsp;</span><span class="text-success fw-bold" id="distance"></span></div>
					<div class="cohidden coord-pair"><span><?= __("Bearing") ?>:&nbsp;</span><span class="text-success fw-bold" id="bearing"></span></div>
					<div class="cohidden coord-pair"><span><?= __("CQ Zone") ?>:&nbsp;</span><span class="text-success fw-bold" id="cqzonedisplay"></span></div>
					<div class="cohidden coord-pair"><span><?= __("ITU Zone") ?>:&nbsp;</span><span class="text-success fw-bold" id="ituzonedisplay"></span></div>
				</div>
			</div>
        </div>
    </div>
</div>

<style>
#mapgeojson {
    height: calc(100vh - 350px);
    width: 100% !important;
    min-height: 400px;
}
/* Geo information bar: a responsive grid that wraps cleanly on narrow
   screens, with reserved value widths so updating values on mousemove
   can't shift the layout. Grid (not flex-wrap) keeps columns uniform so
   wrapped rows stay aligned instead of going ragged. */
#mapContainer .coordinates {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(11rem, max-content));
    column-gap: 1.25rem;
    row-gap: 0.3rem;
}
#mapContainer .coord-pair {
    display: flex;
    align-items: baseline;
    white-space: nowrap;
}
/* Value cells reserve a fixed width so they can't resize when updated
   on mousemove. #latDeg/#lngDeg are longer (DMS format). */
#mapContainer .coord-pair > [id] {
    min-width: 6rem;
}
#mapContainer .coord-pair > #latDeg,
#mapContainer .coord-pair > #lngDeg {
    min-width: 10rem;
}
.leaflet-popup-content {
    min-width: 200px;
}
.marker-cluster {
    background-color: rgba(110, 204, 57, 0.6);
}
.leaflet-marker-qso {
    background-color: #3388ff;
    border: 2px solid #fff;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
.custom-div-icon {
    background: transparent;
    border: none;
}
.custom-div-icon i {
    color: red;
}
.legend {
    background: rgba(255, 255, 255, 0.95);
    padding: 12px;
    border-radius: 6px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.4);
    line-height: 1.6;
    border: 1px solid #ccc;
    min-width: 200px;
}
.legend h4 {
    margin: 0 0 10px 0;
    font-size: 15px;
    font-weight: bold;
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
}
.legend-item {
    display: flex;
    align-items: center;
    margin: 8px 0;
}
.legend-icon {
    display: flex;
    align-items: center;
    margin-right: 10px;
    flex-shrink: 0;
}
.legend-label {
    display: flex;
    align-items: center;
    gap: 0.25em;
    flex: 1 1 auto;
}
.legend-action {
    display: flex;
    align-items: center;
    flex: 0 0 auto;
    margin-left: 8px;
	margin-top: 8px;
    cursor: pointer;
}
#legend-region-count {
	margin-top: 8px;
	bottom: 0px !important;
}
</style>
