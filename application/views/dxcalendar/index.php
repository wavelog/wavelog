<div class="container">
	<br>
	<h2><?php echo $page_title; ?></h2>
	<p>This data is from <a target="_blank" href="https://ng3k.com/">https://ng3k.com/</a></p>

		<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed dxcalendar">
			<thead>
				<tr>
					<th>Date from</th>
					<th>Date to</th>
					<th>DXCC</th>
					<th>Call</th>
					<th>QSL info</th>
					<th>Source</th>
					<th>Info</th>
				</tr>
			</thead>
			<tbody>
			<?php
		foreach($rss as $item) {
			echo '<tr>';
			echo "<td>" . $item->dates[0] ?? '' . "</td>";
			echo "<td>" . $item->dates[1] ?? '' . "</td>";
			echo "<td>$item->dxcc</td>";
			echo "<td>$item->call</td>";
			echo "<td>$item->qslinfo</td>";
			echo "<td>$item->source</td>";
			echo "<td>$item->info</td>";

			echo '</tr>';
		}
		?>
		</tbody>
	</table>

</div>
<?php
// Define a function to extract the dates from the date range

