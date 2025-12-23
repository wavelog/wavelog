<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-search me-2"></i><?= __("Search for duplicates using:"); ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="post">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check form-check-lg border rounded p-3 bg-light h-100">
                        <input class="form-check-input" type="checkbox" name="date_check" id="date_check" checked>
                        <label class="form-check-label fw-semibold" for="date_check">
                            <i class="fas fa-calendar-alt me-2 text-primary"></i><?= __("Date"); ?>
                        </label>
                        <small class="d-block text-muted"><?= __("Match QSOs within 1800s (30min) of each other"); ?></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-check-lg border rounded p-3 bg-light h-100">
                        <input class="form-check-input" type="checkbox" name="mode_check" id="mode_check" checked>
                        <label class="form-check-label fw-semibold" for="mode_check">
                            <i class="fas fa-broadcast-tower me-2 text-success"></i><?= __("Mode"); ?>
                        </label>
                        <small class="d-block text-muted"><?= __("Match QSOs with the same mode (SSB, CW, FM, etc.)"); ?></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-check-lg border rounded p-3 bg-light h-100">
                        <input class="form-check-input" type="checkbox" name="band_check" id="band_check" checked>
                        <label class="form-check-label fw-semibold" for="band_check">
                            <i class="fas fa-wave-square me-2 text-warning"></i><?= __("Band"); ?>
                        </label>
                        <small class="d-block text-muted"><?= __("Match QSOs on the same band"); ?></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-check-lg border rounded p-3 bg-light h-100">
                        <input class="form-check-input" type="checkbox" name="satellite_check" id="satellite_check">
                        <label class="form-check-label fw-semibold" for="satellite_check">
                            <i class="fas fa-satellite me-2 text-info"></i><?= __("Satellite"); ?>
                        </label>
                        <small class="d-block text-muted"><?= __("Match QSOs using the same satellite"); ?></small>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
