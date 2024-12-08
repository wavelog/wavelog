<script type="text/javascript">
var custom_date_format = "<?php echo $custom_date_format ?>";
</script>
<div class="container">
    <?php if ($this->session->flashdata('message')) { ?>
        <!-- Display Message -->
        <div class="alert alert-danger" role="alert">
            <p><?php echo $this->session->flashdata('message'); ?></p>
        </div>
    <?php } ?>
<div class="table-responsive">
   <br>
    <h2><?= __("Satellite Timers"); ?></h2>
    <?php if ($gridsquare != 0) { ?>
       <p><?= sprintf(__("This data comes from %s and is calculated for the current stationlocation grid %s."), "<a target='_blank' href='https://www.df2et.de/tevel/'>https://www.df2et.de/tevel/</a>", strtoupper($gridsquare)); ?></p>
    <?php } ?>
    <script type="text/javascript">
        let dateArray = [];
        dateArray.push(0);
        <?php $i = 1;
           foreach ($activations as $activation) :
           if ($activation['timestamp'] != null) {
              echo "var tevel".$i."Date = ".$activation['timestamp']." * 1000;\n";
              echo "dateArray.push(tevel".$i."Date);\n";
              echo "var tevel".$i."Workable = ".($activation['timestamp'] > $activation['aos_time'] ? 1 : 0)."\n";
              echo "dateArray.push(tevel".$i."Workable);\n";
           } else {
              echo "dateArray.push(0);\n";
              echo "dateArray.push(0);\n";
           }
           $i++;
           endforeach; ?>

    </script>
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th><?= __("Satellite"); ?></th>
                <th colspan="2"><?= __("Status"); ?></th>
                <th><?= __("Time(d)-Out"); ?></th>
                <th>AOS</th>
                <th>LOS</th>
                <th style="text-align: center !important">AOS <?= __("Azimuth"); ?></th>
                <th style="text-align: center !important">LOS <?= __("Azimuth"); ?></th>
                <th style="text-align: center !important"><?= __("Max Elevation"); ?></th>
                <th style="text-align: center !important"><?= __("Duration"); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($activations as $activation) : ?>
                <tr id="line">
                <td><span><?php echo $activation['sat']; ?></span></td>
                <td><span class="emoji" id="emoji<?php echo $i; ?>">n/a</span></td>
                <td><span id="tevel<?php echo $i; ?>Timer"></span></td>
                <td><span id="tevel<?php echo $i; ?>Timeout">...</span></td>
                <td><span id="tevel<?php echo $i; ?>AosTime"><?php echo $activation['aos_time'] ? date('H:i:s', $activation['aos_time']) : ''; ?></span></td>
                <td><span id="tevel<?php echo $i; ?>LosTime"><?php echo $activation['los_time'] ? date('H:i:s', $activation['los_time']) : ''; ?></span></td>
                <td align="right"><span id="tevel<?php echo $i; ?>Aos"><?php echo $activation['aos'] ? $activation['aos']."°" : ''; ?></span><?php echo $activation['aos'] ? "<span style=\"margin-left: 10px; display: inline-block; transform: rotate(".(-45+$activation['aos'])."deg);\"><i class=\"fas fa-location-arrow fa-xs\"></i></span>" : ''; ?></td>
                <td align="right"><span id="tevel<?php echo $i; ?>Los"><?php echo $activation['los'] ? $activation['los']."°" : ''; ?></span><?php echo $activation['los'] ? "<span style=\"margin-left: 10px; display: inline-block; transform: rotate(".(-45+$activation['los'])."deg);\"><i class=\"fas fa-location-arrow fa-xs\"></i></span>" : ''; ?></td>
                <td align="right"><span id="tevel<?php echo $i; ?>MaxEl"><?php echo $activation['max_elev'] ? $activation['max_elev']."°" : ''; ?></span><?php echo $activation['max_elev'] ? "<span style=\"margin-left: 10px; display: inline-block; transform: rotate(-".$activation['max_elev']."deg);\"><i class=\"fas fa-arrow-right fa-xs\"></i></span>" : ''; ?></td>
                <td align="right"><span id="tevel<?php echo $i; ?>Duration"><?php echo $activation['duration_min'] ? $activation['duration_min']." min" : ''; ?></span></td>
                <td>
                <?php
                   if (strpos($activation['sat'], 'TEVEL') !== false) {
                      echo "<a href=\"https://mailman.amsat.org/hyperkitty/search?q=TEVEL&page=1&mlist=amsat-bb%40amsat.org&sort=date-desc\" target=\"_blank\">" . __("Info") . "</a>";
                   } else if (strpos($activation['sat'], 'UVSQ') !== false) {
                      echo "<a href=\"http://uvsq-sat.projet.latmos.ipsl.fr/\" target=\"_blank\">" . __("Info") . "</a>";
                   } else if (strpos($activation['sat'], 'PO-101') !== false) {
                      echo "<a href=\"https://x.com/Diwata2PH?s=20\" target=\"_blank\">" . __("Info") . "</a>";
                   } else if (strpos($activation['sat'], 'CAS-3H') !== false) {
                      echo "<a href=\"https://www.amsat.org/two-way-satellites/lilacsat-2-cas-3h/\" target=\"_blank\">" . __("Info") . "</a>";
                   } else if (strpos($activation['sat'], 'LEDSAT') !== false) {
                      echo "<a href=\"https://www.esa.int/Education/CubeSats_-_Fly_Your_Satellite/Connect_and_communicate_with_a_satellite_via_the_LEDSAT_Digipeater_Challenge\" target=\"_blank\">" . __("Info") . "</a>";
                   } else if (strpos($activation['sat'], 'INSPIRE7') !== false) {
                      echo "<a href=\"https://inspiresat7.projet.latmos.ipsl.fr/\" target=\"_blank\">" . __("Info") . "</a>";
                   }
                ?>
                </td>
                </tr>
            <?php $i++; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>
