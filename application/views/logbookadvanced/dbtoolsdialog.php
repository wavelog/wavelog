<div class="container">
    <div class="row">
        <div class="col-md-6">
            <h5><?= __("Data Repair Tools") ?></h5>
            <div class="list-group">
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Fix CQ Zones") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Update missing or incorrect CQ zone information") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-secondary me-1" id="checkFixCqZonesBtn" onclick="checkFixCqZones()">
                            <?= __("Check") ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="fixCqZonesBtn" onclick="runFixCqZones()">
                            <?= __("Run") ?>
                        </button>
                    </div>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Fix ITU Zones") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Update missing or incorrect ITU zone information") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-secondary me-1" id="checkFixItuZonesBtn" onclick="checkFixItuZones()">
                            <?= __("Check") ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="fixItuZonesBtn" onclick="runFixItuZones()">
                            <?= __("Run") ?>
                        </button>
                    </div>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Fix Continent") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Update missing or incorrect continent information") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-secondary me-1" id="checkFixContinentBtn" onclick="checkFixContinent()">
                            <?= __("Check") ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="fixContinentBtn" onclick="runFixContinent()">
                            <?= __("Run") ?>
                        </button>
                    </div>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Fix State") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Update missing or incorrect state/province information") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-secondary me-1" id="checkFixStateBtn" onclick="checkFixState()">
                            <?= __("Check") ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="fixStateBtn" onclick="runFixState()">
                            <?= __("Run") ?>
                        </button>
                    </div>
                </div>

                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Update Distances") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Calculate and update distance information for QSOs") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-secondary me-1" id="checkUpdateDistancesBtn" onclick="checkUpdateDistances()">
                            <?= __("Check") ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="updateDistancesBtn" onclick="runUpdateDistances()">
                            <?= __("Run") ?>
                        </button>
                    </div>
                </div>

				<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Check QSOs missing DXCC data") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Identify QSOs that are missing DXCC information") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-secondary me-1" id="checkUpdateDistancesBtn" onclick="checkUpdateDistances()">
                            <?= __("Check") ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="updateDistancesBtn" onclick="runUpdateDistances()">
                            <?= __("Run") ?>
                        </button>
                    </div>
                </div>

				<div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1"><?= __("Re-check DXCC for all QSOs in the logbook") ?></h6>
                        <p class="mb-1 small text-muted"><?= __("Use Wavelog to determine DXCC for all QSOs. This will overwrite existing DXCC information.") ?></p>
                    </div>
                    <div class="d-flex nowrap">
                        <button type="button" class="btn btn-sm btn-secondary me-1" id="checkUpdateDistancesBtn" onclick="checkUpdateDistances()">
                            <?= __("Check") ?>
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="updateDistancesBtn" onclick="runUpdateDistances()">
                            <?= __("Run") ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>
		<div class="col-md-6 result"></div>


    </div>
</div>
