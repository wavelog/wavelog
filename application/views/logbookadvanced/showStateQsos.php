<div class="container-fluid">
    <?php if (!empty($qsos) && count($qsos) > 0): ?>
		<div class="table-responsive" style="max-height:50vh; overflow:auto;">
		<p class="text-muted">
                Found <?php echo count($qsos); ?> QSO(s) missing state information for DXCC <?php echo $dxcc; ?>.
		</p>
            <table class="table table-sm table-striped table-hover">
                <thead>
                    <tr>
                        <th>Call</th>
                        <th>Date/Time</th>
                        <th>Mode</th>
                        <th>Band</th>
                        <th>State</th>
                        <th>Gridsquare</th>
                        <th>DXCC</th>
                        <th>Station</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qsos as $qso): ?>
                        <tr>
                            <td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso->col_primary_key . ')">' . htmlspecialchars($qso->col_call) . '</a>'; ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($qso->col_time_on)); ?></td>
                            <td><?php echo $qso->col_mode; ?></td>
                            <td><?php echo $qso->col_band; ?></td>
							<td><?php echo $qso->col_state; ?></td>
                            <td><?php echo $qso->col_gridsquare; ?></td>
							<td><?php echo htmlspecialchars(ucwords(strtolower($qso->dxcc_name), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php echo $qso->station_profile_name; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
	</div>
    <?php else: ?>
        <div class="alert alert-success">
            <h4>No Issues Found</h4>
        </div>
    <?php endif; ?>
</div>
