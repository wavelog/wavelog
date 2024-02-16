<div class="container">
	<br>
	<h2><?php echo $page_title; ?></h2>
	<p>This data is from <a target="_blank" href="https://ng3k.com/">https://ng3k.com/</a></p>

		<table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed dxcalendar">
			<thead>
				<tr>
					<th>Date</th>
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

			$datesplit = explode('-', $date);

			$from = $datesplit[0];
			$to = $datesplit[1];

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

			echo "<td>$date</td>";
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
function iCalDecoder($file) {
        $ical = file_get_contents($file);
        preg_match_all('/(BEGIN:VEVENT.*?END:VEVENT)/si', $ical, $result, PREG_PATTERN_ORDER);
        for ($i = 0; $i < count($result[0]); $i++) {
            $tmpbyline = explode("\r\n", $result[0][$i]);

            foreach ($tmpbyline as $item) {
                $tmpholderarray = explode(":",$item);
                if (count($tmpholderarray) >1) {
                    $majorarray[$tmpholderarray[0]] = $tmpholderarray[1];
                }
            }

            if (preg_match('/DESCRIPTION:(.*)END:VEVENT/si', $result[0][$i], $regs)) {
                $majorarray['DESCRIPTION'] = str_replace("  ", " ", str_replace("\r\n", "", $regs[1]));
            }
            $icalarray[] = $majorarray;
            unset($majorarray);

        }
        return $icalarray;
}

//read events
// $events = iCalDecoder("http://dxcal.kj4z.com/dxcal");
// $events = iCalDecoder($url = $_SERVER['DOCUMENT_ROOT']."/cloudlog/dxcal.ics");

// //sort events into date order
// usort($events, function($a, $b) {
//     return $a['DTSTART;VALUE=DATE'] - $b['DTSTART;VALUE=DATE'];
// });

// foreach($events as $event){
//     $now = date('Y-m-d H:i:s');//current date and time
//     $eventdate = date('Y-m-d H:i:s', strtotime($event['DTSTART']));//user friendly date

//     if($eventdate > $now){
//         echo "
//             <div class='eventHolder'>
//                 <div class='eventDate'>$eventdate</div>
//                 <div class='eventTitle'>".$event['SUMMARY']."</div>
//             </div>";
//     }
// }
