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
		foreach ($mode as $cmode => $singlemode) {
			echo '<td>';
			if (($singlemode ?? '-') != '-') { 
				echo "<a href=\"javascript:displaySatQsos('".$sat."','".$cmode."')\">".$singlemode."</a>"; 
			} else {
				echo '-';
			}
			echo '</td>';
		}
        	echo "<th><a href=\"javascript:displaySatQsos('".$sat."');\">".$sattotal[$sat] . '</a></th>';
		echo '</tr>';
	}
    echo '</tbody><tfoot><tr><th>'.__("Total").'</th>';
	$grandtotal=0;
    foreach($modes as $mode) {
        echo '<th>' . $modetotal[$mode] . '</th>';
        $grandtotal += $modetotal[$mode];
    }
echo '<th>' . $grandtotal . '</th>';
    echo '</tr></tfoot></table>';
}
