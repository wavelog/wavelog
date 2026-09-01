<?php
    $colors = json_decode($user_map_custom);
    $colorCnfm = $colors->qsoconfirm->color;
    $colorWkd = $colors->qso->color;
?>
<script>
    var tileUrl = "<?php echo $this->optionslib->get_option('option_map_tile_server'); ?>";
    var lang_usa_county = "<?= __("County"); ?>";
    var lang_hover_over_a_county = "<?= __("Hover over a county"); ?>";
</script>
<script>
	let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>
<style>
    #countiesmap {
        height: calc(100vh - 300px) !important;
        max-height: 900px !important;
    }

    .dropdown-filters-responsive {
        width: min(850px, 90vw);
        min-width: 600px;
    }

    /* Ensure label sits above multiselect, not beside it (overrides span.multiselect-native-select) */
    span.multiselect-native-select {
        display: block !important;
        width: 100% !important;
    }
    span.multiselect-native-select .btn-group {
        width: 100% !important;
    }
    span.multiselect-native-select .multiselect {
        text-align: left !important;
    }
</style>
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
            <ul class="nav nav-tabs card-header-tabs" id="countiesTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="counties-table-tab" data-bs-toggle="tab" href="#countiestable" role="tab" aria-controls="countiestable" aria-selected="true"><i class="fas fa-table"></i> <?= __("Table"); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="counties-map-tab" onclick="load_counties_map();" data-bs-toggle="tab" href="#countiesmaptab" role="tab" aria-controls="countiesmaptab" aria-selected="false"><i class="fas fa-map"></i> <?= __("Map"); ?></a>
                </li>
            </ul>
        </div>
        <div class="card-body">
    <form class="form" action="<?php echo site_url('awards/counties'); ?>" method="post">
        <div class="mb-4 text-center">
            <div class="dropdown">
                <?php /* data-bs-auto-close="false" needed on the toggle itself, or Bootstrap 5 closes the Filters panel when the nested Band/Mode multiselect dropdowns open */ ?>
                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="countiesFilterDropdown" data-bs-toggle="dropdown" data-bs-auto-close="false" aria-expanded="false"><?= __("Filters") ?></button>
                <button type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
                <?php if ($counties_progress) { ?>
                <button type="button" onclick="load_counties_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-americas"></i> <?= __("Show Counties Map"); ?></button>
                <?php } ?>

                <!-- Dropdown Menu with Filter Content -->
                <div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="countiesFilterDropdown">
                    <div class="card-body filterbody">
						<div class="d-flex justify-content-between align-items-center mb-1">
							<h5><i class="fas fa-filter me-1"></i> <?= __("Filters"); ?></h5>
							<span><?= __("Press 'Apply' to update the table"); ?></span>
						</div>
                        <div class="filter-section">
                        <div class="mb-3 row">
                            <div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-envelope-open-text"></i><?= __("Confirmation"); ?></div>
                            <div class="col-md-9">
                                <div class="form-check-inline">
                                    <input class="btn-check" type="checkbox" name="qsl" value="1" id="countiesQsl" <?php if ($postdata['qsl']) echo ' checked="checked"'; ?>>
                                    <label class="btn btn-outline-primary btn-sm" for="countiesQsl"><?= __("QSL"); ?></label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="btn-check" type="checkbox" name="lotw" value="1" id="countiesLotw" <?php if ($postdata['lotw']) echo ' checked="checked"'; ?>>
                                    <label class="btn btn-outline-primary btn-sm" for="countiesLotw"><?= __("LoTW"); ?></label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="btn-check" type="checkbox" name="eqsl" value="1" id="countiesEqsl" <?php if ($postdata['eqsl']) echo ' checked="checked"'; ?>>
                                    <label class="btn btn-outline-primary btn-sm" for="countiesEqsl"><?= __("eQSL"); ?></label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="btn-check" type="checkbox" name="qrz" value="1" id="countiesQrz" <?php if ($postdata['qrz']) echo ' checked="checked"'; ?>>
                                    <label class="btn btn-outline-primary btn-sm" for="countiesQrz"><?= __("QRZ.com"); ?></label>
                                </div>
                                <div class="form-check-inline">
                                    <input class="btn-check" type="checkbox" name="clublog" value="1" id="countiesClublog" <?php if ($postdata['clublog']) echo ' checked="checked"'; ?>>
                                    <label class="btn btn-outline-primary btn-sm" for="countiesClublog"><?= __("Clublog"); ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="filter-section">
                        <div class="mb-3 row">
                            <div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-tower-broadcast"></i><?= __("Band & Mode"); ?></div>
                            <div class="col-sm-6 mb-2">
                                <label class="form-label mb-1" for="countiesBand"><?= __("Band"); ?></label>
                                <select id="countiesBand" name="band[]" multiple class="form-select form-select-sm">
                                    <?php foreach ($worked_bands as $band) { ?>
                                    <option value="<?= html_escape($band); ?>" <?php if ($postdata['band'] === 'All' ? $band !== 'SAT' : in_array($band, (array)$postdata['band'])) echo ' selected'; ?>><?= html_escape($band); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label mb-1" for="countiesMode"><?= __("Mode"); ?></label>
                                <select id="countiesMode" name="mode[]" multiple class="form-select form-select-sm">
                                    <?php foreach ($modes as $value) { ?>
                                    <option value="<?= html_escape($value); ?>" <?php if ($postdata['mode'] === 'All' || in_array($value, (array)$postdata['mode'])) echo ' selected'; ?>><?= html_escape($value); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" name="button1id" class="btn btn-primary"><i class="fas fa-check me-1"></i> <?= __("Apply"); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="countiesTabContent">
        <div class="tab-pane fade show active" id="countiestable" role="tabpanel" aria-labelledby="counties-table-tab">
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
            echo '<td><a href="javascript:displayStateCounties(\'' . $state . '\')">' . $state . '</a></td>';
            echo '<td><a href="javascript:displayStateCountiesList(\'' . $state . '\',\'worked\')">' . $worked . '</a></td>';
            echo '<td><a href="javascript:displayStateCountiesList(\'' . $state . '\',\'confirmed\')">' . $confirmed . '</a></td>';
            echo '<td><a href="javascript:displayStateCountiesList(\'' . $state . '\',\'target\')">' . $target . '</a></td>';
            echo '<td><a href="javascript:displayStateCountiesList(\'' . $state . '\',\'needed\')">' . $remaining . '</a></td>';
            echo '<td>' . $progress_bar($worked_pct, $colorWkd) . '</td>';
            echo '<td>' . $progress_bar($confirmed_pct, $colorCnfm) . '</td>';
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
        echo '<td>' . $progress_bar($total_worked_pct, $colorWkd) . '</td>';
        echo '<td>' . $progress_bar($total_confirmed_pct, $colorCnfm) . '</td>';
        echo '</tr>';
        ?>
        </tfoot>
    </table>
    <?php } else {
        echo '<div class="alert alert-danger" role="alert">' . __("Nothing found!") . '</div>';
    }
    ?>
        </div>

        <div class="tab-pane fade" id="countiesmaptab" role="tabpanel" aria-labelledby="counties-map-tab">
            <div id="countiesmap" class="map-leaflet"></div>
        </div>
        </div>
    </form>
        </div>
    </div>
</div>
<script>
    var lang_counties_every_band = "<?= __("Every band"); ?>";
    var lang_counties_every_mode = "<?= __("All"); ?>";
</script>
