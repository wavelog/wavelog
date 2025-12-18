<?php $execution_time ?>
<?php $calls_tested ?>
<?php $i = 0; ?>
<div class="table-responsive" style="max-height:70vh; overflow:auto;">
		<table class="table table-sm table-striped table-bordered table-condensed mb-0">
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
				<?php foreach ($result as $qso): ?>
					<tr>
						<td><?php echo ++$i; ?></td>
						<td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso['id'] . ')">' . htmlspecialchars($qso['callsign']) . '</a>'; ?></td>
						<td><?php echo date('Y-m-d H:i', strtotime($qso['qso_date'])); ?></td>
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