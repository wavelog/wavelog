<div class="container">

    <!-- Award Info Box -->
	<br>
  <div id="awardInfoButton">
    <script>
      var lang_awards_info_button = "<?= __("Award Info"); ?>";
      var lang_award_info_ln1 = "<?= __("Worked All Continents (WAC)"); ?>";
      var lang_award_info_ln2 = "<?= __("Sponsored by the International Amateur Radio Union (IARU), the Worked All Continents award is issued for working and confirming all six continents. These are North America, South America, Oceania, Asia, Europe and Africa."); ?>";
      var lang_award_info_ln3 = "";
	  var lang_award_info_ln4 = "<?= sprintf(__("You can find all information about the DXCC Award on the %s."), "<a href='https://www.arrl.org/wac' target='_blank'>" . __("ARRL website") . "</a>"); ?>";
    </script>
    <h2><?= __("Awards - Worked All Continents (WAC)"); ?></h2>
    <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
  </div>
  <!-- End of Award Info Box -->
            <form class="form" action="<?php echo site_url('awards/wac'); ?>" method="post" enctype="multipart/form-data">
            <fieldset>

            <!-- Multiple Checkboxes (inline) -->
            <div class="mb-3 row">
                <div class="col-md-2" for="checkboxes"><?= __("Worked") . ' / ' . __("Confirmed")?></div>
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
                        <label class="form-check-label" for="qsl"><?= __("QSL Card"); ?></label>
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

        <div>

<?php
    $i = 1;
    if ($wac_array) {
    echo "
    <table style='width:100%' class='table tablecq table-sm table-bordered table-hover table-striped table-condensed text-center'>
        <thead>
        <tr>
            <td>#</td>
            <td>" . __("Continent") . "</td>";
        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
            }
            echo '</tr>
        </thead>
        <tbody>';
        foreach ($wac_array as $wac => $value) {      // Fills the table with the data
        echo '<tr>
            <td>' . $i++ . '</td>
            <td>'. $wac.'</td>';
            foreach ($value  as $key) {
            echo '<td style="text-align: center">' . $key . '</td>';
            }
            echo '</tr>';
        }
        echo "</table>
        <h2>" . __("Summary") . "</h2>

        <table class='table-sm tablesummary table table-bordered table-hover table-striped table-condensed text-center'>
        <thead>
        <tr><td></td>";

        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
        }
        echo "<td>" . __("Total") . "</td></tr>
        </thead>
        <tbody>

        <tr><td>" . __("Total worked") . "</td>";

        foreach ($wac_summary['worked'] as $wac) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $wac . '</td>';
        }

        echo "</tr><tr>
        <td>" . __("Total confirmed") . "</td>";
        foreach ($wac_summary['confirmed'] as $wac) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $wac . '</td>';
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
