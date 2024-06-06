<?php
if ($qsoarray) {
    echo '<br />
        <table style="width:100%" class="satuniquetable table-sm table table-bordered table-hover table-striped table-condensed text-center">
            <thead>';
                    echo '<tr><th></th>';
                    foreach($modes as $mode) {
                        echo '<th>' . $mode . '</th>';
                    }
                    echo '<th>'.__("Total").'</th>';
                    echo '</tr>
            </thead>
            <tbody>';
	foreach ($qsoarray as $sat => $mode) {
        	echo '<tr><th>'. $sat .'</th>';
		foreach ($mode as $singlemode) {
			echo '<td>'.$singlemode.'</td>';
		}
        	echo '<th>' . $satunique[$sat] . '</th>';
		echo '</tr>';
	}
    echo '</tbody><tfoot><tr><th>'.__("Total").'</th>';
    foreach($modes as $mode) {
        echo '<th>' . $modeunique[$mode] . '</th>';
    }
echo '<th>' . $total->calls . '</th>';
    echo '</tr></tfoot></table>';
}
