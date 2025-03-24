<?php
if (isset($filtered)) {
	echo '<table style="width:100%" class="satpasstable table-sm table table-bordered table-hover table-striped table-condensed text-center">
			<thead>
				<tr id="toptable">
					<th>' . __("Satellite") . ' <i class="fa-solid fa-satellite"></i></th>
					<th>' . __("AOS Time") . '</th>
					<th>' . __("LOS Time") . '</th>
					<th>' . __("Duration") . '</th>
					<th style="white-space: nowrap">' . __("Path") . '</th>
					<th>' . __("Max Elevation") . '</th>
					<th>' . __("AOS Azimuth") . '</th>
					<th>' . __("LOS Azimuth") . '</th>
				</tr>
			</thead>
			<tbody>';
			foreach ($filtered as $pass) {
				$aos_az = round($pass->visible_aos_az);
				$los_az = round($pass->visible_los_az);
				$aos_ics=Predict_Time::daynum2readable($pass->visible_aos, $zone, 'Y-m-d\TH:i:s\z');
				$los_ics=Predict_Time::daynum2readable($pass->visible_los, $zone, 'Y-m-d\TH:i:s\z');
				$ics='create_ics/'.$pass->satname.'/'.$aos_ics.'/'.$los_ics;
				$max_el = round($pass->max_el);
				echo '<tr>';
				echo '<td>' . $pass->satname . '</td>';
				echo '<td>' . Predict_Time::daynum2readable($pass->visible_aos, $zone, $format) . '<span style="margin-left: 10px; display: inline-block;"><a href="' . $ics.'" target="newics"><i class="fas fa-calendar-plus"></i></a><span></td>';
				echo '<td>' . Predict_Time::daynum2readable($pass->visible_los, $zone, $format) . '</td>';
				echo '<td>' . returntimediff(Predict_Time::daynum2readable($pass->visible_aos, $zone, $format), Predict_Time::daynum2readable($pass->visible_los, $zone, $format), $format) . '</td>';
				echo '<td style="white-space: nowrap;"><a href="flightpath/'.$pass->satname.'"><span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$aos_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span><span style="margin-left: 10px;"><i class="fas fa-mound fa-xs"></i></span><span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$los_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></a></td>';
				echo '<td>' . $max_el . ' °<span style="margin-left: 10px; display: inline-block; transform: rotate(-'.$max_el.'deg);"><i class="fas fa-arrow-right fa-xs"></i></span></td>';
				echo '<td>' . $aos_az . ' ° (' . azDegreesToDirection($pass->visible_aos_az) . ')<span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$aos_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>';
				echo '<td>' . $los_az . ' ° (' . azDegreesToDirection($pass->visible_los_az) . ')<span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$los_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
} else {
	echo '<div style="text-align: center !important">';
	echo '<h2>'.__('Search failed!').'</h2>';
	echo '<p>'.__('No passes found. Please check the input parameters.').'</p>';
	echo '</div>';
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
