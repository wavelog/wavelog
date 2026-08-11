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

if (!function_exists('calltester_base_call')) {
	// Resolve the home callsign from a (possibly portable) callsign: the longest
	// "/"-separated segment is the base call. e.g. TF/DL2NWK/P -> DL2NWK.
	function calltester_base_call($call) {
		$base = '';
		foreach (explode('/', $call) as $seg) {
			if (strlen($seg) > strlen($base)) {
				$base = $seg;
			}
		}
		return strtoupper(trim($base));
	}
}

if (!function_exists('calltester_render_stats')) {
	// Render a row of colored stat tiles (same style as result.php).
	function calltester_render_stats($stats) {
		echo '<div class="row mb-3 g-2">';
		foreach ($stats as $stat) {
			echo '<div class="col-6 col-md-4">
			<div class="card border-' . $stat['color'] . ' text-center py-2">
				<div class="h5 mb-0 text-' . $stat['color'] . '">' . $stat['value'] . '</div>
				<small class="text-muted">' . $stat['label'] . '</small>
			</div>
		</div>';
		}
		echo '</div>';
	}
}
?>

<h6 class="text-muted"><?= __("DXCC Class Results"); ?></h6>
<?php calltester_render_stats([
	['label' => __("Calls tested"), 'value' => $class_calls_tested, 'color' => 'primary'],
	['label' => __("Execution time"), 'value' => round($class_execution_time, 2) . 's', 'color' => 'info'],
	['label' => __("Issues found"), 'value' => $class_total_issues, 'color' => 'warning'],
]); ?>

<h6 class="text-muted"><?= __("Logbook Model Results"); ?></h6>
<?php calltester_render_stats([
	['label' => __("Calls tested"), 'value' => $model_calls_tested, 'color' => 'primary'],
	['label' => __("Execution time"), 'value' => round($model_execution_time, 2) . 's', 'color' => 'info'],
	['label' => __("Issues found"), 'value' => $model_total_issues, 'color' => 'warning'],
]); ?>

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
					<th><?= __("Gridsquare"); ?></th>
					<th><?= __("Band"); ?></th>
					<th><?= __("Existing DXCC"); ?></th>
					<th><?= __("Existing ADIF"); ?></th>
					<th><?= __("Result DXCC"); ?></th>
					<th><?= __("Result ADIF"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($only_in_class as $qso): ?>
					<?php $base_call = calltester_base_call($qso['callsign']); ?>
					<tr>
						<td><?php echo ++$i; ?></td>
						<td>
							<div class="d-flex align-items-center justify-content-between">
								<a id="edit_qso" href="javascript:displayQso(<?php echo $qso['id']; ?>)"><?php echo htmlspecialchars($qso['callsign']); ?></a>
								<span class="d-flex align-items-center gap-2">
									<a href="https://www.qrz.com/db/<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="<?= __("Lookup on QRZ.com"); ?>"><img style="vertical-align: baseline" width="16" height="16" src="<?php echo base_url(); ?>images/icons/qrz.png" alt="<?= __("Lookup on QRZ.com"); ?>"></a>
									<a href="#" class="calltester-call-search" data-call="<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" data-bs-toggle="tooltip" title="<?= __("Show all QSOs with this callsign"); ?>"><i class="fas fa-search"></i></a>
								</span>
							</div>
						</td>
						<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
						<td><?php echo $qso['station_profile']; ?></td>
						<td><?php echo $qso['gridsquare'] ?? ''; ?></td>
						<td><?php echo $qso['band'] ?? ''; ?></td>
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
					<th><?= __("Gridsquare"); ?></th>
					<th><?= __("Band"); ?></th>
					<th><?= __("Existing DXCC"); ?></th>
					<th><?= __("Existing ADIF"); ?></th>
					<th><?= __("Result DXCC"); ?></th>
					<th><?= __("Result ADIF"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php $i = 0; foreach ($only_in_model as $qso): ?>
					<?php $base_call = calltester_base_call($qso['callsign']); ?>
					<tr>
						<td><?php echo ++$i; ?></td>
						<td>
							<div class="d-flex align-items-center justify-content-between">
								<a id="edit_qso" href="javascript:displayQso(<?php echo $qso['id']; ?>)"><?php echo htmlspecialchars($qso['callsign']); ?></a>
								<span class="d-flex align-items-center gap-2">
									<a href="https://www.qrz.com/db/<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="<?= __("Lookup on QRZ.com"); ?>"><img style="vertical-align: baseline" width="16" height="16" src="<?php echo base_url(); ?>images/icons/qrz.png" alt="<?= __("Lookup on QRZ.com"); ?>"></a>
									<a href="#" class="calltester-call-search" data-call="<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" data-bs-toggle="tooltip" title="<?= __("Show all QSOs with this callsign"); ?>"><i class="fas fa-search"></i></a>
								</span>
							</div>
						</td>
						<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
						<td><?php echo $qso['station_profile']; ?></td>
						<td><?php echo $qso['gridsquare'] ?? ''; ?></td>
						<td><?php echo $qso['band'] ?? ''; ?></td>
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
					<th><?= __("Gridsquare"); ?></th>
					<th><?= __("Band"); ?></th>
					<th><?= __("Existing DXCC"); ?></th>
					<th><?= __("Existing ADIF"); ?></th>
					<th><?= __("Result DXCC"); ?></th>
					<th><?= __("Result ADIF"); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php $i = 0; foreach ($common_issues as $qso): ?>
					<?php $base_call = calltester_base_call($qso['callsign']); ?>
					<tr>
						<td><?php echo ++$i; ?></td>
						<td>
							<div class="d-flex align-items-center justify-content-between">
								<a id="edit_qso" href="javascript:displayQso(<?php echo $qso['id']; ?>)"><?php echo htmlspecialchars($qso['callsign']); ?></a>
								<span class="d-flex align-items-center gap-2">
									<a href="https://www.qrz.com/db/<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="<?= __("Lookup on QRZ.com"); ?>"><img style="vertical-align: baseline" width="16" height="16" src="<?php echo base_url(); ?>images/icons/qrz.png" alt="<?= __("Lookup on QRZ.com"); ?>"></a>
									<a href="#" class="calltester-call-search" data-call="<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" data-bs-toggle="tooltip" title="<?= __("Show all QSOs with this callsign"); ?>"><i class="fas fa-search"></i></a>
								</span>
							</div>
						</td>
						<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
						<td><?php echo $qso['station_profile']; ?></td>
						<td><?php echo $qso['gridsquare'] ?? ''; ?></td>
						<td><?php echo $qso['band'] ?? ''; ?></td>
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
