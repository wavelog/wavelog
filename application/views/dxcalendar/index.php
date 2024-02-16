<div class="container">
	<br>
	<h2><?php echo $page_title; ?></h2>
	<p>This data is from <a target="_blank" href="https://ng3k.com/">https://ng3k.com/</a></p>

		<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed dxcalendar">
			<thead>
				<tr>
					<th>Date from</th>
					<th>Date to</th>
					<th>DXCC</th>
					<th>Call</th>
					<th>QSL info</th>
					<th>Source</th>
					<th>Info</th>
				</tr>
			</thead>
			<tbody>
			<?php
		foreach($rss->channel->item as $item) {
			echo '<tr>';
			$title = explode('--', $item->title);
			$tempinfo = explode(':', $title[0]);
			$dxcc = $tempinfo[0];
			$date = $tempinfo[1];

			$dates = extractDates($date);

			$description = $item->description;

			$descsplit = explode("\n", $description);

			$call = (string) $descsplit[3];
			$call = str_replace('--', '', $call);
			$qslinfo = (string) $descsplit[4];
			$qslinfo = str_replace('--', '', $qslinfo);
			$qslinfo = str_replace('QSL: ', '', $qslinfo);
			$source = (string) $descsplit[5];
			$source = str_replace('--', '', $source);
			$source = str_replace('Source: ', '', $source);
			$info = (string) $descsplit[6];
			$link = (string) $item->link;

			echo "<td>" . $dates[0] ?? '' . "</td>";
			echo "<td>" . $dates[1] ?? '' . "</td>";
			echo "<td>$dxcc</td>";
			echo "<td>$call</td>";
			echo "<td>$qslinfo</td>";
			echo "<td>$source</td>";
			echo "<td>$info</td>";

			echo '</tr>';
		}
		?>
		</tbody>
	</table>

</div>
<?php
// Define a function to extract the dates from the date range
function extractDates($dateRange) {
    // Split the date range into two parts: month-day and year
    $dateParts = explode(",", $dateRange);
    if (count($dateParts) != 2) {
        return false; // Invalid date range format
    }

	$monthDayPart = explode("-", trim($dateParts[0]));
	$yearPart = trim($dateParts[1]);

	// Extract the year from the year part
	$year = substr($yearPart, -4);

	$startDate = $monthDayPart[0] . ", " . $year;

	if (strlen($monthDayPart[1]) < 3) {
		$tempdate = explode(" ", $monthDayPart[0]);
		$endDate = $tempdate[0] . " " . $monthDayPart[1] . ", " . $year;
	} else {
		$endDate = $monthDayPart[1] . ", " . $year;
	}

    // Parse the start date
    $startDateTime = date_create_from_format("M j, Y", $startDate);

    // Parse the end date
    $endDateTime = date_create_from_format("M j, Y", $endDate);

    // Check if parsing was successful
    if ($startDateTime !== false && $endDateTime !== false) {
        return array($startDateTime->format("Y-m-d"), $endDateTime->format("Y-m-d"));
    } else {
        return false; // Failed to parse dates
    }
}
