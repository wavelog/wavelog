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
	case 'checkgrids':
		check_missing_grids($result);
		break;
	case 'checkdxcc':
		check_dxcc($result, $custom_date_format);
		break;
	case 'checkincorrectgridsquares':
		check_incorrect_gridsquares($result, $custom_date_format);
		break;
	case 'checkincorrectcqzones':
		check_incorrect_cq_zones($result, $custom_date_format);
		break;
	case 'checkincorrectituzones':
		check_incorrect_itu_zones($result, $custom_date_format);
		break;
	case 'checkiota':
		check_iota($result, $custom_date_format);
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
		echo __("Callsigns tested: ") .  $result['calls_tested'] . ". <br />";
		echo __("Execution time: ") . round($result['execution_time'], 2) . "s. <br />";
		echo __("Number of potential QSOs with wrong DXCC: ") . count($result['result']);

		if ($result) { ?>
		<br />
		<button type="button" class="mt-2 mb-2 btn btn-sm btn-primary ld-ext-right" id="fixSelectedDxccBtn" onclick="fixDxccSelected(true)">
			<?= __("Update selected") ?><div class="ld ld-ring ld-spin"></div>
		</button>
		<div class="dxcctablediv"></div>

			<div class="table-responsive" style="max-height:60vh; overflow:auto;">
				<table class="table table-sm table-striped table-bordered table-condensed" id="dxccCheckTable">
					<thead>
						<tr>
							<th><div class="form-check"><input class="form-check-input mt-2" type="checkbox" id="checkBoxAllDxcc" /></div></th>
							<th><?= __("Callsign"); ?></th>
							<th><?= __("QSO Date"); ?></th>
							<th class="select-filter" scope="col"><?= __("Band"); ?></th>
							<th class="select-filter" scope="col"><?= __("Mode"); ?></th>
							<th style='text-align: center' class="select-filter" scope="col"><?= __("LoTW"); ?></th>
							<th class="select-filter" scope="col"><?= __("Station Profile"); ?></th>
							<th class="select-filter" scope="col"><?= __("Existing DXCC"); ?></th>
							<th class="select-filter" scope="col"><?= __("Result DXCC"); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($result['result'] as $qso): ?>
								<tr id="qsoID-<?php echo $qso['id']; ?>">
									<td><div class="form-check"><input class="row-check form-check-input mt-1" type="checkbox" /></div></td>
									<td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso['id'] . ')">' . htmlspecialchars($qso['callsign']) . '</a>'; ?></td>
									<td><?php echo date($custom_date_format, strtotime($qso['qso_date'])); ?></td>
									<td ><?php if($qso['sat_name'] != '') { echo $qso['sat_name']; } else { echo strtolower($qso['band']); }; ?></td>
									<td><?php echo htmlspecialchars($qso['submode'] ? $qso['submode'] : $qso['mode']); ?></td>
									<td style='text-align: center'><div class="<?php echo $qso['lotw_qsl_rcvd'] == 'Y' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $qso['lotw_qsl_rcvd'] == 'Y' ? __('Yes') : __('No'); ?></div></td>
									<td><?php echo $qso['station_profile']; ?></td>
									<td><?php echo htmlspecialchars(ucwords(strtolower($qso['existing_dxcc']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
									<td><?php echo htmlspecialchars(ucwords(strtolower($qso['result_country']), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
								</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</tfoot>
				</table>
			</div>

		<?php }
}

function check_incorrect_gridsquares($result, $custom_date_format) { ?>
	<h5><?= __("Gridsquare Check Results") ?></h5>
	<?php
		if (is_array($result) && isset($result['status']) && $result['status'] == 'error') {
			echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($result['message']) . '</div>';
			return;
		}
		if ($result) { ?>
		<?= __("These QSOs MAY have incorrect gridsquares.") ?>
		<?= __("Results depends on the correct DXCC. The gridsquare list comes from the TQSL gridsquare database.") ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped table-bordered table-condensed" id="gridsquareCheckTable">
					<thead>
						<tr>
							<th><?= __("Callsign"); ?></th>
							<th><?= __("QSO Date"); ?></th>
							<th class="select-filter" scope="col"><?= __("Band"); ?></th>
							<th class="select-filter" scope="col"><?= __("Mode"); ?></th>
							<th style='text-align: center' class="select-filter" scope="col"><?= __("LoTW"); ?></th>
							<th class="select-filter" scope="col"><?= __("Station Profile"); ?></th>
							<th class="select-filter" scope="col"><?= __("DXCC"); ?></th>
							<th><?= __("Gridsquare"); ?></th>
							<th><?= __("DXCC Gridsquare"); ?></th>
							<th><?= __("Map"); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($result as $qso): ?>
								<tr id="qsoID-<?php echo $qso->col_primary_key; ?>">
									<td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso->col_primary_key . ')">' . htmlspecialchars($qso->col_call) . '</a>'; ?></td>
									<td><?php echo date($custom_date_format, strtotime($qso->col_time_on)); ?></td>
									<td ><?php if($qso->col_sat_name != null) { echo $qso->col_sat_name; } else { echo strtolower($qso->col_band); }; ?></td>
									<td><?php echo htmlspecialchars($qso->col_submode ? $qso->col_submode : $qso->col_mode); ?></td>
									<td style='text-align: center'><div class="<?php echo $qso->col_lotw_qsl_rcvd == 'Y' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $qso->col_lotw_qsl_rcvd == 'Y' ? __('Yes') : __('No'); ?></div></td>
									<td><?php echo $qso->station_profile_name; ?></td>
									<td><?php echo htmlspecialchars(ucwords(strtolower($qso->col_country), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
									<td><?php echo $qso->col_gridsquare; ?></td>
									<td>
										<?php
										$gridsquare = $qso->correctgridsquare;
										$maxChars = 50;
										if (strlen($gridsquare) > $maxChars) {
											$truncated = substr($gridsquare, 0, $maxChars);
											$uniqueId = 'gridsquare-' . $qso->col_primary_key;
											echo '<span id="' . $uniqueId . '-short">' . htmlspecialchars($truncated) . '...</span> ';
											echo '<span id="' . $uniqueId . '-full" style="display:none;">' . htmlspecialchars($gridsquare) . '</span> ';
											echo '<a href="javascript:void(0)" onclick="toggleGridsquare(\'' . $uniqueId . '\')" id="' . $uniqueId . '-link">' . __('Show more') . '</a>';
										} else {
											echo htmlspecialchars($gridsquare);
										}
										?>
									</td>
									<td><a href="javascript:showMapForIncorrectGrid('<?php echo $qso->col_gridsquare; ?>','<?php echo $qso->col_dxcc; ?>','<?php echo htmlspecialchars(ucwords(strtolower($qso->col_country), "- (/"), ENT_QUOTES, 'UTF-8'); ?>')"><i class="fas fa-map-marker-alt"></i> <?php echo __('View on map'); ?></a></td>
								</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</tfoot>
				</table>
			</div>

		<?php }
}

function check_incorrect_cq_zones($result, $custom_date_format) { ?>
	<h5><?= __("CQ Zone Check Results") ?></h5>
	<?php if ($result) {
		echo __("The following QSOs were found to have a different CQ zone compared to what this DXCC normally has (a maximum of 5000 QSOs are shown):"); ?>
		<br /><br />
		<div class="col-md-12">
			<div class="form-check form-check-lg border rounded p-3 bg-light h-100">
				<input class="form-check-input me-2" type="checkbox" id="forceMultiZoneUpdateCq" />
				<?= __("Force update even if DXCC covers multiple CQ zones") ?>
				<div class="d-block mb-1 alert-danger"><?= __("The update function can only set the main CQ zone which is assigned to the DXCC. If the DXCC covers multiple CQ zones there is a chance that this is not correct. So by default only QSOs with DXCCs covering a single CQ zone are updated. This checkbox overrides this but might result in wrong data. Use with caution!"); ?></div>
			</div>
		</div>
		<button type="button" class="mt-2 mb-2 btn btn-sm btn-primary ld-ext-right" id="fixSelectedCqZoneBtn" onclick="fixCqZoneSelected(true)">
			<?= __("Update selected") ?><div class="ld ld-ring ld-spin"></div>
		</button>
		<?php echo '<table style="width:100%" class="qsolist table table-sm table-bordered table-hover table-striped table-condensed" id="incorrectcqzonetable">
		<thead>
			<tr>
				<th><div class="form-check"><input class="form-check-input mt-2" type="checkbox" id="checkBoxAllCqZones" /></div></th>
				<th style=\'text-align: center\'>' . __("Callsign") . '</th>
				<th style=\'text-align: center\'>' . __("Date") . '</th>
				<th style=\'text-align: center\'>' . __("Time") . '</th>
				<th style=\'text-align: center\'>' . __("Mode") . '</th>
				<th style=\'text-align: center\'>' . __("Band") . '</th>
				<th style=\'text-align: center\'>' . __("Gridsquare") . '</th>
				<th style=\'text-align: center\' class="select-filter" scope="col">' . __("CQ Zone") . '</th>
				<th style=\'text-align: center\'>' . __("DXCC CQ Zone") . '</th>
				<th style=\'text-align: center\' class="select-filter" scope="col">' . __("DXCC") . '</th>
				<th style=\'text-align: center\' class="select-filter" scope="col">' . __("Station") . '</th>
			</tr>
		</thead>
		<tbody>';

		$i = 0;

		foreach ($result as $qso) {
			echo '<tr id="qsoID-'. $qso->COL_PRIMARY_KEY .'">';
			echo '<td><div class="form-check"><input class="row-check form-check-input mt-1" type="checkbox" /></div></td>';
			echo '<td style=\'text-align: center\'><a id="edit_qso" href="javascript:displayQso(' . $qso->COL_PRIMARY_KEY . ')">' . str_replace("0","&Oslash;",strtoupper($qso->COL_CALL)) . '</a></td>';
			echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qso->COL_TIME_ON); echo date($custom_date_format, $timestamp); echo '</td>';
			echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qso->COL_TIME_ON); echo date('H:i', $timestamp); echo '</td>';
			echo '<td style=\'text-align: center\'>'; echo $qso->COL_SUBMODE==null?$qso->COL_MODE:$qso->COL_SUBMODE; echo '</td>';
			echo '<td style=\'text-align: center\'>'; if($qso->COL_SAT_NAME != null) { echo $qso->COL_SAT_NAME; } else { echo strtolower($qso->COL_BAND); }; echo '</td>';
			echo '<td style=\'text-align: center\'>'; echo strlen($qso->COL_GRIDSQUARE ?? '')==0?$qso->COL_VUCC_GRIDS:$qso->COL_GRIDSQUARE; echo '</td>';
			echo '<td style=\'text-align: center\'>' . $qso->COL_CQZ . '</td>';
			echo '<td id=\'cqZones\' style=\'text-align: center\'>' . $qso->correctcqzone . '</td>';
			echo '<td style=\'text-align: center\'>' . ucwords(strtolower($qso->COL_COUNTRY), "- (/") . '</td>';
			echo '<td style=\'text-align: center\'>' . $qso->station_profile_name . '</td>';
			echo '</tr>';
		}

		echo '</tbody>
		<tfoot>
			<tr>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
		</tfoot>
		</table>';
		?>

		<?php
	} else {
		echo '<div class="alert alert-success">' . __("No incorrect CQ Zones were found.") . '</div>';
	}
}

function check_incorrect_itu_zones($result, $custom_date_format) { ?>
	<h5><?= __("ITU Zone Check Results") ?></h5>
	<?php if ($result) {
		echo __("The following QSOs were found to have a different ITU zone compared to what this DXCC normally has (a maximum of 5000 QSOs are shown):"); ?>
		<br /><br />
		<div class="col-md-12">
			<div class="form-check form-check-lg border rounded p-3 bg-light h-100">
				<input class="form-check-input me-2" type="checkbox" id="forceMultiZoneUpdate" />
				<?= __("Force update even if DXCC covers multiple ITU zones") ?>
				<div class="d-block mb-1 alert-danger"><?= __("The update function can only set the main ITU zone which is assigned to the DXCC. If the DXCC covers multiple ITU zones there is a chance that this is not correct. So by default only QSOs with DXCCs covering a single ITU zone are updated. This checkbox overrides this but might result in wrong data. Use with caution!"); ?></div>
			</div>
		</div>
		<button type="button" class="mt-2 mb-2 btn btn-sm btn-primary ld-ext-right" id="fixSelectedItuZoneBtn" onclick="fixItuZoneSelected(true)">
			<?= __("Update selected") ?><div class="ld ld-ring ld-spin"></div>
		</button>
		<?php echo '<table style="width:100%" class="qsolist table table-sm table-bordered table-hover table-striped table-condensed" id="incorrectituzonetable">
		<thead>
			<tr>
				<th><div class="form-check"><input class="form-check-input mt-2" type="checkbox" id="checkBoxAllItuZones" /></div></th>
				<th style=\'text-align: center\'>' . __("Callsign") . '</th>
				<th style=\'text-align: center\'>' . __("Date") . '</th>
				<th style=\'text-align: center\'>' . __("Time") . '</th>
				<th style=\'text-align: center\'>' . __("Mode") . '</th>
				<th style=\'text-align: center\'>' . __("Band") . '</th>
				<th style=\'text-align: center\'>' . __("Gridsquare") . '</th>
				<th style=\'text-align: center\' class="select-filter" scope="col">' . __("ITU Zone") . '</th>
				<th style=\'text-align: center\'>' . __("DXCC ITU Zone") . '</th>
				<th style=\'text-align: center\' class="select-filter" scope="col">' . __("DXCC") . '</th>
				<th style=\'text-align: center\' class="select-filter" scope="col">' . __("Station") . '</th>
			</tr>
		</thead>
		<tbody>';

		$i = 0;

		foreach ($result as $qso) {
			echo '<tr id="qsoID-'. $qso->COL_PRIMARY_KEY .'">';
			echo '<td><div class="form-check"><input class="row-check form-check-input mt-1" type="checkbox" /></div></td>';
			echo '<td style=\'text-align: center\'><a id="edit_qso" href="javascript:displayQso(' . $qso->COL_PRIMARY_KEY . ')">' . str_replace("0","&Oslash;",strtoupper($qso->COL_CALL)) . '</a></td>';
			echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qso->COL_TIME_ON); echo date($custom_date_format, $timestamp); echo '</td>';
			echo '<td style=\'text-align: center\'>'; $timestamp = strtotime($qso->COL_TIME_ON); echo date('H:i', $timestamp); echo '</td>';
			echo '<td style=\'text-align: center\'>'; echo $qso->COL_SUBMODE==null?$qso->COL_MODE:$qso->COL_SUBMODE; echo '</td>';
			echo '<td style=\'text-align: center\'>'; if($qso->COL_SAT_NAME != null) { echo $qso->COL_SAT_NAME; } else { echo strtolower($qso->COL_BAND); }; echo '</td>';
			echo '<td style=\'text-align: center\'>'; echo strlen($qso->COL_GRIDSQUARE ?? '')==0?$qso->COL_VUCC_GRIDS:$qso->COL_GRIDSQUARE; echo '</td>';
			echo '<td style=\'text-align: center\'>' . $qso->COL_ITUZ . '</td>';
			echo '<td id=\'ituZones\' style=\'text-align: center\'>' . $qso->correctituzone . '</td>';
			echo '<td style=\'text-align: center\'>' . ucwords(strtolower($qso->COL_COUNTRY), "- (/") . '</td>';
			echo '<td style=\'text-align: center\'>' . $qso->station_profile_name . '</td>';
			echo '</tr>';
		}

		echo '</tbody>
		<tfoot>
			<tr>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
				<th></th>
			</tr>
		</tfoot>
		</table>';
		?>

		<?php
	} else {
		echo '<div class="alert alert-success">' . __("No incorrect CQ Zones were found.") . '</div>';
	}
}

function check_iota($result, $custom_date_format) { ?>
	<h5><?= __("IOTA Check Results") ?></h5>
	<?php
		if (is_array($result) && isset($result['status']) && $result['status'] == 'error') {
			echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($result['message']) . '</div>';
			return;
		}
		if ($result) { ?>
		<?= __("These QSOs MAY have an incorrect IOTA reference.") ?>
		<?= __("Results depends on the correct DXCC, and it will only be checked against current DXCC. False positive results may occur.") ?>
			<div class="table-responsive">
				<table class="table table-sm table-striped table-bordered table-condensed" id="iotaCheckTable">
					<thead>
						<tr>
							<th class="select-filter" scope="col"><?= __("Callsign"); ?></th>
							<th><?= __("QSO Date"); ?></th>
							<th class="select-filter" scope="col"><?= __("Band"); ?></th>
							<th class="select-filter" scope="col"><?= __("Mode"); ?></th>
							<th style='text-align: center' class="select-filter" scope="col"><?= __("LoTW"); ?></th>
							<th class="select-filter" scope="col"><?= __("Station Profile"); ?></th>
							<th class="select-filter" scope="col"><?= __("QSO DXCC"); ?></th>
							<th class="select-filter" scope="col"><?= __("IOTA"); ?></th>
							<th class="select-filter" scope="col"><?= __("IOTA DXCC"); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($result as $qso): ?>
								<tr id="qsoID-<?php echo $qso->col_primary_key; ?>">
									<td><?php echo '<a id="edit_qso" href="javascript:displayQso(' . $qso->col_primary_key . ')">' . htmlspecialchars($qso->col_call) . '</a>'; ?></td>
									<td><?php echo date($custom_date_format, strtotime($qso->col_time_on)); ?></td>
									<td ><?php if($qso->col_sat_name != null) { echo $qso->col_sat_name; } else { echo strtolower($qso->col_band); }; ?></td>
									<td><?php echo htmlspecialchars($qso->col_submode ? $qso->col_submode : $qso->col_mode); ?></td>
									<td style='text-align: center'><div class="<?php echo $qso->col_lotw_qsl_rcvd == 'Y' ? 'bg-success' : 'bg-danger'; ?>"><?php echo $qso->col_lotw_qsl_rcvd == 'Y' ? __('Yes') : __('No'); ?></div></td>
									<td><?php echo $qso->station_profile_name; ?></td>
									<td><?php echo htmlspecialchars(ucwords(strtolower($qso->col_country), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
									<td><?php echo '<a href=\'javascript:displayContacts("'.$qso->col_iota.'","All","All","All","All","IOTA")\'>' . $qso->col_iota . '</a>'; ?> <a href="https://www.iota-world.org/iotamaps/?grpref=<?php echo $qso->col_iota; ?>" target="_blank"><i class="fas fa-globe"></i></a></td>
									<td><?php echo htmlspecialchars(ucwords(strtolower($qso->correctdxcc ?? ''), "- (/"), ENT_QUOTES, 'UTF-8'); ?></td>
								</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
							<th></th>
						</tr>
					</tfoot>
				</table>
			</div>

		<?php }
}