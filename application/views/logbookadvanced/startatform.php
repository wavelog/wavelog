<form method="post" class="col-md">
    <div class="mb-3 row">
        <label for="gridlabel"><?php echo lang('filter_actions_print_include_grid'); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="gridlabel" id="gridlabel">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="reference"><?php echo lang('filter_actions_print_include_ref'); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="reference" id="reference">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="via"><?php echo lang('filter_actions_print_include_via'); ?></label>
        <div class="form-check-inline">
            <input class="form-check-input" type="checkbox" name="via" id="via">
        </div>
    </div>
    <div class="mb-3 row">
        <label for="startat"><?php echo lang('filter_actions_start_print'); ?></label>
        <div class="d-flex align-items-center">
            <input class="form-control input-group-sm" type="number" id="startat" name="startat" value="1">
        </div>
    </div>
    <div class="text-start">
        <button type="button" id="button1id" name="button1id" class="btn btn-primary ld-ext-right" onclick="printlabel();"><?php echo lang('filter_actions_print'); ?></button>
    </div>
</form>
