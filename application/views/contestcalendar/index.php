<div class="container">
	<br>
	<h2><?php echo $page_title; ?></h2>
	<p>This data is from <a target="_blank" href="https://www.contestcalendar.com/">https://www.contestcalendar.com/</a></p>

	<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed dxcalendar">
		<thead>
			<tr>
				<th>Contest</th>
				<th>Start</th>
				<th>End</th>
				<th>Link</th>
			</tr>
		</thead>
		<tbody>
			<?php
			foreach ($rss as $contest) {
				echo '<tr>';
				echo "<td>" . $contest['title'] . "</td>";
				echo "<td>" . $contest['start'] . "</td>";
				echo "<td>" . $contest['end'] . "</td>";
				echo "<td><a href='" . $contest['link'] . "' target=_blank>" . $contest['link'] . "</a></td>";
				echo '</tr>';
			}
			?>
		</tbody>
	</table>
</div>