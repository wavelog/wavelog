<?php
// Get Date format
if($this->session->userdata('user_date_format')) {
	// If Logged in and session exists
	$custom_date_format = $this->session->userdata('user_date_format');
} else {
	// Get Default date format from /config/wavelog.php
	$custom_date_format = $this->config->item('qso_date_format');
}


switch ($type) {
	case 'checkdistance':
		check_missing_distance($result);
		break;
	case 'checkcontinent':
		check_qsos_missing_continent($result);
		break;
	case 'checkmissingdxcc':
		check_missing_dxcc($result);
		break;
	case 'checkcqzones':
		check_missing_cq_zones($result);
		break;
	case 'checkituzones':
		check_missing_itu_zones($result);
		break;
	case 'checkgrids':
		check_missing_grids($result);
		break;
	case 'checkdxcc':
		check_dxcc($result, $custom_date_format);
		break;
	default:
		// Invalid type
		break;
}

function check_missing_distance($result) { ?>
	<h5><?= __("Distance Check Results") ?></h5>
	<?= __("QSOs to update found:"); ?> <?php echo $result[0]->count; ?>
	<br/>
	<br/>
	<?= __("Update all QSOs with the distance based on your gridsquare set in the station profile, and the gridsquare of the QSO partner. Distance will be calculated based on if short path or long path is set."); ?>
	<?= __("This is useful if you have imported QSOs without distance information."); ?><br /><br />
	<?= __("Update will only set the distance for QSOs where the distance is empty."); ?>
	<?php if ($result[0]->count > 0) { ?>
	<br />
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateDistanceButton" onclick="runUpdateDistancesFix('')">
		<?= __("Update now") ?><div class="ld ld-ring ld-spin"></div>
	</button>
	<?php }
}

function check_qsos_missing_continent($result) { ?>
	<h5><?= __("Continent Check Results") ?></h5>
	<?= __("QSOs to update found:"); ?> <?php echo $result[0]->count; ?>
	<br/>
	<br/>
	<?= __("Update all QSOs with the continent based on the DXCC country of the QSO."); ?>
	<?= __("This is useful if you have imported QSOs without continent information."); ?><br /><br />
	<?= __("Update will only set the continent for QSOs where the continent is empty."); ?>
	<?php if ($result[0]->count > 0) { ?>
	<br />
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateContinentButton" onclick="runContinentFix('')">
		<?= __("Update now") ?><div class="ld ld-ring ld-spin"></div>
	</button>
	<?php }
}

function check_missing_dxcc($result) { ?>
	<h5><?= __("DXCC Check Results") ?></h5>
	<?= __("QSOs to update found:"); ?> <?php echo $result[0]->count; ?>
	<?php if ($result[0]->count > 0) { ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="fixMissingDxccBtn" onclick="fixMissingDxcc(false)">
		<?= __("Run fix") ?><div class="ld ld-ring ld-spin"></div>
	</button>
	<button id="openMissingDxccListBtn" onclick="openMissingDxccList()" class="btn btn-sm btn-success mt-2 btn btn-sm ld-ext-right"><i class="fas fa-search"></i><div class="ld ld-ring ld-spin"></div></button>
	<?php }
}

function check_missing_cq_zones($result) { ?>
	<h5><?= __("CQ Zone Check Results") ?></h5>
	<?= __("QSOs to update found:"); ?> <?php echo $result[0]->count; ?>
	<?php if ($result[0]->count > 0) { ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateCqZonesBtn" onclick="fixMissingCqZones()">
		<?= __("Update now") ?><div class="ld ld-ring ld-spin"></div>
	</button>
	<?php }
}

function check_missing_itu_zones($result) { ?>
	<h5><?= __("ITU Zone Check Results") ?></h5>
	<?= __("QSOs to update found:"); ?> <?php echo $result[0]->count; ?>
	<?php if ($result[0]->count > 0) { ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateItuZonesBtn" onclick="fixMissingItuZones()">
		<?= __("Update now") ?><div class="ld ld-ring ld-spin"></div>
	</button>
	<?php }
}

function check_missing_grids($result) { ?>
	<h5><?= __("Gridsquare Check Results") ?></h5>
	<?= __("QSOs to update found:"); ?> <?php echo count($result); ?>
	<br/>
	<button type="button" class="mt-2 btn btn-sm btn-primary ld-ext-right" id="updateGridsBtn" onclick="fixMissingGrids()">
		<?= __("Update now") ?><div class="ld ld-ring ld-spin"></div>
	</button>
<?php }

function check_dxcc($result, $custom_date_format) { ?>
	<h5><?= __("DXCC Check Results") ?></h5>
	<?php
		echo "<p>" . __("Callsigns tested: ") .  $result['calls_tested'] . "</p>";
		echo "<p>" . __("Execution time: ") . round($result['execution_time'], 2) . "s</p>";
		echo "<p>" . __("Number of potential QSOs with wrong DXCC: ") . count($result['result']) . "</p>";

		if ($result) { ?>
		<div class="table-responsive" style="max-height:70vh; overflow:auto;">
			<table class="table table-sm table-striped table-bordered table-condensed mb-0">
				<thead>
					<tr>
						<th><?= __("Callsign"); ?></th>
						<th><?= __("QSO Date"); ?></th>
						<th><?= __("Station Profile"); ?></th>
						<th><?= __("Existing DXCC"); ?></th>
						<th><?= __("Result DXCC"); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($result['result'] as $qso): ?>
						<tr>
							<td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso['id'] . ')">' . htmlspecialchars($qso['callsign']) . '</a>'; ?></td>
							<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
							<td><?php echo $qso['station_profile']; ?></td>
							<td><?php echo htmlspecialchars(ucwords(strtolower($qso['existing_dxcc']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
							<td><?php echo htmlspecialchars(ucwords(strtolower($qso['result_country']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php }
}
