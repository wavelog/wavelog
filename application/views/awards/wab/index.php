<div class="container">
<script>
	var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
	var attributionInfo='<?php echo $this->optionslib->get_option('option_map_tile_server_copyright'); ?>';
</script>

    <!-- Award Info Box -->
    <br>
    <div id="awardInfoButton">
        <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("WAB - Worked All Britain Award"); ?>";
            var lang_award_info_ln2 = "<?= __("WAB, Worked All Britain squares in Amateur Radio, encourages licensed ham radio operators to work all the squares in Great Britain."); ?>";
            var lang_award_info_ln3 = "<?= __("May be claimed for having contacted an amateur station located in the required amount of squares, described on the page linked below."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://wab.intermip.net/default.php' target='_blank'>https://wab.intermip.net/default.php</a>"); ?>";
        </script>
        <h2><?php echo $page_title; ?></h2>
        <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
    </div>

<form class="d-flex align-items-center">
            <label class="my-1 me-2" for="band"><?= __("Band"); ?></label>
            <select class="form-select my-1 me-sm-2 w-auto"  id="band">
                <option value="All"><?= __("All")?></option>
                <?php foreach($bands as $band) {
                    echo '<option value="'.$band.'"';
                    if ($user_default_band == $band) {
                        echo ' selected="selected"';
                    }
                    echo '>'.$band.'</option>'."\n";
                } ?>
            </select>
            <?php if (count($sats_available) != 0) { ?>
                <label class="my-1 me-2" id="satslabel" for="sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?= __("Satellite"); ?></label>
                <select class="form-select my-1 me-sm-2 w-auto"  id="sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
                    <option value="All"><?= __("All")?></option>
                    <?php foreach($sats_available as $sat) {
                        echo '<option value="' . $sat . '"' . '>' . $sat . '</option>'."\n";
                    } ?>
                </select>
            <?php } else { ?>
                <input id="sats" type="hidden" value="All"></input>
            <?php } ?>
                <label class="my-1 me-2" id="orbitslabel" for="orbits" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?= __("Orbit"); ?></label>
                <select class="form-select my-1 me-sm-2 w-auto"  id="orbits" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
                    <option value="All"><?= __("All")?></option>
                    <?php
                    foreach($orbits as $orbit){
                        echo '<option value="' . $orbit . '">' . strtoupper($orbit) . '</option>'."\n";
                    }
                    ?>
            </select>
			<label class="my-1 me-2" for="mode"><?= __("Mode"); ?></label>
            <select class="form-select my-1 me-sm-2 w-auto"  id="mode">
			<option value="All"><?= __("All")?></option>
                    <?php
                    foreach($modes as $mode){
                        if ($mode->submode ?? '' == '') {
                            echo '<option value="' . $mode . '">' . strtoupper($mode) . '</option>'."\n";
                        }
                    }
                    ?>
            </select>
			<label class="my-1 me-2" for="qsl"><?= __("Confirmation"); ?></label>
                <div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" value="1" type="checkbox" name="qsl" id="qsl"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
                    </div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" value="1" type="checkbox" name="lotw" id="lotw"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
                    </div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" value="1" type="checkbox" name="eqsl" id="eqsl"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
                    </div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" value="1" type="checkbox" name="qrz" id="qrz"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
                    </div>
		    <div>
                     <?php echo '<input class="form-check-input" value="1" type="checkbox" name="clublog" id="clublog"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'C') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="clublog"><?= __("Clublog"); ?></label>
                    </div>
                </div>

            <button id="plot" type="button" name="plot" class="btn btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="plotmap()"><?= __("Map"); ?><div class="ld ld-ring ld-spin"></div></button>
			<button id="list" type="button" name="list" class="btn btn-primary me-1 ld-ext-right ld-ext-right-list" onclick="showlist()"><?= __("List"); ?><div class="ld ld-ring ld-spin"></div></button>
</form>
</div>
<div id="mapcontainer">
    <div id="wabmap" style="width: 100%; height: 85vh;"></div>
</div>
