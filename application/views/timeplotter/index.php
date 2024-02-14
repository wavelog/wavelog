<script>
    var lang_statistics_timeplotter_contacts_plotted = '<?php echo lang('statistics_timeplotter_contacts_plotted'); ?>';
    var lang_statistics_timeplotter_chart_header = '<?php echo lang('statistics_timeplotter_chart_header'); ?>';
    var lang_statistics_timeplotter_number_of_qsos = '<?php echo lang('statistics_timeplotter_number_of_qsos'); ?>';
    var lang_general_word_time = '<?php echo lang('general_word_time'); ?>';
    var lang_statistics_timeplotter_callsigns_worked = '<?php echo lang('statistics_timeplotter_callsigns_worked'); ?>';
</script>
<div class="container">
    <h2><?php echo lang('menu_timeplotter'); ?></h1>
        <p><?php echo lang('statistics_timeplotter_description'); ?></p>
        <form class="form">

            <div class="mb-3 row">
                <label class="col-md-1 control-label" for="band"><?php echo lang('gen_hamradio_band'); ?></label>
                <div class="col-md-3">
                    <select id="band" name="band" class="form-select">
                        <option value="All"><?php echo lang('general_word_all'); ?></option>
                        <?php foreach($worked_bands as $band) {
                            echo '<option value="' . $band . '">' . $band . '</option>'."\n";
                        } ?>
                    </select>
                </div>

                <label class="col-md-1 control-label" for="dxcc"><?php echo lang('gen_hamradio_dxcc'); ?></label>
                <div class="col-md-3">
                    <select id="dxcc" name="dxcc" class="form-select">
                        <option value = 'All'><?php echo lang('general_word_all'); ?></option>
                        <?php
                        if ($dxcc_list->num_rows() > 0) {
                                foreach ($dxcc_list->result() as $dxcc) {
                                    echo '<option value=' . $dxcc->adif . '> ' . ucwords(strtolower($dxcc->name)) . ' - ' . $dxcc->prefix;
                                    if ($dxcc->end != null) {
                                        echo ' ('.lang('gen_hamradio_deleted_dxcc').')';
                                    }
                                    echo '</option>';
                                }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-1 control-label" for="cqzone"><?php echo lang('gen_hamradio_cq_zone'); ?></label>
                <div class="col-md-3">
                    <select id="cqzone" name="cqzone" class="form-select">
                        <option value = 'All'><?php echo lang('general_word_all'); ?></option>
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
                    <button id="button1id" type="button" name="button1id" class="btn btn-primary ld-ext-right" onclick="timeplot(this.form);"><?php echo lang('general_word_show'); ?><div class="ld ld-ring ld-spin"></div></button>
                </div>
            </div>

        </form>

        <div id="timeplotter_div">
        </div>
</div>
