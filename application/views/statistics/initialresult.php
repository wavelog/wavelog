<?php
if ($intials_array) {
    // Get Date format
    if($this->session->userdata('user_date_format')) {
        // If Logged in and session exists
        $custom_date_format = $this->session->userdata('user_date_format');
    } else {
        // Get Default date format from /config/wavelog.php
        $custom_date_format = $this->config->item('qso_date_format');
    }
    echo '<table style="width:100%" class="table table-sm intialstable table-bordered table-hover table-striped table-condensed text-center">
			<thead>
                    <tr>
                        <th>#</th>
                        <th>' . __("Date") . '</th>
                        <th>' . __("Time") . '</th>
                        <th>' . __("Callsign") . '</th>
						<th>' . __("Band") . '</th>
						<th>' . __("Mode") . '</th>
						<th>' . __("Gridsquare") . '</th>
						<th>' . __("Distance") . '</th>
                    </tr>
                </thead>
                <tbody>';
		$i = 1;
	foreach ($intials_array as $line) {
        $date_as_timestamp = strtotime($line->col_time_on ?? '1970-01-01 00:00:00');
        echo '<tr>
                <td>' . $i++ . '</td>
                <td>' . date($custom_date_format, $date_as_timestamp) . '</td>
                <td>' . date('H:i', $date_as_timestamp) . '</td>
                <td><a href=javascript:displayQso(' . $line->col_primary_key . ')>' . $line->col_call . '</a></td>
                <td>' . $line->col_band . '</td>
                <td>' . (empty($line->col_submode) ? ($line->col_mode ?? '') : $line->col_submode) . '</td>
				<td>' . (empty($line->col_vucc_grids) ? ($line->col_gridsquare ?? '') : $line->col_vucc_grids) . '</td>
                <td>' . $line->col_distance . ' km</td>
		</tr>';
	}

		echo '</tbody></table>';
} else {
	echo __("No EME QSOs were found.");
}
