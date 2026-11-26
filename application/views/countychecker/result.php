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

if ($result) { ?>
<?php if (!empty($more)) { ?>
<div class="alert alert-warning mb-2"><?= sprintf(__("Only the first %s QSOs with a county issue are shown. Update them and run the check again for the next ones."), count($result)); ?></div>
<?php } ?>
<div class="d-flex align-items-center flex-wrap gap-2 mb-2">
	<button class="btn btn-sm btn-outline-info county-select-missing"><?= __("Select QSOs missing a county"); ?></button>
	<button class="btn btn-sm btn-outline-warning county-select-wrong"><?= __("Select QSOs with wrong county"); ?></button>
	<button id="updateCountiesBtn" class="btn btn-primary btn-sm"><?= __("Update selected QSOs"); ?></button>
	<small class="text-muted"><?= __("Writes the geojson county into the log."); ?></small>
</div>
<div class="table-responsive" style="max-height:70vh; overflow:auto;">
	<table class="table table-sm table-striped table-bordered table-condensed mb-0">
		<thead>
			<tr>
				<th style="width:2em"><input type="checkbox" id="countySelectAll" checked></th>
				<th>#</th>
				<th><?= __("Callsign"); ?></th>
				<th><?= __("QSO Date"); ?></th>
				<th><?= __("Time"); ?></th>
				<th><?= __("Station Profile"); ?></th>
				<th><?= __("Gridsquare"); ?></th>
				<th><?= __("Band"); ?></th>
				<th><?= __("Confirmed"); ?></th>
				<th><?= __("County (log)"); ?></th>
				<th><?= __("County (geojson)"); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($result as $qso): ?>
				<?php $base_call = calltester_base_call($qso['callsign']); ?>
				<tr data-missing="<?php echo $qso['logged_county'] === '' ? 1 : 0; ?>">
					<td class="text-center"><input type="checkbox" class="county-update-check" data-id="<?php echo (int)$qso['id']; ?>" data-state="<?php echo htmlspecialchars($qso['derived_state'], ENT_QUOTES); ?>" data-county="<?php echo htmlspecialchars($qso['derived_county'], ENT_QUOTES); ?>" checked></td>
					<td><?php echo ++$i; ?></td>
					<td>
						<div class="d-flex align-items-center justify-content-between">
							<a id="edit_qso" href="javascript:displayQso(<?php echo $qso['id']; ?>)"><?php echo htmlspecialchars($qso['callsign']); ?></a>
							<span class="d-flex align-items-center gap-2">
								<?php if (!empty($qso['lotw_lastupload'])) {
									$diff = (time() - strtotime($qso['lotw_lastupload'])) / 86400;
									$lotw_hint = $diff > 365 ? ' lotw_info_red' : ($diff > 30 ? ' lotw_info_orange' : ($diff > 7 ? ' lotw_info_yellow' : ''));
								?>
									<a href="https://lotw.arrl.org/lotwuser/act?act=<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" target="_blank" rel="noopener"><small class="badge text-bg-success<?php echo $lotw_hint; ?>" data-bs-toggle="tooltip" title="<?= __("LoTW User"); ?>. <?= __("Last upload"); ?>: <?php echo date($custom_date_format . ' H:i', strtotime($qso['lotw_lastupload'])); ?>">L</small></a>
								<?php } ?>
								<a href="https://www.qrz.com/db/<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="<?= __("Lookup on QRZ.com"); ?>"><img style="vertical-align: baseline" width="16" height="16" src="<?php echo base_url(); ?>images/icons/qrz.png" alt="<?= __("Lookup on QRZ.com"); ?>"></a>
								<a href="#" class="callsign-search" data-call="<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" data-bs-toggle="tooltip" title="<?= __("Show all QSOs with this callsign"); ?>"><i class="fas fa-search"></i></a>
							</span>
						</div>
					</td>
					<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
					<td><?php echo date('H:i', strtotime($qso['qso_date'])); ?></td>
					<td><?php echo htmlspecialchars($qso['station_profile'], ENT_QUOTES); ?></td>
					<td>
						<div class="d-flex align-items-center justify-content-between">
							<span><?php echo htmlspecialchars($qso['gridsquare'], ENT_QUOTES); ?></span>
							<a href="#" class="county-map" data-grid="<?php echo htmlspecialchars($qso['gridsquare'], ENT_QUOTES); ?>" data-loggedstate="<?php echo htmlspecialchars($qso['logged_state'], ENT_QUOTES); ?>" data-loggedcounty="<?php echo htmlspecialchars($qso['logged_county'], ENT_QUOTES); ?>" data-bs-toggle="tooltip" title="<?= __("Show on map"); ?>"><i class="fas fa-map-marked-alt"></i></a>
						</div>
					</td>
					<td><?php echo $qso['band'] ?? ''; ?></td>
					<td class="text-nowrap">
						<?php
						$qsl_confirmed = $qso['qsl_rcvd'] === 'Y';
						$lotw_confirmed = $qso['lotw_rcvd'] === 'Y';
						if (!$qsl_confirmed && !$lotw_confirmed) {
							echo '<span class="text-muted">—</span>';
						} else {
							if ($qsl_confirmed) {
								$qsl_title = __("QSL card") . ': ' . __("Received");
								if ($qso['qslrdate']) { $qsl_title .= ' (' . date($custom_date_format, strtotime($qso['qslrdate'])) . ')'; }
								echo '<small class="badge text-bg-success me-1" data-bs-toggle="tooltip" title="' . $qsl_title . '">' . __("QSL") . '</small>';
							}
							if ($lotw_confirmed) {
								$lotw_title = 'LoTW: ' . __("Received");
								if ($qso['lotwrdate']) { $lotw_title .= ' (' . date($custom_date_format, strtotime($qso['lotwrdate'])) . ')'; }
								echo '<small class="badge text-bg-success" data-bs-toggle="tooltip" title="' . $lotw_title . '">LoTW</small>';
							}
						}
						?>
					</td>
					<td><?php echo $qso['logged_county'] === ''
						? '<span class="text-muted">—</span>'
						: htmlspecialchars($qso['logged_state'] . ($qso['logged_state'] !== '' ? ', ' : '') . $qso['logged_county']); ?></td>
					<td><?php echo htmlspecialchars($qso['derived_state'] . ', ' . $qso['derived_county']); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php } else { ?>
<div class="alert alert-success mb-0">
	<?= __("No county issues found. All QSOs have correct county information."); ?>
</div>
<?php } ?>
