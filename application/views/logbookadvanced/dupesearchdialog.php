<form method="post" class="dupe-search-dialog">
    <p class="mb-3">
        <i class="fas fa-search me-2"></i><?= __("Search for duplicates using:"); ?>
    </p>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="border border-primary rounded h-100">
                <div class="bg-primary text-white px-3 py-2 rounded-top">
                    <div class="form-check form-check-lg mb-0">
                        <input class="form-check-input" type="checkbox" name="date_check" id="date_check" checked>
                        <label class="form-check-label fw-semibold" for="date_check">
                            <i class="fas fa-calendar-alt me-2"></i><?= __("Date"); ?>
                        </label>
                    </div>
                </div>
                <div class="p-3">
                    <div class="d-block mb-2"><?= __("Match QSOs within the selected time window of each other"); ?></div>
                    <div class="px-1" id="dupe_time_group">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted"><i class="fas fa-clock me-1"></i><?= __("Time window"); ?></span>
                            <span class="badge bg-primary fs-6" id="dupe_time_display">30 min</span>
                        </div>
                        <input type="range" class="form-range dupe-time-slider" id="dupe_time" name="dupe_time" min="1" max="60" step="1" value="30">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border border-success rounded h-100">
                <div class="bg-success text-white px-3 py-2 rounded-top">
                    <div class="form-check form-check-lg mb-0">
                        <input class="form-check-input" type="checkbox" name="mode_check" id="mode_check" checked>
                        <label class="form-check-label fw-semibold" for="mode_check">
                            <i class="fas fa-broadcast-tower me-2"></i><?= __("Mode"); ?>
                        </label>
                    </div>
                </div>
                <div class="p-3">
                    <div class="d-block"><?= __("Match QSOs with the same mode (SSB, CW, FM, etc.)"); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border border-warning rounded h-100">
                <div class="bg-warning text-white px-3 py-2 rounded-top">
                    <div class="form-check form-check-lg mb-0">
                        <input class="form-check-input" type="checkbox" name="band_check" id="band_check" checked>
                        <label class="form-check-label fw-semibold" for="band_check">
                            <i class="fas fa-wave-square me-2"></i><?= __("Band"); ?>
                        </label>
                    </div>
                </div>
                <div class="p-3">
                    <div class="d-block"><?= __("Match QSOs on the same band"); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border border-info rounded h-100">
                <div class="bg-info text-white px-3 py-2 rounded-top">
                    <div class="form-check form-check-lg mb-0">
                        <input class="form-check-input" type="checkbox" name="satellite_check" id="satellite_check">
                        <label class="form-check-label fw-semibold" for="satellite_check">
                            <i class="fas fa-satellite me-2"></i><?= __("Satellite"); ?>
                        </label>
                    </div>
                </div>
                <div class="p-3">
                    <div class="d-block"><?= __("Match QSOs using the same satellite"); ?></div>
                </div>
            </div>
        </div>
    </div>
</form>
