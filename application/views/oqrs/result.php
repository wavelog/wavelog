<?php

if ($qsocount > 0) {
$count = 0;
echo '<br />' . sprintf(__("Log search result for %s"), strtoupper($callsign)) . ':<br />';
echo '
    <table style="width:100%" class="result-table table-sm table table-bordered table-hover table-striped table-condensed text-center">
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
	echo '<tr>
			<td>'. strtoupper($mode) .'</td>';
	foreach ($value as $key => $val) {
		echo '<td>' . $val . '</td>';
		if ($val != '-') {
			$count++;
		}
	}
	echo '</tr>';
}
echo '</tbody></table>';
echo strtoupper($callsign) . ' ' . sprintf(
    ngettext('has %d band slot', 'has %d band slots', intval($count)),
    intval($count)
) . ' ' . sprintf(
    ngettext('and has %d QSO in the log', 'and has %d QSOs in the log', intval($qsocount)),
    intval($qsocount)
) . '.<br /><br />';
?>
<button onclick="requestOqrs();" class="btn btn-primary btn-sm" type="button"> <?= __("Request QSL"); ?></button>
<br>
<?php } else {
	echo '<br />' . __("No QSOs found in the log.") . '<br />';
}
	?>
<br>
<button onclick="notInLog();" class="btn btn-primary btn-sm" type="button"> <?= __("Not in log?"); ?></button>