<script type="text/javascript">
    var user_id = <?php echo $this->session->userdata('user_id'); ?>;
</script>
<style>
	/* Ensure label sits above multiselect, not beside it (overrides span.multiselect-native-select) */
	span.multiselect-native-select {
		display: block !important;
		width: 100% !important;
	}
	/* Ensure label sits above multiselect, not beside it */
	span.multiselect-native-select .btn-group {
		width: 100% !important;
	}
	span.multiselect-native-select .multiselect {
		text-align: left !important;
	}
</style>
<div class="container px-3 px-lg-4 mt-3 mb-3">

	<!-- Page Title -->
	<h2><?= __("Satellite passes"); ?></h2>

	<?php if ($satellites) { ?>

	<!-- Station Cards Row -->
	<div class="row gx-2">

		<!-- Home Station Card -->
		<div class="col-lg-6">
			<div class="card">
				<div class="card-header py-2" role="button" data-bs-toggle="collapse" data-bs-target="#homeStationBody" aria-expanded="true" aria-controls="homeStationBody">
					<h6 class="mb-0"><i class="fas fa-home"></i> <?= __("Your station"); ?> <i class="fas fa-chevron-down float-end" style="font-size: 0.75rem; line-height: 1.5;"></i></h6>
				</div>
				<div class="collapse show" id="homeStationBody">
				<div class="card-body">
					<div class="row g-2 mb-2">
						<div class="col">
							<label class="form-label" for="yourgrid"><?= __("Gridsquare"); ?></label>
							<input class="form-control form-control-sm uppercase" id="yourgrid" type="text" name="gridsquare" value="<?= htmlspecialchars($activegrid); ?>">
						</div>
						<div class="col">
							<label class="form-label" for="minelevation"><?= __("Min. Satellite Elevation"); ?></label>
							<input class="form-control form-control-sm" id="minelevation" type="number" min="0" max="90" name="minelevation" value="0">
						</div>
					</div>

					<div class="row g-2 mb-2">
						<div class="col">
							<label class="form-label" for="minazimuth"><?= __("Min. Azimuth"); ?></label>
							<select class="form-select form-select-sm" id="minazimuth" name="minazimuth">
								<?php for ($i = 0; $i <= 350; $i += 10): ?>
									<option value="<?= $i ?>" <?= $i === 0 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="col">
							<label class="form-label" for="maxazimuth"><?= __("Max. Azimuth"); ?></label>
							<select class="form-select form-select-sm" id="maxazimuth" name="maxazimuth">
								<?php for ($i = 10; $i <= 360; $i += 10): ?>
									<option value="<?= $i ?>" <?= $i === 360 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
								<?php endfor; ?>
							</select>
						</div>
					</div>
				</div>
				</div>
			</div>
		</div>

		<!-- Sked Partner Card -->
		<div class="col-lg-6">
			<div class="card">
				<div class="card-header py-2" role="button" data-bs-toggle="collapse" data-bs-target="#skedPartnerBody" aria-expanded="true" aria-controls="skedPartnerBody">
					<h6 class="mb-0"><i class="fas fa-user"></i> <?= __("Sked partner"); ?> <i class="fas fa-chevron-down float-end" style="font-size: 0.75rem; line-height: 1.5;"></i></h6>
				</div>
				<div class="collapse show" id="skedPartnerBody">
				<div class="card-body" id="addskedpartner">
					<div class="row g-2 mb-2">
						<div class="col">
							<label class="form-label" for="skedgrid"><?= __("Gridsquare"); ?></label>
							<input class="form-control form-control-sm uppercase" id="skedgrid" type="text" name="skedgrid" value="">
						</div>
						<div class="col">
							<label class="form-label" for="minskedelevation"><?= __("Min. Satellite Elevation"); ?></label>
							<input class="form-control form-control-sm" id="minskedelevation" type="number" min="0" max="90" name="minskedelevation" value="0">
						</div>
					</div>

					<div class="row g-2 mb-2">
						<div class="col">
							<label class="form-label" for="minskedazimuth"><?= __("Min. Azimuth"); ?></label>
							<select class="form-select form-select-sm" id="minskedazimuth" name="minskedazimuth">
								<?php for ($i = 0; $i <= 350; $i += 10): ?>
									<option value="<?= $i ?>" <?= $i === 0 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
								<?php endfor; ?>
							</select>
						</div>
						<div class="col">
							<label class="form-label" for="maxskedazimuth"><?= __("Max. Azimuth"); ?></label>
							<select class="form-select form-select-sm" id="maxskedazimuth" name="maxskedazimuth">
								<?php for ($i = 10; $i <= 360; $i += 10): ?>
									<option value="<?= $i ?>" <?= $i === 360 ? 'selected' : '' ?>><?= $i ?> &deg;</option>
								<?php endfor; ?>
							</select>
						</div>
					</div>
				</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Settings Card -->
	<div class="card">
		<div class="card-header py-2" role="button" data-bs-toggle="collapse" data-bs-target="#settingsBody" aria-expanded="true" aria-controls="settingsBody">
			<h6 class="mb-0"><i class="fas fa-cog"></i> <?= __("Settings"); ?> <i class="fas fa-chevron-down float-end" style="font-size: 0.75rem; line-height: 1.5;"></i></h6>
		</div>
		<div class="collapse show" id="settingsBody">
			<div class="card-body">
				<div class="row g-2 mb-3">
					<div class="col-md-4">
						<label class="form-label" for="satlist"><?= __("Satellite"); ?>
							<i class="fa fa-question-circle" aria-hidden="true" data-bs-toggle="tooltip" title="<?= __("Only satellites with TLE data are shown here!"); ?>"></i>
						</label>
						<select id="satlist" multiple class="form-select form-select-sm" size="5">
							<?php foreach($satellites as $sat): ?>
								<option value="<?= $sat->satname == '' ? $sat->displayname : $sat->satname; ?>"><?= $sat->satname == '' ? $sat->displayname : $sat->satname.' ('.$sat->displayname.')'; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-md-4">
						<label class="form-label" for="date"><?= __("Date"); ?></label>
						<input name="date" id="date" type="date" class="form-control form-control-sm" value="<?= date('Y-m-d'); ?>">
					</div>
					<div class="col-md-4">
						<label class="form-label" for="mintime"><?= __("Min. time"); ?></label>
						<select class="form-select form-select-sm" id="mintime" name="mintime">
							<?php for ($i = 0; $i <= 24; $i += 1): ?>
								<option value="<?= $i ?>" <?= ($i == gmdate("H")) ? 'selected' : '' ?>><?= $i ?>:00</option>
							<?php endfor; ?>
						</select>
					</div>
				</div>

				<!-- Control Buttons -->
				<div class="d-flex justify-content-center gap-1">
					<button id="plot" type="button" name="searchpass" class="btn btn-primary px-4 me-1 ld-ext-right ld-ext-right-plot" onclick="searchpasses()"><i class="fas fa-search"></i> <?= __("Load predictions"); ?><div class="ld ld-ring ld-spin"></div></button>
					<div class="btn-group me-1">
						<button id="loadsettings" type="button" class="btn btn-secondary px-4 dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-download"></i> <?= __("Presets"); ?></button>
						<ul class="dropdown-menu">
							<li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#saveSettingsModal"> <i class="fas fa-save"></i> <?= __("Save current settings"); ?></button></li>
							<li><hr class="dropdown-divider"></li>
							<div id="passSettingsList"></div>
						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Results -->
	<!-- Results Card -->
	<div class="card" id="resultsCard" style="display: none;">
		<div class="card-header py-2">
			<h6 class="mb-0"><i class="fas fa-satellite"></i> <?= __("Pass Predictions"); ?></h6>
		</div>
		<div class="card-body" id="resultpasses"></div>
	</div>

	<!-- Save Settings Modal -->
	<div class="modal fade" id="saveSettingsModal" aria-labelledby="saveSettingsModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="saveSettingsModalLabel"><?= __("Save Settings"); ?></h5>
				</div>
				<div class="modal-body">
					<div class="mb-3">
						<label for="settingsName" class="form-label"><?= __("Settings Name"); ?></label>
						<input type="text" class="form-control" id="settingsName" name="setting_name" placeholder="<?= __("Enter a name"); ?>" />
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
					<button type="button" id="confirmSaveSettings" class="btn btn-primary" onclick="savePassSettings()"><i class="fas fa-save"></i> <?= __("Save"); ?></button>
				</div>
			</div>
		</div>
	</div>

	<?php } else { ?>
		<div class="alert alert-warning"><?= __("No TLE information detected. Please update TLE's."); ?></div>
	<?php } ?>

</div>

<script>
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(header) {
	var target = document.querySelector(header.dataset.bsTarget);
	if (target) {
		var icon = header.querySelector('.fa-chevron-down');
		if (icon) {
			target.addEventListener('show.bs.collapse', function() {
				icon.style.transform = 'rotate(0deg)';
			});
			target.addEventListener('hidden.bs.collapse', function() {
				icon.style.transform = 'rotate(180deg)';
			});
		}
	}
});
</script>
