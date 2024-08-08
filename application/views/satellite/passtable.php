<?php
if ($filtered) {
	echo '<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
				<tr id="toptable">
					<th>Satellite</th>
					<th>AOS Time</th>
					<th>Duration</th>
					<th>AOS az</th>
					<th>AOS el</th>
					<th>Max El</th>
					<th>LOS Time</th>
					<th>LOS Az</th>
					<th>LOS El</th>
				</tr>';
			foreach ($filtered as $pass) {
				echo '<tr>';
				echo '<td>' . $pass->satname . '</td>';
				echo '<td>' . Predict_Time::daynum2readable($pass->visible_aos, $zone, $format) . '</td>';
				echo '<td>' . returntimediff(Predict_Time::daynum2readable($pass->visible_aos, $zone, $format), Predict_Time::daynum2readable($pass->visible_los, $zone, $format)) . '</td>';
				echo '<td>' . round($pass->visible_aos_az) . ' (' . azDegreesToDirection($pass->visible_aos_az) . ')</td>';
				echo '<td>' . round($pass->visible_aos_el) . '</td>';
				echo '<td>' . round($pass->max_el) . '</td>';
				echo '<td>' . Predict_Time::daynum2readable($pass->visible_los, $zone, $format) . '</td>';
				echo '<td>' . round($pass->visible_los_az) . ' (' . azDegreesToDirection($pass->visible_los_az) . ')</td>';
				echo '<td>' . round($pass->visible_los_el) . '</td>';
				echo '</tr>';
			}
			echo '</table>';
}

function returntimediff($start, $end) {
	$datetime1 = DateTime::createFromFormat('m-d-Y H:i:s', $end);
	$datetime2 = DateTime::createFromFormat('m-d-Y H:i:s', $start);
	$interval = $datetime1->diff($datetime2);

	$minutesDifference = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i + ($interval->s / 60);

	return round($minutesDifference) . ' min';
}

function azDegreesToDirection($az = 0) {
	$i = floor($az / 22.5);
	$m = (22.5 * (2 * $i + 1)) / 2;
	$i = ($az >= $m) ? $i + 1 : $i;

	return trim(substr('N  NNENE ENEE  ESESE SSES  SSWSW WSWW  WNWNW NNWN  ', $i * 3, 3));
}
