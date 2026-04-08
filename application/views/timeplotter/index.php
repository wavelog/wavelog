<script>
    var lang_statistics_timeplotter_contacts_plotted = '<?= __("contacts were plotted"); ?>';
    var lang_statistics_timeplotter_chart_header = '<?= __("Time Distribution"); ?>';
    var lang_statistics_timeplotter_number_of_qsos = '<?= __("Number of QSOs"); ?>';
    var lang_general_word_time = '<?= __("Time"); ?>';
    var lang_statistics_timeplotter_callsigns_worked = '<?= __("Callsign(s) worked (max 5)"); ?>';
</script>

<div class="container timeplotter mt-4 mb-4">
    <h2 class="mb-2"><?= __("Timeplotter"); ?></h2>


    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="timeplotter-filters" class="row gy-3 gx-3 align-items-end" novalidate>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="dxcc"><?= __("DXCC"); ?></label>
                    <select id="dxcc" name="dxcc" class="form-select">
                        <option value="All"><?= __("All"); ?></option>
                        <?php
                        if ($dxcc_list->num_rows() > 0) {
                                foreach ($dxcc_list->result() as $dxcc) {
                                    echo '<option value=' . $dxcc->adif . '>' . $dxcc->prefix . ' - ' . ucwords(strtolower($dxcc->name));
                                    if ($dxcc->end != null) {
                                        echo ' ('.__("Deleted DXCC").')';
                                    }
                                    echo '</option>';
                                }
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="cqzone"><?= __("CQ Zone"); ?></label>
                    <select id="cqzone" name="cqzone" class="form-select">
                        <option value="All"><?= __("All"); ?></option>
                        <?php
                        for ($i = 1; $i<=40; $i++) {
                            echo '<option value='. $i . '>'. $i .'</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="band"><?= __("Band"); ?></label>
                    <select id="band" name="band" class="form-select">
                        <option value="All"><?= __("All"); ?></option>
                        <?php foreach($worked_bands as $band) {
                            echo '<option value="' . $band . '">' . $band . '</option>'."\n";
                        } ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label" for="mode"><?= __("Mode"); ?></label>
                    <select id="mode" name="mode" class="form-select">
                        <option value="All"><?= __("All"); ?></option>
                        <?php
                        foreach ($modes as $mode) {
								if ($mode->submode ?? '' == '') {
									echo '<option value="' . $mode . '">' . strtoupper($mode) . '</option>' . "\n";
								}
							}
                        ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <button id="button1id" type="button" name="button1id" class="btn btn-primary w-100 ld-ext-right" onclick="timeplot(this.form);"><?= __("Show"); ?><div class="ld ld-ring ld-spin"></div></button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3" id="timeplotter-summary" style="display:none;">
        <div class="col-6 col-md-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1"><?= __("Best Time Window"); ?></p>
                    <h5 class="mb-0" id="summary-best-window">-</h5>
                    <span class="text-muted small" id="summary-best-window-count"></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1"><?= __("Best Band"); ?></p>
                    <h5 class="mb-0" id="summary-best-band">-</h5>
                    <span class="text-muted small" id="summary-best-band-count"></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1"><?= __("Best Mode"); ?></p>
                    <h5 class="mb-0" id="summary-best-mode">-</h5>
                    <span class="text-muted small" id="summary-best-mode-count"></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card summary-card h-100">
                <div class="card-body">
                    <p class="text-muted small mb-1"><?= __("Total QSOs plotted"); ?></p>
                    <h5 class="mb-0" id="summary-total-qsos">-</h5>
                    <span class="text-muted small" id="summary-date-range"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-3" id="timeplotterTabs" role="tablist" style="display:none;">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="heatmap-tab" data-bs-toggle="tab" data-bs-target="#heatmap-pane" type="button" role="tab" aria-selected="true">
                <?= __("Heatmap"); ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="chart-tab" data-bs-toggle="tab" data-bs-target="#chart-pane" type="button" role="tab" aria-selected="false">
                <?= __("Chart"); ?>
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="timeplotterTabContent">
        <!-- Heatmap Tab Pane -->
        <div class="tab-pane fade show active" id="heatmap-pane" role="tabpanel">
            <div class="card shadow-sm mb-3" id="timeplotter-heatmap-card" style="display:none;">
                <div class="card-header">
                    <span><?= __("Activity by Time of Day (UTC)"); ?></span>
                </div>
                <div class="card-body" style="min-height: 200px;">
                    <div class="heatmap-legend mb-3">
                        <span class="text-muted small me-2"><?= __("Less"); ?></span>
                        <span class="legend-item"><span class="legend-swatch none"></span></span>
                        <span class="legend-item"><span class="legend-swatch glanceyear-legend-1"></span></span>
                        <span class="legend-item"><span class="legend-swatch glanceyear-legend-2"></span></span>
                        <span class="legend-item"><span class="legend-swatch glanceyear-legend-3"></span></span>
                        <span class="legend-item"><span class="legend-swatch glanceyear-legend-4"></span></span>
						<span class="text-muted small me-2"><?= __("More"); ?></span>
                    </div>
                    <div class="heatmap-grid-wrapper">
                        <div id="timeplotterHeatmap" class="heatmap-grid" style="display:none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Tab Pane -->
        <div class="tab-pane fade" id="chart-pane" role="tabpanel">
            <div id="timeplotter_div"></div>
        </div>
    </div>
</div>
