<script>
    let lang_activators_map = "<?= __("Activators Map"); ?>";
</script>
<div class="container">
    <h1><?= __("Gridsquare Activators"); ?></h1>

    <form class="form" action="<?php echo site_url('activators'); ?>" method="post" enctype="multipart/form-data">
        <!-- Select Basic -->
        <div class="mb-3 row">
            <label class="col-md-1 control-label" for="band"><?= __("Band"); ?></label>
            <div class="col-md-3">
                <select id="band" name="band" class="form-select">
                    <option value="All" <?php if ($bandselect == "All") echo ' selected'; ?>><?= __("All"); ?></option>
                    <?php foreach ($worked_bands as $band) {
                        echo '<option value="' . $band . '"';
                        if ($bandselect == $band) echo ' selected';
                        echo '>' . $band . '</option>' . "\n";
                    } ?>
                </select>
            </div>
        </div>
        <div class="mb-3 row" id="leogeo" style="display: none;">
            <label class="col-md-1 control-label" for="leogeo">LEO/GEO</label>
            <div class="col-md-3">
                <select id="leogeo" name="leogeo" class="form-select">
                    <option value="both" <?php if ($orbit == 'both') echo ' selected'; ?>><?= _pgettext("Orbiter LEO or GEO", "Both"); ?></option>
                    <option value="leo" <?php if ($orbit == 'leo') echo ' selected'; ?>>LEO</option>
                    <option value="geo" <?php if ($orbit == 'geo') echo ' selected'; ?>>GEO</option>
                </select>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-md-1 control-label" for="mincount"><?= __("Minimum Count"); ?></label>
            <div class="col-md-3">
                <select id="mincount" name="mincount" class="form-select">
                    <?php
                    $i = 1;
                    do {
                        echo '<option value="' . $i . '"';
                        if ($mincount == $i) echo ' selected';
                        echo '>' . $i . '</option>' . "\n";
                        $i++;
                    } while ($i <= $maxactivatedgrids);
                    ?>
                </select>
            </div>

        </div>

        <div class="mb-3 row">
            <label class="col-md-1 control-label" for="button1id"></label>
            <div class="col-md-10">
                <button id="button1id" type="submit" name="button1id" class="btn btn-primary"><?= __("Show"); ?></button>
            </div>
        </div>

    </form>

    <?php
    // Get Date format
    if ($this->session->userdata('user_date_format')) {
        // If Logged in and session exists
        $custom_date_format = $this->session->userdata('user_date_format');
    } else {
        // Get Default date format from /config/wavelog.php
        $custom_date_format = $this->config->item('qso_date_format');
    }
    ?>
    <?php
    $vucc_grids = array();

    if ($activators_array) {

        $result = write_activators($activators_array, $vucc_grids, $custom_date_format, $bandselect, $orbit);
    } else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>

</div>


<?php

function write_activators($activators_array, $vucc_grids, $custom_date_format, $band, $leogeo)
{
    if ($band == '') {
        $band = 'All';
    }
    if ($leogeo == '') {
        $leogeo = 'both';
    }
    $i = 1;
    echo '<table style="width:100%" class="table table-sm activatorstable table-bordered table-hover table-striped table-condensed text-center">
              <thead>
                    <tr>
                        <td>#</td>
                        <td>' . __("Callsign") . '</td>
                        <td>' . __("Count") . '</td>
                        <td>' . __("Gridsquares") . '</td>
                        <td>' . __("Show QSOs") . '</td>
                        <td>' . __("Show Map") . '</td>
                    </tr>
                </thead>
                <tbody>';

    $activators = array();
    foreach ($activators_array as $line) {
        $call = $line->call;
        $grids = $line->grids;
        $count = $line->count;
        if (array_key_exists($line->call, $vucc_grids)) {
            foreach (explode(',', $vucc_grids[$line->call]) as $vgrid) {
                if (!strpos($grids, $vgrid)) {
                    $grids .= ',' . $vgrid;
                }
            }
            $grids = str_replace(' ', '', $grids);
            $grid_array = explode(',', $grids);
            sort($grid_array);
            $count = count($grid_array);
            $grids = implode(', ', $grid_array);
        }
        array_push($activators, array($count, $call, $grids));
    }
    arsort($activators);
    foreach ($activators as $line) {
        echo '<tr>
                <td>' . $i++ . '</td>
                <td>' . $line[1] . '</td>
                <td>' . $line[0] . '</td>
                <td style="text-align: left; font-family: monospace;">' . $line[2] . '</td>
                <td><a href=javascript:displayActivatorsContacts("' . $line[1] . '","' . $band . '","' . $leogeo . '")><i class="fas fa-list"></i></a></td>
                <td><a href=javascript:spawnActivatorsMap("' . $line[1] . '","' . $line[0] . '","' . str_replace(' ', '', $line[2]) . '")><i class="fas fa-globe"></i></a></td>
               </tr>';
    }
    echo '</tfoot></table></div>';
}
