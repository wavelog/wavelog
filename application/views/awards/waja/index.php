<script>
	var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
    var lang_japan_province = "<?= _pgettext("Japan Prefecture", "Prefecture"); ?>";
    var lang_hover_over_a_prefecture = "<?= __("Hover over a prefecture"); ?>";
</script>

<style>
    #wajamap {
	height: calc(100vh - 500px) !important;
	max-height: 900px !important;
}
</style>
<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("WAJA - Worked All Japan prefectures Award"); ?>";
            var lang_award_info_ln2 = "<?= __("WAJA, Worked All Japan prefectures in Amateur Radio, encourages licensed ham radio operators to work all the prefectures in Japan."); ?>";
            var lang_award_info_ln3 = "<?= __("May be claimed for having contacted (heard) and received a QSL card from an amateur station located in each of the 47 prefectures of Japan. A list of QSL cards should be arranged in order of WAJA (HAJA) reference number, however names of prefectures may be omitted."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://www.jarl.org/English/4_Library/A-4-2_Awards/Award_Main.htm' target='_blank'>https://www.jarl.org/English/4_Library/A-4-2_Awards/Award_Main.htm</a>"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->

    <form class="form" action="<?php echo site_url('awards/waja'); ?>" method="post" enctype="multipart/form-data">
        <fieldset>

            <div class="mb-3 row">
                <div class="col-md-2" for="checkboxes"><?= __("Worked / Confirmed"); ?></div>
                <div class="col-md-10">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="worked" id="worked" value="1" <?php if ($this->input->post('worked') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="worked"><?= __("Show worked"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="confirmed" id="confirmed" value="1" <?php if ($this->input->post('confirmed') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="confirmed"><?= __("Show confirmed"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="notworked" id="notworked" value="1" <?php if ($this->input->post('notworked') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="notworked"><?= __("Show not worked"); ?></label>
                    </div>
                </div>
            </div>

            <div class="mb-3 row">
                <div class="col-md-2"><?= __("Show QSO with QSL Type"); ?></div>
                <div class="col-md-10">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="qsl" value="1" id="qsl" <?php if ($this->input->post('qsl') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="lotw" value="1" id="lotw" <?php if ($this->input->post('lotw') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="eqsl" value="1" id="eqsl" <?php if ($this->input->post('eqsl')) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="qrz" value="1" id="qrz" <?php if ($this->input->post('qrz')) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="clublog" value="1" id="clublog" <?php if ($this->input->post('clublog')) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="clublog"><?= __("Clublog"); ?></label>
                    </div>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="band2"><?= __("Band"); ?></label>
                <div class="col-md-2">
                    <select id="band2" name="band" class="form-select form-select-sm">
                        <option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("Every band"); ?></option>
                        <?php foreach($worked_bands as $band) {
                            echo '<option value="' . $band . '"';
                            if ($this->input->post('band') == $band) echo ' selected';
                            echo '>' . $band . '</option>'."\n";
                        } ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="mode"><?= __("Mode"); ?></label>
                <div class="col-md-2">
                <select id="mode" name="mode" class="form-select form-select-sm">
                    <option value="All" <?php if ($this->input->post('mode') == "All" || $this->input->method() !== 'mode') echo ' selected'; ?>><?= __("All"); ?></option>
                    <?php
                    foreach($modes->result() as $mode){
                        if ($mode->submode == null) {
                            echo '<option value="' . $mode->mode . '"';
                            if ($this->input->post('mode') == $mode->mode) echo ' selected';
                            echo '>'. $mode->mode . '</option>'."\n";
                        } else {
                            echo '<option value="' . $mode->submode . '"';
                            if ($this->input->post('mode') == $mode->submode) echo ' selected';
                            echo '>' . $mode->submode . '</option>'."\n";
                        }
                    }
                    ?>
                </select>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="button1id"></label>
                <div class="col-md-10">
                    <button id="button2id" type="reset" name="button2id" class="btn btn-sm btn-warning"><?= __("Reset"); ?></button>
                    <button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
                    <?php if ($waja_array) {
                        ?><button type="button" onclick="load_waja_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-americas"></i><?= __("Show WAJA Map"); ?></button>
                    <?php }?>
                </div>
            </div>

        </fieldset>
    </form>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="table-tab" data-bs-toggle="tab" href="#table" role="tab" aria-controls="table" aria-selected="true"><?= __("Table"); ?></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="map-tab" onclick="load_waja_map();" data-bs-toggle="tab" href="#wajamaptab" role="tab" aria-controls="home" aria-selected="false"><?= __("Map"); ?></a>
        </li>
    </ul>
    <br />

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade" id="wajamaptab" role="tabpanel" aria-labelledby="home-tab">
    <br />

    <div id="wajamap" class="map-leaflet" ></div>

    </div>

        <div class="tab-pane fade show active" id="table" role="tabpanel" aria-labelledby="table-tab">

    <?php
    $i = 1;
    if ($waja_array) {
        echo '
                <table style="width:100%" class="table-sm table tablewaja table-bordered table-hover table-striped table-condensed text-center">
                    <thead>
                    <tr>
						<td>' . __("Number") . '</td>
						<td>' . __("Prefecture") . '</td>';

        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
        }
        echo '</tr>
                    </thead>
                    <tbody>';
        foreach ($waja_array as $waja => $value) {      // Fills the table with the data
            echo '<tr>';
            foreach ($value as $name => $key) {
				echo '<td style="text-align: center">' . $key . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>
        <h2>' . __("Summary") . '</h2>

        <table class="table-sm tablesummary table table-bordered table-hover table-striped table-condensed text-center">
        <thead>
        <tr><td></td>';

        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
        }
        echo '<td>' . __("Total") . '</td>
        </tr>
        </thead>
        <tbody>

        <tr><td>' . __("Total worked") . '</td>';

        foreach ($waja_summary['worked'] as $waja) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $waja . '</td>';
        }

        echo '</tr><tr>
        <td>' . __("Total confirmed") . '</td>';
        foreach ($waja_summary['confirmed'] as $waja) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $waja . '</td>';
        }

        echo '</tr>
        </table>
        </div>';

    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>
                </div>
        </div>
</div>
