<script>
    var lang_days_with_qso_short = "<?= __("Days with QSOs"); ?>";
    var lang_qsos_this_weekday = "<?= __('Number of QSOs for this day of the week'); ?>";
</script>

<div class="container">
    <br>
    <h2><?php echo $page_title; ?></h2>

    <br>
	<div class="tabs">
		<ul class="nav nav-tabs" id="myTab" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="home-tab" data-bs-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true"><?= __("Yearly"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="daysofweek-tab" data-bs-toggle="tab" href="#daysofweek" role="tab" aria-controls="daysofweek" aria-selected="false"><?= __("Days of the week"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="monthsofyear-tab" data-bs-toggle="tab" href="#monthsofyear" role="tab" aria-controls="monthsofyear" aria-selected="false"><?= __("Months of the year"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="streaks-tab" data-bs-toggle="tab" href="#streaks" role="tab" aria-controls="streaks" aria-selected="false"><?= __("Streaks"); ?></a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="punchcard-tab" data-bs-toggle="tab" href="#punchcard" role="tab" aria-controls="punchcard" aria-selected="false"><?= __("QSOs of Year"); ?></a>
			</li>
		</ul>
	</div>

    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
            <br/>
            <h3><?= __('Number of days with QSOs each year'); ?></h3>
            <br/>
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
        </div>

        <div class="tab-pane fade" id="daysofweek" role="tabpanel" aria-labelledby="daysofweek-tab">
            <br/>
            <h3><?= __('QSOs breakdown by day of the week'); ?></h3>
            <canvas id="weekdaysChart" width="400" height="150"></canvas>
        </div>

	<div class="tab-pane fade" id="monthsofyear" role="tabpanel" aria-labelledby="monthsofyear-tab">
            <br/>
            <h3><?= __('QSOs breakdown by month of the year'); ?></h3>
            <canvas id="monthChart" width="400" height="150"></canvas>
        </div>

	<div class="tab-pane fade" id="punchcard" role="tabpanel" aria-labelledby="punchcard-tab">
            <br/>
		<select class="form-select form-select-sm me-2 w-auto" id="yr" name="yr">
		<?php
			foreach($years as $yr) {
				echo '<option value="'.$yr.'">'.__("Year")." ".$yr.'</option>';
			}
		?>
		</select>
		<div class="glanceyear-container mt-2">
			<h1 class="glanceyear-header"><?= __("QSOs per Year")?>
				<span class="glanceyear-quantity"></span>
			</h1>
			<div class="glanceyear-content" id="js-glanceyear">
			</div>

			<div class="glanceyear-summary">
				<div class="glanceyear-legend">
					<?= __("Less")?>
					<span class="glanceyear-legend-1"></span>
					<span class="glanceyear-legend-2"></span>
					<span class="glanceyear-legend-3"></span>
					<span class="glanceyear-legend-4"></span>
					<?= __("More")?>
				</div>
				<?= __("Calendar with QSOs") ?><br>
				<span id="debug"></span>
			</div>
	</div>
        </div>


        <div class="tab-pane fade" id="streaks" role="tabpanel" aria-labelledby="streaks-tab">
            <br/>
            <h2><?= __("Longest streak with QSOs in the log"); ?></h2>
            <p><?= __('A maximum of the 10 longest streaks are shown!'); ?></p>

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

    </div>
</div>
