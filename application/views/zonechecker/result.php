<?php
$i = 0;

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

// Determine zone type from data (default to CQ)
$zone_type = isset($zone_type) ? $zone_type : 'cq';
$is_itu = ($zone_type === 'itu');
$zone_label = $is_itu ? 'ITU' : 'CQ';

// Calculate color for cache hit rate
$cache_color = $cache_hit_rate >= 70 ? 'success' : ($cache_hit_rate >= 40 ? 'warning' : 'danger');

// Compact statistics row
echo '<div class="row mb-3 g-2">';

$stats = [
	['label' => __("Callsigns Tested"), 'value' => $calls_tested, 'color' => 'primary'],
	['label' => __("Execution Time"), 'value' => round($execution_time, 2) . 's', 'color' => 'info'],
	['label' => __("Potential Wrong Zones"), 'value' => count($result), 'color' => 'warning'],
	['label' => __("Cache Hits"), 'value' => $cache_hits, 'color' => 'success'],
	['label' => __("Cache Misses"), 'value' => $cache_misses, 'color' => 'info'],
	['label' => __("Hit Rate"), 'value' => $cache_hit_rate . '%', 'color' => $cache_color],
];

foreach ($stats as $stat) {
	echo '<div class="col-6 col-md-2">
		<div class="card border-' . $stat['color'] . ' text-center py-2">
			<div class="h5 mb-0 text-' . $stat['color'] . '">' . $stat['value'] . '</div>
			<small class="text-muted">' . $stat['label'] . '</small>
		</div>
	</div>';
}

echo '</div>';

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
				<th><?= __("Time"); ?></th>
				<th><?= __("Station Profile"); ?></th>
				<th><?= __("Gridsquare"); ?></th>
				<th><?= __("Band"); ?></th>
				<?php if ($is_itu): ?>
					<th><?= __("ITUz"); ?></th>
					<th><?= __("ITUz geojson"); ?></th>
				<?php else: ?>
					<th><?= __("CQz"); ?></th>
					<th><?= __("CQz geojson"); ?></th>
				<?php endif; ?>
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
								<a href="#" class="callsign-search" data-call="<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" data-bs-toggle="tooltip" title="<?= __("Show all QSOs with this callsign"); ?>"><i class="fas fa-search"></i></a>
							</span>
						</div>
					</td>
					<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
					<td><?php echo date('H:i', strtotime($qso['qso_date'])); ?></td>
					<td><?php echo $qso['station_profile']; ?></td>
					<td>
						<div class="d-flex align-items-center justify-content-between">
							<span><?php echo $qso['gridsquare']; ?></span>
							<a href="#" class="zone-map" data-grid="<?php echo htmlspecialchars($qso['gridsquare'], ENT_QUOTES); ?>" data-zonetype="<?php echo $is_itu ? 'itu' : 'cq'; ?>" data-zone="<?php echo $is_itu ? (int)$qso['itugeo'] : (int)$qso['cqgeo']; ?>" data-bs-toggle="tooltip" title="<?= __("Show on map"); ?>"><i class="fas fa-map-marked-alt"></i></a>
						</div>
					</td>
					<td><?php echo $qso['band'] ?? ''; ?></td>
					<?php if ($is_itu): ?>
						<td><?php echo $qso['ituzone']; ?></td>
						<td><?php echo $qso['itugeo']; ?></td>
					<?php else: ?>
						<td><?php echo $qso['cqzone']; ?></td>
						<td><?php echo $qso['cqgeo']; ?></td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php } else { ?>
<div class="alert alert-success mb-0">
	<?= __("No zone issues found. All QSOs have correct zone information."); ?>
</div>
<?php } ?>
