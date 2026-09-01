<?php
if (!function_exists('qsl_field_keys')) {
	function qsl_field_keys() {
		return ['qso', 'qsl', 'lotw', 'eqsl', 'qrz', 'clublog'];
	}
}

if (!function_exists('qsl_mode_rows')) {
	/* Normalize a [mode => stats] map to full integer counters and drop modes without any QSOs or confirmations. */
	function qsl_mode_rows($modeStats) {
		$rows = [];
		foreach ($modeStats as $mode => $stats) {
			$row = [];
			$sum = 0;
			foreach (qsl_field_keys() as $field) {
				$row[$field] = (int)($stats[$field] ?? 0);
				$sum += $row[$field];
			}
			if ($sum > 0) {
				$rows[$mode] = $row;
			}
		}
		return $rows;
	}
}

if (!function_exists('qsl_render_table')) {
	/* Render one QSL statistics table (overall, per band or per satellite) with a totals footer.
	 * Confirmation cells carry data-abs/data-pct so qslSetDisplay() can toggle between absolute counts and percentages. */
	function qsl_render_table($title, $rows) {
		$totals = array_fill_keys(qsl_field_keys(), 0);
		foreach ($rows as $stats) {
			foreach (qsl_field_keys() as $field) {
				$totals[$field] += $stats[$field];
			}
		}

		$percent = function ($value, $qsos) {
			return number_format($value / ($qsos ?: 1) * 100, 1) . '%';
		};

		echo '<div class="table-wrapper">';
		echo '<table class="table table-sm table-bordered table-hover table-striped w-100 text-center">';
		echo '<thead>';
		echo '<tr><th colspan="7">' . htmlspecialchars($title) . '</th></tr>';
		echo '<tr><th></th><th>QSO</th><th>QSL</th><th>LoTW</th><th>eQSL</th><th>QRZ</th><th>Clublog</th></tr>';
		echo '</thead>';
		echo '<tbody>';
		foreach ($rows as $mode => $stats) {
			echo '<tr><th>' . htmlspecialchars($mode) . '</th>';
			echo '<td>' . $stats['qso'] . '</td>';
			foreach (['qsl', 'lotw', 'eqsl', 'qrz', 'clublog'] as $field) {
				echo '<td data-abs="' . $stats[$field] . '" data-pct="' . $percent($stats[$field], $stats['qso']) . '">' . $stats[$field] . '</td>';
			}
			echo '</tr>';
		}
		echo '</tbody>';
		echo '<tfoot>';
		echo '<tr><th>' . __("Total") . '</th>';
		echo '<th>' . $totals['qso'] . '</th>';
		foreach (['qsl', 'lotw', 'eqsl', 'qrz', 'clublog'] as $field) {
			echo '<th data-abs="' . $totals[$field] . '" data-pct="' . $percent($totals[$field], $totals['qso']) . '">' . $totals[$field] . '</th>';
		}
		echo '</tr>';
		echo '</tfoot>';
		echo '</table>';
		echo '</div>';
	}
}
?>
<div class="container px-3 px-lg-4 mt-3 mb-3">
	<h2><?= $page_title; ?></h2>
	<div class="card">
		<div class="card-header d-flex justify-content-between align-items-center">
			<span><?= __("QSL Statistics"); ?></span>
			<div class="d-flex align-items-center gap-2">
				<?= __("Year"); ?>
				<select class="form-select form-select-sm w-auto" id="qsl_year" onchange="qslYearFilter()">
					<option value=""><?= __("All"); ?></option>
					<?php foreach (($years ?? []) as $y): ?>
						<option value="<?= $y; ?>"<?php if ((string) ($selected_year ?? '') === (string) $y) { echo ' selected'; } ?>><?= $y; ?></option>
					<?php endforeach; ?>
				</select>
				<div class="btn-group btn-group-sm" role="group">
					<button type="button" class="btn btn-primary" id="qsl_abs" onclick="qslSetDisplay(false)"><?= __("Absolute"); ?></button>
					<button type="button" class="btn btn-outline-primary" id="qsl_pct" onclick="qslSetDisplay(true)"><?= __("Percent"); ?></button>
				</div>
			</div>
		</div>
		<div class="card-body">
			<?php $hasBands = !empty($qsoarray); $hasSats = !empty($qsosatarray ?? []); ?>
			<?php if ($hasBands || $hasSats): ?>
				<?php
				// Grand totals per mode across every band and satellite
				$modeTotals = [];
				foreach ([($qsoarray ?? []), ($qsosatarray ?? [])] as $source) {
					foreach ($source as $mode => $perKey) {
						if (!isset($modeTotals[$mode])) {
							$modeTotals[$mode] = array_fill_keys(qsl_field_keys(), 0);
						}
						foreach ($perKey as $stats) {
							foreach (qsl_field_keys() as $field) {
								$modeTotals[$mode][$field] += (int)($stats[$field] ?? 0);
							}
						}
					}
				}
				?>
				<ul class="nav nav-tabs mb-3" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" id="qsl-modes-tab" data-bs-toggle="tab" href="#qsl-modes" role="tab" aria-controls="qsl-modes" aria-selected="true"><?= __("Modes"); ?></a>
					</li>
					<?php if ($hasBands): ?>
						<li class="nav-item">
							<a class="nav-link" id="qsl-bands-tab" data-bs-toggle="tab" href="#qsl-bands" role="tab" aria-controls="qsl-bands" aria-selected="false"><?= __("Bands"); ?></a>
						</li>
					<?php endif; ?>
					<?php if ($hasSats): ?>
						<li class="nav-item">
							<a class="nav-link" id="qsl-sats-tab" data-bs-toggle="tab" href="#qsl-sats" role="tab" aria-controls="qsl-sats" aria-selected="false"><?= __("Satellites"); ?></a>
						</li>
					<?php endif; ?>
				</ul>
				<div class="tab-content">
					<div class="tab-pane fade show active" id="qsl-modes" role="tabpanel" aria-labelledby="qsl-modes-tab">
						<div class="tables-container">
							<?php qsl_render_table(__("Overall Stats by Mode"), qsl_mode_rows($modeTotals)); ?>
						</div>
					</div>
					<?php if ($hasBands): ?>
						<div class="tab-pane fade" id="qsl-bands" role="tabpanel" aria-labelledby="qsl-bands-tab">
							<div class="tables-container">
								<?php foreach ($bands as $band): ?>
									<?php
									$modeBand = [];
									foreach ($qsoarray as $mode => $perBand) {
										$modeBand[$mode] = $perBand[$band] ?? [];
									}
									qsl_render_table($band, qsl_mode_rows($modeBand));
									?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
					<?php if ($hasSats): ?>
						<div class="tab-pane fade" id="qsl-sats" role="tabpanel" aria-labelledby="qsl-sats-tab">
							<div class="tables-container">
								<?php foreach ($sats as $sat): ?>
									<?php
									$modeSat = [];
									foreach ($qsosatarray as $mode => $perSat) {
										$modeSat[$mode] = $perSat[$sat] ?? [];
									}
									qsl_render_table($sat, qsl_mode_rows($modeSat));
									?>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endif; ?>
				</div>
			<?php else: ?>
				<p class="text-muted mb-0"><?= __("Nothing found!"); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
<script>
function qslYearFilter() {
	var year = document.getElementById('qsl_year').value;
	window.location.href = '<?php echo site_url('statistics/qslstats'); ?>' + (year ? '?year=' + encodeURIComponent(year) : '');
}
function qslSetDisplay(pct) {
	document.querySelectorAll('[data-abs][data-pct]').forEach(function (cell) {
		cell.textContent = pct ? cell.dataset.pct : cell.dataset.abs;
	});
	document.getElementById('qsl_abs').className = pct ? 'btn btn-outline-primary' : 'btn btn-primary';
	document.getElementById('qsl_pct').className = pct ? 'btn btn-primary' : 'btn btn-outline-primary';
	try { localStorage.setItem('qslstats_pct', pct ? '1' : '0'); } catch (e) { /* storage unavailable */ }
}
(function () {
	var pct = false;
	try { pct = localStorage.getItem('qslstats_pct') === '1'; } catch (e) { /* storage unavailable */ }
	if (pct) { qslSetDisplay(true); }
})();
</script>
