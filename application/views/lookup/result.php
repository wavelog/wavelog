<?php
echo '
    <table style="width:100%" class="table-sm table table-bordered table-hover table-striped table-condensed text-center">
	    <thead>
			<tr>
				<th></th>';
			foreach($bands as $band) {
				echo '<th>' . $band . '</th>';
			}
    echo '</tr>
		</thead>
		<tbody>';
foreach ($result as $mode => $value) {

	$showRow = true;
	if ($reduced_mode) {
		$showRow = false;
		foreach ($value as $val) {
			if ($val == 'W' || $val == 'C') {
				$showRow = true;
				break;
			}
		}
	}

	if ($showRow) {
		echo '<tr>
				<td>'. strtoupper($mode) .'</td>';
		foreach ($value as $key => $val) {
			switch($type) {
				// function displayContacts(searchphrase, band, sat, orbit, mode, type, qsl) {

				case 'dxcc': $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $dxcc).'","' . $key . '","All","All","' . $mode . '","DXCC2")\'>'  . $val . '</a>'; break;
				case 'iota': $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $iota).'","' . $key . '","All","All","' . $mode . '","IOTA")\'>'   . $val . '</a>'; break;
				case 'vucc': $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $grid).'","' . $key . '","All","All","' . $mode . '","VUCC")\'>'   . $val . '</a>'; break;
				case 'cq':  $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $cqz).'","'  . $key . '","All","All","' . $mode . '","CQZone")\'>' . $val . '</a>'; break;
				case 'was':  $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $was).'","'  . $key . '","All","All","' . $mode . '","WAS")\'>'    . $val . '</a>'; break;
				case 'sota': $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $sota).'","' . $key . '","All","All","' . $mode . '","SOTA")\'>'   . $val . '</a>'; break;
				case 'pota': $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $pota).'","' . $key . '","All","All","' . $mode . '","POTA")\'>'   . $val . '</a>'; break;
				case 'wwff': $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $wwff).'","' . $key . '","All","All","' . $mode . '","WWFF")\'>'   . $val . '</a>'; break;
				case 'itu': $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $ituz).'","' . $key . '","All","All","' . $mode . '","ITU")\'>'   . $val . '</a>'; break;
				case 'continent': $linkinfo = '<a href=\'javascript:displayContacts("'.str_replace("&", "%26", $continent).'","' . $key . '","All","All","' . $mode . '","WAC")\'>'   . $val . '</a>'; break;
			}

			if ($current_band == $key && strtoupper($current_mode )== strtoupper($mode)) {
				$info = '<td class=\'border-3 border-danger\'>';
			} else {
				$info = '<td>';
			}

			if ($val == 'W') {
				$info .= '<div class=\'bg-danger awardsBgDanger\'>' . $linkinfo . '</div>';
			}
			else if ($val == 'C') {
				$info .= '<div class=\'bg-success awardsBgSuccess\'>' . $linkinfo . '</div>';
			}
			else {
				$info .= $val;
			}

			$info .= '</td>';

			echo $info;
		}
		echo '</tr>';
	}
}
echo '</tbody></table>';
?>
