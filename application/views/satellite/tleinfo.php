

<div class="tleinfo">
<?php
	if ($tleinfo) {
		echo 'TLE information for ' . $satinfo[0]->name . ' (last updated: ' . date($custom_date_format . " H:i", strtotime($tleinfo->updated)) . ')' ;
		echo '<br /><br /><pre>' . $tleinfo->tle . '</pre>';
		echo '<button class="btn btn-sm btn-danger deletetlebutton" onclick="deleteTle(' . $satinfo[0]->id . ');">'.__("Delete TLE"). '</button>';
	} else {
		echo 'No TLE information found for ' . $satinfo[0]->name;
		echo '<br /><br /><button class="btn btn-sm btn-success addtlebutton" onclick="addTle(' . $satinfo[0]->id . ');">'.__("Add TLE"). '</button>';
	} ?>
	</div>
