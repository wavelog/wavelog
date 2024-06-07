<form method="post" class="col-md" action="<?php echo site_url('labels/print/' . $stationid) ?>" target="_blank">
    <div class="mb-3 row">
        <label for="grid"><?= __("Include Grid?"); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="grid" id="grid">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="reference"><?= __("Include reference? (SIG, SOTA, POTA, IOTA, WWFF; If available in location)"); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="reference" id="reference">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="via"><?= __("Include Via (if filled)?"); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="via" id="via">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="startat"><?= __("Start printing at?"); ?></label>
        <div class="d-flex align-items-center">
            <input class="form-control input-group-sm" type="number" id="startat" name="startat" value="1">
        </div>
    </div>
    <div class="text-start">
        <button type="submit" id="button1id" name="button1id" class="btn btn-primary ld-ext-right"><?= __("Print"); ?></button>
    </div>
</form>