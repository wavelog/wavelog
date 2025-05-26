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
    echo '<table style="width:100%" class="table table-sm timelinetable table-bordered table-hover table-striped table-condensed text-center">
			<thead>
                    <tr>
                        <td>#</td>
                        <td>' . __("Date") . '</td>
                        <td>' . __("Callsign") . '</td>
						<td>' . __("Band") . '</td>
						<td>' . __("Mode") . '</td>
                        <td>' . __("Show QSOs") . '</td>
                    </tr>
                </thead>
                <tbody>';
		$i = 1;
	foreach ($intials_array as $line) {
        $date_as_timestamp = strtotime($line->firstworked ?? '1970-01-01 00:00:00');
        echo '<tr>
                <td>' . $i++ . '</td>
                <td>' . date($custom_date_format, $date_as_timestamp) . '</td>
                <td>' . $line->col_call . '</td>
                <td>' . $line->col_band . '</td>
                <td></td>
                <td><a href=javascript:displayContacts()>' . __("Show") . '</a></td>
		</tr>';
	}

		echo '</tbody></table>';
} else {
	echo __("No EME QSO(s) was found.");
}
