<script>
    var lang_statistics_timeplotter_contacts_plotted = '<?php echo __("contacts were plotted"); ?>';
    var lang_statistics_timeplotter_chart_header = '<?php echo __("Time Distribution"); ?>';
    var lang_statistics_timeplotter_number_of_qsos = '<?php echo __("Number of QSOs"); ?>';
    var lang_general_word_time = '<?php echo __("Time"); ?>';
    var lang_statistics_timeplotter_callsigns_worked = '<?php echo __("Callsign(s) worked (max 5)"); ?>';
</script>
<div class="container">
    <h2><?php echo __("Timeplotter"); ?></h1>
        <p><?php echo __("The Timeplotter is used to analyze your logbook and find out at what times you worked certain CQ zones or DXCC countries on a selected band."); ?></p>
        <form class="form">

            <div class="mb-3 row">
                <label class="col-md-1 control-label" for="band"><?php echo __("Band"); ?></label>
                <div class="col-md-3">
                    <select id="band" name="band" class="form-select">
                        <option value="All"><?php echo __("All"); ?></option>
                        <?php foreach($worked_bands as $band) {
                            echo '<option value="' . $band . '">' . $band . '</option>'."\n";
                        } ?>
                    </select>
                </div>

                <label class="col-md-1 control-label" for="dxcc"><?php echo __("DXCC"); ?></label>
                <div class="col-md-3">
                    <select id="dxcc" name="dxcc" class="form-select">
                        <option value = 'All'><?php echo __("All"); ?></option>
                        <?php
                        if ($dxcc_list->num_rows() > 0) {
                                foreach ($dxcc_list->result() as $dxcc) {
                                    echo '<option value=' . $dxcc->adif . '> ' . ucwords(strtolower($dxcc->name)) . ' - ' . $dxcc->prefix;
                                    if ($dxcc->end != null) {
                                        echo ' ('.__("Deleted DXCC").')';
                                    }
                                    echo '</option>';
                                }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-1 control-label" for="cqzone"><?php echo __("CQ Zone"); ?></label>
                <div class="col-md-3">
                    <select id="cqzone" name="cqzone" class="form-select">
                        <option value = 'All'><?php echo __("All"); ?></option>
                        <?php
                        for ($i = 1; $i<=40; $i++) {
                            echo '<option value='. $i . '>'. $i .'</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 row">
                <div class="col-md-3">
                    <button id="button1id" type="button" name="button1id" class="btn btn-primary ld-ext-right" onclick="timeplot(this.form);"><?php echo __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
                </div>
            </div>

        </form>

        <div id="timeplotter_div">
        </div>
</div>
