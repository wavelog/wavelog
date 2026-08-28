<script>
    let tileUrl = "<?php echo $this->optionslib->get_option('option_map_tile_server'); ?>";
    let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>

<div class="container px-3 px-lg-4 mt-3 mb-3">
    <!-- Award Info Box -->
    <div id="awardInfoButton" class="mb-3">
        <script>
        var lang_awards_info_button = "<?= __("Award Info"); ?>";
        var lang_award_info_ln1 = "<?= __("JCC - Japan Century Cities Award"); ?>";
        var lang_award_info_ln2 = "<?= __("May be claimed for having contacted (heard) and received a QSL card from an amateur station located in each of at least 100 different cities of Japan."); ?>";
        var lang_award_info_ln3 = "<?= __("JCC-200, 300, 400, 500, 600, 700 and 800 will be issued as separate awards. A list of QSL cards should be arranged in order of JCC reference number, however names of city may be omitted. An additional sticker will be issued at every 50 contacts like 150, 250, 350, 450, 550, 650, 750 cities."); ?>";
        var lang_award_info_ln4 = "<?= sprintf(__("For more information, please visit: %s."), "<a href='https://www.jarl.org/English/4_Library/A-4-2_Awards/Award_Main.htm' target='_blank'>https://www.jarl.org/English/4_Library/A-4-2_Awards/Award_Main.htm</a>"); ?>";
        var lang_award_info_ln5 = "<?= __("Fields taken for this Award: DXCC (Japan) and County (Must contain a valid reference!)"); ?>";
        </script>
        <h2><?php echo $page_title; ?></h2>
        <button type="button" class="btn btn-sm btn-primary me-1" id="displayAwardInfo"><?= __("Award Info"); ?></button>
    </div>
    <!-- End of Award Info Box -->

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="jcc-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="jcc-results-tab" data-bs-toggle="tab" href="#jcc-results" role="tab" aria-controls="jcc-results" aria-selected="true"><i class="fas fa-table"></i> <?= __("Results"); ?></a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="jcc-map-tab" onclick="load_jcc_map();" data-bs-toggle="tab" href="#jcc-map-panel" role="tab" aria-controls="jcc-map-panel" aria-selected="false"><i class="fas fa-map"></i> <?= __("Map"); ?></a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form class="form" action="<?php echo site_url('awards/jcc'); ?>" method="post" enctype="multipart/form-data">
                <div class="mb-4 text-center">
                    <div class="dropdown" data-bs-auto-close="outside">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false"><?= __("Filters"); ?></button>
                        <button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
                        <button type="button" onclick="load_jcc_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-asia"></i> <?= __("Show JCC Map"); ?></button>
                        <button id="button3id" type="button" onclick="export_qsos();" name="button3id" class="btn btn-sm btn-info"<?php echo (($jcc_summary['confirmed'] ?? 0) == 0) ? ' disabled' : ''; ?>><?= __("Export confirmed QSOs"); ?></button>

                        <!-- Dropdown Menu with Filter Content -->
                        <div class="dropdown-menu start-50 translate-middle-x p-3 mt-5 dropdown-filters-responsive" aria-labelledby="filterDropdown">
                            <div class="card-body filterbody">
						<div class="d-flex justify-content-between align-items-center mb-1">
							<h5><i class="fas fa-filter me-1"></i> <?= __("Filters"); ?></h5>
							<span><?= __("Press 'Apply' to update the table"); ?></span>
						</div>
                                <div class="filter-section">
                                <div class="mb-3 row">
                                    <div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-envelope-open-text"></i><?= __("Confirmation"); ?></div>
                                    <div class="col-md-10">
                                        <div class="form-check-inline">
                                            <input class="btn-check" type="checkbox" name="qsl" value="1" id="qsl" <?php if (($postdata['qsl'] ?? null) == 1) echo ' checked="checked"'; ?> >
                                            <label class="btn btn-outline-primary btn-sm" for="qsl"><?= __("QSL"); ?></label>
                                        </div>
                                        <div class="form-check-inline">
                                            <input class="btn-check" type="checkbox" name="lotw" value="1" id="lotw" <?php if (($postdata['lotw'] ?? null) == 1) echo ' checked="checked"'; ?> >
                                            <label class="btn btn-outline-primary btn-sm" for="lotw"><?= __("LoTW"); ?></label>
                                        </div>
                                        <div class="form-check-inline">
                                            <input class="btn-check" type="checkbox" name="eqsl" value="1" id="eqsl" <?php if (($postdata['eqsl'] ?? null) == 1) echo ' checked="checked"'; ?> >
                                            <label class="btn btn-outline-primary btn-sm" for="eqsl"><?= __("eQSL"); ?></label>
                                        </div>
                                        <div class="form-check-inline">
                                            <input class="btn-check" type="checkbox" name="qrz" value="1" id="qrz" <?php if (($postdata['qrz'] ?? null) == 1) echo ' checked="checked"'; ?> >
                                            <label class="btn btn-outline-primary btn-sm" for="qrz"><?= __("QRZ.com"); ?></label>
                                        </div>
                                        <div class="form-check-inline">
                                            <input class="btn-check" type="checkbox" name="clublog" value="1" id="clublog" <?php if (($postdata['clublog'] ?? null) == 1) echo ' checked="checked"'; ?> >
                                            <label class="btn btn-outline-primary btn-sm" for="clublog"><?= __("Clublog"); ?></label>
                                        </div>
                                    </div>
                                </div>

                                <div class="filter-section">
                                <div class="mb-3 row">
                                    <div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-circle-check"></i><?= __("Status"); ?></div>
                                    <div class="col-md-10">
                                        <div class="form-check-inline">
                                            <input class="btn-check" type="checkbox" name="includedeleted" value="1" id="includedeleted" <?php if (($postdata['includedeleted'] ?? null) == 1) echo ' checked="checked"'; ?> >
                                            <label class="btn btn-outline-warning btn-sm" for="includedeleted"><?= __("Include deleted"); ?></label>
                                        </div>
                                    </div>
                                </div>

                                </div>
                                </div>
				<div class="filter-section">
                                					<div class="mb-3 row">
						<div class="w-100 d-flex align-items-center gap-2 mb-2"><i class="fas fa-tower-broadcast"></i><?= __("Band & Mode"); ?></div>
						<div class="col-sm-6 mb-2">
							<label class="form-label mb-1" for="band2"><?= __("Band"); ?></label>
							<select id="band2" name="band" class="form-select form-select-sm">
                                            <option value="All" <?php if (($postdata['band'] ?? 'All') == 'All') echo ' selected'; ?>><?= __("Every band"); ?></option>
                                            <?php foreach ($worked_bands as $band) {
                                                echo '<option value="' . $band . '"';
                                                if (($postdata['band'] ?? 'All') == $band) {
                                                    echo ' selected';
                                                }
                                                echo '>' . $band . '</option>' . "\n";
                                            } ?>
                                        </select>
						</div>
						<div class="col-sm-6">
							<label class="form-label mb-1" for="mode"><?= __("Mode"); ?></label>
							<select id="mode" name="mode" class="form-select form-select-sm">
                                            <option value="All" <?php if (($postdata['mode'] ?? 'All') == 'All') echo ' selected'; ?>><?= __("All"); ?></option>
                                            <?php
                                            foreach ($modes as $mode) {
												echo '<option value="' . $mode . '"';
												if (($postdata['mode'] ?? 'All') == $mode) {
													echo ' selected';
												}
												echo '>' . $mode . '</option>' . "\n";
											}
                                            ?>
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
            </form>

            <div class="tab-content" id="jcc-tab-content">
                <div class="tab-pane fade show active" id="jcc-results" role="tabpanel" aria-labelledby="jcc-results-tab">
                    <div class="border rounded px-3 py-2 mb-3">
                        <div class="d-flex flex-column flex-xl-row justify-content-between align-items-start align-items-xl-center gap-3">
                            <div class="d-flex flex-wrap align-items-center gap-3 gap-xl-4">
                                <div class="d-inline-flex align-items-baseline gap-2">
                                    <span class="small"><?= __("Confirmed"); ?></span>
                                    <span class="fs-5 fw-bold text-success"><?php echo $jcc_summary['confirmed']; ?></span>
                                    <span class="small"><?php echo number_format($jcc_summary['confirmed_percent'], 1); ?>%</span>
                                </div>
                                <div class="d-inline-flex align-items-baseline gap-2">
                                    <span class="small"><?= __("Worked"); ?></span>
                                    <span class="fs-5 fw-bold text-danger"><?php echo $jcc_summary['worked']; ?></span>
                                    <span class="small"><?php echo number_format($jcc_summary['worked_percent'], 1); ?>%</span>
                                </div>
                                <div class="d-inline-flex align-items-baseline gap-2">
                                    <span class="small"><?= __("Total"); ?></span>
                                    <span class="fs-5 fw-bold"><?php echo $jcc_summary['total']; ?></span>
                                </div>
                                <?php if (($postdata['includedeleted'] ?? null) == 1) { ?>
                                    <div class="d-inline-flex align-items-baseline gap-2">
                                        <span class="small"><?= __("Deleted"); ?></span>
                                        <span class="fs-5 fw-bold"><?php echo $jcc_summary['deleted']; ?></span>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="d-flex flex-wrap align-items-center gap-3 small justify-content-xl-end flex-shrink-0">
                                <div class="d-inline-flex align-items-center gap-2">
                                    <span class="award-grid-legend-swatch rounded border border-success text-bg-success"></span>
                                    <span><?= __("Confirmed"); ?></span>
                                </div>
                                <div class="d-inline-flex align-items-center gap-2">
                                    <span class="award-grid-legend-swatch rounded border border-danger text-bg-danger"></span>
                                    <span><?= __("Worked not confirmed"); ?></span>
                                </div>
                                <div class="d-inline-flex align-items-center gap-2">
                                    <span class="award-grid-legend-swatch rounded border text-bg-light"></span>
                                    <span><?= __("Not worked"); ?></span>
                                </div>
                                <?php if (($postdata['includedeleted'] ?? null) == 1) { ?>
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <span class="award-grid-legend-swatch award-grid-slot-deleted rounded border text-bg-light"></span>
                                        <span><?= __("Deleted"); ?></span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="progress award-grid-progress mt-2" role="progressbar" aria-label="<?= __("JCC progress"); ?>" aria-valuenow="<?php echo (int) round($jcc_summary['worked_percent']); ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: <?php echo $jcc_summary['confirmed_percent']; ?>%"></div>
                            <?php if (($jcc_summary['worked_only_percent'] ?? 0) > 0) { ?>
                                <div class="progress-bar bg-danger" style="width: <?php echo $jcc_summary['worked_only_percent']; ?>%"></div>
                            <?php } ?>
                        </div>
                    </div>

                    <?php if (($postdata['includedeleted'] ?? null) == 1) { ?>
                        <div class="alert alert-warning" role="alert">
                            <?= __("Attention! Wavelog does not verify whether a QSO happened before the entity deletion date."); ?>
                        </div>
                    <?php } ?>

                    <?php if (!$has_active_slots) { ?>
                        <div class="alert alert-danger" role="alert">
                            <?= __("No worked or confirmed JCC slots match the current filters."); ?>
                        </div>
                    <?php } ?>

                    <div class="border-top">
                        <?php foreach ($jcc_groups as $group) { ?>
                            <section class="d-flex flex-column flex-lg-row gap-3 py-3 border-bottom">
                                <div class="award-grid-prefecture flex-shrink-0">
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <span class="fs-5 fw-bold"><?php echo $group['prefecture_code']; ?></span>
                                        <span><?php echo $group['prefecture_name']; ?></span>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($group['slots'] as $slot) {
                                        echo $slot;
                                    } ?>
                                </div>
                            </section>
                        <?php } ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="jcc-map-panel" role="tabpanel" aria-labelledby="jcc-map-tab">
                    <div id="jccmap" class="map-leaflet"></div>
                </div>
            </div>
        </div>
    </div>
</div>