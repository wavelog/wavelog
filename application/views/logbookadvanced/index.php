<script type="text/javascript">
    let user_id = <?php echo $this->session->userdata('user_id'); ?>;

    /*
     * Custom user settings
     */
    let custom_date_format = "<?php echo $custom_date_format ?>";
    let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');

    let lang_gen_hamradio_latitude = '<?= __("Latitude"); ?>';
    let lang_gen_hamradio_longitude = '<?= __("Longitude"); ?>';
    let lang_gen_hamradio_gridsquare = '<?= __("Gridsquare"); ?>';
    let lang_gen_hamradio_gridsquares = '<?= _pgettext("Map Options", "Gridsquares"); ?>';
    let lang_gen_hamradio_distance = '<?= __("Distance"); ?>';
    let lang_gen_hamradio_bearing = '<?= __("Bearing"); ?>';
    let lang_gen_hamradio_pathlines = '<?= _pgettext("Map Options", "Path lines"); ?>';
    let lang_gen_hamradio_callsigns = '<?= __("Show Callsigns"); ?>';
    let lang_gen_hamradio_cq_zones = '<?= _pgettext("Map Options", "CQ Zones"); ?>';
    let lang_gen_hamradio_itu_zones = '<?= _pgettext("Map Options", "ITU Zones"); ?>';
    let lang_gen_hamradio_nightshadow = '<?= _pgettext("Map Options", "Night Shadow"); ?>';
    let lang_gen_hamradio_ituzone = '<?= __("ITU Zone"); ?>';
    let lang_gen_hamradio_cqzone = '<?= __("CQ Zone"); ?>';
    let lang_gen_advanced_logbook_help = '<?= __("Advanced Logbook Help"); ?>';
    let lang_gen_advanced_logbook_continent_fix = '<?= __("Continent fix"); ?>';
    let lang_gen_advanced_logbook_problem_fixing_itu_zones = '<?= __("There was a problem fixing ITU Zones."); ?>';
    let lang_gen_advanced_logbook_problem_fixing_cq_zones = '<?= __("There was a problem fixing CQ Zones."); ?>';
    let lang_gen_advanced_logbook_itu_zones_updated = '<?= __("ITU Zones updated successfully!"); ?>';
    let lang_gen_advanced_logbook_cq_zones_updated = '<?= __("CQ Zones updated successfully!"); ?>';
    let lang_gen_advanced_logbook_select_row_itu_zones = '<?= __("You need to select at least 1 row to fix ITU Zones!"); ?>';
    let lang_gen_advanced_logbook_select_row_cq_zones = '<?= __("You need to select at least 1 row to fix CQ Zones!"); ?>';
    let lang_gen_advanced_logbook_select_row_state = '<?= __("You need to select at least 1 row to fix State!"); ?>';
    let lang_gen_advanced_logbook_state_updated = '<?= __("State updated successfully!"); ?>';
    let lang_gen_advanced_logbook_problem_fixing_state = '<?= __("There was a problem fixing State."); ?>';
    let lang_gen_advanced_logbook_fixing_state = '<?= __("Fixing State"); ?>';
    let lang_gen_advanced_logbook_fixing_state_qsos = '<?= __("Fixing State (%s QSOs)"); ?>';
    let lang_gen_advanced_logbook_fixing_state_remaining = '<?= __("Fixing State: %s remaining"); ?>';
    let lang_gen_advanced_logbook_fixed = '<?= __("Fixed"); ?>';
    let lang_gen_advanced_logbook_fixed_with_count = '<?= __("Fixed: %s"); ?>';
    let lang_gen_advanced_logbook_skipped = '<?= __("Skipped"); ?>';
    let lang_gen_advanced_logbook_skipped_with_count = '<?= __("Skipped: %s, see details for skipped rows below"); ?>';
    let lang_gen_advanced_logbook_state_fix_complete = '<?= __("State Fix Complete"); ?>';
    let lang_gen_advanced_logbook_state_not_supported = '<?= __("Not all DXCC entities have state support. If you need support for additional countries, please create a ticket at %s with the GeoJSON file and desired letter coding for your country."); ?>';
    let lang_gen_advanced_logbook_github_url = 'https://github.com/wavelog/wavelog/issues';
    let lang_gen_advanced_logbook_github_link = '<a href="https://github.com/wavelog/wavelog/issues" target="_blank">Wavelog GitHub</a>';
    let lang_gen_advanced_logbook_select_only_one_row_quickfilter = '<?= __("Only 1 row can be selected for Quickfilter!"); ?>'
    let lang_gen_advanced_logbook_select_at_least_one_row_quickfilter = '<?= __("You need to select a row to use the Quickfilters!"); ?>';
    let lang_gen_advanced_logbook_select_at_least_one_row_qslcard = '<?= __("You need to select a least 1 row to display a QSL card!"); ?>';
    let lang_gen_advanced_logbook_continents_updated = '<?= __("Continents updated successfully!"); ?>';
    let lang_gen_advanced_logbook_problem_fixing_continents = '<?= __("There was a problem fixing Continents."); ?>';
    let lang_gen_advanced_logbook_error = '<?= __("ERROR"); ?>';
    let lang_gen_advanced_logbook_success = '<?= __("SUCCESS"); ?>';
    let lang_gen_advanced_logbook_info = '<?= __("INFO"); ?>';
    let lang_gen_advanced_logbook_warning = '<?= __("WARNING"); ?>';
    let lang_gen_advanced_logbook_qsl_card = '<?= __("QSL Card"); ?>';
    let lang_gen_advanced_logbook_close = '<?= __("Close"); ?>';
    let lang_gen_advanced_logbook_save = '<?= __("Save"); ?>';
    let lang_gen_advanced_logbook_update_now = '<?= __("Update now"); ?>';
    let lang_gen_advanced_logbook_options = '<?= __("Options for the Advanced Logbook"); ?>';
    let lang_gen_advanced_logbook_label_print_error = '<?= __("Something went wrong with label print. Go to labels and check if you have defined a label, and that it is set for print!"); ?>';
    let lang_gen_advanced_logbook_select_at_least_one_row = '<?= __("You need to select a least 1 row!"); ?>';
    let lang_gen_advanced_logbook_start_printing_at_which_label = '<?= __("Start printing at which label?"); ?>';
    let lang_gen_advanced_logbook_select_at_least_one_row_label = '<?= __("You need to select at least 1 row to print a label!"); ?>';
    let lang_gen_advanced_logbook_error_saving_options = '<?= __("An error occurred while saving options: "); ?>';
    let lang_gen_advanced_logbook_select_at_least_one_row_delete = '<?= __("You need to select a least 1 row to delete!"); ?>';
    let lang_gen_advanced_logbook_select_at_least_one_row_callbook = '<?= __("You need to select a least 1 row to update from callbook!"); ?>';
    let lang_gen_advanced_logbook_an_error_ocurred_while_making_request = '<?= __("An error ocurred while making the request"); ?>';
    let lang_gen_advanced_logbook_select_at_least_one_location = '<?= __("You need to select at least 1 location to do a search!"); ?>';
    let lang_gen_advanced_logbook_update_distances = '<?= __("Update Distances"); ?>';
    let lang_gen_advanced_logbook_records_updated = '<?= __("QSO records updated."); ?>';
    let lang_gen_advanced_logbook_problem_updating_distances = '<?= __("There was a problem updating distances."); ?>';
    let lang_gen_advanced_logbook_distances_updated = '<?= __("Distances updated successfully!"); ?>';

    let lang_gen_advanced_logbook_confirm_fix_missing_dxcc = '<?= __("Are you sure you want to fix all QSOs with missing DXCC information? This action cannot be undone."); ?>';
    let lang_gen_advanced_logbook_dupe_search = '<?= __("Duplicate Search"); ?>';
    let lang_gen_advanced_logbook_search = '<?= __("Search"); ?>';

    let lang_gen_advanced_logbook_show_more = '<?= __("Show more"); ?>';
    let lang_gen_advanced_logbook_show_less = '<?= __("Show less"); ?>';

	let lang_gen_advanced_logbook_confirmedLabel = '<?= __("Gridsquares for"); ?>';
	let lang_gen_advanced_logbook_workedLabel = '<?= __("Non DXCC matching gridsquare"); ?>';

    let homegrid ='<?php echo strtoupper($homegrid[0]); ?>';
    <?php
    if (!isset($options)) {
        $options = "{
            \"datetime\":{\"show\":\"true\"},
            \"de\":{\"show\":\"true\"},
            \"dx\":{\"show\":\"true\"},
            \"mode\":{\"show\":\"true\"},
            \"rstr\":{\"show\":\"true\"},
            \"rsts\":{\"show\":\"true\"},
            \"band\":{\"show\":\"true\"},
            \"myrefs\":{\"show\":\"true\"},
            \"name\":{\"show\":\"true\"},
            \"qslvia\":{\"show\":\"true\"},
            \"qsl\":{\"show\":\"true\"},
            \"lotw\":{\"show\":\"true\"},
            \"eqsl\":{\"show\":\"true\"},
            \"clublog\":{\"show\":\"true\"},
            \"qslmsgs\":{\"show\":\"false\"},
            \"qslmsgr\":{\"show\":\"false\"},
            \"dxcc\":{\"show\":\"true\"},
            \"state\":{\"show\":\"true\"},
            \"cqzone\":{\"show\":\"true\"},
            \"ituzone\":{\"show\":\"true\"},
            \"iota\":{\"show\":\"true\"},
            \"pota\":{\"show\":\"true\"},
            \"operator\":{\"show\":\"true\"},
            \"comment\":{\"show\":\"true\"},
            \"propagation\":{\"show\":\"true\"},
            \"contest\":{\"show\":\"true\"},
            \"gridsquare\":{\"show\":\"true\"},
            \"sota\":{\"show\":\"true\"},
            \"dok\":{\"show\":\"true\"},
            \"wwff\":{\"show\":\"true\"},
            \"sig\":{\"show\":\"true\"},
            \"continent\":{\"show\":\"true\"},
            \"qrz\":{\"show\":\"true\"},
            \"profilename\":{\"show\":\"true\"},
            \"stationpower\":{\"show\":\"true\"},
            \"distance\":{\"show\":\"true\"},
            \"region\":{\"show\":\"true\"},
            \"antennaazimuth\":{\"show\":\"true\"},
            \"antennaelevation\":{\"show\":\"true\"},
            \"county\":{\"show\":\"true\"},
            \"qth\":{\"show\":\"true\"},
            \"frequency\":{\"show\":\"true\"},
            \"dcl\":{\"show\":\"true\"},
            \"last_modification\":{\"show\":\"false\"},
        }";
    }
    $current_opts = json_decode($options);
    echo "var user_options = $options;";
    if (!isset($current_opts->qslmsgs)) {
        echo "\nvar o_template = { qslmsgs: {show: 'false'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->qslmsgr)) {
        echo "\nvar o_template = { qslmsgr: {show: 'false'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->pota)) {
        echo "\nvar o_template = { pota: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->operator)) {
        echo "\nvar o_template = { operator: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->comment)) {
        echo "\nvar o_template = { comment: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->ituzone)) {
        echo "\nvar o_template = { ituzone: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->propagation)) {
        echo "\nvar o_template = { propagation: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->contest)) {
        echo "\nvar o_template = { contest: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->gridsquare)) {
        echo "\nvar o_template = { gridsquare: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->sota)) {
        echo "\nvar o_template = { sota: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->dok)) {
        echo "\nvar o_template = { dok: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->wwff)) {
        echo "\nvar o_template = { wwff: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->clublog)) {
        echo "\nvar o_template = { clublog: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->sig)) {
        echo "\nvar o_template = { sig: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->continent)) {
        echo "\nvar o_template = { continent: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->qrz)) {
        echo "\nvar o_template = { qrz: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->profilename)) {
        echo "\nvar o_template = { profilename: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->stationpower)) {
        echo "\nvar o_template = { stationpower: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->distance)) {
        echo "\nvar o_template = { distance: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->region)) {
        echo "\nvar o_template = { region: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->antennaazimuth)) {
        echo "\nvar o_template = { antennaazimuth: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->antennaelevation)) {
        echo "\nvar o_template = { antennaelevation: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->county)) {
        echo "\nvar o_template = { county: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->qth)) {
        echo "\nvar o_template = { qth: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->frequency)) {
        echo "\nvar o_template = { frequency: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->dcl)) {
        echo "\nvar o_template = { dcl: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }
    if (!isset($current_opts->last_modification)) {
        echo "\nvar o_template = { last_modification: {show: 'false'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }

    foreach ($mapoptions as $mo) {
	    if ($mo != null) {
		    if (($mo->option_key == 'boolean') && (($mo->option_value ?? '') == '')) {
			    $mo->option_value='false';
		    }
		    echo "var " . $mo->option_name . "=" . $mo->option_value . ";";
	    }
    }
    ?>
    var tileUrl = "<?php echo $this->optionslib->get_option('option_map_tile_server'); ?>"
</script>
<style>
    .row>[class*="col-"] {
        padding-right: 5px;
        padding-left: 5px;
    }
</style>
<?php
$options = json_decode($options);
?>
<div id="lba_div">
    <div class="container-fluid qso_manager pt-3 ps-4 pe-4">
        <?php if ($this->session->flashdata('message')) { ?>
            <!-- Display Message -->
            <div class="alert-message error">
                <p><?php echo $this->session->flashdata('message'); ?></p>
            </div>
        <?php } ?>
        <div class="row">

            <form id="searchForm" name="searchForm" action="<?php echo base_url() . "index.php/logbookadvanced/search"; ?>" method="post">
                <input type="hidden" id="dupes" name="dupes" value="">
				<input type="hidden" id="invalid" name="invalid" value="">
				<input type="hidden" id="dupedate" name="dupedate" value="">
				<input type="hidden" id="dupemode" name="dupemode" value="">
				<input type="hidden" id="dupeband" name="dupeband" value="">
				<input type="hidden" id="dupesat" name="dupesat" value="">

        <div class="row pt-2">
			<div class="d-flex flex-wrap btn-group w-auto mx-auto">

                <!-- Main Filters Dropdown -->
                    <div class="dropdown d-inline-block" data-bs-auto-close="outside">
                        <button class="btn btn-sm btn-primary dropdown-toggle me-1" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter"></i> <?= __("Filters"); ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start p-3 mt-2" aria-labelledby="filterDropdown" style="min-width: 900px; max-height: 600px; overflow-y: auto;">
                            <div class="card-body filterbody container-fluid">
								<div class="row">
									<div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
									<label class="form-label" for="checkboxes"><?= __("Date Presets") . ": " ?></label>
										<div class="d-flex flex-wrap gap-1">
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
								</div>
                                <div class="row">
                                    <div <?php if (($options->datetime->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="dateFrom"><?= __("From") . ": " ?></label>
                                        <input name="dateFrom" id="dateFrom" type="date" class="form-control form-control-sm border border-secondary">
                                    </div>
                                    <div <?php if (($options->datetime->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="dateTo"><?= __("To") . ": " ?></label>
                                        <input name="dateTo" id="dateTo" type="date" class="form-control form-control-sm border border-secondary">
                                    </div>
                                    <div <?php if (($options->dx->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="dx"><?= __("Dx"); ?></label>
                                        <input onclick="this.select()" type="text" name="dx" id="dx" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->dxcc->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="dxcc"><?= __("DXCC"); ?></label>
                                        <select class="form-select form-select-sm border border-secondary" id="dxcc" name="dxcc">
                                            <option value="">-</option>
                                            <?php
                                            foreach ($dxccarray as $dxcc) {
                                                if ($dxcc->adif == '0') {
                                                    echo '<option value='.$dxcc->adif.'>';
                                                    echo $dxcc->name;
                                                    echo '</option>';
                                                } else {
                                                    echo '<option value=' . $dxcc->adif;
                                                    echo '>' . $dxcc->prefix . ' - ' . ucwords(strtolower($dxcc->name), "- (/");
                                                    if ($dxcc->Enddate != null) {
                                                        echo ' - (' . __("Deleted DXCC") . ')';
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div <?php if (($options->state->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="state"><?= __("State"); ?></label>
                                        <input onclick="this.select()" type="text" name="state" id="state" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
								</div>
								<div class="row">
                                    <div <?php if (($options->gridsquare->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="gridsquare"><?= __("Gridsquare"); ?></label>
                                        <input onclick="this.select()" type="text" name="gridsquare" id="gridsquare" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->mode->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="mode"><?= __("Mode"); ?></label>
                                        <select id="mode" name="mode" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <?php
                                            foreach ($modes as $modeId => $mode) {
                                            ?><option value="<?php echo htmlspecialchars($mode ?? ''); ?>"><?php echo htmlspecialchars($mode ?? ''); ?></option><?php
                                                                                                                                            }
                                                                                                                                                ?>
                                        </select>
                                    </div>
                                    <div <?php if (($options->band->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="band"><?= __("Band"); ?></label>
                                        <select id="band" name="band" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <?php
                                            foreach ($bands as $band) {
                                            ?><option value="<?php echo htmlentities($band ?? ''); ?>"><?php echo htmlspecialchars($band ?? ''); ?></option><?php
                                                                                                                                        }
                                                                                                                                            ?>
                                        </select>
                                    </div>
                                    <div hidden class="sats_dropdown mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="sats"><?= __("Satellite"); ?></label>
                                        <select class="form-select form-select-sm border border-secondary" id="sats" name="sats">
                                            <option value="All"><?= __("All"); ?></option>
                                            <?php foreach ($sats as $sat) {
                                                echo '<option value="' . htmlentities($sat) . '"' . '>' . htmlentities($sat) . '</option>' . "\n";
                                            } ?>
                                        </select>
                                    </div>
                                    <div hidden class="orbits_dropdown mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="orbits"><?= __("Orbit"); ?></label>
                                        <select class="form-select form-select-sm border border-secondary" id="orbits" name="orbits">
                                            <option value="All"><?= __("All"); ?></option>
                                            <?php foreach ($orbits as $orbit) {
                                                echo '<option value="' . htmlentities($orbit) . '"' . '>' . htmlentities($orbit) . '</option>' . "\n";
                                            } ?>
                                        </select>
                                    </div>
                                    <div <?php if (($options->propagation->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="selectPropagation"><?= __("Propagation"); ?></label>
                                        <select id="selectPropagation" class="form-select form-select-sm border border-secondary" name="propmode">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="None"><?= _pgettext("Propagation Mode", "None/Empty"); ?></option>
                                            <option value="AS"><?= _pgettext("Propagation Mode", "Aircraft Scatter"); ?></option>
                                            <option value="AUR"><?= _pgettext("Propagation Mode", "Aurora"); ?></option>
                                            <option value="AUE"><?= _pgettext("Propagation Mode", "Aurora-E"); ?></option>
                                            <option value="BS"><?= _pgettext("Propagation Mode", "Back scatter"); ?></option>
                                            <option value="ECH"><?= _pgettext("Propagation Mode", "EchoLink"); ?></option>
                                            <option value="EME"><?= _pgettext("Propagation Mode", "Earth-Moon-Earth"); ?></option>
                                            <option value="ES"><?= _pgettext("Propagation Mode", "Sporadic E"); ?></option>
                                            <option value="FAI"><?= _pgettext("Propagation Mode", "Field Aligned Irregularities"); ?></option>
                                            <option value="F2"><?= _pgettext("Propagation Mode", "F2 Reflection"); ?></option>
                                            <option value="INTERNET"><?= _pgettext("Propagation Mode", "Internet-assisted"); ?></option>
                                            <option value="ION"><?= _pgettext("Propagation Mode", "Ionoscatter"); ?></option>
                                            <option value="IRL"><?= _pgettext("Propagation Mode", "IRLP"); ?></option>
                                            <option value="MS"><?= _pgettext("Propagation Mode", "Meteor scatter"); ?></option>
                                            <option value="RPT"><?= _pgettext("Propagation Mode", "Terrestrial or atmospheric repeater or transponder"); ?></option>
                                            <option value="RS"><?= _pgettext("Propagation Mode", "Rain scatter"); ?></option>
                                            <option value="SAT"><?= _pgettext("Propagation Mode", "Satellite"); ?></option>
                                            <option value="TEP"><?= _pgettext("Propagation Mode", "Trans-equatorial"); ?></option>
                                            <option value="TR"><?= _pgettext("Propagation Mode", "Tropospheric ducting"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->cqzone->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="cqzone"><?= __("CQ Zone"); ?></label>
                                        <select id="cqzone" name="cqzone" class="form-select form-select-sm border border-secondary">
                                            <option value="All"><?= __("All"); ?></option>
                                            <option value=""><?= __("Empty"); ?></option>
                                            <?php
                                            for ($i = 1; $i <= 40; $i++) {
                                                echo '<option value="' . $i . '">' . $i . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
								</div>
								<div class="row">
                                    <div <?php if (($options->ituzone->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="ituzone"><?= __("ITU Zone"); ?></label>
                                        <select id="ituzone" name="ituzone" class="form-select form-select-sm border border-secondary">
                                            <option value="All"><?= __("All"); ?></option>
                                            <option value=""><?= __("Empty"); ?></option>
                                            <?php
                                            for ($i = 1; $i <= 90; $i++) {
                                                echo '<option value="' . $i . '">' . $i . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div <?php if (($options->county->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="county"><?= __("County"); ?></label>
                                        <input onclick="this.select()" type="text" name="county" id="county" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->dok->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="dok"><?= __("DOK"); ?></label>
                                        <input onclick="this.select()" type="text" name="dok" id="dok" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->sota->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="sota"><?= __("SOTA"); ?></label>
                                        <input onclick="this.select()" type="text" name="sota" id="sota" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->pota->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="pota"><?= __("POTA"); ?></label>
                                        <input onclick="this.select()" type="text" name="pota" id="pota" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
								</div>
								<div class="row">
                                    <div <?php if (($options->iota->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="iota"><?= __("IOTA"); ?></label>
                                        <select class="form-select form-select-sm border border-secondary" id="iota" name="iota">
                                            <option value="">-</option>
                                            <?php
                                            foreach ($iotaarray as $iota) {
                                                echo '<option value=' . $iota->tag;
                                                echo '>' . $iota->tag . ' - ' . $iota->name . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div <?php if (($options->wwff->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="wwff"><?= __("WWFF"); ?></label>
                                        <input onclick="this.select()" type="text" name="wwff" id="wwff" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->operator->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="operator"><?= __("Operator"); ?></label>
                                        <input onclick="this.select()" type="text" name="operator" id="operator" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->contest->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="contest"><?= __("Contest"); ?></label>
                                        <input onclick="this.select()" type="text" name="contest" id="contest" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->continent->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="continent"><?= __("Continent"); ?></label>
                                        <select id="continent" name="continent" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="blank"><?= __("None/Empty"); ?></option>
                                            <option value="af"><?= __("Africa"); ?></option>
                                            <option value="an"><?= __("Antarctica"); ?></option>
                                            <option value="na"><?= __("North America"); ?></option>
                                            <option value="as"><?= __("Asia"); ?></option>
                                            <option value="eu"><?= __("Europe"); ?></option>
                                            <option value="sa"><?= __("South America"); ?></option>
                                            <option value="oc"><?= __("Oceania"); ?></option>
                                            <option value="invalid"><?= __("Invalid"); ?></option>
                                        </select>
                                    </div>
								</div>
								<div class="row">
                                    <div <?php if (($options->comment->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="comment"><?= __("Comment"); ?></label>
                                        <input onclick="this.select()" type="text" name="comment" id="comment" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
									<div <?php if (($options->distance->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="distance"><?= __("Distance"); ?> <i class="fa fa-question-circle" aria-hidden="true" data-bs-toggle="tooltip" title="<?= __("Distance in kilometers. Search will look for distances greater than or equal to this value."); ?>"></i></label>
                                        <input onclick="this.select()" type="text" name="distance" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                </div>
								<div class="row">
                                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="sortcolumn"><?= __("Sort column"); ?></label>
										<select id="sortcolumn" name="sortcolumn" class="form-select form-select-sm border border-secondary">
                                            <option value="qsotime"><?= __("QSO Time"); ?></option>
                                            <option value="band"><?= __("Band"); ?></option>
                                            <option value="mode"><?= __("Mode"); ?></option>
                                            <option value="qsomodified"><?= __("QSO Modified"); ?></option>
                                        </select>
                                    </div>
									<div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label class="form-label" for="sortdirection"><?= __("Sort direction"); ?></label>
										<select id="sortdirection" name="sortdirection" class="form-select form-select-sm border border-secondary">
                                            <option value="desc"><?= __("Descending"); ?></option>
                                            <option value="asc"><?= __("Ascending"); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
							<div class="row">
									<div class="col-lg-2 col-md-2 col-sm-3 col-xl">
										<div class="d-flex flex-wrap gap-1">
											<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="$('#searchForm').submit(); $('#filterDropdown').dropdown('hide');"><i class="fas fa-search"></i> <?= __("Apply filters"); ?></button>
										</div>
									</div>
								</div>
							</div>
                    </div>

                    <!-- QSL Filters Dropdown -->
                    <div class="dropdown d-inline-block" data-bs-auto-close="outside">
                        <button class="btn btn-sm btn-primary dropdown-toggle me-1" type="button" id="qslFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter"></i> <?= __("QSL Filters"); ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start p-3 mt-2" aria-labelledby="qslFilterDropdown" style="min-width: 900px; max-height: 600px; overflow-y: auto;">
                            <div class="card-body qslfilterbody">
                                <div class="row">
                                    <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="qslSent"><?= __("QSL sent"); ?></label>
                                        <select id="qslSent" name="qslSent" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="R"><?= __("Requested"); ?></option>
                                            <option value="Q"><?= __("Queued"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="qslReceived"><?= __("QSL received"); ?></label>
                                        <select id="qslReceived" name="qslReceived" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="R"><?= __("Requested"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                            <option value="V"><?= __("Verified"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="qslSentMethod"><?= __("QSL send. method"); ?></label>
                                        <select id="qslSentMethod" name="qslSentMethod" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="B"><?= __("Bureau"); ?></option>
                                            <option value="D"><?= __("Direct"); ?></option>
                                            <option value="E"><?= __("Electronic"); ?></option>
                                            <option value="M"><?= __("Manager"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="qslReceivedMethod"><?= __("QSL recv. method"); ?></label>
                                        <select id="qslReceivedMethod" name="qslReceivedMethod" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="B"><?= __("Bureau"); ?></option>
                                            <option value="D"><?= __("Direct"); ?></option>
                                            <option value="E"><?= __("Electronic"); ?></option>
                                            <option value="M"><?= __("Manager"); ?></option>
                                        </select>
                                    </div>
								</div>
                                <div class="row">
                                    <div <?php if (($options->lotw->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="lotwSent"><?= __("LoTW sent"); ?></label>
                                        <select id="lotwSent" name="lotwSent" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="R"><?= __("Requested"); ?></option>
                                            <option value="Q"><?= __("Queued"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->lotw->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="lotwReceived"><?= __("LoTW received"); ?></label>
                                        <select id="lotwReceived" name="lotwReceived" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="R"><?= __("Requested"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                            <option value="V"><?= __("Verified"); ?></option>
                                        </select>
                                    </div>

                                    <div <?php if (($options->clublog->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="clublogSent"><?= __("Clublog sent"); ?></label>
                                        <select id="clublogSent" name="clublogSent" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="R"><?= __("Requested"); ?></option>
                                            <option value="Q"><?= __("Queued"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->clublog->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="clublogReceived"><?= __("Clublog received"); ?></label>
                                        <select id="clublogReceived" name="clublogReceived" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="R"><?= __("Requested"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                            <option value="V"><?= __("Verified"); ?></option>
                                        </select>
                                    </div>
								</div>
                                <div class="row">
                                    <div <?php if (($options->eqsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="eqslSent"><?= __("eQSL sent"); ?></label>
                                        <select id="eqslSent" name="eqslSent" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="R"><?= __("Requested"); ?></option>
                                            <option value="Q"><?= __("Queued"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->eqsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="eqslReceived"><?= __("eQSL received"); ?></label>
                                        <select id="eqslReceived" name="eqslReceived" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="R"><?= __("Requested"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                            <option value="V"><?= __("Verified"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->dcl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="dclSent"><?= __("DCL sent"); ?></label>
                                        <select id="dclSent" name="dclSent" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->dcl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="dclReceived"><?= __("DCL received"); ?></label>
                                        <select id="dclReceived" name="dclReceived" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                        </select>
                                    </div>
								</div>
                                <div class="row">
                                    <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="qslvia"><?= __("QSL via"); ?></label>
                                        <input onclick="this.select()" type="search" name="qslvia" class="form-control form-control-sm border border-secondary" value="*" placeholder="<?= __("Empty"); ?>">
                                    </div>
                                    <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="qslimages"><?= __("QSL Images"); ?></label>
                                        <select class="form-select form-select-sm border border-secondary" id="qslimages" name="qslimages">
                                            <option value="">-</option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                        </select>
                                    </div>
									<div <?php if (($options->qrz->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="qrzSent"><?= __("QRZ sent"); ?></label>
                                        <select id="qrzSent" name="qrzSent" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                        </select>
                                    </div>
                                    <div <?php if (($options->qrz->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                                        <label for="qrzReceived"><?= __("QRZ received"); ?></label>
                                        <select id="qrzReceived" name="qrzReceived" class="form-select form-select-sm border border-secondary">
                                            <option value=""><?= __("All"); ?></option>
                                            <option value="Y"><?= __("Yes"); ?></option>
                                            <option value="N"><?= __("No"); ?></option>
                                            <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                                        </select>
                                    </div>
                                </div>
								<div class="row">
									<div class="col-lg-2 col-md-2 col-sm-3 col-xl">
										<div class="d-flex flex-wrap gap-1">
											<button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="$('#searchForm').submit(); $('#qslFilterDropdown').dropdown('hide');"><i class="fas fa-search"></i> <?= __("Apply filters"); ?></button>
										</div>
									</div>
								</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quickfilters Dropdown -->
                    <div class="dropdown d-inline-block" data-bs-auto-close="outside">
                        <button class="btn btn-sm btn-primary dropdown-toggle me-1" type="button" id="quickfilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-filter"></i> <?= __("Quickfilters"); ?>
                        </button>
						<div class="dropdown-menu dropdown-menu-start" aria-labelledby="quickfilterDropdown" style="min-width: 300px;">
							<div class="card">
								<div class="card-header p-2">
									<span class="h6 w-100 mt-0 mb-0"><?= __("Quicksearch with selected: "); ?></span>
								</div>
								<div class="card-body p-2">
									<div class="d-grid gap-2">
										<?php if (($options->datetime->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchDate"><?= __("Search Date"); ?></button>
										<?php } ?>
										<?php if (($options->dx->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchCallsign"><?= __("Search Callsign"); ?></button>
										<?php } ?>
										<?php if (($options->dxcc->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchDxcc"><?= __("Search DXCC"); ?></button>
										<?php } ?>
										<?php if (($options->state->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchState"><?= __("Search State"); ?></button>
										<?php } ?>
										<?php if (($options->gridsquare->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchGridsquare"><?= __("Search Gridsquare"); ?></button>
										<?php } ?>
										<?php if (($options->cqzone->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchCqZone"><?= __("Search CQ Zone"); ?></button>
										<?php } ?>
										<?php if (($options->ituzone->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchItuZone"><?= __("Search ITU Zone"); ?></button>
										<?php } ?>
										<?php if (($options->mode->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchMode"><?= __("Search Mode"); ?></button>
										<?php } ?>
										<?php if (($options->band->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchBand"><?= __("Search Band"); ?></button>
										<?php } ?>
										<?php if (($options->iota->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchIota"><?= __("Search IOTA"); ?></button>
										<?php } ?>
										<?php if (($options->sota->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchSota"><?= __("Search SOTA"); ?></button>
										<?php } ?>
										<?php if (($options->pota->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchPota"><?= __("Search POTA"); ?></button>
										<?php } ?>
										<?php if (($options->wwff->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchWwff"><?= __("Search WWFF"); ?></button>
										<?php } ?>
										<?php if (($options->operator->show ?? "true") == "true") { ?>
											<button type="button" class="btn btn-sm btn-primary dropdown-action" id="searchOperator"><?= __("Search Operator"); ?></button>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>

					</div>

				<!-- End of Main Filters Dropdown -->

				<?php if(clubaccess_check(9)) { ?>
                    <!-- Actions Dropdown -->
                    <div class="dropdown d-inline-block" data-bs-auto-close="outside">
                        <button class="btn btn-sm btn-success dropdown-toggle me-1" type="button" id="actionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-tasks"></i> <?= __("Actions"); ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-start" aria-labelledby="actionsDropdown" style="min-width: 300px;">
                            <script>
                                var lang_filter_actions_delete_warning = '<?= __("Warning! Are you sure you want to delete the marked QSO(s)?"); ?>';
                                var lang_filter_actions_delete_warning_details = '<?= __(" QSO(s) will be deleted"); ?>';
                            </script>
                            <div class="card">
								<div class="card-header p-2">
									<span class="h6 w-100 mt-0 mb-0"><?= __("With selected: "); ?></span>
								</div>
								<div class="card-body p-2">
									<div class="d-grid gap-2">
										<button type="button" class="btn btn-sm btn-primary dropdown-action" id="btnUpdateFromCallbook"><?= __("Update from Callbook"); ?></button>
										<button type="button" class="btn btn-sm btn-primary dropdown-action" id="queueBureau"><?= __("Queue Bureau"); ?></button>
										<button type="button" class="btn btn-sm btn-primary dropdown-action" id="queueDirect"><?= __("Queue Direct"); ?></button>
										<button type="button" class="btn btn-sm btn-primary dropdown-action" id="queueElectronic"><?= __("Queue Electronic"); ?></button>
										<button type="button" class="btn btn-sm btn-success dropdown-action" id="sentBureau"><?= __("Sent (Bureau)"); ?></button>
										<button type="button" class="btn btn-sm btn-success dropdown-action" id="sentDirect"><?= __("Sent (Direct)"); ?></button>
										<button type="button" class="btn btn-sm btn-success dropdown-action" id="sentElectronic"><?= __("Sent (Electronic)"); ?></button>
										<button type="button" class="btn btn-sm btn-danger dropdown-action" id="dontSend"><?= __("Not Sent"); ?></button>
										<button type="button" class="btn btn-sm btn-danger dropdown-action" id="notRequired"><?= __("QSL Not Required"); ?></button>
										<button type="button" class="btn btn-sm btn-danger dropdown-action" id="notReceived"><?= __("Not Received"); ?></button>
										<button type="button" class="btn btn-sm btn-warning dropdown-action" id="receivedBureau"><?= __("Received (Bureau)"); ?></button>
										<button type="button" class="btn btn-sm btn-warning dropdown-action" id="receivedDirect"><?= __("Received (Direct)"); ?></button>
										<button type="button" class="btn btn-sm btn-warning dropdown-action" id="receivedElectronic"><?= __("Received (Electronic)"); ?></button>
										<button type="button" class="btn btn-sm btn-info dropdown-action" id="exportAdif"><?= __("Create ADIF"); ?></button>
										<button type="button" class="btn btn-sm btn-info dropdown-action" id="printLabel"><?= __("Print Label"); ?></button>
										<button type="button" class="btn btn-sm btn-info dropdown-action" id="qslSlideshow"><?= __("QSL Slideshow"); ?></button>
										<button type="button" class="btn btn-sm btn-success dropdown-action" id="fixState"><?= __("Fix State"); ?></button>
									</div>
								</div>
							</div>
                        </div>
                    </div>
				<?php } ?>
				<label for="qsoResults" class="me-2" style="white-space: nowrap;"><?= __("# Results"); ?></label>
				<select id="qsoResults" name="qsoresults" class="form-select form-select-sm w-auto me-2" style="height: calc(1.5em + .5rem + 2px) !important;">
					<option value="250">250</option>
					<option value="1000">1000</option>
					<option value="2500">2500</option>
					<option value="5000">5000</option>
				</select>
				<label class="me-2" for="de"><?= __("Location"); ?></label>
				<select class="form-select form-select-sm w-auto me-2" id="de" name="de" multiple="multiple">
					<?php foreach ($station_profile->result() as $station) { ?>
						<option value="<?php echo $station->station_id; ?>" <?php if ($station->station_id == $active_station_id) {
							echo " selected =\"selected\""; } ?>>
							<?= __("Callsign: ") . " " ?>
							<?php echo str_replace("0", "&Oslash;", strtoupper($station->station_callsign)); ?> (<?php echo $station->station_profile_name; ?>)
						</option>
					<?php } ?>
				</select>
				<button type="submit" class="btn btn-sm btn-success me-1 ld-ext-right flex-grow-0 mb-2" aria-label="<?= __("Search"); ?>" id="searchButton" style="white-space: nowrap;" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Search"); ?>">
					<i class="fas fa-search"></i><div class="ld ld-ring ld-spin"></div>
				</button>
				<button type="button" class="btn btn-sm btn-primary me-1 ld-ext-right flex-grow-0 mb-2" id="dupeButton" style="white-space: nowrap;">
					<i class="fa fa-clone"></i> <?= __("Dupes"); ?><div class="ld ld-ring ld-spin"></div>
				</button>
				<button type="button" class="btn btn-sm btn-primary me-1 ld-ext-right flex-grow-0 mb-2" id="invalidButton" style="white-space: nowrap;">
					<i class="fa fa-exclamation-triangle"></i> <?= __("Invalid"); ?><div class="ld ld-ring ld-spin"></div>
				</button>
				<?php if(clubaccess_check(9)) { ?>
				<button type="button" class="btn btn-sm btn-primary me-1 ld-ext-right flex-grow-0 mb-2" id="editButton" style="white-space: nowrap;">
					<i class="fas fa-edit"></i> <?= __("Edit"); ?><div class="ld ld-ring ld-spin"></div>
				</button>
				<?php } ?>
				<div class="btn-group me-1" role="group">
					<button type="button" class="btn btn-sm btn-primary ld-ext-right flex-grow-0 mb-2" id="mapButton" onclick="mapQsos(this.form);" style="white-space: nowrap;">
						<i class="fas fa-globe-europe"></i> <?= __("Map"); ?><div class="ld ld-ring ld-spin"></div>
					</button>
					<button id="btnGroupDrop1" type="button" class="btn btn-sm btn-primary dropdown-toggle flex-grow-0 mb-2" data-bs-toggle="dropdown" aria-expanded="false"></button>
					<ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
						<li><button type="button" class="dropdown-item" onclick="mapGlobeQsos(this.form);" id="mapGlobeButton"><?= __("Globe map"); ?></button></li>
					</ul>
				</div>
				<?php if(clubaccess_check(9)) { ?>
					<button type="options" class="btn btn-sm btn-primary me-1 flex-grow-0 mb-2" id="optionButton" aria-label="<?= __("Options"); ?>" style="white-space: nowrap;" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Options"); ?>">
						<i class="fas fa-cog"></i>
					</button>
					<button type="button" class="btn btn-sm btn-primary me-1 flex-grow-0 mb-2" id="dbtools" style="white-space: nowrap;" aria-label="<?= __("Database Tools"); ?>"  data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Database Tools"); ?>">
						<i class="fas fa-wrench"></i>
					</button>
					<button type="button" class="btn btn-sm btn-danger me-1 flex-grow-0 mb-2" id="deleteQsos" style="white-space: nowrap;" aria-label="<?= __("Delete"); ?>"  data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Delete"); ?>">
						<i class="fas fa-trash-alt"></i>
					</button>
				<?php } ?>
				<button type="reset" class="btn btn-sm btn-danger me-1 flex-grow-0 mb-2" id="resetButton" style="white-space: nowrap;" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Reset"); ?>">
					<i class="fas fa-undo"></i> <?= __("Reset"); ?>
				</button>
				<button type="button" class="btn btn-sm btn-success me-1 flex-grow-0 mb-2" id="helpButton" style="white-space: nowrap;" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= __("Help"); ?>">
					<i class="fa fa-question"></i>
				</button>
			</div>
		</div>

        </form>
        <table style="width:100%" class="table-sm table table-hover table-striped table-bordered table-condensed text-center" id="qsoList">
            <thead>
                <tr>
                    <th>
                        <div class="form-check" style="margin-top: -1.5em"><input class="form-check-input" type="checkbox" id="checkBoxAll" /></div>
                    </th>
                    <?php if (($options->datetime->show ?? "true") == "true") {
                        echo '<th>' . __("Date/Time") . '</th>';
                    } ?>
                    <?php if (($options->last_modification->show ?? "false") == "true") {
                        echo '<th>' . __("Last modified") . '</th>';
                    } ?>
                    <?php if (($options->de->show ?? "true") == "true") {
                        echo '<th>' . __("De") . '</th>';
                    } ?>
                    <?php if (($options->dx->show ?? "true") == "true") {
                        echo '<th>' . __("Dx") . '</th>';
                    } ?>
                    <?php if (($options->mode->show ?? "true") == "true") {
                        echo '<th>' . __("Mode") . '</th>';
                    } ?>
                    <?php if (($options->rsts->show ?? "true") == "true") {
                        echo '<th>' . __("RST (S)") . '</th>';
                    } ?>
                    <?php if (($options->rstr->show ?? "true") == "true") {
                        echo '<th>' . __("RST (R)") . '</th>';
                    } ?>
                    <?php if (($options->band->show ?? "true") == "true") {
                        echo '<th>' . __("Band") . '</th>';
                    } ?>
                    <?php if (($options->frequency->show ?? "true") == "true") {
                        echo '<th>' . __("Frequency") . '</th>';
                    } ?>
                    <?php if (($options->gridsquare->show ?? "true") == "true") {
                        echo '<th>' . __("Gridsquare") . '</th>';
                    } ?>
                    <?php if (($options->name->show ?? "true") == "true") {
                        echo '<th>' . __("Name") . '</th>';
                    } ?>
                    <?php if (($options->qth->show ?? "true") == "true") {
                        echo '<th>' . __("QTH") . '</th>';
                    } ?>
                    <?php if (($options->qslvia->show ?? "true") == "true") {
                        echo '<th>' . __("QSL via") . '</th>';
                    } ?>
                    <?php if (($options->clublog->show ?? "true") == "true") {
                        echo '<th class="clublogconfirmation">Clublog</th>';
                    } ?>
                    <?php if (($options->qsl->show ?? "true") == "true") {
                        echo '<th>' . __("QSL") . '</th>';
                    } ?>
                    <?php if (($options->eqsl->show ?? "true") == "true") {
                        echo '<th class="eqslconfirmation">eQSL</th>';
                    } ?>
                    <?php if (($options->lotw->show ?? "true") == "true") {
                        echo '<th class="lotwconfirmation">LoTW</th>';
                    } ?>
                    <?php if (($options->qrz->show ?? "true") == "true") {
                        echo '<th class="qrz">' . __("QRZ") . '</th>';
                    } ?>
                    <?php if (($options->dcl->show ?? "true") == "true") {
                        echo '<th>' . __("DCL") . '</th>';
                    } ?>
                    <?php if (($options->qslmsgs->show ?? "false") == "true") {
                        echo '<th>' . __("QSL Msg (S)") . '</th>';
                    } ?>
                    <?php if (($options->qslmsgr->show ?? "false") == "true") {
                        echo '<th>' . __("QSL Msg (R)") . '</th>';
                    } ?>
                    <?php if (($options->dxcc->show ?? "true") == "true") {
                        echo '<th>' . __("DXCC") . '</th>';
                    } ?>
                    <?php if (($options->state->show ?? "true") == "true") {
                        echo '<th>' . __("State") . '</th>';
                    } ?>
                    <?php if (($options->county->show ?? "true") == "true") {
                        echo '<th>' . __("County") . '</th>';
                    } ?>
                    <?php if (($options->cqzone->show ?? "true") == "true") {
                        echo '<th>' . __("CQ Zone") . '</th>';
                    } ?>
                    <?php if (($options->ituzone->show ?? "true") == "true") {
                        echo '<th>' . __("ITU Zone") . '</th>';
                    } ?>
                    <?php if (($options->iota->show ?? "true") == "true") {
                        echo '<th>' . __("IOTA") . '</th>';
                    } ?>
                    <?php if (($options->pota->show ?? "true") == "true") {
                        echo '<th>' . __("POTA") . '</th>';
                    } ?>
                    <?php if (($options->sota->show ?? "true") == "true") {
                        echo '<th>SOTA</th>';
                    } ?>
                    <?php if (($options->dok->show ?? "true") == "true") {
                        echo '<th>' . __("DOK") . '</th>';
                    } ?>
                    <?php if (($options->wwff->show ?? "true") == "true") {
                        echo '<th>WWFF</th>';
                    } ?>
                    <?php if (($options->sig->show ?? "true") == "true") {
                        echo '<th>SIG</th>';
                    } ?>
                    <?php if (($options->region->show ?? "true") == "true") {
                        echo '<th>' . __("Region") . '</th>';
                    } ?>
                    <?php if (($options->operator->show ?? "true") == "true") {
                        echo '<th>' . __("Operator") . '</th>';
                    } ?>
                    <?php if (($options->comment->show ?? "true") == "true") {
                        echo '<th>' . __("Comment") . '</th>';
                    } ?>
                    <?php if (($options->propagation->show ?? "true") == "true") {
                        echo '<th>' . __("Propagation") . '</th>';
                    } ?>
                    <?php if (($options->contest->show ?? "true") == "true") {
                        echo '<th>' . __("Contest") . '</th>';
                    } ?>
                    <?php if (($options->myrefs->show ?? "true") == "true") {
                        echo '<th>' . __("My Refs") . '</th>';
                    } ?>
                    <?php if (($options->continent->show ?? "true") == "true") {
                        echo '<th>' . __("Continent") . '</th>';
                    } ?>
                    <?php if (($options->distance->show ?? "true") == "true") {
                        echo '<th class="distance-column-sort">' . __("Distance") . '</th>';
                    } ?>
                    <?php if (($options->antennaazimuth->show ?? "true") == "true") {
                        echo '<th class="antennaazimuth-column-sort" data-bs-toggle="tooltip" data-bs-placement="top" title="' . __("Antenna azimuth") . '">' . __("Ant az") . '</th>';
                    } ?>
                    <?php if (($options->antennaelevation->show ?? "true") == "true") {
                        echo '<th class="antennaelevation-column-sort" data-bs-toggle="tooltip" data-bs-placement="top" title="' .__("Antenna elevation") .'">' . __("Ant el") . '</th>';
                    } ?>
                    <?php if (($options->profilename->show ?? "true") == "true") {
                        echo '<th>' . __("Profile name") . '</th>';
                    } ?>
                    <?php if (($options->stationpower->show ?? "true") == "true") {
                        echo '<th class="stationpower-column-sort">' . __("Station power") . '</th>';
                    } ?>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
