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
				$max_el_az = round($pass->visible_max_el_az);
				$scale = 95;
				$aos=sat2pol($aos_az,0,$scale);
				$los=sat2pol($los_az,0,$scale);
				$tca=sat2pol($max_el_az,$max_el,$scale);
				echo '<tr>';
				echo '<td>' . $pass->satname . '</td>';
				echo '<td>' . Predict_Time::daynum2readable($pass->visible_aos, $zone, $format) . '<span style="margin-left: 10px; display: inline-block;"><a href="' . $ics.'" target="newics"><i class="fas fa-calendar-plus"></i></a><span></td>';
				echo '<td>' . Predict_Time::daynum2readable($pass->visible_los, $zone, $format) . '</td>';
				echo '<td>' . returntimediff(Predict_Time::daynum2readable($pass->visible_aos, $zone, $format), Predict_Time::daynum2readable($pass->visible_los, $zone, $format), $format) . '</td>';
				echo '<td><a href="flightpath/'.$pass->satname.'"><?xml version="1.0" encoding="UTF-8" standalone="no"?>
					<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" baseProfile="full" width="'.($scale*2).'" height="'.($scale*2).'">
					<circle cx="'.$scale.'" cy="'.$scale.'" r="'.($scale / 10 * 9).'" stroke="darkgrey" stroke-width="1" fill="none" />
					<circle cx="'.$scale.'" cy="'.$scale.'" r="'.($scale / 10 * 6).'" stroke="darkgrey" stroke-width="1" fill="none" />
					<circle cx="'.$scale.'" cy="'.$scale.'" r="'.($scale / 10 * 3).'" stroke="darkgrey" stroke-width="1" fill="none" />
					<line x1="0" y1="'.$scale.'" x2="'.($scale*2).'" y2="'.$scale.'" stroke="darkgrey" stroke-width="1" />
					<line x1="'.$scale.'" y1="0" x2="'.$scale.'" y2="'.($scale*2).'" stroke="darkgrey" stroke-width="1" />
					<circle cx="'.$aos[0].'" cy="'.$aos[1].'" r="1" stroke="green" stroke-width="5" fill="none" />
					<circle cx="'.$los[0].'" cy="'.$los[1].'" r="1" stroke="red" stroke-width="5" fill="none" />
					<circle cx="'.$tca[0].'" cy="'.$tca[1].'" r="1" stroke="blue" stroke-width="5" fill="none" />
					<path d="M '.$aos[0].' '.$aos[1].' Q '.$tca[0].' '.$tca[1].' '.$tca[0].' '.$tca[1].' Q '.$los[0].' '.$los[1].' '.$los[0].' '.$los[1].'" fill="none" stroke="blue" stroke-width="2" />
					<!-- <path d="M '.$aos[0].' '.$aos[1].' Q '.$tca[0].' '.$tca[1].' '.$los[0].' '.$los[1].'" fill="none" stroke="blue" stroke-width="2" /> -->
					</svg></a></td>';
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

function sat2pol($azimuth_deg, $elevation_deg,$scale) {
	$azimuth_rad = deg2rad(270+$azimuth_deg);
	$r = (90 - $elevation_deg)/90;
	$x = ($r * cos($azimuth_rad)*($scale / 10 * 9))+$scale;
	$y = ($r * sin($azimuth_rad)*($scale / 10 * 9))+$scale;
	return array($x, $y);
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
