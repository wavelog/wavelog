<div class="container">
<script>
	var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
	var attributionInfo='<?php echo $this->optionslib->get_option('option_map_tile_server_copyright'); ?>';
</script>

    <!-- Award Info Box -->
    <br>
    <div id="awardInfoButton">
        <script>
            var lang_awards_info_button = "<?php echo lang('awards_info_button'); ?>";
            var lang_award_info_ln1 = "<?php echo lang('awards_wab_description_ln1'); ?>";
            var lang_award_info_ln2 = "<?php echo lang('awards_wab_description_ln2'); ?>";
            var lang_award_info_ln3 = "<?php echo lang('awards_wab_description_ln3'); ?>";
            var lang_award_info_ln4 = "<?php echo lang('awards_wab_description_ln4'); ?>";
        </script>
        <h2><?php echo $page_title; ?></h2>
        <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?php echo lang('awards_info_button'); ?></button>
    </div>

<form class="d-flex align-items-center">
            <label class="my-1 me-2" for="band"><?php echo lang('gridsquares_band'); ?></label>
            <select class="form-select my-1 me-sm-2 w-auto"  id="band">
                <option value="All"><?php echo lang('general_word_all')?></option>
                <?php foreach($bands as $band) {
                    echo '<option value="'.$band.'"';
                    if ($user_default_band == $band) {
                        echo ' selected="selected"';
                    }
                    echo '>'.$band.'</option>'."\n";
                } ?>
            </select>
            <?php if (count($sats_available) != 0) { ?>
                <label class="my-1 me-2" id="satslabel" for="distplot_sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?php echo lang('gridsquares_sat'); ?></label>
                <select class="form-select my-1 me-sm-2 w-auto"  id="sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
                    <option value="All"><?php echo lang('general_word_all')?></option>
                    <?php foreach($sats_available as $sat) {
                        echo '<option value="' . $sat . '"' . '>' . $sat . '</option>'."\n";
                    } ?>
                </select>
            <?php } else { ?>
                <input id="sats" type="hidden" value="All"></input>
            <?php } ?>
                <label class="my-1 me-2" id="orbitslabel" for="orbits" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?php echo lang('gridsquares_orbit'); ?></label>
                <select class="form-select my-1 me-sm-2 w-auto"  id="orbits" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
                    <option value="All"><?php echo lang('general_word_all')?></option>
                    <?php
                    foreach($orbits as $orbit){
                        echo '<option value="' . $orbit . '">' . strtoupper($orbit) . '</option>'."\n";
                    }
                    ?>
            </select>
			<label class="my-1 me-2" for="mode"><?php echo lang('gridsquares_mode'); ?></label>
            <select class="form-select my-1 me-sm-2 w-auto"  id="mode">
			<option value="All"><?php echo lang('general_word_all')?></option>
                    <?php
                    foreach($modes as $mode){
                        if ($mode->submode ?? '' == '') {
                            echo '<option value="' . $mode . '">' . strtoupper($mode) . '</option>'."\n";
                        }
                    }
                    ?>
            </select>
			<label class="my-1 me-2"><?php echo lang('gridsquares_confirmation'); ?></label>
                <div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" type="checkbox" name="qsl" id="qsl"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="qsl">QSL</label>
                    </div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" type="checkbox" name="lotw" id="lotw"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="lotw">LoTW</label>
                    </div>
                    <div class="form-check-inline">
                    <?php echo '<input class="form-check-input" type="checkbox" name="eqsl" id="eqsl"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="eqsl">eQSL</label>
                    </div>
                    <div class="form-check-inline">
                     <?php echo '<input class="form-check-input" type="checkbox" name="qrz" id="qrz"';
                        if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
                            echo ' checked' ;
                        }
                        echo '>'; ?>
                        <label class="form-check-label" for="qrz">QRZ.com</label>
                    </div>
                </div>

            <button id="plot" type="button" name="plot" class="btn btn-primary me-1 ld-ext-right ld-ext-right-plot" onclick="plotmap()"><?php echo lang('gridsquares_button_plot'); ?><div class="ld ld-ring ld-spin"></div></button>
</form>
</div>

    <div id="wabmap" style="width: 100%; height: 85vh;"></div>
