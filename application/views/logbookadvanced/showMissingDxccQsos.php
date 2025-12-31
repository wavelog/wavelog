<?php
if($this->session->userdata('user_date_format')) {
	// If Logged in and session exists
	$custom_date_format = $this->session->userdata('user_date_format');
} else {
	// Get Default date format from /config/wavelog.php
	$custom_date_format = $this->config->item('qso_date_format');
}
?>
<div class="container-fluid">
    <?php if (!empty($qsos) && count($qsos) > 0): ?>
		<div class="table-responsive" style="max-height:50vh; overflow:auto;">
		<p class="text-muted">
			<?php echo sprintf(__("Found %s QSO(s) missing DXCC information."), count($qsos));?>
		</p>
            <table class="table table-sm table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= __("Call") ?></th>
                        <th><?= __("Date/Time") ?></th>
                        <th><?= __("Mode") ?></th>
                        <th><?= __("Band") ?></th>
                        <th><?= __("State") ?></th>
                        <th><?= __("Gridsquare") ?></th>
                        <th><?= __("DXCC") ?></th>
                        <th><?= __("Station Location") ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qsos as $qso): ?>
                        <tr>
                            <td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso->col_primary_key . ')">' . htmlspecialchars($qso->col_call) . '</a>'; ?></td>
							<td><?php echo date($custom_date_format . ' H:i', strtotime($qso->col_time_on)); ?></td>
                            <td><?php echo $qso->col_mode; ?></td>
                            <td><?php echo $qso->col_band; ?></td>
							<td><?php echo $qso->col_state; ?></td>
                            <td><?php echo $qso->col_gridsquare; ?></td>
							<td><?php echo $qso->dxcc_name; ?></td>
							<td><?php echo $qso->station_profile_name; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
	</div>
    <?php else: ?>
        <div class="alert alert-success">
            <h4><?= __("No Issues Found") ?></h4>
        </div>
    <?php endif; ?>
</div>
