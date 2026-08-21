<?php
$i = 0;

// Resolve the home callsign from a (possibly portable) callsign: the longest
// "/"-separated segment is the base call. Used for the QRZ link so the URL has
// no "/" to break path-based routing. (Mirrors calltester/result.php.)
if ( ! function_exists('calltester_base_call')) {
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

$show_time = isset($show_time) ? $show_time : true;

// Header summary cards (only when we have at least one matching QSO)
if ($count > 0) {
	$dxcc_value = $info['dxcc_name'];
	if ($info['dxcc_adif'] !== null && $info['dxcc_adif'] !== '') {
		$dxcc_value .= ' (' . htmlspecialchars($info['dxcc_adif'], ENT_QUOTES) . ')';
	}
	$stats = [
		['label' => __("Callsign"),  'value' => '<span class="callsign">' . htmlspecialchars($call, ENT_QUOTES) . '</span>', 'color' => 'primary'],
		['label' => __("DXCC"),      'value' => $dxcc_value !== '' ? $dxcc_value : '—',                       'color' => 'info'],
		['label' => __("QSOs"),      'value' => $count,                                                        'color' => 'success'],
	];

	echo '<div class="row mb-3 g-2">';
	foreach ($stats as $stat) {
		echo '<div class="col-6 col-md">
			<div class="card border-' . $stat['color'] . ' text-center py-2 h-100">
				<div class="h6 mb-0 text-' . $stat['color'] . '">' . $stat['value'] . '</div>
				<small class="text-muted">' . $stat['label'] . '</small>
			</div>
		</div>';
	}
	echo '</div>';
}

if ($count == 0) { ?>
<div class="alert alert-info mb-0">
	<?= sprintf(__("No QSOs with %s found in your logbook."), '<span class="callsign">' . htmlspecialchars($call, ENT_QUOTES) . '</span>'); ?>
</div>
<?php } else { ?>
<div class="table-responsive" style="max-height:60vh; overflow:auto;">
	<table class="table table-sm table-striped table-bordered table-condensed mb-0">
		<thead>
			<tr>
				<th>#</th>
				<th><?= __("Date"); ?></th>
				<th><?= __("Time"); ?></th>
				<th><?= __("Callsign"); ?></th>
				<th><?= __("Band"); ?></th>
				<th><?= __("Mode"); ?></th>
				<th><?= __("Grid"); ?></th>
				<th><?= __("DXCC"); ?></th>
				<th><?= __("CQz"); ?></th>
				<th><?= __("ITUz"); ?></th>
				<th><?= __("RST S"); ?></th>
				<th><?= __("RST R"); ?></th>
				<th><?= __("QSL"); ?></th>
				<th><?= __("Location"); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($results->result() as $row): ?>
				<?php
					$grid = !empty($row->COL_GRIDSQUARE) ? $row->COL_GRIDSQUARE : ($row->COL_VUCC_GRIDS ?? '');
					$dxcc = !empty($row->COL_COUNTRY) ? $row->COL_COUNTRY : ($row->COL_DXCC ?? '');
					$base_call = calltester_base_call($row->COL_CALL);

					// Compact QSL markers
					$qsl = '';
					if (($row->COL_QSL_RCVD ?? '') == 'Y') { $qsl .= '<span class="badge text-bg-success" data-bs-toggle="tooltip" title="' . __("QSL card") . '">Q</span> '; }
					if (($row->COL_LOTW_QSL_RCVD ?? '') == 'Y') { $qsl .= '<span class="badge text-bg-success" data-bs-toggle="tooltip" title="' . __("LoTW") . '">L</span> '; }
					if (($row->COL_EQSL_QSL_RCVD ?? '') == 'Y') { $qsl .= '<span class="badge text-bg-success" data-bs-toggle="tooltip" title="' . __("eQSL") . '">E</span> '; }
					if ($qsl === '') { $qsl = '<span class="text-muted">—</span>'; }
				?>
				<tr>
					<td><?php echo ++$i; ?></td>
					<td><?php echo date($custom_date_format, strtotime($row->COL_TIME_ON)); ?></td>
					<?php if ($show_time) { ?><td><?php echo date('H:i', strtotime($row->COL_TIME_ON)); ?></td><?php } ?>
					<td>
						<div class="d-flex align-items-center justify-content-between">
							<a class="callsign" href="javascript:void(0)" onclick="displayQso(<?php echo $row->COL_PRIMARY_KEY; ?>)"><?php echo htmlspecialchars(strtoupper($row->COL_CALL)); ?></a>
							<a href="https://www.qrz.com/db/<?php echo htmlspecialchars($base_call, ENT_QUOTES); ?>" target="_blank" rel="noopener" data-bs-toggle="tooltip" title="<?= __("Lookup on QRZ.com"); ?>"><img style="vertical-align: baseline" width="16" height="16" src="<?php echo base_url(); ?>images/icons/qrz.png" alt="<?= __("Lookup on QRZ.com"); ?>"></a>
						</div>
					</td>
					<td><?php echo ($row->COL_PROP_MODE ?? '') == 'SAT' ? htmlspecialchars($row->COL_SAT_NAME ?? 'SAT') : htmlspecialchars($row->COL_BAND ?? ''); ?></td>
					<td><?php echo htmlspecialchars(($row->COL_SUBMODE ?? '') ?: ($row->COL_MODE ?? '')); ?></td>
					<td><?php echo $grid !== '' ? htmlspecialchars(strtoupper($grid), ENT_QUOTES) : '<span class="text-muted">—</span>'; ?></td>
					<td><?php echo $dxcc !== '' ? htmlspecialchars(ucwords(strtolower($dxcc), "- (/"), ENT_QUOTES, 'UTF-8') : '<span class="text-muted">—</span>'; ?></td>
					<td><?php echo !empty($row->COL_CQZ) ? htmlspecialchars($row->COL_CQZ, ENT_QUOTES) : '<span class="text-muted">—</span>'; ?></td>
					<td><?php echo !empty($row->COL_ITUZ) ? htmlspecialchars($row->COL_ITUZ, ENT_QUOTES) : '<span class="text-muted">—</span>'; ?></td>
					<td><?php echo htmlspecialchars($row->COL_RST_SENT ?? ''); ?></td>
					<td><?php echo htmlspecialchars($row->COL_RST_RCVD ?? ''); ?></td>
					<td><?php echo $qsl; ?></td>
					<td><?php echo $row->station_profile_name; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
<?php } ?>
