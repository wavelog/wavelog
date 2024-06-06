<form method="post" class="col-md">
    <div class="mb-3 row">
        <label for="gridlabel"><?php echo __("Include Grid?"); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="gridlabel" id="gridlabel">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="reference"><?php echo __("Include reference? (SIG, SOTA, POTA, IOTA, WWFF; If available in location)"); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="reference" id="reference">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="via"><?php echo __("Include Via"); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="via" id="via">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="startat"><?php echo __("Start printing at?"); ?></label>
        <div class="d-flex align-items-center">
            <input class="form-control input-group-sm" type="number" id="startat" name="startat" value="1">
        </div>
    </div>
    <div class="text-start">
        <button type="button" id="button1id" name="button1id" class="btn btn-primary ld-ext-right" onclick="printlabel();"><?php echo __("Print"); ?></button>
    </div>
</form>
