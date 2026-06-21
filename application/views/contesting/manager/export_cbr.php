<?php
$cbr = $cabrillo ?? [];

// Helper: select an option, falling back to a default if key is missing
function cbr_selected($cbr, $key, $value, $default = '') {
	$saved = $cbr[$key] ?? $default;
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

	<!-- ─── Cabrillo Export ─────────────────────────────────────────────── -->
	<div class="mb-4">
		<div class="card">
				<div class="card-header fw-semibold">
					<i class="fas fa-file-alt me-2"></i><?= __("Cabrillo Export") ?>
				</div>
				<div class="card-body">
					<p class="text-muted small mb-3">
						<?= __("Settings are saved automatically when you download. Name and e-mail are taken from your user profile.") ?>
					</p>

					<form method="post" action="<?php echo site_url('contesting/export_cabrillo/' . $contest_session_id) ?>">

						<!-- Category (required) -->
						<fieldset class="mb-3">
							<legend class="h6"><?= __("Category") ?> <span class="text-danger">*</span></legend>

							<div class="row g-2">
								<div class="col-sm-6">
									<label class="form-label" for="categoryoperator"><?= __("Operator") ?></label>
									<select class="form-select form-select-sm" id="categoryoperator" name="categoryoperator" required>
										<option value="SINGLE-OP" <?php echo cbr_selected($cbr, 'category_operator', 'SINGLE-OP', 'SINGLE-OP') ?>>SINGLE-OP</option>
										<option value="MULTI-OP"  <?php echo cbr_selected($cbr, 'category_operator', 'MULTI-OP') ?>>MULTI-OP</option>
										<option value="CHECKLOG"  <?php echo cbr_selected($cbr, 'category_operator', 'CHECKLOG') ?>>CHECKLOG</option>
									</select>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="categoryassisted"><?= __("Assisted") ?></label>
									<select class="form-select form-select-sm" id="categoryassisted" name="categoryassisted" required>
										<option value="NON-ASSISTED" <?php echo cbr_selected($cbr, 'category_assisted', 'NON-ASSISTED', 'NON-ASSISTED') ?>>NON-ASSISTED</option>
										<option value="ASSISTED"     <?php echo cbr_selected($cbr, 'category_assisted', 'ASSISTED') ?>>ASSISTED</option>
									</select>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="categoryband"><?= __("Band") ?></label>
									<select class="form-select form-select-sm" id="categoryband" name="categoryband" required>
										<option value="ALL"     <?php echo cbr_selected($cbr, 'category_band', 'ALL', 'ALL') ?>><?= __("All") ?></option>
										<option value="160M"    <?php echo cbr_selected($cbr, 'category_band', '160M') ?>>160 M</option>
										<option value="80M"     <?php echo cbr_selected($cbr, 'category_band', '80M') ?>>80 M</option>
										<option value="40M"     <?php echo cbr_selected($cbr, 'category_band', '40M') ?>>40 M</option>
										<option value="20M"     <?php echo cbr_selected($cbr, 'category_band', '20M') ?>>20 M</option>
										<option value="15M"     <?php echo cbr_selected($cbr, 'category_band', '15M') ?>>15 M</option>
										<option value="10M"     <?php echo cbr_selected($cbr, 'category_band', '10M') ?>>10 M</option>
										<option value="6M"      <?php echo cbr_selected($cbr, 'category_band', '6M') ?>>6 M</option>
										<option value="4M"      <?php echo cbr_selected($cbr, 'category_band', '4M') ?>>4 M</option>
										<option value="2M"      <?php echo cbr_selected($cbr, 'category_band', '2M') ?>>2 M</option>
										<option value="222"     <?php echo cbr_selected($cbr, 'category_band', '222') ?>>222 MHz</option>
										<option value="432"     <?php echo cbr_selected($cbr, 'category_band', '432') ?>>432 MHz</option>
										<option value="902"     <?php echo cbr_selected($cbr, 'category_band', '902') ?>>902 MHz</option>
										<option value="1.2G"    <?php echo cbr_selected($cbr, 'category_band', '1.2G') ?>>1.2 GHz</option>
										<option value="2.3G"    <?php echo cbr_selected($cbr, 'category_band', '2.3G') ?>>2.3 GHz</option>
										<option value="3.4G"    <?php echo cbr_selected($cbr, 'category_band', '3.4G') ?>>3.4 GHz</option>
										<option value="5.7G"    <?php echo cbr_selected($cbr, 'category_band', '5.7G') ?>>5.7 GHz</option>
										<option value="10G"     <?php echo cbr_selected($cbr, 'category_band', '10G') ?>>10 GHz</option>
										<option value="24G"     <?php echo cbr_selected($cbr, 'category_band', '24G') ?>>24 GHz</option>
										<option value="47G"     <?php echo cbr_selected($cbr, 'category_band', '47G') ?>>47 GHz</option>
										<option value="75G"     <?php echo cbr_selected($cbr, 'category_band', '75G') ?>>75 GHz</option>
										<option value="122G"    <?php echo cbr_selected($cbr, 'category_band', '122G') ?>>122 GHz</option>
										<option value="134G"    <?php echo cbr_selected($cbr, 'category_band', '134G') ?>>134 GHz</option>
										<option value="241G"    <?php echo cbr_selected($cbr, 'category_band', '241G') ?>>241 GHz</option>
										<option value="Light"   <?php echo cbr_selected($cbr, 'category_band', 'Light') ?>><?= __("Light/Laser") ?></option>
									</select>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="categorymode"><?= __("Mode") ?></label>
									<select class="form-select form-select-sm" id="categorymode" name="categorymode" required>
										<option value="MIXED" <?php echo cbr_selected($cbr, 'category_mode', 'MIXED', 'MIXED') ?>>MIXED</option>
										<option value="CW"    <?php echo cbr_selected($cbr, 'category_mode', 'CW') ?>>CW</option>
										<option value="DIGI"  <?php echo cbr_selected($cbr, 'category_mode', 'DIGI') ?>>DIGI</option>
										<option value="FM"    <?php echo cbr_selected($cbr, 'category_mode', 'FM') ?>>FM</option>
										<option value="RTTY"  <?php echo cbr_selected($cbr, 'category_mode', 'RTTY') ?>>RTTY</option>
										<option value="SSB"   <?php echo cbr_selected($cbr, 'category_mode', 'SSB') ?>>SSB</option>
									</select>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="categorypower"><?= __("Power") ?></label>
									<select class="form-select form-select-sm" id="categorypower" name="categorypower" required>
										<option value="LOW"  <?php echo cbr_selected($cbr, 'category_power', 'LOW', 'LOW') ?>>LOW</option>
										<option value="HIGH" <?php echo cbr_selected($cbr, 'category_power', 'HIGH') ?>>HIGH</option>
										<option value="QRP"  <?php echo cbr_selected($cbr, 'category_power', 'QRP') ?>>QRP</option>
									</select>
								</div>
							</div>
						</fieldset>

						<!-- Optional category fields -->
						<fieldset class="mb-3">
							<legend class="h6"><?= __("Category (optional)") ?></legend>
							<div class="row g-2">
								<div class="col-sm-6">
									<label class="form-label" for="categorystation"><?= __("Station") ?></label>
									<select class="form-select form-select-sm" id="categorystation" name="categorystation">
										<option value="FIXED"            <?php echo cbr_selected($cbr, 'category_station', 'FIXED', 'FIXED') ?>>FIXED</option>
										<option value="DISTRIBUTED"      <?php echo cbr_selected($cbr, 'category_station', 'DISTRIBUTED') ?>>DISTRIBUTED</option>
										<option value="MOBILE"           <?php echo cbr_selected($cbr, 'category_station', 'MOBILE') ?>>MOBILE</option>
										<option value="PORTABLE"         <?php echo cbr_selected($cbr, 'category_station', 'PORTABLE') ?>>PORTABLE</option>
										<option value="ROVER"            <?php echo cbr_selected($cbr, 'category_station', 'ROVER') ?>>ROVER</option>
										<option value="ROVER-LIMITED"    <?php echo cbr_selected($cbr, 'category_station', 'ROVER-LIMITED') ?>>ROVER-LIMITED</option>
										<option value="ROVER-UNLIMITED"  <?php echo cbr_selected($cbr, 'category_station', 'ROVER-UNLIMITED') ?>>ROVER-UNLIMITED</option>
										<option value="EXPEDITION"       <?php echo cbr_selected($cbr, 'category_station', 'EXPEDITION') ?>>EXPEDITION</option>
										<option value="HQ"               <?php echo cbr_selected($cbr, 'category_station', 'HQ') ?>>HQ</option>
										<option value="SCHOOL"           <?php echo cbr_selected($cbr, 'category_station', 'SCHOOL') ?>>SCHOOL</option>
										<option value="EXPLORER"         <?php echo cbr_selected($cbr, 'category_station', 'EXPLORER') ?>>EXPLORER</option>
									</select>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="categorytransmitter"><?= __("Transmitter") ?></label>
									<select class="form-select form-select-sm" id="categorytransmitter" name="categorytransmitter">
										<option value="ONE"       <?php echo cbr_selected($cbr, 'category_transmitter', 'ONE', 'ONE') ?>>ONE</option>
										<option value="TWO"       <?php echo cbr_selected($cbr, 'category_transmitter', 'TWO') ?>>TWO</option>
										<option value="LIMITED"   <?php echo cbr_selected($cbr, 'category_transmitter', 'LIMITED') ?>>LIMITED</option>
										<option value="UNLIMITED" <?php echo cbr_selected($cbr, 'category_transmitter', 'UNLIMITED') ?>>UNLIMITED</option>
										<option value="SWL"       <?php echo cbr_selected($cbr, 'category_transmitter', 'SWL') ?>>SWL</option>
									</select>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="categorytime"><?= __("Time") ?></label>
									<select class="form-select form-select-sm" id="categorytime" name="categorytime">
										<option value=""        <?php echo cbr_selected($cbr, 'category_time', '') ?>>&mdash;</option>
										<option value="6-HOURS"  <?php echo cbr_selected($cbr, 'category_time', '6-HOURS') ?>><?= sprintf(__("%d Hours"), 6) ?></option>
										<option value="8-HOURS"  <?php echo cbr_selected($cbr, 'category_time', '8-HOURS') ?>><?= sprintf(__("%d Hours"), 8) ?></option>
										<option value="12-HOURS" <?php echo cbr_selected($cbr, 'category_time', '12-HOURS') ?>><?= sprintf(__("%d Hours"), 12) ?></option>
										<option value="24-HOURS" <?php echo cbr_selected($cbr, 'category_time', '24-HOURS') ?>><?= sprintf(__("%d Hours"), 24) ?></option>
									</select>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="categoryoverlay"><?= __("Overlay") ?></label>
									<select class="form-select form-select-sm" id="categoryoverlay" name="categoryoverlay">
										<option value=""           <?php echo cbr_selected($cbr, 'category_overlay', '') ?>>&mdash;</option>
										<option value="CLASSIC"    <?php echo cbr_selected($cbr, 'category_overlay', 'CLASSIC') ?>>CLASSIC</option>
										<option value="ROOKIE"     <?php echo cbr_selected($cbr, 'category_overlay', 'ROOKIE') ?>>ROOKIE</option>
										<option value="TB-WIRES"   <?php echo cbr_selected($cbr, 'category_overlay', 'TB-WIRES') ?>>TB-WIRES</option>
										<option value="YOUTH"      <?php echo cbr_selected($cbr, 'category_overlay', 'YOUTH') ?>>YOUTH</option>
										<option value="NOVICE-TECH"<?php echo cbr_selected($cbr, 'category_overlay', 'NOVICE-TECH') ?>>NOVICE-TECH</option>
										<option value="YL"         <?php echo cbr_selected($cbr, 'category_overlay', 'YL') ?>>YL</option>
										<option value="WIRE-ONLY"  <?php echo cbr_selected($cbr, 'category_overlay', 'WIRE-ONLY') ?>>WIRE-ONLY</option>
									</select>
								</div>
							</div>
						</fieldset>

						<!-- Extra fields -->
						<fieldset class="mb-3">
							<legend class="h6"><?= __("Additional Information") ?></legend>
							<div class="row g-2">
								<div class="col-sm-6">
									<label class="form-label" for="club"><?= __("Club") ?></label>
									<input type="text" class="form-control form-control-sm" id="club" name="club"
									       value="<?php echo htmlspecialchars($cbr['club'] ?? '') ?>">
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="location">
										<?= __("Location") ?>
										<i class="fas fa-question-circle text-muted ms-1"
										   data-bs-toggle="tooltip"
										   title="<?= __("ARRL section for USA/Canada, 'DX' for foreign stations. Required for IARU-HF, ARRL and CQ contests.") ?>"></i>
									</label>
									<input type="text" class="form-control form-control-sm" id="location" name="location"
									       value="<?php echo htmlspecialchars($cbr['location'] ?? '') ?>">
								</div>
								<div class="col-12">
									<label class="form-label" for="operators"><?= __("Operators") ?></label>
									<input type="text" class="form-control form-control-sm" id="operators" name="operators"
									       value="<?php echo htmlspecialchars($session_operators) ?>"
									       placeholder="<?php echo htmlspecialchars($session_info['station_callsign']) ?>">
								</div>
								<div class="col-12">
									<label class="form-label" for="soapbox"><?= __("Soapbox") ?></label>
									<textarea class="form-control form-control-sm" id="soapbox" name="soapbox" rows="2"><?php echo htmlspecialchars($cbr['soapbox'] ?? '') ?></textarea>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="certificate">
										<?= __("Certificate") ?>
										<i class="fas fa-question-circle text-muted ms-1"
										   data-bs-toggle="tooltip"
										   title="<?= __("Request a paper certificate from the contest sponsor if eligible.") ?>"></i>
									</label>
									<select class="form-select form-select-sm" id="certificate" name="certificate">
										<option value=""    <?php echo cbr_selected($cbr, 'certificate', '') ?>>&mdash;</option>
										<option value="YES" <?php echo cbr_selected($cbr, 'certificate', 'YES') ?>><?= __("Yes") ?></option>
										<option value="NO"  <?php echo cbr_selected($cbr, 'certificate', 'NO') ?>><?= __("No") ?></option>
									</select>
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="grid_export">
										<?= __("Grid in exchange?") ?>
										<i class="fas fa-question-circle text-muted ms-1"
										   data-bs-toggle="tooltip"
										   title="<?= __("Select Yes if the grid square was part of the contest exchange.") ?>"></i>
									</label>
									<select class="form-select form-select-sm" id="grid_export" name="grid_export">
										<option value="0" <?php echo cbr_selected($cbr, 'grid_export', '0', '0') ?>><?= __("No") ?></option>
										<option value="1" <?php echo cbr_selected($cbr, 'grid_export', '1') ?>><?= __("Yes") ?></option>
									</select>
									<div class="form-check mt-1">
										<input class="form-check-input" type="checkbox" id="grid_precision" name="grid_precision"
										       value="6" <?php echo (($cbr['grid_precision'] ?? '4') === '6') ? 'checked' : '' ?>>
										<label class="form-check-label small" for="grid_precision">
											<?= __("Use 6-char grid instead of 4-char") ?>
										</label>
									</div>
								</div>
							</div>
						</fieldset>

						<!-- Name / E-Mail / Address -->
						<fieldset class="mb-3">
							<legend class="h6"><?= __("Contact & Address") ?></legend>
							<div class="row g-2">
								<div class="col-sm-6">
									<label class="form-label" for="cbr_name"><?= __("Name") ?></label>
									<input type="text" class="form-control form-control-sm" id="cbr_name" name="cbr_name"
									       value="<?php echo htmlspecialchars($cbr['name'] ?? $user_name) ?>">
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="cbr_email"><?= __("E-Mail") ?></label>
									<input type="email" class="form-control form-control-sm" id="cbr_email" name="cbr_email"
									       value="<?php echo htmlspecialchars($cbr['email'] ?? $user_email) ?>">
								</div>
								<div class="col-12">
									<label class="form-label" for="address"><?= __("Street Address") ?></label>
									<input type="text" class="form-control form-control-sm" id="address" name="address"
									       value="<?php echo htmlspecialchars($cbr['address'] ?? '') ?>">
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="addresscity"><?= __("City") ?></label>
									<input type="text" class="form-control form-control-sm" id="addresscity" name="addresscity"
									       value="<?php echo htmlspecialchars($cbr['addresscity'] ?? '') ?>">
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="addressprovince"><?= __("State / Province") ?></label>
									<input type="text" class="form-control form-control-sm" id="addressprovince" name="addressprovince"
									       value="<?php echo htmlspecialchars($cbr['addressprovince'] ?? '') ?>">
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="addresspostalcode"><?= __("Postal Code") ?></label>
									<input type="text" class="form-control form-control-sm" id="addresspostalcode" name="addresspostalcode"
									       value="<?php echo htmlspecialchars($cbr['addresspostalcode'] ?? '') ?>">
								</div>
								<div class="col-sm-6">
									<label class="form-label" for="addresscountry"><?= __("Country") ?></label>
									<input type="text" class="form-control form-control-sm" id="addresscountry" name="addresscountry"
									       value="<?php echo htmlspecialchars($cbr['addresscountry'] ?? '') ?>">
								</div>
							</div>
						</fieldset>

						<button type="submit" class="btn btn-primary" <?php echo clubaccess_check(6) ? '' : 'disabled' ?>>
							<i class="fas fa-download me-2"></i><?= __("Download Cabrillo") ?>
						</button>
					</form>
				</div>
			</div>
	</div>
</div>

