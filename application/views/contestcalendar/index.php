<div class="container">
	<br>
	<h2><?= $page_title; ?></h2>
	<p>This data is from <a target="_blank" href="https://www.contestcalendar.com/">https://www.contestcalendar.com/</a></p>

	<?php
	function generateTableRows($contests, $custom_date_format) { ?>
		<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed">
			<thead>
				<tr>
					<th>Contest</th>
					<th>Start</th>
					<th>End</th>
					<th>Link</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($contests as $contest) { ?>
					<tr>
						<td><b><?php echo $contest['title']; ?></b></td>
						<td><?php echo $contest['start']->format('d M - H:i'); ?></td>
						<td><?php echo $contest['end']->format('d M - H:i'); ?></td>
						<td><a class='btn btn-secondary btn-sm' href='<?php echo $contest['link']; ?>' target='_blank'>Show Details</a></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	<?php } ?>

	<div class="row mb-3">
		<div class="col">
			<div class="card">
				<div class="card-header">
					<h4>Today</h4>
				</div>
				<div class="card-body">
					<?php generateTableRows($contestsToday, $custom_date_format); ?>
				</div>
			</div>
		</div>
		<div class="col">
			<div class="card">
				<div class="card-header">
					<h4>Weekend</h4>
				</div>
				<div class="card-body">
					<?php generateTableRows($contestsNextWeekend, $custom_date_format); ?>
				</div>
			</div>
		</div>
	</div>
	<div class="border-1">
		<div class="card">
			<div class="card-header">
				<h4>Next Week</h4>
			</div>
			<div class="card-body">
				<?php generateTableRows($contestsNextWeek, $custom_date_format); ?>
			</div>
		</div>
	</div>
</div>