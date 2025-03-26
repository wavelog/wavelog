<?php
echo '
    <table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
	    <thead>
			<tr>
				<th></th>';
			foreach($bands as $band) {
				echo '<th>' . $band . '</th>';
			}
    echo '</tr>
		</thead>
		<tbody>';
foreach ($result as $mode => $value) {

	$showRow = true;
	if ($reduced_mode) {
		$showRow = false;
		foreach ($value as $val) {
			if ($val == 'W' || $val == 'C') {
				$showRow = true;
				break;
			}
		}
	}
	if (strtoupper($mode) == strtoupper($current_mode)) {
		$showRow = true;
	}

	if ($showRow) {
		echo '<tr><td>' . strtoupper($mode) . '</td>';

		$typeMapping = [
			'dxcc' => $dxcc,
			'iota' => $iota,
			'vucc' => substr(trim($grid), 0, 4),
			'cq' => $cqz,
			'was' => $was,
			'sota' => $sota,
			'wwff' => $wwff,
			'itu' => $ituz,
			'continent' => $continent,
			'pota' => $pota,
			'dxcc2' => $dxcc
		];

		if ($type == 'dxcc') {
			$type = 'dxcc2';
		}

		foreach ($value as $key => $val) {
			$searchPhrase = isset($typeMapping[$type]) ? str_replace("&", "%26", $typeMapping[$type]) : '';

			$linkinfo = $searchPhrase
				? "<a href='javascript:displayContacts(\"$searchPhrase\",\"$key\",\"All\",\"All\",\"$mode\",\"" . strtoupper($type) . "\")'>$val</a>"
				: $val;

			$tdClass = ($current_band == $key && strtoupper($current_mode) == strtoupper($mode))
				? "class='border-3 border-danger'"
				: '';

			$content = $val;
			if ($val === 'W') {
				$content = "<div class='bg-danger awardsBgDanger'>$linkinfo</div>";
			} elseif ($val === 'C') {
				$content = "<div class='bg-success awardsBgSuccess'>$linkinfo</div>";
			}

			echo "<td $tdClass>$content</td>";
		}

		echo '</tr>';
	}
}
echo '</tbody></table>';
?>
