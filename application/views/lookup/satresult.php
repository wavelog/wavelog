<?php

		$colors = json_decode($user_map_custom);?>
		<style>
			.awardsBgSuccess {
				background-color: <?php echo $colors->qsoconfirm->color; ?> !important;
			}
			.awardsBgDanger {
				background-color: <?php echo $colors->qso->color; ?> !important;
			}
		</style>
<?php
$i = 1;
echo '<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
		<tr>';
foreach ($result as $key => $val) {
	$tdClass = '';
	$content = $val;
	$linkinfo = "<a href='javascript:displayContacts(\"".strtoupper($callsign)."\",\"SAT\",\"$key\",\"All\",\"All\",\"SAT\")'>$val</a>";
	if ($val == 'W') {
		$content = "<div class='bg-danger awardsBgDanger'>$linkinfo</div>";
	} elseif ($val === 'C') {
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
