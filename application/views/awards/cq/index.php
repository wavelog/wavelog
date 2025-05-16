<script>
	var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>";
    var lang_general_hamradio_cqzone = "<?= __("CQ Zone"); ?>";
    var lang_hover_over_a_zone = "<?= __("Hover over a zone"); ?>";
</script>
<style>
    #cqmap {
	height: calc(100vh - 480px) !important;
	max-height: 900px !important;
}
</style>

<div class="container">

    <!-- Award Info Box -->
	<br>
  <div id="awardInfoButton">
    <script>
      var lang_awards_info_button = "<?= __("Award Info"); ?>";
      var lang_award_info_ln1 = "<?= __("CQ WAZ (Worked All Zones) Award"); ?>";
      var lang_award_info_ln2 = "<?= __("The CQ Magazine was located in the US and one of the most popular amateur radio magazines in the world. They stopped service by the end of 2023. The magazine first appeared in January 1945 and focuses on awards and the practical aspects of amateur radio."); ?>";
      var lang_award_info_ln3 = "<?= __("The WAZ Award stands for 'Worked All Zones' and requires radio contacts to all 40 CQ Zones along with the corresponding confirmation. Since the CQ Magazine does no longer exists the CQ WAZ Awards is now managed directly by N4BAA."); ?>";
      var lang_award_info_ln4 = "<?= sprintf(__("You can find all the information and rules on the Website of N4BAA: %s"), "<a href='https://n4baa.com/cqwaz.html' target='_blank'>N4BAA.com</a>"); ?>";
    </script>
    <h2><?= __("Awards - CQ WAZ"); ?></h2>
    <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
  </div>
  <!-- End of Award Info Box -->
            <form class="form" action="<?php echo site_url('awards/cq'); ?>" method="post" enctype="multipart/form-data">
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
                <label class="col-md-2 control-label" for="band2"><?= __("Band"); ?></label>
                <div class="col-md-2">
                    <select id="band2" name="band" class="form-select form-select-sm">
                        <option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("All"); ?></option>
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
                    <?php if ($cq_array) {
                        ?><button type="button" onclick="load_cq_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-americas"></i> <?= __("Show CQ Zone Map"); ?></button>
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
            <a class="nav-link" onclick="load_cq_map();" id="map-tab" data-bs-toggle="tab" href="#cqmaptab" role="tab" aria-controls="home" aria-selected="false"><?= __("Map"); ?></a>
        </li>
    </ul>
    <br />

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade" id="cqmaptab" role="tabpanel" aria-labelledby="home-tab">
    <br />

    <div id="cqmap" class="map-leaflet" ></div>

    </div>

        <div class="tab-pane fade show active" id="table" role="tabpanel" aria-labelledby="table-tab" style="margin-bottom: 30px;">

<?php
    $i = 1;
    if ($cq_array) {
    echo "
    <table style='width:100%' class='table tablecq table-sm table-bordered table-hover table-striped table-condensed text-center'>
        <thead>
        <tr>
            <td>#</td>
            <td>" . __("CQ Zone") . "</td>";
        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
            }
            echo '</tr>
        </thead>
        <tbody>';
        foreach ($cq_array as $cq => $value) {      // Fills the table with the data
        echo '<tr>
            <td>' . $i++ . '</td>
            <td>'. $cq .'</td>';
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

        foreach ($cq_summary['worked'] as $dxcc) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $dxcc . '</td>';
        }

        echo "</tr><tr>
        <td>" . __("Total confirmed") . "</td>";
        foreach ($cq_summary['confirmed'] as $dxcc) {      // Fills the table with the data
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

            </div>
        </div>
</div>
