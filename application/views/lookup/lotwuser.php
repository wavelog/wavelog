<?php
echo 'LoTW User: ';
if (isset($lotw_lastupload) && $lotw_lastupload != '') {
	$lotw_hint = '';
	if ($lotw_lastupload > 365) {
		$lotw_hint = ' lotw_info_red';
	} elseif ($lotw_lastupload > 30) {
		$lotw_hint = ' lotw_info_orange';
	} elseif ($lotw_lastupload > 7) {
		$lotw_hint = ' lotw_info_yellow';
	}
	echo '<a id="lotw_badge" href="https://lotw.arrl.org/lotwuser/act?act='.$callsign.'" target="_blank"><small id="lotw_info" class="badge text-bg-success'.$lotw_hint.'" data-bs-toggle="tooltip" title="LoTW User">Yes</small></a> <a id="lotw_badge" href="https://lotw.arrl.org/lotwuser/act?act='.$callsign.'" target="_blank"> last upload</a> '.$lotw_lastupload.' days ago';
} else {
	echo "<span data-bs-toggle=\"tooltip\" title=\"No LoTW User\" class=\"badge text-bg-danger\" style=\"padding-left: 0.2em; padding-right: 0.2em;\">No</span>";
}
?>
