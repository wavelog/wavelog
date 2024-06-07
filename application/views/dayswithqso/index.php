<script>
    var lang_days_with_qso_short = "<?= __("Days with QSOs"); ?>";
</script>

<div class="container">

    <br>

    <h2><?php echo $page_title; ?></h2>

    <?php
    if (is_array($result)) {
    echo '<div id="diffDays" class="table-responsive"><table class="qsotable table table-sm table-bordered table-hover table-striped table-condensed">';

            echo '<tr>';
                echo '<th style=\'text-align: center\'>' . __("Year") . '</th>';

                foreach ($result as $master) {
                echo '<td style=\'text-align: center\'>' . $master->Year . '</td>';
                }

                echo '</tr>';

            echo '<tr>';
                echo '<th style=\'text-align: center\'>' . __("Days") . '</th>';

                foreach ($result as $master) {
                echo '<td style=\'text-align: center\'>' . $master->Days . '</td>';
                }

                echo '</tr>';

            echo '</table></div>';
    }
    ?>
    <canvas id="myChartDiff" width="400" height="150"></canvas>
    <h2><?= __("Longest streak with QSOs in the log"); ?></h2>
    <p><?= __("A maximum of the 10 longest streaks are shown!"); ?></p>

    <?php 
    // Get Date format
    if($this->session->userdata('user_date_format')) {
        // If Logged in and session exists
        $custom_date_format = $this->session->userdata('user_date_format');
    } else {
        // Get Default date format from /config/wavelog.php
        $custom_date_format = $this->config->item('qso_date_format');
    }
    ?>

    <?php
    if (is_array($streaks)) {
        echo '<div id="streaks" class="table-responsive"><table class="qsotable table table-sm table-bordered table-hover table-striped table-condensed">';

            echo '<tr>';
                echo '<th style=\'text-align: center\'>' . __("Streak (continuous days with QSOs)") . '</th>';
                echo '<th style=\'text-align: center\'>' . __("Start Date") . '</th>';
                echo '<th style=\'text-align: center\'>' . __("End Date") . '</th>';
                echo '</tr>';

            foreach ($streaks as $streak) {
                echo '<tr>';
                echo '<td style=\'text-align: center\'>' . $streak['highstreak'] . '</td>';
                $beginstreak_newdate = strtotime($streak['beginstreak']);
                echo '<td style=\'text-align: center\'>' . date($custom_date_format, $beginstreak_newdate) . '</td>';
                $endstreak_newdate = strtotime($streak['endstreak']);
                echo '<td style=\'text-align: center\'>' . date($custom_date_format, $endstreak_newdate) . '</td>';
                echo '</tr>';
            }

            echo '</table></div>';
    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . _pgettext("Days with QSOs", "No streak found!") . '</div>';
    }
    ?>

    <h2><?= __("Current streak with QSOs in the log"); ?></h2>
    <?php
    if (is_array($currentstreak)) {
        echo '<div id="streaks" class="table-responsive"><table class="qsotable table table-sm table-bordered table-hover table-striped table-condensed">';

        echo '<tr>';
        echo '<th style=\'text-align: center\'>' . __("Current streak (continuous days with QSOs)") . '</th>';
        echo '<th style=\'text-align: center\'>' . __("Start Date") . '</th>';
        echo '<th style=\'text-align: center\'>' . __("End Date") . '</th>';
        echo '</tr>';

            echo '<tr>';
            echo '<td style=\'text-align: center\'>' . $currentstreak['highstreak'] . '</td>';
            $beginstreak_newdate = strtotime($currentstreak['beginstreak']);
            echo '<td style=\'text-align: center\'>' . date($custom_date_format, $beginstreak_newdate) . '</td>';
            $endstreak_newdate = strtotime($currentstreak['endstreak']);
            echo '<td style=\'text-align: center\'>' . date($custom_date_format, $endstreak_newdate) . '</td>';
            echo '</tr>';

        echo '</table></div>';
    }
    elseif (is_array($almostcurrentstreak)) {
        ?>
        <div class="alert alert-warning" role="alert"><?= __("If you make a QSO today, you can continue to extend your streak... or else your current streak will be broken!"); ?></div>
        <?php
        echo '<div id="streaks" class="table-responsive"><table class="qsotable table table-sm table-bordered table-hover table-striped table-condensed">';

        echo '<tr>';
        echo '<th style=\'text-align: center\'>' . __("Current streak (continuous days with QSOs)") . '</th>';
        echo '<th style=\'text-align: center\'>' . __("Start Date") . '</th>';
        echo '<th style=\'text-align: center\'>' . __("End Date") . '</th>';
        echo '</tr>';

        echo '<tr>';
        echo '<td style=\'text-align: center\'>' . $almostcurrentstreak['highstreak'] . '</td>';
        $beginstreak_newdate = strtotime($almostcurrentstreak['beginstreak']);
        echo '<td style=\'text-align: center\'>' . date($custom_date_format, $beginstreak_newdate) . '</td>';
        $endstreak_newdate = strtotime($almostcurrentstreak['endstreak']);
        echo '<td style=\'text-align: center\'>' . date($custom_date_format, $endstreak_newdate) . '</td>';
        echo '</tr>';

        echo '</table></div>';
    }
    else {
        echo '<div class="alert alert-danger" role="alert">' . __("No current streak found!") . '</div>';
    }
    ?>
</div>
