<?php
if ($this->session->userdata('user_date_format')) {
	// If Logged in and session exists
	$custom_date_format = $this->session->userdata('user_date_format');
} else {
	// Get Default date format from /config/wavelog.php
	$custom_date_format = $this->config->item('qso_date_format');
}

if ($result) { ?>
	<div class="table-responsive">
		<table style="width:100%" class="confirmationtable table table-sm table-striped text-center">
			<thead>
				<tr>
					<th><?= __("Callsign"); ?></th>
					<th><?= __("QSO date"); ?></th>
					<th><?= __("Mode"); ?></th>
					<th><?= __("Band"); ?></th>
					<th><?= __("Confirmation date"); ?></th>
					<th><?= __("Type"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($result as $qso) {
					$qsotimestamp = strtotime($qso->col_time_on);
					$confirmationtimestamp = strtotime($qso->rxdate);
					?>
					<tr>
						<td style="text-align: center; vertical-align: middle;" ><a href="javascript:displayQso('<?php echo $qso->col_primary_key; ?>')"><?php echo $qso->col_call; ?></a></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo date($custom_date_format, $qsotimestamp) . ' ' . date('H:i', $qsotimestamp)?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->col_submode == null ? $qso->col_mode : $qso->col_submode;?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php if($qso->col_sat_name != null) { echo $qso->col_sat_name; } else { echo strtolower($qso->col_band); };?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo date($custom_date_format, $confirmationtimestamp);
						if (date('H:i:s', $confirmationtimestamp) !== '00:00:00') {
							echo ' ' . date('H:i', $confirmationtimestamp);
						}
						if ($qso->qslcount ?? 0 != 0) {
							echo ' <a href="javascript:displayQsl('.$qso->col_primary_key.');"><i class="fa fa-id-card"></i></a>';
						}
						?></td>
						<td style="text-align: center; vertical-align: middle;" ><?php echo $qso->type;?></td>
					</tr>

				<?php } ?>
			</tbody>
			<table>
	</div>
<?php } else {
	echo __("No confirmations found.");
} ?>
