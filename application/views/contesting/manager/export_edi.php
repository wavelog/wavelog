<?php
$reg1testsettings = $reg1test ?? [];

// Helper: select an option, falling back to a default if key is missing
function edi_selected($reg1testsettings, $key, $value, $default = '') {
	$saved = $reg1testsettings[$key] ?? $default;
	return $saved === $value ? 'selected' : '';
}
?>

<div class="container mt-4">

	<div class="d-flex align-items-center mb-3 gap-2">
		<a href="<?php echo site_url('contesting') ?>" class="btn btn-sm btn-secondary">
			<i class="fas fa-arrow-left me-1"></i><?= __("Contest Management") ?>
		</a>
		<h2 class="mb-0"><?php echo htmlspecialchars($page_title) ?></h2>
	</div>

	<p class="text-muted">
		<?php echo htmlspecialchars($session_info['station_callsign']) ?>
		&bull;
		<?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($session_info['time_start']))) ?>
		&ndash;
		<?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($session_info['time_end']))) ?>
		&bull;
		<?= sprintf(__("%d QSOs"), $qso_count) ?>
	</p>

	<?php if ($qso_count === 0): ?>
		<div class="alert alert-warning">
			<i class="fas fa-exclamation-triangle me-2"></i><?= __("This contest session has no QSOs yet. Export will produce an empty file.") ?>
		</div>
	<?php endif; ?>

	<?php if (!clubaccess_check(6)): ?>
		<div class="alert alert-info">
			<i class="fas fa-info-circle me-2"></i><?= __("You do not have permission to export files. Contact the station officer.") ?>
		</div>
	<?php endif; ?>

	<!-- ─── EDI Export ─────────────────────────────────────────────── -->
	<div class="mb-4">
		<div class="card">
			<div class="card-header fw-semibold">
				<i class="fas fa-file-alt me-2"></i><?= __("EDI Export") ?>
			</div>
			<div class="card-body">
				<p class="text-muted small mb-3">
					<?= __("Settings are saved automatically when you download. Name and e-mail are taken from your user profile.") ?>
				</p>

				<form method="post" action="<?php echo site_url('contesting/export_reg1test/' . $contest_session_id) ?>">

					<!-- Header data -->
					<fieldset class="mb-3">
						<legend class="h6"><?= __("Header data") ?> <span class="text-danger">*</span></legend>

						<div class="row g-2">
							<div class="col-sm-6">
								<label class="form-label" for="sentexchange">
									<?= __("Sent Exchange") ?>
									<i class="fas fa-question-circle text-muted ms-1"
										data-bs-toggle="tooltip"
										title="<?= __("The exchange which was sent during the contest. Can be any type of information, e.g. Province, DOK, County, State, Power, Name. Max. length: 6 characters."); ?>"></i>
								</label>
								<input type="text" class="form-control form-control-sm" id="sentexchange" name="sentexchange"
										value="<?php echo htmlspecialchars($reg1testsettings['sentexchange'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="club">
									<?= __("Club") ?>
									<i class="fas fa-question-circle text-muted ms-1"
										data-bs-toggle="tooltip"
										title="<?= __("Describes the callsign of the radio club where operator(s) are member. E.g. can be used if points are accumulated to the club."); ?>"></i>
								</label>
								<input type="text" class="form-control form-control-sm" id="club" name="club"
										value="<?php echo htmlspecialchars($reg1testsettings['club'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="categoryoperator"><?= __("Category Operator") ?></label>
								<select class="form-select form-select-sm" id="categoryoperator" name="categoryoperator">
									<option value="SINGLE-OP" <?php echo edi_selected($reg1testsettings, 'categoryoperator', 'SINGLE-OP', 'SINGLE-OP') ?>>SINGLE-OP</option>
									<option value="MULTI-OP"  <?php echo edi_selected($reg1testsettings, 'categoryoperator', 'MULTI-OP') ?>>MULTI-OP</option>
									<option value="CHECKLOG"  <?php echo edi_selected($reg1testsettings, 'categoryoperator', 'CHECKLOG') ?>>CHECKLOG</option>
								</select>
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="responsible_operator"><?= __("Callsign of responsible operator") ?></label>
								<input type="text" class="form-control form-control-sm" id="responsible_operator" name="responsible_operator"
										value="<?php echo htmlspecialchars($reg1testsettings['responsible_operator'] ?? '') ?>">
							</div>
							<div class="col-12">
								<label class="form-label" for="operators">
									<?= __("Operators") ?>
									<i class="fas fa-question-circle text-muted ms-1"
										data-bs-toggle="tooltip"
										title="<?= __("List of all operators. Seperated with a semicolon ';'. The responsible operator is not needed here."); ?>"></i>
								</label>
								<input type="text" class="form-control form-control-sm" id="operators" name="operators"
										value="<?php echo htmlspecialchars($session_operators) ?>"
										placeholder="<?php echo htmlspecialchars($session_info['station_callsign']) ?>">
							</div>
						</div>
					</fieldset>

					<!-- Location data -->
					<fieldset class="mb-3">
						<legend class="h6"><?= __("Location and Operator Information") ?></legend>
						<div class="row g-2">
							<div class="col-sm-6">
								<label class="form-label" for="contestaddress1"><?= __("Contest Address 1") ?></label>
								<input type="text" class="form-control form-control-sm" id="contestaddress1" name="contestaddress1"
										value="<?php echo htmlspecialchars($reg1testsettings['contestaddress1'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="contestaddress2"><?= __("Contest Address 2") ?></label>
								<input type="text" class="form-control form-control-sm" id="contestaddress2" name="contestaddress2"
										value="<?php echo htmlspecialchars($reg1testsettings['contestaddress2'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="address1"><?= __("Operator Address 1") ?></label>
								<input type="text" class="form-control form-control-sm" id="address1" name="address1"
										value="<?php echo htmlspecialchars($reg1testsettings['address1'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="address2"><?= __("Operator Address 2") ?></label>
								<input type="text" class="form-control form-control-sm" id="address2" name="address2"
										value="<?php echo htmlspecialchars($reg1testsettings['address2'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="addresspostalcode"><?= __("Operator Address Postalcode") ?></label>
								<input type="text" class="form-control form-control-sm" id="addresspostalcode" name="addresspostalcode"
										value="<?php echo htmlspecialchars($reg1testsettings['addresspostalcode'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="addresscity"><?= __("Operator Address City") ?></label>
								<input type="text" class="form-control form-control-sm" id="addresscity" name="addresscity"
										value="<?php echo htmlspecialchars($reg1testsettings['addresscity'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="addresscountry"><?= __("Operator Address Country") ?></label>
								<input type="text" class="form-control form-control-sm" id="addresscountry" name="addresscountry"
										value="<?php echo htmlspecialchars($reg1testsettings['addresscountry'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="operatorphone"><?= __("Operator Phone Number") ?></label>
								<input type="text" class="form-control form-control-sm" id="operatorphone" name="operatorphone"
										value="<?php echo htmlspecialchars($reg1testsettings['operatorphone'] ?? '') ?>">
							</div>
						</div>
					</fieldset>

					<!-- Additional Station Description -->
					<fieldset class="mb-3">
						<legend class="h6"><?= __("Additional Station Description") ?></legend>
						<div class="row g-2">
							<div class="col-sm-6">
								<label class="form-label" for="txequipment"><?= __("Transmit Equipment Description") ?></label>
								<input type="text" class="form-control form-control-sm" id="txequipment" name="txequipment"
										value="<?php echo htmlspecialchars($reg1testsettings['txequipment'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="power"><?= __("Transmit Power (W)") ?></label>
								<input type="text" class="form-control form-control-sm" id="power" name="power"
										value="<?php echo htmlspecialchars($reg1testsettings['power'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="rxequipment"><?= __("Receive Equipment Description") ?></label>
								<input type="text" class="form-control form-control-sm" id="rxequipment" name="rxequipment"
										value="<?php echo htmlspecialchars($reg1testsettings['rxequipment'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="antenna"><?= __("Antenna Description") ?></label>
								<input type="text" class="form-control form-control-sm" id="antenna" name="antenna"
										value="<?php echo htmlspecialchars($reg1testsettings['antenna'] ?? '') ?>">
							</div>
							<div class="col-sm-6">
								<label class="form-label" for="antennaheight"><?= __("Antenna Height Above Ground (m)") ?></label>
								<input type="text" class="form-control form-control-sm" id="antennaheight" name="antennaheight"
										value="<?php echo htmlspecialchars($reg1testsettings['antennaheight'] ?? '') ?>">
							</div>
							<div class="col-12">
								<label class="form-label" for="soapbox"><?= __("Soapbox") ?></label>
								<textarea class="form-control form-control-sm" id="soapbox" name="soapbox" rows="2"><?php echo htmlspecialchars($reg1testsettings['soapbox'] ?? '') ?></textarea>
							</div>
						</div>
					</fieldset>

					<fieldset class="mb-3">
						<legend class="h6"><?= __("Scoring Adjustment and Band Selection") ?></legend>

						<div class="row g-2 align-items-center">
							<div class="col-sm-6">
								<label class="form-label" for="bandmultiplicator"><?= __("Band multiplicator") ?></label>
								<input type="number" min="1" max="9999"	step="0.01" class="form-control form-control-sm" id="bandmultiplicator" name="bandmultiplicator" --
										value="<?= htmlspecialchars($reg1testsettings['bandmultiplicator'] ?? 1) ?>">
							</div>
							<div class="col-sm-6 d-flex align-items-center">
								<p class="small mb-0" style="color: red;"><?= __("The Band multiplicator is usually 1. Only change this if necessary according to the contest scoring rules."); ?>
								</p>
							</div>
						</div>
					</fieldset>

					<fieldset class="mb-3">
						<div class="col-sm-6">
								<label class="form-label" for="contestband"><?= __("Choose Band for export") ?></label>
								<select class="form-select form-select-sm" id="contestband" name="contestband">
									<?php
									foreach ($bands as $index => $band) {
										echo '<option value="' . $band . '" '.  edi_selected($reg1testsettings, 'contestband', $band, $index == 0 ? $band : '') . '>' . $band . '</option>';
									}
									?>
								</select>
							</div>
					</fieldset>

					<button type="submit" class="btn btn-primary" <?php echo clubaccess_check(6) ? '' : 'disabled' ?>>
						<i class="fas fa-download me-2"></i><?= __("Download EDI") ?>
					</button>
				</form>
			</div>
		</div>
	</div>
</div>

