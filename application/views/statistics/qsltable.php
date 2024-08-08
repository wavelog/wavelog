<div class="container">
<br />
<div class="card">
	<div class="card-header">
		<?= __("QSL Statistics"); ?>
	</div>
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
				$qsototal += $value[$band]['qso'];
				$qsltotal += $value[$band]['qsl'];
				$lotwtotal += $value[$band]['lotw'];
				$eqsltotal += $value[$band]['eqsl'];
				$qrztotal += $value[$band]['qrz'];
				$clublogtotal += $value[$band]['clublog'];
				$total = $value[$band]['qso'] + $value[$band]['qsl'] + $value[$band]['lotw'] + $value[$band]['eqsl'] + $value[$band]['qrz'] + $value[$band]['clublog'];
				if ($total > 0) {
					echo '<tr>
							<th>'. $mode .'</th>';
						echo '<td>' . $value[$band]['qso'] . '</td>';
						echo '<td>' . $value[$band]['qsl'] . '</td>';
						echo '<td>' . $value[$band]['lotw'] . '</td>';
						echo '<td>' . $value[$band]['eqsl'] . '</td>';
						echo '<td>' . $value[$band]['qrz'] . '</td>';
						echo '<td>' . $value[$band]['clublog'] . '</td>';
					echo '</tr>';
				}
			}
			echo '</tbody><tfoot><tr><th>'.__("Total").'</th>';
			echo '<th>' . $qsototal . '</th>';
			echo '<th>' . $qsltotal . '</th>';
			echo '<th>' . $lotwtotal . '</th>';
			echo '<th>' . $eqsltotal . '</th>';
			echo '<th>' . $qrztotal . '</th>';
			echo '<th>' . $clublogtotal . '</th>';
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
				$qsototal += $value[$sat]['qso'];
				$qsltotal += $value[$sat]['qsl'];
				$lotwtotal += $value[$sat]['lotw'];
				$eqsltotal += $value[$sat]['eqsl'];
				$qrztotal += $value[$sat]['qrz'];
				$clublogtotal += $value[$sat]['clublog'];
				$total = $value[$sat]['qso'] + $value[$sat]['qsl'] + $value[$sat]['lotw'] + $value[$sat]['eqsl'] + $value[$sat]['qrz'] + $value[$sat]['clublog'];
				if ($total > 0) {
				echo '<tr>
						<th>'. $mode .'</th>';
					echo '<td>' . $value[$sat]['qso'] . '</td>';
					echo '<td>' . $value[$sat]['qsl'] . '</td>';
					echo '<td>' . $value[$sat]['lotw'] . '</td>';
					echo '<td>' . $value[$sat]['eqsl'] . '</td>';
					echo '<td>' . $value[$sat]['qrz'] . '</td>';
					echo '<td>' . $value[$sat]['clublog'] . '</td>';
				echo '</tr>';
				}
			}
			echo '</tbody><tfoot><tr><th>'.__("Total").'</th>';
			echo '<th>' . $qsototal . '</th>';
			echo '<th>' . $qsltotal . '</th>';
			echo '<th>' . $lotwtotal . '</th>';
			echo '<th>' . $eqsltotal . '</th>';
			echo '<th>' . $qrztotal . '</th>';
			echo '<th>' . $clublogtotal . '</th>';
			echo '</tr></tfoot></table></div>';
		}
	}
	?>
	</div>
</div>
</div>
