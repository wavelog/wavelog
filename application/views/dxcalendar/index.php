<div class="container">
	<br>
	<h2><?php echo $page_title; ?></h2>
	<p><?= __("This data comes from"); ?> <a target="_blank" href="https://ng3k.com/">https://ng3k.com/</a></p>

		<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed dxcalendar">
			<thead>
				<tr>
					<th><?= __("Date from"); ?></th>
					<th><?= __("Date to"); ?></th>
					<th><?= __("DXCC"); ?></th>
					<th><?= __("Call"); ?></th>
					<th><?= __("QSL Info"); ?></th>
					<th><?= __("Source"); ?></th>
					<th><?= __("Info"); ?></th>
				</tr>
			</thead>
			<tbody>
<?php
foreach($rss as $item) {
	echo '<tr>';
	echo "<td>" . $item->dates[0] ?? '' . "</td>";
	echo "<td>" . $item->dates[1] ?? '' . "</td>";
	echo "<td>";
	if ($item->dxcc_adif >= 1) {
		echo '<a href="javascript:spawnLookupModal(\''.$item->dxcc_adif.'\',\'dxcc\')">';
	}

	if ($item->dxcc_cnfmd) {
		echo '<span class="badge bg-success">';
	} elseif ($item->dxcc_wked) {
		echo '<span class="badge bg-warning">';
	} elseif ($item->no_dxcc)  {
		echo '<span>';
	} else  {
		echo '<span class="badge bg-danger">';
	}

	echo $item->dxcc."</span>";

	if ($item->dxcc_adif >= 1) {
		echo "</a>";
	}
	echo "</td><td><span";

	if ($item->call_cnfmd) {
		echo ' class"badge bg-success">';
	} elseif ($item->call_wked) {
		echo ' class="badge bg-warning">';
	} else  {
		echo ' class="badge bg-danger">';

	}
	echo $item->call."</span></td>";
	echo "<td>$item->qslinfo</td>";
	echo "<td>$item->source</td>";
	echo "<td>$item->info</td>";

	echo '</tr>';
	echo "\n";
}
?>
		</tbody>
	</table>

</div>
