<style>
    .option-item {
        display: grid;
        grid-template-columns: 32px 1fr auto;
        align-items: start;
        gap: 0.5rem;
    }
    .option-item .switch-container {
        margin-top: -0.25rem;
    }
</style>

<form method="post" class="col-md">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="option-item mb-3">
                <i class="fas fa-broadcast-tower text-primary mt-1"></i>
                <div>
                    <label for="mycall" class="form-label fw-bold mb-0">
                        <?= __("Include my call?"); ?>
                    </label>
                </div>
                <div class="form-check form-switch switch-container">
                    <input class="form-check-input" type="checkbox" name="mycall" id="mycall" style="width: 3em; height: 1.5em;">
                </div>
            </div>

            <div class="option-item mb-3">
                <i class="fas fa-map-marker-alt text-success mt-1"></i>
                <div>
                    <label for="gridlabel" class="form-label fw-bold mb-0">
                        <?= __("Include Grid?"); ?>
                    </label>
                </div>
                <div class="form-check form-switch switch-container">
                    <input class="form-check-input" type="checkbox" name="gridlabel" id="gridlabel" style="width: 3em; height: 1.5em;">
                </div>
            </div>

            <div class="option-item mb-3">
                <i class="fas fa-mountain text-info mt-1"></i>
                <div>
                    <label for="reference" class="form-label fw-bold mb-0">
                        <?= __("Include reference? (SIG, SOTA, POTA, IOTA, WWFF; If available in location)"); ?>
                    </label>
                </div>
                <div class="form-check form-switch switch-container">
                    <input class="form-check-input" type="checkbox" name="reference" id="reference" style="width: 3em; height: 1.5em;">
                </div>
            </div>

            <div class="option-item mb-3">
                <i class="fas fa-share-alt text-warning mt-1"></i>
                <div>
                    <label for="via" class="form-label fw-bold mb-0">
                        <?= __("Include Via"); ?>
                    </label>
                </div>
                <div class="form-check form-switch switch-container">
                    <input class="form-check-input" type="checkbox" name="via" id="via" style="width: 3em; height: 1.5em;">
                </div>
            </div>

            <div class="option-item mb-3">
                <i class="fas fa-envelope text-danger mt-1"></i>
                <div>
                    <label for="qslmsg" class="form-label fw-bold mb-0">
                        <?= __("Include QSLMSG"); ?>
                    </label>
                </div>
                <div class="form-check form-switch switch-container">
                    <input class="form-check-input" type="checkbox" name="qslmsg" id="qslmsg" style="width: 3em; height: 1.5em;">
                </div>
            </div>

            <div class="option-item mb-3">
                <i class="fas fa-heart text-danger mt-1"></i>
                <div>
                    <label for="tnxmsg" class="form-label fw-bold mb-0">
                        <?= __("Include TNX message"); ?>
                    </label>
                </div>
                <div class="form-check form-switch switch-container">
                    <input class="form-check-input" type="checkbox" name="tnxmsg" id="tnxmsg" checked style="width: 3em; height: 1.5em;">
                </div>
            </div>

            <div class="option-item mb-3">
                <i class="fas fa-check-circle text-success mt-1"></i>
                <div>
                    <label for="markprinted" class="form-label fw-bold mb-0">
                        <?= __("Mark QSL as printed"); ?>
                    </label>
                </div>
                <div class="form-check form-switch switch-container">
                    <input class="form-check-input" type="checkbox" name="markprinted" id="markprinted" style="width: 3em; height: 1.5em;">
                </div>
            </div>

            <hr class="my-4">

            <div class="mb-3">
                <label for="startat" class="form-label fw-bold d-flex align-items-center">
                    <i class="fas fa-hashtag me-2 text-secondary" style="width: 20px;"></i>
                    <?= __("Start printing at?"); ?>
                </label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-sort-numeric-up"></i></span>
                    <input class="form-control" type="number" id="startat" name="startat" value="1" min="1">
                </div>
                <small class="form-text text-muted"><?= __("Enter the starting position for label printing"); ?></small>
            </div>
        </div>
    </div>
</form>
