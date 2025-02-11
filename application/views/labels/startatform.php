<form method="post" id="pform" class="col-md" action="<?php echo site_url('labels/print/' . $stationid) ?>" target="_blank">
    <input type="hidden" name="sid2print" id="sid2print" value="<?php echo $stationid; ?>">
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
        <label for="qslmsg"><?= __("Include QSLMSG (if filled)?"); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="qslmsg" id="qslmsg">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="tnxmsg"><?= __("Include TNX message?"); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="tnxmsg" id="tnxmsg" checked>
        </div>
    </div>
    <div class="mb-3 row">
        <label for="startat"><?= __("Start printing at?"); ?></label>
        <div class="d-flex align-items-center">
            <input class="form-control input-group-sm" type="number" id="startat" name="startat" value="1">
        </div>
    </div>
</form>
