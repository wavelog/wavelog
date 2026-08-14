<?php
$i = 0;

$stats = [
	['label' => __("Callsigns tested"), 'value' => $calls_tested, 'color' => 'primary'],
	['label' => __("Execution time"), 'value' => round($execution_time, 2) . 's', 'color' => 'info'],
	['label' => __("Potential wrong DXCC"), 'value' => count($result), 'color' => 'warning'],
];

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

// Resolve the home callsign from a (possibly portable) callsign: the longest
// "/"-separated segment is the base call. e.g. TF/DL2NWK/P -> DL2NWK.
// Used for lookups so the link targets the station's real callsign and the
// resulting URL has no "/" to break path-based routing.
function calltester_base_call($call) {
	$base = '';
	foreach (explode('/', $call) as $seg) {
		if (strlen($seg) > strlen($base)) {
			$base = $seg;
		}
	}
	return strtoupper(trim($base));
}
?>
<?php
// Get Date format
if($this->session->userdata('user_date_format')) {
	// If Logged in and session exists
	$custom_date_format = $this->session->userdata('user_date_format');
} else {
	// Get Default date format from /config/wavelog.php
	$custom_date_format = $this->config->item('qso_date_format');
}

if ($result) { ?>
<div class="table-responsive" style="max-height:70vh; overflow:auto;">
	<table class="table table-sm table-striped table-bordered table-condensed mb-0">
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
			<?php foreach ($result as $qso): ?>
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

<?php } else { ?>
<div class="alert alert-success mb-0">
	<?= __("No DXCC issues found. All calls have correct DXCC information."); ?>
</div>
<?php } ?>
