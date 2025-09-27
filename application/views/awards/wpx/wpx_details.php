<h3><?php echo sprintf("%s – %s", ucfirst($status), $band); ?></h3>

<?php

if ($this->session->userdata('user_date_format')) {
	// If Logged in and session exists
	$custom_date_format = $this->session->userdata('user_date_format');
} else {
	// Get Default date format from /config/wavelog.php
	$custom_date_format = $this->config->item('qso_date_format');
}

	$i = 1;
	if (!empty($results)): ?>
    <table class="table wpxdetails table-sm table-bordered table-striped text-center">
        <thead>
            <tr>
				<th>#</th>
				<th><?= __("WPX"); ?></th>
				<th><?= __("Callsign"); ?></th>
				<th><?= __("QSO Date"); ?></th>
				<th><?= __("Band"); ?></th>
				<th><?= __("Mode"); ?></th>
				<td><?= __("Confirmed"); ?></td>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($results as $qso):
					$qsotimestamp = strtotime($qso->col_time_on);
			?>
            <tr>
				<td><?php echo $i++; ?></td>
                <td><?php echo $qso->wpx_prefix; ?></td>
				<td><?php echo $qso->col_call; ?></td>
				<td><?php echo date($custom_date_format, $qsotimestamp) . ' ' . date('H:i', $qsotimestamp); ?></td>
				<td><?php echo $qso->col_band; ?></td>
				<td><?php echo $qso->col_submode ?? $qso->col_mode; ?></td>
				<td><?php echo cf_type($qso->col_qsl_rcvd, $qso->col_lotw_qsl_rcvd, $qso->col_eqsl_qsl_rcvd, $qso->COL_QRZCOM_QSO_DOWNLOAD_STATUS, $qso->COL_CLUBLOG_QSO_DOWNLOAD_STATUS); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div class="alert alert-warning">No QSOs found for <?php echo $band; ?>.</div>
<?php endif;


function cf_type($qsl, $lotw, $eqsl, $qrz, $clublog) {
	$string = '';
	if ((($qsl ?? 'N') == 'Y')) { $string.= 'Q'; }
	if ((($lotw ?? 'N') == 'Y')) { $string.= 'L'; }
	if ((($eqsl ?? 'N') == 'Y')) { $string.= 'E'; }
	if ((($qrz ?? 'N') == 'Y')) { $string.= 'Z'; }
	if ((($clublog ?? 'N') == 'Y')) { $string.= 'C'; }
	if ($string == '') { return '<div class="bg-danger awardsBgDanger">-</div>'; }

	return '<div class="bg-success awardsBgSuccess">' . $string . '</div>';
}

?>
