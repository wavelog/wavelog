<?php
$i = 0;

// Get Date format
if($this->session->userdata('user_date_format')) {
	// If Logged in and session exists
	$custom_date_format = $this->session->userdata('user_date_format');
} else {
	// Get Default date format from /config/wavelog.php
	$custom_date_format = $this->config->item('qso_date_format');
}
?>

<div class="row mb-4">
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0"><?= __("DXCC Class Results"); ?></h6>
			</div>
			<div class="card-body">
				<p><strong><?= __("Calls tested:"); ?></strong> <?= $class_calls_tested; ?></p>
				<p><strong><?= __("Execution time:"); ?></strong> <?= round($class_execution_time, 2); ?>s</p>
				<p><strong><?= __("Issues found:"); ?></strong> <?= $class_total_issues; ?></p>
			</div>
		</div>
	</div>
	<div class="col-md-6">
		<div class="card">
			<div class="card-header">
				<h6 class="card-title mb-0"><?= __("Logbook Model Results"); ?></h6>
			</div>
			<div class="card-body">
				<p><strong><?= __("Calls tested:"); ?></strong> <?= $model_calls_tested; ?></p>
				<p><strong><?= __("Execution time:"); ?></strong> <?= round($model_execution_time, 2); ?>s</p>
				<p><strong><?= __("Issues found:"); ?></strong> <?= $model_total_issues; ?></p>
			</div>
		</div>
	</div>
</div>

<div class="row mb-4">
	<div class="col-12">
		<div class="alert alert-info">
			<strong><?= __("Comparison Summary"); ?></strong><br>
			- <?= __("Only found in DXCC Class:"); ?> <?= count($only_in_class); ?><br>
			- <?= __("Only found in Logbook Model:"); ?> <?= count($only_in_model); ?><br>
			- <?= __("Found in both methods:"); ?> <?= count($common_issues); ?>
		</div>
	</div>
</div>

<?php if ($only_in_class): ?>
<div class="mb-4">
	<h6 class="text-danger"><?= __("Issues found only in DXCC Class (not in Logbook Model):"); ?> <?= count($only_in_class); ?></h6>
	<div class="table-responsive" style="max-height:50vh; overflow:auto;">
		<table class="table table-sm table-striped table-bordered">
			<thead>
				<tr>
					<th>#</th>
					<th><?= __("Callsign"); ?></th>
					<th><?= __("QSO Date"); ?></th>
					<th><?= __("Station Profile"); ?></th>
					<th><?= __("Existing DXCC"); ?></th>
					<th><?= __("Existing ADIF"); ?></th>
					<th><?= __("Result DXCC"); ?></th>
					<th><?= __("Result ADIF"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($only_in_class as $qso): ?>
					<tr>
						<td><?php echo ++$i; ?></td>
						<td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso['id'] . ')">' . htmlspecialchars($qso['callsign']) . '</a>'; ?></td>
						<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
						<td><?php echo $qso['station_profile']; ?></td>
						<td><?php echo htmlspecialchars(ucwords(strtolower($qso['existing_dxcc']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo $qso['existing_adif']; ?></td>
						<td><?php echo htmlspecialchars(ucwords(strtolower($qso['result_country']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo $qso['result_adif']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php endif; ?>

<?php if ($only_in_model): ?>
<div class="mb-4">
	<h6 class="text-warning"><?= __("Issues found only in Logbook Model (not in DXCC Class):"); ?> <?= count($only_in_model); ?></h6>
	<div class="table-responsive" style="max-height:50vh; overflow:auto;">
		<table class="table table-sm table-striped table-bordered">
			<thead>
				<tr>
					<th>#</th>
					<th><?= __("Callsign"); ?></th>
					<th><?= __("QSO Date"); ?></th>
					<th><?= __("Station Profile"); ?></th>
					<th><?= __("Existing DXCC"); ?></th>
					<th><?= __("Existing ADIF"); ?></th>
					<th><?= __("Result DXCC"); ?></th>
					<th><?= __("Result ADIF"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php $i = 0; foreach ($only_in_model as $qso): ?>
					<tr>
						<td><?php echo ++$i; ?></td>
						<td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso['id'] . ')">' . htmlspecialchars($qso['callsign']) . '</a>'; ?></td>
						<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
						<td><?php echo $qso['station_profile']; ?></td>
						<td><?php echo htmlspecialchars(ucwords(strtolower($qso['existing_dxcc']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo $qso['existing_adif']; ?></td>
						<td><?php echo htmlspecialchars(ucwords(strtolower($qso['result_country']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo $qso['result_adif']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php endif; ?>

<?php if ($common_issues): ?>
<div class="mb-4">
	<h6 class="text-success"><?= __("Issues found in both methods:"); ?> <?= count($common_issues); ?></h6>
	<div class="table-responsive" style="max-height:50vh; overflow:auto;">
		<table class="table table-sm table-striped table-bordered">
			<thead>
				<tr>
					<th>#</th>
					<th><?= __("Callsign"); ?></th>
					<th><?= __("QSO Date"); ?></th>
					<th><?= __("Station Profile"); ?></th>
					<th><?= __("Existing DXCC"); ?></th>
					<th><?= __("Existing ADIF"); ?></th>
					<th><?= __("Result DXCC"); ?></th>
					<th><?= __("Result ADIF"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php $i = 0; foreach ($common_issues as $qso): ?>
					<tr>
						<td><?php echo ++$i; ?></td>
						<td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso['id'] . ')">' . htmlspecialchars($qso['callsign']) . '</a>'; ?></td>
						<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
						<td><?php echo $qso['station_profile']; ?></td>
						<td><?php echo htmlspecialchars(ucwords(strtolower($qso['existing_dxcc']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo $qso['existing_adif']; ?></td>
						<td><?php echo htmlspecialchars(ucwords(strtolower($qso['result_country']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
						<td><?php echo $qso['result_adif']; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php endif; ?>

<?php if (!$only_in_class && !$only_in_model && !$common_issues): ?>
<div class="alert alert-success">
	<?= __("No DXCC issues found in either method. All calls have correct DXCC information."); ?>
</div>
<?php endif; ?>