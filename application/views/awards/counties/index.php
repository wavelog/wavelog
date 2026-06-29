<div class="container px-3 px-lg-4 mt-3 mb-3">
        <!-- Award Info Box -->
        <div id="awardInfoButton">
            <script>
            var lang_awards_info_button = "<?= __("Award Info"); ?>";
            var lang_award_info_ln1 = "<?= __("US County Award"); ?>";
            var lang_award_info_ln2 = "<?= sprintf(__("The United States of America Counties Award (USA-CA), sponsored by MARAC (Mobile Amateur Radio Awards Club), is issued for confirmed two-way radio contacts with specified numbers of U.S. counties under rules and conditions you can find %s."), "<a href='https://www.marac.org/' target='_blank'>" . __("here") . "</a>"); ?>";
            var lang_award_info_ln3 = "<?= __("USA-CA is available to all licensed amateurs worldwide and is issued to individuals for all county contacts made, regardless of callsigns used, operating locations, or dates."); ?>";
            var lang_award_info_ln4 = "<?= __("Special USA-CA awards are also available to SWLs on a heard basis."); ?>";
            var lang_award_info_ln5 = "<?= __("Fields taken for this Award: State (ADIF: STATE), Stations County (ADIF: CNTY), DXCC (Must be one of 291 (U.S.A.) ,6 (ALASKA) or 110 (HAWAII))"); ?>";
            </script>
            <h2><?php echo $page_title; ?></h2>
            <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
        </div>
        <!-- End of Award Info Box -->
    <div class="card">
        <div class="card-header">
            <?= __("Counties Progress"); ?>
        </div>
        <div class="card-body">
    <?php if ($counties_progress) {
        $progress_bar = function ($pct, $color) {
            return '<div class="progress" style="height: 20px; position: relative;">'
                . '<div class="progress-bar" role="progressbar" style="width: ' . number_format($pct, 2) . '%; background-color: ' . $color . '; font-size: 14px;">' . number_format($pct, 1) . '%</div></div>';
        };

        // Totals across all states
        $total_worked = 0;
        $total_confirmed = 0;
        $total_target = 0;
        foreach ($counties_progress as $counties) {
            $total_worked += $counties['worked'];
            $total_confirmed += $counties['confirmed'];
            $total_target += $counties['target'];
        }
        $total_remaining = max($total_target - $total_worked, 0);
        $total_worked_pct = $total_target > 0 ? ($total_worked / $total_target) * 100 : 0;
        $total_confirmed_pct = $total_target > 0 ? ($total_confirmed / $total_target) * 100 : 0;
        $confirmed_of_worked_pct = $total_worked > 0 ? ($total_confirmed / $total_worked) * 100 : 0;
    ?>
    <!-- Summary stat panels -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="border rounded p-3 h-100 text-center">
                <div class="text-uppercase text-muted small fw-bold"><?= __("Worked Counties"); ?></div>
                <div class="fs-3 fw-bold lh-1 my-2"><?= $total_worked; ?></div>
                <div class="text-muted small"><?= sprintf(__("of %s known counties"), $total_target); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-3 h-100 text-center">
                <div class="text-uppercase text-muted small fw-bold"><?= __("Confirmed Counties"); ?></div>
                <div class="fs-3 fw-bold lh-1 my-2"><?= $total_confirmed; ?></div>
                <div class="text-muted small"><?= sprintf(__("%s%% of worked"), number_format($confirmed_of_worked_pct, 1)); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-3 h-100 text-center">
                <div class="text-uppercase text-muted small fw-bold"><?= __("Remaining Counties"); ?></div>
                <div class="fs-3 fw-bold lh-1 my-2"><?= $total_remaining; ?></div>
                <div class="text-muted small"><?= __("Based on current worked count"); ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="border rounded p-3 h-100 text-center">
                <div class="text-uppercase text-muted small fw-bold"><?= __("Progress"); ?></div>
                <div class="fs-3 fw-bold lh-1 my-2"><?= number_format($total_worked_pct, 1); ?>%</div>
                <div class="text-muted small"><?= sprintf(__("%s%% confirmed overall"), number_format($total_confirmed_pct, 1)); ?></div>
            </div>
        </div>
    </div>

    <table style="width:100%" class="countiesprogresstable table table-sm table-bordered table-hover table-striped table-condensed text-center">
        <thead>
        <tr>
            <td><?= __("State"); ?></td>
            <td><?= __("Worked"); ?></td>
            <td><?= __("Confirmed"); ?></td>
            <td><?= __("Target"); ?></td>
            <td><?= __("Remaining"); ?></td>
            <td><?= __("Worked Progress"); ?></td>
            <td><?= __("Confirmed Progress"); ?></td>
        </tr>
        </thead>
        <tbody>
        <?php
        foreach ($counties_progress as $state => $counties) {
            $worked = $counties['worked'];
            $confirmed = $counties['confirmed'];
            $target = $counties['target'];
            $remaining = max($target - $worked, 0);
            $worked_pct = $target > 0 ? ($worked / $target) * 100 : 0;
            $confirmed_pct = $target > 0 ? ($confirmed / $target) * 100 : 0;

            echo '<tr>';
            echo '<td><a href=\'counties_state?State="' . $state . '"\'>' . $state . '</a></td>';
            echo '<td><a href=\'counties_details?State="' . $state . '"&Type="worked"\'>' . $worked . '</a></td>';
            echo '<td><a href=\'counties_details?State="' . $state . '"&Type="confirmed"\'>' . $confirmed . '</a></td>';
            echo '<td>' . $target . '</td>';
            echo '<td>' . $remaining . '</td>';
            echo '<td>' . $progress_bar($worked_pct, '#fd7e14') . '</td>';
            echo '<td>' . $progress_bar($confirmed_pct, '#198754') . '</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
        <tfoot>
        <?php
        echo '<tr>';
        echo '<td>' . __("Total") . '</td>';
        echo '<td>' . $total_worked . '</td>';
        echo '<td>' . $total_confirmed . '</td>';
        echo '<td>' . $total_target . '</td>';
        echo '<td>' . $total_remaining . '</td>';
        echo '<td>' . $progress_bar($total_worked_pct, '#fd7e14') . '</td>';
        echo '<td>' . $progress_bar($total_confirmed_pct, '#198754') . '</td>';
        echo '</tr>';
        ?>
        </tfoot>
    </table>
    <?php } else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>
        </div>
    </div>
</div>
