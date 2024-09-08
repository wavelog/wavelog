<div class="container">

    <br>

    <h2><?= __("Distances Worked"); ?></h2>
    <script>
        var lang_general_word_qso_data = '<?= __("QSO Data"); ?>';
        var lang_statistics_distances_worked = '<?= __("Distances Worked"); ?>';
        var lang_statistics_distances_part1_contacts_were_plotted_furthest = '<?= sprintf(__("contacts were plotted.%s Your furthest contact was with"), '<br>'); ?>';
        var lang_statistics_distances_part2_contacts_were_plotted_furthest = '<?= __("in gridsquare"); ?>';
        var lang_statistics_distances_part3_contacts_were_plotted_furthest = '<?= __("the distance was"); ?>';
        var lang_statistics_distances_number_of_qsos = '<?= __("Number of QSOs"); ?>';
        var lang_gen_hamradio_distance = '<?= __("Distance"); ?>';
        var lang_statistics_distances_callsigns_worked = '<?= __("Callsign(s) worked (max 5 shown)"); ?>';
        var lang_statistics_distances_qsos_with = '<?= __("QSOs with"); ?>';
    </script>
    <div id="distances_div">
        <form class="d-flex align-items-center">
            <label class="my-1 me-2" for="distplot_bands"><?= __("Band selection"); ?></label>
            <select class="form-select my-1 me-sm-2 w-auto"  id="distplot_bands">
				<option value="All"><?= __("All"); ?></option>
                <?php if (count($sats_available) != 0) { ?>
                    <option value="sat"><?= __("SAT"); ?></option>
                <?php } ?>
                <?php foreach($bands_available as $band) {
                    echo '<option value="'.$band.'"';
                    if ($user_default_band == $band) {
                        echo ' selected="selected"';
                    }
                    echo '>'.$band.'</option>'."\n";
                } ?>
            </select>
            <?php if (count($sats_available) != 0) { ?>
                <label class="my-1 me-2" id="satslabel" for="distplot_sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>><?= __("Satellite")?></label>
                <select class="form-select my-1 me-sm-2 w-auto"  id="distplot_sats" <?php if ($user_default_band != "SAT") { ?>style="display: none;"<?php } ?>>
                    <option value="All"><?= __("All")?></option>
                    <?php foreach($sats_available as $sat) {
                        echo '<option value="' . $sat . '"' . '>' . $sat . '</option>'."\n";
                    } ?>
                </select>
            <?php } else { ?>
                <input id="distplot_sats" type="hidden" value="All"></input>
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
			<label class="my-1 me-2" for="propmode"><?= __("Propagation"); ?></label>
                <div class="my-1 me-2">
                    <select class="form-select w-auto" name="propmode" id="propmode">
                        <option value="All"><?= __("All"); ?></option>
                        <option value="NoSAT"><?= __("All but SAT"); ?></option>
                        <option value="None"><?= __("None/Empty"); ?></option>
                        <option value="AS"><?= _pgettext("Propagation Mode","Aircraft Scatter"); ?></option>
                        <option value="AUR"><?= _pgettext("Propagation Mode","Aurora"); ?></option>
                        <option value="AUE"><?= _pgettext("Propagation Mode","Aurora-E"); ?></option>
                        <option value="BS"><?= _pgettext("Propagation Mode","Back scatter"); ?></option>
                        <option value="ECH"><?= _pgettext("Propagation Mode","EchoLink"); ?></option>
                        <option value="EME"><?= _pgettext("Propagation Mode","Earth-Moon-Earth"); ?></option>
                        <option value="ES"><?= _pgettext("Propagation Mode","Sporadic E"); ?></option>
                        <option value="FAI"><?= _pgettext("Propagation Mode","Field Aligned Irregularities"); ?></option>
                        <option value="F2"><?= _pgettext("Propagation Mode","F2 Reflection"); ?></option>
                        <option value="INTERNET"><?= _pgettext("Propagation Mode","Internet-assisted"); ?></option>
                        <option value="ION"><?= _pgettext("Propagation Mode","Ionoscatter"); ?></option>
                        <option value="IRL"><?= _pgettext("Propagation Mode","IRLP"); ?></option>
                        <option value="MS"><?= _pgettext("Propagation Mode","Meteor scatter"); ?></option>
                        <option value="RPT"><?= _pgettext("Propagation Mode","Terrestrial or atmospheric repeater or transponder"); ?></option>
                        <option value="RS"><?= _pgettext("Propagation Mode","Rain scatter"); ?></option>
                        <option value="SAT"><?= _pgettext("Propagation Mode","Satellite"); ?></option>
                        <option value="TEP"><?= _pgettext("Propagation Mode","Trans-equatorial"); ?></option>
                        <option value="TR"><?= _pgettext("Propagation Mode","Tropospheric ducting"); ?></option>
                    </select>
                </div>
            <button id="plot" type="button" name="plot" class="btn btn-primary ld-ext-right ld-ext-right-plot" onclick="distPlot(this.form)"><?= __("Show")?><div class="ld ld-ring ld-spin"></div></button>
        </form>
    </div>

</div>
