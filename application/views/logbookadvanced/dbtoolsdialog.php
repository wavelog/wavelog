<div class="container-fluid">
    <div class="row">
        <div class="col-md-5">
            <h5><?= __("Data Repair Tools") ?>
			<a href="https://docs.wavelog.org/user-guide/logbook/advanced-logbook/#database-tools-dbtools" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info me-1 ld-ext-right">
				<?= __("Wiki Help") ?></a>
			</h5>
			<p class="mb-3 alert-danger"><?= __("Warning. This tool can be dangerous to your data, and should only be used if you know what you are doing.") ?></p>

			<!-- Station Location Selector -->
			<div class="card mb-3 border-success">
				<div class="card-body bg-light">
					<div class="d-flex align-items-center">
						<div class="flex-grow-1">
							<label for="station_id" class="form-label fw-bold text-success mb-1">
								<i class="fas fa-broadcast-tower"></i> <?= __("Station Location") ?>
							</label>
							<select id="dbtools_station_id" name="station_profile" class="form-select">
								<option value="All"><?= __("All Station Locations") ?></option>
								<?php foreach ($station_profile->result() as $station) { ?>
								<option value="<?php echo $station->station_id; ?>">
									<?= __("Callsign"); ?>: <?php echo $station->station_callsign; ?> (<?php echo $station->station_profile_name; ?>)
								</option>
								<?php } ?>
							</select>
						</div>
					</div>
				</div>
			</div>
            <div class="list-group">
				<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Check all QSOs in the logbook for incorrect CQ Zones") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Use Wavelog to determine CQ Zone for all QSOs.") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkIncorrectCqZonesBtn" onclick="checkIncorrectCqZones()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>
				<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Check all QSOs in the logbook for incorrect ITU Zones") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Use Wavelog to determine ITU Zone for all QSOs.") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkIncorrectItuZonesBtn" onclick="checkIncorrectItuZones()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>
				<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Check Gridsquares") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Check gridsquares that does not match the DXCC") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkIncorrectGridsquaresBtn" onclick="checkIncorrectGridsquares()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Fix Continent") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Update missing or incorrect continent information") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkFixContinentBtn" onclick="checkFixContinent()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Fix State") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Update missing state/province information") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkFixStateBtn" onclick="checkFixState()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Update Distances") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Calculate and update distance information for QSOs") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkUpdateDistancesBtn" onclick="checkUpdateDistances()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>
				<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Check all QSOs in the logbook for incorrect DXCC") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Use Wavelog to determine DXCC for all QSOs.") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkDxccBtn" onclick="checkDxcc()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>
				<?php if (($this->config->item('callbook_batch_lookup') ?? true) && $this->config->item('callbook')): ?>
				<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Lookup QSOs with missing grid in callbook") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Use callbook lookup to set gridsquare") ?></p>
						<p class="mb-1 small alert-danger"><?= __("This is limited to 150 callsigns for each run!") ?></p>
                    </div>
                    <div class="d-flex nowrap">
						<button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkGridsBtn" onclick="checkGrids()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>
				<?php endif; ?>
				<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Check IOTA against DXCC") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Use Wavelog to check IOTA against DXCC") ?></p>
                    </div>
                    <div class="d-flex nowrap">
						<button type="button" class="btn btn-sm btn-success me-1 ld-ext-right" id="checkIotaBtn" onclick="checkIota()">
                            <?= __("Check") ?><div class="ld ld-ring ld-spin"></div>
                        </button>
                    </div>
                </div>

            </div>
        </div>
		<div class="col-md-7 result"></div>


    </div>
</div>
