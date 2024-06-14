<script>
   var tileUrl="<?php echo $this->optionslib->get_option('option_map_tile_server');?>"
</script>
<style>
    #jccmap {
       height: calc(100vh - 480px) !important;
       max-height: 900px !important;
    }
</style>
<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?php echo lang('awards_info_button'); ?>";
            var lang_award_info_ln1 = "<?php echo lang('awards_jcc_description_ln1'); ?>";
            var lang_award_info_ln2 = "<?php echo lang('awards_jcc_description_ln2'); ?>";
            var lang_award_info_ln3 = "<?php echo lang('awards_jcc_description_ln3'); ?>";
            var lang_award_info_ln4 = "<?php echo lang('awards_jcc_description_ln4'); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?php echo lang('awards_info_button'); ?></button>
        </div>
        <!-- End of Award Info Box -->

    <form class="form" action="<?php echo site_url('awards/jcc'); ?>" method="post" enctype="multipart/form-data">
        <fieldset>

            <div class="mb-3 row">
                <div class="col-md-2" for="checkboxes">Worked / Confirmed</div>
                <div class="col-md-10">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="worked" id="worked" value="1" <?php if ($this->input->post('worked') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="worked">Show worked</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="confirmed" id="confirmed" value="1" <?php if ($this->input->post('confirmed') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="confirmed">Show confirmed</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="notworked" id="notworked" value="1" <?php if ($this->input->post('notworked')) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="notworked">Show not worked</label>
                    </div>
                </div>
            </div>

            <div class="mb-3 row">
                <div class="col-md-2"><?php echo lang('awards_qsl_type'); ?></div>
                <div class="col-md-10">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="qsl" value="1" id="qsl" <?php if ($this->input->post('qsl') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="qsl">QSL</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="lotw" value="1" id="lotw" <?php if ($this->input->post('lotw') || $this->input->method() !== 'post') echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="lotw">LoTW</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="eqsl" value="1" id="eqsl" <?php if ($this->input->post('eqsl')) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="eqsl">eQSL</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="qrz" value="1" id="qrz" <?php if ($this->input->post('qrz')) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="qrz">QRZ.com</label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="clublog" value="1" id="clublog" <?php if ($this->input->post('clublog')) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="clublog">Clublog</label>
                    </div>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="band">Band</label>
                <div class="col-md-2">
                    <select id="band2" name="band" class="form-select form-select-sm">
                        <option value="All" <?php if ($this->input->post('band') == "All" || $this->input->method() !== 'post') echo ' selected'; ?> >Every band</option>
                        <?php foreach($worked_bands as $band) {
                            echo '<option value="' . $band . '"';
                            if ($this->input->post('band') == $band) echo ' selected';
                            echo '>' . $band . '</option>'."\n";
                        } ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="mode">Mode</label>
                <div class="col-md-2">
                <select id="mode" name="mode" class="form-select form-select-sm">
                    <option value="All" <?php if ($this->input->post('mode') == "All" || $this->input->method() !== 'mode') echo ' selected'; ?>>All</option>
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
                    <button id="button2id" type="reset" name="button2id" class="btn btn-sm btn-warning">Reset</button>
                    <button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary">Show</button>
                    <?php if ($jcc_array) {?>
                    <button type="button" onclick="load_jcc_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-asia"></i> <?php echo lang('awards_show_jcc_map'); ?></button>
                    <button id="button3id" type="button" onclick="export_qsos();" name="button3id" class="btn btn-sm btn-info">Export</button>
                    <?php } ?>
                </div>
            </div>

        </fieldset>
    </form>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="table-tab" data-bs-toggle="tab" href="#table" role="tab" aria-controls="table" aria-selected="true">Results</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" onclick="load_jcc_map();" id="map-tab" data-bs-toggle="tab" href="#jccmaptab" role="tab" aria-controls="home" aria-selected="false"><?php echo lang('filter_map'); ?></a>
        </li>
    </ul>
    <br />

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade" id="jccmaptab" role="tabpanel" aria-labelledby="home-tab">
    <br />

    <div id="jccmap" class="map-leaflet" ></div>

    </div>

        <div class="tab-pane fade show active" id="table" role="tabpanel" aria-labelledby="table-tab">

    <?php
    $i = 1;
    if ($jcc_array) {
        echo '
                <table id="jccTable" style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
                    <thead>
                    <tr>
						<td>Number</td>
						<td>City</td>';

        foreach($bands as $band) {
            echo '<td>' . $band . '</td>';
        }
        echo '</tr>
                    </thead>
                    <tbody>';
        foreach ($jcc_array as $jcc => $value) {      // Fills the table with the data
            echo '<tr>';
            foreach ($value as $name => $key) {
				echo '<td style="text-align: center">' . $key . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>
        <h2>Summary</h2>

        <table class="table-sm tablesummary table table-bordered table-hover table-striped table-condensed text-center">';
        $sat = 0;
        if (in_array('SAT', $bands)) {
           $sat = 1;
        }

        echo '<thead><tr>';
        if (count($bands) > 1) {
           echo '<td></td>';

           foreach($bands as $band) {
               if ($band != 'SAT') {
                   echo '<td>' . $band . '</td>';
               }
           }
           echo '<td><b>Total</b></td>';
           if ($sat == 1) {
              echo '<td>SAT</td>';
           }
        } else {
           echo '<td></td><td><b>'.$bands[0].'</b></td>';
        }
        echo '</tr></thead>';
        echo '<tbody>

        <tr><td>Total worked</td>';

        if (count($bands) > 2) {
           $len_worked = count($jcc_summary['worked']);
           $j = 0;
           foreach ($jcc_summary['worked'] as $jcc) {      // Fills the table with the data
               if ($j == $len_worked - 1 - $sat) {
                  echo '<td style="text-align: center"><b>' . $jcc . '</b></td>';
               } else {
                  echo '<td style="text-align: center">' . $jcc . '</td>';
               }
               $j++;
           }
        } else {
           echo '<td style="text-align: center"><b>' . $jcc_summary['worked']['Total'] . '</b></td>';
        }

        echo '</tr><tr>';

        echo '<td>Total confirmed</td>';
        if (count($bands) > 2) {
           $len_confirmed = count($jcc_summary['confirmed']);
           $j = 0;
           foreach ($jcc_summary['confirmed'] as $jcc) {      // Fills the table with the data
               if ($j == $len_confirmed - 1 - $sat) {
                  echo '<td style="text-align: center"><b>' . $jcc . '</b></td>';
               } else {
                  echo '<td style="text-align: center">' . $jcc . '</td>';
               }
               $j++;
           }
        } else {
           echo '<td style="text-align: center"><b>' . $jcc_summary['confirmed']['Total'] . '</b></td>';
        }

        echo '</tr>
        </table>
        </div>';

    }
    else {
        echo '<div class="alert alert-danger" role="alert">Nothing found!</div>';
    }
    ?>
                </div>
        </div>
</div>
