

<div class="tleinfo">
<?php
	if ($tleinfo->tle) {
		echo sprintf(__("TLE information for %s (last updated: %s)"), $satinfo[0]->name, date($custom_date_format . " H:i", strtotime($tleinfo->updated)));
		echo '<br /><br /><pre>' . $tleinfo->tle . '</pre>';
		echo '<button class="btn btn-sm btn-danger deletetlebutton" onclick="deleteTle(' . $satinfo[0]->id . ');">'.__("Delete TLE"). '</button>';
	} else {
		echo sprintf(__("No TLE information found for %s"), $satinfo[0]->name);
		echo '<br /><br /><button class="btn btn-sm btn-success addtlebutton" onclick="addTle(' . $satinfo[0]->id . ');">'.__("Add TLE"). '</button>';
	} ?>
	</div>
