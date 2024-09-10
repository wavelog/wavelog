<?php
if ($qsos->result() != NULL) {
	echo __("The following QSOs were found to have an incorrect ITU zone that this DXCC normally has (a maximum of 1000 QSOs are shown):");
	echo '<table style="width:100%" class="qsolist table table-sm table-bordered table-hover table-striped table-condensed">
	<thead>
	<tr>
	<th style=\'text-align: center\'>' . __("Date") . '</th>
	<th style=\'text-align: center\'>' . __("Time") . '</th>
	<th style=\'text-align: center\'>' . __("Callsign") . '</th>
	<th style=\'text-align: center\'>' . __("Mode") . '</th>
	<th style=\'text-align: center\'>' . __("Band") . '</th>
	<th style=\'text-align: center\'>' . __("Gridsquare") . '</th>
	<th style=\'text-align: center\'>' . __("ITU Zone") . '</th>
	<th style=\'text-align: center\'>' . __("DXCC ITU Zone") . '</th>
	<th style=\'text-align: center\'>' . __("DXCC") . '</th>
	<th style=\'text-align: center\'>' . __("Station") . '</th>
	</tr>
	</thead><tbody>';

	// Get Date format
	if($this->session->userdata('user_date_format')) {
		// If Logged in and session exists
		$custom_date_format = $this->session->userdata('user_date_format');
	} else {
		// Get Default date format from /config/wavelog.php
		$custom_date_format = $this->config->item('qso_date_format');
	}

	$i = 0;

	foreach ($qsos->result() as $qso) {
		echo '<tr>';
		echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qso->COL_TIME_ON); echo date($custom_date_format, $timestamp); echo '</td>';
		echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qso->COL_TIME_ON); echo date('H:i', $timestamp); echo '</td>';
		echo '<td style=\'text-align: center\'><a id="edit_qso" href="javascript:displayQso(' . $qso->COL_PRIMARY_KEY . ')">' . str_replace("0","&Oslash;",strtoupper($qso->COL_CALL)) . '</a></td>';
		echo '<td style=\'text-align: center\'>'; echo $qso->COL_SUBMODE==null?$qso->COL_MODE:$qso->COL_SUBMODE; echo '</td>';
		echo '<td style=\'text-align: center\'>'; if($qso->COL_SAT_NAME != null) { echo $qso->COL_SAT_NAME; } else { echo strtolower($qso->COL_BAND); }; echo '</td>';
		echo '<td style=\'text-align: center\'>'; echo strlen($qso->COL_GRIDSQUARE ?? '')==0?$qso->COL_VUCC_GRIDS:$qso->COL_GRIDSQUARE; echo '</td>';
		echo '<td style=\'text-align: center\'>' . $qso->COL_ITUZ . '</td>';
		echo '<td style=\'text-align: center\'>' . $qso->correctituzone . '</td>';
		echo '<td style=\'text-align: center\'>' . ucwords(strtolower($qso->COL_COUNTRY), "- (/") . '</td>';
		echo '<td style=\'text-align: center\'><span class="badge text-bg-light">' . $qso->station_callsign . '</span></td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	?>

	<?php
} else {
	echo '<div class="alert alert-success">' . __("No incorrect CQ Zones were found.") . '</div>';
}
?>
