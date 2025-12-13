<?php
switch ($type) {
	case 'checkdistance':
		check_missing_distance($result);
		break;
	case 'checkcontinent':
		check_qsos_missing_continent($result);
		break;
	case 'checkdxcc':
		check_missing_dxcc($result);
		break;
	case 'checkcqzones':
		check_missing_cq_zones($result);
		break;
	case 'checkituzones':
		check_missing_itu_zones($result);
		break;
	default:
		// Invalid type
		break;
}

function check_missing_distance($result) { ?>
	<h5>Distance Check Results</h5>
	QSOs to update found: <?php echo $result[0]->count; ?>
	<br/>
	<br/>
	<?= __("Update all QSOs with the distance based on your gridsquare set in the station profile, and the gridsquare of the QSO partner. Distance will be calculated based on if short path or long path is set."); ?>
	<?= __("This is useful if you have imported QSOs without distance information."); ?><br /><br />
	<?= __("Update will only set the distance for QSOs where the distance is empty."); ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateDistancesBtn" onclick="runUpdateDistancesFix('')">
		<?= __("Run fix") ?><div class="ld ld-ring ld-spin"></div>
	</button>

<?php }

function check_qsos_missing_continent($result) { ?>
	<h5>Continent Check Results</h5>
	QSOs to update found: <?php echo $result[0]->count; ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateDistancesBtn" onclick="fixMissingDxcc('All')">
		<?= __("Run fix") ?><div class="ld ld-ring ld-spin"></div>
	</button>
<?php }

function check_missing_dxcc($result) { ?>
	<h5>DXCC Check Results</h5>
	QSOs to update found: <?php echo $result[0]->count; ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="fixMissingDxccBtn" onclick="fixMissingDxcc(false)">
		<?= __("Run fix") ?><div class="ld ld-ring ld-spin"></div>
	</button>
	<button id="openMissingDxccListBtn" onclick="openMissingDxccList()" class="btn btn-sm btn-success mt-2 btn btn-sm ld-ext-right"><i class="fas fa-search"></i><div class="ld ld-ring ld-spin"></div></button>
<?php }

function check_missing_cq_zones($result) { ?>
	<h5>CQ Zone Check Results</h5>
	QSOs to update found: <?php echo $result[0]->count; ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateDistancesBtn" onclick="fixMissingDxcc('All')">
		<?= __("Run fix") ?><div class="ld ld-ring ld-spin"></div>
	</button>
<?php }

function check_missing_itu_zones($result) { ?>
	<h5>ITU Zone Check Results</h5>
	QSOs to update found: <?php echo $result[0]->count; ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateDistancesBtn" onclick="fixMissingDxcc('All')">
		<?= __("Run fix") ?><div class="ld ld-ring ld-spin"></div>
	</button>
<?php }
