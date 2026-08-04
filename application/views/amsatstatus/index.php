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
        <div class="card-header">
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

                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0 text-center" style="--bs-table-accent-bg: transparent; table-layout: fixed; width: auto; margin: 0 auto;">
                        <colgroup>
                            <col style="width: 160px;">
                            <col style="width: 110px;">
                            <?php for ($c = 0; $c < 24; $c++): ?><col style="width: 32px;"><?php endfor; ?>
                        </colgroup>
                        <thead>
                            <tr>
                                <th class="text-end"></th>
                                <th class="small"><?= __("Next AOS"); ?></th>
                                <?php for ($col = 0; $col < 24; $col++):
                                    $age = $col;
                                    $show = ($age % 3 == 0);
                                    if ($show) {
                                        $label = ($age == 0) ? __("now") : sprintf(_ngettext('%dh', '%dh', $age), $age);
                                    } else {
                                        $label = '';
                                    }
                                ?>
                                    <th class="small" title="<?= htmlspecialchars(sprintf(__('%d hours ago'), $age)); ?>"><?= $label; ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sat_order as $name):
                                $display = $display_names[$name] ?? $name;
                                $display_esc = htmlspecialchars($display);
                                $name_esc = htmlspecialchars($name);
                                $row = $matrix[$name] ?? [];
                            ?>
                                <tr>
                                    <th class="text-end text-nowrap" title="<?= $name_esc; ?>"><?php
                                        if (isset($wl_link[$name])) {
                                            echo '<a href="' . site_url('satellite/flightpath/' . $wl_link[$name]) . '" target="_blank">' . $display_esc . '</a>';
                                        } else {
                                            echo $display_esc;
                                        }
                                    ?></th>
                                    <td class="small"><?php
                                        if (isset($next_pass[$name])) {
                                            $np = $next_pass[$name];
                                            echo htmlspecialchars($np['time']) . ' <span class="text-muted">UTC</span>';
                                            if ($np['maxel'] !== null) {
                                                echo ' &middot; ' . (int)$np['maxel'] . '&deg;';
                                            }
                                        } else {
                                            echo '<span class="text-muted">&mdash;</span>';
                                        }
                                    ?></td>
                                    <?php for ($col = 0; $col < 24; $col++):
                                        $age = $col;
                                        $cell = $row[$col] ?? null;

                                        $end_epoch   = $now - $age * 3600;
                                        $start_epoch = $end_epoch - 3600;
                                        $window_lbl  = gmdate('M d, H:00', $start_epoch) . '&ndash;' . gmdate('H:i', $end_epoch) . ' UTC';

                                        if ($cell === null) { ?>
                                            <td title="<?= $display_esc . ' &middot; ' . $window_lbl . ' &middot; ' . htmlspecialchars(__("No report")); ?>"></td>
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
                                            $tip .= '<small class="text-muted">' . $window_lbl . '</small><br>';
                                            $tip .= '<hr class="my-1">';
                                            $tip .= '<span class="text-muted">' . htmlspecialchars(sprintf(_ngettext('%d report', '%d reports', $cell['total']), $cell['total'])) . ':</span> ' . $breakdown . '<br>';
                                            if (!empty($shown)) {
                                                $tip .= '<hr class="my-1">';
                                                foreach ($shown as $rep) {
                                                    $tip .= '<span class="text-muted">' . gmdate('H:i', $rep['epoch']) . '</span> '
                                                          . '<strong>' . htmlspecialchars($rep['callsign']) . '</strong> '
                                                          . htmlspecialchars($rep['grid'])
                                                          . ' &mdash; ' . htmlspecialchars($rep['status']) . '<br>';
                                                }
                                                if ($more > 0) {
                                                    $tip .= '<span class="text-muted">+' . (int)$more . ' ' . htmlspecialchars(__('more')) . '</span><br>';
                                                }
                                            }
                                        ?>
                                            <td class="<?= $cls; ?>" data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top" data-bs-title="<?= htmlspecialchars($tip, ENT_QUOTES); ?>">&nbsp;</td>
                                        <?php } ?>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
