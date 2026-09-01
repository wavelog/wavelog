<div class="container px-3 px-lg-4 mt-3 mb-3">

    <h2><?= __("Count QSOs by..."); ?></h2>
    <script>
        var lang_general_word_qso_data = '<?= __("QSO Data"); ?>';
        var user_map_custom = JSON.parse('<?= $user_map_custom; ?>');
        var lang_gen_hamradio_qso_short = '<?= __("QSOs"); ?>';
        var lang_distinct_counts_worked = '<?= __("Worked"); ?>';
        var lang_distinct_counts_confirmed = '<?= __("Confirmed"); ?>';
        var lang_distinct_counts_qsos_total = '<?= __("QSOs total"); ?>';
        var lang_distinct_counts_type_grid = '<?= __("Gridsquare"); ?>';
        var lang_distinct_counts_type_dxcc = '<?= __("DXCC"); ?>';
        var lang_distinct_counts_type_ref = '<?= __("Reference"); ?>';
        var lang_distinct_counts_type_itu = '<?= __("ITU Zone"); ?>';
        var lang_distinct_counts_type_cq = '<?= __("CQ Zone"); ?>';
        var lang_distinct_counts_deleted_dxcc = '<?= __("Deleted DXCC"); ?>';
    </script>

    <style>
        #distincttable th:nth-child(1) { text-align: left !important; }
        #distincttable th:nth-child(2), #distincttable th:nth-child(3) { text-align: right !important; }
    </style>

    <div id="countqsoby_div">
        <div class="card mb-3">
                        <div class="card-header" role="button" data-bs-toggle="collapse" data-bs-target="#distinctFilterBody" aria-expanded="true" aria-controls="distinctFilterBody">
                <h6 class="mb-0"><?= __("Filter"); ?> <i class="fas fa-chevron-down float-end" style="font-size: 0.75rem; line-height: 1.5;"></i></h6>
            </div>
            <div class="collapse show" id="distinctFilterBody">
            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-md-2 control-label" for="datepresets"><?= __("Date presets"); ?></div>
                    <div class="col-md-10 d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="distinctApplyPreset('today')"><?= __("Today") ?></button>
                        <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="distinctApplyPreset('yesterday')"><?= __("Yesterday") ?></button>
                        <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="distinctApplyPreset('last7days')"><?= __("Last 7 Days") ?></button>
                        <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="distinctApplyPreset('last30days')"><?= __("Last 30 Days") ?></button>
                        <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="distinctApplyPreset('thismonth')"><?= __("This Month") ?></button>
                        <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="distinctApplyPreset('lastmonth')"><?= __("Last Month") ?></button>
                        <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="distinctApplyPreset('thisyear')"><?= __("This Year") ?></button>
                        <button type="button" class="btn btn-primary btn-sm flex-shrink-0" onclick="distinctApplyPreset('lastyear')"><?= __("Last Year") ?></button>
                        <button type="button" class="btn btn-danger btn-sm flex-shrink-0" onclick="distinctResetDates()"><i class="fas fa-times"></i> <?= __("Clear") ?></button>
                    </div>
                </div>

                <div class="mb-3 row justify-content-center">
                    <div class="col-md-2 control-label" for="distinctdateFrom"><?= __("Date from"); ?></div>
                    <div class="col-md-4">
                        <div class="form-check-inline">
                            <input id="distinctdateFrom" type="date" class="form-control form-control-sm w-auto border border-secondary">
                        </div>
                    </div>
                    <div class="col-md-2 control-label" for="distinctdateTo"><?= __("Date to"); ?></div>
                    <div class="col-md-4">
                        <div class="form-check-inline">
                            <input id="distinctdateTo" type="date" class="form-control form-control-sm w-auto border border-secondary">
                        </div>
                    </div>
                </div>

                <div class="mb-3 row justify-content-center">
                    <label class="col-md-2 control-label" for="distincttype"><?= __("Count QSOs by"); ?></label>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" id="distincttype">
                            <option value="dxcc" selected="selected"><?= __("DXCC"); ?></option>
                            <option value="grid"><?= __("Gridsquare"); ?></option>
                            <option value="itu"><?= __("ITU Zone"); ?></option>
                            <option value="cq"><?= __("CQ Zone"); ?></option>
                            <option value="pota"><?= __("POTA Reference"); ?></option>
                            <option value="sota"><?= __("SOTA Reference"); ?></option>
                            <option value="iota"><?= __("IOTA Reference"); ?></option>
                            <option value="wwff"><?= __("WWFF Reference"); ?></option>
                        </select>
                    </div>
                    <label class="col-md-2 control-label" for="distinctplot_bands"><?= __("Band"); ?></label>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" id="distinctplot_bands">
                            <option value="All"><?= __("Every band (w/o SAT)"); ?></option>
                            <?php foreach($user_bands as $band) {
                                echo '<option value="' . html_escape($band) . '"';
                                if ($user_default_band == $band) {
                                    echo ' selected="selected"';
                                }
                                echo '>' . html_escape($band) . '</option>'."\n";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div id="distinctsatrow" class="mb-3 col-md-6">
                    <?php if (count($sats_available) != 0) { ?>
                        <div class="row">
                            <label class="col-md-4 control-label" id="distinctsatslabel" for="distinctplot_sats"><?= __("Satellite") ?></label>
                            <div class="col-md-8">
                                <select class="form-select form-select-sm" id="distinctplot_sats">
                                    <option value="All"><?= __("All") ?></option>
                                    <?php foreach($sats_available as $sat) {
                                        $sat_display = ($sat->displayname ?? '') != '' ? $sat->displayname : $sat->name;
                                        echo '<option value="' . html_escape($sat->name) . '">' . html_escape($sat_display) . '</option>'."\n";
                                    } ?>
                                </select>
                            </div>
                        </div>
                    <?php } else { ?>
                        <input id="distinctplot_sats" type="hidden" value="All">
                    <?php } ?>
                    </div>
                    <div id="distinctorbitrow" class="mb-3 col-md-6">
                        <div class="row">
                            <label class="col-md-4 control-label" id="distinctorbitslabel" for="distinctorbits"><?= __("Orbit"); ?></label>
                            <div class="col-md-8">
                                <select class="form-select form-select-sm" id="distinctorbits">
                                    <option value="All"><?= __("All") ?></option>
                                    <?php
                                    foreach($orbits as $orbit){
                                        echo '<option value="' . html_escape($orbit->orbit) . '">' . html_escape(strtoupper($orbit->orbit)) . '</option>'."\n";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3 row justify-content-center">
                    <label class="col-md-2 control-label" for="distinctplot_mode"><?= __("Mode"); ?></label>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" id="distinctplot_mode">
                            <option value="All"><?= __("All"); ?></option>
                            <?php foreach ($modes->result() as $mode) {
                                if ($mode->submode == null) {
                                    echo '<option value="' . html_escape($mode->mode) . '">' . html_escape($mode->mode) . '</option>' . "\n";
                                } else {
                                    echo '<option value="' . html_escape($mode->submode) . '">' . html_escape($mode->submode) . '</option>' . "\n";
                                }
                            } ?>
                        </select>
                    </div>
                    <label class="col-md-2 control-label" for="distinctpropmode"><?= __("Propagation"); ?></label>
                    <div class="col-md-4">
                        <select class="form-select form-select-sm" name="distinctpropmode" id="distinctpropmode">
                            <option value="All"><?= __("All"); ?></option>
                            <option value="None"><?= __("None/Empty"); ?></option>
                            <?php foreach ($adif_propmodes as $mode => $desc) {
								echo "<option value=\"$mode\">".htmlspecialchars_decode($desc)."</option>\n";
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="mb-3 row">
                    <div class="col-md-2 control-label" for="distinctqsl"><?= __("Confirmation"); ?></div>
                    <div class="col-md-10">
                        <div class="form-check-inline">
                            <?php echo '<input class="form-check-input" type="checkbox" id="distinctqsl"';
                            if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Q') !== false) {
                                echo ' checked';
                            }
                            echo '>'; ?>
                            <label class="form-check-label" for="distinctqsl"><?= __("QSL"); ?></label>
                        </div>
                        <div class="form-check-inline">
                            <?php echo '<input class="form-check-input" type="checkbox" id="distinctlotw"';
                            if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'L') !== false) {
                                echo ' checked';
                            }
                            echo '>'; ?>
                            <label class="form-check-label" for="distinctlotw"><?= __("LoTW"); ?></label>
                        </div>
                        <div class="form-check-inline">
                            <?php echo '<input class="form-check-input" type="checkbox" id="distincteqsl"';
                            if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'E') !== false) {
                                echo ' checked';
                            }
                            echo '>'; ?>
                            <label class="form-check-label" for="distincteqsl"><?= __("eQSL"); ?></label>
                        </div>
                        <div class="form-check-inline">
                            <?php echo '<input class="form-check-input" type="checkbox" id="distinctqrz"';
                            if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'Z') !== false) {
                                echo ' checked';
                            }
                            echo '>'; ?>
                            <label class="form-check-label" for="distinctqrz"><?= __("QRZ.com"); ?></label>
                        </div>
                        <div class="form-check-inline">
                            <?php echo '<input class="form-check-input" type="checkbox" id="distinctclublog"';
                            if (isset($user_default_confirmation) && strpos($user_default_confirmation, 'C') !== false) {
                                echo ' checked';
                            }
                            echo '>'; ?>
                            <label class="form-check-label" for="distinctclublog"><?= __("Clublog"); ?></label>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <button id="distinctplot" type="button" name="distinctplot" class="btn btn-sm btn-primary ld-ext-right ld-ext-right-distinctplot" onclick="distinctPlot()"><?= __("Show") ?><div class="ld ld-ring ld-spin"></div></button>
                </div>

				</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <?= __("Count QSOs by..."); ?>
            </div>
            <div class="card-body">
                <div class="text-center" id="distinct_summary"></div>
                <div class="table-responsive mt-2">
                    <table id="distincttable" class="table table-sm table-striped table-hover" style="width:100%"></table>
                </div>
            </div>
        </div>
    </div>

	<script>
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(header) {
            var target = document.querySelector(header.dataset.bsTarget);
            if (target) {
                var icon = header.querySelector('.fa-chevron-down');
                if (icon) {
                    target.addEventListener('show.bs.collapse', function() {
                        icon.style.transform = 'rotate(0deg)';
                    });
                    target.addEventListener('hidden.bs.collapse', function() {
                        icon.style.transform = 'rotate(180deg)';
                    });
                }
            }
        });
    </script>
</div>
