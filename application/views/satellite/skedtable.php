<?php
if (!empty($overlaps)) {
	echo '<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">';
	echo '<thead>
	<tr>
	<th>' . __("Grid") . '</th>
	<th>' . __("Satellite") . '</th>
	<th>' . __("AOS Time") . '</th>
	<th>' . __("Duration") . '</th>
	<th>' . __("AOS Azimuth") . '</th>
	<th>' . __("Max Elevation") . '</th>
	<th>' . __("LOS Time") . '</th>
	<th>' . __("LOS Azimuth") . '</th>
	</tr>
	</thead>
	<tbody>';

	foreach ($overlaps as $overlap) {
		echo '<tr>';
		echo '<td>' . strtoupper($yourgrid) . '</td>';
		echo '<td>' . $overlap['grid1']->satname . '</td>';
		echo '<td>' . Predict_Time::daynum2readable($overlap['grid1']->visible_aos, $zone, $format) . '</td>';
		echo '<td>' . returntimediff(Predict_Time::daynum2readable($overlap['grid1']->visible_aos, $zone, $format), Predict_Time::daynum2readable($overlap['grid1']->visible_los, $zone, $format), $format) . '</td>';
		$aos_az = round($overlap['grid1']->visible_aos_az);
		echo '<td>' . $aos_az . ' ° (' . azDegreesToDirection($overlap['grid1']->visible_aos_az) . ')<span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$aos_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>';
		$max_el = round($overlap['grid1']->max_el);
		echo '<td>' . $max_el . ' °<span style="margin-left: 10px; display: inline-block; transform: rotate(-'.$max_el.'deg);"><i class="fas fa-arrow-right fa-xs"></i></span></td>';
		echo '<td>' . Predict_Time::daynum2readable($overlap['grid1']->visible_los, $zone, $format) . '</td>';
		$los_az = round($overlap['grid1']->visible_los_az);
		echo '<td>' . $los_az . ' ° (' . azDegreesToDirection($overlap['grid1']->visible_los_az) . ')<span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$los_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>' . strtoupper($skedgrid) . '</td>';
		echo '<td>' . $overlap['grid2']->satname . '</td>';
		echo '<td>' . Predict_Time::daynum2readable($overlap['grid2']->visible_aos, $zone, $format) . '</td>';
		echo '<td>' . returntimediff(Predict_Time::daynum2readable($overlap['grid2']->visible_aos, $zone, $format), Predict_Time::daynum2readable($overlap['grid2']->visible_los, $zone, $format), $format) . '</td>';
		$aos_az = round($overlap['grid2']->visible_aos_az);
		echo '<td>' . $aos_az . ' ° (' . azDegreesToDirection($overlap['grid2']->visible_aos_az) . ')<span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$aos_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>';
		$max_el = round($overlap['grid2']->max_el);
		echo '<td>' . $max_el . ' °<span style="margin-left: 10px; display: inline-block; transform: rotate(-'.$max_el.'deg);"><i class="fas fa-arrow-right fa-xs"></i></span></td>';
		echo '<td>' . Predict_Time::daynum2readable($overlap['grid2']->visible_los, $zone, $format) . '</td>';
		$los_az = round($overlap['grid2']->visible_los_az);
		echo '<td>' . $los_az . ' ° (' . azDegreesToDirection($overlap['grid2']->visible_los_az) . ')<span style="margin-left: 10px; display: inline-block; transform: rotate('.(-45+$los_az).'deg);"><i class="fas fa-location-arrow fa-xs"></i></span></td>';
		echo '</tr>';

		echo "<tr><td colspan='8'>---</td></tr>"; // Separator row
	}

	echo "</tbody>";
	echo "</table>";

	echo '<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">';
	echo '<thead>
	<tr>
	<th>' . __("Satellite") . '</th>
	<th>' . __("Date") . '</th>
	<th>' . __("Sked AOS Time") . '</th>
	<th>' . __("Sked LOS Time") . '</th>
	<th>' . __("Duration") . '</th>
	</tr>
	</thead>
	<tbody>';

	foreach ($overlaps as $overlap) {
		$satellite = $overlap['grid1']->satname;
		$skedDate = Predict_Time::daynum2readable($overlap['grid1']->visible_aos, $zone, $format);

		$skedAOS = $overlap['grid1']->visible_aos < $overlap['grid2']->visible_aos ? $overlap['grid2']->visible_aos : $overlap['grid1']->visible_aos;
		$skedLOS = $overlap['grid1']->visible_los < $overlap['grid2']->visible_los ? $overlap['grid1']->visible_los : $overlap['grid2']->visible_los;
		$timestamp = strtotime($date);

		echo '<tr>';
		echo "<td>". $satellite . "</td>";
		echo "<td>" . date($custom_date_format, $timestamp) . "</td>";
		echo "<td>" . Predict_Time::daynum2readable($skedAOS, $zone, $format) . "</td>";
		echo "<td>" . Predict_Time::daynum2readable($skedLOS, $zone, $format) . "</td>";
		echo "<td>" . returntimediff(Predict_Time::daynum2readable($skedAOS, $zone, $format), Predict_Time::daynum2readable($skedLOS, $zone, $format), $format) . "</td>";
		echo "</div>";
	}
} else {
	echo '<div style="text-align: center !important">';
	echo '<h2>'.__('Search failed!').'</h2>';
	echo '<p>'.__('No overlapping passes found. Please check the input parameters.').'</p>';
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
