<div class="container">
        <!-- Award Info Box -->
        <br>
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("US County Award"); ?>";
            var lang_award_info_ln2 = "<?= sprintf(__("The United States of America Counties Award (USA-CA), sponsored by MARAC (Mobile Amateur Radio Awards Club), is issued for confirmed two-way radio contacts with specified numbers of U.S. counties under rules and conditions you can find %s."), "<a href='http://www.marac.org/' target='_blank'>" . __("here") . "</a>"); ?>";
            var lang_award_info_ln3 = "<?= __("USA-CA is available to all licensed amateurs worldwide and is issued to individuals for all county contacts made, regardless of callsigns used, operating locations, or dates."); ?>";
            var lang_award_info_ln4 = "<?= __("Special USA-CA awards are also available to SWLs on a heard basis."); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->
    <?php if ($counties_array) { ?>
    <table  style="width:100%" class="countiestable table table-sm table-bordered table-hover table-striped table-condensed text-center">
        <thead>
        <tr>
            <td><?= __("State"); ?></td>
            <td><?= __("Counties Worked"); ?></td>
            <td><?= __("Counties Confirmed"); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php
        $worked = 0;
        $confirmed = 0;
        foreach($counties_array as $counties) {
            echo '<tr>';
            echo '<td>' . $counties['COL_STATE'] .'</td>';
            echo '<td><a href=\'counties_details?State="'.$counties['COL_STATE'].'"&Type="worked"\'>'. $counties['countycountworked'] .'</a></td>';
            echo '<td><a href=\'counties_details?State="'.$counties['COL_STATE'].'"&Type="confirmed"\'>'. $counties['countycountconfirmed'] .'</a></td>';
            echo '</tr>';
            $worked += $counties['countycountworked'];
            $confirmed += $counties['countycountconfirmed'];
        }
        ?><tfoot><tr>
            <td><?= __("Total"); ?></td>
            <td><a href=counties_details?State="All"&Type="worked"><?php echo $worked ?></a></td>
            <td><a href=counties_details?State="All"&Type="confirmed"><?php echo $confirmed ?></a></td>
        </tr></tfoot>
        </tbody>
    </table>
    <?php } else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>
</div>