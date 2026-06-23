

<div class="tleinfo">
<?php
	if ($tleinfo->tle) {
		echo sprintf(__("TLE information for %s (last updated: %s)"), ($satinfo[0]->name ? $satinfo[0]->name : $satinfo[0]->displayname), date($custom_date_format . " H:i", strtotime($tleinfo->updated)));
		$_tle_raw = trim($tleinfo->tle);
		$_tle_show = $_tle_raw;
		if ($_tle_raw !== '' && ($_tle_raw[0] === '{' || $_tle_raw[0] === '[')) {
			$_tle_dec = json_decode($_tle_raw);
			if ($_tle_dec !== null) {
				$_tle_show = json_encode($_tle_dec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
			}
		}
		echo '<br /><br /><pre>' . htmlspecialchars($_tle_show) . '</pre>';
		echo '<button class="btn btn-sm btn-danger deletetlebutton" onclick="deleteTle(' . $satinfo[0]->id . ');">'.__("Delete TLE"). '</button>';
	} else {
		echo sprintf(__("No TLE information found for %s"), ($satinfo[0]->name ? $satinfo[0]->name : $satinfo[0]->displayname));
		echo '<br /><br /><button class="btn btn-sm btn-success addtlebutton" onclick="addTle(' . $satinfo[0]->id . ');">'.__("Add TLE"). '</button>';
	} ?>
	</div>
