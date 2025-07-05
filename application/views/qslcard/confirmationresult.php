<?php if ($result) { ?>
	<div class="table-responsive">
		<table style="width:100%" class="confirmationtable table table-sm table-striped text-center">
			<thead>
				<tr>
					<th><?= __("Callsign"); ?></th>
					<th><?= __("Date"); ?></th>
					<th><?= __("Mode"); ?></th>
					<th><?= __("Band"); ?></th>
					<th><?= __("Sat"); ?></th>
					<th><?= __("Confirmation Date"); ?></th>
					<th><?= __("Type"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($result as $qso) { ?>
					<tr>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->col_call;?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->col_time_on;?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->col_mode;?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->col_band;?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->col_sat_name;?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->rxdate;?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->type;?></td>
					</tr>

				<?php } ?>
			</tbody>
			<table>
	</div>
<?php } else {
	echo __("No confirmations found.");
} ?>
