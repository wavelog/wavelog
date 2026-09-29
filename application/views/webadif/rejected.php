<?php if (!empty($rejected)) { ?>
<div class="card mt-3 border-danger" id="webadif-rejected-<?php echo $station->station_id; ?>">
	<div class="card-header bg-danger text-white"><?= __("Rejected by QO-100 Dx Club"); ?>: <?php echo htmlspecialchars((string)$station->station_callsign); ?></div>
	<div class="card-body">
		<p><?= __("These QSOs were rejected by the QO-100 Dx Club API and the upload of those is not retried automatically."); ?></p>
		<table class="table table-bordered table-hover table-striped table-condensed">
			<thead>
			<tr>
				<td><?= __("Call"); ?></td>
				<td><?= __("Time"); ?></td>
				<td><?= __("Reason for rejection by QO-100 Dx Club"); ?></td>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($rejected as $rejected_qso) { ?>
				<tr>
					<td><span class="callsign"><?php echo htmlspecialchars((string)$rejected_qso->COL_CALL); ?></span></td>
					<td><?php echo htmlspecialchars((string)$rejected_qso->COL_TIME_ON); ?></td>
					<td><?php echo htmlspecialchars((string)$rejected_qso->message); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<button type="button" class="btn btn-warning btn-sm ld-ext-right ld-ext-right-rejected-<?php echo $station->station_id; ?>" onclick="ResetWebADIFRejected(<?php echo $station->station_id; ?>)"><i class="fas fa-redo"></i> <?= __("Retry Upload"); ?><div class="ld ld-ring ld-spin"></div></button>
	</div>
</div>
<?php } ?>
