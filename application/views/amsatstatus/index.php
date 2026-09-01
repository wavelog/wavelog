<div class="container px-3 px-lg-4 mt-3 mb-3 amsat-status">
    <?php if ($this->session->flashdata('message')) { ?>
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="fas fa-circle-info"></i>
            <span><?php echo $this->session->flashdata('message'); ?></span>
        </div>
    <?php } ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
        <h2 class="mb-0"><?= __("AMSAT Satellite Status"); ?></h2>
    </div>
    <p class="text-muted small mb-4">
        <?= sprintf(__("Last-heard status for the past 24 hours. Data source: %s (cached for 15 minutes)."),
            '<a href="https://www.amsat.org/status/" target="_blank" rel="noopener noreferrer">amsat.org/status</a>'); ?>
    </p>

    <?php if (empty($sat_order)) { ?>
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <i class="fas fa-satellite-dish fa-2x mb-3 opacity-50"></i>
                <p class="mb-0"><?= __("No status reports available."); ?></p>
            </div>
        </div>
    <?php } else {

        // Status -> validated status-palette hex (good -> critical, ordinal).
        $status_hex = [
            'Heard'          => '#22c55e', // green
            'Crew Active'    => '#f59e0b', // amber
            'Telemetry Only' => '#3b82f6', // blue
            'Not Heard'      => '#ef4444', // red
        ];
        $status_icon = [
            'Heard'          => 'fa-solid fa-square',
            'Crew Active'    => 'fa-solid fa-square',
            'Telemetry Only' => 'fa-solid fa-square',
            'Not Heard'      => 'fa-solid fa-square',
        ];
        $status_order = ['Heard', 'Crew Active', 'Telemetry Only', 'Not Heard'];
        $reporter_cap = 8;

        // ---- KPI roll-up over the 24h matrix ----
        $total_sats   = count($sat_order);
        $reachable_1h = 0;   // Heard or Crew Active in the last hour
        $next_aos     = null; // soonest upcoming pass
        foreach ($sat_order as $name) {
            $row = $matrix[$name] ?? [];

            $c0 = $row[0] ?? null;
            if ($c0 !== null && in_array($c0['winning'], ['Heard', 'Crew Active'], true)) {
                $reachable_1h++;
            }

            if (isset($next_pass[$name]['aos'])) {
                $aos = (float) $next_pass[$name]['aos'];
                // Compare the AOS daynum (chronological), not the "H:i" string:
                // string comparison breaks for passes that cross midnight.
                if ($next_aos === null || $aos < $next_aos['aos']) {
                    $next_aos = [
                        'aos'   => $aos,
                        'time'  => $next_pass[$name]['time'],
                        'maxel' => $next_pass[$name]['maxel'],
                        'name'  => $display_names[$name] ?? $name,
                    ];
                }
            }
        }
        ?>

        <!-- KPI row -->
        <div class="amsat-kpis">
            <div class="amsat-kpi">
                <div class="amsat-kpi-val"><?= (int)$total_sats; ?></div>
                <div class="amsat-kpi-lbl"><?= __("Satellites"); ?></div>
            </div>
            <div class="amsat-kpi">
                <div class="amsat-kpi-val" style="color: var(--ams-heard);"><?= (int)$reachable_1h; ?></div>
                <div class="amsat-kpi-lbl"><?= __("Active last hour"); ?></div>
                <div class="amsat-kpi-sub"><?= __("heard or crew active"); ?></div>
            </div>
            <div class="amsat-kpi">
                <div class="amsat-kpi-val"><?php if ($next_aos) { echo htmlspecialchars($next_aos['time']); ?><small class="ms-1">UTC</small><?php } else { ?>&mdash;<?php } ?></div>
                <div class="amsat-kpi-lbl"><?= __("Next pass"); ?></div>
                <div class="amsat-kpi-sub"><?php
                    if ($next_aos) {
                        echo htmlspecialchars($next_aos['name']);
                        if ($next_aos['maxel'] !== null) { echo ' &middot; ' . (int)$next_aos['maxel'] . '&deg;'; }
                    } else {
                        echo __("no upcoming pass");
                    }
                ?></div>
            </div>
        </div>

        <!-- Legend -->
        <div class="amsat-legend" role="list" aria-label="<?= __("Status legend"); ?>">
            <?php foreach ($status_order as $st) {
                $hex  = $status_hex[$st];
                $icon = $status_icon[$st]; ?>
                <span class="amsat-lgnd" role="listitem"><i class="<?= $icon; ?>" style="color: <?= $hex; ?>"></i> <?= htmlspecialchars(__($st)); ?></span>
            <?php } ?>
            <span class="amsat-lgnd" role="listitem"><i class="fa-solid fa-square" style="color: color-mix(in srgb, var(--bs-secondary-color, #6c757d) 25%, transparent);"></i> <?= htmlspecialchars(__("No report")); ?></span>
        </div>

        <!-- Hour pill grid (same layout as the JCC award grid:
             satellite left, status pills for each hour right) -->
        <div class="card amsat-card">
            <div class="card-header" id="amsatStatusCardHeader"><?= __("AMSAT Satellite Status (Last 24h)"); ?></div>
            <div class="card-body">
                <div>
                    <!-- Column headers, mirroring the section layout -->
                    <div class="d-none d-lg-flex align-items-end gap-3 pb-1 mb-1 text-muted">
                        <div class="award-grid-prefecture flex-shrink-0 small fw-bold"><?= __("Satellite"); ?></div>
                        <div class="amsat-sat-aos flex-shrink-0 small fw-bold"><?= __("Next AOS"); ?></div>
                        <div class="small"><?= __("Hours ago (left-most: now)"); ?></div>
                    </div>
                    <?php foreach ($sat_order as $name):
                        $display     = $display_names[$name] ?? $name;
                        $display_esc = htmlspecialchars($display);
                        $row         = $matrix[$name] ?? [];

                        $row_total = 0;
                        foreach ($row as $cell) { if ($cell !== null) { $row_total += $cell['total']; } }
                    ?>
                        <section class="amsat-sat d-flex flex-wrap flex-lg-nowrap align-items-lg-center gap-3">
                            <div class="award-grid-prefecture flex-shrink-0">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <span class="fw-bold"><?php
                                        if (isset($wl_link[$name])) {
                                            echo '<a href="' . site_url('satellite/flightpath/' . $wl_link[$name]) . '" target="_blank" rel="noopener">' . $display_esc . '</a>';
                                        } else {
                                            echo $display_esc;
                                        }
                                    ?></span><?php if ($row_total > 0) { ?>
                                        <span class="amsat-sat-cnt" title="<?= htmlspecialchars(sprintf(_ngettext('%d report in 24h', '%d reports in 24h', $row_total), $row_total)); ?>"><?= (int)$row_total; ?></span>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="amsat-sat-aos flex-shrink-0"><?php
                                if (isset($next_pass[$name])) {
                                    $np = $next_pass[$name];
                                    echo '<strong>' . htmlspecialchars($np['time']) . '</strong>&nbsp;<span class="text-muted">UTC</span>';
                                    if ($np['maxel'] !== null) {
                                        echo '&nbsp;<span class="text-muted">&middot; ' . (int)$np['maxel'] . '&deg;</span>';
                                    }
                                } else {
                                    echo '<span class="text-muted">&mdash;</span>';
                                }
                            ?></div>
                            <div class="amsat-pills">
                                <?php for ($col = 0; $col < 24; $col++):
                                    $age  = $col;
                                    $cell = $row[$col] ?? null;

                                    $end_epoch   = $now - $age * 3600;
                                    $start_epoch = $end_epoch - 3600;
                                    $window_lbl  = gmdate('M d, H:00', $start_epoch) . '&ndash;' . gmdate('H:i', $end_epoch) . ' UTC';
                                    $age_lbl     = $age === 0 ? __("now") : sprintf(__('%d hours ago'), $age);

                                    if ($cell === null) {
                                        $winning = null;
                                        $tip  = '<strong>' . $display_esc . '</strong><br>';
                                        $tip .= '<small>' . $window_lbl . '</small><br>';
                                        $tip .= '<hr class="my-1"><span>' . htmlspecialchars(__("No report")) . '</span>';
                                    } else {
                                        $winning = $cell['winning'];
                                        $hex     = $status_hex[$winning] ?? '#6c757d';

                                        $parts = [];
                                        foreach ($status_order as $st) {
                                            $c = $cell['counts'][$st] ?? 0;
                                            if ($c > 0) {
                                                $parts[] = htmlspecialchars(__($st)) . ' &times;' . (int)$c;
                                            }
                                        }
                                        $breakdown = implode(', ', $parts);

                                        $reporters = $cell['reporters'];
                                        $shown = array_slice($reporters, 0, $reporter_cap);
                                        $more  = count($reporters) - count($shown);

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
                                                      . ' &mdash; ' . htmlspecialchars(__($rep['status'])) . '<br>';
                                            }
                                            if ($more > 0) {
                                                $tip .= '<span>+' . (int)$more . ' ' . htmlspecialchars(__('more')) . '</span><br>';
                                            }
                                        }
                                    }
                                    $aria = $display_esc . ', ' . htmlspecialchars($age_lbl) . ', ' . htmlspecialchars($winning !== null ? __($winning) : __("No report"));
                                ?>
                                    <span class="amsat-grid-pill btn border d-inline-flex align-items-center justify-content-center amsat-slot"<?= $cell !== null ? ' data-st="" style="--cell: ' . $hex . '"' : ''; ?><?= $age === 0 ? ' data-now' : ''; ?> data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-title="<?= htmlspecialchars($tip, ENT_QUOTES); ?>" aria-label="<?= $aria; ?>"><?= htmlspecialchars($age === 0 ? __("now") : $age); ?></span>
                                <?php endfor; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
