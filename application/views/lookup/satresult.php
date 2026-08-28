<?php

		$colors = json_decode($user_map_custom);
		$_hex = function($c) { return preg_match('/^#[0-9a-fA-F]{6}$/', $c ?? '') ? $c : '#000000'; };
		$_qsoconfirm = $_hex($colors->qsoconfirm->color ?? '');
		$_qso = $_hex($colors->qso->color ?? '');
		?>
		<style>
			.awardsBgSuccess {
				background-color: <?php echo $_qsoconfirm; ?> !important;
			}
			.awardsBgDanger {
				background-color: <?php echo $_qso; ?> !important;
			}
		</style>
<?php
$i = 1;
echo '<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
		<tr>';
foreach ($result as $key => $val) {
	$tdClass = '';
	$content = $val;
	$qslParam = ($val == 'W' || $val == '-') ? '' : ",\"$val\"";
	$linkinfo = "<a href='javascript:displayContacts(\"".strtoupper($callsign)."\",\"SAT\",\"$key\",\"All\",\"All\",\"SAT\"$qslParam)'>$val</a>";
	if ($val == 'W') {
		$content = "<div class='bg-danger awardsBgDanger'>$linkinfo</div>";
	} elseif ($val != '-') {
		$content = "<div class='bg-success awardsBgSuccess'>$linkinfo</div>";
	}
	echo "<td $tdClass><b>$key</b><br />$content</td>";
	if ($i % 10 == 0) {
		echo "</tr><tr>";
	}
	$i++;
}

	echo '</tr>';
echo '</tbody></table>';
?>
