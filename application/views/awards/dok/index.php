<style>
	.dropdown-filters-responsive {
		width: min(850px, 90vw);
		min-width: 600px;
    }
</style>

<div class="container px-3 px-lg-4 mt-3 mb-3">
        <!-- Award Info Box -->
        <div id="awardInfoButton">
            <script>
            let lang_awards_info_button = "<?= __("Award Info"); ?>";
            let lang_award_info_ln1 = "<?= __("DOK Award"); ?>";
            let lang_award_info_ln2 = "<?= __("Germany extends over 630 km from East to West and nearly 900 km from North to South. Around 70,000 of Germany's 82 million inhabitants are licensed hams, with more than 40,000 of them being members of DARC. DOK is a system that provides individual local chapters with an identifier and means 'Deutscher Ortsverband Kenner' (English: 'German Local Association Identifier')."); ?>";
            let lang_award_info_ln3 = "<?= __("The DOK consists of a letter for the district and a two-digit number for the local chapter, like P03 Friedrichshafen (city of the 'Hamradio exhibition') or F41 Baunatal (location of the DARC headquarters). Note: A zero in a DOK is a common mistake, often being logged as the letter O."); ?>";
            let lang_award_info_ln4 = "<?= sprintf(_pgettext("uses 'DARC Website' and 'here'", "This information is provided by the %s. Information about the DOK Awards and its rules can be found %s."), "<a href='https://www.darc.de/der-club/referate/conteste/wag-contest/en/service/districtsdoks/' target='_blank'>" . __("DARC website") . "</a>", "<a href='https://www.darc.de/der-club/referate/conteste/wag-contest/en/service/award-check/' target='_blank'>" . __("here") . "</a>"); ?>";
            let lang_award_info_ln5 = "<?= __("Fields taken for this Award: DOK (ADIF: DARC_DOK)"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->

    <div class="card">
		<div class="card-header">
            <?= __("View progress of DOK awards"); ?>
        </div>
        <div class="card-body">
    <?php
       $doks = array();
       if ($dok_array) {
          foreach ($dok_array as $dok => $value) {
             if (preg_match('/^[A-Z][0-9]{2}$/', $dok)) {
                $doks[] = $dok;
             }
          }
       }
    ?>
    <form class="form" action="<?php echo site_url('awards/dok'); ?>" method="post" enctype="multipart/form-data">
        <div class="mb-4 text-center">
            <div class="dropdown" data-bs-auto-close="outside">
                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters") ?></button>
                <button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
                <button id="button3id" type="button" name="button3id" class="btn btn-sm btn-info" onclick=" window.open('https://dd3ah.de/dokmap/?lat=51.3035&lng=11.1475&zoom=7<?php print implode(',', $doks); ?>','_blank')"><i class="fas fa-globe-americas"></i> <?= __("Map"); ?></button>

            <!-- Dropdown Menu with Filter Content -->
            <div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="filterDropdown">
                <div class="card-body filterbody">

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="doks"><?= __("DOK / SDOK"); ?></label>
                <div class="col-md-2">
                    <select id="doks" name="doks" class="form-select form-select-sm">
                        <option value="both" <?php if ($this->input->post('doks') == "both" || $this->input->method() !== 'post') echo ' selected'; ?> ><?= __("DOK + SDOK"); ?></option>
                        <?php echo '<option value="dok"';
                            if ($this->input->post('doks') == 'dok') echo ' selected';
                            echo '>DOK</option>'."\n";
                        ?>
                        <?php echo '<option value="sdok"';
                            if ($this->input->post('doks') == 'sdok') echo ' selected';
                            echo '>SDOK</option>'."\n";
                        ?>
                    </select>
                </div>
            </div>

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

                </div>
            </div>
            </div>
        </div>
    </form>

    <br />

    <?php
    if ($dok_array) {
    echo '
    <table style="width:100%" id="doktable" class="table table-sm table-bordered table-hover table-striped table-condensed text-center">
        <thead>
        <tr>
            <th>' . __("DOK") . '</th>';
        foreach($bands as $band) {
            echo '<th>' . $band . '</th>';
            }
            echo '</tr>
        </thead>
        <tbody>';
        foreach ($dok_array as $dok => $value) {      // Fills the table with the data
        echo '<tr>
            <td>'. $dok .'</td>';
            foreach ($value  as $key) {
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
        echo '<td>' . __("Total") . '</td></tr>
        </thead>
        <tbody>

        <tr><td>' . __("Total worked") . '</td>';

        foreach ($dok_summary['worked'] as $dxcc) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $dxcc . '</td>';
        }

        echo '</tr><tr>
        <td>' . __("Total confirmed") . '</td>';
        foreach ($dok_summary['confirmed'] as $dxcc) {      // Fills the table with the data
            echo '<td style="text-align: center">' . $dxcc . '</td>';
        }

        echo '</tr>
        </table>';
    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>
        </div>
    </div>
</div>
