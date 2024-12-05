<?php
if ($filtered) {
	echo '<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
				<tr id="toptable">
					<th>' . __("Satellite") . '</th>
					<th>' . __("AOS Time") . '</th>
					<th>' . __("Duration") . '</th>
					<th>' . __("AOS Azimuth") . '</th>
					<th>' . __("Max Elevation") . '</th>
					<th>' . __("LOS Time") . '</th>
					<th>' . __("LOS Azimuth") . '</th>
				</tr>';
			foreach ($filtered as $pass) {
				echo '<tr>';
				echo '<td>' . $pass->satname . '</td>';
				echo '<td>' . Predict_Time::daynum2readable($pass->visible_aos, $zone, $format) . '</td>';
				echo '<td>' . returntimediff(Predict_Time::daynum2readable($pass->visible_aos, $zone, $format), Predict_Time::daynum2readable($pass->visible_los, $zone, $format), $format) . '</td>';
				$aos_az = round($pass->visible_aos_az);
				echo '<td>' . $aos_az . ' ° (' . azDegreesToDirection($pass->visible_aos_az) . ')<span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$aos_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>';
				$max_el = round($pass->max_el);
				echo '<td>' . $max_el . ' °<span style="margin-left: 10px; display: inline-block; transform: rotate(-'.$max_el.'deg);"><i class="fas fa-arrow-right fa-xs"></i></span></td>';
				echo '<td>' . Predict_Time::daynum2readable($pass->visible_los, $zone, $format) . '</td>';
				$los_az = round($pass->visible_los_az);
				echo '<td>' . $los_az . ' ° (' . azDegreesToDirection($pass->visible_los_az) . ')<span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$los_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>';
				echo '</tr>';
			}
			echo '</table>';
}

function returntimediff($start, $end, $format) {
	$datetime1 = DateTime::createFromFormat($format, $end);
	$datetime2 = DateTime::createFromFormat($format, $start);
	$interval = $datetime1->diff($datetime2);

	$diff = sprintf('%02d', (($interval->h*60)+$interval->i)).':'.sprintf('%02d', $interval->s).' '.__("min");

	return $diff;
}

function azDegreesToDirection($az = 0) {
	$i = floor($az / 22.5);
	$m = (22.5 * (2 * $i + 1)) / 2;
	$i = ($az >= $m) ? $i + 1 : $i;

	return trim(substr('N  NNENE ENEE  ESESE SSES  SSWSW WSWW  WNWNW NNWN  ', $i * 3, 3));
}
