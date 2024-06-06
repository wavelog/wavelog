<script type="text/javascript">
    /*
     * Custom user settings
     */
    var custom_date_format = "<?php echo $custom_date_format ?>";
    let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');

    var lang_gen_hamradio_latitude = '<?php echo __("Latitude"); ?>';
    var lang_gen_hamradio_longitude = '<?php echo __("Longitude"); ?>';
    var lang_gen_hamradio_gridsquare = '<?php echo __("Gridsquare"); ?>';
    var lang_gen_hamradio_distance = '<?php echo __("Distance"); ?>';
    var lang_gen_hamradio_bearing = '<?php echo __("Bearing"); ?>';
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
            \"qslmsg\":{\"show\":\"true\"},
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
			\"sig\":{\"show\":\"true\"}
        }";
    }
    $current_opts = json_decode($options);
    echo "var user_options = $options;";
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
	if (!isset($current_opts->sig)) {
        echo "\nvar o_template = { sig: {show: 'true'}};";
        echo "\nuser_options={...user_options, ...o_template};";
    }


    foreach ($mapoptions as $mo) {
        if ($mo != null) {
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

        <form id="searchForm" name="searchForm" action="<?php echo base_url()."index.php/logbookadvanced/search";?>" method="post">
            <input type="hidden" id="dupes" name="dupes" value="">
            <div class="filterbody collapse">
                <div class="row">
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="dateFrom"><?php echo __("From") . ": " ?></label>
                        <input name="dateFrom" id="dateFrom" type="date" class="form-control form-control-sm w-auto">
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="dateTo"><?php echo __("To") . ": " ?></label>
                        <input name="dateTo" id="dateTo" type="date" class="form-control form-control-sm w-auto">
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="dx"><?php echo __("Dx"); ?></label>
                        <input type="text" name="dx" id="dx" class="form-control form-control-sm" value="">
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="dxcc"><?php echo __("DXCC"); ?></label>
                        <select class="form-select form-select-sm" id="dxcc" name="dxcc">
                            <option value="">-</option>
                            <option value="0"><?php echo __("- NONE - (e.g. /MM, /AM)"); ?></option>
                            <?php
					foreach($dxccarray as $dxcc){
						echo '<option value=' . $dxcc->adif;
						echo '>' . $dxcc->prefix . ' - ' . ucwords(strtolower($dxcc->name), "- (/");
						if ($dxcc->Enddate != null) {
							echo ' - (Deleted DXCC)';
						}
						echo '</option>';
					}
					?>
                        </select>
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="iota"><?php echo __("IOTA"); ?></label>
                        <select class="form-select form-select-sm" id="iota" name="iota">
                            <option value="">-</option>
                            <?php
					foreach($iotaarray as $iota){
						echo '<option value=' . $iota->tag;
						echo '>' . $iota->tag . ' - ' . $iota->name . '</option>';
					}
					?>
                        </select>
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="state"><?php echo __("State"); ?></label>
                        <input type="text" name="state" id="state" class="form-control form-control-sm" value="">
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="gridsquare"><?php echo __("Gridsquare"); ?></label>
                        <input type="text" name="gridsquare" id="gridsquare" class="form-control form-control-sm" value="">
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="mode"><?php echo __("Mode"); ?></label>
                        <select id="mode" name="mode" class="form-select form-select-sm">
                            <option value=""><?php echo __("All"); ?></option>
                            <?php
					foreach($modes as $modeId => $mode){
						?><option value="<?php echo htmlspecialchars($mode);?>"><?php echo htmlspecialchars($mode);?></option><?php
					}
					?>
                        </select>
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="band"><?php echo __("Band"); ?></label>
                        <select id="band" name="band" class="form-select form-select-sm">
                            <option value=""><?php echo __("All"); ?></option>
                            <?php
					foreach($bands as $band){
						?><option value="<?php echo htmlentities($band);?>"><?php echo htmlspecialchars($band);?></option><?php
					}
					?>
                        </select>
                    </div>
                    <div hidden class="sats_dropdown mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="sats"><?php echo __("Satellite"); ?></label>
                        <select class="form-select form-select-sm" id="sats" name="sats">
                            <option value="All"><?php echo __("All"); ?></option>
                            <?php foreach($sats as $sat) {
					echo '<option value="' . htmlentities($sat) . '"' . '>' . htmlentities($sat) . '</option>'."\n";
				} ?>
                        </select>
                    </div>
                    <div hidden class="orbits_dropdown mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="orbits"><?php echo __("Orbit"); ?></label>
                        <select class="form-select form-select-sm" id="orbits" name="orbits">
                            <option value="All"><?php echo __("All"); ?></option>
                            <?php foreach($orbits as $orbit) {
					echo '<option value="' . htmlentities($orbit) . '"' . '>' . htmlentities($orbit) . '</option>'."\n";
				} ?>
                        </select>
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="selectPropagation"><?php echo __("Propagation"); ?></label>
                        <select id="selectPropagation" class="form-select form-select-sm" name="propmode">
                            <option value=""><?php echo __("All"); ?></option>
                            <option value="AS">Aircraft Scatter</option>
                            <option value="AUR">Aurora</option>
                            <option value="AUE">Aurora-E</option>
                            <option value="BS">Back scatter</option>
                            <option value="ECH">EchoLink</option>
                            <option value="EME">Earth-Moon-Earth</option>
                            <option value="ES">Sporadic E</option>
                            <option value="FAI">Field Aligned Irregularities</option>
                            <option value="F2">F2 Reflection</option>
                            <option value="INTERNET">Internet-assisted</option>
                            <option value="ION">Ionoscatter</option>
                            <option value="IRL">IRLP</option>
                            <option value="MS">Meteor scatter</option>
                            <option value="RPT">Terrestrial or atmospheric repeater or transponder</option>
                            <option value="RS">Rain scatter</option>
                            <option value="SAT">Satellite</option>
                            <option value="TEP">Trans-equatorial</option>
                            <option value="TR">Tropospheric ducting</option>
                        </select>
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="cqzone">CQ Zone</label>
                        <select id="cqzone" name="cqzone" class="form-select form-select-sm">
                            <option value=""><?php echo __("All"); ?></option>
                            <?php
								for ($i = 1; $i<=40; $i++) {
									echo '<option value="'. $i . '">'. $i .'</option>';
								}
							?>
                        </select>
                    </div>
					<div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="ituzone">ITU Zone</label>
                        <select id="ituzone" name="ituzone" class="form-select form-select-sm">
                            <option value=""><?php echo __("All"); ?></option>
                            <?php
							for ($i = 1; $i<=90; $i++) {
								echo '<option value="'. $i . '">'. $i .'</option>';
							}
							?>
                        </select>
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="sota"><?php echo __("SOTA"); ?></label>
                        <input type="text" name="sota" id="sota" class="form-control form-control-sm" value="">
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="wwff"><?php echo __("WWFF"); ?></label>
                        <input type="text" name="wwff" id="wwff" class="form-control form-control-sm" value="">
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="pota"><?php echo __("POTA"); ?></label>
                        <input type="text" name="pota" id="pota" class="form-control form-control-sm" value="">
                    </div>
                    <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                        <label class="form-label" for="operator"><?php echo __("Operator"); ?></label>
                        <input type="text" name="operator" id="operator" class="form-control form-control-sm" value="">
                    </div>
                </div>
            </div>
    </div>
    <div class="qslfilterbody collapse">
        <div class="row">
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="qslSent"><?php echo __("QSL sent"); ?></label>
                <select id="qslSent" name="qslSent" class="form-select form-select-sm">
                    <option value=""><?php echo __("All"); ?></option>
                    <option value="Y"><?php echo __("Yes"); ?></option>
                    <option value="N"><?php echo __("No"); ?></option>
                    <option value="R"><?php echo __("Requested"); ?></option>
                    <option value="Q"><?php echo __("Queued"); ?></option>
                    <option value="I"><?php echo __("Invalid (Ignore)"); ?></option>
                </select>
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="qslReceived"><?php echo __("QSL received"); ?></label>
                <select id="qslReceived" name="qslReceived" class="form-select form-select-sm">
                    <option value=""><?php echo __("All"); ?></option>
                    <option value="Y"><?php echo __("Yes"); ?></option>
                    <option value="N"><?php echo __("No"); ?></option>
                    <option value="R"><?php echo __("Requested"); ?></option>
                    <option value="I"><?php echo __("Invalid (Ignore)"); ?></option>
                    <option value="V"><?php echo __("Verified"); ?></option>
                </select>
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="qslSentMethod"><?php echo __("QSL send. method"); ?></label>
                <select id="qslSentMethod" name="qslSentMethod" class="form-select form-select-sm">
                    <option value=""><?php echo __("All"); ?></option>
                    <option value="B"><?php echo __("Bureau"); ?></option>
                    <option value="D"><?php echo __("Direct"); ?></option>
                    <option value="E"><?php echo __("Electronic"); ?></option>
                    <option value="M"><?php echo __("Manager"); ?></option>
                </select>
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="qslReceivedMethod"><?php echo __("QSL recv. method"); ?></label>
                <select id="qslReceivedMethod" name="qslReceivedMethod" class="form-select form-select-sm">
                    <option value=""><?php echo __("All"); ?></option>
                    <option value="B"><?php echo __("Bureau"); ?></option>
                    <option value="D"><?php echo __("Direct"); ?></option>
                    <option value="E"><?php echo __("Electronic"); ?></option>
                    <option value="M"><?php echo __("Manager"); ?></option>
                </select>
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="lotwSent"><?php echo __("LoTW sent"); ?></label>
                <select id="lotwSent" name="lotwSent" class="form-select form-select-sm">
                    <option value=""><?php echo __("All"); ?></option>
                    <option value="Y"><?php echo __("Yes"); ?></option>
                    <option value="N"><?php echo __("No"); ?></option>
                    <option value="R"><?php echo __("Requested"); ?></option>
                    <option value="Q"><?php echo __("Queued"); ?></option>
                    <option value="I"><?php echo __("Invalid (Ignore)"); ?></option>
                </select>
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="lotwReceived"><?php echo __("LoTW received"); ?></label>
                <select id="lotwReceived" name="lotwReceived" class="form-select form-select-sm">
                    <option value=""><?php echo __("All"); ?></option>
                    <option value="Y"><?php echo __("Yes"); ?></option>
                    <option value="N"><?php echo __("No"); ?></option>
                    <option value="R"><?php echo __("Requested"); ?></option>
                    <option value="I"><?php echo __("Invalid (Ignore)"); ?></option>
                    <option value="V"><?php echo __("Verified"); ?></option>
                </select>
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="eqslSent"><?php echo __("eQSL sent"); ?></label>
                <select id="eqslSent" name="eqslSent" class="form-select form-select-sm">
                    <option value=""><?php echo __("All"); ?></option>
                    <option value="Y"><?php echo __("Yes"); ?></option>
                    <option value="N"><?php echo __("No"); ?></option>
                    <option value="R"><?php echo __("Requested"); ?></option>
                    <option value="Q"><?php echo __("Queued"); ?></option>
                    <option value="I"><?php echo __("Invalid (Ignore)"); ?></option>
                </select>
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="eqslReceived"><?php echo __("eQSL received"); ?></label>
                <select id="eqslReceived" name="eqslReceived" class="form-select form-select-sm">
                    <option value=""><?php echo __("All"); ?></option>
                    <option value="Y"><?php echo __("Yes"); ?></option>
                    <option value="N"><?php echo __("No"); ?></option>
                    <option value="R"><?php echo __("Requested"); ?></option>
                    <option value="I"><?php echo __("Invalid (Ignore)"); ?></option>
                    <option value="V"><?php echo __("Verified"); ?></option>
                </select>
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="qslvia"><?php echo __("QSL via"); ?></label>
                <input type="search" name="qslvia" class="form-control form-control-sm">
            </div>
            <div class="mb-3 col-lg-2 col-md-2 col-sm-3 col-xl">
                <label for="qslimages"><?php echo __("QSL Images"); ?></label>
                <select class="form-select form-select-sm" id="qslimages" name="qslimages">
                    <option value="">-</option>
                    <option value="Y"><?php echo __("Yes"); ?></option>
                    <option value="N"><?php echo __("No"); ?></option>
                </select>
            </div>
        </div>
    </div>

    <div class="actionbody collapse">
        <script>
            var lang_filter_actions_delete_warning = '<?php echo __("Warning! Are you sure you want to delete the marked QSO(s)?"); ?>';
        </script>
        <div class="mb-2 btn-group">
            <span class="h6 me-1"><?php echo __("With selected: "); ?></span>
            <button type="button" class="btn btn-sm btn-primary me-1" id="btnUpdateFromCallbook"><?php echo __("Update from Callbook"); ?></button>
            <button type="button" class="btn btn-sm btn-primary me-1" id="queueBureau"><?php echo __("Queue Bureau"); ?></button>
            <button type="button" class="btn btn-sm btn-primary me-1" id="queueDirect"><?php echo __("Queue Direct"); ?></button>
            <button type="button" class="btn btn-sm btn-primary me-1" id="queueElectronic"><?php echo __("Queue Electronic"); ?></button>
            <button type="button" class="btn btn-sm btn-success me-1" id="sentBureau"><?php echo __("Sent (Bureau)"); ?></button>
            <button type="button" class="btn btn-sm btn-success me-1" id="sentDirect"><?php echo __("Sent (Direct)"); ?></button>
            <button type="button" class="btn btn-sm btn-success me-1" id="sentElectronic"><?php echo __("Sent (Electronic)"); ?></button>
            <button type="button" class="btn btn-sm btn-danger me-1" id="dontSend"><?php echo __("Not Sent"); ?></button>
            <button type="button" class="btn btn-sm btn-danger me-1" id="notRequired"><?php echo __("QSL Not Required"); ?></button>
            <button type="button" class="btn btn-sm btn-danger me-1" id="notReceived"><?php echo __("Not Received"); ?></button>
            <button type="button" class="btn btn-sm btn-warning me-1" id="receivedBureau"><?php echo __("Received (Bureau)"); ?></button>
            <button type="button" class="btn btn-sm btn-warning me-1" id="receivedDirect"><?php echo __("Received (Direct)"); ?></button>
            <button type="button" class="btn btn-sm btn-warning me-1" id="receivedElectronic"><?php echo __("Received (Electronic)"); ?></button>
            <button type="button" class="btn btn-sm btn-info me-1" id="exportAdif"><?php echo __("Create ADIF"); ?></button>
            <button type="button" class="btn btn-sm btn-info me-1" id="printLabel"><?php echo __("Print Label"); ?></button>
            <button type="button" class="btn btn-sm btn-info me-1" id="qslSlideshow"><?php echo __("QSL Slideshow"); ?></button>
        </div>
    </div>
    <div class="quickfilterbody collapse">
        <div class="mb-2 btn-group">
            <span class="h6 me-1"><?php echo __("Quicksearch with selected: "); ?></span>
			<?php if (($options->dx->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchCallsign"><?php echo __("Search Callsign"); ?></button><?php
            } ?>
			<?php if (($options->dxcc->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchDxcc"><?php echo __("Search DXCC"); ?></button><?php
            } ?>
			<?php if (($options->state->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchState"><?php echo __("Search State"); ?></button><?php
            } ?>
			<?php if (($options->gridsquare->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchGridsquare"><?php echo __("Search Gridsquare"); ?></button><?php
            } ?>
			<?php if (($options->cqzone->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchCqZone"><?php echo __("Search CQ Zone"); ?></button><?php
            } ?>
			<?php if (($options->ituzone->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchItuZone"><?php echo __("Search ITU Zone"); ?></button><?php
            } ?>
			<?php if (($options->mode->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchMode"><?php echo __("Search Mode"); ?></button><?php
            } ?>
			<?php if (($options->band->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchBand"><?php echo __("Search Band"); ?></button><?php
            } ?>
            <?php if (($options->iota->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchIota"><?php echo __("Search IOTA"); ?></button><?php
            } ?>
			<?php if (($options->sota->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchSota"><?php echo __("Search SOTA"); ?></button><?php
            } ?>
            <?php if (($options->pota->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchPota"><?php echo __("Search POTA"); ?></button><?php
            } ?>
            <?php if (($options->wwff->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchWwff"><?php echo __("Search WWFF"); ?></button><?php
            } ?>
            <?php if (($options->operator->show ?? "true") == "true") { ?>
                <button type="button" class="btn btn-sm btn-primary me-1" id="searchOperator"><?php echo __("Search Operator"); ?></button><?php
            } ?>
        </div>
    </div>
<div class="row pt-2">
    <div class="mb-3 d-flex align-items-center col-lg d-flex flex-row justify-content-center align-items-center">
        <button type="button" class="btn btn-sm btn-primary me-1 lba_buttons" data-bs-toggle="collapse"
            data-bs-target=".quickfilterbody"><?php echo __("Quickfilters"); ?></button>
        <button type="button" class="btn btn-sm btn-primary me-1 lba_buttons" data-bs-toggle="collapse"
            data-bs-target=".qslfilterbody"><?php echo __("QSL Filters"); ?></button>
        <button type="button" class="btn btn-sm btn-primary me-1 lba_buttons" data-bs-toggle="collapse"
            data-bs-target=".filterbody"><?php echo __("Filters"); ?></button>
        <button type="button" class="btn btn-sm btn-primary me-1 lba_buttons" data-bs-toggle="collapse"
            data-bs-target=".actionbody"><?php echo __("Actions"); ?></button>
        <label for="qsoResults" class="me-2"><?php echo __("# Results"); ?></label>
        <select id="qsoResults" name="qsoresults" class="form-select form-select-sm me-2 w-auto">
            <option value="250">250</option>
            <option value="1000">1000</option>
            <option value="2500">2500</option>
            <option value="5000">5000</option>
        </select>
		<label class="me-2" for="de"><?php echo __("Location"); ?></label>
		<select id="de" name="de" class="form-select form-select-sm me-2 w-auto">
			<option value="All"><?php echo __("All"); ?></option>
			<?php foreach ($station_profile->result() as $station) { ?>
				<option value="<?php echo $station->station_id; ?>"
				<?php if ($station->station_id == $active_station_id) {
					echo " selected =\"selected\"";
                } ?>>
				<?php echo __("Callsign") . ": " ?>
				<?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name; ?>)
			</option>
			<?php } ?>
		</select>
        <button type="submit" class="btn btn-sm btn-primary me-1 ld-ext-right" id="searchButton"><?php echo __("Search"); ?><div class="ld ld-ring ld-spin"></div></button>
        <button type="button" class="btn btn-sm btn-primary me-1 ld-ext-right" id="dupeButton"><?php echo __("Dupes"); ?><div class="ld ld-ring ld-spin"></div></button>
        <button type="button" class="btn btn-sm btn-primary me-1 ld-ext-right" id="editButton">Edit<div class="ld ld-ring ld-spin"></div></button>
		<button type="button" class="btn btn-sm btn-danger me-1" id="deleteQsos"><?php echo __("Delete"); ?></button>
		<div class="btn-group me-1" role="group">
            <button type="button" class="btn btn-sm btn-primary ld-ext-right" id="mapButton" onclick="mapQsos(this.form);"><?php echo __("Map"); ?><div class="ld ld-ring ld-spin"></div></button>
			<button id="btnGroupDrop1" type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
			<ul class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                <li><button type="button" class="dropdown-item" onclick="mapGlobeQsos(this.form);" id="mapGlobeButton">Globe map</button></li>
            </ul>
        </div>
		<button type="options" class="btn btn-sm btn-primary me-1" id="optionButton"><?php echo __("Options"); ?></button>
		<button type="reset" class="btn btn-sm btn-danger me-1" id="resetButton"><?php echo __("Reset"); ?></button>

            </div>
        </div>
        </form>
        <table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center" id="qsoList">
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
					<?php if (($options->gridsquare->show ?? "true") == "true") {
                        echo '<th>Gridsquare</th>';
                    } ?>
                    <?php if (($options->name->show ?? "true") == "true") {
                        echo '<th>' . __("Name") . '</th>';
                    } ?>
                    <?php if (($options->qslvia->show ?? "true") == "true") {
                        echo '<th>' . __("QSL via") . '</th>';
                    } ?>
                    <?php if (($options->qsl->show ?? "true") == "true") {
                        echo '<th>' . __("QSL") . '</th>';
                    } ?>
                    <?php if ($this->session->userdata('user_eqsl_name') != ""  && ($options->eqsl->show ?? "true") == "true") {
                        echo '<th class="eqslconfirmation">eQSL</th>';
                    } ?>
                    <?php if ($this->session->userdata('user_lotw_name') != "" && ($options->lotw->show ?? "true") == "true") {
                        echo '<th class="lotwconfirmation">LoTW</th>';
                    } ?>
                    <?php if (($options->qslmsg->show ?? "true") == "true") {
                        echo '<th>' . __("QSL Msg") . '</th>';
                    } ?>
                    <?php if (($options->dxcc->show ?? "true") == "true") {
                        echo '<th>' . __("DXCC") . '</th>';
                    } ?>
                    <?php if (($options->state->show ?? "true") == "true") {
                        echo '<th>' . __("State") . '</th>';
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
                        echo '<th>Contest</th>';
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
					<?php if (($options->myrefs->show ?? "true") == "true") {
                        echo '<th>' . __("My Refs") . '</th>';
                    } ?>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>
