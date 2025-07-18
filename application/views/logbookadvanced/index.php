<script type="text/javascript">
    var user_id = <?php echo $this->session->userdata('user_id'); ?>;

    /*
     * Custom user settings
     */
    var custom_date_format = "<?php echo $custom_date_format ?>";
    let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');

    var lang_gen_hamradio_latitude = '<?= __("Latitude"); ?>';
    var lang_gen_hamradio_longitude = '<?= __("Longitude"); ?>';
    var lang_gen_hamradio_gridsquare = '<?= __("Gridsquare"); ?>';
    var lang_gen_hamradio_gridsquares = '<?= _pgettext("Map Options", "Gridsquares"); ?>';
    var lang_gen_hamradio_distance = '<?= __("Distance"); ?>';
    var lang_gen_hamradio_bearing = '<?= __("Bearing"); ?>';
    var lang_gen_hamradio_pathlines = '<?= _pgettext("Map Options", "Path lines"); ?>';
    var lang_gen_hamradio_callsigns = '<?= __("Show Callsigns"); ?>';
    var lang_gen_hamradio_cq_zones = '<?= _pgettext("Map Options", "CQ Zones"); ?>';
    var lang_gen_hamradio_itu_zones = '<?= _pgettext("Map Options", "ITU Zones"); ?>';
    var lang_gen_hamradio_nightshadow = '<?= _pgettext("Map Options", "Night Shadow"); ?>';
    var lang_gen_hamradio_ituzone = '<?= __("ITU Zone"); ?>';
    var lang_gen_hamradio_cqzone = '<?= __("CQ Zone"); ?>';
	var lang_gen_advanced_logbook_help = '<?= __("Advanced Logbook Help"); ?>';
    <?php
    echo "var homegrid ='" . strtoupper($homegrid[0]) . "';";
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
                <div class="filterbody collapse">
                    <div class="row">
                        <div <?php if (($options->datetime->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="dateFrom"><?= __("From") . ": " ?></label>
                            <input name="dateFrom" id="dateFrom" type="date" class="form-control form-control-sm w-auto">
                        </div>
                        <div <?php if (($options->datetime->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="dateTo"><?= __("To") . ": " ?></label>
                            <input name="dateTo" id="dateTo" type="date" class="form-control form-control-sm w-auto">
                        </div>
                        <div <?php if (($options->dx->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="dx"><?= __("Dx"); ?></label>
                            <input onclick="this.select()" type="text" name="dx" id="dx" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
                        <div <?php if (($options->dxcc->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="dxcc"><?= __("DXCC"); ?></label>
                            <select class="form-select form-select-sm" id="dxcc" name="dxcc">
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
                            <input onclick="this.select()" type="text" name="state" id="state" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
                        <div <?php if (($options->gridsquare->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="gridsquare"><?= __("Gridsquare"); ?></label>
                            <input onclick="this.select()" type="text" name="gridsquare" id="gridsquare" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
                        <div <?php if (($options->mode->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="mode"><?= __("Mode"); ?></label>
                            <select id="mode" name="mode" class="form-select form-select-sm">
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
                            <select id="band" name="band" class="form-select form-select-sm">
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
                            <select class="form-select form-select-sm" id="sats" name="sats">
                                <option value="All"><?= __("All"); ?></option>
                                <?php foreach ($sats as $sat) {
                                    echo '<option value="' . htmlentities($sat) . '"' . '>' . htmlentities($sat) . '</option>' . "\n";
                                } ?>
                            </select>
                        </div>
                        <div hidden class="orbits_dropdown mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="orbits"><?= __("Orbit"); ?></label>
                            <select class="form-select form-select-sm" id="orbits" name="orbits">
                                <option value="All"><?= __("All"); ?></option>
                                <?php foreach ($orbits as $orbit) {
                                    echo '<option value="' . htmlentities($orbit) . '"' . '>' . htmlentities($orbit) . '</option>' . "\n";
                                } ?>
                            </select>
                        </div>
                        <div <?php if (($options->propagation->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="selectPropagation"><?= __("Propagation"); ?></label>
                            <select id="selectPropagation" class="form-select form-select-sm" name="propmode">
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
                            <select id="cqzone" name="cqzone" class="form-select form-select-sm">
                                <option value="All"><?= __("All"); ?></option>
                                <option value=""><?= __("Empty"); ?></option>
                                <?php
                                for ($i = 1; $i <= 40; $i++) {
                                    echo '<option value="' . $i . '">' . $i . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div <?php if (($options->ituzone->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="ituzone"><?= __("ITU Zone"); ?></label>
                            <select id="ituzone" name="ituzone" class="form-select form-select-sm">
                                <option value="All"><?= __("All"); ?></option>
                                <option value=""><?= __("Empty"); ?></option>
                                <?php
                                for ($i = 1; $i <= 90; $i++) {
                                    echo '<option value="' . $i . '">' . $i . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
						<div <?php if (($options->county->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="county"><?= __("County"); ?></label>
                            <input onclick="this.select()" type="text" name="county" id="county" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
						<div <?php if (($options->dok->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="dok"><?= __("DOK"); ?></label>
                            <input onclick="this.select()" type="text" name="dok" id="dok" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
                        <div <?php if (($options->sota->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="sota"><?= __("SOTA"); ?></label>
                            <input onclick="this.select()" type="text" name="sota" id="sota" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
                        <div <?php if (($options->pota->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="pota"><?= __("POTA"); ?></label>
                            <input onclick="this.select()" type="text" name="pota" id="pota" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
                        <div <?php if (($options->iota->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="iota"><?= __("IOTA"); ?></label>
                            <select class="form-select form-select-sm" id="iota" name="iota">
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
                            <input onclick="this.select()" type="text" name="wwff" id="wwff" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
                        <div <?php if (($options->operator->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="operator"><?= __("Operator"); ?></label>
                            <input onclick="this.select()" type="text" name="operator" id="operator" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>

                        <div <?php if (($options->contest->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="contest"><?= __("Contest"); ?></label>
                            <input onclick="this.select()" type="text" name="contest" id="contest" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>

						<div <?php if (($options->continent->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="continent"><?= __("Continent"); ?></label>
							<select id="continent" name="continent" class="form-select form-select-sm">
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

						<div <?php if (($options->comment->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                            <label class="form-label" for="comment"><?= __("Comment"); ?></label>
                            <input onclick="this.select()" type="text" name="comment" id="comment" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                        </div>
                    </div>
                </div>
        </div>
        <div class="qslfilterbody collapse">
            <div class="row">
                <div  <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                    <label for="qslSent"><?= __("QSL sent"); ?></label>
                    <select id="qslSent" name="qslSent" class="form-select form-select-sm">
                        <option value=""><?= __("All"); ?></option>
                        <option value="Y"><?= __("Yes"); ?></option>
                        <option value="N"><?= __("No"); ?></option>
                        <option value="R"><?= __("Requested"); ?></option>
                        <option value="Q"><?= __("Queued"); ?></option>
                        <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                    </select>
                </div>
                <div  <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                    <label for="qslReceived"><?= __("QSL received"); ?></label>
                    <select id="qslReceived" name="qslReceived" class="form-select form-select-sm">
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
                    <select id="qslSentMethod" name="qslSentMethod" class="form-select form-select-sm">
                        <option value=""><?= __("All"); ?></option>
                        <option value="B"><?= __("Bureau"); ?></option>
                        <option value="D"><?= __("Direct"); ?></option>
                        <option value="E"><?= __("Electronic"); ?></option>
                        <option value="M"><?= __("Manager"); ?></option>
                    </select>
                </div>
                <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                    <label for="qslReceivedMethod"><?= __("QSL recv. method"); ?></label>
                    <select id="qslReceivedMethod" name="qslReceivedMethod" class="form-select form-select-sm">
                        <option value=""><?= __("All"); ?></option>
                        <option value="B"><?= __("Bureau"); ?></option>
                        <option value="D"><?= __("Direct"); ?></option>
                        <option value="E"><?= __("Electronic"); ?></option>
                        <option value="M"><?= __("Manager"); ?></option>
                    </select>
                </div>
                <div <?php if (($options->lotw->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                    <label for="lotwSent"><?= __("LoTW sent"); ?></label>
                    <select id="lotwSent" name="lotwSent" class="form-select form-select-sm">
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
                    <select id="lotwReceived" name="lotwReceived" class="form-select form-select-sm">
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
                    <select id="clublogSent" name="clublogSent" class="form-select form-select-sm">
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
                    <select id="clublogReceived" name="clublogReceived" class="form-select form-select-sm">
                        <option value=""><?= __("All"); ?></option>
                        <option value="Y"><?= __("Yes"); ?></option>
                        <option value="N"><?= __("No"); ?></option>
                        <option value="R"><?= __("Requested"); ?></option>
                        <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                        <option value="V"><?= __("Verified"); ?></option>
                    </select>
                </div>
                <div <?php if (($options->eqsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                    <label for="eqslSent"><?= __("eQSL sent"); ?></label>
                    <select id="eqslSent" name="eqslSent" class="form-select form-select-sm">
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
                    <select id="eqslReceived" name="eqslReceived" class="form-select form-select-sm">
                        <option value=""><?= __("All"); ?></option>
                        <option value="Y"><?= __("Yes"); ?></option>
                        <option value="N"><?= __("No"); ?></option>
                        <option value="R"><?= __("Requested"); ?></option>
                        <option value="I"><?= __("Invalid (Ignore)"); ?></option>
                        <option value="V"><?= __("Verified"); ?></option>
                    </select>
                </div>
                <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                    <label for="qslvia"><?= __("QSL via"); ?></label>
                    <input onclick="this.select()" type="search" name="qslvia" class="form-control form-control-sm" value="*" placeholder="<?= __("Empty"); ?>">
                </div>
                <div <?php if (($options->qsl->show ?? "true") == "false") { echo 'style="display:none"'; } ?> class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                    <label for="qslimages"><?= __("QSL Images"); ?></label>
                    <select class="form-select form-select-sm" id="qslimages" name="qslimages">
                        <option value="">-</option>
                        <option value="Y"><?= __("Yes"); ?></option>
                        <option value="N"><?= __("No"); ?></option>
                    </select>
                </div>
            </div>
        </div>
		<?php if(clubaccess_check(9)) { ?>
        <div class="actionbody collapse">
            <script>
                var lang_filter_actions_delete_warning = '<?= __("Warning! Are you sure you want to delete the marked QSO(s)?"); ?>';
                var lang_filter_actions_delete_warning_details = '<?= __(" QSO(s) will be deleted"); ?>';
            </script>
            <div class="mb-2 btn-group">
                <span class="h6 me-1"><?= __("With selected: "); ?></span>
                <button type="button" class="btn btn-sm btn-primary me-1" id="btnUpdateFromCallbook"><?= __("Update from Callbook"); ?></button>
                <button type="button" class="btn btn-sm btn-primary me-1" id="queueBureau"><?= __("Queue Bureau"); ?></button>
                <button type="button" class="btn btn-sm btn-primary me-1" id="queueDirect"><?= __("Queue Direct"); ?></button>
                <button type="button" class="btn btn-sm btn-primary me-1" id="queueElectronic"><?= __("Queue Electronic"); ?></button>
                <button type="button" class="btn btn-sm btn-success me-1" id="sentBureau"><?= __("Sent (Bureau)"); ?></button>
                <button type="button" class="btn btn-sm btn-success me-1" id="sentDirect"><?= __("Sent (Direct)"); ?></button>
                <button type="button" class="btn btn-sm btn-success me-1" id="sentElectronic"><?= __("Sent (Electronic)"); ?></button>
                <button type="button" class="btn btn-sm btn-danger me-1" id="dontSend"><?= __("Not Sent"); ?></button>
                <button type="button" class="btn btn-sm btn-danger me-1" id="notRequired"><?= __("QSL Not Required"); ?></button>
                <button type="button" class="btn btn-sm btn-danger me-1" id="notReceived"><?= __("Not Received"); ?></button>
                <button type="button" class="btn btn-sm btn-warning me-1" id="receivedBureau"><?= __("Received (Bureau)"); ?></button>
                <button type="button" class="btn btn-sm btn-warning me-1" id="receivedDirect"><?= __("Received (Direct)"); ?></button>
                <button type="button" class="btn btn-sm btn-warning me-1" id="receivedElectronic"><?= __("Received (Electronic)"); ?></button>
                <button type="button" class="btn btn-sm btn-info me-1" id="exportAdif"><?= __("Create ADIF"); ?></button>
                <button type="button" class="btn btn-sm btn-info me-1" id="printLabel"><?= __("Print Label"); ?></button>
                <button type="button" class="btn btn-sm btn-info me-1" id="qslSlideshow"><?= __("QSL Slideshow"); ?></button>
				<button type="button" class="btn btn-sm btn-success me-1" id="fixCqZones"><?= __("Fix CQ Zones"); ?></button>
				<button type="button" class="btn btn-sm btn-success me-1" id="fixItuZones"><?= __("Fix ITU Zones"); ?></button>
            </div>
        </div>
		<?php } ?>
        <div class="quickfilterbody collapse">
            <div class="mb-2 btn-group">
                <span class="h6 me-1"><?= __("Quicksearch with selected: "); ?></span>
				<?php if (($options->datetime->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchDate"><?= __("Search Date"); ?></button><?php
                                                                                                                                    } ?>
                <?php if (($options->dx->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchCallsign"><?= __("Search Callsign"); ?></button><?php
                                                                                                                                    } ?>
                <?php if (($options->dxcc->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchDxcc"><?= __("Search DXCC"); ?></button><?php
                                                                                                                            } ?>
                <?php if (($options->state->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchState"><?= __("Search State"); ?></button><?php
                                                                                                                                } ?>
                <?php if (($options->gridsquare->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchGridsquare"><?= __("Search Gridsquare"); ?></button><?php
                                                                                                                                        } ?>
                <?php if (($options->cqzone->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchCqZone"><?= __("Search CQ Zone"); ?></button><?php
                                                                                                                                    } ?>
                <?php if (($options->ituzone->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchItuZone"><?= __("Search ITU Zone"); ?></button><?php
                                                                                                                                    } ?>
                <?php if (($options->mode->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchMode"><?= __("Search Mode"); ?></button><?php
                                                                                                                            } ?>
                <?php if (($options->band->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchBand"><?= __("Search Band"); ?></button><?php
                                                                                                                            } ?>
                <?php if (($options->iota->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchIota"><?= __("Search IOTA"); ?></button><?php
                                                                                                                            } ?>
                <?php if (($options->sota->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchSota"><?= __("Search SOTA"); ?></button><?php
                                                                                                                            } ?>
                <?php if (($options->pota->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchPota"><?= __("Search POTA"); ?></button><?php
                                                                                                                            } ?>
                <?php if (($options->wwff->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchWwff"><?= __("Search WWFF"); ?></button><?php
                                                                                                                            } ?>
                <?php if (($options->operator->show ?? "true") == "true") { ?>
                    <button type="button" class="btn btn-sm btn-primary me-1" id="searchOperator"><?= __("Search Operator"); ?></button><?php
                                                                                                                                    } ?>
            </div>
        </div>
        <div class="row pt-2">
			<div class="d-flex flex-wrap btn-group w-auto mx-auto">
				<button type="button" class="btn btn-sm btn-primary me-1 mb-2 lba_buttons flex-grow-0" data-bs-toggle="collapse" data-bs-target=".quickfilterbody" style="white-space: nowrap;">
					<i class="fas fa-filter"></i> <?= __("Quickfilters"); ?>
				</button>
				<button type="button" class="btn btn-sm btn-primary me-1 lba_buttons flex-grow-0 mb-2" data-bs-toggle="collapse" data-bs-target=".qslfilterbody" style="white-space: nowrap;">
					<i class="fas fa-filter"></i> <?= __("QSL Filters"); ?>
				</button>
				<button type="button" class="btn btn-sm btn-primary me-1 lba_buttons flex-grow-0 mb-2" data-bs-toggle="collapse" data-bs-target=".filterbody" style="white-space: nowrap;">
					<i class="fas fa-filter"></i> <?= __("Filters"); ?>
				</button>
				<?php if(clubaccess_check(9)) { ?>
				<button type="button" class="btn btn-sm btn-success lba_buttons me-1 flex-grow-0 mb-2" data-bs-toggle="collapse" data-bs-target=".actionbody" style="white-space: nowrap;">
					<i class="fas fa-tasks"></i> <?= __("Actions"); ?>
				</button>
				<?php } ?>
				<label for="qsoResults" class="me-2" style="white-space: nowrap;"><?= __("# Results"); ?></label>
				<select id="qsoResults" name="qsoresults" class="form-select form-select-sm me-2 w-auto">
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
