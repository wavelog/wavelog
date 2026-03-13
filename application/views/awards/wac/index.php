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
			var lang_award_info_ln5 = "<?= __("Fields taken for this Award: Continent (ADIF tag 'CONT'). Must contain a valid continent-abbreviation!"); ?>";
		</script>
		<h2><?= __("Awards - Worked All Continents (WAC)"); ?></h2>
		<button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
	</div>
	<!-- End of Award Info Box -->
            <form class="form" action="<?php echo site_url('awards/wac'); ?>" method="post" enctype="multipart/form-data">
            <fieldset>

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
                        <option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("Every band (w/o SAT)"); ?></option>
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
                <label class="col-md-2 control-label" id="satslabel" for="sats"><?= __("Satellite"); ?></label>
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
					<button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
                </div>
            </div>
        </fieldset>
    </form>

        <div>

<?php
    $i = 1;
    if ($wac_array) {
		echo __('Legend:');
		echo '<pre>'.__("(Q)SL-Paper-Card").", ";
		echo __("(L)oTW").", ";
		echo __("(e)QSL").", ";
		echo __('QR(Z)-"confirmation"').", ";
		echo __("(C)lublog").", ";
		echo __("(W)orked").'</pre>';
    echo "
    <table style='width:100%' class='table tablecq table-sm table-bordered table-hover table-striped table-condensed text-center'>
        <thead>
        <tr>
            <td>#</td>
            <td>" . __("Continent") . "</td>";
        foreach($bands as $band) {
            if (($posted_band != 'SAT') && ($band == 'SAT')) {
				continue;
			}
			echo '<td>' . $band . '</td>';
            }
            echo '</tr>
        </thead>
        <tbody>';
        foreach ($wac_array['matrix'] as $wac => $value) {      // Fills the table with the data
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
			if (($posted_band != 'SAT') && ($band == 'SAT')) {
				continue;
			}
            echo '<td>' . $band . '</td>';
		}
		if ($posted_band != 'SAT') {
			echo "<td>" . __("Total (ex SAT)") . "</td>";
		} ?>

		</tr>
        </thead>
        <tbody>

		<?php
        echo "<tr><td>" . __("Total worked") . "</td>";

        foreach ($wac_summary['worked'] as $wac => $value) {      // Fills the table with the data
			if (($posted_band != 'SAT') && ($wac == 'SAT')) {
				continue;
			}

			if (($posted_band == 'SAT') && ($wac == 'Total')) {
				continue;
			}
            echo '<td style="text-align: center">';
			if ($wac == 'Total' && $posted_band != 'SAT') {
				echo '<b>'.$value.'</b>';
			} else {
				echo $value;
			}
			echo '</td>';
        }

        echo "</tr><tr>
        <td>" . __("Total confirmed") . "</td>";
        foreach ($wac_summary['confirmed'] as $wac => $value) {      // Fills the table with the data
			if (($posted_band != 'SAT') && ($wac == 'SAT')) {
				continue;
			}

			if (($posted_band == 'SAT') && ($wac == 'Total')) {
				continue;
			}
            echo '<td style="text-align: center">';
			if ($wac == 'Total' && $posted_band != 'SAT') {
				echo '<b>'.$value.'</b>';
			} else {
				echo $value;
			}
			echo '</td>';
        }

        echo '</tr>
        </table>
        </div>';

    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . __("No QSOS found matching the criteria for this award!") . '</div>';
    }
    ?>

            </div>
        </div>
