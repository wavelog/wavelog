<script>
    let tileUrl = "<?php echo $this->optionslib->get_option('option_map_tile_server'); ?>";
    let user_map_custom = JSON.parse('<?php echo $user_map_custom; ?>');
</script>
<style>
    #jccmap {
       height: calc(100vh - 480px) !important;
       max-height: 900px !important;
    }

    .award-grid-legend-swatch {
        width: 1rem;
        height: 1rem;
        display: inline-block;
    }

    .award-grid-legend-swatch-deleted {
        background-image: repeating-linear-gradient(135deg, rgba(0, 0, 0, 0.18) 0, rgba(0, 0, 0, 0.18) 2px, transparent 2px, transparent 6px);
    }

    .award-grid-prefecture {
        min-width: 12rem;
    }

    .award-grid-slots {
        align-content: flex-start;
    }

    .award-grid-slot {
        --award-slot-hover-color: inherit;
        --award-slot-hover-bg: transparent;
        --award-slot-hover-border-color: currentColor;
        --award-slot-focus-shadow: 0 0 0 0.25rem rgba(var(--bs-secondary-rgb), 0.15);
        width: 3rem;
        height: 2rem;
        padding: 0 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.9rem;
        line-height: 1;
        transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    @media (prefers-reduced-motion: reduce) {
        .award-grid-slot {
            transition: none;
        }
    }

    .award-grid-slot:hover {
        background-color: var(--award-slot-hover-bg) !important;
        border-color: var(--award-slot-hover-border-color) !important;
        text-decoration: none;
        box-shadow: none;
    }

    .award-grid-slot:focus-visible {
        outline: 0;
        box-shadow: var(--award-slot-focus-shadow);
    }

    .award-grid-slot.text-bg-success {
        --award-slot-hover-bg: color-mix(in srgb, var(--bs-success) 85%, black);
        --award-slot-hover-border-color: color-mix(in srgb, var(--bs-success) 80%, black);
        --award-slot-focus-shadow: 0 0 0 0.25rem rgba(var(--bs-success-rgb), 0.25);
    }

    .award-grid-slot.text-bg-danger {
        --award-slot-hover-bg: color-mix(in srgb, var(--bs-danger) 85%, black);
        --award-slot-hover-border-color: color-mix(in srgb, var(--bs-danger) 80%, black);
        --award-slot-focus-shadow: 0 0 0 0.25rem rgba(var(--bs-danger-rgb), 0.25);
    }

    .award-grid-slot.text-bg-light {
        --award-slot-hover-bg: color-mix(in srgb, var(--bs-light) 85%, black);
        --award-slot-hover-border-color: color-mix(in srgb, var(--bs-light) 80%, black);
        --award-slot-focus-shadow: 0 0 0 0.25rem rgba(var(--bs-secondary-rgb), 0.15);
    }

    .award-grid-slot-deleted {
        background-image: repeating-linear-gradient(135deg, rgba(0, 0, 0, 0.18) 0, rgba(0, 0, 0, 0.18) 2px, transparent 2px, transparent 6px);
    }

    .award-grid-progress {
        height: 0.5rem;
    }

    @media (max-width: 991.98px) {
        .award-grid-prefecture {
            min-width: 0;
        }
    }
</style>

<div class="container">
    <div id="awardInfoButton" class="py-3">
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

    <form class="form" action="<?php echo site_url('awards/jcc'); ?>" method="post" enctype="multipart/form-data">
        <fieldset>
            <div class="mb-3 row">
                <div class="col-md-2"><?= __("QSL Type"); ?></div>
                <div class="col-md-10">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="qsl" value="1" id="qsl" <?php if (($postdata['qsl'] ?? null) == 1) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="qsl"><?= __("QSL"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="lotw" value="1" id="lotw" <?php if (($postdata['lotw'] ?? null) == 1) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="lotw"><?= __("LoTW"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="eqsl" value="1" id="eqsl" <?php if (($postdata['eqsl'] ?? null) == 1) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="eqsl"><?= __("eQSL"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="qrz" value="1" id="qrz" <?php if (($postdata['qrz'] ?? null) == 1) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="qrz"><?= __("QRZ.com"); ?></label>
                    </div>
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="clublog" value="1" id="clublog" <?php if (($postdata['clublog'] ?? null) == 1) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="clublog"><?= __("Clublog"); ?></label>
                    </div>
                </div>
            </div>

            <div class="mb-3 row">
                <div class="col-md-2"><?= __("Deleted cities"); ?></div>
                <div class="col-md-10">
                    <div class="form-check-inline">
                        <input class="form-check-input" type="checkbox" name="includedeleted" value="1" id="includedeleted" <?php if (($postdata['includedeleted'] ?? null) == 1) echo ' checked="checked"'; ?> >
                        <label class="form-check-label" for="includedeleted"><?= __("Include deleted"); ?></label>
                    </div>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="band2"><?= __("Band"); ?></label>
                <div class="col-md-2">
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
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="mode"><?= __("Mode"); ?></label>
                <div class="col-md-2">
                    <select id="mode" name="mode" class="form-select form-select-sm">
                        <option value="All" <?php if (($postdata['mode'] ?? 'All') == 'All') echo ' selected'; ?>><?= __("All"); ?></option>
                        <?php
                        foreach ($modes->result() as $mode) {
                            if ($mode->submode == null) {
                                echo '<option value="' . $mode->mode . '"';
                                if (($postdata['mode'] ?? 'All') == $mode->mode) {
                                    echo ' selected';
                                }
                                echo '>' . $mode->mode . '</option>' . "\n";
                            } else {
                                echo '<option value="' . $mode->submode . '"';
                                if (($postdata['mode'] ?? 'All') == $mode->submode) {
                                    echo ' selected';
                                }
                                echo '>' . $mode->submode . '</option>' . "\n";
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="col-md-2 control-label" for="button1id"></label>
                <div class="col-md-10">
                    <button id="button2id" type="reset" name="button2id" class="btn btn-sm btn-warning"><?= __("Reset"); ?></button>
                    <button id="button1id" type="submit" name="button1id" class="btn btn-sm btn-primary"><?= __("Show"); ?></button>
                    <button type="button" onclick="load_jcc_map();" class="btn btn-info btn-sm"><i class="fas fa-globe-asia"></i> <?= __("Show JCC Map"); ?></button>
                    <button id="button3id" type="button" onclick="export_qsos();" name="button3id" class="btn btn-sm btn-info"<?php echo !$has_active_slots ? ' disabled' : ''; ?>><?= __("Export confirmed QSOs"); ?></button>
                </div>
            </div>
        </fieldset>
    </form>

    <ul class="nav nav-tabs" id="jcc-tabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="jcc-results-tab" data-bs-toggle="tab" data-bs-target="#jcc-results" type="button" role="tab" aria-controls="jcc-results" aria-selected="true"><?= __("Results"); ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="jcc-map-tab" data-bs-toggle="tab" data-bs-target="#jcc-map-panel" type="button" role="tab" aria-controls="jcc-map-panel" aria-selected="false" onclick="load_jcc_map();"><?= __("Map"); ?></button>
        </li>
    </ul>

    <div class="tab-content" id="jcc-tab-content">
        <div class="tab-pane fade show active" id="jcc-results" role="tabpanel" aria-labelledby="jcc-results-tab">
            <div class="mt-4">
                <div class="border rounded px-3 py-2 mb-3">
                    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-start align-items-xl-center gap-3">
                        <div class="d-flex flex-wrap align-items-center gap-3 gap-xl-4">
                            <div class="d-inline-flex align-items-baseline gap-2">
                                <span class="small text-body-secondary"><?= __("Confirmed"); ?></span>
                                <span class="fs-5 fw-bold text-success"><?php echo $jcc_summary['confirmed']; ?></span>
                                <span class="small text-body-secondary"><?php echo number_format($jcc_summary['confirmed_percent'], 1); ?>%</span>
                            </div>
                            <div class="d-inline-flex align-items-baseline gap-2">
                                <span class="small text-body-secondary"><?= __("Worked"); ?></span>
                                <span class="fs-5 fw-bold text-danger"><?php echo $jcc_summary['worked']; ?></span>
                                <span class="small text-body-secondary"><?php echo number_format($jcc_summary['worked_percent'], 1); ?>%</span>
                            </div>
                            <div class="d-inline-flex align-items-baseline gap-2">
                                <span class="small text-body-secondary"><?= __("Total"); ?></span>
                                <span class="fs-5 fw-bold"><?php echo $jcc_summary['total']; ?></span>
                            </div>
                            <?php if (($postdata['includedeleted'] ?? null) == 1) { ?>
                                <div class="d-inline-flex align-items-baseline gap-2">
                                    <span class="small text-body-secondary"><?= __("Deleted"); ?></span>
                                    <span class="fs-5 fw-bold text-body-secondary"><?php echo $jcc_summary['deleted']; ?></span>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3 small text-body-secondary justify-content-xl-end flex-shrink-0">
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
                                    <span class="award-grid-legend-swatch award-grid-legend-swatch-deleted rounded border text-bg-light"></span>
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
                                    <span class="fw-bold"><?php echo $group['prefecture_name']; ?></span>
                                </div>
                            </div>
                            <div class="award-grid-slots d-flex flex-wrap gap-2">
                                <?php foreach ($group['slots'] as $slot) {
                                    echo awards_render_jcc_grid_slot($slot, $postdata);
                                } ?>
                            </div>
                        </section>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="jcc-map-panel" role="tabpanel" aria-labelledby="jcc-map-tab">
            <div class="mt-4">
                <div id="jccmap" class="map-leaflet"></div>
            </div>
        </div>
    </div>
</div>
