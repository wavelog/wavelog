<div class="container px-3 px-lg-4 mt-3 mb-3">
    <?php if ($this->session->flashdata('message')) { ?>
        <div class="alert alert-danger" role="alert">
            <p><?php echo $this->session->flashdata('message'); ?></p>
        </div>
    <?php } ?>

    <h2><?= __("AMSAT Satellite Status"); ?></h2>
    <p class="text-muted small">
        <?= sprintf(__("Last-heard status for the past 24 hours. Data source: %s (cached for 15 minutes)."),
            '<a href="https://www.amsat.org/status/" target="_blank">amsat.org/status</a>'); ?>
    </p>

    <div class="card">
        <div class="card-header" id="amsatStatusCardHeader">
            <?= __("AMSAT Satellite Status (Last 24h)"); ?>
        </div>
        <div class="card-body">
            <?php if (empty($sat_order)) { ?>
                <p class="text-muted"><?= __("No status reports available."); ?></p>
            <?php } else { ?>
                <div class="mb-3">
                    <span class="badge bg-success me-1"><?= __("Heard"); ?></span>
                    <span class="badge bg-warning text-dark me-1"><?= __("Crew Active"); ?></span>
                    <span class="badge bg-info text-dark me-1"><?= __("Telemetry Only"); ?></span>
                    <span class="badge bg-danger me-1"><?= __("Not Heard"); ?></span>
                    <span class="badge bg-secondary me-1"><?= __("No report"); ?></span>
                </div>

                <?php
                $status_class = [
                    'Heard'          => 'bg-success',
                    'Crew Active'    => 'bg-warning',
                    'Telemetry Only' => 'bg-info',
                    'Not Heard'      => 'bg-danger',
                ];
                $status_order = ['Heard', 'Crew Active', 'Telemetry Only', 'Not Heard'];
                $reporter_cap = 8;
                ?>

                <style>
                    /* The grid carries .border-top, which draws its top edge and also supplies the
                       theme-aware --bs-border-color (general.css + each theme's overrides.css).
                       .border-start reuses that value, and the cells inherit it. */
                    .amsat-grid {
                        display: grid;
                        grid-template-columns: 160px 140px repeat(24, 32px);
                        grid-auto-rows: minmax(32px, auto);
                        width: max-content;
                        margin: 0 auto;
                    }
                    .amsat-row {
                        display: contents;
                    }
                    .amsat-cell {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        min-width: 0;
                        box-sizing: border-box;
                        border-right: 1px solid var(--bs-border-color);
                        border-bottom: 1px solid var(--bs-border-color);
                        padding: .25rem .5rem;
                        text-align: center;
                        font-size: .875rem;
                    }
                    .amsat-cell-name {
                        justify-content: flex-end;
                        text-align: right;
                    }
                    .amsat-cell-name > span {
                        min-width: 0;
                    }
                    .amsat-cell-aos {
                        white-space: nowrap;
                    }
                    .amsat-cell-hour {
                        padding: 0;
                    }
                </style>

                <div class="table-responsive">
                    <div class="amsat-grid border-top border-start" role="table" aria-labelledby="amsatStatusCardHeader">
                        <div class="amsat-row" role="row">
                        <div class="amsat-cell amsat-cell-name" role="columnheader" aria-rowindex="1" aria-colindex="1"></div>
                        <div class="amsat-cell amsat-cell-aos small" role="columnheader" aria-rowindex="1" aria-colindex="2"><?= __("Next AOS"); ?></div>
                        <?php for ($col = 0; $col < 24; $col++):
                            $age = $col;
                            $show = ($age % 3 == 0);
                            if ($show) {
                                $label = ($age == 0) ? __("now") : sprintf(_ngettext('%dh', '%dh', $age), $age);
                            } else {
                                $label = '';
                            }
                        ?>
                            <div class="amsat-cell amsat-cell-hour small" role="columnheader" aria-rowindex="1" aria-colindex="<?= $col + 3; ?>" title="<?= htmlspecialchars(sprintf(__('%d hours ago'), $age)); ?>"><?= $label; ?></div>
                        <?php endfor; ?>
                        </div>

                        <?php $row_i = 1; foreach ($sat_order as $name):
                            $row_i++;
                            $display = $display_names[$name] ?? $name;
                            $display_esc = htmlspecialchars($display);
                            $name_esc = htmlspecialchars($name);
                            $row = $matrix[$name] ?? [];
                        ?>
                            <div class="amsat-row" role="row">
                            <div class="amsat-cell amsat-cell-name" role="rowheader" aria-rowindex="<?= $row_i; ?>" aria-colindex="1" title="<?= $name_esc; ?>"><span class="text-truncate"><?php
                                if (isset($wl_link[$name])) {
                                    echo '<a href="' . site_url('satellite/flightpath/' . $wl_link[$name]) . '" target="_blank">' . $display_esc . '</a>';
                                } else {
                                    echo $display_esc;
                                }
                            ?></span></div>
                            <div class="amsat-cell amsat-cell-aos small" role="cell" aria-rowindex="<?= $row_i; ?>" aria-colindex="2"><?php
                                if (isset($next_pass[$name])) {
                                    $np = $next_pass[$name];
                                    echo htmlspecialchars($np['time']) . ' <span class="text-muted">UTC</span>';
                                    if ($np['maxel'] !== null) {
                                        echo ' &middot; ' . (int)$np['maxel'] . '&deg;';
                                    }
                                } else {
                                    echo '<span class="text-muted">&mdash;</span>';
                                }
                            ?></div>
                            <?php for ($col = 0; $col < 24; $col++):
                                $age = $col;
                                $cell = $row[$col] ?? null;

                                $end_epoch   = $now - $age * 3600;
                                $start_epoch = $end_epoch - 3600;
                                $window_lbl  = gmdate('M d, H:00', $start_epoch) . '&ndash;' . gmdate('H:i', $end_epoch) . ' UTC';

                                if ($cell === null) { ?>
                                    <div class="amsat-cell amsat-cell-hour" role="cell" aria-rowindex="<?= $row_i; ?>" aria-colindex="<?= $col + 3; ?>" title="<?= $display_esc . ' &middot; ' . $window_lbl . ' &middot; ' . htmlspecialchars(__("No report")); ?>"></div>
                                <?php } else {
                                    $winning = $cell['winning'];
                                    $cls = $status_class[$winning] ?? 'bg-secondary';

                                    $parts = [];
                                    foreach ($status_order as $st) {
                                        $c = $cell['counts'][$st] ?? 0;
                                        if ($c > 0) {
                                            $parts[] = htmlspecialchars($st) . ' &times;' . (int)$c;
                                        }
                                    }
                                    $breakdown = implode(', ', $parts);

                                    $reporters = $cell['reporters'];
                                    $shown = array_slice($reporters, 0, $reporter_cap);
                                    $more = count($reporters) - count($shown);

                                    $tip  = '<strong>' . $display_esc . '</strong><br>';
                                    $tip .= '<small>' . $window_lbl . '</small><br>';
                                    $tip .= '<hr class="my-1">';
                                    $tip .= '<span>' . htmlspecialchars(sprintf(_ngettext('%d report', '%d reports', $cell['total']), $cell['total'])) . ':</span> ' . $breakdown . '<br>';
                                    if (!empty($shown)) {
                                        $tip .= '<hr class="my-1">';
                                        foreach ($shown as $rep) {
                                            $tip .= '<span>' . gmdate('H:i', $rep['epoch']) . '</span> '
                                                  . '<strong>' . htmlspecialchars($rep['callsign']) . '</strong> '
                                                  . htmlspecialchars($rep['grid'])
                                                  . ' &mdash; ' . htmlspecialchars($rep['status']) . '<br>';
                                        }
                                        if ($more > 0) {
                                            $tip .= '<span>+' . (int)$more . ' ' . htmlspecialchars(__('more')) . '</span><br>';
                                        }
                                    }
                                ?>
                                    <div class="amsat-cell amsat-cell-hour <?= $cls; ?>" role="cell" aria-rowindex="<?= $row_i; ?>" aria-colindex="<?= $col + 3; ?>" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-title="<?= htmlspecialchars($tip, ENT_QUOTES); ?>"></div>
                                <?php } ?>
                            <?php endfor; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
