<div class="container">
<br />
<div class="card">
	<div class="card-header d-flex justify-content-between align-items-center">
		<span><?= __("QSL Statistics"); ?></span>
		<div class="btn-group btn-group-sm" role="group">
			<button type="button" class="btn btn-primary" id="qsl_abs" onclick="qslSetDisplay(false)"><?= __("Absolute"); ?></button>
			<button type="button" class="btn btn-outline-primary" id="qsl_pct" onclick="qslSetDisplay(true)"><?= __("Percent"); ?></button>
		</div>
	</div>
	<?php
	if ($qsoarray) {
		$modeTotals = [];
		foreach ($qsoarray as $mode => $bandData) {
			foreach ($bandData as $band => $stats) {
				if (!isset($modeTotals[$mode])) {
					$modeTotals[$mode] = ['qso'=>0,'qsl'=>0,'lotw'=>0,'eqsl'=>0,'qrz'=>0,'clublog'=>0];
				}
				$modeTotals[$mode]['qso']     += $stats['qso']     ?? 0;
				$modeTotals[$mode]['qsl']     += $stats['qsl']     ?? 0;
				$modeTotals[$mode]['lotw']    += $stats['lotw']    ?? 0;
				$modeTotals[$mode]['eqsl']    += $stats['eqsl']    ?? 0;
				$modeTotals[$mode]['qrz']     += $stats['qrz']     ?? 0;
				$modeTotals[$mode]['clublog'] += $stats['clublog'] ?? 0;
			}
		}
		if ($qsosatarray) {
			foreach ($qsosatarray as $mode => $satData) {
				foreach ($satData as $sat => $stats) {
					if (!isset($modeTotals[$mode])) {
						$modeTotals[$mode] = ['qso'=>0,'qsl'=>0,'lotw'=>0,'eqsl'=>0,'qrz'=>0,'clublog'=>0];
					}
					$modeTotals[$mode]['qso']     += $stats['qso']     ?? 0;
					$modeTotals[$mode]['qsl']     += $stats['qsl']     ?? 0;
					$modeTotals[$mode]['lotw']    += $stats['lotw']    ?? 0;
					$modeTotals[$mode]['eqsl']    += $stats['eqsl']    ?? 0;
					$modeTotals[$mode]['qrz']     += $stats['qrz']     ?? 0;
					$modeTotals[$mode]['clublog'] += $stats['clublog'] ?? 0;
				}
			}
		}

		$grandQso = $grandQsl = $grandLotw = $grandEqsl = $grandQrz = $grandClublog = 0;

		echo '
		<div class="mx-2"><div class="table-wrapper" style="width:100%">
			<table style="width: 100%" class="flex-wrap table-sm table table-bordered table-hover table-striped table-condensed text-center">
				<thead>
					<tr><th colspan="7">' . __("Overall Stats by Mode") . '</th></tr>
				</thead>
				<tbody>
					<tr>
						<th></th>
						<th>QSO</th>
						<th>QSL</th>
						<th>LoTW</th>
						<th>eQSL</th>
						<th>QRZ</th>
						<th>Clublog</th>
					</tr>';

		foreach ($modeTotals as $mode => $totals) {
			if (($totals['qso'] + $totals['qsl'] + $totals['lotw'] + $totals['eqsl'] + $totals['qrz'] + $totals['clublog']) > 0) {
				$grandQso     += $totals['qso'];
				$grandQsl     += $totals['qsl'];
				$grandLotw    += $totals['lotw'];
				$grandEqsl    += $totals['eqsl'];
				$grandQrz     += $totals['qrz'];
				$grandClublog += $totals['clublog'];
				$q = $totals['qso'] ?: 1;
				echo '<tr>
					<th>' . $mode . '</th>
					<td>' . $totals['qso'] . '</td>
					<td data-abs="' . $totals['qsl']     . '" data-pct="' . number_format($totals['qsl']     / $q * 100, 1) . '%">' . $totals['qsl']     . '</td>
					<td data-abs="' . $totals['lotw']    . '" data-pct="' . number_format($totals['lotw']    / $q * 100, 1) . '%">' . $totals['lotw']    . '</td>
					<td data-abs="' . $totals['eqsl']    . '" data-pct="' . number_format($totals['eqsl']    / $q * 100, 1) . '%">' . $totals['eqsl']    . '</td>
					<td data-abs="' . $totals['qrz']     . '" data-pct="' . number_format($totals['qrz']     / $q * 100, 1) . '%">' . $totals['qrz']     . '</td>
					<td data-abs="' . $totals['clublog'] . '" data-pct="' . number_format($totals['clublog'] / $q * 100, 1) . '%">' . $totals['clublog'] . '</td>
				</tr>';
			}
		}

		$gq = $grandQso ?: 1;
		echo '</tbody>
			<tfoot>
				<tr>
					<th>' . __("Total") . '</th>
					<th>' . $grandQso . '</th>
					<th data-abs="' . $grandQsl     . '" data-pct="' . number_format($grandQsl     / $gq * 100, 1) . '%">' . $grandQsl     . '</th>
					<th data-abs="' . $grandLotw    . '" data-pct="' . number_format($grandLotw    / $gq * 100, 1) . '%">' . $grandLotw    . '</th>
					<th data-abs="' . $grandEqsl    . '" data-pct="' . number_format($grandEqsl    / $gq * 100, 1) . '%">' . $grandEqsl    . '</th>
					<th data-abs="' . $grandQrz     . '" data-pct="' . number_format($grandQrz     / $gq * 100, 1) . '%">' . $grandQrz     . '</th>
					<th data-abs="' . $grandClublog . '" data-pct="' . number_format($grandClublog / $gq * 100, 1) . '%">' . $grandClublog . '</th>
				</tr>
			</tfoot>
		</table>
		</div></div>';
	}
	?>
	<div class="tables-container mx-2">
	<?php
	if ($qsoarray) {
		foreach($bands as $band) {
			echo '
			<div class="table-wrapper">
				<table style="width: 100%" class="flex-wrap table-sm table table-bordered table-hover table-striped table-condensed text-center">
					<thead>';
					echo '<tr>';
					echo '<th colspan = 7>' . $band . '</th>';
					echo '</tr>
					</thead>
					<tbody>';
					echo '<tr><th></th>';
						echo '<th>QSO</th>';
						echo '<th>QSL</th>';
						echo '<th>LoTW</th>';
						echo '<th>eQSL</th>';
						echo '<th>QRZ</th>';
						echo '<th>Clublog</th>';
					echo '</tr>';
			$qsototal = 0;
			$qsltotal = 0;
			$lotwtotal = 0;
			$eqsltotal = 0;
			$qrztotal = 0;
			$clublogtotal = 0;
			foreach ($qsoarray as $mode => $value) {
				$qsototal += $value[$band]['qso'] ?? 0;
				$qsltotal += $value[$band]['qsl'] ?? 0;
				$lotwtotal += $value[$band]['lotw'] ?? 0;
				$eqsltotal += $value[$band]['eqsl'] ?? 0;
				$qrztotal += $value[$band]['qrz'] ?? 0;
				$clublogtotal += $value[$band]['clublog'] ?? 0;
				$total = ($value[$band]['qso'] ?? 0) + ($value[$band]['qsl'] ?? 0) + ($value[$band]['lotw'] ?? 0) + ($value[$band]['eqsl'] ?? 0) + ($value[$band]['qrz'] ?? 0) + ($value[$band]['clublog'] ??0 );
				if ($total > 0) {
					$q = ($value[$band]['qso'] ?? 0) ?: 1;
					echo '<tr>
							<th>'. $mode .'</th>';
						echo '<td>' . $value[$band]['qso'] . '</td>';
						echo '<td data-abs="' . $value[$band]['qsl']     . '" data-pct="' . number_format(($value[$band]['qsl']     ?? 0) / $q * 100, 1) . '%">' . $value[$band]['qsl']     . '</td>';
						echo '<td data-abs="' . $value[$band]['lotw']    . '" data-pct="' . number_format(($value[$band]['lotw']    ?? 0) / $q * 100, 1) . '%">' . $value[$band]['lotw']    . '</td>';
						echo '<td data-abs="' . $value[$band]['eqsl']    . '" data-pct="' . number_format(($value[$band]['eqsl']    ?? 0) / $q * 100, 1) . '%">' . $value[$band]['eqsl']    . '</td>';
						echo '<td data-abs="' . $value[$band]['qrz']     . '" data-pct="' . number_format(($value[$band]['qrz']     ?? 0) / $q * 100, 1) . '%">' . $value[$band]['qrz']     . '</td>';
						echo '<td data-abs="' . $value[$band]['clublog'] . '" data-pct="' . number_format(($value[$band]['clublog'] ?? 0) / $q * 100, 1) . '%">' . $value[$band]['clublog'] . '</td>';
					echo '</tr>';
				}
			}
			$bq = $qsototal ?: 1;
			echo '</tbody><tfoot><tr><th>'.__("Total").'</th>';
			echo '<th>' . $qsototal . '</th>';
			echo '<th data-abs="' . $qsltotal    . '" data-pct="' . number_format($qsltotal    / $bq * 100, 1) . '%">' . $qsltotal    . '</th>';
			echo '<th data-abs="' . $lotwtotal   . '" data-pct="' . number_format($lotwtotal   / $bq * 100, 1) . '%">' . $lotwtotal   . '</th>';
			echo '<th data-abs="' . $eqsltotal   . '" data-pct="' . number_format($eqsltotal   / $bq * 100, 1) . '%">' . $eqsltotal   . '</th>';
			echo '<th data-abs="' . $qrztotal    . '" data-pct="' . number_format($qrztotal    / $bq * 100, 1) . '%">' . $qrztotal    . '</th>';
			echo '<th data-abs="' . $clublogtotal . '" data-pct="' . number_format($clublogtotal / $bq * 100, 1) . '%">' . $clublogtotal . '</th>';
			echo '</tr></tfoot></table></div>';
		}
	}
	if ($qsosatarray) {
		foreach($sats as $sat) {
			echo '
			<div class="table-wrapper">
				<table style="width: 100%" class="mx-2 flex-wrap table-sm table table-bordered table-hover table-striped table-condensed text-center">
					<thead>';
					echo '<tr>';
					echo '<th colspan = 7>' . $sat . '</th>';
					echo '</tr>
					</thead>
					<tbody>';
					echo '<tr><th></th>';
						echo '<th>QSO</th>';
						echo '<th>QSL</th>';
						echo '<th>LoTW</th>';
						echo '<th>eQSL</th>';
						echo '<th>QRZ</th>';
						echo '<th>Clublog</th>';
					echo '</tr>';
			$qsototal = 0;
			$qsltotal = 0;
			$lotwtotal = 0;
			$eqsltotal = 0;
			$qrztotal = 0;
			$clublogtotal = 0;
			foreach ($qsosatarray as $mode => $value) {
				$qsototal += $value[$sat]['qso'] ?? 0;
				$qsltotal += $value[$sat]['qsl'] ?? 0;
				$lotwtotal += $value[$sat]['lotw'] ?? 0;
				$eqsltotal += $value[$sat]['eqsl'] ?? 0;
				$qrztotal += $value[$sat]['qrz'] ?? 0;
				$clublogtotal += $value[$sat]['clublog'] ?? 0;
				$total = ($value[$sat]['qso'] ?? 0) + ($value[$sat]['qsl'] ?? 0) + ($value[$sat]['lotw'] ?? 0) + ($value[$sat]['eqsl'] ?? 0) + ($value[$sat]['qrz'] ?? 0) + ($value[$sat]['clublog'] ?? 0);
				if ($total > 0) {
					$q = ($value[$sat]['qso'] ?? 0) ?: 1;
					echo '<tr>
							<th>'. $mode .'</th>';
					echo '<td>' . $value[$sat]['qso'] . '</td>';
					echo '<td data-abs="' . $value[$sat]['qsl']     . '" data-pct="' . number_format(($value[$sat]['qsl']     ?? 0) / $q * 100, 1) . '%">' . $value[$sat]['qsl']     . '</td>';
					echo '<td data-abs="' . $value[$sat]['lotw']    . '" data-pct="' . number_format(($value[$sat]['lotw']    ?? 0) / $q * 100, 1) . '%">' . $value[$sat]['lotw']    . '</td>';
					echo '<td data-abs="' . $value[$sat]['eqsl']    . '" data-pct="' . number_format(($value[$sat]['eqsl']    ?? 0) / $q * 100, 1) . '%">' . $value[$sat]['eqsl']    . '</td>';
					echo '<td data-abs="' . $value[$sat]['qrz']     . '" data-pct="' . number_format(($value[$sat]['qrz']     ?? 0) / $q * 100, 1) . '%">' . $value[$sat]['qrz']     . '</td>';
					echo '<td data-abs="' . $value[$sat]['clublog'] . '" data-pct="' . number_format(($value[$sat]['clublog'] ?? 0) / $q * 100, 1) . '%">' . $value[$sat]['clublog'] . '</td>';
				echo '</tr>';
				}
			}
			$sq = $qsototal ?: 1;
			echo '</tbody><tfoot><tr><th>'.__("Total").'</th>';
			echo '<th>' . $qsototal . '</th>';
			echo '<th data-abs="' . $qsltotal    . '" data-pct="' . number_format($qsltotal    / $sq * 100, 1) . '%">' . $qsltotal    . '</th>';
			echo '<th data-abs="' . $lotwtotal   . '" data-pct="' . number_format($lotwtotal   / $sq * 100, 1) . '%">' . $lotwtotal   . '</th>';
			echo '<th data-abs="' . $eqsltotal   . '" data-pct="' . number_format($eqsltotal   / $sq * 100, 1) . '%">' . $eqsltotal   . '</th>';
			echo '<th data-abs="' . $qrztotal    . '" data-pct="' . number_format($qrztotal    / $sq * 100, 1) . '%">' . $qrztotal    . '</th>';
			echo '<th data-abs="' . $clublogtotal . '" data-pct="' . number_format($clublogtotal / $sq * 100, 1) . '%">' . $clublogtotal . '</th>';
			echo '</tr></tfoot></table></div>';
		}
	}
	?>
	</div>
</div>
</div>
<script>
function qslSetDisplay(pct) {
	document.querySelectorAll('[data-abs][data-pct]').forEach(function (cell) {
		cell.textContent = pct ? cell.dataset.pct : cell.dataset.abs;
	});
	document.getElementById('qsl_abs').className = pct ? 'btn btn-outline-primary' : 'btn btn-primary';
	document.getElementById('qsl_pct').className = pct ? 'btn btn-primary' : 'btn btn-outline-primary';
}
</script>
