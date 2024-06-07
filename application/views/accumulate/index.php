<script>
    var lang_statistics_accumulated_worked_dxcc = '<?= __("Accumulated Number of DXCCs worked"); ?>';
    var lang_statistics_accumulated_worked_states = '<?= __("Accumulated Number of States worked"); ?>';
    var lang_statistics_accumulated_worked_iota = '<?= __("Accumulated Number of IOTAs worked"); ?>';
    var lang_statistics_accumulated_worked_cqzone = '<?= __("Accumulated Number of CQ Zones worked"); ?>';
    var lang_general_word_year = '<?= __("Year"); ?>';
    var lang_general_word_yearly = '<?= __("Yearly"); ?>';
    var lang_general_word_month = '<?= __("Month"); ?>';
    var lang_general_word_monthly = '<?= __("Monthly"); ?>';
</script>
<div class="container">
    <h2><?php echo $page_title; ?></h1>

        <form class="form">

            <!-- Select Basic -->
            <div class="mb-3 row">
                <label class="col-md-1 control-label" for="band"><?= __("Band"); ?></label>
                <div class="col-md-3">
                    <select id="band" name="band" class="form-select">
                        <option value="All"><?= __("All"); ?></option>
                        <?php foreach ($worked_bands as $band) {
                            echo '<option value="' . $band . '">' . $band . '</option>' . "\n";
                        } ?>
                    </select>
                </div>

                <label class="col-md-1 control-label" for="mode"><?= __("Mode"); ?></label>
                <div class="col-md-3">
                    <select id="mode" name="mode" class="form-select">
                        <option value="All"><?= __("All"); ?></option>
                        <?php
                        foreach ($modes->result() as $mode) {
                            if ($mode->submode == null) {
                                printf("<option value=\"%s\">%s</option>", $mode->mode, $mode->mode);
                            } else {
                                printf("<option value=\"%s\">&rArr; %s</option>", $mode->submode, $mode->submode);
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 row">

                <label class="col-md-1 control-label" for="radio"><?= __("Award"); ?></label>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="awardradio" id="dxcc" value="dxcc" checked>
                        <label class="form-check-label" for="dxcc">
                            <?= __("DX Century Club (DXCC)"); ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="awardradio" id="was" value="was">
                        <label class="form-check-label" for="was">
                            <?= __("Worked All States (WAS)"); ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="awardradio" id="iota" value="iota">
                        <label class="form-check-label" for="iota">
                            <?= __("Islands On The Air (IOTA)"); ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="awardradio" id="waz" value="waz">
                        <label class="form-check-label" for="waz">
                            <?= __("Worked All Zones (WAZ)"); ?>
                        </label>
                    </div>
                </div>

                <label class="col-md-1 control-label" for="radio"><?= __("Period"); ?></label>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="periodradio" id="yearly" value="year" checked>
                        <label class="form-check-label" for="yearly">
                            <?= __("Yearly"); ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="periodradio" id="monthly" value="month">
                        <label class="form-check-label" for="monthly">
                            <?= __("Monthly"); ?>
                        </label>
                    </div>
                </div>
            </div>


            <!-- Button (Double) -->
            <div class="mb-3 row">
                <div class="col-md-10">
                    <button id="button1id" type="button" name="button1id" class="btn btn-primary ld-ext-right" onclick="accumulatePlot(this.form)"><?= __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
                </div>
            </div>


        </form>

        <div id="accumulateContainer">
            <canvas id="myChartAccumulate" width="400" height="150"></canvas>
            <div id="accumulateTable" class="mt-2"></div>
        </div>

</div>