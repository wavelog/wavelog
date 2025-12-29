<script>
	// Pass supported DXCC list from PHP to JavaScript
	const supportedDxccs = <?php echo json_encode(array_keys($supported_dxccs)); ?>;
	const homegrid = "<?php echo strtoupper($homegrid[0]); ?>";
	let lang_gen_hamradio_cq_zones = '<?= _pgettext("Map Options", "CQ Zones"); ?>';
    let lang_gen_hamradio_itu_zones = '<?= _pgettext("Map Options", "ITU Zones"); ?>';
	let lang_gen_hamradio_gridsquares = '<?= _pgettext("Map Options", "Gridsquares"); ?>';
</script>

<div class="container">
    <h2><?= ('GeoJSON QSO Map'); ?></h2>

    <div class="row mb-3 align-items-end">
        <div class="col-auto">
            <label for="countrySelect" class="form-label"><?= __("Select Country:"); ?></label>
            <select class="form-select" id="countrySelect" style="min-width: 200px;">
                <option value=""><?= __("Choose a country...") ?></option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?php echo htmlspecialchars(ucwords(strtolower(($country['dxcc_name'])), "- (/")); ?>"
                            data-dxcc="<?php echo htmlspecialchars($country['COL_DXCC']); ?>">
                        <?php echo htmlspecialchars($country['prefix']) . ' - ' . htmlspecialchars(ucwords(strtolower(($country['dxcc_name'])), "- (/") . ' (' . $country['qso_count'] . ' ' . __("QSOs") . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label for="locationSelect" class="form-label">Location:</label>
            <select class="form-select" id="locationSelect" style="min-width: 200px;">
                <option value="all">All</option>
                <?php foreach ($station_profiles as $profile): ?>
                    <option value="<?php echo htmlspecialchars($profile->station_id); ?>">
                        <?php echo htmlspecialchars($profile->station_profile_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <button id="loadMapBtn" class="btn btn-primary" disabled>Load Map</button>
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

    <div id="mapContainer" class="mt-3" style="display: none;">
        <div id="mapgeojson" style="border: 1px solid #ccc;"></div>
		<div class="coordinates d-flex">
			<div class="cohidden"><?= __("Latitude") ?>:&nbsp;</div>
			<div class="cohidden col-auto text-success fw-bold" id="latDeg"></div>
			<div class="cohidden"><?= __("Longitude") ?>:&nbsp;</div>
			<div class="cohidden col-auto text-success fw-bold" id="lngDeg"></div>
			<div class="cohidden"><?= __("Gridsquare") ?>:&nbsp;</div>
			<div class="cohidden col-auto text-success fw-bold" id="locator"></div>
			<div class="cohidden"><?= __("Distance") ?>:&nbsp;</div>
			<div class="cohidden col-auto text-success fw-bold" id="distance"></div>
			<div class="cohidden"><?= __("Bearing") ?>:&nbsp;</div>
			<div class="cohidden col-auto text-success fw-bold" id="bearing"></div>
			<div class="cohidden"><?= __("CQ Zone") ?>:&nbsp;</div>
			<div class="cohidden col-auto text-success fw-bold" id="cqzonedisplay"></div>
			<div class="cohidden"><?= __("ITU Zone") ?>:&nbsp;</div>
			<div class="cohidden col-auto text-success fw-bold" id="ituzonedisplay"></div>
		</div>
		<div class="mt-2">
			<small class="text-muted">
				<i class="fas fa-info-circle"></i>
				<?= ('Map shows QSOs with 6+ character gridsquares.') ?>
			</small>
		</div>
    </div>



    <div id="noDataMessage" class="alert alert-warning mt-3" style="display: none;">
        <i class="fas fa-exclamation-triangle"></i>
        <?= ('No QSOs with 6+ character grids found for the selected country.') ?>
    </div>
</div>

<style>
#mapgeojson {
    border-radius: 4px;
    height: calc(100vh - 250px);
    width: 100% !important;
    min-height: 400px;
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
    margin-right: 10px;
    flex-shrink: 0;
}
</style>
