<div class="container px-3 px-lg-4 mt-3 mb-3">
	<?php if ($this->session->flashdata('error')) { ?>
		<!-- Display Message -->
		<div class="alert alert-danger">
			<p><?php echo $this->session->flashdata('error'); ?></p>
		</div>
	<?php } ?>

	<h2><?= $page_title; ?></h2>
	<p><?= __("This data comes from"); ?> <a target="_blank" href="https://www.contestcalendar.com/">https://www.contestcalendar.com/</a></p>

	<?php
	function generateTableRows($contests, $custom_date_format) {
		if (empty($contests)) { ?>
			<p><?= __("No Contests"); ?></p>
		<?php } else { ?>
			<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed">
				<thead>
					<tr>
						<th><?= __("Contest"); ?></th>
						<th><?= __("Start"); ?></th>
						<th><?= __("End"); ?></th>
						<th><?= __("Link"); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($contests as $contest) { ?>
						<tr>
							<td><b><?php echo $contest['title']; ?></b></td>
							<td><?php echo $contest['start'] == '' ? '' : $contest['start']->format('d M - H:i'); ?></td>
							<td><?php echo $contest['end'] == '' ? '' : $contest['end']->format('d M - H:i'); ?></td>
							<td><a class='btn btn-secondary btn-sm' href='<?php echo $contest['link']; ?>' target='_blank'><?= __("Show Details"); ?></a></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } ?>
	<?php } ?>

	<div class="row mb-3">
		<div class="col">
			<div class="card">
				<div class="card-header">
					<h5><?= __("Today"); ?></h5>
				</div>
				<div class="card-body">
					<?php generateTableRows($contestsToday, $custom_date_format); ?>
				</div>
			</div>
		</div>
		<div class="col">
			<div class="card">
				<div class="card-header">
					<h5><?= __("Weekend"); ?></h5>
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
				<h5><?= __("Next Week"); ?></h5>
			</div>
			<div class="card-body">
				<?php generateTableRows($contestsNextWeek, $custom_date_format); ?>
			</div>
		</div>
	</div>
</div>
