<div class="container">
	<br>
	<h2><?= $page_title; ?></h2>
	<p>This data is from <a target="_blank" href="https://www.contestcalendar.com/">https://www.contestcalendar.com/</a></p>

	<?php
	function generateTableRows($contests, $custom_date_format) {
		foreach ($contests as $contest) {
			echo '<tr>';
			echo "<td>{$contest['title']}</td>";
			echo "<td>{$contest['start']->format($custom_date_format)}</td>";
			echo "<td>{$contest['start']->format('H:i')}</td>";
			echo "<td>{$contest['end']->format($custom_date_format.' H:i')}</td>";
			echo "<td><a class='btn btn-secondary btn-sm' href='{$contest['link']}' target='_blank'>Show Details</a></td>";
			echo '</tr>';
		}
	}
	?>

	<div class="row">
		<div class="col">
			<h4 class="mb-3">Today</h4>
			<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed">
				<thead>
					<tr>
						<th>Contest</th>
						<th>Date</th>
						<th>Start</th>
						<th>End</th>
						<th>Link</th>
					</tr>
				</thead>
				<tbody>
					<?php generateTableRows($contestsToday, $custom_date_format); ?>
				</tbody>
			</table>
		</div>
		<div class="col">
			<h4 class="mb-3">Weekend</h4>
			<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed">
				<thead>
					<tr>
						<th>Contest</th>
						<th>Date</th>
						<th>Start</th>
						<th>End</th>
						<th>Link</th>
					</tr>
				</thead>
				<tbody>
					<?php generateTableRows($contestsNextWeekend, $custom_date_format); ?>
				</tbody>
			</table>
		</div>
	</div>
	<h4 class="mb-3">Next Week</h4>
	<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed">
		<thead>
			<tr>
				<th>Contest</th>
				<th>Date</th>
				<th>Start</th>
				<th>End</th>
				<th>Link</th>
			</tr>
		</thead>
		<tbody>
			<?php generateTableRows($contestsNextWeek, $custom_date_format); ?>
		</tbody>
	</table>
</div>