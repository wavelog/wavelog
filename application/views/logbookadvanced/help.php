<?= __("In the text input searches, you can search in the following way:"); ?><br />
<li><?= __("* - means search for everything."); ?></li>
<li><?= __("Remove star and leave blank, means to search where column is empty."); ?></li>
<li><?= __("!empty - means to search where column is not empty."); ?></li>
<br />
<?= __("The dupe search checks for duplicate QSOs with the same callsign, mode, submode, station callsign, band and satellite within 1500 seconds."); ?>
<br /><br />
<?= __("The invalid search checks for the following conditions:"); ?>
<li><?= __("Mode is blank or set to 0."); ?></li>
<li><?= __("Band is blank."); ?></li>
<li><?= __("Callsign is blank."); ?></li>
<li><?= __("Time and date is not set."); ?></li>
<li><?= __("Date is set to 1970-01-01."); ?></li>
<li><?= __("Continent different from AF, AN, AS, EU, NA, OC or SA."); ?></li>
<br />
<?= __("The map uses the same search criteria as the normal search. All QSOs in the search result will be mapped, unless you have checked one or more QSOs."); ?>
<br /><br />
<?= __("The ADIF export uses the same search criteria as the normal search. All QSOs will be exported (all for selected location), unless you have checked one or more QSOs."); ?>
