<script>
    var lang_statistics_accumulated_worked_dxcc = '<?= __("Accumulated number of DXCCs worked"); ?>';
    var lang_statistics_accumulated_worked_states = '<?= __("Accumulated number of States worked"); ?>';
    var lang_statistics_accumulated_worked_iota = '<?= __("Accumulated number of IOTAs worked"); ?>';
    var lang_statistics_accumulated_worked_cqzone = '<?= __("Accumulated number of CQ Zones worked"); ?>';
    var lang_statistics_accumulated_worked_vucc = '<?= __("Accumulated number of VUCC Grids worked"); ?>';
    var lang_statistics_accumulated_worked_waja = '<?= __("Accumulated number of WAJA worked"); ?>';
    var lang_general_word_year = '<?= __("Year"); ?>';
    var lang_general_word_yearly = "<?= html_entity_decode(__("Yearly")); ?>";
    var lang_general_word_month = '<?= __("Month"); ?>';
    var lang_general_word_monthly = '<?= html_entity_decode(__("Monthly")); ?>';
    var lang_general_word_diff = '<?= __("Difference"); ?>';
</script>
<div class="container">
    <h2><?php echo $page_title; ?></h1>

        <form class="form">

            <!-- Select Basic -->
            <div class="mb-3 row">
                <label class="col-md-1 control-label" for="band"><?= __("Band"); ?></label>
                <div class="col-md-3">
                    <select id="band" name="band" class="form-select">
                        <option value="All"><?= __("All"); ?></option>
                        <?php foreach ($worked_bands as $band) {
                            echo '<option value="' . $band . '">' . $band . '</option>' . "\n";
                        } ?>
                    </select>
                </div>

                <label class="col-md-1 control-label" for="mode"><?= __("Mode"); ?></label>
                <div class="col-md-3">
                    <select id="mode" name="mode" class="form-select">
                        <option value="All"><?= __("All"); ?></option>
                        <?php
                        foreach ($modes->result() as $mode) {
                            if ($mode->submode == null) {
                                printf("<option value=\"%s\">%s</option>", $mode->mode, $mode->mode);
                            } else {
                                printf("<option value=\"%s\">&rArr; %s</option>", $mode->submode, $mode->submode);
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 row">

                <label class="col-md-1 control-label" for="radio"><?= __("Award"); ?></label>
                <div class="col-md-3">
				<select id="award" name="award" class="form-select">
                        <option value="dxcc"><?= __("DX Century Club (DXCC)"); ?></option>
                        <option value="was"><?= __("Worked All States (WAS)"); ?></option>
                        <option value="iota"><?= __("Islands On The Air (IOTA)"); ?></option>
                        <option value="waz"><?= __("Worked All Zones (WAZ)"); ?></option>
                        <option value="vucc"><?= __("VHF / UHF Century Club (VUCC)"); ?></option>
                        <option value="waja"><?= __("Worked All Japan (WAJA)"); ?></option>
                    </select>
                </div>

                <label class="col-md-1 control-label" for="radio"><?= __("Period"); ?></label>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="periodradio" id="yearly" value="year" checked>
                        <label class="form-check-label" for="yearly">
                            <?= __("Yearly"); ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="periodradio" id="monthly" value="month">
                        <label class="form-check-label" for="monthly">
                            <?= __("Monthly"); ?>
                        </label>
                    </div>
                </div>
            </div>

	    <div class="mb-4 row">
                <label class="col-md-1" for="propmode"><?= __("Propagation"); ?></label>
                <div class="col-md-3">
                    <select class="form-select w-auto" name="propmode" id="propmode">
                        <option value="0"<?php if (($propmode ?? '') == '0') { echo 'selected="selected"'; } ?>><?= __("All"); ?></option>
                        <option value="NoSAT"<?php if (($propmode ?? '') == 'NoSAT') { echo 'selected="selected"'; } ?>><?= __("All but SAT"); ?></option>
                        <option value="None"<?php if (($propmode ?? '') == 'None') { echo ' selected="selected"'; } ?>><?= __("None/Empty"); ?></option>
                        <option value="AS"<?php if (($propmode ?? '') == 'AS') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Aircraft Scatter"); ?></option>
                        <option value="AUR"<?php if (($propmode ?? '') == 'AUR') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Aurora"); ?></option>
                        <option value="AUE"<?php if (($propmode ?? '') == 'AUE') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Aurora-E"); ?></option>
                        <option value="BS"<?php if (($propmode ?? '') == 'BS') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Back scatter"); ?></option>
                        <option value="ECH"<?php if (($propmode ?? '') == 'ECH') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","EchoLink"); ?></option>
                        <option value="EME"<?php if (($propmode ?? '') == 'EME') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Earth-Moon-Earth"); ?></option>
                        <option value="ES"<?php if (($propmode ?? '') == 'ES') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Sporadic E"); ?></option>
                        <option value="FAI"<?php if (($propmode ?? '') == 'FAI') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Field Aligned Irregularities"); ?></option>
                        <option value="F2"<?php if (($propmode ?? '') == 'F2') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","F2 Reflection"); ?></option>
                        <option value="INTERNET"<?php if (($propmode ?? '') == 'INTERNET') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Internet-assisted"); ?></option>
                        <option value="ION"<?php if (($propmode ?? '') == 'ION') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Ionoscatter"); ?></option>
                        <option value="IRL"<?php if (($propmode ?? '') == 'IRL') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","IRLP"); ?></option>
                        <option value="MS"<?php if (($propmode ?? '') == 'MS') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Meteor scatter"); ?></option>
                        <option value="RPT"<?php if (($propmode ?? '') == 'RPT') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Terrestrial or atmospheric repeater or transponder"); ?></option>
                        <option value="RS"<?php if (($propmode ?? '') == 'RS') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Rain scatter"); ?></option>
                        <option value="SAT" <?php if ($propmode == 'SAT') {echo 'selected="selected"';} ?>><?= _pgettext("Propagation Mode","Satellite"); ?></option>
                        <option value="TEP"<?php if (($propmode ?? '') == 'TEP') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Trans-equatorial"); ?></option>
                        <option value="TR"<?php if (($propmode ?? '') == 'TR') { echo ' selected="selected"'; } ?>><?= _pgettext("Propagation Mode","Tropospheric ducting"); ?></option>
                    </select>
                </div>
            </div>


            <!-- Button (Double) -->
            <div class="mb-3 row">
                <div class="col-md-10">
                    <button id="button1id" type="button" name="button1id" class="btn btn-primary ld-ext-right" onclick="accumulatePlot(this.form)"><?= __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
                </div>
            </div>


        </form>

        <div id="accumulateContainer">
            <canvas id="myChartAccumulate" width="400" height="150"></canvas>
            <div id="accumulateTable" class="mt-2"></div>
        </div>

</div>
