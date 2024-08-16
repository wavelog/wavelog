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
            <button id="plot" type="button" name="plot" class="btn btn-primary ld-ext-right ld-ext-right-plot" onclick="distPlot(this.form)"><?= __("Show")?><div class="ld ld-ring ld-spin"></div></button>
        </form>
    </div>

</div>
