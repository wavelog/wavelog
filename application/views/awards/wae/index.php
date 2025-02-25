<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("WAE Award"); ?>";
            var lang_award_info_ln2 = "<?= __("The oldest and most renowned of all DARC certificates is awarded for contacts with amateur radio stations in European countries and on islands listed in the WAE country list on different bands."); ?>";
            var lang_award_info_ln3 = "<?= __("The WAE will be issued in the following modes: CW, SSB, Phone, RTTY, FT8,  Digital and Mixed Modes. It is issued in five classes: WAE III, WAE II, WAE I, WAE TOP and the WAE Trophy."); ?>";
            var lang_award_info_ln4 = "<?= sprintf(__("Official information and the rules can be found in this document: %s."), "<a href='https://www.darc.de/en/der-club/referate/committee-dx/diplome/wae-award/' target='_blank'>https://www.darc.de/en/der-club/referate/committee-dx/diplome/wae-award/</a>"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->

    <form class="form" action="<?php echo site_url('awards/wae'); ?>" method="post" enctype="multipart/form-data">
        <fieldset>
<?php
      /*      <div class="mb-3 row">
                <div class="col-md-2 control-label" for="checkboxes"><?= __("Deleted WAE"); ?></div>
                <div class="col-md-10">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="includedeleted" id="includedeleted" value="1" <?php if ($this->input->post('includedeleted')) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="includedeleted"><?= __("Include deleted"); ?></label>
                    </div>
                </div>
            </div>*/
?>
            <!-- Multiple Checkboxes (inline) -->
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
                <label class="col-md-2 control-label" for="band"><?= __("Band"); ?></label>
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
            <div id="satrow" class="mb-3 row" <?php if ($this->input->post('band') != 'SAT' && $this->input->post('band') != 'All') echo "style=\"display: none\""; ?>>
			<?php if (count($sats_available) != 0) { ?>
                <label class="col-md-2 control-label" id="satslabel" for="distplot_sats"><?= __("Satellite"); ?></label>
				<div class="col-md-2">
                <select class="form-select form-select-sm"  id="sats" name="sats">
                    <option value="All" <?php if ($this->input->post('sats') == "All" || $this->input->method() !== 'post') echo ' selected'; ?>><?= __("All")?></option>
                    <?php foreach($sats_available as $sat) {
                        echo '<option value="' . $sat . '"';
						if ($this->input->post('sats') == $sat) echo ' selected';
						echo '>' . $sat . '</option>'."\n";
                    } ?>
                </select>
				</div>
            <?php } else { ?>
                <input id="sats" type="hidden" value="All"></input>
            <?php } ?>
			</div>
        <div id="orbitrow" class="mb-3 row" <?php if ($this->input->post('band') != 'SAT' && $this->input->post('band') != 'All') echo "style=\"display: none\""; ?>>
                <label class="col-md-2 control-label" id="orbitslabel" for="orbits"><?= __("Orbit"); ?></label>
				<div class="col-md-2">
                <select class="form-select form-select-sm"  id="orbits" name="orbits">
                    <option value="All" <?php if ($this->input->post('orbits') == "All" || $this->input->method() !== 'post') echo ' selected'; ?>><?= __("All")?></option>
                    <?php
                    foreach($orbits as $orbit){
                        echo '<option value="' . $orbit . '"';
						if ($this->input->post('orbits') == $orbit) echo ' selected';
						echo '>' . strtoupper($orbit) . '</option>'."\n";
                    }
                    ?>
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
                </div>
            </div>

        </fieldset>
    </form>

    <?php
    $i = 1;
    if ($wae_array) {
        echo '
                <table style="width:100%" class="table-sm table tabledxcc table-bordered table-hover table-striped table-condensed text-center">
                    <thead>
                    <tr>
                        <td>#</td>
                        <td>' . __("WAE Name") . '</td>
                        <td>' . __("Prefix") . '</td>';
        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
        }
        echo '</tr>
                    </thead>
                    <tbody>';
        foreach ($wae_array as $dxcc => $value) {      // Fills the table with the data
            echo '<tr>
                        <td>'. $i++ .'</td>';
            foreach ($value as $name => $key) {
                if (isset($value['Deleted']) && $value['Deleted'] == 1 && $name == "name") {
                   echo '<td style="text-align: center">' . $key . ' <span class="badge text-bg-danger">'.__("Deleted DXCC").'</span></td>';
                } else if ($name == "Deleted") {
                   continue;
                } else {
                   echo '<td style="text-align: center">' . $key . '</td>';
                }
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

        foreach ($wae_summary['worked'] as $dxcc) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $dxcc . '</td>';
        }

        echo '</tr><tr>
        <td>' . __("Total confirmed") . '</td>';
        foreach ($wae_summary['confirmed'] as $dxcc) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $dxcc . '</td>';
        }

        echo '</tr>
        </table>
        </div>';

    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>
