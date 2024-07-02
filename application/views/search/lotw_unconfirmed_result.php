<?php
if ($qsos->result() != NULL) {
	echo '<table style="width:100%" class="table qsolist table-sm table-bordered table-hover table-striped table-condensed">
	<thead>
	<tr>
		<th style=\'text-align: center\'>' . __("Callsign") . '</th>
		<th style=\'text-align: center\'>' . __("Date/Time") . '</th>
		<th style=\'text-align: center\'>' . __("Mode") . '</th>
		<th style=\'text-align: center\'>' . __("Band") . '</th>
		<th style=\'text-align: center\'>' . __("Last LoTW upload") . '</th>
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
		echo '<td style=\'text-align: center\'><form id="searchcall_'.$i.'" method="POST" action="'.site_url('search'). '"><input type="hidden" value="'. strtoupper($qso->COL_CALL) .'" name="callsign"><a href="javascript:$(\'#searchcall_'.$i++.'\').submit()">' . $qso->COL_CALL . '</a></form></td>';
		echo '<td style=\'text-align: center\'>' . $qso->COL_TIME_ON . '</td>';
		echo '<td style=\'text-align: center\'>'; echo $qso->COL_SUBMODE==null?$qso->COL_MODE:$qso->COL_SUBMODE; echo '</td>';
		echo '<td style=\'text-align: center\'>'; if($qso->COL_SAT_NAME != null) { echo $qso->COL_SAT_NAME; } else { echo strtolower($qso->COL_BAND); }; echo '</td>';
		echo '<td style=\'text-align: center\'>' . $qso->lastupload . '</td>';
		echo '<td style=\'text-align: center\'><span class="badge text-bg-light">' . $qso->station_callsign . '</span></td>';
		echo '</tr>';
	}

	echo '</tbody></table>';
	?>

	<?php
} else {
	echo '<div class="alert alert-success">' . __("No QSOs with outstanding LoTW upload were found.") . '</div>';
}
?>
