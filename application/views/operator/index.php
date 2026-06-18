<?php
$op_call = $this->session->userdata('operator_callsign');
$account_call = $this->session->userdata('user_callsign');
$dismissible = ($op_call !== $account_call);
?>
<div class="modal fade bg-black bg-opacity-50" id="operatorModal" tabindex="-1" aria-labelledby="operatorLabel" aria-hidden="true"<?php if (!$dismissible): ?> data-bs-backdrop="static" data-bs-keyboard="false"<?php endif; ?>>
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="operatorLabel"><?= __("Operator Callsign") ?></h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="operator_callsign" class="form-label mb-4"><?= __("Please provide your personal call sign. This makes sure that QSOs are logged and exported with correct operator information."); ?></label>
                    <br>
                    <div class="row p-2">
                        <div class="col">
                            <p><?= __("Your personal Callsign:"); ?> </p>
                            <input type="text" class="form-control w-auto uppercase" id="operator_callsign" name="operator_callsign">
                            <div class="invalid-feedback">
                                <?= __("You have to provide your personal callsign."); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($dismissible): ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __("Cancel"); ?></button>
                <?php endif; ?>
                <button class="btn btn-primary" onclick="saveOperator()"><?= __("Save"); ?></button>
            </div>
        </div>
    </div>
</div>
