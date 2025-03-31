<?php
 echo '
 <table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
	 <thead>
	 <tr>
	 <th>' . __("Name") . '</th>
	 <th>' . __("Mode") . '</th>
	 <th>' . __("Uplink mode") . '</th>
	 <th>' . __("Uplink frequency") . '</th>
	 <th>' . __("Downlink mode") . '</th>
	 <th>' . __("Downlink frequency") . '</th>
	 <th>' . __("Orbit") . '</th>
	 <th>' . __("LoTW") . '</th>
	 <th>' . __("TLE date") . '</th>
	 </tr>
	</thead>
	<tbody><tr>';
	foreach($satinfo as $sat) {
		echo '<tr>';
		echo '<td>' . $sat->satname . '</td>';
		echo '<td>' . $sat->modename . '</td>';
		echo '<td>' . $sat->uplink_mode . '</td>';
		echo '<td>' . $sat->uplink_freq . '</td>';
		echo '<td>' . $sat->downlink_mode . '</td>';
		echo '<td>' . $sat->downlink_freq . '</td>';
		echo '<td>' . $sat->orbit . '</td>';
		echo '<td>' . $sat->lotw . '</td>';
		echo '<td>' . $sat->updated . '</td>';
		echo '</tr>';
	}
		echo '</tbody>
	</table>';
?>
